<?php

namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;

/**
 * @method GroupMeta  getMeta($key)
 * @method Criteria handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 * @method string   getSearchQ()
 * @method string   getSearchQBis()
 * @method array    getVisibility()
 * @method boolean  isVisible()
 * @method boolean  isPublic()
 * @method boolean  isPrivate()
 * @method boolean  isSecret
 * @method User     getOwner()
 * @method string   getOwnername()
 * @method string   getOwnerId()
 * @method array    getAdmins(array $orderBy = array(), $limite = null, $offset = null)
 * @method array    getManagers(array $orderBy = array(), $limite = null, $offset = null)
 * @method array    getMembers(array $orderBy = array(), $limite = null, $offset = null)
 * @method array    getRequesters(array $orderBy = array(), $limite = null, $offset = null)
 * @method array    getSimplePosts(array $orderBy = array(), $limite = null, $offset = null)
 * @method array    getResources(array $orderBy = array(), $limite = null, $offset = null)
 * @method array    getPublishedPosts(array $orderBy = array(), $limite = null, $offset = null)
 * @method array    getPastSurveys(array $orderBy = array(), $limite = null, $offset = null)
 * @method array    getPresentSurveys(array $orderBy = array(), $limite = null, $offset = null)
 * @method Survey   getLastPresentSurvey()
 * @method array    getFutureSurveys(array $orderBy = array(), $limite = null, $offset = null)
 * @method array    serializeList($storage, $httpHost)
 * @method array    serialize($storage, $httpHost)
 * @method boolean  isModuleEnabled($groupModule)
 * @method string   getModuleInSlot($slot)
 * @method string   getModuleSlotIn($module)
 */
abstract class Group extends SearchFactory
{

    /**
     * Access type
     */
    const ACCESS_PUBLIC        = 'PUBLIC';
    const ACCESS_ON_DEMAND     = 'ON_DEMAND';
    const ACCESS_ON_INVITATION = 'ON_INVITATION';

    /**
     * VISIBILITY
     */
    const VISIBILITY_PREFIX = 'GR_';
    const VISIBILITY_PUBLIC = 'VISIBLE';
    const VISIBILITY_HIDDEN = 'HIDDEN';

