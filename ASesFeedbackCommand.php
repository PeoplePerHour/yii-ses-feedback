<?php
/**
 * ASesFeedbackCommand - A Yii command that processes a SQS queue to work off bounce and complaint messages.
 *
 * Yii events are raised so that a interested listener can react to the event as needed.
 *
 * The format of the SQS message is determined by Amazon.
 * There are some examples at @link http://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
 *
 *
 * Usage:
 *
 * sudo ./yiic asesfeedback
 *
 * or useful for testing on local:
 *
 * sudo ./yiic asesfeedback --maxNum=1 --leaveFailuresInQueue=1
 *
 */
class ASesFeedbackCommand extends CConsoleCommand
{
    /**
     * Logging category
     */
    const LOGCAT = 'application.commands.ASesFeedbackCommand';


    private $_countMsgs      = 0;
    private $_countBounce    = 0;
    private $_countComplaint = 0;

    /**
     * Initialize the command object.
     */
    public function init()
    {
        // Tell yii to flush the logs every message (instead of buffering for the default 10,000)
        // Without this, you cannot use the log to find out where the script has got to in it's current run.
        Yii::getLogger()->autoFlush = 1;
        Yii::getLogger()->autoDump = true;

        parent::init();
    }

    /**
     * Fetch feedback notifications from SQS and process them.
     *
     * @param Boolean $maxNum               Set if you want to limit processing to a certain number of messages.
     * @param Boolean $leaveFailuresInQueue Set to true if you don't want to remove msgs that failed to process from the SQS queue. Useful when testing.
     */
    public function actionIndex($maxNum=1000,$leaveFailuresInQueue=false)
    {
        $sesFeedback = Yii::app()->sesFeedback;

        if ($sesFeedback->beforeWorkOff()) {
            // For each configured queue - work it off
            foreach ($sesFeedback->getHandlers() as $name => $queueHandler) {
                $this->workOff($queueHandler, $maxNum, $leaveFailuresInQueue);
            }
        }

        $this->printLine("Finished. Bounce count: ".$this->_countBounce.", complaint count: ".$this->_countComplaint);
    }

    /**
     * Fetch feedback notifications from SQS and process them.
     *
     * @param Object  $handler              The SQS Queue handler to process.
     * @param Boolean $maxNum               Set if you want to limit processing to a certain number of messages.
     * @param Boolean $leaveFailuresInQueue Set to true if you don't want to remove msgs that failed to process from the SQS queue. Useful when testing.
     */
    public function workOff($handler, $maxNum, $leaveFailuresInQueue)
    {
        $sesFeedback = Yii::app()->sesFeedback;

        $this->printLine("Number of messages in the ".$handler->queueName." SQS: ".$handler->queueSize);

        // Get up to 10 messages from the feedback queue at a time
        while (
            $this->_countMsgs < $maxNum
            && $sesFeedback->beforeProcessMsg()
            && ($result = $handler->instance->receiveMessage(array('QueueUrl' => $handler->queueUrl,'MaxNumberOfMessages'=>10))) !== null
            && $result['Messages'] !== null
         ) {
            foreach ($result['Messages'] as $m) {
                // Decode the SQS message
                $sqsMsgBody = json_decode($m['Body'], true);
                $msg = json_decode($sqsMsgBody['Message'], true);

                if ($msg['notificationType'] == 'Complaint') {
                    $logText = "Processing Complaint for messageId ".$msg['mail']['messageId']."... ";

                    $status = $sesFeedback->processComplaint($msg);

                    $this->_countComplaint++;
                } else if ($msg['notificationType'] == 'Bounce') {
                    $logText =  "Processing Bounce for messageId ".$msg['mail']['messageId']."... ";

                    $status = $sesFeedback->processBounce($msg);

                    $this->_countBounce++;
                } else {
                    // Not a bounce or complaint.
                    // Example: AmazonSnsSubscriptionSucceeded - "You have successfully subscribed your Amazon SNS topic to receive 'Complaint' notifications from Amazon SES"

                    $logText = "Unknown message type: ".$msg['notificationType'];
                    if (isset($msg['mail']) && isset($msg['mail']['messageId'])) {
                        $logText .= " for messageId ".$msg['mail']['messageId'];
                    }
                    if (isset($msg['message'])) {
                        $logText .= ". Message: ".$msg['message'];
                    }
                    $logText .= "... ";
                    $status = false;
                }
                $statusString = $status ? "OK" : "FAILED";
                $this->_countMsgs++;

                // Log all bounces/complaints
                $this->printLine($logText.$statusString, CLogger::LEVEL_INFO);
                // Also log (with trace) the message so we can debug more easily)
                $this->printLine($logText.$statusString." - Msg: ".CVarDumper::dumpAsString($m), CLogger::LEVEL_TRACE);

                if ($status || !$leaveFailuresInQueue) {
                    // Now that this message has been processed, remove it from the queue.
                    $handler->instance->deleteMessage(array('QueueUrl' => $handler->queueUrl,'ReceiptHandle'=>$m['ReceiptHandle']));
                } else {
                    $leaveTxt = $leaveFailuresInQueue ? "Yes" : "No";
                    $this->printLine("Message NOT removed from queue. status: ".$statusString.". leaveFailuresInQueue: ".$leaveTxt.".");
                }
            }
        }
    }

    /**
     * Helper function to display process information to the console.
     * @param string $line  The text to echo
     * @param string $level The Yii log level, error, info, trace, etc. Defaults to info.
     */
    protected function printLine($line, $level = 'info')
    {
        Yii::log($line, $level, self::LOGCAT);
        if ($level != CLogger::LEVEL_TRACE) {
            echo $line.PHP_EOL;
        }
    }
}
