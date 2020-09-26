<?php

namespace PAMI\Message\Event;

use PAMI\Message\Event\EventMessage;

class QueueMemberRinginuseEvent extends EventMessage
{
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

  public function getStateInterface()
  {
    return $this->getKey('StateInterface');
  }

  public function getMembership()
  {
    return $this->getKey('Membership');
  }

  public function getPenalty()
  {
    return $this->getKey('Penalty');
  }

  public function getCallsTaken()
  {
    return $this->getKey('CallsTaken');
  }

  public function getLastCall()
  {
    return $this->getKey('LastCall');
  }

  public function getInCall()
  {
    return $this->getKey('InCall');
  }

  public function getStatus()
  {
    return $this->getKey('Status');
  }

  public function getPaused()
  {
    return $this->getKey('Paused');
  }

  public function getPausedReason()
  {
    return $this->getKey('PausedReason');
  }

  public function getRinginuse()
  {
    return $this->getKey('Ringinuse');
  }

}
