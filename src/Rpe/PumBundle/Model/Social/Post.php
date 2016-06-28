<?php
namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;

/**
 * @method string getSearchQ()
 * @method string getSearchQBis()
 * @method string getAuthorAvatarUrl($default = null)
 * @method array  getPostVisibility()
 * @method boolean isLike($user)
 * @method boolean isBookmark($user)
 * @method boolean hasBadge($badgeKey)
 * @method boolean isCoAuthor($user)
 * @method array   getMainComments(array $orderBy = array('id' => Criteria::ASC), $limite = null, $offset = null)
 * @method array   getPostVersions(array $orderBy = array('id' => Criteria::DESC), $limite = null, $offset = null)
 * @method array   getVersion($versionId)
 * @method PostVersion getLastVersion(array $orderBy = array('id' => Criteria::DESC), $limite = 1, $offset = null)
 * @method Criteria  handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 * @method boolean isTypeBlog()
 * @method boolean isTypeWall()
 * @method boolean isTypeGroup()
 * @method boolean isTypePage()
 * @method boolean isTypeEditor()
 * @method int     getShareCount()
 * @method boolean isVisible()
 * @method array   serializeList($storage, $httpHost, $parameters = array())
 * @method array   serialize($storage, $httpHost, $parameters = null)
 * @method boolean isCollaborative()
 * @method boolean padIsClosed()
 */
abstract class Post extends SearchFactory
{

    /**
     * Status
     */
    const STATUS_DRAFTING  = 'DRAFTING';
    const STATUS_PUBLISHED = 'PUBLISHED';
    const STATUS_ARCHIVED  = 'ARCHIVED';
    const STATUS_DELETED   = 'DELETED';

    /**
     * Type
     */
    const TYPE_BLOG     = '1';
    const TYPE_WALL     = '2';
    const TYPE_PAGE     = '3';
    const TYPE_GROUP    = '4';
    const TYPE_EDITOR   = '5';

    /**
     * VISIBILITY
     */
    const VISIBILITY_PREFIX = 'POST_';
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
        return $this->getFormattedText('keywords');
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
     * getPostVisibility
     * 
     * @access public
     * @return array Return array contain all visibility
     * 
     */
    public function getPostVisibility()
    {
        $visibility = array();

        $group = $this->getPublishedGroup();
        if (null === $group || false === $group->isSecret()) {
            $visibility[] = self::VISIBILITY_PUBLIC;
        } else {
            $visibility[] = self::VISIBILITY_HIDDEN;
            $visibility[] = Group::VISIBILITY_PREFIX.$group->getId();
        }

        if ($author = $this->getAuthor()) {
            $visibility[] = User::VISIBILITY_PREFIX.$author->getId();
        }

        foreach ($this->getCoAuthors() as $author) {
            $visibility[] = User::VISIBILITY_PREFIX.$author->getId();
        }

        return $visibility;
    }

