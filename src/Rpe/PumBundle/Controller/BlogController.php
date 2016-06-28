<?php
namespace Rpe\PumBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Rpe\PumBundle\Model\Social\Log;
use Rpe\PumBundle\Model\Social\Post;
use Pagerfanta\Pagerfanta;
use Rpe\PumBundle\Model\Social\UserInBlog;
use Rpe\PumBundle\Model\Social\Notification;
use Rpe\PumBundle\Model\Social\Invitation;
use Rpe\PumBundle\Model\Social\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blog controller
 *
 * @method Response blogCreateAction(Request $request)
 * @method Response blogAction(Request $request, $id)
 * @method Response blogListAction(Request $request, $page)
 * @method Response blogEditAction(Request $request, $id)
 * @method Response blogsAction($mode)
 * @method Response blogAjaxUserList($blog_id, $status = null)
 * @method Response blogAjaxUserInfos($blog_id, $user_id)
 * @method Response inviteRelationsAction(Request $request, $id, $scope)
 * @method Response inviteReturnAction(Request $request, $id)
 * @method Resposne inviteExternalAction(Request $request, $id_blog)
 * @method array    createUserFromInvitation(Invitation $invitation)
 * @method string   getUserTypeFromEmail($email)
 * @method void     sendEmailFromInvitation(Invitation $invitation, $id_blog = null)
 *
 */
class BlogController extends Controller
{
    /** All blogs */
    const LISTMODE_ALL      = 'all_blogs';
    /** Followed blogs */
    const LISTMODE_FOLLOWED = 'followed';
    /** Public blogs */
    const LISTMODE_PUBLIC   = 'public';
    /** Private blogs */
    const LISTMODE_PRIVATE  = 'private';

    /**
     * Display and process Blog's creation & edit.
     * Display the form and process its submission for creating or editing a Blog
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @uses Rpe\PumBundle\Extension\Tool\Avatar:getPaletteColorFromText() to get Avatar's color from Blog's name
     * @uses Rpe\PumBundle\Extension\Tool\Avatar:getMaskedImage() to get generated Avatar for Blog
     *
     * @return Response A Response instance
     *
     * @Route(path="/create-blog", name="create-blog", defaults={"_project"="rpe"})
     */
    public function blogCreateAction(Request $request)
    {
        if (null != $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        if (null != $blog = $user->getBlog()) {
            return $this->redirect($this->generateUrl('blog', array('id' => $blog->getId())));
        }

        if (true === $user->isInvited()) {
            throw new \RuntimeException('You cannot create blog as invited user');
        }

        $blog = $this->createObject('blog');
        $form = $this->createNamedForm('blog', 'pum_object', $blog, array(
            'attr'          => array('class'    => 'create-form'),
            'form_view'     => $this->createFormViewByName('blog', 'create', $update = false),
            'with_submit'   => false
        ));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $colors = $this->get('tool.avatar')->getPaletteColorFromText($form->get('name')->getData(), false);

            $blog->setCover($this->get('tool.avatar')->getMaskedImage('users', $colors, 837, 400, false));

            $blog
                ->setCreateDate(new \DateTime())
                ->setUpdateDate(new \DateTime())
                ->setStatus($blog::STATUS_TYPE_ACTIVE)
                ->setOwner($user);

            $user->setBlog($blog);
            $this->setUserMeta($user, User::META_HAS_BLOG, 1);

            $this->persist($blog, $user)->flush();

            return $this->redirect($this->generateUrl('blog', array('id' => $blog->getId())));
        }

        return $this->render('pum://page/blog/blog_edit.html.twig', array(
            'form' => $form->createView(),
            'mode' => 'create'
        ));
    }

    /**
     * Display a blog and handle posting short publications.
     * Display a blog based on its ID, show form to post short publications and handle publication submission
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of blog
     *
     * @uses Rpe\PumBundle\Model\Social\Blog:isPrivate() to know if a blog is Private or not
     * @uses Rpe\PumBundle\Controller\Controller:addLinkPreviewToForm() to add a link preview
     *
     * @return Response A Response instance
     *
     * @Route(path="/blog/{id}", name="blog", defaults={"_project"="rpe", "id"=null})
     */
    public function blogAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        if (null === $id) {
            $blog = $user->getBlog();
        } else {
            $blog = $this->getRepository('blog')->find($id);
        }

