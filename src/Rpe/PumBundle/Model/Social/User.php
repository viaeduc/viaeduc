<?php
namespace Rpe\PumBundle\Model\Social;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\Criteria;

/**
 * @method boolean      isAdmin()
 * @method boolean      isAccountNonExpired()
 * @method boolean      isAccountNonLocked()
 * @method boolean      isCredentialsNonExpired()
 * @method boolean      isEnabled()
 * @method boolean      isVisible()
 * @method string       getSearchQ()
 * @method string       getSearchQBis()
 * @method string       getFullname()
 * @method array        getAccess()
 * @method array        getVisibility()
 * @method int          getDiskQuota()
 * @method boolean      isInvited()
 * @method boolean      isCommon()
 * @method boolean      isPrivilege()
 * @method boolean      isEditor()
 * @method boolean      isLike($user)
 * @method array        getAcceptedGroups(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getAwaitingGroups(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getInvitedGroups(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        checkGroup($group, array $orderBy = array(), $limite = null, $offset = null)
 * @method boolean      isGroupAdmin($group)
 * @method boolean      isBlogOwner($blog)
 * @method boolean      isGroupOwner($group)
 * @method boolean      isEditorOwner($editor)
 * @method boolean      isInGroup($group, $isUser = true)
 * @method boolean      isInEditor($editor)
 * @method boolean      isInBlog($blog, $isUser = true)
 * @method boolean      isFriend($user)
 * @method boolean      hasBadge($badgeKey)
 * @method boolean      isInEvent($event, $status = false)
 * @method string       getMeta($key)
 * @method boolean      isInEvent($event, $status = false)
 * @method array        getMyGroups(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getMyEvents(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getMyOwnGroups(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getPotentialGroups(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getAcceptedFriends(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getRequestedFriends(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getDraftsPosts(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getBlogPosts(array $orderBy = array(), $limite = null, $offset = null)
 * @method array        getResources(array $orderBy = array(), $limite = null, $offset = null)
 * @method Friend       getRelation($user)
 * @method array        getAwaitingInvitations()
 * @method array        getConfirmedInvitations()
 * @method array        getRemainingInvitations()
 * @method boolean      canInvitUser()
 * @method array        getOtherQuestions($id, array $orderBy = array('id' => Criteria::DESC), $limite = null, $offset = null)
 * @method array        getMediasInFolder($folder, array $orderBy = array('name' => Criteria::ASC), $limite = null, $offset = null)
 * @method array        getMediasFromIDs($mediaIDs, array $orderBy = array('id' => Criteria::DESC), $limite = null, $offset = null)
 * @method array        getReversedNotification($limit = null, $offset = null)
 * @method Editor       getEditor()
 * @method Criteria     handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 * @method array        serializeList($storage, $httpHost)
 * @method array        serialize($storage, $httpHost)
 * @method string       getJabberId($domain = '')
 *
 */
abstract class User extends SearchFactory implements AdvancedUserInterface
{
    /**
     * Gender
     */
    const SEX_TYPE_MALE   = 1;
    const SEX_TYPE_FEMALE = 2;

    /**
     * Status
     */
    const STATUS_TYPE_ACTIVE                = 'ACTIVE';
    const STATUS_TYPE_AWAITING_CONFIRMATION = 'AWAITING_CONFIRMATION';
    const STATUS_TYPE_LOCKED                = 'LOCKED';
    const STATUS_TYPE_EXPIRED               = 'EXPIRED';

    /**
     * User type
     */
    const TYPE_INVITED   = 'INVITED';
    const TYPE_EDITOR    = 'EDITOR';
    const TYPE_COMMON    = 'COMMON';
    const TYPE_PRIVILEGE = 'PRIVILEGE';
    const TYPE_ADMIN     = 'ADMIN';

    /**
     * VISIBILITY
     */
    const VISIBILITY_PREFIX = 'USER_';
    const VISIBILITY_PUBLIC = 'VISIBLE';
    const VISIBILITY_HIDDEN = 'HIDDEN';

    /**
     * META
     */
    const META_VISIBILITY                = 'user.config.visibility.hidden';
    const META_NOTIFICATION_LAST_VIEW_ID = 'user.notification.lastview.id';
    const META_TUTORIAL_ENABLED          = 'user.tutorial.enabled';
    const META_MESSAGE_LAST_VIEW_DATE    = 'user.message.lastview.date';
    const META_HAS_BLOG                  = 'user.blog.created';
    const META_HAS_EDITOR                = 'user.editor.created';
    const META_TOKEN_RESET_PWD           = 'user.token.pwd';

