<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\GroupWidget;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\UserInGroup;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Notification;
use Rpe\PumBundle\Model\Social\Log;
use Pagerfanta\Pagerfanta;
use Rpe\PumBundle\Model\Social\Invitation;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Rpe\PumBundle\Model\Social\Comment;

/**
 * Group controller
 *
 * @method Response groupAction(Request $request, $id)
 * @method Response groupPublicationsListAction(Request $request, $page, $groupid)
 * @method Response groupMemberListAction(Request $request, $id)
 * @method Response groupAdminListAction(Request $request, $id)
 * @method Response groupResourceListAction(Request $request, $id)
 * @method Response groupSubGroupListAction(Request $request, $id)
 * @method Response groupResourcePadListAction(Request $request, $id)
 * @method Response groupCreateAction(Request $request, $parent_group_id = null)
 * @method Response groupEditAction(Request $request, $id)
 * @method Response groupInvitMembersAction(Request $request, $id)
 * @method Response groupInvitListMembersAction(Request $request, $id, $page)
 * @method Response requestManageGroupAction(Request $request, $action, $group_id, $member_id)
 * @method Response requestGroupAction(Request $request, $mode, $id)
 * @method Response groupAdministrationAction($id)
 * @method Response groupAjaxUserList($group_id, $status = null)
 * @method Response groupAjaxUserInfos($group_id, $user_id)
 * @method Response groupListAction(Request $request, $page)
 * @method Response groupsAction($mode)
 * @method void     registerGroupMeta(Group $group, $key, $value, $type = 'modules')
 * @method void     setDefaultPosition($group, array $modules)
 * @method void     setModulePosition(Group $group, $module, $position)
 * @method void     enableModule(Group $group, $module, $enable = true)
 * @method Response groupModuleAction($group_id, $module, $action)
 * @method Response groupModulePositionAction(Request $request, $group_id, $module)
 * @method Response groupStats(Request $request, $id)
 * @method Response inviteRelationsAction(Request $request, $id, $scope)
 * @method Response inviteExternalAction(Request $request, $id_group)
 * @method Response groupNotMemberListAction(Request $request, $id)
 * @method Response inviteReturnAction(Request $request, $id)
 * @method array    createUserFromInvitation(Invitation $invitation)
 * @method string   getUserTypeFromEmail($email)
 * @method void     sendEmailFromInvitation(Invitation $invitation, $id_group = null)
 * @method Response groupWidgetAction(Request $request, $groupId, $type, $widgetId = null)
 * @method Response groupWidgetDeleteAction(Request $request, $groupId, $widgetId)
 * @method Response groupWidgetEditAction(Request $request, $groupId, $widgetId)
 * @method Response parseRssUrl($groupId, $id)
 *
 */
class GroupController extends Controller
{
    /** List mode: all groups */
    const LISTMODE_ALL              =   'all_groups';
    /** List mode: suggested groups only */
    const LISTMODE_SUGGESTEDGROUPS  =   'suggested';
    /** List mode: groups where user is */
    const LISTMODE_MYGROUPS         =   'my_groups';
    /** List mode: groups where user is owner */
    const LISTMODE_OWNGROUPS        =   'own_groups';
    /** List mode: groups where user wait to be accepted */
    const LISTMODE_AWAITINGS        =   'awaiting';
    /** List mode: groups where user has been invited to join */
    const LISTMODE_INVITED          =   'invited';
    /** List mode: public groups only */
    const LISTMODE_PUBLIC           =   'public';
    /** List mode: private groups only */
    const LISTMODE_PRIVATE          =   'private';
    /** List mode: favorite groups only */
    const LISTMODE_FAVORITE          =   'favorite';

    /**
     * Groups action
     */
    const ACTION_ACCEPT           = 'accept';
    const ACTION_REJECT           = 'reject';
    const ACTION_SET_ADMIN        = 'set_admin';
    const ACTION_REMOVE_ADMIN     = 'remove_admin';
    const ACTION_SET_MODERATOR    = 'set_moderator';
    const ACTION_REMOVE_MODERATOR = 'remove_moderator';
    const ACTION_SET_USER         = 'set_user';
    const ACTION_BAN_USER         = 'ban_user';
    const ACTION_UNBAN_USER       = 'unban_user';

    /**
     * Display a group page.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of group
     * @return Response A Response instance
     *
     * @Route(path="/group/{id}", name="group", defaults={"_project"="rpe"})
     */
    public function groupAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();
        $group = $this->getRepository('group')->find($id);
        $active_tab = $request->query->get('tab', 1);

        if (null === $group) {
            // $this->throwNotFound('error.group.not_found');
            return $this->redirect($this->generateUrl('home'));
        }

        // Set the defaults position of each group module
        $modules = array('questions', 'agenda', 'survey', 'group-facebook', 'group-rss', 'group-theme');
        if ($this->get('pum.vars')->getValue('add_etherpad_group-module')) {
            $modules[] = 'resource-etherpad';
        }
        $this->setDefaultPosition($group, $modules);

        $userHasAccess = true;
        $group_parent = $group->getParent();

