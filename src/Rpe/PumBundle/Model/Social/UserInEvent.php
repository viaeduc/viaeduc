<?php

namespace Rpe\PumBundle\Model\Social;

/**
 * @method boolean  hasAccepted()
 * @method boolean  hasRejected()
 * @method boolean  isInvited()
 */
abstract class UserInEvent
{
    const STATUS_ACCEPT     = 1;
    const STATUS_REJECT     = 2;
    const STATUS_INVITED    = 3;

    /**
     * Check if user is accepted in event
     * 
     * @return boolean
     * @access public
     */
    public function hasAccepted()
    {
        if ($this->getStatus() == self::STATUS_ACCEPT) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is rejected in event
     * 
     * @return boolean
     * @access public
     */
    public function hasRejected()
    {
        if ($this->getStatus() == self::STATUS_REJECT) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is invited in event
     * 
     * @return boolean
     * @access public
     */
    public function isInvited()
    {
        if ($this->getStatus() == self::STATUS_INVITED) {
            return true;
        }

        return false;
    }
}
