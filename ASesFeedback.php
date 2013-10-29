<?php
/**
 * This Yii Component defines and raises Yii events realated to Amazon SES feedback notifications.
 */
class ASesFeedback extends CApplicationComponent
{
    /**
     * @var CMap Queue Handlers.
     **/
    private $_handlers;


    /**
     * Set all the available handlers for this ASesFeedback instance. Set occurs only one time.
     *
     * @param array $handlers configuration for ASesFeedbackHandler implementations
     */
    protected function setHandlers($handlers)
    {
        if( !is_array($handlers)) {
            throw new CException(__CLASS__.'::$handlers must be an array. '.gettype($handlers).' given.');
        }
        if ($this->_handlers===null) {
            $this->_handlers = new CMap();
            foreach($handlers as $key => $config) {
                $handler = Yii::createComponent($config);

                if (!($handler instanceof ASesFeedbackHandler)) {
                    throw new CException('Handler was the wrong class type');
                }

                $this->_handlers[$key] = $handler; // Add handler to our map.
            }
            $this->_handlers->readOnly = true;
            return true;
        }
        throw new CException(__CLASS__.'::$handlers can only be set on component initialization');
    }

    /**
     * @return CMap All the configured queue handlers.
     **/
    public function getHandlers()
    {
        return $this->_handlers;
    }

    /**
     * This event is raised when a SES Complaint notification is processed.
     *
     * The "on" prefixed to this method name magically defines this as an event.
     * Note: In order to attach a function to an object you need to be able to access the object from other areas of the application.
     *
     * @param CEvent $event The event object
     */
    public function onSesComplaint($event)
    {
        // Define the event to allow Yii to attach functions to it.
        $this->raiseEvent('onSesComplaint',$event);
    }

    /**
     * This event is raised when a SES Bounce notification is processed.
     *
     * The "on" prefixed to this method name magically defines this as an event.
     * Note: In order to attach a function to an object you need to be able to access the object from other areas of the application.
     *
     * @param CEvent $event The event object
     */
    public function onSesBounce($event)
    {
        // Define the event to allow Yii to attach functions to it.
        $this->raiseEvent('onSesBounce',$event);
    }

    /**
     * This event is raised before We start working off the queue.
     * By setting {@link CModelEvent::isValid} to be false, the normal {@link workOff()} process will be stopped.
     *
     * The "on" prefixed to this method name magically defines this as an event.
     * Note: In order to attach a function to an object you need to be able to access the object from other areas of the application.
     *
     * @param CModelEvent $event The event parameter
     */
    public function onBeforeWorkOff($event)
    {
        // Define the event to allow Yii to attach functions to it.
        $this->raiseEvent('onBeforeWorkOff',$event);
    }

    /**
     * This event is raised before we start processing a message from the queue.
     * By setting {@link CModelEvent::isValid} to be false, the normal {@link workOff()} process will be stopped.
     *
     * The "on" prefixed to this method name magically defines this as an event.
     * Note: In order to attach a function to an object you need to be able to access the object from other areas of the application.
     *
     * @param CModelEvent $event The event parameter
     */
    public function onBeforeProcessMsg($event)
    {
        // Define the event to allow Yii to attach functions to it.
        $this->raiseEvent('onBeforeProcessMsg',$event);
    }

    /**
     * This method is invoked before working off the feedback queue.
     * The default implementation raises the {@link onBeforeWorkOff} event.
     *
     * @return boolean whether the working off should be executed. Defaults to true.
     */
    public function beforeWorkOff()
    {
        if ($this->hasEventHandler('onBeforeWorkOff')) {
            $event=new CModelEvent($this);
            $this->onBeforeWorkOff($event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * This method is invoked before processing a feedback notification message.
     * The default implementation raises the {@link onBeforeProcessMsg} event.
     *
     * @return boolean whether the processing should continue. Defaults to true.
     */
    public function beforeProcessMsg()
    {
        if ($this->hasEventHandler('onBeforeProcessMsg')) {
            $event=new CModelEvent($this);
            $this->onBeforeProcessMsg($event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * This method is invoked when we have a SES Complaint notification to process.
     * The default implementation raises the {@link onSesComplaint} event.
     *
     * @param Array The SES Complaint Message
     *
     * @return boolean whether the processing was successful.
     */
    public function processComplaint($msg)
    {
        if ($this->hasEventHandler('onSesComplaint')) {
            $event=new CModelEvent($this, array('msg'=>$msg));
            $this->onSesComplaint($event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * This method is invoked when we have a SES Bounce notification to process.
     * The default implementation raises the {@link onSesBounce} event.
     *
     * @param Array The SES Bounce Message
     *
     * @return boolean whether the processing was successful.
     */
    public function processBounce($msg)
    {
        if ($this->hasEventHandler('onSesBounce')) {
            $event=new CModelEvent($this, array('msg'=>$msg));
            $this->onSesBounce($event);
            return $event->isValid;
        }

        return true;
    }
}
