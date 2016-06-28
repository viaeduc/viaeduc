<?php

namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;

/**
 * @method User     getOwner()
 * @method boolean  isBookmark($user)
 * @method array    getResources(array $orderBy = array(), $limite = null, $offset = null)
 * @method Criteria handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 * @method array    serializeList($storage, $httpHost)
 * @method array    serialize($storage, $httpHost)
 *
 */
abstract class Editor
{
    /**
     * @access public
     * @return User Owner of editor or null
     */
    public function getOwner()
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', UserInEditor::STATUS_OWNER));
        $criteria->setMaxResults(1);

        if ($this->users->matching($criteria)->count()) {
            return $this->users->matching($criteria)->first()->getUser();
        }

        return null;
    }

    /**
     * Check if Editor is bookmarked by user
     * 
     * @param User $user    User object
     * @access public
     * 
     * @return boolean
     */
    public function isBookmark($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('user', $user));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->bookmarkby->matching($criteria)->count() === 0) {
            return false;
        }

        return true;
    }

    /**
     * get editor resources
     * 
     * @access public
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return array Array contain posts  
     */
    public function getResources(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('resource', true));
        $criteria->andWhere(Criteria::expr()->eq('status', Post::STATUS_PUBLISHED));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->posts->matching($criteria);
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
            'page_id' => $this->getId(), 
            'name' => $this->getName(), 
            'description' => $this->getDescription(), 
            'profile_picture_url' => ($this->getCover()) ? $httpHost.$storage->getWebPath($this->getCover(), true) : null, 
        );
        
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
            'page_id' => $this->getId(), 
            'name' => $this->getName(), 
            'description' => $this->getDescription(), 
            'profile_picture_url' => ($this->getCover()) ? $httpHost.$storage->getWebPath($this->getCover(), true) : null, 
        );
        
        return $outObject;
    }
}
