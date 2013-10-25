<?php
/**
 * ExampleSesFeedbackCommand - A Yii command that processes a SQS queue to work off bounce and complaint messages.
 *
 * The parent Object raises the necessary events - we listen to them and add a bounce record to the database each time.
 *
 */
class ExampleSesFeedbackCommand extends SesFeedbackCommand
{
    /**
     * Initialize the command object so we can attach listeners to the parent Object events
     */
    public function init()
    {
        parent::init();

        // Attach the reactToBounce() method to the onSesBounce event.
        Yii::app()->sesFeedback->onSesBounce = array($this, "reactToBounce");

        // Attach the reactToComplaint() method to the onSesComplaint event.
        Yii::app()->sesFeedback->onSesComplaint = array($this, "reactToComplaint");
    }

    /**
     * Fetch feedback notifications from SQS and process them.
     * We override the parent just so that we can auto-schedule it with phpdoc-crontab
     *
     * @param Boolean $maxNum               Set if you want to limit processing to a certain number of messages.
     * @param Boolean $leaveFailuresInQueue Set to true if you don't want to remove msgs that failed to process from the SQS queue. Useful when testing.
     *
     * @cron 17 * * * *
     * @cron-tags live
     */
    public function actionIndex($maxNum=1000,$leaveFailuresInQueue=false)
    {
        return parent::actionIndex($maxNum,$leaveFailuresInQueue);
    }

    /**
     * Process a Email bounce event.
     */
    public function reactToBounce(CEvent $event)
    {
        $msg = $event->params['msg'];

        // Extract the reason for the bounce
        $reason = 'bounceType: '.$msg['bounce']['bounceType'].'. ';       // e.g. 'Permanent' or 'Transient'.
        $reason .= 'bounceSubType: '.$msg['bounce']['bounceSubType'].'.'; // e.g. 'Undetermined','General','Suppressed','NoEmail','MailboxFull','MessageToolarge','ContentRejected','AttachmentRejected'

        foreach($msg['bounce']['bouncedRecipients'] as $r) {

            $email = $r['emailAddress'];

            // If this bounce notification has a delivery status notification (DSN), fetch it:
            if (isset($r['diagnosticCode'])) {
                $reason .= ' diagnosticCode: '.$r['diagnosticCode'];
            }

            // Add your own application logic here.
            // Example:
            // Add a new bounce record to the database
            EmailBounce::model()->addNewBounce(
                $email,
                substr($msg['bounce']['bounceType'],0,5),
                $reason,
                date('Y-m-d H:i:s', strtotime($msg['mail']['timestamp']))
            );
        }
    }

    /**
     * Process a Email complaint event.
     */
    public function reactToComplaint(CEvent $event)
    {
        Yii::log('Complaint recieved - We currently do not process Complaints', 'info', self::LOGCAT);

        // Add your own application logic here.
    }
}
