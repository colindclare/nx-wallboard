<?php

namespace PAMI\Message\Event;

use PAMI\Message\Event\EventMessage;

class AgentRingNoAnswerEvent extends EventMessage
{

    public function getChannel()
    {
        return $this->getKey('Channel');
    }

    public function getChannelState()
    {
        return $this->getKey('ChannelState');
    }

    public function getChannelStateDesc()
    {
        return $this->getKey('ChannelStateDesc');
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

    public function getLanguage()
    {
        return $this->getKey('Language');
    }

    public function getAccountCode()
    {
        return $this->getKey('AccountCode');
    }

    public function getContext()
    {
        return $this->getKey('Context');
    }

    public function getExten()
    {
        return $this->getKey('Exten');
    }

    public function getPriority()
    {
        return $this->getKey('Priority');
    }

    public function getUniqueid()
    {
        return $this->getKey('Uniqueid');
    }

    public function getLinkedid()
    {
        return $this->getKey('Linkedid');
    }

    public function getDestChannel()
    {
        return $this->getKey('DestChannel');
    }

    public function getDestChannelState()
    {
        return $this->getKey('DestChannelState');
    }

    public function getDestChannelStateDesc()
    {
        return $this->getKey('DestChannelStateDesc');
    }

    public function getDestCallerIDNum()
    {
        return $this->getKey('DestCallerIDNum');
    }

    public function getDestCallerIDName()
    {
        return $this->getKey('DestCallerIDName');
    }

    public function getDestConnectedLineNum()
    {
        return $this->getKey('DestConnectedLineNum');
    }

    public function getDestConnectedLineName()
    {
        return $this->getKey('DestConnectedLineName');
    }

    public function getDestLanguage()
    {
        return $this->getKey('DestLanguage');
    }

    public function getDestAccountCode()
    {
        return $this->getKey('DestAccountCode');
    }

    public function getDestContext()
    {
        return $this->getKey('DestContext');
    }

    public function getDestExten()
    {
        return $this->getKey('DestExten');
    }

    public function getDestPriority()
    {
        return $this->getKey('DestPriority');
    }

    public function getDestUniqueid()
    {
        return $this->getKey('DestUniqueid');
    }

    public function getDestLinkedid()
    {
        return $this->getKey('DestLinkedid');
    }

    public function getQueue()
    {
        return $this->getKey('Queue');
    }

    public function getMemberName()
    {
        return $this->getKey('MemberName');
    }

    public function getInterface()
    {
        return $this->getKey('Interface');
    }

    public function getRingTime()
    {
        return $this->getKey('RingTime');
    }

}