        $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group);
        $user_in_group_parent = null;
        if ($group_parent !== null) {
            $user_in_group_parent = $this->getRepository('user_in_group')->getUserInGroup($user, $group_parent);
        }

        if (false === $group->isPublic()) {
            if (null === $user_in_group || !$user_in_group->isUserInGroup()) {
                $userHasAccess = false;
                if ($group->isSecret()) {
                    return $this->redirect($this->generateUrl('home'));
                }
            }
        } else {  // group public in group sur invitation
            if ($group_parent !== null) { // && false === $group_parent->isPublic()) {
                if ($user_in_group_parent == null || !$user_in_group_parent->isUserInGroup()) {
                    $userHasAccess = false;
                    $this->addError($this->get('translator')->trans('common.action.group.sub.access_error', array('%name%' => $group_parent->getName()), 'rpe'));
                    return $this->redirect($this->generateUrl('group', array('id' => $group_parent->getId())));
                }
            }
        }

        if ($user->isInvited() && $user_in_group_parent === null) {
            if (null === $user_in_group || $user_in_group->getStatus() > UserInGroup::IN_GROUP) {
                return $this->redirect($this->generateUrl('home'));
            };
        }

        $this->get('rpe.logs')->create($user, Log::TYPE_SEE_GROUP, $user, $group);

        $post = $this->createObject('post');
        $form  = $this->createNamedForm('post', 'pum_object', $post, array(
            'attr'        => array('class' => 'post-form', 'id' => "simple-post-form"),
            'form_view'   => $this->createFormViewByName('post', 'simple', $update = false),
            'with_submit' => false
        ));
        $form = $this->addLinkPreviewToForm($form);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $post
                ->setAuthor($user)
                ->setCommentStatus(true)
                ->setResource(false)
                ->setBroadcast(false)
                ->setGlobal(false)
                ->setImportant(false)
                ->setType(Post::TYPE_GROUP)
                ->setStatus(Post::STATUS_PUBLISHED)
                ->setCreateDate(new \DateTime())
                ->setUpdateDate(new \DateTime())
            ;

            $group->addPost($post);
            $user->addPost($post);
            $post->setPublishedGroup($group);
            $post = $this->handleLinkPreview($form, $post, false);
            $this->persist($post, $group, $user)->flush();

            $this->get('rpe.search.index.factory')->update($group);
            $this->get('rpe.notifications')->wait(Notification::TYPE_PUBLICATION, $user, $post);
            $this->get('rpe.logs')->create($user, Log::TYPE_POST_PUBLICATION, $user, $group);

            if ($request->isXmlHttpRequest()) {
                return $this->render('pum://includes/common/componants/publications/publications.html.twig', array(
                    'user'        => $user,
                    'post'        => $post,
                    'single'      => true,
                ));
            }

            return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
        }

        $form_cover  = null;
        $form_avatar = null;

        if ($user->isGroupOwner($group) || $user->isGroupAdmin($group)) {

            $form_cover  = $this->createNamedForm('cover', 'pum_object', $group, array(
                'form_view'   => $this->createFormViewByName('group', 'cover', $update = false),
            ));
            $form_cover = $this->addCroppedDataToForm($form_cover);

            if ($request->isMethod('POST') && $form_cover->handleRequest($request)->isValid()) {
                $cover = $form_cover->get('originalCover')->getData();
                if ($cover instanceof Media) {
                    $coords = $coords = $this->getCropCoordsFromForm($form_cover);

                    $group->setCover($this->get('tool.avatar')->getCroppedImage($cover, $coords));
                    $this->setGroupMeta($group, 'group.cover.coords', json_encode($coords));

                    $this->flush();

                    if (!$request->isXmlHttpRequest()) {
                        return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
                    }

                    return new Response();
                }
            }

            $form_avatar  = $this->createNamedForm('avatar', 'pum_object', $group, array(
                'form_view'   => $this->createFormViewByName('group', 'avatar', $update = false),
            ));
            $form_avatar = $this->addCroppedDataToForm($form_avatar);

            if ($request->isMethod('POST') && $form_avatar->handleRequest($request)->isValid()) {
                $avatar = $form_avatar->get('picture')->getData();
                if ($avatar instanceof Media) {
                    $coords = $coords = $this->getCropCoordsFromForm($form_avatar);

                    $group->setPicture($this->get('tool.avatar')->getCroppedImage($avatar, $coords));

                    $this->flush();

                    // $this->get('rpe.search.index.factory')->update($group);

                    if (!$request->isXmlHttpRequest()) {
                        return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
                    }

                    return new Response();
                }
            }
        }

        $canCreateSubGroup = true;
        if ($group_parent !== null) {
            $canCreateSubGroup = false;
        } else {
            if (null === ($subgroupLevel = $group->getSubgroupLevel())) {
                $subgroupLevel = 2;
            }
            if ((null === $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group))
            || ($user_in_group->getStatus() > $subgroupLevel)) {
                $canCreateSubGroup = false;
            }
        }

        $newCoContentForm = $this->createNamedForm('rpe_create_co_content', 'form')
            ->add('title', 'text')
            ->add('group_id', 'hidden', array('data' => $id))
        ;

        return $this->render('pum://page/group/group.html.twig', array(
            'group'             => $group,
            'active_tab'        => $active_tab,
            'form'              => $form->createView(),
            'form_cover'        => (null === $form_cover) ? $form_cover : $form_cover->createView(),
            'form_avatar'       => (null === $form_avatar) ? $form_avatar : $form_avatar->createView(),
            'userHasAccess'     => $userHasAccess,
            'userInGroup'       => $user_in_group,
            'subGroupsCount'    => $userHasAccess ? $this->getRepository('group')->getSubGroups($user, $group, false, null, null, 'count') : 0,
            'subGroups'         => $userHasAccess ? $this->getRepository('group')->getSubGroups($user, $group, false, 8) : false,
            'canCreateSubGroup' => $canCreateSubGroup,
            'publications'      => $this->getRepository('post')->getGroupResources($group, $user, false),
            'countPublications' => $this->getRepository('post')->getGroupResources($group, $user, false, null, null, 'count'),
            'countHeadlines'    => $this->getRepository('post')->getGroupHeadlines($group, false, null, null, false, true),
            'members'           => $this->getRepository('user')->getMembers($group, false),
            'admins'            => $this->getRepository('user')->getAdmins($group, false),
            'newCoContentForm'  => $newCoContentForm->createView(),
            'padResources'      => $this->getRepository('post')->getGroupPadResources($group, $user, false, 3),
            'padResourcesCount' => $this->getRepository('post')->getGroupPadResources($group, $user, false, null, 'count'),
            'groupwidget'       => $this->createObject('social_group_widget'),
        ));
    }

    /**
     * XHR Method to display publications from a group.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $page        Page number
     * @param  string  $groupid     Group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-group-publications/{groupid}/{page}/", name="ajax_group_publicationslist", defaults={"_project"="rpe", "page"="1"})
     */
    public function groupPublicationsListAction(Request $request, $page, $groupid)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();
        $group = $this->getRepository('group')->find($groupid);

        // Page
        $byPage  = 10;

        $publications = $this->getRepository('post')->getGroupPublications($group, true);
        $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($publications, true, false);

        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/group/ajax-group_publicationslist.html.twig', array(
            'publications'  => $pager,
            'group'    => $group
        ));
    }

    /**
     * XHR Method to display headlines from a group.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $page        Page number
     * @param  string  $groupid     Group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-group-headlines/{groupid}/{page}/", name="ajax_group_headlineslist", defaults={"_project"="rpe", "page"="1"})
     */
    public function groupHeadlinesListAction(Request $request, $page, $groupid)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();
        $group = $this->getRepository('group')->find($groupid);

        // Page
        $byPage  = 10;

        $publications = $this->getRepository('post')->getGroupHeadlines($group, true);
        $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($publications, true, false);

        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/group/ajax-group_publicationslist.html.twig', array(
            'publications'  => $pager,
            'group'    => $group
        ));
    }

    /**
     * Display group members.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group_memberlist/{id}", name="ajax_group_memberlist", defaults={"_project"="rpe"})
     */
    public function groupMemberListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($id);
        $user_in_group = $this->getRepository('user_in_group')
            ->createQueryBuilder('uig')
            ->select('uig')
            ->andWhere('uig.group = :idGroup')
            ->andWhere('uig.status <= :uig_accepted')
            ->leftJoin('uig.user', 'u')
            ->andWhere('u.status = :status')
            ->setParameters(array('idGroup' => $id, 'status' => 'ACTIVE', 'uig_accepted' => UserInGroup::IN_GROUP))
            ->getQuery()
            ->getResult();

        return $this->render('pum://page/group/ajax-group_memberlist.html.twig', array(
            'group' => $group,
            'groupMembers' => $user_in_group
        ));
    }

    /**
     * Display the admin list of members in a group
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group_adminlist/{id}", name="ajax_group_adminlist", defaults={"_project"="rpe"})
     */
    public function groupAdminListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $group = $this->getRepository('group')->find($id);
        $user_in_group = $this->getRepository('user_in_group')
            ->createQueryBuilder('uig')
            ->select('uig')
            ->andWhere('uig.group = :idGroup')
            ->andWhere('uig.status <= :status_admin')
            ->leftJoin('uig.user', 'u')
            ->andWhere('u.status = :status')
            ->setParameters(array('idGroup' => $id, 'status' => 'ACTIVE', 'status_admin' => UserInGroup::STATUS_ADMIN))
            ->getQuery()
            ->getResult();

        return $this->render('pum://page/group/ajax-group_memberlist.html.twig', array(
            'group' => $group,
            'groupMembers' => $user_in_group
        ));
    }

    /**
     * Display the list of publications published into a group.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group_resourcelist/{id}", name="ajax_group_resourcelist", defaults={"_project"="rpe"})
     */
    public function groupResourceListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($id);

        return $this->render('pum://page/group/ajax-group_resourcelist.html.twig', array(
            'group' => $group
        ));
    }

    /**
     * Display the list of sub-groups from a parent group.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group_subgrouplist/{id}", name="ajax_group_subgrouplist", defaults={"_project"="rpe"})
     */
    public function groupSubGroupListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($id);
        $subGroups = $this->getRepository('group')->findByParent($id);

        return $this->render('pum://page/group/ajax-group_subgrouplist.html.twig', array(
            'group' => $group,
            'subGroups' => $subGroups
        ));
    }

    /**
     * Display the list of collaborative publications (Etherpad) available into the group.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group_resourcepadlist/{id}", name="ajax_group_resourcepadlist", defaults={"_project"="rpe"})
     */
    public function groupResourcePadListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user  = $this->getUser();
        $group = $this->getRepository('group')->find($id);

        return $this->render('pum://page/group/ajax-group_resourcepadlist.html.twig', array(
            'group'     => $group,
            'resources' => $this->getRepository('post')->getGroupPadResources($group, $user, false)
        ));
    }

    /**
     * Display and handle the form for creating Group or Sub-Group.
     *
     * @access public
     * @param  Request       $request             A request instance
     * @param  string|null   $parent_group_id     Parent group id
     *
     * @throws AccessDeniedException if the user is not in the parent group or not invited
     *
     * @return Response A Response instance
     *
     * @Route(path="/create-group/{parent_group_id}", name="create_group", defaults={"_project"="rpe"})
     */
    public function groupCreateAction(Request $request, $parent_group_id = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();
        $parent = null;

        if (null !== $parent_group_id
            && null !== $parent = $this->getRepository('group')->find($parent_group_id)) {

            if ($user->isInvited()) {
                $this->throwAccessDenied('error.group.create_subgroup_denied');
            }
            if (null === ($subgroupLevel = $parent->getSubgroupLevel())) {
                $subgroupLevel = 2;
            }

            if ((null === $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $parent))
                || ($user_in_group->getStatus() > $subgroupLevel)) {
                $parent = null;
                $parent_group_id = null;

                $this->throwAccessDenied('error.group.create_subgroup_denied');
            }
        }

        $group = $this->createObject('group');
        $form  = $this->createNamedForm('group', 'pum_object', $group, array(
            'attr'        => array('class' => 'create-form'),
            'form_view'   => $this->createFormViewByName('group', 'create', $update = false),
            'with_submit' => false
        ));
        $form->get('accesstype')->setData(Group::ACCESS_PUBLIC);

        if ($response = $this->get('pum.form_ajax')->handleForm($form, $request)) {
            return $response;
        }

        if (!$user->isInvited() && $request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            // Avatar & Cover
            $avatar = $form->get('picture')->getData();

            $colors = $this->get('tool.avatar')->getPaletteColorFromText($form->get('name')->getData(), false);
            if (!($avatar instanceof Media)) {
                $group->setPicture($this->get('tool.avatar')->getMaskedImage('users', $colors));
            }
            $group->setCover($this->get('tool.avatar')->getMaskedImage('users', $colors, 837, 400, false));

            $user_in_group = $this->createObject('user_in_group')
                ->setUser($user)
                ->setStatus(UserInGroup::STATUS_OWNER)
                ->setGroup($group)
                ->setDate(new \DateTime())
            ;
            $group
                ->setCreateDate(new \DateTime())
                ->setUpdateDate(new \DateTime())
                ->addUser($user_in_group)
            ;

            if (null !== $parent_group_id) {
                $group->setParent($parent);
            }

            $user->addGroup($user_in_group);

            $this->persist($user_in_group, $group, $user)->flush();

            $this->get('rpe.logs')->create($user, Log::TYPE_BECAME_ADMIN, $user, $group);

            return $this->redirect($this->generateUrl('group_invit_members', array('id' => $group->getId())));
        }

        return $this->render('pum://page/group/group_edit.html.twig', array(
            'form' => $form->createView(),
            'mode' => 'create',
            'parent' => $parent
        ));
    }

    /**
     * Handle Group or Sub-Group edit form displaying and submission.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Group id
     *
     * @throws AccessDeniedException if user has not the right permissions to edit group
     *
     * @return Response A Response instance
     *
     * @Route(path="/group/{id}/edit", name="edit_group", defaults={"_project"="rpe"})
     */
    public function groupEditAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null !== $group = $this->getRepository('group')->find($id)) {
            if (null !== $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($this->getUser(), $group)) {
                if ($user_in_group->isManager()) {
                    $form  = $this->createNamedForm('group', 'pum_object', $group, array(
                        'attr'        => array('class' => 'create-form'),
                        'form_view'   => $this->createFormViewByName('group', 'edit', $update = false),
                        'with_submit' => false
                    ));

                    if ($response = $this->get('pum.form_ajax')->handleForm($form, $request)) {
                        return $response;
                    }

                    if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                        $group->setUpdateDate(new \DateTime());

                        if (true === $group->isPublic()) {
                            foreach ($group->getRequesters() as $requester) {
                                $requester->setStatus(UserInGroup::STATUS_USER);
                                $this->persist($requester);
                            }
                        }

                        $this->persist($group)->flush();

                        return $this->redirect($this->generateUrl('group', array('id' => $id)));
                    }

                    return $this->render('pum://page/group/group_edit.html.twig', array(
                        'form'  => $form->createView(),
                        'group' => $group,
                        'mode'  => 'edit',
                        'parent' => $group->getParent()
                    ));
                }
            }

            $this->throwAccessDenied('error.group.manage_access_denied');
        }

        $this->throwNotFound('error.group.not_found');
    }

    /**
     * Display a form and handle its submission to invite people to join a group.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group/{id}/invit-members", name="group_invit_members", defaults={"_project"="rpe"})
     */
    public function groupInvitMembersAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();

        if (null !== $group = $this->getRepository('group')->find($id)) {
            if (null !== $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group)) {
                if ($user_in_group->getStatus() == UserInGroup::STATUS_OWNER) {
                    $form = $this->createNamedForm('user_in_group', 'collection', null, array(
                        'mapped'    => false,
                        'type'      => 'number',
                        'allow_add' => true,
                        'options'   => array(
                            'required'  => false
                    )));

                    if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                        $ids = $form->getData();
                        foreach ($ids as $id) {
                            if (null !== $member = $this->getRepository('user')->find($id)) {
                                if (null === $this->getRepository('user_in_group')->getUserInGroup($member, $group)) {
                                    $user_in_group = $this->createObject('user_in_group')
                                        ->setUser($member)
                                        ->setStatus(UserInGroup::STATUS_INVITED)
                                        ->setGroup($group)
                                        ->setDate(new \DateTime())
                                    ;
                                    $group->addUser($user_in_group);
                                    $member->addGroup($user_in_group);
                                    $this->persist($user_in_group, $member);
                                    $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_INVITE, $user, $group, $id);
                                }
                            }
                        }

                        $this->persist($group);
                        $this->flush();

                        return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
                    }

                    return $this->render('pum://page/group/group_invit_members.html.twig', array(
                        'group' => $group,
                        'form'  => $form->createView()
                    ));
                }
            }

            $this->throwAccessDenied('error.group.invit_members_denied');
        }

        $this->throwNotFound('error.group.not_found');
    }

    /**
     * Display the list of invited members to join the group.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Group id
     * @param  string  $page        Page number
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-invit-list-members/{id}/{page}", name="group_invit_list_members", defaults={"_project"="rpe", "id"=null, "page"=1})
     */
    public function groupInvitListMembersAction(Request $request, $id, $page)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $bypage = 10;
        if (null !== $id && null !== $group = $this->getRepository('group')->find($id)) {
            $users_in_groups = $this->getRepository('user_in_group')->getUserListInGroup($group);
            $friends         = $this->getRepository('user')->getFriendsNotInList($this->getUser(), $users_in_groups, true);

            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($friends, true, false);
            $pager = new Pagerfanta($pager);
            $pager = $pager->setMaxPerPage($bypage);
            $pager = $pager->setCurrentPage($page);

            return $this->render('pum://page/group/friends-invite.html.twig', array(
                'group' => $group,
                'pager' => $pager
            ));
        }
        return new Response('ERROR');
    }

    /**
     * Handle admin actions.
     * Handle actions from administration of group, based on `ACTION_*` constants
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $action      Action to perform (based on `ACTION_*` constants)
     * @param  string  $group_id    Group id
     * @param  string  $member_id   The user id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-action/manage/{action}/{group_id}/{member_id}", name="group_action_manage_request", defaults={"_project"="rpe"})
     */
    public function requestManageGroupAction(Request $request, $action, $group_id, $member_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // $group_id  = $request->query->get('group_id', null);
        // $member_id = $request->query->get('member_id', null);
        // $action    = $request->query->get('action', null);
        $redirect_url = $request->get('redirect_url', null);
        $user      = $this->getUser();

        $actions = array(
            self::ACTION_ACCEPT,
            self::ACTION_REJECT,
            self::ACTION_SET_ADMIN,
            self::ACTION_REMOVE_ADMIN,
            self::ACTION_SET_MODERATOR,
            self::ACTION_REMOVE_MODERATOR,
            self::ACTION_SET_USER,
            self::ACTION_BAN_USER,
            self::ACTION_UNBAN_USER
        );

        if (!in_array($action, $actions)) {
            throw new \RuntimeException('Unauthorised action');
        }

        if (null !== $group_id && null !== $group = $this->getRepository('group')->find($group_id)) {
            if (null !== $me_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group)) {
                if (in_array($me_in_group->getStatus(), array(UserInGroup::STATUS_OWNER, UserInGroup::STATUS_ADMIN))) {
                    if (null !== $member = $this->getRepository('user')->find($member_id)) {
                        if (null !== $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($member, $group)) {
                            switch ($action) {
                                case self::ACTION_REMOVE_ADMIN:
                                    $user_in_group->setStatus(UserInGroup::STATUS_USER);

                                    $this->persist($user_in_group);
                                    $action = self::ACTION_SET_ADMIN;
                                    break;

                                case self::ACTION_SET_USER:
                                    $user_in_group->setStatus(UserInGroup::STATUS_USER);

                                    $this->persist($user_in_group);
                                    break;

                                case self::ACTION_ACCEPT:
                                    $user_in_group->setStatus(UserInGroup::STATUS_USER);

                                    // if user accept a tmp_intervenant group, change its type to "COMMON"
                                    $group_tmp_intervenant = explode(',', $this->get('pum.vars')->getValue('group_tmp_intervenant'));
                                    if ($group_tmp_intervenant && in_array($group->getId(), $group_tmp_intervenant) && $user->isInvited()) {
                                        $user->setType(User::TYPE_COMMON);
                                        $this->setUserMeta($user, User::META_TMP_INTERVENANT, 1);
                                        $this->persist($user);
                                    }
                                    $this->persist($user_in_group);

                                    $this->get('rpe.logs')->create($member, Log::TYPE_JOIN_GROUP_REQUEST, $user, $group);
                                    $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_GROUP_ACCEPT, $user, $group, $member_id);
                                    $this->get('rpe.search.index.factory')->update($group);

                                    $user_in_child_groups = $this->getRepository('user_in_group')->getUserListInChildGroups($member, $group, UserInGroup::STATUS_WAITING_PARENT_GROUP);

                                    foreach ($user_in_child_groups as $user_in_child_group) {
                                        $child_group = $user_in_child_group->getGroup();

                                        $status = UserInGroup::STATUS_REQUEST;

                                        if ($child_group->isPublic()) {
                                            $status = UserInGroup::STATUS_USER;
                                        }

                                        $user_in_child_group->setStatus($status);
                                        $this->persist($user_in_child_group);

                                        $this->get('rpe.search.index.factory')->update($child_group);

                                        if ($status == UserInGroup::STATUS_REQUEST) {
                                            if ($child_owner = $child_group->getOwner()) {
                                                $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_REQUEST, $member, $child_group, $child_owner->getId());
                                            }
                                        }
                                    }
                                    break;

                                case self::ACTION_REJECT:
                                    $group->removeUser($user_in_group);
                                    $member->removeGroup($user_in_group);
                                    $this->remove($user_in_group);

                                    $this->persist($group, $member);
                                    break;

                                case self::ACTION_SET_ADMIN:
                                    $user_in_group->setStatus(UserInGroup::STATUS_ADMIN);

                                    $this->persist($user_in_group);
                                    $action = self::ACTION_REMOVE_ADMIN;
                                    $this->get('rpe.notifications')->wait(Notification::TYPE_BECAME_ADMIN, $user, $group, $member_id);
                                    break;

                                case self::ACTION_SET_MODERATOR:
                                    $user_in_group->setStatus(UserInGroup::STATUS_MODERATOR);

                                    $this->persist($user_in_group);
                                    $action = self::ACTION_REMOVE_MODERATOR;
                                    break;

                                case self::ACTION_BAN_USER:
                                    $user_in_group->setStatus(UserInGroup::STATUS_BANNED);
                                    $this->remove($user_in_group);
//                                     $this->persist($user_in_group);
                                    $action = self::ACTION_UNBAN_USER;

                                    $user_in_child_groups = $this->getRepository('user_in_group')->getUserListInChildGroups($member, $group);

                                    foreach ($user_in_child_groups as $user_in_child_group) {
                                        $user_in_child_group->setStatus(UserInGroup::STATUS_BANNED);
//                                         $this->persist($user_in_child_group);
                                        $this->remove($user_in_child_group);
                                    }
                                    break;

                                case self::ACTION_UNBAN_USER:
                                    $user_in_group->setStatus(UserInGroup::STATUS_USER);

                                    $this->persist($user_in_group);
                                    $action = self::ACTION_BAN_USER;

                                    $user_in_child_groups = $this->getRepository('user_in_group')->getUserListInChildGroups($member, $group);

                                    foreach ($user_in_child_groups as $user_in_child_group) {
                                        $user_in_child_group->setStatus(UserInGroup::STATUS_REQUEST);
                                        $this->persist($user_in_child_group);
                                    }
                                    break;
                            }

                            $this->flush();

                            if (null !== $redirect_url) {
                                return $this->redirect($redirect_url);
                            }

                            if ($request->isXmlHttpRequest()) {
                                return $this->render('pum://page/ajax/action/useringroup-request.html.twig', array(
                                    'group_id'      => $group_id,
                                    'member_id'     => $member_id,
                                    'action'        => $action
                                ));
                            } else {
                                return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
                            }
                        }
                    }
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * Handle group action for regular members.
     * Handle actions like join group, accept invitation or cancel request/invitation for regular members
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Group id
     * @param  string  $mode        Type of request: `join`, `accept` or `cancel`
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-action/{mode}/{id}", name="group_action_request", defaults={"_project"="rpe", "id": null})
     */
    public function requestGroupAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $group = $this->getRepository('group')->find($id);
        $return = false;


        if ($mode == 'join') {
            if (null !== $id && null !== $group && $group->isSecret() === false) {
                $isInParentGroup = null;
                $parentGroupIsPublic = null;

                if (null !== $parentGroup = $group->getParent()) {
                    $parentGroupIsPublic = $parentGroup->isPublic();
                    $isInParentGroup = $this->getRepository('user_in_group')->getUserInGroup($user, $parentGroup);
                    if (null === $isInParentGroup || $isInParentGroup->getStatus() > UserInGroup::STATUS_USER) {
                        /***   CHANGE: to join a sub group, it has to join the parent group before

                        $parent_status = UserInGroup::STATUS_REQUEST;

                        if (true === $parentGroupIsPublic = $parentGroup->isPublic()) {
                            $parent_status = UserInGroup::STATUS_USER;
                        }

                        $user_in_parent_group = $this->createObject('user_in_group')
                            ->setUser($user)
                            ->setStatus($parent_status)
                            ->setGroup($parentGroup)
                            ->setDate(new \DateTime())
                        ;
                        $parentGroup->addUser($user_in_parent_group);
                        $user->addGroup($user_in_parent_group);

                        $this->persist($user_in_parent_group, $parentGroup, $user)->flush();

                        if ($parent_status == UserInGroup::STATUS_REQUEST) {
                            if ($parent_owner = $parentGroup->getOwner()) {
                                $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_REQUEST, $user, $parentGroup, $parent_owner->getId());
                            }
                        } else {
                            $this->get('rpe.logs')->create($user, Log::TYPE_JOIN_GROUP_DIRECT, $user, $parentGroup);
                        }
                        */

                    }
                }

                $status = UserInGroup::STATUS_REQUEST;

                if ((null === $parentGroup || null !== $isInParentGroup || true === $parentGroupIsPublic) && true === $group->isPublic()) {
                    $status = UserInGroup::STATUS_USER;
                } elseif (null !== $parentGroup && false === $group->isPublic()) {
                    $status = UserInGroup::STATUS_REQUEST;
                }

                if (null === $this->getRepository('user_in_group')->getUserInGroup($user, $group)) {
                    $user_in_group = $this->createObject('user_in_group')
                        ->setUser($user)
                        ->setStatus($status)
                        ->setGroup($group)
                        ->setDate(new \DateTime())
                    ;
                    $group->addUser($user_in_group);
                    $user->addGroup($user_in_group);

                    $this->persist($user_in_group, $group, $user)->flush();

                    if ($status == UserInGroup::STATUS_REQUEST) {
                        if ($owner = $group->getOwner()) {
                            $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_REQUEST, $user, $group, $owner->getId());
                        }
                    } else {
                        $this->get('rpe.logs')->create($user, Log::TYPE_JOIN_GROUP_DIRECT, $user, $group);
                    }

                    $return = true;
                }
            }
        } elseif ($mode == 'accept') {
            if (null !== $id && null !== $group) {
                if (null !== $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group)) {
                    if (!$user->isInGroup($group)) {
                        // if user accept a tmp_intervenant group, change its type to "COMMON"
                        $group_tmp_intervenant = explode(',', $this->get('pum.vars')->getValue('group_tmp_intervenant'));
                        if ($group_tmp_intervenant && in_array($group->getId(), $group_tmp_intervenant) && $user->isInvited()) {
                            $user->setType(User::TYPE_COMMON);
                            $this->setUserMeta($user, User::META_TMP_INTERVENANT, 1);
                            $this->persist($user);
                        }

                        $user_in_group->setStatus(UserInGroup::STATUS_USER);
                        $this->persist($user_in_group)->flush();

                        if ($owner = $group->getOwner()) {
                            $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_USER_ACCEPT, $user, $group, $owner->getId());
                        }
                        $this->get('rpe.logs')->create($user, Log::TYPE_JOIN_GROUP_INVITATION, $user, $group);
                        $this->get('rpe.search.index.factory')->update($group);

                        $return = true;
                    }
                }
            }
        } elseif ($mode == 'cancel') {
            if (null !== $id && null !== $group) {
                if (null !== $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group)) {
                    $user_in_child_groups = $this->getRepository('user_in_group')->getUserListInChildGroups($user, $group);

                    foreach ($user_in_child_groups as $user_in_child_group) {
                        $child_group = $user_in_child_group->getGroup();

                        $child_group->removeUser($user_in_child_group);
                        $user->removeGroup($user_in_child_group);
                        $this->remove($user_in_child_group);

                        $this->persist($child_group, $user)->flush();

                        $this->get('rpe.search.index.factory')->update($child_group);
                    }

                    if (null !== $joinRequestNotification = $this->getRepository('notification')->getLastNotificationByParams($group->getOwner(), 'group', $group->getId(), Notification::TYPE_JOIN_REQUEST)) {
                        $this->remove($joinRequestNotification);
                    }

                    $group->removeUser($user_in_group);
                    $user->removeGroup($user_in_group);
                    $this->remove($user_in_group);

                    $this->persist($group, $user)->flush();

                    $this->get('rpe.search.index.factory')->update($group);

                    $user_in_group = null;

                    $return = true;
                }
            }
        }

        if ($return) {
            if ($request->isXmlHttpRequest()) {

                return $this->render('pum://page/ajax/action/group-request.html.twig', array(
                    'group' => $group,
                    'id' => $id,
                    'userInGroup' => $user_in_group,
                    'style' => $request->query->get('style')
                ));
            } else {
                return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
            }
        }

