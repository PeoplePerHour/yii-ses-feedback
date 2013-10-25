<?php
/**
 * Unit Test the ASesFeedback extension
 */
class ASesFeedbackTest extends CTestCase
{
    public $msgs;

    // This method is called before the first test of this test class is run.
    public function setUp()
    {
        // Load our test messages
        $this->msgs = require dirname(__FILE__).'/../fixtures/AfeedbackNotifications.php';

        parent::setUp();
    }

    public function testDefaults()
    {
        foreach (Yii::app()->sesFeedback->getHandlers() as $name => $hander) {
            $this->assertEquals('us-east-1', $hander->region, 'Expected the unit tests to use the default region.');
            $this->assertEquals('bounces_and_complaints', $hander->queueName, 'Expected the unit tests to use the default queue name.');
        }
    }

    /**
     * Test Complaint Processing
     */
    public function testComplaint()
    {
        $sesFeedback = Yii::app()->sesFeedback;

        foreach (array($this->msgs['complaintMessage1'], $this->msgs['complaintMessage2']) as $msg) {
            $this->assertTrue($sesFeedback->processComplaint($msg));
        }
    }

    /**
     * Test Bounce Processing
     */
    public function testBouncePermanent()
    {
        $sesFeedback = Yii::app()->sesFeedback;

        foreach (array($this->msgs['bounceMessage1'], $this->msgs['bounceMessage2'],$this->msgs['bounceMessage3']) as $msg) {
            $this->assertTrue($sesFeedback->processComplaint($msg));
        }
    }

    /**
     * Test Bounce Processing - Extra listener
     */
    public function testBounceExtraListener()
    {
        $sesFeedback = Yii::app()->sesFeedback;

        $extraListener = new testASesFeedbackBounceEventListener();
        $this->assertEquals(0, $extraListener->getCounter(), 'Expected the counter to start at zero');

        $sesFeedback->processBounce($this->msgs['bounceMessage1']);
        $this->assertEquals(1, $extraListener->getCounter(), 'Expected the counter to have incremented once.');

        for ($i=0;$i<10;$i++) {
            $sesFeedback->processBounce($this->msgs['bounceMessage1']);
        }
        $this->assertEquals(11, $extraListener->getCounter(), 'Expected the counter to have incremented 10 more times.');
    }

    /**
     * Test Complaint Processing - Extra listener
     */
    public function testComplaintExtraListener()
    {
        $sesFeedback = Yii::app()->sesFeedback;

        $extraListener = new testASesFeedbackComplaintEventListener();
        $this->assertEquals(0, $extraListener->getCounter(), 'Expected the counter to start at zero');

        $sesFeedback->processComplaint($this->msgs['complaintMessage1']);
        $this->assertEquals(1, $extraListener->getCounter(), 'Expected the counter to have incremented once.');

        for ($i=0;$i<10;$i++) {
            $sesFeedback->processComplaint($this->msgs['complaintMessage1']);
        }
        $this->assertEquals(11, $extraListener->getCounter(), 'Expected the counter to have incremented 10 more times.');
    }

