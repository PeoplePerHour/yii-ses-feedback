<?php
/**
 * A Mock SQS Handler used so we can run unit tests without a connection to Amazon SQS.
 */
class ASesFeedbackHandlerMock extends ASesFeedbackHandler
{
    /**
     * @var array the messages that should be submitted to SQS (but are stored locally)
     */
    public $_mockQueue=array();

    /**
     * Get the Mock AWS SDK SQS singleton instance.
     *
     * @return Mock AWS SQS singleton instance.
     */
    public function getInstance()
    {
        if ($this->_sqs === null) {
            // Instead of returning a real SQS queue, We have embedded a mock one which mirros the same API.
            $this->_sqs = $this;
        }

        return $this->_sqs;
    }

    ////////////////////////////////////////////////////////////////////
    /////////////       HERE WE'LL PUT MOCK SQS METHODS   //////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Mock the SQS sendMessage() method
     *
     * @param mixed $options
     */
    public function sendMessage($options=array())
    {
        $mockSqsMsgWrapper = array(
          "Type"      => "Notification",
          "MessageId" => uniqid(),
          "TopicArn"  => "arn:aws:sns:us-east-1:298029549985:blah",
          "Message"   => $options['MessageBody'],
        );
        $this->_mockQueue[] = json_encode($mockSqsMsgWrapper);
    }

    /**
     * Mock the SQS receiveMessage() method.
     *
     * @param mixed $options
     */
    public function receiveMessage($options=array())
    {
        $msgs = array();
        $numWanted = isset($options['MaxNumberOfMessages']) ? $options['MaxNumberOfMessages'] : 1;

        for ($i=0;$i<$numWanted;$i++) {
            $msg = array_shift($this->_mockQueue);
            if ($msg) {
                $msgs[] = array(
                    'Body' => $msg,
                );
            }
        }
        return array('Messages' => $msgs);
    }

    /**
     * Mock the SQS getQueueUrl function
     */
    public function getQueueUrl($options = array())
    {
        return new ASesFeedbackMockGetQueueUrlResponse();
    }

    /**
     * Mock the SQS getQueueAttributes function - Often used to find the size of a queue
     */
    public function getQueueAttributes($options = array())
    {
        if (isset($options['AttributeNames']) && isset($options['AttributeNames']['ApproximateNumberOfMessages'])) {
            return count($this->_mockQueue);
        } else {
            throw new Exception('Not implemented');
        }
    }
}

class ASesFeedbackMockGetQueueUrlResponse
{
    function get($arg)
    {
        return 'unittest_mock_queue';
    }
}