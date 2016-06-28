<?php

namespace Rpe\PumBundle\Model\Social;

/**
 * @method boolean  isUserInGroup()
 * @method boolean  isPotentialUser()
 * @method boolean  isOwner()
 * @method boolean  isAdmin()
 * @method boolean  isManager()
 * @method boolean  isValidUser()
 * @method boolean  isUser()
 * @method boolean  isRequested()
 * @method boolean  isRefused()
 * @method boolean  isInvited()
 * @method boolean  isBanned()
 *
 */
abstract class UserInGroup
{

    /**
     * Status
     */
    const STATUS_OWNER                   = 1;
    const STATUS_ADMIN                   = 2;
    const STATUS_MODERATOR               = 3;
    const STATUS_USER                    = 4;
    const STATUS_REQUEST                 = 5;
    const STATUS_INVITED                 = 6;
    const STATUS_REFUSED                 = 7;
    const STATUS_BANNED                  = 8;
    const STATUS_WAITING_PARENT_GROUP    = 9;
    
    const IS_OWNER        = 1;
    const IS_ADMIN        = 2;
    const IS_MANAGER      = 3;
    const IN_GROUP        = 4;
    const MY_GROUP        = 5;
    const POTENTIAL_GROUP = 6;

    /**
     * Check if user is in group
     * 
     * @return boolean
     * @access public
     */
    public function isUserInGroup()
    {
        if ($this->getStatus() <= self::IN_GROUP) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is potential for group
     * 
     * @return boolean
     * @access public
     */
    public function isPotentialUser()
    {
        if ($this->getStatus() <= self::STATUS_INVITED) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is owner of group
     * 
     * @return boolean
     * @access public
     */
    public function isOwner()
    {
        if ($this->getStatus() == self::STATUS_OWNER) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is admin of group
     * 
     * @return boolean
     * @access public
     */
    public function isAdmin()
    {
        if ($this->getStatus() <= self::IS_ADMIN) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is manager of group
     * 
     * @return boolean
     * @access public
     */
    public function isManager()
    {
        if ($this->getStatus() <= self::IS_MANAGER) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is valid for group
     * 
     * @return boolean
     * @access public
     */
    public function isValidUser()
    {
        if ($this->getStatus() <= self::STATUS_USER) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is normal user in group
     * 
     * @return boolean
     * @access public
     */
    public function isUser()
    {
        if ($this->getStatus() == self::STATUS_USER) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is requested in group
     * 
     * @return boolean
     * @access public
     */
    public function isRequested()
    {
        if ($this->getStatus() == self::STATUS_REQUEST) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is invited in group
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

    /**
     * Check if user is refused in group
     * 
     * @return boolean
     * @access public
     */
    public function isRefused()
    {
        if ($this->getStatus() == self::STATUS_REFUSED) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is banned in group
     * 
     * @return boolean
     * @access public
     */
    public function isBanned()
    {
        if ($this->getStatus() == self::STATUS_BANNED) {
            return true;
        }

        return false;
    }
}
