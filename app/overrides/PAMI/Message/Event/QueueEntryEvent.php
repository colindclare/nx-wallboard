<?php

namespace PAMI\Message\Event;

use PAMI\Message\Event\EventMessage;

class QueueEntryEvent extends EventMessage
{

    public function getQueue()
    {
        return $this->getKey('Queue');
    }

    public function getPosition()
    {
        return $this->getKey('Position');
    }

    public function getChannel()
    {
        return $this->getKey('Channel');
    }

    public function getUniqueID()
    {
        return $this->getKey('UniqueID');
    }

    public function getCallerIDNum()
    {
        return $this->getKey('CallerIDNum');
    }

    public function getCallerIDName()
    {
        return $this->getKey('CallerIDName');
    }

    public function getConnectedLineNum()
    {
        return $this->getKey('ConnectedLineNum');
    }

    public function getConnectedLineName()
    {
        return $this->getKey('ConnectedLineName');
    }

    public function getWait()
    {
        return $this->getKey('Wait');
    }

    public function getPriority()
    {
        return $this->getKey('Priority');
    }

}