    const META_NOTIFICATION_MYCONTENT_SOMEONE_PUBLISH            = 'user.notifications.mycontent.someone_publish';
    const META_NOTIFICATION_MYCONTENT_SOMEONE_PUBLISH_RESOURCE   = 'user.notifications.mycontent.someone_publish_resource';
    const META_NOTIFICATION_MYCONTENT_SOMEONE_COMMENT            = 'user.notifications.mycontent.someone_comment';
    const META_NOTIFICATION_MYCONTENT_SOMEONE_COMMENT_RESOURCE   = 'user.notifications.mycontent.someone_comment_resource';
    const META_NOTIFICATION_MYCONTENT_SOMEONE_SHARE              = 'user.notifications.mycontent.someone_share';
    const META_NOTIFICATION_MYCONTENT_SOMEONE_SHARE_RESOURCE     = 'user.notifications.mycontent.someone_share_resource';
    const META_NOTIFICATION_MYCONTENT_SOMEONE_RECOMMEND          = 'user.notifications.mycontent.someone_recommend';
    const META_NOTIFICATION_MYCONTENT_SOMEONE_RECOMMEND_RESOURCE = 'user.notifications.mycontent.someone_recommend_resource';
    const META_NOTIFICATION_MYCONTENT_COREDACTOR_EDIT_RESOURCE   = 'user.notifications.mycontent.coredactor_edit_resource';
    const META_NOTIFICATION_COLLABORATIVE_RESOURCE_ACCESS        = 'user.notifications.collaborativeresource.access';
    const META_NOTIFICATION_MYCONTENT_SOMEONE_ANSWER             = 'user.notifications.mycontent.someone_answer';
    const META_NOTIFICATION_GROUP_SOMEONE_PUBLISH                = 'user.notifications.group.someone_publish';
    const META_NOTIFICATION_GROUP_SOMEONE_PUBLISH_MSG            = 'user.notifications.group.someone_publish_msg';

    const META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_PUBLISH            = 'user.notifications.mail.mycontent.someone_publish';
    const META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_PUBLISH_RESOURCE   = 'user.notifications.mail.mycontent.someone_publish_resource';
    const META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_COMMENT            = 'user.notifications.mail.mycontent.someone_comment';
    const META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_COMMENT_RESOURCE   = 'user.notifications.mail.mycontent.someone_comment_resource';
    const META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_SHARE              = 'user.notifications.mail.mycontent.someone_share';
    const META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_SHARE_RESOURCE     = 'user.notifications.mail.mycontent.someone_share_resource';
    const META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_RECOMMEND          = 'user.notifications.mail.mycontent.someone_recommend';
    const META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_RECOMMEND_RESOURCE = 'user.notifications.mail.mycontent.someone_recommend_resource';
    const META_NOTIFICATION_MAIL_MYCONTENT_COREDACTOR_EDIT_RESOURCE   = 'user.notifications.mail.mycontent.coredactor_edit_resource';
    const META_NOTIFICATION_MAIL_COLLABORATIVE_RESOURCE_ACCESS        = 'user.notifications.mail.collaborativeresource.access';
    const META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_ANSWER             = 'user.notifications.mail.mycontent.someone_answer';
    const META_NOTIFICATION_MAIL_GROUP_SOMEONE_PUBLISH                = 'user.notifications.mail.group.someone_publish';
    const META_NOTIFICATION_MAIL_GROUP_SOMEONE_PUBLISH_MSG            = 'user.notifications.mail.group.someone_publish_msg';
    const META_NOTIFICATION_MAIL_ADDRESS_PRO                          = 'user.notifications.mail.address_pro';

    const META_CONFIDENTIAL_FIND_SEARCH                       = 'user.confidentials.find_search';
    const META_CONFIDENTIAL_VIEW_MY_PAGE                      = 'user.confidentials.view_my_page';
    const META_CONFIDENTIAL_VIEW_MY_RESOURCES                 = 'user.confidentials.view_my_resources';
    const META_CONFIDENTIAL_VIEW_MY_RELATIONS                 = 'user.confidentials.view_my_relations';
    const META_CONFIDENTIAL_VIEW_MY_JOINED_GROUPS             = 'user.confidentials.view_my_joined_groups';
    const META_CONFIDENTIAL_VIEW_MY_INFORMATIONS              = 'user.confidentials.view_my_informations';
    const META_CONFIDENTIAL_VIEW_MY_FORMATION_AND_EXPERIENCES = 'user.confidentials.view_my_formation_and_experiences';
    const META_CONFIDENTIAL_VIEW_MY_INTERESTS                 = 'user.confidentials.view_my_interests';
    const META_CONFIDENTIAL_CONTACT_ME                        = 'user.confidentials.contact_me';

