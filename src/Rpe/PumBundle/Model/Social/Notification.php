<?php
namespace Rpe\PumBundle\Model\Social;

/**
 * @method array    serializeList($storage, $httpHost)  
 * @method array    serialize($storage, $httpHost)
 * @method boolean  isTypePublication()
 * @method boolean  isTypeResource()
 * @method boolean  isTypeResourceEdit()
 * @method boolean  isTypeRecommend()
 * @method boolean  isTypeComment()
 * @method boolean  isTypeRelationRequest()
 * @method boolean  isTypeRelationAccept()
 * @method boolean  isTypeJoinRequest()
 * @method boolean  isTypeJoinInvite()
 * @method boolean  isTypeJoinUserAccept()
 * @method boolean  isTypeJoinGroupAccept()
 * @method boolean  isTypeSharePublication()
 * @method boolean  isTypeShareResource()
 * @method boolean  isTypeEventInvitation()
 * @method boolean  isTypeAnswer()
 * @method boolean  isTypeCoAuthor()
 * @method boolean  isTypeEditPublication()
 * @method boolean  isTypeResourcePadClose()
 * @method boolean  isTypeResourcePadReopen()
 * @method boolean  isTypeResourcePadCreate()
 * @method boolean  isFree()
 * @method boolean  getRealContent()
 * 
 */
abstract class Notification
{
    const TYPE_PUBLICATION         = 1;
    const TYPE_RESOURCE            = 2;
    const TYPE_RECOMMEND           = 3;
    const TYPE_COMMENT             = 4;
    const TYPE_RELATION_REQUEST    = 7;
    const TYPE_RELATION_ACCEPT     = 8;
    const TYPE_JOIN_REQUEST        = 9;
    const TYPE_JOIN_INVITE         = 10;
    const TYPE_JOIN_USER_ACCEPT    = 11;
    const TYPE_JOIN_GROUP_ACCEPT   = 12;
    const TYPE_SHARE_PUBLICATION   = 13;
    const TYPE_SHARE_RESOURCE      = 14;
    const TYPE_EVENT_INVITATION    = 15;
    const TYPE_ANSWER              = 16;
    const TYPE_BECAME_ADMIN        = 17;
    const TYPE_COAUTHOR            = 18;
    const TYPE_EDIT_PUBLICATION    = 19;
    const TYPE_FREE                = 20;
    const TYPE_RESOURCE_EDIT       = 21;
    const TYPE_RESOURCE_PAD_CREATE = 22;
    const TYPE_RESOURCE_PAD_REOPEN = 23;
    const TYPE_RESOURCE_PAD_CLOSE  = 24;
    
    /**
     * set treated label
     * @access public
     * @return void
     * 
     */
    public function treat()
    {
        $this->setTreated(true);
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypePublication()
    {
        if ($this->getType() == self::TYPE_PUBLICATION) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeBecomeAdmin()
    {
        if ($this->getType() == self::TYPE_BECAME_ADMIN) {
            return true;
        }
        return false;
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function isTypeResource()
    {
        if ($this->getType() == self::TYPE_RESOURCE) {
            return true;
        }

        return false;
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function isTypeResourceEdit()
    {
        if ($this->getType() == self::TYPE_RESOURCE_EDIT) {
            return true;
        }
    
        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeRecommend()
    {
        if ($this->getType() == self::TYPE_RECOMMEND) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeComment()
    {
        if ($this->getType() == self::TYPE_COMMENT) {
            return true;
        }

        return false;
    }

    /**
     * @return boolean
     */
    public function isTypeRelationRequest()
    {
        if ($this->getType() == self::TYPE_RELATION_REQUEST) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeRelationAccept()
    {
        if ($this->getType() == self::TYPE_RELATION_ACCEPT) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeJoinRequest()
    {
        if ($this->getType() == self::TYPE_JOIN_REQUEST) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeJoinInvite()
    {
        if ($this->getType() == self::TYPE_JOIN_INVITE) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeJoinUserAccept()
    {
        if ($this->getType() == self::TYPE_JOIN_USER_ACCEPT) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeJoinGroupAccept()
    {
        if ($this->getType() == self::TYPE_JOIN_GROUP_ACCEPT) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeSharePublication()
    {
        if ($this->getType() == self::TYPE_SHARE_PUBLICATION) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeShareResource()
    {
        if ($this->getType() == self::TYPE_SHARE_RESOURCE) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeEventInvitation()
    {
        if ($this->getType() == self::TYPE_EVENT_INVITATION) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return boolean
     */
    public function isTypeAnswer()
    {
        if ($this->getType() == self::TYPE_ANSWER) {
            return true;
        }

        return false;
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function isTypeCoAuthor()
    {
        if ($this->getType() == self::TYPE_COAUTHOR) {
            return true;
        }
    
        return false;
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function isTypeEditPublication()
    {
        if ($this->getType() == self::TYPE_EDIT_PUBLICATION) {
            return true;
        }
        
        return false;
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function isTypeResourcePadCreate()
    {
        if ($this->getType() == self::TYPE_RESOURCE_PAD_CREATE) {
            return true;
        }
        
        return false;
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function isTypeResourcePadClose()
    {
        if ($this->getType() == self::TYPE_RESOURCE_PAD_CLOSE) {
            return true;
        }
        
        return false;
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function isTypeResourcePadReopen()
    {
        if ($this->getType() == self::TYPE_RESOURCE_PAD_REOPEN) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 
     * @access public
     * @return boolean
     */
    public function isFree()
    {
        if ($this->type == self::TYPE_FREE) {
            return true;
        }
        
        return false;
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function getRealContent()
    {
        if (null !== $this->getContent()) {
            return $this->getContent();
        }
        
        return '';
    }
    
    /**
     * serializeList
     * 
     * @access public
     * @param Object      $storage   Storage object
     * @param string      $httpHost  Http host
     * 
     * @return array
     * 
     */
    public function serializeList($storage, $httpHost)
    {
        $outObject = array(
            'notification_id' => $this->getId(), 
            'content' => $this->getRealContent(), 
            'date' => $this->getDate(), 
            'type' => $this->getType(), 
            'url' => $this->getUrl()
        );
        
        return $outObject;
    }
    
    /**
     * serialize
     * 
     * @access public
     * @param Object      $storage   Storage object
     * @param string      $httpHost  Http host
     * 
     * @return array
     * 
     */
    public function serialize($storage, $httpHost)
    {
        $outObject = array(
            'notification_id' => $this->getId(), 
            'content' => $this->getRealContent(), 
            'date' => $this->getDate(), 
            'type' => $this->getType(), 
            'url' => $this->getUrl()
        );
        
        return $outObject;
    }
}
