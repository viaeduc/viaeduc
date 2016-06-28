<?php
namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;
use Rpe\PumBundle\Model\Social\UserInDiscussion;

/**
 * Discussion class
 *
 * @method boolean getIsReadDiscussionBy($user)
 * @method array   getUserInDiscussion($user)
 * @method array   getRecipients($user, array $orderBy = array('id' => Criteria::ASC), $limite = null, $offset = null)
 * @method Message getLastMessage()
 * @method Message getLastMessageFromRecipients($user)
 * @method Criteria handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 * @method array   serializeList($storage, $httpHost) 
 * 
 */
abstract class Discussion
{

    /**
     * Status
     */
    const STATUS_OPENED  = 'OPENED';
    const STATUS_PRIVATE = 'PRIVATE';
    const STATUS_CLOSED  = 'CLOSED';

    /**
     * TYPE
     */
    const TYPE_ACTIV    = '1';
    const TYPE_ARCHIVED = '2';
    const TYPE_DELETED  = '3';

    /**
     * Check if discussion is read by user
     * 
     * @access public
     * @param  User $user    User object
     *
     * @return boolean
     */
    public function getIsReadDiscussionBy($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('user', $user));
        $criteria = $this->handleCriteria($criteria, $orderBy = array('id' => Criteria::DESC), $limite = 1, $offset = null);

        if (false !== $user_in_discussion = $this->users->matching($criteria)->first()) {
            if (null === $user_in_discussion->getViewDate() || $this->getLastMessage()->getDate() > $user_in_discussion->getViewDate()) {
                return true;
            }
        }

        return false;
    }

    /**
     * getUserInDiscussion
     *
     * @access public
     * @param User $user User object
     *
     * @return array
     */
    public function getUserInDiscussion($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('user', $user));
        $criteria = $this->handleCriteria($criteria, $orderBy = array('id' => Criteria::DESC), $limite = 1, $offset = null);

        if ($this->users->matching($criteria)->count() === 1) {
            return $this->users->matching($criteria)->first();
        }

        return null;
    }

    /**
     * getRecipients
     *
     * @access public
     * @param User $user User object
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     *
     * @return array
     */
    public function getRecipients($user, array $orderBy = array('id' => Criteria::ASC), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->neq('user', $user));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->users->matching($criteria);
    }

    /**
     * getLastMessage
     * 
     * @access public
     *
     * @return Message|null
     */
    public function getLastMessage()
    {
        $criteria = Criteria::create();
        $criteria = $this->handleCriteria($criteria, $orderBy = array('id' => Criteria::DESC), $limite = 1, $offset = null);

        if ($this->messages->matching($criteria)->count() === 1) {
            return $this->messages->matching($criteria)->first();
        }

        return null;
    }

    /**
     * getLastMessageFromRecipients
     *
     * @param User $user User object
     * @access public
     *
     * @return Message|null
     */
    public function getLastMessageFromRecipients($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->neq('author', $user));
        $criteria = $this->handleCriteria($criteria, array('id' => Criteria::DESC), $limite = 1, $offset = null);

        if ($this->messages->matching($criteria)->count() === 1) {
            return $this->messages->matching($criteria)->first();
        }

        return null;
    }
    
    /**
     * @access public
     * @param Criteria  $criteria  Criteria object
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return Criteria  
     */
    private function handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
    {
        if (null !== $limite) {
            $criteria->setMaxResults($limite);
        }
        
        if (null !== $offset) {
            $criteria->setFirstResult($offset);
        }
        
        if (null === $orderBy || empty($orderBy)) {
            $criteria->orderBy(array('id' => Criteria::DESC));
        } else {
            $criteria->orderBy($orderBy);
        }
        
        return $criteria;
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
            'discussion_id' => $this->getId(), 
            'create_date' => $this->getCreateDate(), 
            'update_date' => $this->getUpdateDate(), 
            'status' => $this->getStatus(), 
            'participants' => array(), 
        );
        
        foreach($this->getUsers() as $participant) {
            $user = $participant->getUser();
            
            $outObject['participants'][] = array(
                'type' => $participant->getStatus(), 
                'id' => $user->getId(), 
                'firstname' => $user->getFirstname(), 
                'lastname' => $user->getLastname(), 
                'profile_picture_url' => $httpHost.$storage->getWebPath($user->getAvatar(), true)
            );
        }
        
        return $outObject;
    }
}
