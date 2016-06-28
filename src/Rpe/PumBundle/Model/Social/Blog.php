<?php
namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;

/**
 * Blog class
 * 
 * @method boolean isActive()
 * @method boolean isPublic()
 * @method boolean isFriends()
 * @method boolean isPrivate()
 * @method boolean isBookmark($user)
 * @method array   getResources(array $orderBy = array(), $limite = null, $offset = null) 
 * @method array   getPublications(array $orderBy = array(), $limite = null, $offset = null) 
 * @method Criteria handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset) 
 * @method array   getVisibility() 
 * @method User    getOwner()
 * @method string  getOwnername()  
 * @method string  getOwnerId()
 * @method boolean isVisible()
 * @method string  getSearchQ()
 * @method string  getSearchQBis()
 */
abstract class Blog extends SearchFactory
{
    /**
     * Access type
     */
    const ACCESS_PUBLIC  = 'PUBLIC';
    const ACCESS_FRIENDS = 'FRIENDS';
    const ACCESS_PRIVATE = 'PRIVATE';

    /**
     * VISIBILITY
     */
    const VISIBILITY_PREFIX = 'BL_';
    const VISIBILITY_PUBLIC = 'VISIBLE';
    const VISIBILITY_HIDDEN = 'HIDDEN';
    
    /**
     * Status
     */
    const STATUS_TYPE_ACTIVE   = 'ACTIVE';
    const STATUS_TYPE_INACTIVE = 'INACTIVE';

    /**
     * Check if Blog is active
     *
     * @access public
     * @return boolean
     */
    public function isActive()
    {
        return $this->getStatus() === self::STATUS_TYPE_ACTIVE;
    }

    /**
     * Check if Blog is public
     *
     * @access public
     * @return boolean
     */
    public function isPublic()
    {
        if ($this->getAccessType() === self::ACCESS_PUBLIC) {
            return true;
        }

        return false;
    }

    /**
     * Check if Blog is accses to friends
     *
     * @access public
     * @return boolean
     */
    public function isFriends()
    {
        if ($this->getAccessType() === self::ACCESS_FRIENDS) {
            return true;
        }

        return false;
    }

    /**
     * Check if Blog is private
     *
     * @access public
     * @return boolean
     */
    public function isPrivate()
    {
        if ($this->getAccessType() === self::ACCESS_PRIVATE) {
            return true;
        }

        return false;
    }

    /**
     * Check if Blog is bookmarked by user
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
     * getResources
     * 
     * @access public
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return array  Array of resources
     *  
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
     * getPublications
     * 
     * @access public
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return array  Array of posts
     *  
     */
    public function getPublications(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(
        Criteria::expr()->andX(
            Criteria::expr()->eq('status', Post::STATUS_PUBLISHED),
            Criteria::expr()->orX(
                Criteria::expr()->isNull('publishDate'),
                Criteria::expr()->lte('publishDate', new \DateTime())
            )
        ));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->posts->matching($criteria);
    }

   /**
     * Handle criterias 
     *
     * @access public
     * @param Criteria $criteria    Criteria object
     * @param array    $orderBy     Order array
     * @param int      $limite      Number of limit
     * @param int      $offset      Start offset for retrieve
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
     * @access public
     * @return array Return array contain all visibility
     */
    public function getVisibility()
    {
        $visibility = array();
        
        if (false === $this->isPrivate()) {
            $visibility[] = self::VISIBILITY_PUBLIC;
        } else {
            $visibility[] = self::VISIBILITY_HIDDEN;
        }
        
        return $visibility;
    }

    /**
     * @access public
     * @return User Owner of blog or null
     */
    public function getOwner()
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', UserInBlog::STATUS_OWNER));
        $criteria->setMaxResults(1);
    
        if ($this->users->matching($criteria)->count()) {
            return $this->users->matching($criteria)->first()->getUser();
        }
    
        return null;
    }
    
    /**
     * @access public
     * @return string  Ownername of blog or empty
     */
    public function getOwnername()
    {
        if($owner = $this->getOwner()){
            return $owner->getFullname();
        }
        return "";
    }

    /**
     * @access public
     * @return string  Owner id of blog or empty
     */
    public function getOwnerId()
    {
        if($owner = $this->getOwner()){
            return $owner->getId();
        }
        return "";
    }
    
    /**
     * @access public
     * @return boolean
     * @see \Rpe\PumBundle\Model\Social\SearchFactory::isVisible()
     */
    public function isVisible()
    {
        return $this->isActive();
    }
    
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
        return $this->getFormattedSort('name');
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
}
