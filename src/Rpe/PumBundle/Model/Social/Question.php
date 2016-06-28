<?php
namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;

/**
 * @method string   getSearchQ()
 * @method string   getSearchQBis()
 * @method string   getSearchQTer()
 * @method string   getAuthorAvatarUrl($default = null)
 * @method array    getVisibility()
 * @method string   getFormattedAnswers()
 * @method array    getFormattedKeywords()
 * @method boolean  isPublic()
 * @method boolean  isPrivate()
 * @method boolean  isInGroup()
 * @method Question incrementViewed()
 * @method int      getViewedCount()  
 * @method array    getMainAnswers($excludeIDs = array(), array $orderBy = array('id' => Criteria::ASC), $limite = null, $offset = null)
 * @method array    getOnlySelectedAnswers(array $orderBy = array('id' => Criteria::ASC), $limite = null, $offset = null)
 * @method Criteria handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 * @method boolean  isBookmark($user)
 * @method array    serializeList($storage, $httpHost)
 * @method array    serialize($storage, $httpHost)
 * 
 */
abstract class Question extends SearchFactory
{
    /**
     * Access type
     */
    const ACCESS_PUBLIC        = 'PUBLIC';
    const ACCESS_FRIENDS       = 'FRIENDS';
    const ACCESS_GROUP         = 'GROUP';
    
    /**
     * VISIBILITY
     */
    const VISIBILITY_PREFIX = 'FAQ_';
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

    /**
     * Get search query tier param
     * 
     * @access public
     * @return string 
     * @see \Rpe\PumBundle\Model\Social\SearchFactory::getSearchQBis()
     */
    public function getSearchQTer()
    {
        return $this->getFormattedText('formattedAnswers');
    }

    /**
     * @access public 
     * @param string $default default if null
     * @return string
     */
    public function getAuthorAvatarUrl($default = null)
    {
        if (null === $author = $this->getAuthor()) {
            return $default;
        }

        return $author->getImageField('avatar', 'id');
    }

    /**
     * getVisibility
     * 
     * @access public
     * @return array Return array contain all visibility
     * 
     */
    public function getVisibility()
    {
        if ($this->isPublic()) {
            return array(self::VISIBILITY_PUBLIC);
        }

        $visibility = array();

        $group = $this->getPublishedGroup();
        if (null === $group || false === $group->isSecret()) {
            $visibility[] = self::VISIBILITY_PUBLIC;
        } else {
            $visibility[] = self::VISIBILITY_HIDDEN;
            $visibility[] = Group::VISIBILITY_PREFIX.$group->getId();
        }
        
        if ($this->getAuthor()) {
            $visibility[] = User::VISIBILITY_PREFIX.$this->getAuthor()->getId();
        }
        return $visibility;
    }

    /**
     * getFormattedAnswers
     *
     * @access public
     * @return string  Concanated answers
     * 
     */
    public function getFormattedAnswers()
    {
        $result = null;

        foreach ($this->getOnlySelectedAnswers() as $answer) {
            $result .= ' '.$answer->getContent();
        }

        return $result;
    }

    /**
     * getFormattedKeywords
     *
     * @access public
     * @return array  Array of all keywords
     * 
     */
    public function getFormattedKeywords()
    {
        if (null === $this->getKeywords() || !$this->getKeywords()) {
            return null;
        }

        $result = array();

        foreach (explode(',', $this->getKeywords()) as $keyword) {
            $result[] = trim($keyword);
        }

        return $result;
    }

    /**
     * Check if question is public
     *
     * @access public
     *
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
     * Check if question is private
     *
     * @access public
     *
     * @return boolean
     */
    public function isPrivate()
    {
        if ($this->getAccessType() === self::ACCESS_FRIENDS) {
            return true;
        }

        return false;
    }

    /**
     * Check if question is in group
     *
     * @access public
     *
     * @return boolean
     */
    public function isInGroup()
    {
        if ($this->getAccessType() === self::ACCESS_GROUP) {
            return true;
        }

        return false;
    }

