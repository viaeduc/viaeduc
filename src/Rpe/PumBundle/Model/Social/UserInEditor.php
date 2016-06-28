<?php
namespace Rpe\PumBundle\Model\Social;

/**
 * @method boolean  isOwner()
 * @method boolean  isAdmin()
 * @method boolean  isManager()
 * 
 */
abstract class UserInEditor
{

    /**
     * Status
     */
    const STATUS_OWNER     = 1;
    const STATUS_ADMIN     = 2;
    const STATUS_MODERATOR = 3;

    const IS_OWNER   = 1;
    const IS_ADMIN   = 2;
    const IS_MANAGER = 3;

    /**
     * Check if user is owner of editor
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
     * Check if user is admin of editor
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
     * Check if user is manager of editor
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
}
