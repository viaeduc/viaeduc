<?php

namespace Rpe\PumBundle\Model\Social;

/**
 * @method boolean isFriend()
 * @method boolean isOnHold()
 * 
 */
abstract class Friend
{
    /**
     * Status
     */
    const STATUS_ON_HOLD  = 'ON_HOLD';
    const STATUS_ACCEPTED = 'ACCEPTED';
    const STATUS_DENIED   = 'REJECTED';
    const STATUS_NONE     = 'NONE';

    /**
     * isFriend
     *
     * @access public
     * @return boolean 
     *
     */
    public function isFriend()
    {
        if ($this->getStatus() === self::STATUS_ACCEPTED) {
            return true;
        }

        return false;
    }

    /**
     * isOnHold
     *
     * @access public
     * @return boolean 
     */
    public function isOnHold()
    {
        if ($this->getStatus() === self::STATUS_ON_HOLD) {
            return true;
        }

        return false;
    }
}
