<?php
namespace Rpe\PumBundle\Model\External;

use Doctrine\Common\Collections\Criteria;
use Rpe\PumBundle\Model\Social\SearchFactory;
use Rpe\PumBundle\Model\Social\UserMeta;

/**
 * Abstract class Notice
 * 
 * @method boolean  isLike($user)
 * @method Criteria handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 * @method string   getSearchQ()
 * @method string   getSearchQBis()
 * @method array    getVisibility()  
 * @method boolean  isVisible()  
 * @method UserMeta getMeta($key)  
 * @method array    getMainComments(array $orderBy = array('id' => Criteria::ASC), $limite = null, $offset = null)  
 * @method int      getShareCount()  
 * @method boolean  isLike($user)  
 * @method array    serializeList($storage, $httpHost)  
 * @method array    serialize($storage, $httpHost)  
 * 
 */
abstract class Notice extends SearchFactory
{   
    /**
     * VISIBILITY
     */
    const VISIBILITY_PREFIX = 'NO_';
    const VISIBILITY_PUBLIC = 'VISIBLE';
    const VISIBILITY_HIDDEN = 'HIDDEN';

    /**
     * Get search query param
     *
     * @access public
     * @return string
     *  
     * @see \Rpe\PumBundle\Model\Social\SearchFactory::getSearchQ()
     */
    public function getSearchQ()
    {
        return $this->getFormattedSort('title');
    }


    /**
     * Get search query bis param
     * 
     * @access public
     * @return string 
     * @see \Rpe\PumBundle\Model\Social\SearchFactory::getSearchQBis()
     */
    public function getSearchQBis()
    {
        return $this->getFormattedText('description');
    }

    /**
     * @access public
     * @return array Return array contain all visibility
     */
    public function getVisibility()
    {
        $visibility = array();

        $visibility[] = self::VISIBILITY_PUBLIC;

        $visibility[] = self::VISIBILITY_PREFIX.$this->getId();

        return $visibility;
    }

    /**
     * @access public
     * @return boolean
     * @see \Rpe\PumBundle\Model\Social\SearchFactory::isVisible()
     */
    public function isVisible()
    {
        return $this->getIsPublishable();
    }

    /**
     * @access public
     * @param string     $key   Meta key
     * @return UserMeta  Meta object  
     */
    public function getMeta($key)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('metaKey', $key));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);
        
        if ($this->noticeMetas->matching($criteria)->count() === 1) {
            return $this->noticeMetas->matching($criteria)->first();
        }

        return null;
    }

    /**
     * getMainComments
     * 
     * @access public
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return array  Array of comments       
     */
    public function getMainComments(array $orderBy = array('id' => Criteria::ASC), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('parent', null));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->comments->matching($criteria);
    }

    /**
     *
     * @access public
     * @return int
     */
    public function getShareCount()
    {
        return $this->getShareBy()->count();
    }

    /**
     * Check if notice is liked by user
     *
     * @access public
     * @param  User $user    User object
     *
     * @return boolean
     */
    public function isLike($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('user', $user));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->recommendby->matching($criteria)->count() === 0) {
            return false;
        }

        return true;
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
            'notice_id' => $this->getId(),
            'title' => $this->getTitle(),
            'notice_category' => $this->getCategory(),
            'description' => $this->getDescription()

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
            'notice_id' => $this->getId(),
            'title' => $this->getTitle(),
            'notice_category' => $this->getCategory(),
            'description' => $this->getDescription()
        );
        
        return $outObject;
    }
}
