<?php
namespace Rpe\PumBundle\Model\Social;

/**
 * @method boolean  isUserInBlog()
 * @method boolean  isPotentialUser()
 * @method boolean  isAdmin()
 * @method boolean  isManager()
 * @method boolean  isValidUser()
 * @method boolean  isUser()
 * @method boolean  isRequested()
 * @method boolean  isInvited()
 * @method boolean  isRefused()
 * @method boolean  isBanned()
 * 
 */
abstract class UserInBlog
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
    
    const IS_OWNER        = 1;
    const IS_ADMIN        = 2;
    const IS_MANAGER      = 3;
    const IN_BLOG         = 4;
    const MY_BLOG         = 5;
    const POTENTIAL_BLOG  = 6;

    /**
     * Check if user is in blog
     * 
     * @return boolean
     * @access public
     */
    public function isUserInBlog()
    {
        if ($this->getStatus() <= self::IN_BLOG) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is potential user
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
     * Check if user is admin of blog
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
     * Check if user is manager of blog
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
     * Check if user is a valid user
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
     * Check if user is nomal status
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
     * Check if user is requested for blog
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
     * Check if user is invited for blog
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
     * Check if user is refused for blog
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
     * Check if user is banned for blog
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