        if (null === $blog) {
            $this->throwNotFound('error.blog.not_found');
        }

        $userHasAccess = false;
        $abonnerButton = false;
        $owner         = $blog->getOwner();
        $isOwner       = $user === $owner;

        if ($blog->isPublic() || $user === $owner || ($blog->isFriends() && $owner->isFriend($user)) || $user->isInBlog($blog)) {
            $userHasAccess = true;
            if ($user !== $owner) {
                $abonnerButton = true;
            }
        } elseif (true === $blog->isPrivate()) {
            $user_in_blog = $this->getRepository('social_user_in_blog')->getUserInBlog($user, $blog);
            if (null !== $user_in_blog && $user_in_blog->isUserInBlog()) {
                $userHasAccess = true;
            }
        }

        /* BEGIN post form */
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
                ->setType(Post::TYPE_BLOG)
                ->setStatus(Post::STATUS_PUBLISHED)
                ->setCreateDate(new \DateTime())
                ->setUpdateDate(new \DateTime());

            $blog->addPost($post);
            $user->addPost($post);
            $post->setPublishedBlog($blog);

            $post = $this->handleLinkPreview($form, $post, false);

            $this->persist($post, $blog, $user)->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->render('pum://includes/common/componants/publications/publications.html.twig', array(
                    'user'        => $user,
                    'post'        => $post
                ));
            }

            return $this->redirect($this->generateUrl('blog', array('id' => $blog->getId())));
        }
        /* END post form */

        /* BEGIN cover */
        $form_cover  = null;

        $form_cover  = $this->createNamedForm('cover', 'pum_object', $blog, array(
            'form_view' => $this->createFormViewByName('blog', 'cover', $update = false),
        ));
        $form_cover = $this->addCroppedDataToForm($form_cover);

        if ($request->isMethod('POST') && $form_cover->handleRequest($request)->isValid()) {
            $cover = $form_cover->get('cover')->getData();
            if ($cover instanceof Media) {
                $coords = $this->getCropCoordsFromForm($form_cover);

                $blog->setCover($this->get('tool.avatar')->getCroppedImage($cover, $coords));
                $this->setUserMeta($user, 'blog.cover.coords', json_encode($coords));

                $this->flush();

                if (!$request->isXmlHttpRequest()) {
                    return $this->redirect($this->generateUrl('blog', array('id' => $id)));
                }

                return new Response();
            }
        }
        /* END cover */        

        return $this->render('pum://page/blog/blog.html.twig', array(
            'owner'             => $owner,
            'isOwner'           => $isOwner,
            'blog'              => $blog,
            'form'              => $form->createView(),
            'form_cover'        => (null === $form_cover) ? $form_cover : $form_cover->createView(),
            'userHasAccess'     => $userHasAccess,
            'abonnerButton'      => $abonnerButton,
            'mode'              => 'create',
            'members'           => $this->getRepository('user')->getBlogMembers($blog, false),
        ));
    }

    /**
     * Display form to edit Blog and handle submission.
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/blog/{id}/edit", name="edit_blog", defaults={"_project"="rpe"})
     */
    public function blogEditAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        if (null !== $blog = $this->getRepository('blog')->find($id)) {
            if ($user === $blog->getOwner()) {
                $form  = $this->createNamedForm('blog', 'pum_object', $blog, array(
                    'attr'        => array('class' => 'create-form'),
                    'form_view'   => $this->createFormViewByName('blog', 'edit', $update = false),
                    'with_submit' => false
                ));

                if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                    $blog->setUpdateDate(new \DateTime());
                    $this->persist($blog)->flush();

                    return $this->redirect($this->generateUrl('blog', array('id' => $id)));
                }

                return $this->render('pum://page/blog/blog_edit.html.twig', array(
                    'form'  => $form->createView(),
                    'blog' => $blog,
                    'mode'  => 'edit'
                ));
            }

            $this->throwAccessDenied('error.blog.manage_access_denied');
        }

        $this->throwNotFound('error.blog.not_found');
    }

    /**
     * XHR method to display a page of blogs list.
     * Show a specific page from a blogs list with or without filters
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $page        List mode (base on LISTMODE_* constants)
     *
     * @return Response A Response instance
     *
     * @Route(path="/bloglist/{page}", name="ajax_bloglist", defaults={"_project"="rpe", "page"="1"})
     */
    public function blogListAction(Request $request, $page)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // Page
        $byPage  = 9;

        // Mode
        $mode = $request->query->get('mode', self::LISTMODE_ALL);

        // Get Groups
        switch ($mode) {
            case self::LISTMODE_ALL:
                $blogs = $this->getRepository('blog')->getAllBlogs(true);
                $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($blogs, true, false);
                break;

            case self::LISTMODE_FOLLOWED:
                $blogs = $this->getRepository('blog')->getFollowedBlogs($this->getUser(), true);
                $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($blogs, true, false);
                break;

            case self::LISTMODE_PUBLIC:
                $blogs = $this->getRepository('blog')->getPublicBlogs(true);
                $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($blogs, true, false);
                break;

            case self::LISTMODE_PRIVATE:
                $blogs = $this->getRepository('blog')->getPrivateBlogs(true);
                $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($blogs, true, false);
                break;
        }

        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/blog/ajax-bloglist.html.twig', array(
            'mode'  => $mode,
            'pager'  => $pager
        ));
    }

    /**
     * Display the list of blogs.
     *
	 * @access public
     * @param  string  $mode        List mode (based on LISTMODE_* constants)
     *
     * @return Response A Response instance
     *
     * @Route(path="/blogs/{mode}", name="blogs", defaults={"_project"="rpe", "mode"= null})
     */
    public function blogsAction($mode)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $filters = array(
            self::LISTMODE_ALL,
            self::LISTMODE_FOLLOWED,
            self::LISTMODE_PUBLIC,
            self::LISTMODE_PRIVATE
        );

        if (null == $mode) {
            $mode = self::LISTMODE_ALL;
        } elseif (!in_array($mode, $filters)) {
            $this->throwNotFound('error.filters.not_found');
        }

        return $this->render('pum://page/blog/blogs.html.twig', array(
            'filters' => $filters,
            'mode'    => $mode
        ));
    }

    /**
     * Show a list of users for a blog.
     *
     * @deprecated No longer used by internal code and not recommended.
     * @access public
     * @param  string  $status       User in blog status
     * @param  string  $blog_id      Id of blog
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-blog-userlist/{blog_id}/{status}", name="blog_userlist", defaults={"_project"="rpe"})
     */
    public function blogAjaxUserList($blog_id, $status = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $blog = $this->getRepository('blog')->find($blog_id);
        $user = $this->getUser();

        if (!$user->isBlogOwner($blog)) {
            return new Response('ERROR');
        }

        $users = $this->getRepository('social_user_in_blog')->getUserListByStatus($blog, $status);

        return $this->render('pum://includes/common/blog/members/userlist.html.twig', array(
            'blog'    => $blog,
            'users'    => $users
        ));
    }

    /**
     * Show informations of a user from a blog.
     *
     * @access public
     * @param  string  $user_id      Id of user
     * @param  string  $blog_id      Id of blog
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-blog-user/{blog_id}/{user_id}", name="blog_user_getinfo", defaults={"_project"="rpe"})
     */
    public function blogAjaxUserInfos($blog_id, $user_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $blog = $this->getRepository('blog')->find($blog_id);
        $user = $this->getUser();

        if (!$user->isBlogOwner($blog)) {
            return new Response('ERROR');
        }

        $user_infos = $this->getRepository('social_user_in_blog')->getUserInBlog($user_id, $blog_id);
        $relation_detail = $this->getRepository('friend')->getRelation($user_infos->getUser(), $user);

        return $this->render('pum://page/blog/ajax-user-getinfo.html.twig', array(
            'blog'             => $blog,
            'userinblog'       => $user_infos,
            'relation_detail'   => $relation_detail,
            'user_id'           => $user_id
        ));
    }

    /**
     * Action to invite relations to a blog
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $scope       Scope of user ("relations")
     * @param  string  $id          Id of blog
     *
     * @return Response A Response instance
     *
     * @Route(path="/invite-relation-blog/{id}/{scope}", name="invite_relation_blog", defaults={"_project"="rpe","scope"="relation"})
     */
    public function inviteRelationsAction(Request $request, $id, $scope)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user  = $this->getUser();

        if (null !== $blog = $this->getRepository('blog')->find($id)) {

            if ($user->isBlogOwner($blog)) {
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
                            if (null === $this->getRepository('social_user_in_blog')->getUserInBlog($member, $blog)) {
                                $user_in_blog = $this->createObject('social_user_in_blog')
                                    ->setUser($member)
                                    ->setStatus(UserInBlog::STATUS_USER)
                                    ->setBlog($blog)
                                    ->setDate(new \DateTime());

                                $blog->addUser($user_in_blog);
                                $member->addBlog($user_in_blog);

                                $this->persist($user_in_blog, $member);
                                $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_INVITE, $user, $blog, $id);
                                $invited[] = $member;
                            }
                        }
                    }
                    $this->persist($blog);
                    $this->flush();

                    return $this->render('pum://includes/common/componants/blogs/invite_relation_result.html.twig', array(
                        'invited'   => $invited
                    ));
                }

                $users_in_blogs = $this->getRepository('social_user_in_blog')->getUserListInBlog($blog);
                $friends = ($scope == 'relation') ? $this->getRepository('user')->getFriendsNotInList($this->getUser(), $users_in_blogs) : null;

                return $this->render('pum://includes/common/componants/blogs/invite_relation.html.twig', array(
                    'scope' => $scope,
                    'blog' => $blog,
                    'form'  => $form->createView(),
                    'friends' => $friends
                ));
            }
            $this->throwAccessDenied('error.group.invit_members_denied');

        }
        $this->throwNotFound('error.group.not_found');
    }

    /**
     * Display list of invited users in blog
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of blog
     *
     * @return Response A Response instance
     *
     * @Route(path="/invite-return-blog/{id}", name="invite_return_blog", defaults={"_project"="rpe"})
     */
    public function inviteReturnAction(Request $request, $id)
    {
        // Security : blocks user if not logged
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // Geting group id
        $blog = $this->getRepository('blog')->find($id);

        // Geting member id
        $membersId = $this->getRepository('social_user_in_blog')->getActiveUserInBlog($id);
        foreach ($membersId as $key => $value) {
            $membersId[$key] = $value['id'];
        };
        $members = $this->getRepository('user')->findBy(array('id' => $membersId));

        // Render
        return $this->render('pum://includes/common/blog/blog_administration_members.html.twig', array(
            'blog'   => $blog,
            'members' => $members
        ));
    }


    /**
     * Action to invite an external people (with mail) to the blog
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id_blog     Id of blog
     *
     * @uses BlogController:createUserFromInvitation() to create an User based on the invitation
     * @uses BlogController:sendEmailFromInvitation() to send a invitation's mail
     *
     * @return Response A Response instance
     *
     * @Route(path="/invite-external-blog/{id_blog}", name="invite_external_blog", defaults={"_project"="rpe"})
     */
    public function inviteExternalAction(Request $request, $id_blog)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user       = $this->getUser();
        $blog = $this->getRepository('blog')->find($id_blog);

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

            if (!filter_var($invitation->getEmail(), FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Invalid email');
            }

            $existUser = $this->getRepository('user')->findOneByEmailPro($invitation->getEmail());
            if (null !== $existUser) {
                $uig = $this->getRepository('social_user_in_blog')->getUserInblog($existUser, $blog);
                if ($uig === null) {
                    $user_in_blog = $this->createObject('social_user_in_blog')
                    ->setUser($existUser)
                    ->setStatus(UserInBlog::STATUS_INVITED)
                    ->setBlog($blog)
                    ->setDate(new \DateTime());
                    $blog->addUser($user_in_blog);
                    $existUser->addBlog($user_in_blog);
                    $this->persist($user_in_blog, $existUser);

                    $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_INVITE, $user, $blog, $existUser->getId());
                    return $this->render('pum://includes/common/componants/blogs/invite_relation_result.html.twig', array(
                        'externalEmail' => $existUser->getEmailPro()
                    ));
                }
                throw new \RuntimeException('User already invited in blog');
            }

            $invitation
                ->setInviteby($user)
                ->setDate(new \DateTime())
                ->setStatus(Invitation::STATUS_AWAITING)
                ;
            $user->addInvitation($invitation);

            list($invitation, $invited) = $this->createUserFromInvitation($invitation);

            $this->persist($user, $invitation, $invited)->flush();

            $this->sendEmailFromInvitation($invitation, $id_blog);

            return $this->render('pum://includes/common/componants/blogs/invite_relation_result.html.twig', array(
                'externalEmail' => $invitation->getEmail()
            ));
        }

        return $this->render('pum://includes/common/componants/blogs/invite_external.html.twig', array(
            'blog' => $blog,
            'form'  => $form->createView()
        ));
    }

    /**
     * @access private
     * @param  Invitation $invitation     An invitation instance
     *
     * @uses BlogController:getUserTypeFromEmail() to get the User type based on the email
     *
     * @return array  An array contains form
     *
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
     * Get the user type based on email domain.
     * Match mail domain with the email_domain repository content.
     *
     * @access private
     * @param  string $email     User email
     *
     * @return string  type of user
     *
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
     * Send a invitation's mail based on invitation object
     *
     * @access private
     * @param  Invitation $invitation     An invitation instance
     * @param  string  $id_blog     Id of blog
     *
     * @return void
     *
     */
    private function sendEmailFromInvitation(Invitation $invitation, $id_blog = null)
    {
        $user = $invitation->getUser();
        $blog = isset($id_blog) ? $this->getRepository('blog')->find($id_blog) : null;

        $subject = $this->get('translator')->trans('invitation.email_subject', array(), 'rpe');
        if ($blog !== null) {
            $subject = $this->get('translator')->trans('invitation.blog.email_subject', array('%name%' => $blog->getName()), 'rpe');
        }

        $name_template = isset($id_blog) ? 'pum://emails/invitation_blog.html.twig' : 'pum://emails/invitation.html.twig';

        $this->get('rpe.mailer')->send(array(
            'subject'      => $subject,
            'from'         => $this->getSenderEmail(),
            'to'           => $invitation->getEmail(),
            'template' => array(
                'name'     => $name_template,
                'vars'     => array(
                    'invitation'        => $invitation,
                    'blog'             => $blog,
                    'confirmation_link' => $this->generateUrl('invited_register', array('id' => $invitation->getId(), 'blog' => $id_blog, 'key' => $user->getValidationKey()), UrlGeneratorInterface::ABSOLUTE_URL)
                )
            ),
            'type'         => 'text/html'
        ));
    }
    
    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of group to perform
     *
     * @return Response A Response instance
     * 
     * @Route(path="/blog-notmemberlist/{id}", name="ajax_blog_notmemberlist", defaults={"_project"="rpe"})
     */
    public function blogNotMemberListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $search = trim($request->get('search', ""));
        $blog = $this->getRepository('blog')->find($id);
    
        // select users not in group and not invited to group
        $qb_users = $this->getRepository('user')
            ->createQueryBuilder('user');
        $qb_users
            ->select('user.id, user.firstname, user.lastname, user.avatar_id, user.avatar_mime')
            ->andWhere('user.status = :status')
            ->leftJoin('user.blogs', 'uib', 'WITH', 'uib.blog = :blog')
            ->andWhere('uib IS NULL')
            ->andWhere($qb_users->expr()->like(
                $qb_users->expr()->concat('user.firstname',
                    $qb_users->expr()->concat($qb_users->expr()->literal(' '),
                        $qb_users->expr()->concat('user.lastname',
                            $qb_users->expr()->concat($qb_users->expr()->literal(' '), 'user.firstname')))),
                $qb_users->expr()->literal('%'.$search.'%')
            ))
            
            ->setMaxResults(28)
            ->setParameters(array('status' => 'ACTIVE', 'blog' => $blog));
            $result = $qb_users->getQuery()->getResult();
    
        return $this->render('pum://page/blog/ajax-blog_notmemberlist.html.twig', array(
            'blog'         => $blog,
            'users'          => $result
        ));
    }
}