    /**
     * Check if question view number is cremented
     *
     * @access public
     *
     * @return Question return self
     */
    public function incrementViewed()
    {
        if (null !== $this->viewed) {
            $this->viewed->increment();
        }

        return $this;
    }

    /**
     * Get view count number
     *
     * @access public
     * @return int 
     */
    public function getViewedCount()
    {
        if (null !== $this->viewed) {
            return $this->viewed->getValue();
        }

        return 0;
    }

    /**
     * Get main answers for question
     *
     * @access public
     * @param array     $excludeIDs     Exclude answer ids
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return array    Array containing answers
     */
    public function getMainAnswers($excludeIDs = array(), array $orderBy = array('id' => Criteria::ASC), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('parent', null));
        if (count($excludeIDs) > 0) {
            $criteria->andWhere(Criteria::expr()->notIn('id', $excludeIDs));
        }
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->answers->matching($criteria);
    }

    /**
     * Get the selected answers for question
     *
     * @access public
     * @param array     $excludeIDs     Exclude answer ids
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return array    Array containing answers
     */
    public function getOnlySelectedAnswers(array $orderBy = array('id' => Criteria::ASC), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('isGood', true));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->answers->matching($criteria);
    }

    /**
     * Handle matching criterias
     * 
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
     * Check if question is bookmarked by user
     *
     * @access public
     * @param  User $user    User object
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
            'question_id' => $this->getId(), 
            'name' => $this->getName(), 
            'description' => $this->getDescription(), 
            'date' => $this->getDate(), 
            'viewed' => $this->getViewed()->getValue(), 
            'keywords' => $this->getKeywords(), 
            'author' => array(), 
        );

        $outObject['author'] = array(
            'id' => $this->getAuthor()->getId(), 
            'firstname' => $this->getAuthor()->getFirstname(), 
            'lastname' => $this->getAuthor()->getLastname(), 
            'profile_picture_url' => $httpHost.$storage->getWebPath($this->getAuthor()->getAvatar(), true)
        );
        
        return $outObject;
    }
    
    /**
     * serialize
     * 
     * @access public
     * @param Object      $storage   Storage object
     * @param string      $httpHost  Http host
     * @param array       $parameters 
     * 
     * @return array
     */
    public function serialize($storage, $httpHost)
    {
        $outObject = array(
            'publication_id' => $this->getId(), 
            'name' => $this->getName(), 
            'description' => $this->getDescription(), 
            'date_create' => $this->getCreateDate(), 
            'date_update' => $this->getUpdateDate(), 
            'profile_picture_url' => ($this->getCoverImage()) ? $httpHost.$storage->getWebPath($this->getCoverImage(), true) : null, 
            'language' => ($this->getLanguage()) ? $this->getLanguage()->getName() : null, 
            'keywords' => $this->getKeywords(), 
            'author' => array(), 
            'co_authors' => array(), 
            'group' => array('id' => $this->getPublishedGroup()->getId(), 'name' => $this->getPublishedGroup()->getName()), 
        );
        
        $outObject['author'] = array(
            'id' => $this->getAuthor()->getId(), 
            'firstname' => $this->getAuthor()->getFirstname(), 
            'lastname' => $this->getAuthor()->getLastname(), 
            'profile_picture_url' => $httpHost.$storage->getWebPath($this->getAuthor()->getAvatar(), true)
        );
        
        foreach($this->getCoAuthors() as $coAuthor) {
            $outObject['co_authors'][] = array(
                'id' => $coAuthor->getId(), 
                'firstname' => $coAuthor->getFirstname(), 
                'lastname' => $coAuthor->getLastname(), 
                'profile_picture_url' => $httpHost.$storage->getWebPath($coAuthor->getAvatar(), true)
            );
        }
        
        return $outObject;
    }
}