    /**
     * META
     *
     */
    const META_GROUP_MODULE_ENABLED    = 'group.modules.enabled';
    const META_GROUP_MODULE_IN_SLOT    = 'group.modules.in_slot';
    const META_GROUP_MODULE_SLOT_IN    = 'group.modules.slot_in';

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
     * getMeta
     *
     * @access publics
     * @param string  $key  Meta key
     * @return GroupMeta
     */
    public function getMeta($key)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('metaKey', $key));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->groupMetas->matching($criteria)->count() === 1) {
            return $this->groupMetas->matching($criteria)->first();
        }

        return null;
    }

    /**
     * @access public
     * @return array Return array contain all visibility
     */
    public function getVisibility()
    {
        $visibility = array();

        if (false === $this->isSecret()/* && (null === $this->getParent() || false === $this->getParent()->isSecret())*/) {
            $visibility[] = self::VISIBILITY_PUBLIC;
            $visibility[] = self::VISIBILITY_PREFIX.$this->getId();

            if ($this->isPrivate() && (null !== $this->getParent() && $this->getParent()->isSecret())) {
                array_shift($visibility);
            }
        } else {
            $visibility[] = self::VISIBILITY_HIDDEN;
        }

        return $visibility;
    }

    /**
     * Check if Group is public
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
     * Check if Group is private
     *
     * @access public
     * @return boolean
     */
    public function isPrivate()
    {
        if ($this->getAccessType() === self::ACCESS_ON_DEMAND) {
            return true;
        }

        return false;
    }

    /**
     * Get indentedName for subGroups in select list
     *
     * @access public
     * @return boolean
     */
    public function getIndentedName()
    {
        $indented = $this->getParent() ? "---- " : "";
        return $indented . $this->getName();
    }

    /**
     * Check if Blog is private
     *
     * @access public
     * @return boolean
     */
    public function isSecret()
    {
        if ($this->getAccessType() === self::ACCESS_ON_INVITATION) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return User Owner of group or null
     */
    public function getOwner()
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', UserInGroup::STATUS_OWNER));
        $criteria->setMaxResults(1);

        if ($this->users->matching($criteria)->count()) {
            return $this->users->matching($criteria)->first()->getUser();
        }

        return null;
    }

    /**
     * @access public
     * @param User $user User object
     *
     * @return check if user is admin of group
     */
    public function isAdmin($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::STATUS_ADMIN));
        $criteria->andWhere(Criteria::expr()->eq('user', $user));
        $criteria->setMaxResults(1);

        if ($this->users->matching($criteria)->count()) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return string  Ownername of group or empty
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
     * @return string  Owner id of group or empty
     */
    public function getOwnerId()
    {
        if($owner = $this->getOwner()){
            return $owner->getId();
        }
        return "";
    }

    /**
     * getAdmins
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
     */
    public function getAdmins(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::IS_ADMIN));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->users->matching($criteria);
    }

    /**
     * getManagers of group
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
     */
    public function getManagers(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::IS_MANAGER));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->users->matching($criteria);
    }

    /**
     * getMembers of group
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
     */
    public function getMembers(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::IN_GROUP));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->users->matching($criteria);
    }

    /**
     * getRequesters
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
     */
    public function getRequesters(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', UserInGroup::STATUS_REQUEST));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->users->matching($criteria);
    }

    /**
     * getSimplePosts
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
     */
    public function getSimplePosts(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('resource', false));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->posts->matching($criteria);
    }

    /**
     * getResources
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
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
     * getPublishedPosts
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
     *
     */
    public function getPublishedPosts(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', Post::STATUS_PUBLISHED));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->posts->matching($criteria);
    }

    /**
     * getPastSurveys
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
     */
    public function getPastSurveys(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('endDate', new \DateTime()));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->surveys->matching($criteria);
    }

    /**
     * getPresentSurveys
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
     *
     */
    public function getPresentSurveys(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->orX(
            Criteria::expr()->lte('startDate', new \DateTime()),
            Criteria::expr()->isNull('startDate')
        ));
        $criteria->andWhere(Criteria::expr()->orX(
            Criteria::expr()->gt('endDate', new \DateTime()),
            Criteria::expr()->isNull('endDate')
        ));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->surveys->matching($criteria);
    }

    /**
     * getLastPresentSurvey
     *
     * @access public
     *
     * @return Survey
     */
    public function getLastPresentSurvey()
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('startDate', new \DateTime()));
        $criteria->andWhere(Criteria::expr()->gt('endDate', new \DateTime()));
        $criteria = $this->handleCriteria($criteria, array('startDate' => Criteria::DESC), 1, null);

        if ($this->surveys->matching($criteria)->count() === 1) {
            return $this->surveys->matching($criteria)->first();
        }

        return null;
    }

    /**
     * getFutureSurveys
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array
     */
    public function getFutureSurveys(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->gt('startDate', new \DateTime()));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->surveys->matching($criteria);
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
     * Check if group is bookmarked by user
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
     * isModuleEnabled
     *
     * @access public
     * @param string $groupModule   Module name
     *
     * @return boolean
     */
    public function isModuleEnabled($groupModule)
    {
        $group_meta = $this->getMeta(self::META_GROUP_MODULE_ENABLED.'.'.$groupModule);

        if(null !== $group_meta) {
            return $group_meta->getValue();
        }

        return true;
    }

    /**
     * getModuleInSlot
     *
     * @access public
     * @param string $slot    Slot key
     *
     * @return string
     */
    public function getModuleInSlot($slot)
    {
        $group_meta = $this->getMeta(self::META_GROUP_MODULE_IN_SLOT.'.'.$slot);

        if(null !== $group_meta) {
            return $group_meta->getValue();
        }

        return false;
    }

    /**
     * getModuleSlotIn
     *
     * @access public
     * @param string $module    Module name
     *
     * @return string|boolean
     */
    public function getModuleSlotIn($module)
    {
        $group_meta = $this->getMeta(self::META_GROUP_MODULE_SLOT_IN.'.'.$module);

        if(null !== $group_meta) {
            return $group_meta->getValue();
        }

        return false;
    }

    /**
     * Get current event list
     *
     * @access public
     * @param string $module    Module name
     *
     * @return string|boolean
     */
    public function getCurrentEvents(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->gt('endDate', new \DateTime()));
        $orderBy = array(
            'startDate' => Criteria::ASC
        );

        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);
        return $this->events->matching($criteria);
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
            'group_id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'profile_picture_url' => ($this->getPicture()) ? $httpHost.$storage->getWebPath($this->getPicture(), true) : null,
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
            'group_id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'profile_picture_url' => ($this->getPicture()) ? $httpHost.$storage->getWebPath($this->getPicture(), true) : null,
        );

        return $outObject;
    }
}