    const META_BELINSSO_USER_ID = 'user.belinsso.userid';
    const META_MEDIA_DISK_QUOTA = 'user.media.disk.quota';
    const META_TOKEN_CHAT       = 'user.chat.token';
    const META_TIMEZONE         = 'user.timezone';
    const META_TMP_INTERVENANT  = 'user.tmp.intervenant';

    /**
     * Check if user is admin
     *
     * @return boolean
     * @access public
     */
    public function isAdmin()
    {
        return $this->getType() === self::TYPE_ADMIN;
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return Boolean true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired()
    {
        return $this->getStatus() !== self::STATUS_TYPE_EXPIRED;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return Boolean true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        return $this->getStatus() !== self::STATUS_TYPE_LOCKED;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return Boolean true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return Boolean true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return $this->getStatus() === self::STATUS_TYPE_ACTIVE;
    }


    /**
     * Checks whether the user is visible, call isEnabled
     *
     * @return Boolean true if the user is enabled, false otherwise
     */
    public function isVisible()
    {
        return $this->isEnabled();
    }
    
    /**
     * Checks whether the user profil is public
     *
     * @return Boolean true if the user's profil  is public, false otherwise
     */
    public function isPublic()
    {
        $meta = $this->getMeta(self::META_CONFIDENTIAL_VIEW_MY_PAGE);
        if (null === $meta || $meta->getValue() == "everybody") {
            return true;
        }
        return false;
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
        return $this->getFormattedSort('fullname');
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
        // search also by academy
        if ($this->getAcademy() != null) {
            return $this->getAcademy()->getName();
        } else {
            return null;
        }
    }

    /**
     * Get full name of the user
     *
     * @access public
     * @return string  Full name as firstanme, lastname
     */
    public function getFullname()
    {
        return $this->getFirstname().' '.$this->getLastname();
    }

    /**
     * Get access array list
     *
     * @access public
     * @return array Return array contain all access items
     */
    public function getAccess()
    {
        if (false === $this->isInvited()) {
            $access = array(self::VISIBILITY_PUBLIC, self::VISIBILITY_PREFIX.$this->getId());
        } else {
            $access = array(self::VISIBILITY_PREFIX.$this->getId());
        }

        foreach ($this->getAcceptedGroups() as $group) {
            if (null !== $group = $group->getGroup()) {
                $access[] = Group::VISIBILITY_PREFIX.$group->getId();
            }
        }

        return $access;
    }

    /**
     * getPostVisibility
     *
     * @access public
     * @return array Return array contain all visibility
     *
     */
    public function getVisibility()
    {
        if ($this->getStatus() !== self::STATUS_TYPE_ACTIVE) {
            return array(self::VISIBILITY_HIDDEN);
        }

        $is_hidden_config = $this->getMeta(self::META_CONFIDENTIAL_FIND_SEARCH);
        if (null !== $is_hidden_config && $is_hidden_config->getValue() == "me") {
            return array(self::VISIBILITY_HIDDEN);
        }

        $visibility = array(self::VISIBILITY_PUBLIC);

        if ((true === $this->isInvited()) || (null !== $is_hidden_config && $is_hidden_config->getValue() == "myfriends")) {
            array_shift($visibility);
            foreach ($this->getAcceptedFriends() as $uif) {
                if($friend = $uif->getFriend()){
                    $visibility[] = User::VISIBILITY_PREFIX.$friend->getId();
                }
            }

        } else {
            foreach ($this->getAcceptedGroups() as $group) {
                if (null !== $group = $group->getGroup()) {
                    $visibility[] = Group::VISIBILITY_PREFIX.$group->getId();
                }
            }
        }
        return $visibility;
    }

    /**
     * get user disk quota usage
     *
     * @access public
     * @return int
     */
    public function getDiskQuota()
    {
        $meta = $this->getMeta(User::META_MEDIA_DISK_QUOTA);
        if (null !== $meta) {
            return (int)$meta->getValue();
        }
        return 0;
    }

    /**
     * Check if user is invited
     *
     * @access public
     * @return boolean
     */
    public function isInvited()
    {
        return $this->getType() === self::TYPE_INVITED;
    }

    /**
     * Check if user is common user
     *
     * @access public
     * @return boolean
     */
    public function isCommon()
    {
        return $this->getType() === self::TYPE_COMMON;
    }

    /**
     * Check if user is privilege user
     *
     * @access public
     * @return boolean
     */
    public function isPrivilege()
    {
        return $this->getType() === self::TYPE_PRIVILEGE;
    }

   /**
     * Check if user is editor
     *
     * @access public
     * @return boolean
     */
    public function isEditor()
    {
        return $this->getType() === self::TYPE_EDITOR;
    }

    /**
     * Check if user is liked by user
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
     * Check if user is followed by user
     *
     * @access public
     * @param  User $user    User object
     *
     * @return boolean
     */
    public function isFollowed($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('user', $user));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->followers->matching($criteria)->count() === 0) {
            return false;
        }