    /**
     * Check if post is liked by user
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
     * Check if post is bookmarked by user
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
     * hasBadge
     *
     * @access public
     * @param string $badgeKey  Badge key value
     *
     * @return boolean
     */
    public function hasBadge($badgeKey)
    {   
        foreach ($this->badges as $badge){
            if($badge->getBadgeKey() == $badgeKey){
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user is coauthor of post
     *
     * @access public
     * @param  User $user    User object
     *
     * @return boolean
     */
    public function isCoAuthor($user)
    {
//         invalid : Matching Criteria on PersistentCollection only works on OneToMany associations
//         $criteria = Criteria::create();
//         $criteria->andWhere(Criteria::expr()->eq('user', $user));

//         $criteria = $this->handleCriteria($criteria, array(), 1, null);

//         if ($this->coAuthors->matching($criteria)->count() === 0) {
//             return false;
//         }

        // coAthor created as manytomany relation, corrige:
        // Matching Criteria on PersistentCollection only works on OneToMany associations
        if($coAuthors = $this->coAuthors){
            if(count($coAuthors)){
                foreach ($coAuthors as $coAuthor){
                    if($coAuthor->getId() == $user->getId()){
                        return true;
                    }
                }
            }
        }
        return false;
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
     * getPostVersions
     * 
     * @access public
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return array  Array of post versions 
     */
    public function getPostVersions(array $orderBy = array('id' => Criteria::DESC), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->versions->matching($criteria);
    }

    /**
     * Get version
     * 
     * @access public
     * @param int       $versionId     id of post version
     * 
     * @return array  Array of post versions 
     */
    public function getVersion($versionId)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('id', $versionId));
        $criteria = $this->handleCriteria($criteria, array('id' => Criteria::DESC), 1, null);

        if ($this->versions->matching($criteria)->count() === 1) {
            return $this->versions->matching($criteria)->first();
        }

        return null;
    }

    /**
     * getLastVersion
     * 
     * @access public
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return PostVersion 
     */
    public function getLastVersion(array $orderBy = array('id' => Criteria::DESC), $limite = 1, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        if ($this->versions->matching($criteria)->count() === 1) {
            return $this->versions->matching($criteria)->first();
        }

        return null;
    }

    /**
     * handleCriteria
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
     * isTypeBlog
     *
     * @return boolean
     * @access public
     */
    public function isTypeBlog()
    {
        if ($this->getType() == self::TYPE_BLOG) {
            return true;
        }

        return false;
    }

    /**
     * isTypeWall
     * 
     * @return boolean
     * @access public
     */
    public function isTypeWall()
    {
        if ($this->getType() == self::TYPE_WALL) {
            return true;
        }

        return false;
    }

    /**
     * isTypeGroup
     *
     * @return boolean
     * @access public
     */
    public function isTypeGroup()
    {
        if ($this->getType() == self::TYPE_GROUP) {
            return true;
        }

        return false;
    }

    /**
     * isTypePage
     * 
     * @return boolean
     * @access public
     */
    public function isTypePage()
    {
        if ($this->getType() == self::TYPE_PAGE) {
            return true;
        }

        return false;
    }

    /**
     * isTypeEditor
     * 
     * @return boolean
     * @access public
     */
    public function isTypeEditor()
    {
    	if ($this->getType() == self::TYPE_EDITOR) {
    	    return true;
    	}

    	return false;
    }
    
    /**
     * getShareCount
     * 
     * @return int
     * @access public
     */
    public function getShareCount()
    {
        return $this->getShareBy()->count();
    }
    
    /**
     * isVisible
     * @return boolean
     * @access public
     */
    public function isVisible($checkResource = true)
    {
        $publishDate = $this->getPublishDate();
        $is_published = ($publishDate == null) || ($publishDate <= new \DateTime('now'));
        
        $option = true;
        if ($checkResource === true) {
            $option = $this->getResource();
        }
        
        return $option && $this->getStatus() === self::STATUS_PUBLISHED && $is_published;
    }
    
    /**
     * serializeList
     * 
     * @access public
     * @param Object      $storage   Storage object
     * @param string      $httpHost  Http host
     * 
     * @return array
     */
    public function serializeList($storage, $httpHost, $parameters = array())
    {
        if ($parameters['type'] === 'activities') {
            $outObject = array(
                'activity_id' => $this->getId(), 
                'message' => $this->getContent(), 
                'date' => $this->getUpdateDate(), 
                'type' => $this->getType(), 
                'comments_count' => $this->getComments()->count(), 
                'recommendations_count' => $this->getRecommendby()->count(), 
                'shares_count' => $this->getShareby()->count(), 
                'attachment' => '', 
                'from' => '', 
            );
            
            if(null !== ($file = $this->getFile())) {
                $outObject['attachment'] = $httpHost.$storage->getWebPath($file, true);
            }
            
            $outObject['from'] = array(
                'id' => $this->getAuthor()->getId(), 
                'firstname' => $this->getAuthor()->getFirstname(), 
                'lastname' => $this->getAuthor()->getLastname(), 
                'profile_picture_url' => $httpHost.$storage->getWebPath($this->getAuthor()->getAvatar(), true)
            );
        } else {
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
            
            $author = $this->getAuthor();
            
            $outObject['author'] = array(
                'id' => $author->getId(), 
                'firstname' => $author->getFirstname(), 
                'lastname' => $author->getLastname(), 
                'profile_picture_url' => $httpHost.$storage->getWebPath($author->getAvatar(), true)
            );
            
            foreach($this->getCoAuthors() as $coAuthor) {
                $outObject['co_authors'][] = array(
                    'id' => $coAuthor->getId(), 
                    'firstname' => $coAuthor->getFirstname(), 
                    'lastname' => $coAuthor->getLastname(), 
                    'profile_picture_url' => $httpHost.$storage->getWebPath($coAuthor->getAvatar(), true)
                );
            }
        }
        
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
     * 
     */
    public function serialize($storage, $httpHost, $parameters = null)
    {
        if ($parameters['type'] === 'activities') {
            $outObject = array(
                'activity_id' => $this->getId(), 
                'message' => $this->getContent(), 
                'date' => $this->getUpdateDate(), 
                'type' => $this->getType(), 
                'comments_count' => $this->getComments()->count(), 
                'recommendations_count' => $this->getRecommendby()->count(), 
                'shares_count' => $this->getShareby()->count(), 
                'attachment' => '', 
                'from' => '', 
            );
            
            if(null !== ($file = $this->getFile())) {
                $outObject['attachment'] = $httpHost.$storage->getWebPath($file, true);
            }
            
            $outObject['from'] = array(
                'id' => $this->getAuthor()->getId(), 
                'firstname' => $this->getAuthor()->getFirstname(), 
                'lastname' => $this->getAuthor()->getLastname(), 
                'profile_picture_url' => $httpHost.$storage->getWebPath($this->getAuthor()->getAvatar(), true)
            );
        } else {
            $outObject = array(
                'publication_id' => $this->getId(), 
                'name' => $this->getName(), 
                'description' => $this->getDescription(), 
                'date_create' => $this->getCreateDate(), 
                'date_update' => $this->getUpdateDate(), 
                'profile_picture_url' => ($this->getCoverImage()) ? $httpHost.$storage->getWebPath($this->getCoverImage(), true) : null, 
                'content' => $this->getContent(),
                'disciplines' => array(), 
                'teaching_levels' => array(), 
                'document_type' => (null !== $this->getDocumentType()) ? $this->getDocumentType()->getName() : null, 
                'language' => (null !== $this->getLanguage()) ? $this->getLanguage()->getName() : null, 
                'keywords' => $this->getKeywords(), 
                'author' => array(), 
                'co_authors' => array(), 
                'groups' => array(), 
                'attachments' => array(), 
                'versions' => array(), 
            );
            
            $author = $this->getAuthor();
            
            $outObject['author'] = array(
                'id' => $author->getId(), 
                'firstname' => $author->getFirstname(), 
                'lastname' => $author->getLastname(), 
                'profile_picture_url' => $httpHost.$storage->getWebPath($author->getAvatar(), true)
            );
            
            foreach($this->getDisciplines() as $discipline) {
                $outObject['disciplines'][] = array('id' => $discipline->getId(), 'name' => $discipline->getName());
            }
            
            foreach($this->getTeachingLevels() as $teachingLevel) {
                $outObject['teaching_levels'][] = array('id' => $teachingLevel->getId(), 'name' => $teachingLevel->getName());
            }
            
            foreach($this->getCoAuthors() as $coAuthor) {
                $outObject['co_authors'][] = array(
                    'id' => $coAuthor->getId(), 
                    'firstname' => $coAuthor->getFirstname(), 
                    'lastname' => $coAuthor->getLastname(), 
                    'profile_picture_url' => $httpHost.$storage->getWebPath($coAuthor->getAvatar(), true)
                );
            }
            
            if(null !== ($group = $this->getPublishedGroup())) {
                $outObject['groups'][] = array(
                    'id' => $group->getId(), 
                    'name' => $group->getName()
                );
            }
            
            if(null !== ($file = $this->getFile())) {
                $outObject['attachments'][] = $httpHost.$storage->getWebPath($file, true);
            }
            
            foreach($this->getPostVersions() as $postVersion) {
                $version = array(
                    'id' => $postVersion->getId(), 
                    'date' => $postVersion->getUpdateDate(), 
                    'authors' => array()
                );
                
                if (null !== ($author = $postVersion->getAuthor())) {
                    $version['authors'][] = array(
                        'id' => $author->getId(), 
                        'firstname' => $author->getFirstname(), 
                        'lastname' => $author->getLastname(), 
                        'profile_picture_url' => $httpHost.$storage->getWebPath($author->getAvatar(), true)
                    );
                }
                
                $outObject['versions'][] = $version;
            }
            
            if (null !== $parameters) {
                if (isset($parameters['version_id'])) {
                    $postVersion = $this->getVersion($parameters['version_id']);
                    
                    $outObject['name'] = $postVersion->getName();
                    $outObject['content'] = $postVersion->getContent();
                    $outObject['date_update'] = $postVersion->getUpdateDate();
                    $outObject['author'] = $postVersion->getAuthor();
                    
                    $versionAuthor = $postVersion->getAuthor();
                    
                    $outObject['author'] = array(
                        'id' => $versionAuthor->getId(), 
                        'firstname' => $versionAuthor->getFirstname(), 
                        'lastname' => $versionAuthor->getLastname(), 
                        'profile_picture_url' => $httpHost.$storage->getWebPath($versionAuthor->getAvatar(), true)
                    );
                }
            }
        }
        
        return $outObject;
    }

    /**
     * getMeta
     *
     * @access public
     * @param string  $key  Meta key
     * @return PostMeta
     */
    public function getMeta($key)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('metaKey', $key));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->postMetas->matching($criteria)->count() === 1) {
            return $this->postMetas->matching($criteria)->first();
        }

        return null;
    }

    /**
     * isCollaborative
     * 
     * @access public
     * @return boolean
     * 
     */
    public function isCollaborative()
    {
        return null !== $this->getMeta('pad_id');
    }

    /**
     * padIsClosed
     * 
     * @access public
     * @return boolean
     * 
     */
    public function padIsClosed()
    {
        if ($postMeta = $this->getMeta('pad_is_closed')) {
            return (bool)$postMeta->getValue();
        }
        return false;
    }
}
