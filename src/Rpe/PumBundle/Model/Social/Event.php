<?php

namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;

/**
 * @method Criteria handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 * @method array    serializeList($storage, $httpHost)
 * @method array    serialize($storage, $httpHost)
 * @method array    getUsersInvited(array $orderBy = array(), $limite = null, $offset = null)
 */
abstract class Event
{
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
            'event_id' => $this->getId(), 
            'name' => $this->getTitle(), 
            'description' => $this->getDescription(), 
            'date' => $this->getDate(), 
            'start' => $this->getStartDate(), 
            'end' => $this->getEndDate(), 
            'location' => $this->getPlaceAddress(), 
            // 'profile_picture_url' => $httpHost.$storage->getWebPath($this->getPicture(), true), 
            'pages' => array(), // @TODO
            'groups' => array(), 
        );

        if(null !== ($group = $this->getOwnerGroup())) {
            $outObject['groups'][] = array(
                'id' => $group->getId(), 
                'name' => $group->getName()
            );
        }
        
        return $outObject;
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
    public function serialize($storage, $httpHost)
    {
        $outObject = array(
            'event_id' => $this->getId(), 
            'name' => $this->getTitle(), 
            'description' => $this->getDescription(), 
            'date' => $this->getDate(), 
            'start' => $this->getStartDate(), 
            'end' => $this->getEndDate(), 
            'location' => $this->getPlaceAddress(), 
            // 'profile_picture_url' => $httpHost.$storage->getWebPath($this->getPicture(), true), 
            'pages' => array(), // @TODO
            'groups' => array(), 
        );
        
        $outObject['groups'][] = (null !== $this->getOwnerGroup()) ? $this->getOwnerGroup()->getId() : null;
        
        return $outObject;
    }
    
    /**
     * getUsersInvited
     *
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     *
     * @return array 
     */
    public function getUsersInvited(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', UserInEvent::STATUS_INVITED));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);
    
        return $this->users->matching($criteria);
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
}
