<?php
/**
 * This Component is to handle a SQS queue being used for SES feedback notifications.
 *
 * The reason we have a separate class for the handlers is that there might be
 * multiple SQS's to handle. Some folks will have one queue for all notifications,
 * others will have one queue for bounces and another for complaints.
 */
class ASesFeedbackHandler extends CComponent
{
    /**
     * Logging category
     */
    const LOGCAT = 'application.components.ASesFeedbackHandler';

    /**
     * @var string AWS SQS access key (a.k.a. AWS_KEY).
     */
    public $accessKey;

    /**
     * @var string AWS SQS secret key (a.k.a. AWS_SECRET_KEY).
     */
    public $secretKey;

    /**
     * @var string The AWS region where this SQS queue lives
     */
    public $region;

    /**
     * @var string The SQS Queue Name that holds the feedback notification messages.
     */
    public $queueName;

    /**
     * @var Aws\Sqs\SqsClient AmazonSQS Singleton instance of SQS client.
     */
    protected $_sqs;

    /**
     * @var string The SQS Queue URL for this queue.
     */
    protected $_queueUrl;

    /*
     * Set some defaults, if wasn't already configured in the yii configuration.
     */
    public function init()
    {
        if ($this->region === null) {
            $this->region = 'us-east-1'; // Default region
        }

        if ($this->queueName === null) {
            $this->queueName = 'bounces_and_complaints'; // Default SQS name
        }
    }

    /**
     * Get the AWS SDK SQS singleton instance.
     *
     * @return AWS SQS singleton instance.
     */
    public function getInstance()
    {
        if ($this->_sqs === null) {
            if ($this->accessKey === null && $this->secretKey === null) {
                // If you don't specify the key and secret, then the SDK will use the IAM role of the machine for the credentials.
                $this->_sqs = Aws\Sqs\SqsClient::factory(['region'=>$this->region]);
            } else {
                $this->_sqs = Aws\Sqs\SqsClient::factory(['key'=>$this->accessKey,'secret'=>$this->secretKey,'region'=>$this->region]);
            }
        }

        return $this->_sqs;
    }

    /*
     * @return String URL of SQS queue, needed in many SQS API calls
     */
    public function getQueueUrl()
    {
        if ($this->_queueUrl === null) {
            // Do a SQS API call to get the Queue URL
            $this->_queueUrl = $this->instance->getQueueUrl(['QueueName'=>$this->queueName])->get('QueueUrl');
        }
        return $this->_queueUrl;
    }

    /*
     * @return Integer Number of items in the queue
     */
    public function getQueueSize()
    {
        // Do a SQS API call to get the "ApproximateNumberOfMessages" in this queue
        return $this->instance->getQueueAttributes(['QueueUrl'=>$this->queueUrl, 'AttributeNames'=>['ApproximateNumberOfMessages']])['Attributes']['ApproximateNumberOfMessages'];
    }
}