        return true;
    }

    /**
     * Get the user's accepted groups
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of groups
     */
    public function getAcceptedGroups(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::IN_GROUP));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->groups->matching($criteria);
    }

    /**
     * Get the user's awaiting groups
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of groups
     */
    public function getAwaitingGroups(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', UserInGroup::STATUS_REQUEST));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->groups->matching($criteria);
    }

    /**
     * Get the user's invited groups
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of groups
     */
    public function getInvitedGroups(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', UserInGroup::STATUS_INVITED));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->groups->matching($criteria);
    }

    /**
     * Get the user in group object for specific group
     *
     * @access public
     * @param Group     $group     Group
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of groups
     */
    public function checkGroup($group, array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('group', $group));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->groups->matching($criteria);
    }

    /**
     * Check if user is group admin
     *
     * @access public
     * @param Group  $group  Group object to check
     *
     * @return boolean
     */
    public function isGroupAdmin($group)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('group', $group));
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::IS_ADMIN));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->groups->matching($criteria)->count() === 0) {
            return false;
        }

        return true;
    }

    /**
     * Check if user is blog owner
     *
     * @access public
     * @param Blog  $blog  Blog object to check
     *
     * @return boolean
     */
    public function isBlogOwner($blog)
    {
        if ($this === $blog->getOwner()) {
            return true;
        }
        return false;
    }

    /**
     * Check if user is group owner
     *
     * @access public
     * @param Group  $group  Group object to check
     *
     * @return boolean
     */
    public function isGroupOwner($group)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('group', $group));
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::IS_OWNER));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->groups->matching($criteria)->count() === 0) {
            return false;
        }

        return true;
    }
    
    /**
     * Check if user has added the discussion to favorite
     *
     * @access public
     * @param Post  $discussion 
     *
     * @return boolean
     */
    public function hasFavorited($discussion)
    {
        
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('discussion', $discussion));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);
    
        if ($this->favoriteDiscussions->matching($criteria)->count() === 0) {
            return false;
        }
    
        return true;
    }

    /**
     * Check if user is editor owner
     *
     * @access public
     * @param Editor  $editor  Editor object to check
     *
     * @return boolean
     */
    public function isEditorOwner($editor)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('editor', $editor));
        $criteria->andWhere(Criteria::expr()->lte('status', UserInEditor::STATUS_OWNER));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->editors->matching($criteria)->count() === 0) {
            return false;
        }
        return true;
    }

    /**
     * Check if user is in group
     *
     * @access public
     * @param Group  $group  Group object to check
     * @param boolean  $isUser  Check only user, except admin
     *
     * @return boolean
     */
    public function isInGroup($group, $isUser = true)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('group', $group));

        if ($isUser) {
            $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::IN_GROUP));
        }

        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        $userInGroups = $this->groups->matching($criteria);

        if ($userInGroups->count() !== 0) {
            return $userInGroups->first();
        }

        return false;
    }

    /**
     * Check if user is in editor
     *
     * @access public
     * @param Editor  $editor   Editor object to check
     *
     * @return boolean
     */
    public function isInEditor($editor)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('editor', $editor));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        $userInEditors = $this->editors->matching($criteria);

        if ($userInEditors->count() !== 0) {
            return $userInEditors->first();
        }

        return false;
    }

    /**
     * Check if user is in blog
     *
     * @access public
     * @param Blog  $blog   Blog object to check
     *
     * @return boolean
     */
    public function isInBlog($blog, $isUser = true)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('blog', $blog));

        if ($isUser) {
            $criteria->andWhere(Criteria::expr()->lte('status', UserInBlog::IN_BLOG));
        }

        $criteria = $this->handleCriteria($criteria, array(), 1, null);
        $userInBlogs = $this->blogs->matching($criteria);

        if ($userInBlogs->count() !== 0) {
            return $userInBlogs->first();
        }
        return false;
    }

    /**
     * Check if user is friend with user
     *
     * @access public
     * @param User  $user   User object to check
     *
     * @return boolean
     */
    public function isFriend($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('friend', $user));
        $criteria->andWhere(Criteria::expr()->lte('status', Friend::STATUS_ACCEPTED));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->friends->matching($criteria)->count() === 0) {
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
        foreach ($this->badges as $badge) {
            if ($badge->getBadgeKey() == $badgeKey) {
                return true;
            }
        }
        return false;
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

        if ($this->userMetas->matching($criteria)->count() === 1) {
            return $this->userMetas->matching($criteria)->first();
        }

        return null;
    }

    /**
     * Check if user is in event
     *
     * @access public
     * @param Event  $event   Event object to check
     * @param string $status  User in event status
     *
     * @return boolean
     */
    public function isInEvent($event, $status = false)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('event', $event));

        if ($status) {
            $criteria->andWhere(Criteria::expr()->eq('status', $status));
        }

        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        $userInEvents = $this->events->matching($criteria);

        if ($userInEvents->count() !== 0) {
            return $userInEvents->first();
        }

        return false;
    }

    /**
     * Get user groups list
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of Groups
     */
    public function getMyGroups(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::IN_GROUP));
        $criteria->andWhere(Criteria::expr()->neq('group', null));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->groups->matching($criteria);
    }

    /**
     * Get user events list
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of events
     */
    public function getMyEvents(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->events->matching($criteria);
    }
    
    /**
     * Get user experiences list
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of experiences
     */
    public function getMyExperiences(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);
    
        return $this->experiences->matching($criteria);
    }

    /**
     * Get user owns groups list
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of Groups
     */
    public function getMyOwnGroups(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::STATUS_ADMIN));
        $criteria->andWhere(Criteria::expr()->neq('group', null));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->groups->matching($criteria);
    }

    /**
     * Get user potentials groups list
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of Groups
     */
    public function getPotentialGroups(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('status', UserInGroup::POTENTIAL_GROUP));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->groups->matching($criteria);
    }

    /**
     * Get user accepted friends list
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of Friends
     */
    public function getAcceptedFriends(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', Friend::STATUS_ACCEPTED));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->friends->matching($criteria);
    }

    /**
     * Get user requested friends list
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of Friends
     */
    public function getRequestedFriends(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', Friend::STATUS_ON_HOLD));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->friends->matching($criteria);
    }

    /**
     * Get user's draft posts
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of Posts
     */
    public function getDraftsPosts(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('resource', true));
        $criteria->andWhere(Criteria::expr()->eq('status', Post::STATUS_DRAFTING));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->posts->matching($criteria);
    }

    /**
     * Get user's profil posts
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of Posts
     */
    public function getBlogPosts(array $orderBy = array(), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        //$criteria->andWhere(Criteria::expr()->eq('resource', true));
        $criteria->andWhere(Criteria::expr()->eq('type', Post::TYPE_BLOG));
        $criteria->andWhere(Criteria::expr()->eq('publishedGroup', null));
        $criteria->andWhere(Criteria::expr()->eq('status', Post::STATUS_PUBLISHED));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->posts->matching($criteria);
    }

    /**
     * Get user's resources
     *
     * @access public
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of Posts
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
     * Get relation with user
     *
     * @access public
     * @param User  user  User object to check
     *
     * @return Friend
     */
    public function getRelation($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('friend', $user));

        if ($this->friends->matching($criteria)->count() === 1) {
            return $this->friends->matching($criteria)->first();
        }

        return null;
    }

    /**
     * Get user's waiting invitations
     *
     * @access public
     * @return array  Array of invitations
     */
    public function getAwaitingInvitations()
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', Invitation::STATUS_AWAITING));

        return $this->invitations->matching($criteria);
    }

    /**
     * Get user's confirmed invitations
     *
     * @access public
     * @return array  Array of invitations
     */
    public function getConfirmedInvitations()
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('status', Invitation::STATUS_CONFIRMED));

        return $this->invitations->matching($criteria);
    }

    /**
     * Get user's remaining invitations
     *
     * @access public
     * @return array  Array of invitations
     */
    public function getRemainingInvitations()
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->neq('status', Invitation::STATUS_CANCELLED));

        return (Invitation::INVITATION_COUNTER - $this->invitations->matching($criteria)->count());
    }

    /**
     * Check if user can invite others
     *
     * @return boolean
     * @access public
     */
    public function canInvitUser()
    {
        if ($this->getRemainingInvitations() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get user questions other than id
     *
     * @access public
     * @param int       $id        Question id
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of questions
     */
    public function getOtherQuestions($id, array $orderBy = array('id' => Criteria::DESC), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->neq('id', $id));
        $criteria->andWhere(Criteria::expr()->eq('accesstype', Question::ACCESS_PUBLIC));
        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->questions->matching($criteria);
    }

    /**
     * Get user medias in specific folder
     *
     * @access public
     * @param Folder    $folder    Folder object
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of medias
     */
    public function getMediasInFolder($folder, array $orderBy = array('name' => Criteria::ASC), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        if (null === $folder) {
            $criteria->andWhere(Criteria::expr()->isNull('folder'));
        } else {
            $criteria->andWhere(Criteria::expr()->eq('folder', $folder));
        }

        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->medias->matching($criteria);
    }

    /**
     * Get user medias by ids
     *
     * @access public
     * @param array     $mediaIDs  Media ids
     * @param array     $orderBy   Array containing order
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of medias
     */
    public function getMediasFromIDs($mediaIDs, array $orderBy = array('id' => Criteria::DESC), $limite = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->in('id', $mediaIDs));

        $criteria = $this->handleCriteria($criteria, $orderBy, $limite, $offset);

        return $this->medias->matching($criteria);
    }

    /**
     * Get notifications by reverse order
     *
     * @access public
     * @param int       $limit     Number of limit
     * @param int       $offset    Offset start
     *
     * @return array  Array of notifications
     */
    public function getReversedNotification($limit = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria = $this->handleCriteria($criteria, array('date' => Criteria::DESC), $limit, $offset);

        return ($this->notifications->matching($criteria));
    }

    /**
     * Get user editor
     *
     * @access public
     *
     * @return Editor  Editor object or null
     */
    public function getEditor()
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->lte('status', UserInEditor::IS_OWNER));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        $userInEditor = $this->editors->matching($criteria);

        if ($userInEditor->count() !== 0) {
            return $userInEditor->first()->getEditor();
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
     * serializeList
     *
     * @access public
     * @param Object      $storage   Storage object
     * @param string      $httpHost  Http host
     *
     * @return array
     */
    public function serializeList($storage, $httpHost)
    {
        $outObject = array(
            'user_id' => $this->getId(),
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'profile_picture_url' => ($this->getAvatar()) ? $httpHost.$storage->getWebPath($this->getAvatar(), true) : null,
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
            'user_id' => $this->getId(),
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'gender' => ($this->getSex() == 'Monsieur') ? 'man' : 'woman',
            'profile_picture_url' => ($this->getAvatar()) ? $httpHost.$storage->getWebPath($this->getAvatar(), true) : null,
            'birthdate' => $this->getBirthdate(),
            'email' => array('private' => $this->getEmailPrivate(), 'pro' => $this->getEmailPro()),
            'phone' => $this->getPhone(),
            'institution' => $this->getInstitutionName(),
            'occupation' => (null !== $this->getOccupation()) ? array('id' => $this->getOccupation()->getId(), 'name' => $this->getOccupation()->getName()) : null,
            'status' => $this->getStatus(),
            'instructed_discipline' => array(),
            'interests' => array()
        );

        foreach ($this->getInstructedDisciplines() as $instructedDiscipline) {
            $outObject['instructed_discipline'][] = array('id' => $instructedDiscipline->getId(), 'name' => $instructedDiscipline->getName());
        }

        foreach ($this->getInterests() as $interest) {
            $outObject['interests'][] = array('id' => $interest->getId(), 'name' => $interest->getName());
        }

        return $outObject;
    }

    /**
     * Unique id for the chat server
     *
     * @access public
     * @param string $domain : chat domain
     * @return string
     */
    public function getJabberId($domain = '')
    {
        $email = str_replace('@', '\40', $this->getEmailPro());

        if ($domain) {
            $email .= '@' . $domain;
        }

        return $email;
    }
    
    public function canContactMe($user)
    {
        $showContact = false;
        $canContactMe = ($meta = $this->getMeta(User::META_CONFIDENTIAL_CONTACT_ME)) ? $meta->getValue() : false;
        if (!$canContactMe || ($canContactMe == 'myfriends' && $this->isFriend($user)) || $canContactMe == "everybody") {
            $showContact = true;
        }
        return $showContact;
    }
}