    /**
     * Test that we can successfully receive feedback notifications from the queue
     */
    public function testSesFeedbackCommand()
    {
        $sesFeedback = Yii::app()->sesFeedback;

        $this->assertEquals(1, $sesFeedback->getHandlers()->getCount(), 'Expected a handler to be defined in the test config');

        // Set $hander - Any one in the list will do
        foreach ($sesFeedback->getHandlers() as $name => $hander);

        // Put a bounce into the queue
        $msg1=$this->msgs['bounceMessage1'];
        $hander->instance->sendMessage(array(
            'QueueUrl' => $hander->queueUrl,
            'MessageBody' => json_encode($msg1),
        ));

        // Put a complaint into the queue
        $msg2=$this->msgs['complaintMessage1'];
        $hander->instance->sendMessage(array(
            'QueueUrl' => $hander->queueUrl,
            'MessageBody' => json_encode($msg2),
        ));


        // Fetch feedback notification messages from the queue
        $result = $hander->instance->receiveMessage(array(
            'QueueUrl' => $hander->queueUrl,
            'MaxNumberOfMessages'=>10
        ));

        // Check the 1st one is a Bounce
        $res1 = $result['Messages'][0];
        $sqsMsgBody1 = json_decode($res1['Body'], true);
        $msg1 = json_decode($sqsMsgBody1['Message'], true);
        $this->assertEquals('Bounce', $msg1['notificationType'], 'Expected the message to be a bounce.');

        // Check the 2nd one is a Complaint
        $res2 = $result['Messages'][1];
        $sqsMsgBody2 = json_decode($res2['Body'], true);
        $msg2 = json_decode($sqsMsgBody2['Message'], true);
        $this->assertEquals('Complaint', $msg2['notificationType'], 'Expected the message to be a bounce.');
    }

    /**
     * Test the onBeforeWorkOff event.
     */
    public function testBeforeWorkOffEvent()
    {
        $sesFeedback = Yii::app()->sesFeedback;

        // Check that by default WorkOff() is not blocked
        $this->assertTrue($sesFeedback->beforeWorkOff(), 'Expected workOff() not to be blocked by default.');

        // Attach a event handler with does nothing at all
        Yii::app()->sesFeedback->attachEventHandler('onBeforeWorkOff', function ($eventA) {$noop = true;});

        $this->assertTrue($sesFeedback->beforeWorkOff(), 'Expected workOff() still not to be blocked.');

        // Attach a event handler that will block WorkOff()  (use the alternative syntax)
        Yii::app()->sesFeedback->onBeforeWorkOff = function ($eventA) {$eventA->isValid = false;};

        // Check that now WorkOff() is blocked
        $this->assertFalse($sesFeedback->beforeWorkOff(), 'Expected to block workOff().');
    }

    /**
     * Test the onBeforeProcessMsg event.
     */
    public function testbeforeProcessMsgEvent()
    {
        $sesFeedback = Yii::app()->sesFeedback;

        // Check that by default processMsg()  is not blocked
        $this->assertTrue($sesFeedback->beforeProcessMsg(), 'Expected processMsg() not to be blocked by default.');

        // Create a object that attaches to onBeforeProcessMsg and blocks it
        $haltingListener2 = new testASesFeedbackHaltingEventListener();

        // Check that now processMsg() is blocked
        $this->assertFalse($sesFeedback->beforeProcessMsg(), 'Expected to block processMsg().');
    }
}

/**
 * Class used to test using SesFeedback event handling - Test callback function
 */
class testASesFeedbackBounceEventListener
{
    private $counter = 0;

    public function __construct()
    {
        // Attach the reactToBounce() method to the onSesBounce event.
        Yii::app()->sesFeedback->onSesBounce = array($this, "reactToBounce");
    }

    public function reactToBounce($event)
    {
        $this->counter++;
    }

    public function getCounter()
    {
        return $this->counter;
    }
}

/**
 * Class used to test using SesFeedback event handling - Test anonymous function
 */
class testASesFeedbackComplaintEventListener
{
    private static $counter = 0;

    public function __construct()
    {
        // Attach an anonymous function to the onSesComplaint event.
        Yii::app()->sesFeedback->onSesComplaint = function ($event) {self::$counter++;};
    }

    public function getCounter()
    {
        return self::$counter;
    }
}

/**
 * Class used to test using SesFeedback event handling (onBeforeWorkOff and onBeforeProcessMsg)
 */
class testASesFeedbackHaltingEventListener
{
    public function __construct()
    {
        // Attach an anonymous function to the onBeforeProcessMsg event that causes peforeProcessMsg() to be blocked
        Yii::app()->sesFeedback->onBeforeProcessMsg = function ($eventB) {$eventB->isValid = false;};
    }
}