//         return new Response('ERROR');
        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * Display the administration page of a group.
     *
     * @access public
     * @param  string  $id          Group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-administration/{id}", name="group_administration", defaults={"_project"="rpe"})
     */
    public function groupAdministrationAction($id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();
        $group = $this->getRepository('group')->find($id);

        if ($user != $group->getOwner()) {
            return $this->redirect($this->generateUrl('home'));
        }

        return $this->render('pum://page/group/group_administration.html.twig', array(
            'group' => $group
        ));
    }

    /**
     * XHR Method to display members of a group.
     *
     * @access public
     * @param  string  $group_id          Group id
     * @param  string  $status            Status of users to list
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-group-userlist/{group_id}/{status}", name="group_userlist", defaults={"_project"="rpe"})
     */
    public function groupAjaxUserList($group_id, $status = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($group_id);
        $user = $this->getUser();

        if (!$user->isGroupAdmin($group)) {
            return new Response('ERROR');
        }

        $users = $this->getRepository('user_in_group')->getUserListByStatus($group, $status);

        return $this->render('pum://includes/common/group/members/userlist.html.twig', array(
            'group'    => $group,
            'users'    => $users
        ));
    }


    /**
     * XHR Method to get informations about an user into a group.
     *
     * @access public
     * @param  string  $group_id          Group id
     * @param  string  $user_id           User id
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-group-user/{group_id}/{user_id}", name="group_user_getinfo", defaults={"_project"="rpe"})
     */
    public function groupAjaxUserInfos($group_id, $user_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($group_id);
        $user = $this->getUser();

        if (!$user->isGroupAdmin($group)) {
            return new Response('ERROR');
        }

        $user_infos = $this->getRepository('user_in_group')->getUserInGroup($user_id, $group_id);
        if ($user_infos === null) {
            return new Response('');
        }
        $relation_detail = $this->getRepository('friend')->getRelation($user_infos->getUser(), $user);
        return $this->render('pum://page/group/ajax-user-getinfo.html.twig', array(
            'group'             => $group,
            'useringroup'       => $user_infos,
            'relation_detail'   => $relation_detail,
            'user_id'           => $user_id
        ));
    }


    /**
     * XHR Method to display an optionally filtered list of groups.
     * Display a list of groups, with filters based on `LISTMODE_*` constants
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $page        Page number
     *
     * @return Response A Response instance
     *
     * @Route(path="/grouplist/{page}", name="ajax_grouplist", defaults={"_project"="rpe", "page"="1"})
     */
    public function groupListAction(Request $request, $page)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // Page
        $byPage  = 9;

        // Mode
        $mode = $request->query->get('mode', self::LISTMODE_ALL);

        // Get Groups
        if (self::LISTMODE_MYGROUPS == $mode) {
            $groups = $this->getUser()->getMyGroups();
            $pager = new \Pagerfanta\Adapter\DoctrineCollectionAdapter($groups);
        } elseif (self::LISTMODE_OWNGROUPS == $mode) {
            $groups = $this->getUser()->getMyOwnGroups();
            $pager = new \Pagerfanta\Adapter\DoctrineCollectionAdapter($groups);
        } elseif (self::LISTMODE_AWAITINGS == $mode) {
            $groups = $this->getUser()->getAwaitingGroups();
            $pager = new \Pagerfanta\Adapter\DoctrineCollectionAdapter($groups);
        } elseif (self::LISTMODE_INVITED == $mode) {
            $groups = $this->getUser()->getInvitedGroups();
            $pager = new \Pagerfanta\Adapter\DoctrineCollectionAdapter($groups);
        } elseif (self::LISTMODE_SUGGESTEDGROUPS == $mode) {
            $groups = $this->getRepository('group')->getSuggestedGroups($this->getUser(), true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($groups, true, false);
        } elseif (self::LISTMODE_PUBLIC == $mode) {
            $groups = $this->getRepository('group')->getGroupByAccess($this->getUser(), Group::ACCESS_PUBLIC, true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($groups, true, false);
        } elseif (self::LISTMODE_PRIVATE == $mode) {
            $groups = $this->getRepository('group')->getGroupByAccess($this->getUser(), Group::ACCESS_ON_DEMAND, true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($groups, true, false);
        } elseif (self::LISTMODE_FAVORITE == $mode) {
            $groups = $this->getRepository('group')->getFavoriteGroups($this->getUser(), true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($groups, true, false);
        } else {
            $groups = $this->getRepository('group')->getAllGroups(true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($groups, true, false);
        }

        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/group/ajax-grouplist.html.twig', array(
            'mode'  => $mode,
            'pager'  => $pager
        ));
    }


    /**
     * Display the page with an optionally filtered list of groups.
     * The list of groups filters are based on `LISTMODE_*` constants
     *
     * @access public
     * @param  string  $mode        Group list mode
     *
     * @return Response A Response instance
     *
     * @Route(path="/groups/{mode}", name="groups", defaults={"_project"="rpe", "mode"= null})
     */
    public function groupsAction($mode)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        if ($user->isInvited()) {
            $groupFilters = array(
                self::LISTMODE_MYGROUPS,
                self::LISTMODE_INVITED,
                self::LISTMODE_FAVORITE
            );
            $mode_default = self::LISTMODE_MYGROUPS;
        } else {
            $groupFilters = array(
                self::LISTMODE_ALL,
                self::LISTMODE_MYGROUPS,
                self::LISTMODE_OWNGROUPS,
                self::LISTMODE_AWAITINGS,
                self::LISTMODE_INVITED,
                self::LISTMODE_SUGGESTEDGROUPS,
                self::LISTMODE_PUBLIC,
                self::LISTMODE_PRIVATE,
                self::LISTMODE_FAVORITE
            );
            $mode_default = self::LISTMODE_ALL;
        }

        if (null == $mode) {
            $mode = $mode_default;
        } elseif (!in_array($mode, $groupFilters)) {
            $this->throwNotFound('error.filters.not_found');
        }

        return $this->render('pum://page/group/groups.html.twig', array(
            'groupFilters'   =>  $groupFilters,
            'mode'           =>  $mode
        ));
    }


    /**
     * To save a meta for a group
     *
     * @access private
     * @param Group     $group    Group object
     * @param string    $key      The meta key
     * @param string    $value    The meta value
     * @param string    $type     The meta type
     *
     * @return void
     */
    private function registerGroupMeta(Group $group, $key, $value, $type = 'modules')
    {
        $group_meta = $group->getMeta($key);

        if (null === $group_meta) {
            $group_meta = $this->createObject('group_meta')
                ->setGroup($group)
                ->setType($type)
                ->setMetaKey($key)
            ;

            $group->addgroupMeta($group_meta);
        }

        $group_meta->setValue($value);

        $this->persist($group_meta);
    }

    /**
     * Set default postition of group
     *
     * @access private
     * @param Group     $group    Group object
     * @param array     $modules  List of modules
     *
     * @uses GroupController:setModulePosition() to update position's meta of the group
     *
     * @return void
     */
    private function setDefaultPosition($group, array $modules)
    {
        $nbModules = count($modules);
        foreach ($modules as $module) {
            if (false === $group->getModuleSlotIn($module)) {
                // set modules position in $modules order
                for ($slot=1; $slot <= $nbModules; $slot++) {
                    if (!$group->getModuleInSlot($slot)) {
                        $this->setModulePosition($group, $module, $slot);
                        break;
                    }
                }

                if (in_array($module, array('survey', 'resource-etherpad'))) {
                    $this->enableModule($group, $module, false);
                } else {
                    $this->enableModule($group, $module, true);
                }
            }
        }
    }


    /**
     * Set default postition of group
     *
     * @access private
     * @param Group     $group    Group object
     * @param string    $module   Name of the module
     * @param string    $position Position of the module
     *
     * @return void
     */
    private function setModulePosition(Group $group, $module, $position)
    {
        if ($oldPosition = $group->getModuleSlotIn($module)) {
            $this->registerGroupMeta($group, Group::META_GROUP_MODULE_IN_SLOT.'.'.$oldPosition, '');
        }

        $this->registerGroupMeta($group, Group::META_GROUP_MODULE_IN_SLOT.'.'.$position, $module);
        $this->registerGroupMeta($group, Group::META_GROUP_MODULE_SLOT_IN.'.'.$module, $position);
    }


    /**
     * Enable a module of group
     *
     * @access private
     * @param Group     $group    Group object
     * @param string    $module   Name of the module
     * @param boolean   $enable   Enable of a module, default to true
     *
     * @return void
     */
    private function enableModule(Group $group, $module, $enable = true)
    {
        $this->registerGroupMeta($group, Group::META_GROUP_MODULE_ENABLED.'.'.$module, $enable);
    }


    /**
     * Perform an action for a group.
     * Enable or Disable module.
     *
     * @access private
     * @param string    $group_id    Group id
     * @param string    $module      Name of the module
     * @param string    $action      Action to perform: `enable` or `disable`
     *
     * @return Response A Response instance
     *
     * @Route(path="/group_module_action/{group_id}/{module}/{action}", name="group_module_action", defaults={"_project"="rpe"})
     */
    public function groupModuleAction($group_id, $module, $action)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($group_id);

        if (null === $group) {
            return new Response('ERROR');
        }

        switch ($action) {
            case 'enable':
                $this->enableModule($group, $module, true);
                break;

            case 'disable':
                $this->enableModule($group, $module, false);
                break;
        }

        $this->flush();

        return $this->render('pum://includes/common/componants/groups/admin_modules_button-activation.html.twig', array(
            'group'         =>  $group,
            'groupModule'   =>  $module
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param string    $group_id    Group id
     * @param string    $module      Name of the module
     *
     * @return Response A Response instance
     *
     * @Route(path="/group_module_position/{group_id}/{module}", name="group_module_position", defaults={"_project"="rpe"})
     */
    public function groupModulePositionAction(Request $request, $group_id, $module)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($group_id);

        if (null === $group) {
            return new Response('ERROR');
        }

        $position = $request->request->get('position');

        if (!is_numeric($position)) {
            return new Response('ERROR');
        }

        $this->setModulePosition($group, $module, $position);

        $this->flush();

        return $this->render('pum://includes/common/componants/groups/admin_modules_select-position.html.twig', array(
            'group'         =>  $group,
            'groupModule'   =>  $module
        ));
    }

    /**
     * Ajax to calculate statistics for a group
     *
     * @access private
     * @param  Request $request     A request instance
     * @param  Group  $group     Group object
     *
     * @return Response A Response instance
     *
     * @Route(path="/group/statistic/{id}", name="ajax_group_statistic", defaults={"_project"="rpe"})
     */
    public function groupStats(Request $request, $id)
    {
        $group = $this->getRepository('group')->find($id);
        if (null === $group) {
            return new Response('Group id invalide');
        }

        $dought_colors = array("#d56c31", '#1995aa', '#37475f', '#000000');
        $gender = $academy = $techingLevels = $disciplines = array();
        $totalUser = 1;

        $parameters = array(
            'group'     => $group,
            'status'    => UserInGroup::IN_GROUP,
            'uStatus'   => 'ACTIVE'
        );

        /* BEGIN Gender Info  */
        $queryGender = $this->getRepository('user_in_group')
            ->createQueryBuilder('uig')
            ->leftJoin('uig.user', 'u')
            ->select(array('u.sex', 'COUNT(u.id) AS subTotal'))
            ->andWhere('uig.group = :group')
            ->andWhere('uig.status <= :status')
			->andWhere('u.status = :uStatus')
            ->groupBy('u.sex')
            ->orderBy('subTotal', 'DESC')
            ->setParameters($parameters)
            ->getQuery()->getResult();

        $gender = array(
            'Monsieur'  => 0,
            'Madame'    => 0,
            'total'     => 0
        );
        if (count($queryGender)) {
            foreach ($queryGender as $item) {
                if (in_array($item['sex'], array('Monsieur', 'Madame'))) {
                    $gender[$item['sex']] = $item['subTotal'];
                }
            }
            $gender['total'] = $gender['Monsieur'] + $gender['Madame'];
            $totalUser = $gender['total'];
        }
        /* END Gender Info END */

        /* BEGIN Academy Info  */
        $queryBuilder = $this->getRepository('user_in_group')
            ->createQueryBuilder('uig')
            ->leftJoin('uig.user', 'u', 'WITH')
            ->leftJoin('u.academy', 'ac')
            ->andWhere('ac IS NOT NULL')
            ->andWhere('uig.group = :group')
            ->andWhere('uig.status <= :status')
            ->andWhere('u.status = :uStatus')

            ->select(array('ac.id', 'ac.name', 'COUNT(uig.id) AS subTotal'))
            ->groupBy('ac.name')
            ->orderBy('subTotal', 'DESC')
            ->setMaxResults(3)
            ->setParameters($parameters)
            ->getQuery();
        $result = $queryBuilder->getResult();

        if (count($result)) {
            $temp_total = 0;
            $data_chart_dought_academy = array();
            foreach ($result as $k => $item) {
                $academy[$item['id']] = array(
                    'name'          => $item['name'],
                    'subTotal'      => $item['subTotal'],
                    'percentage'    => number_format($item['subTotal'] * 100 / $totalUser, 2)
                );
                $data_chart_dought_academy[] = array(
                    'value' => $academy[$item['id']]['percentage'],
                    'color' => $dought_colors[$k]
                );
                $temp_total += $academy[$item['id']]['percentage'];
            }
            $academy[3] = array(
                'name'          => 'Autre',
                'percentage'    => 100 - $temp_total
            );
            $data_chart_dought_academy[] = array(
                'value' => $academy[3]['percentage'],
                'color' => $dought_colors[3]
            );
        } else {
            $academy = null;
        }
        /* END Academy Info  */

        // inQuery for restrictiong user_in_group
        $inQuery = $this->getRepository('user_in_group')
            ->createQueryBuilder('uig')
            ->leftJoin('uig.user', 'u')
            ->select('u.id')
            ->andWhere('u.status = :uStatus')
            ->andWhere('uig.group = :group')
            ->andWhere('uig.status <= :status');

        /* BEGIN Discipline Info  */
        $queryBuilder = $this->getRepository('instructed_discipline')
            ->createQueryBuilder('dis');
        $finalQuery = $queryBuilder
            ->innerJoin('dis.users', 'user')
            ->select('dis.id', 'dis.name', 'COUNT(user.id) as subTotal')

            ->where($queryBuilder->expr()->in('user.id', $inQuery->getDQL()))
            ->groupBy('dis.id')
            ->orderBy('subTotal', 'DESC')
            ->setMaxResults(3)
            ->setParameters($parameters)
            ->getQuery();
        $result = $finalQuery->getResult();
        if (count($result)) {
            $temp_total = 0;
            foreach ($result as $k => $item) {
                $disciplines[$item['id']] = array(
                    'name'          => $item['name'],
                    'subTotal'      => $item['subTotal'],
                    'percentage'    => number_format($item['subTotal'] * 100 / $totalUser, 2)
                );
                $disciplines_chart_data[] = array(
                    'value' => $disciplines[$item['id']]['percentage'],
                    'color' => $dought_colors[$k]
                );
                $temp_total += $disciplines[$item['id']]['percentage'];
            }
            $disciplines[3] = array(
                'name'          => 'Autre',
                'percentage'    => 100 - $temp_total
            );
            $disciplines_chart_data[] = array(
                'value' => $disciplines[3]['percentage'],
                'color' => $dought_colors[3]
            );
        } else {
            $disciplines = null;
        }
        /* END Discipline Info  */

        /* BEGIN Teaching level Info  */
        $queryBuilder = $this->getRepository('teaching_level')
            ->createQueryBuilder('tcl');
        $finalQuery = $queryBuilder
            ->innerJoin('tcl.users', 'user')
            ->select('tcl.id', 'tcl.name', 'tcl.description', 'COUNT(user.id) as subTotal')

            ->where($queryBuilder->expr()->in('user.id', $inQuery->getDQL()))
            ->groupBy('tcl.id')
            ->orderBy('subTotal', 'DESC')
            ->setMaxResults(3)
            ->setParameters($parameters)
            ->getQuery();

        $result = $finalQuery->getResult();
        if (count($result)) {
            $teachinglevels_chart_data = array();
            $temp_total = 0;
            foreach ($result as $k => $item) {
                $techingLevels[$item['id']] = array(
                    'name'          => $item['description'],
                    'subTotal'      => $item['subTotal'],
                    'percentage'    => number_format($item['subTotal'] * 100 / $totalUser, 2)
                );
                $teachinglevels_chart_data[] = array(
                    'value' => $techingLevels[$item['id']]['percentage'],
                    'color' => $dought_colors[$k]
                );
                $temp_total += $techingLevels[$item['id']]['percentage'];
            }
            $techingLevels[3] = array(
                'name'          => 'Autre',
                'percentage'    => 100 - $temp_total
            );
            $teachinglevels_chart_data[] = array(
                'value' => $techingLevels[3]['percentage'],
                'color' => $dought_colors[3]
            );
        } else {
            $techingLevels = null;
        }
        /* END Teaching level Info  */
        $data_chart_line = $data_chart_bar_post = $data_chart_bar_comment = array();
        $datasets = array(
            "fillColor"         => "rgba(25,149,170,0.5)",
            "strokeColor"       => "#1995aa",
            "pointColor"        => "#1995aa",
            "pointStrokeColor"  => "#1995aa",
        );
        $datasets_bar_post = $datasets_bar_comment = array(
            "fillColor"         => "#1995aa",
            "strokeColor"       => "#1995aa"
        );
        for ($i = 7; $i > 0; $i--) {
            $end = new \DateTime('now');
            $count = $i - 1;
            $end->setTime(0, 0, 0);
            $end->modify("first day of -$count months");
            $month = date_format($end, 'n');

            $period_start = clone $end; // used for calculate publications and comments
            $period_end   = clone $end;
            $period_end->modify("first day of +1 months");

            if ($i == 1) {
                $end = new \DateTime('now');
                $period_end = clone $end;
            }
            $parameters['date_end'] = $end;

            // subquery for calculate members
            $subQuery = $this->getRepository('user_in_group')
                ->createQueryBuilder('uig')
                ->leftJoin('uig.user', 'u')
                ->select('COUNT(uig.id)')
                ->andWhere('u.status = :uStatus')
                ->andWhere('uig.group = :group')
                ->andWhere('uig.status <= :status')
                ->andWhere('uig.date < :date_end')
                ->setParameters($parameters)
                ->getQuery()->getSingleScalarResult();
            $data_chart_line['labels'][] = $this->get('translator')->trans('groupAdmin.stats.month.' . $month, array(), 'rpe');
            $datasets['data'][] = $subQuery;
            $data_chart_line['datasets'][] = $datasets;

            if (!isset($data_chart_line['maxValue']) || $subQuery > $data_chart_line['maxValue']) {
                $data_chart_line['maxValue'] = $subQuery;
            }
            if ($i == 1) {
                $data_chart_line['stepWidth'] = ceil(abs($data_chart_line['maxValue'])/5);
            }

            // subquery for calculate publications
            $subQuery = $this->getRepository('post')
                ->createQueryBuilder('p');
            $result = $subQuery->select('COUNT(p.id)')
                ->join('p.publishedGroup', 'g', 'WITH', $subQuery->expr()->eq('g', ':group'))
                ->andWhere($subQuery->expr()->eq('p.status', ':status'))
                ->andWhere($subQuery->expr()->between('p.createDate', ':period_start', ':period_end'))
                ->setParameters(array(
                    'group' => $group,
                    'status' => Post::STATUS_PUBLISHED,
                    'period_start'  =>  $period_start,
                    'period_end'    =>  $period_end
                ))
                ->getQuery()->getSingleScalarResult();
            $data_chart_bar_post['labels'][] = $this->get('translator')->trans('groupAdmin.stats.month.' . $month, array(), 'rpe');
            $datasets_bar_post['data'][] = $result;

            if (!isset($data_chart_bar_post['maxValue']) || $result > $data_chart_bar_post['maxValue']) {
                $data_chart_bar_post['maxValue'] = $result;
            }
            if ($i == 1) {
                $data_chart_bar_post['stepWidth'] = ceil(abs($data_chart_bar_post['maxValue'])/5);
            }

            // subquery for calculate commentaires
            $subQuery = $this->getRepository('comment')
                ->createQueryBuilder('cm');
            $result = $subQuery->select('COUNT(cm.id)')
                ->join('cm.post', 'p')
                ->join('p.publishedGroup', 'g', 'WITH', $subQuery->expr()->eq('g', ':group'))
                ->andWhere($subQuery->expr()->in('cm.status', ':cm_status'))
                ->andWhere($subQuery->expr()->between('cm.date', ':period_start', ':period_end'))
                ->setParameters(array(
                    'group' => $group,
                    'cm_status' => Comment::STATUS_OK,
                    'period_start'  =>  $period_start,
                    'period_end'    =>  $period_end
                ))
                ->getQuery()->getSingleScalarResult();
            $data_chart_bar_comment['labels'][] = $this->get('translator')->trans('groupAdmin.stats.month.' . $month, array(), 'rpe');
            $datasets_bar_comment['data'][] = $result;

            if (!isset($data_chart_bar_comment['maxValue']) || $result > $data_chart_bar_comment['maxValue']) {
                $data_chart_bar_comment['maxValue'] = $result;
            }
            if ($i == 1) {
                $data_chart_bar_comment['stepWidth'] = ceil(abs($data_chart_bar_comment['maxValue'])/5);
            }
        }

        $data_chart_bar_comment['datasets'][] = $datasets_bar_comment;
        $data_chart_bar_post['datasets'][] = $datasets_bar_post;

        // calculate publications and partages, commentaire, recommandation
        $subQuery = $this->getRepository('share_post')
            ->createQueryBuilder('shp');
        $countPartage = $subQuery
            ->select('COUNT(shp.id)')
            ->LeftJoin('shp.sourcePost', 'p', 'WITH', $subQuery->expr()->eq('p.status', ':status'))
            ->LeftJoin('p.publishedGroup', 'g', 'WITH', $subQuery->expr()->eq('g', ':group'))
            ->andWhere($subQuery->expr()->isNotNull('g'))
            ->andWhere($subQuery->expr()->isNotNull('p'))
            ->setParameters(array(
                'group' => $group,
                'status' => Post::STATUS_PUBLISHED
            ))
            ->getQuery()->getSingleScalarResult();

        $subQuery =  $this->getRepository('post')->createQueryBuilder('p');
        $countPublications = $subQuery->select('COUNT(p.id)')
            ->join('p.publishedGroup', 'g', 'WITH', $subQuery->expr()->eq('g', ':group'))
            ->andWhere($subQuery->expr()->eq('p.status', ':status'))
            ->setParameters(array(
                'group' => $group,
                'status' => Post::STATUS_PUBLISHED
            ))
            ->getQuery()->getSingleScalarResult();

        $subQuery = $this->getRepository('comment')->createQueryBuilder('cm');
        $countComments = $subQuery->select('COUNT(cm.id)')
            ->join('cm.post', 'p')
            ->join('p.publishedGroup', 'g', 'WITH', $subQuery->expr()->eq('g', ':group'))
            ->andWhere($subQuery->expr()->in('cm.status', ':cm_status'))
            ->setParameters(array(
                'group' => $group,
                'cm_status' => Comment::STATUS_OK
            ))
            ->getQuery()->getSingleScalarResult();

        $subQuery = $this->getRepository('recommend_post')->createQueryBuilder('rp');
        $countRecommend = $subQuery->select('COUNT(rp.id)')
            ->join('rp.post', 'p')
            ->join('p.publishedGroup', 'g', 'WITH', $subQuery->expr()->eq('g', ':group'))
            ->setParameters(array(
                'group' => $group
            ))
            ->getQuery()->getSingleScalarResult();

        $total_publish_share = $countPartage + $countPublications;
        if ($total_publish_share) {
            $percent_pulbications = number_format($countPublications * 100 / $total_publish_share, 2);
            $percent_partages     = number_format(100 - $percent_pulbications, 2);
        }
        $avg_comment = $avg_share = $avg_recommend = 0;
        if ($countPublications) {
            $avg_comment = number_format($countComments / $countPublications, 2);
            $avg_share   = number_format($countPartage / $countPublications, 2);
            $avg_recommend = number_format($countRecommend / $countPublications, 2);
        }

        // get the most activ three users
        $subQuery = $this->getRepository('user')->createQueryBuilder('u');
        $users = $subQuery
            ->select('u.id, (COUNT(DISTINCT p.id) + COUNT(DISTINCT cm.id)) as total')

            ->leftJoin('u.groups', 'uig', 'WITH', $subQuery->expr()->eq('uig.group', ':group'))
            ->leftJoin('u.posts', 'p', 'WITH', $subQuery->expr()->eq('p.publishedGroup', ':group'))
            ->leftJoin('u.comments', 'cm')
            ->leftJoin('cm.post', 'cm_p', 'WITH', $subQuery->expr()->eq('p.publishedGroup', ':group'))
            ->andWhere($subQuery->expr()->isNotNull('cm_p.publishedGroup'))
            ->andWhere($subQuery->expr()->isNotNull('p.publishedGroup'))
            ->andWhere($subQuery->expr()->eq('p.status', ':p_status'))
            ->andWhere('u.status = :uStatus')
            ->andWhere('uig.status = :status')
            ->groupBy('u.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults(3)
            ->setParameters(array(
                'group' => $group,
                'uStatus' => User::STATUS_TYPE_ACTIVE,
                'status'  => UserInGroup::STATUS_USER,
                'p_status' => Post::STATUS_PUBLISHED
            ))
            ->getQuery()->getResult();//->getSQL()
        ;

        $final = array(
            'gender'    => $gender,
            'academy'   => $academy,
            'teachinglevels' => $techingLevels,
            'disciplines'    => $disciplines,
            'academy_chart_data' => isset($data_chart_dought_academy) ? $data_chart_dought_academy : null,
            'teachinglevels_chart_data' => isset($teachinglevels_chart_data) ? $teachinglevels_chart_data : null,
            'disciplines_chart_data' => isset($disciplines_chart_data) ? $disciplines_chart_data : null
        );

        return $this->render("pum://includes/common/group/group_chart.html.twig", array(
            "statsInfo" => $final,
            "data_chart_line" => $data_chart_line,
            "data_chart_bar_post" => $data_chart_bar_post,
            "data_chart_bar_comment" => $data_chart_bar_comment,
            "total_publish_share"  => $total_publish_share,
            "percent_pulbications" => isset($percent_pulbications) ? $percent_pulbications : null,
            "percent_partages" => isset($percent_partages) ? $percent_partages : null,
            "avg_comment"       => $avg_comment,
            "avg_share"         => $avg_share,
            "avg_recommend"     => $avg_recommend,
            "active_users"      => $users
        ));
    }


    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of group to perform
     * @param  string  $scope       Invite scope, external or relation
     *
     * @return Response A Response instance
     *
     * @Route(path="/invite-relation/{id}/{scope}", name="invite_relation", defaults={"_project"="rpe","scope"="relation"})
     */
    public function inviteRelationsAction(Request $request, $id, $scope)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user  = $this->getUser();

        if (null !== $group = $this->getRepository('group')->find($id)) {
            if (null !== $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group)) {
                if (in_array($user_in_group->getStatus(), array(UserInGroup::STATUS_OWNER, UserInGroup::STATUS_ADMIN))) {
                    $form = $this->createNamedForm('user_in_group', 'collection', null, array(
                        'mapped'    => false,
                        'attr'        => array('class' => '', 'id' => "group-invite-form"),
                        'type'      => 'number',
                        'allow_add' => true,
                        'csrf_protection' => false,
                        'options'   => array(
                            'required'  => false
                        )));
                    if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                        $ids = $form->getData();
                        $invited = array();

                        foreach ($ids as $id) {
                            if (null !== $member = $this->getRepository('user')->find($id)) {
                                $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($member, $group);
                                $user_in_group = $user_in_group ? $user_in_group : $this->createObject('user_in_group');

                                $user_in_group->setUser($member)
                                    ->setStatus(UserInGroup::STATUS_INVITED)
                                    ->setGroup($group)
                                    ->setDate(new \DateTime());

                                $group->addUser($user_in_group);
                                $member->addGroup($user_in_group);

                                $this->persist($user_in_group, $member);
                                $invited[] = $member;
                                $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_INVITE, $user, $group, $id);
                            }
                        }
                        $this->persist($group);
                        $this->flush();

                        return $this->render('pum://includes/common/componants/groups/invite_relation_result.html.twig', array(
                            'scope'     => $scope,
                            'group'     => $group,
                            'invited'   => $invited
                        ));
                    }

                    $users_in_groups = $this->getRepository('user_in_group')->getUserListInGroup($group);
                    $friends = ($scope == 'relation') ? $this->getRepository('user')->getFriendsNotInList($this->getUser(), $users_in_groups) : null;

                    return $this->render('pum://includes/common/componants/groups/invite_relation.html.twig', array(
                        'scope' => $scope,
                        'group' => $group,
                        'form'  => $form->createView(),
                        'friends' => $friends
                    ));
                }
            }
            $this->throwAccessDenied('error.group.invit_members_denied');
        }
        $this->throwNotFound('error.group.not_found');
    }


    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id_group    Id of group to perform
     *
     * @return Response A Response instance
     *
     * @Route(path="/invite-external/{id_group}", name="invite_external", defaults={"_project"="rpe"})
     */
    public function inviteExternalAction(Request $request, $id_group)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user       = $this->getUser();
        $group = $this->getRepository('group')->find($id_group);

        $invitation = $this->createObject('invitation');
        $form       = $this->createNamedForm('invitation', 'pum_object', $invitation, array(
            'form_view'   => $this->createFormViewByName('invitation', 'create', false),
            'attr'        => array('class' => '', 'id' => "group-invite-form"),
            'csrf_protection' => false
        ));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            if (true === $user->isInvited()) {
                throw new \RuntimeException('You cannot invit as invited user');
            }

            $userExternal = $userInvited = $userExist = $userInvalid = array();
            $emails = explode(';', $invitation->getEmail());

            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $userInvalid[] = $email;
                    continue;
                }

                $existUser = $this->getRepository('user')->findOneByEmailPro($email);
                if (null !== $existUser) {
                    $uig = $this->getRepository('user_in_group')->getUserInGroup($existUser, $group);
                    if ($uig === null) {
                        $user_in_group = $this->createObject('user_in_group')
                            ->setUser($existUser)
                            ->setStatus(UserInGroup::STATUS_INVITED)
                            ->setGroup($group)
                            ->setDate(new \DateTime());
                        $group->addUser($user_in_group);
                        $existUser->addGroup($user_in_group);
                        $this->persist($user_in_group, $existUser);

                        $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_INVITE, $user, $group, $existUser->getId());
                        $userExist[] = $email;
                        continue;

                    } elseif ($uig->isUserInGroup() === false) {
                        $uig->setStatus(UserInGroup::STATUS_INVITED);
                        $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_INVITE, $user, $group, $existUser->getId());
                        $this->persist($uig);
                        $userExist[] = $email;
                        continue;
                    } else {
                        $userInvited[] = $email;
                        continue;
                    }
                }

                $invit = clone $invitation;

                $invit
                    ->setEmail($email)
                    ->setInviteby($user)
                    ->setDate(new \DateTime())
                    ->setStatus(Invitation::STATUS_AWAITING);
                $user->addInvitation($invit);
                list($invit, $invited) = $this->createUserFromInvitation($invit);

                $this->persist($user, $invit, $invited)->flush();
                $this->sendEmailFromInvitation($invit, $id_group);
                $userExternal[] = $email;
            }

            return $this->render('pum://includes/common/componants/groups/invite_relation_result.html.twig', array(
                'userInvited' => $userInvited,
                'userInvalid' => $userInvalid,
                'userExist'   => $userExist,
                'userExternal' => $userExternal
            ));
        }

        return $this->render('pum://includes/common/componants/groups/invite_external.html.twig', array(
            'group' => $group,
            'form'  => $form->createView()
        ));
    }


    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of group to perform
     *
     * @return Response A Response instance
     *
     * @Route(path="/group_notmemberlist/{id}", name="ajax_group_notmemberlist", defaults={"_project"="rpe"})
     */
    public function groupNotMemberListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $search = trim($request->get('search', ""));
        $group = $this->getRepository('group')->find($id);

        // select users not in group and not invited to group
        $qb_users = $this->getRepository('user')
            ->createQueryBuilder('user');
        $qb_users
            ->select('user.id, user.firstname, user.lastname, user.avatar_id, user.avatar_mime')
            ->andWhere('user.status = :status')
            ->leftJoin('user.groups', 'uig', 'WITH', 'uig.group = :group')
            ->andWhere('uig IS NULL')
            ->andWhere($qb_users->expr()->like(
                $qb_users->expr()->concat('user.firstname',
                    $qb_users->expr()->concat($qb_users->expr()->literal(' '),
                        $qb_users->expr()->concat('user.lastname',
                            $qb_users->expr()->concat($qb_users->expr()->literal(' '), 'user.firstname')))),
                $qb_users->expr()->literal('%'.$search.'%')
            ))
            ->setMaxResults(28)
            ->setParameters(array('status' => 'ACTIVE', 'group' => $group));
        $result = $qb_users->getQuery()->getResult();

        return $this->render('pum://page/group/ajax-group_notmemberlist.html.twig', array(
            'group'         => $group,
            'users'          => $result
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of group to perform
     *
     * @return Response A Response instance
     *
     * @Route(path="/invite-return/{id}", name="invite_return", defaults={"_project"="rpe"})
     */
    public function inviteReturnAction(Request $request, $id)
    {
        // Security : blocks user if not logged
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // Geting group id
        $group = $this->getRepository('group')->find($id);
        $members = $this->getRepository('user')->getMembers($group, false);

        // Render
        return $this->render('pum://includes/common/group/group_administration_members.html.twig', array(
            'group'   => $group,
            'members' => $members
        ));
    }


    /**
     * @access private
     * @param  Invitation $invitation     Invitation instance
     *
     * @return array    An array contains the invitation and user instance
     */
    private function createUserFromInvitation(Invitation $invitation)
    {
        $user_type = $this->getUserTypeFromEmail($invitation->getEmail());

        $invited = $this->createObject('user')
            ->setStatus(User::STATUS_TYPE_AWAITING_CONFIRMATION)
            ->setType($user_type)
            ->setEmailPro($invitation->getEmail())
            ->setValidationKey(md5(mt_rand().uniqid().microtime()))
            ->setDate(new \Datetime());
        $invitation->setUser($invited);
        return array($invitation, $invited);
    }


    /**
     * @access private
     * @param  string $email     Email
     *
     * @return string   Return the user type according to email
     */
    private function getUserTypeFromEmail($email)
    {
        $email = explode('@', $email);
        if (count($email) === 2) {
            $email = '@'.trim(strtolower($email[1]));

            if (null !== $this->getRepository('email_domain')->findOneByDomain($email)) {
                return User::TYPE_COMMON;
            }
        }
        return User::TYPE_INVITED;
    }


    /**
     * @access private
     * @param  Invitation $invitation     Invitation instance
     * @param  string     $id_group          Id of group
     *
     * @return void
     */
    private function sendEmailFromInvitation(Invitation $invitation, $id_group = null)
    {
        $user = $invitation->getUser();
        $group = isset($id_group) ? $this->getRepository('group')->find($id_group) : null;

        $subject = $this->get('translator')->trans('invitation.email_subject', array(), 'rpe');
        if ($group !== null) {
            $subject = $this->get('translator')->trans('invitation.group.email_subject', array('%name%' => $group->getName()), 'rpe');
        }

        $name_template = isset($id_group) ? 'pum://emails/invitation_group.html.twig' : 'pum://emails/invitation.html.twig';

        $this->get('rpe.mailer')->send(array(
            'subject'      => $subject,
            'from'         => $this->getSenderEmail(),
            'to'           => $invitation->getEmail(),
            'template' => array(
                'name'     => $name_template,
                'vars'     => array(
                    'invitation'        => $invitation,
                    'group'             => $group,
                    'confirmation_link' => $this->generateUrl('invited_register', array('id' => $invitation->getId(), 'group' => $id_group, 'key' => $user->getValidationKey()), UrlGeneratorInterface::ABSOLUTE_URL)
                )
            ),
            'type'         => 'text/html'
        ));
    }

    /**
     * @access public
     * @param  Request    $request     A request instance
     * @param  string     $groupId     Id of group
     * @param  string     $type        Widget type of group
     * @param  string     $widgetId    Widget id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-widget/{groupId}/{type}/{widgetId}", name="group_widget", defaults={"_project"="rpe", "widgetId": null})
     */
    public function groupWidgetAction(Request $request, $groupId, $type, $widgetId = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null === $group = $this->getRepository('group')->find($groupId)) {
            $this->throwNotFound('error.group.not_found');
        }

        if (!$this->getUser()->isGroupAdmin($group)) {
            $this->throwAccessDenied('error.group.manage_access_denied');
        }

        if (null != $widgetId) {
            if (null === $widget = $this->getRepository('social_group_widget')->find($widgetId)) {
                $this->throwNotFound('error.groupwidget.not_found');
            }
        } else {
            $widget = $this
                ->createObject('social_group_widget')
                ->setSocialGroup($group)
                ->setType($type)
            ;
        }
        // get max number widgets to create
        if (null === $nbModulesLimit = $group->getMeta($widget::META_DEFAULT_NB_MAX.$type)) {
            $nbModulesLimit = $widget::DEFAULT_NB_MAX;
            $this->setGroupMeta($group, $widget::META_DEFAULT_NB_MAX.$type, $widget::DEFAULT_NB_MAX, 'modules');
        } else {
            $nbModulesLimit = $nbModulesLimit->getValue();
        }

        // get widget type
        if (null == $type = $widget->getType()) {
            throw new \RuntimeException('Widget type is unknown');
        }
        $formWidget = $this->createNamedForm('groupwidget_'.$type, 'rpe_form_groupwidget_'.$type, $widget);
        if ($request->isMethod('POST')) {
            $groupWidgets = $group->getWidgetsBy(array('type' => $type));
            if ($groupWidgets->count() < $nbModulesLimit) {
                // handle request
                if ($formWidget->handleRequest($request)->isSubmitted()) {
                    if ($formWidget->isValid()) {
                        $widget = $formWidget->getData();
                        $group->addWidget($widget);

                        $this
                            ->persist($group, $widget)
                            ->flush();
                    } else {
                        foreach ($formWidget->getErrors() as $error) {
                            $this->addError($this->get('translator')->trans('groupPage.module.creation_error', array(), 'rpe') . ' : ' . $error->getMessage());
                        }
                    }
                    return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
                }
            } else {
                $this->addError($this->get('translator')->trans('groupPage.module.limit_error', array(), 'rpe'));
            }
        }

        // Render
        return $this->render('pum://page/group/module/form-group-widget.html.twig', array(
            'group'      => $group,
            'widgetForm' => $formWidget->createView(),
            'widget'     => $widget
        ));
    }


    /**
     * @access public
     * @param  Request    $request     A request instance
     * @param  string     $groupId     Id of group
     * @param  string     $widgetId    Widget id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-widget-delete/{groupId}/{widgetId}", name="group_widget_delete", defaults={"_project"="rpe"})
     */
    public function groupWidgetDeleteAction(Request $request, $groupId, $widgetId)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null === $group = $this->getRepository('group')->find($groupId)) {
            $this->throwNotFound('error.group.not_found');
        }

        if (!$this->getUser()->isGroupAdmin($group)) {
            $this->throwAccessDenied('error.group.manage_access_denied');
        }

        if (null === $widget = $this->getRepository('social_group_widget')->find($widgetId)) {
            $this->throwNotFound('error.groupwidget.not_found');
        }

        $group->removeWidget($widget);

        $this
            ->persist($group, $widget)
            ->flush();

        return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
    }


    /**
     * @access public
     * @param  Request    $request     A request instance
     * @param  string     $groupId     Id of group
     * @param  string     $widgetId    Widget id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-widget-edit/{groupId}/{widgetId}", name="group_widget_edit", defaults={"_project"="rpe"})
     */
    public function groupWidgetEditAction(Request $request, $groupId, $widgetId)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null === $group = $this->getRepository('group')->find($groupId)) {
            $this->throwNotFound('error.group.not_found');
        }

        if (!$this->getUser()->isGroupOwner($group)) {
            $this->throwAccessDenied('error.group.manage_access_denied');
        }

        if (null === $widget = $this->getRepository('social_group_widget')->find($widgetId)) {
            $this->throwNotFound('error.groupwidget.not_found');
        }

        $type = $widget->getType();
        $formWidget = $this->createNamedForm('groupwidget_'.$type, 'rpe_form_groupwidget_'.$type, $widget);

        if ($request->isMethod('POST')) {
            // handle request
            if ($formWidget->handleRequest($request)->isValid()) {
                $widget = $formWidget->getData();

                $this
                    ->persist($widget)
                    ->flush();

                $this->addSuccess($this->get('translator')->trans('groupPage.module.edit_success', array(), 'rpe'));
            }
        }
        // Render
        return $this->render('pum://page/group/module/form-group-widget.html.twig', array(
            'group'      => $group,
            'widgetForm' => $formWidget->createView(),
            'widget'     => $widget,
            'editForm'   => true,
        ));
    }

    /**
     * @access public
     * @param  string     $groupId     Id of group
     * @param  string     $id          Group Widget id
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-rss-parser/{groupId}/{id}", name="group-rss-parser", defaults={"_project"="rpe"})
     */
    public function parseRssUrl($groupId, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null === $group = $this->getRepository('group')->find($groupId)) {
            $this->throwNotFound('error.group.not_found');
        }

        if ($id && null !== $widget = $this->getRepository('social_group_widget')->findOneBy(array('socialGroup' => $group, 'id' => $id, 'type' => GroupWidget::TYPE_RSS))) {
            return $this->render('pum://page/group/module/ajax/rss.html.twig', array(
                'data' => $this->get('rpe.rss.parser')->get($widget->getContent())
            ));
        }

        return new Response();
    }

    /**
     * @access public
     * @param  Request    $request     A request instance
     * @param  string     $groupId     Id of group
     * @param  string     $theme_id    Id of theme
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-theme/{id}/{theme_id}", name="group-theme", defaults={"_project"="rpe", "theme_id"=null})
     */
    public function groupThemes(Request $request, $id, $theme_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        if (null === $group = $this->getRepository('group')->find($id)) {
            $this->throwNotFound('error.group.not_found');
        }
        $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group);
        $themes = $this->getRepository('social_group_theme')->getGroupThemes($group);

        // if AJAX request : return only posts from themes instead of all library page
        if ($request->isXmlHttpRequest()) {
            // Get medias from folder
            if (null !== $theme_id) {
                $theme = $this->getRepository('social_group_theme')->find($theme_id);
                if ($theme) {
                    $posts = $theme->getPosts();
                }
            } else {
                $theme = null;
                $posts = $this->getRepository('post')->getGroupPublicationsWithoutTheme($group);
            }

            return $this->render('pum://page/group/ajax-post_list.html.twig', array(
                'theme' => $theme,
                'posts' => isset($posts) ? $posts : null,
                'theme_id' => $theme_id
            ));
        }

        // Render
        return $this->render('pum://page/group/group_theme.html.twig', array(
            'group'      => $group,
            'themes'    => $themes,
            'userInGroup' => $user_in_group,
            'theme_id'   => $theme_id
        ));
    }


    /**
     * Display the form to create a group theme
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $theme_id    The theme id
     * @param  string  $group_id    The group id
     *
     * @uses GroupController:createThemeForm() to create the form
     *
     * @return Response A Response instance
     *
     * @Route(path="/createform/type/group-theme/{group_id}/{theme_id}", name="create_form_group_theme", defaults={"_project"="rpe", "theme_id"=null})
     */
    public function createThemeAction(Request $request, $group_id, $theme_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user = $this->getUser();
        $group = $this->getRepository('group')->find($group_id);

        if (false == $user->isGroupAdmin($group)) {
            return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
        }

        list($form, $theme) = $this->createThemeForm($request, $theme_id, $group);

        if (null !== $theme_id && $response = $this->editTheme($request, $form, $theme)) {
            return $response;
        } elseif ($response = $this->addTheme($request, $form, $theme, $group)) {
            return $response;
        }

        return $this->render('pum://includes/common/componants/groups/theme_form.html.twig', array(
            'form'  => $form->createView(),
            'group' => $group,
            'theme_id' => $theme_id
        ));
    }

    /**
     * @access private
     * @param  Request $request     A request instance
     * @param  Form    $form        Form object
     * @param  Theme   $theme       Theme object
     *
     * @return Response|boolean A Response instance
     *
     */
    private function editTheme(Request $request, $form, $theme)
    {
        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->persist($theme)->flush();

            return $this->render('pum://includes/common/componants/groups/theme_form.html.twig', array(
                'theme' => $theme,
                'group' => $theme->getGroup()
            ));
        }
        return false;
    }

    /**
     * @access private
     * @param  Request $request     A request instance
     * @param  string  $theme_id    Theme id
     * @param  Group   $group       Group object
     *
     * @return array Array contain the form and folder object
     */
    private function createThemeForm(Request $request, $theme_id, $group)
    {
        if (null === $theme_id || ((null === $theme = $this->getRepository('social_group_theme')->find($theme_id)) || $theme->getGroup() !== $group)) {
            $theme = $this->createObject('social_group_theme');
        }

        $form = $this->createNamedForm('theme', 'pum_object', $theme, array(
            'form_view'   => $this->createFormViewByName('social_group_theme', 'simple', $update = false),
            'with_submit' => true
        ));

        return array($form, $theme);
    }

    /**
     * Add theme
     *
     * @access private
     * @param  Request $request     A request instance
     * @param  Form    $form        Form object
     * @param  Theme   $theme       Theme object
     * @param  Group   $group       Group object
     *
     * @return Response|boolean A Response instance
     */
    private function addTheme(Request $request, $form, $theme, $group)
    {
        $user = $this->getUser();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                $theme->setGroup($group);
                $group->addTheme($theme);

                $this->persist($group, $theme)->flush();

            return $this->render('pum://includes/common/componants/groups/theme_form.html.twig', array(
                'group'  => $group,
                'theme'  => $theme
            ));
        }
        return false;
    }

    /**
     * XHR Method to move a post to another theme.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $media_id    The post id
     * @param  string  $theme_id    The theme id
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-post-move/{media_id}/{theme_id}", name="ajax_move_post_to_theme", defaults={"_project"="rpe", "theme_id"=null})
     */
    public function ajaxMovePostToThemeAction(Request $request, $media_id, $theme_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            if ((null !== $post = $this->getRepository('post')->find($media_id))) {
                if ((null !== $theme_id) && (null !== $theme = $this->getRepository('social_group_theme')->find($theme_id))) {
                    $theme->addPost($post);
                    $post->setTheme($theme);
                } else {
                    $post->setTheme(null);
                }
                $this->flush();
                return new Response('OK');
            }
        }

        return new Response('ERROR');
    }

    /**
     * XHR Method to display posts of a theme
     *
     * @access public
     * @param  string  $group_id          Group id
     * @param  string  $status            Status of users to list
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-theme-postlist/{theme_id}", name="ajax_theme_postlist", defaults={"_project"="rpe"})
     */
    public function groupAjaxThemePostList($theme_id, $status = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $theme = $this->getRepository('social_group_theme')->find($theme_id);
        if (null === $theme) {
            return new Response('Theme not found');
        }

        $posts = $theme->getPosts();

        return $this->render('pum://includes/common/group/theme_posts.html.twig', array(
            'posts'    => $posts,
            'theme'    => $theme
        ));
    }
}
