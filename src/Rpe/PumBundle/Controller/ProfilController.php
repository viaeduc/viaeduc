<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Notification;
use Rpe\PumBundle\Model\Social\Log;
use Rpe\PumBundle\Model\Social\User;
use Symfony\Component\Form\FormError;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Profil controller
 * 
 * @method Response profilAction(Request $request, $id = null)
 * @method Response profilNotificationsListAction(Request $request, $page, $profilid)
 * @method Response profilPublicationsListAction(Request $request, $page, $profilid)
 * @method int      mergeAndSortArray($a, $b) 
 * @method Response editProfilAction(Request $request)
 * @method Response askRelationAction($relationId)
 * @method Response myAcountAction(Request $request)
 * @method Response myAcountVerifyUserAction(Request $request)
 * @method Response myExperiencesAction(Request $request)
 * @method Response myTrainingAction(Request $request)
 * @method void     registerUserMeta(User $user, $key, $value, $type = 'notifications') 
 * @method Response myConfidentialAction(Request $request)
 * @method Response myNotificationAction(Request $request)
 * @method Response deleteExperienceAction(Request $request)
 * @method Response deleteFormationAction(Request $request)
 * @method Response profilRelationListAction(Request $request, $id)
 * @method Response profilInfoListAction(Request $request, $id)
 * @method Response profilCommonRelationListAction(Request $request, $id)
 * @method Response profilResourceListAction(Request $request, $id)
 * @method Response profilGroupListAction(Request $request, $id)
 * @method Response myApiKeysAction(Request $request)
 * @method string   getFreeApiKey()
 * @method string   generateApiSecret()
 * @method boolean  getConfidentiality($type, $user, $profil)
 */
class ProfilController extends Controller
{
    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          The profil user id
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/profil/{id}", name="profil", defaults={"_project"="rpe"})
     */
    public function profilAction(Request $request, $id = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        
        if (!is_null($id)) {
            
            $id = $this->get('rpe.hashid')->checkHash($id, 'user');
            $profil = $id ? $this->getRepository('user')->find($id) : $id;
            if ($profil == false) {
                $this->throwNotFound('error.profil.invalid');
            }
            $profilIsCurrent = ($user === $profil);
            
            // for invited user, check if it has right
            if ($user->isInvited()) {
                $uig_common = $this->getRepository('user_in_group')->getCommonGroups($user, $profil);
                if (count($uig_common) == 0 && !$user->isFriend($profil)) {
                    return $this->redirect($this->generateUrl('home'));
                }
            }
        } else {
            $profil          = $user;
            $profilIsCurrent = true;
        }

        if (null === $profil) {
            $this->throwNotFound('error.user.not_found');
        }

        $commonRelation   = array();
        if (!$profilIsCurrent) {
            $commonRelation = $this->getRepository('friend')->getCommonFriends($profil, $user);
        }

        $relation_detail = $this->getRepository('friend')->getRelation($user, $profil, true);
        $canPost         = false;
        $form            = null;
        $blog_form       = null;
        
        if ($user === $profil || (null !== $relation_detail && $relation_detail->isFriend())) {
            $canPost = true;

            $post = $this->createObject('post');
            $form = $this->createNamedForm('post', 'pum_object', $post, array(
                'attr'        => array('class' => 'post-form', 'id' => "simple-post-form"),
                'form_view'   => $this->createFormViewByName('post', 'simple', $update = false),
                'with_submit' => false
            ));
            $form = $this->addLinkPreviewToForm($form);

            if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                $post
                    ->setAuthor($user)
                    ->setTargetUser($profil)
                    ->setCommentStatus(true)
                    ->setResource(false)
                    ->setBroadcast(false)
                    ->setGlobal(false)
                    ->setImportant(false)
                    ->setType(Post::TYPE_WALL)
                    ->setStatus(Post::STATUS_PUBLISHED)
                    ->setCreateDate(new \DateTime())
                    ->setUpdateDate(new \DateTime())
                ;

                $user->addPost($post);

                $post = $this->handleLinkPreview($form, $post, false);

                $this->persist($post, $user)->flush();

                if ($profil !== $user) {
                    $this->get('rpe.notifications')->wait(Notification::TYPE_PUBLICATION, $user, $post, $profil->getId());
                }
                
                if ($request->isXmlHttpRequest()) {
                    return $this->render('pum://includes/common/componants/publications/publications.html.twig', array(
                        'post'   => $post,
                        'profil' => $profil,
                        'user' => $user,
                        'single' => true
                    ));
                }

                return $this->redirect($this->generateUrl('profil', array('id' => $this->get('rpe.hashid')->encode($id))));
            }

            if ($user === $profil) {
                $post_blog = $this->createObject('post');
                $blog_form = $this->createNamedForm('blog', 'pum_object', $post_blog, array(
                    'attr'        => array('class' => 'post-form', 'id' => "blog-post-form"),
                    'form_view'   => $this->createFormViewByName('post', 'simple', $update = false),
                    'with_submit' => false
                ));

                if ($request->isMethod('POST') && $blog_form->handleRequest($request)->isValid()) {
                    $post_blog
                        ->setAuthor($user)
                        ->setCommentStatus(true)
                        ->setResource(false)
                        ->setBroadcast(false)
                        ->setGlobal(false)
                        ->setImportant(false)
                        ->setType(Post::TYPE_BLOG)
                        ->setStatus(Post::STATUS_PUBLISHED)
                        ->setCreateDate(new \DateTime())
                        ->setUpdateDate(new \DateTime())
                    ;

                    $user->addPost($post_blog);

                    $this->persist($post_blog, $user)->flush();

                    if ($request->isXmlHttpRequest()) {
                        return $this->render('pum://includes/common/componants/publications/publications.html.twig', array(
                            'post'   => $post_blog,
                            'profil' => $profil,
                            'user' => $user,
                            'single' => true
                        ));
                    }

                    return $this->redirect($this->generateUrl('profil', array('id' => $this->get('rpe.hashid')->encode($id))));
                }
            }
        }

        $form_cover  = null;
        $form_avatar = null;

        if ($user === $profil) {
            $form_cover  = $this->createNamedForm('cover', 'pum_object', $user, array(
                'form_view'   => $this->createFormViewByName('user', 'cover', $update = false),
            ));
            $form_cover = $this->addCroppedDataToForm($form_cover);

            if ($request->isMethod('POST') && $form_cover->handleRequest($request)->isValid()) {
                $cover = $form_cover->get('originalCover')->getData();
                if ($cover instanceof Media) {
                    $coords = $this->getCropCoordsFromForm($form_cover);

                    $user->setCover($this->get('tool.avatar')->getCroppedImage($cover, $coords));
                    $this->setUserMeta($user, 'user.cover.coords', json_encode($coords));

                    $this->get('rpe.search.index.factory')->update($user);

                    $this->flush();

                    if (!$request->isXmlHttpRequest()) {
                        return $this->redirect($this->generateUrl('profil', array('id' => $this->get('rpe.hashid')->encode($id))));
                    }

                    return new Response();
                }
            }

            $form_avatar  = $this->createNamedForm('avatar', 'pum_object', $user, array(
                'form_view'   => $this->createFormViewByName('user', 'avatar', $update = false),
            ));
            $form_avatar = $this->addCroppedDataToForm($form_avatar);

            if ($request->isMethod('POST') && $form_avatar->handleRequest($request)->isValid()) {
                $avatar = $form_avatar->get('avatar')->getData();
                if ($avatar instanceof Media) {
                    $coords = $this->getCropCoordsFromForm($form_avatar);

                    $user->setAvatar($this->get('tool.avatar')->getCroppedImage($avatar, $coords));

                    $this->flush();

                    // $this->get('rpe.search.index.factory')->update($user);

                    if (!$request->isXmlHttpRequest()) {
                        return $this->redirect($this->generateUrl('profil', array('id' => $this->get('rpe.hashid')->encode($id))));
                    }

                    return new Response();
                }
            }
        }

        /* CONFIDENTIALS */
        $showFriends                 = $this->getConfidentiality(User::META_CONFIDENTIAL_VIEW_MY_RELATIONS, $user, $profil);
        $showGroups                  = $this->getConfidentiality(User::META_CONFIDENTIAL_VIEW_MY_JOINED_GROUPS, $user, $profil);
        $showFormationAndExperiences = $this->getConfidentiality(User::META_CONFIDENTIAL_VIEW_MY_FORMATION_AND_EXPERIENCES, $user, $profil);
        $showInformations            = $this->getConfidentiality(User::META_CONFIDENTIAL_VIEW_MY_INFORMATIONS, $user, $profil);
        $showInterests               = $this->getConfidentiality(User::META_CONFIDENTIAL_VIEW_MY_INTERESTS, $user, $profil);

        /* LOGS */
        $this->get('rpe.logs')->create($user, Log::TYPE_SEE_PROFIL, $user, $profil);

        return $this->render('pum://page/user/profil.html.twig', array(
            'form'                         => (null === $form) ? $form : $form->createView(),
            'blog_form'                    => (null === $blog_form) ? $blog_form : $blog_form->createView(),
            'form_cover'                   => (null === $form_cover) ? $form_cover : $form_cover->createView(),
            'form_avatar'                  => (null === $form_avatar) ? $form_avatar : $form_avatar->createView(),
            'canPost'                      => $canPost,
            'profil'                       => $profil,
            'profil_is_current'            => $profilIsCurrent,
            'relation_detail'              => $relation_detail,
            'common_relation'              => $commonRelation,
            'groups'                       => $showGroups ? $this->get('rpe.object.fetcher')->getProfilGroups($profil, $user, false, 8, null, $select = 'id, name, picture_id, picture_mime') : array(),
            'countGroups'                  => $showGroups ? $this->get('rpe.object.fetcher')->getProfilGroups($profil, $user, false, null, null, $select = 'count') : 0,
            'publications'                 => $this->get('rpe.object.fetcher')->getProfilResources($profil, $user, false, 4, null, $select='id, name, coverimage_id, file_id, coverimage_mime, file_mime', true),
            'countPublications'            => $this->get('rpe.object.fetcher')->getProfilResources($profil, $user, false, null, null, $select = 'count'),
            'showFriends'                  => $showFriends,
            'showGroups'                   => $showGroups,
            'showFormationAndExperiences'  => $showFormationAndExperiences,
            'showInformations'             => $showInformations,
            'showInterests'                => $showInterests,
        ));
    }

    
    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $profilid    The profil user id
     * @param  string  $page        The page number
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/ajax-profil-notifications/{page}/{profilid}", name="ajax_profil_notificationslist", defaults={"_project"="rpe", "page"="1", "profilid"=null})
     */
    public function profilNotificationsListAction(Request $request, $page, $profilid)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
    
        $user = $this->getUser();
        if (!is_null($profilid) && $profilid != $user->getId()) {
            if (null === $profil = $this->getRepository('user')->find($profilid)) {
                throw new \RuntimeException('Unknow user');
            }
        } else {
            $profil = $user;
        }
    
        // Page
        $byPage       = 10;
        $notifications = $this->getRepository('notification')->getAllNotifications($user, true);
    
        $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($notifications, true, false);
        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage((int)$page);
        
        return $this->render('pum://page/user/ajax-profil_notificationslist.html.twig', array(
            'notifications' => $pager,
            'profil'       => $profil->getId()
        ));
    }
    
    
    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $profilid    The profil user id
     * @param  string  $page        The page number
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/ajax-profil-publications/{page}/{profilid}", name="ajax_profil_publicationslist", defaults={"_project"="rpe", "page"="1", "profilid"=null})
     */
    public function profilPublicationsListAction(Request $request, $page, $profilid)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        if (!is_null($profilid) && $profilid != $user->getId()) {
            if (null === $profil = $this->getRepository('user')->find($profilid)) {
                throw new \RuntimeException('Unknow user');
            }
        } else {
            $profil = $user;
        }

        // Page
        $byPage       = 10;
        $publications = $this->getRepository('post')->getProfilPublications($profil, $this->getRepository('group')->getIdentityAcceptedGroups($user), $user, false);
        $notices       = $this->getRepository('external_notice')->getSharedNotices($profil);

        $arr = array_merge($notices, $publications);
        usort($arr, array($this, 'mergeAndSortArray'));

        // $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($publications, true, false);
        $pager = new ArrayAdapter($arr);
        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage((int)$page);

        return $this->render('pum://page/user/ajax-profil_publicationslist.html.twig', array(
            'publications' => $pager,
            'profil'       => $profil->getId()
        ));
    }

    /**
     * @access private
     * @param  User  $a     A user object
     * @param  User  $b     A user object
     * 
     * @return int  Timestamps diff between 2 user create date
     */
    private function mergeAndSortArray($a, $b)
    {
            $ad = strtotime($a['createDate']->format('Y-m-d H:i:s'));
            $bd = strtotime($b['createDate']->format('Y-m-d H:i:s'));
            return ($bd-$ad);
        }

    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/edit-profil", name="edit_profil", defaults={"_project"="rpe"})
     */
    public function editProfilAction(Request $request)
    {
        return $this->profilAction($request);
    }

    /**
     * @access public
     * @param  string $relationId     The user id who ask for relation
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/ask_relation/{relationId}", name="ask_relation", defaults={"_project"="rpe"})
     */
    public function askRelationAction($relationId)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $result = false;
        $user = $this->getUser();

        $userRepository = $this->getRepository('user');
        if (null != ($relation = $userRepository->find($relationId))) {
            #create relationship
            $relationship = $this->createObject('friend');
            $relationship
                ->setUser($user)
                ->setFriend($relation)
                ->setDate(new \Datetime())
                ->setStatus($relationship::STATUS_ON_HOLD)
            ;
            $this->persist($relationship);

            $this->get('rpe.notifications')->wait(Notification::TYPE_RELATION_REQUEST, $user, $user, $relationId);

            $this->flush();

            $result = true;
        }

        $response = new Response(json_encode(array('result' => $result)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /* EDIT PROFIL */

    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/my-account", name="my_account", defaults={"_project"="rpe"})
     */
    public function myAcountAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user      = $this->getUser();
        $form_type = $request->query->get('form', 'basic');

        // treat for ajax service
        if(null !== $request->query->get('_pum_list', null)){
            $form_type = "addition";
        }
        switch ($form_type)
        {
            case 'basic':
                $form_name = "personal_information_basic";
                break;
            case 'addition':
                $form_name = "personal_information_addition";
                break;
        }

        $form = $this->createForm('pum_object', $user, array(
            'form_view'   =>  $this->createFormViewByName('user', $form_name, $update = false),
            'with_submit' => false
        ));
        if($form_type == "basic"){
            $form->add('password_current', 'pum_password', array('label' => 'Votre mot de passe actuel *', 'mapped' => false, 'required' => false))
            ->add('password_new', 'pum_password', array('label' => 'Nouveau mot de passe', 'mapped' => false, 'required' => false))
            ->add('password_confirm', 'pum_password', array('label' => 'Confirmation nouveau mot de passe *', 'mapped' => false, 'required' => false))
            ->add('timezone', 'rpe_timezone', array(
                'data'   => $this->getUserTimezone(),
                'label'  => $this->get('translator')->trans('common.field.fuseau_horaire', array(), 'rpe').' *',
                'mapped' => false
            ))
            ;
        }
        $form = $this->addCroppedDataToForm($form);
        if ($form_type == "addition") {
            if ($user->isInvited()) {
                $form->remove('academy');
                $form->remove('instructedDisciplines');
                $form->remove('teachingLevels');
                $form->remove('interests');
                
                $institutName = $form->get('institutionName');
                $options = $institutName->getConfig()->getOptions();
                $type = $institutName->getConfig()->getType()->getName();
                $options['label'] = $this->get('translator')->trans('register.invited.institution_name.label', array(), 'rpe');
                $form->add('institutionName', $type, $options);
            };
        }

        if ($response = $this->get('pum.form_ajax')->handleForm($form, $request)) {
            return $response;
        }

        $emailProOrigin = $user->getEmailPro();
        $emailPrivateOrigin = $user->getEmailPrivate();
        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            if($form_type != "basic"){
                $avatar = $form->get('avatar')->getData();
                if ($avatar instanceof Media) {
                    $coords = $this->getCropCoordsFromForm($form);

                    $user->setAvatar($this->get('tool.avatar')->getCroppedAvatar($avatar, $coords));
                    $this->get('rpe.search.index.factory')->update($user);
                }
            }
            else{
                $current_pwd = $form['password_current']->getData()['single'];
                $new_pwd = $form['password_new']->getData()['single'];
            }

            $this->addSuccess($this->get('translator')->trans('profil.update_ok', array(), 'rpe'));


            // if change password or change email pro
            if($form_type == "basic") {
                $emailPro = $form['emailPro']->getData();
                $emailPrivate = $form['emailPrivate']->getData();
                $user->setEmailPro($emailProOrigin);
                
                if(!empty($new_pwd) || $emailPro !== $emailProOrigin || $emailPrivate != $emailPrivateOrigin ){
                    $factory = $this->get('security.encoder_factory');

                    if($emailPro !== $emailProOrigin || $emailPrivate != $emailPrivateOrigin){
                        // check if input email not already used
                        $qb = $this->getRepository('user')->createQueryBuilder('u');
                        $searchByEmailPro = $this->getRepository('user')->createQueryBuilder('u')
                            ->select('u.id')
                            ->andWhere('u.id != :userId')
                            ->andWhere('u.emailPro = :emailPro OR u.emailPrivate = :emailPro OR 
                                u.emailPro = :emailPrivate OR u.emailPrivate = :emailPrivate')
                            ->setParameters(array(
                                'emailPro' => $emailPro,
                                'emailPrivate' => $emailPrivate,
                                'userId'   => $user->getId()
                            ))
                            ->getQuery()
                            ->getResult()
                        ;
                        if (!empty($searchByEmailPro)) {
                            $this->get('session')->getFlashBag()->clear();
                            $this->addError($this->get('translator')->trans('profil.email_in_use', array(), 'rpe'));
                            return $this->redirect($this->generateUrl('my_account', array('form' => $form_type)));
                        }

                        // validating user password
                        $check = $factory->getEncoder($user)->isPasswordValid($user->getPassword(), $current_pwd, $user->getSalt());
                        if($check !== true){
                            $this->get('session')->getFlashBag()->clear();
                            $this->addError($this->get('translator')->trans('profil.current_mdp_error', array(), 'rpe'));
                            return $this->redirect($this->generateUrl('my_account', array('form' => $form_type)));
                        }
                        else{
                            $user->setEmailPro($emailPro);
                        }
                    }
                    if(!empty($new_pwd)){
                        $check = $factory->getEncoder($user)->isPasswordValid($user->getPassword(), $current_pwd, $user->getSalt());
                        if($check !== true){
                            $this->get('session')->getFlashBag()->clear();
                            $this->addError($this->get('translator')->trans('profil.current_mdp_error', array(), 'rpe'));
                            return $this->redirect($this->generateUrl('my_account', array('form' => $form_type)));
                        }
                        else{
                            if(!empty($new_pwd)){
                                //set password
                                $user->setPassword($new_pwd, $factory);
                                $this->get('session')->getFlashBag()->clear();
                                $this->addSuccess($this->get('translator')->trans('profil.current_mdp_changed', array(), 'rpe'));
                            }
                        }
                    }
                }
                $this->setUserMeta($user, $user::META_TIMEZONE, $form['timezone']->getData());
                $this->get('session')->set('user.timezone', $form['timezone']->getData());
            }

            $this->persist($user);
            $this->flush();

            return $this->redirect($this->generateUrl('my_account', array('form' => $form_type)));
        }

        $this->getOEM()->refresh($user);

        $verifyUserForm = $this->createNamedForm('verify_user', 'rpe_security_verify_user');

        return $this->render('pum://page/user/edit/my_account.html.twig', array(
            'form'             => $form->createView(),
            'verify_user_form' => $verifyUserForm->createView()
        ));
    }

    /**
     * Verify the user password when modify information
     * 
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/my-account/verify-user", name="my_account_verify_user", defaults={"_project"="rpe"})
     */
    public function myAcountVerifyUserAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $verifyUserForm = $this->createNamedForm('verify_user', 'rpe_security_verify_user');

        if ($request->isMethod('POST') && $verifyUserForm->handleRequest($request)->isValid()) {
            $user     = $this->getUser();
            $factory  = $this->get('security.encoder_factory');
            $password = $verifyUserForm['password']->getData();

            if ($factory->getEncoder($user)->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
                return new Response('OK');
            }
        }
        return new Response('ERROR');
    }

    /**
     * 
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/my-professional-experiences", name="my_professional_experiences", defaults={"_project"="rpe"})
     */
    public function myExperiencesAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user                = $this->getUser();
        $experience_formview = $this->createFormViewByName('experience', 'create', $update = false);
        $form                = $this->createNamedForm('experience', 'pum_object', $this->createObject('experience'), array(
            'form_view' => $experience_formview,
            'with_submit' => false,
        ));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $experience = $form->getData();

            $user->addExperience($experience);
            $experience->setUser($user);

            $this->persist($user, $experience)->flush();

            return $this->redirect($this->generateUrl('my_professional_experiences'));
        }

        $experiences_forms = array();
        
        foreach ($user->getMyExperiences(array('enddate' => 'DESC'), 10) as $k => $_experience) {
            $_form = $this->createNamedForm('formation_'.$k, 'pum_object', $_experience, array(
                'form_view'   =>  $experience_formview,
                'with_submit' => false
            ));
            $experiences_forms[$_experience->getId()] = $_form->createView();

            if ($request->isMethod('POST') && $_form->handleRequest($request)->isValid()) {
                $this->persist($_form->getData())->flush();

                return $this->redirect($this->generateUrl('my_professional_experiences'));
            }
        }

        return $this->render('pum://page/user/edit/my_professional_experiences.html.twig', array(
            'form' => $form->createView(),
            'experiences_forms' => $experiences_forms
        ));
    }

    /**
     * 
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/my-training", name="my_training", defaults={"_project"="rpe"})
     */
    public function myTrainingAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user               = $this->getUser();
        $formation_formview = $this->createFormViewByName('formation', 'create', $update = false);
        $form               = $this->createNamedForm('formation', 'pum_object', $this->createObject('formation'), array(
            'form_view'   =>  $formation_formview,
            'with_submit' => false
        ));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $formation = $form->getData();

            $user->addFormation($formation);
            $formation->setUser($user);

            $this->persist($formation)->flush();

            return $this->redirect($this->generateUrl('my_training'));
        }

        $formations_forms = array();
        foreach ($user->getFormations() as $k => $_formation) {
            $_form = $this->createNamedForm('formation_'.$k, 'pum_object', $_formation, array(
                'form_view'   =>  $formation_formview,
                'with_submit' => false
            ));
            $formations_forms[$_formation->getId()] = $_form->createView();

            if ($request->isMethod('POST') && $_form->handleRequest($request)->isValid()) {
                $this->persist($_form->getData())->flush();

                return $this->redirect($this->generateUrl('my_training'));
            }
        }

        return $this->render('pum://page/user/edit/my_training.html.twig', array(
            'form' => $form->createView(),
            'formations_forms' => $formations_forms
        ));
    }

    /**
     * @access private
     * @param User      $user       The user to operate
     * @param string    $key        Meta key
     * @param string    $value      Meta value
     * @param string    $type       Meta type
     *
     * @return void
     *
     */
    private function registerUserMeta(User $user, $key, $value, $type = 'notifications')
    {
        $user_meta = $user->getMeta($key);

        if (null === $user_meta) {
            $user_meta = $this->createObject('user_meta')
                ->setUser($user)
                ->setType($type)
                ->setMetaKey($key)
            ;

            $user->addUserMeta($user_meta);
        }

        $user_meta->setValue($value);

        $this->persist($user_meta);
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/my-confidential", name="my_confidential", defaults={"_project"="rpe"})
     */
    public function myConfidentialAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        $form = $this->createForm('rpe_my_confidentials', null, array('user' => $user));

        if ($request->isMethod('POST')) {
            if ($form->handleRequest($request)->isValid()) {
                $this->registerUserMeta($user, User::META_CONFIDENTIAL_FIND_SEARCH, $form->get('find_search')->getData());
                $this->registerUserMeta($user, User::META_CONFIDENTIAL_VIEW_MY_PAGE, $form->get('view_my_page')->getData());
                // $this->registerUserMeta($user, User::META_CONFIDENTIAL_VIEW_MY_RESOURCES, $form->get('view_my_resources')->getData());
                $this->registerUserMeta($user, User::META_CONFIDENTIAL_VIEW_MY_RELATIONS, $form->get('view_my_relations')->getData());
                $this->registerUserMeta($user, User::META_CONFIDENTIAL_VIEW_MY_JOINED_GROUPS, $form->get('view_my_joined_groups')->getData());
                $this->registerUserMeta($user, User::META_CONFIDENTIAL_VIEW_MY_FORMATION_AND_EXPERIENCES, $form->get('view_my_formation_and_experiences')->getData());
                $this->registerUserMeta($user, User::META_CONFIDENTIAL_VIEW_MY_INFORMATIONS, $form->get('view_my_informations')->getData());
                $this->registerUserMeta($user, User::META_CONFIDENTIAL_VIEW_MY_INTERESTS, $form->get('view_my_interests')->getData());
                $this->registerUserMeta($user, User::META_CONFIDENTIAL_CONTACT_ME, $form->get('contact_me')->getData());

                $this->persist($user);
                $this->flush();
            }
        }

        return $this->render('pum://page/user/edit/my_confidential.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/my-notifications", name="my_notifications", defaults={"_project"="rpe"})
     */
    public function myNotificationAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        $form = $this->createForm('rpe_my_notifications', null, array('user' => $user));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_ADDRESS_PRO, $form->get('mail_address_pro')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_SOMEONE_PUBLISH, $form->get('mycontent_someone_publish')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_SOMEONE_COMMENT, $form->get('mycontent_someone_comment')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_SOMEONE_RECOMMEND, $form->get('mycontent_someone_recommend')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_SOMEONE_SHARE, $form->get('mycontent_someone_share')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_SOMEONE_PUBLISH_RESOURCE, $form->get('mycontent_someone_publish_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_SOMEONE_COMMENT_RESOURCE, $form->get('mycontent_someone_comment_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_SOMEONE_RECOMMEND_RESOURCE, $form->get('mycontent_someone_recommend_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_SOMEONE_SHARE_RESOURCE, $form->get('mycontent_someone_share_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_COREDACTOR_EDIT_RESOURCE, $form->get('mycontent_coredactor_edit_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_COLLABORATIVE_RESOURCE_ACCESS, $form->get('collaborative_resource_access')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MYCONTENT_SOMEONE_ANSWER, $form->get('mycontent_someone_answer')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_GROUP_SOMEONE_PUBLISH, $form->get('group_someone_publish')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_GROUP_SOMEONE_PUBLISH_MSG, $form->get('group_someone_publish_msg')->getData());

            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_PUBLISH, $form->get('mail_mycontent_someone_publish')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_COMMENT, $form->get('mail_mycontent_someone_comment')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_RECOMMEND, $form->get('mail_mycontent_someone_recommend')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_SHARE, $form->get('mail_mycontent_someone_share')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_PUBLISH_RESOURCE, $form->get('mail_mycontent_someone_publish_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_COMMENT_RESOURCE, $form->get('mail_mycontent_someone_comment_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_RECOMMEND_RESOURCE, $form->get('mail_mycontent_someone_recommend_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_SHARE_RESOURCE, $form->get('mail_mycontent_someone_share_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_COREDACTOR_EDIT_RESOURCE, $form->get('mail_mycontent_coredactor_edit_resource')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_COLLABORATIVE_RESOURCE_ACCESS, $form->get('mail_collaborative_resource_access')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_ANSWER, $form->get('mail_mycontent_someone_answer')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_GROUP_SOMEONE_PUBLISH, $form->get('mail_group_someone_publish')->getData());
            $this->registerUserMeta($user, User::META_NOTIFICATION_MAIL_GROUP_SOMEONE_PUBLISH_MSG, $form->get('mail_group_someone_publish_msg')->getData());

            $this->persist($user);
            $this->flush();
        }

        return $this->render('pum://page/user/edit/my_notifications.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/experience-delete", name="delete_experience", defaults={"_project"="rpe"})
     */
    public function deleteExperienceAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $id = $request->query->get('id', null);
            if (null !== $id) {
                $experience = $this->getRepository('experience')->find($id);
                $user = $this->getUser();

                if (null !== $experience && $user->getExperiences()->contains($experience)) {
                    $user->removeExperience($experience);

                    $this->persist($user);
                    $this->flush();

                    return new Response('OK');
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/formation-delete", name="delete_formation", defaults={"_project"="rpe"})
     */
    public function deleteFormationAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $id = $request->query->get('id', null);
            if (null !== $id) {
                $formation = $this->getRepository('formation')->find($id);
                $user = $this->getUser();

                if (null !== $formation && $user->getFormations()->contains($formation)) {
                    $user->removeFormation($formation);

                    $this->persist($user);
                    $this->flush();

                    return new Response('OK');
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Profil id
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/profil_relationlist/{id}", name="ajax_profil_relationlist", defaults={"_project"="rpe"})
     */
    public function profilRelationListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getRepository('user')->find($id);

        return $this->render('pum://page/user/ajax-profil_relationlist.html.twig', array(
            'profil' => $user,
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Profil id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/profile_infolist/{id}", name="ajax_profil_infolist", defaults={"_project"="rpe"})
     */
    public function profilInfoListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($id != "occupation"){
            $form = $this->createNamedForm('info_form_' . $id, 'collection', null, array(
                'mapped'    => false,
                'attr'        => array('class' => '', 'id' => "info_form_" . $id),
                'type'      => 'number',
                'allow_add' => true,
                'options'   => array(
                    'required'  => false
                )));
        }

        return $this->render('pum://page/user/ajax-profil_infolist.html.twig', array(
            'infoList'  => $this->getInfoList($id),
            'idInfo'    => $id,
            'form'      => isset($form) ? $form->createView() : null
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Profil id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/profil_commonrelationlist/{id}", name="ajax_profil_commonrelationlist", defaults={"_project"="rpe"})
     */
    public function profilCommonRelationListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getRepository('user')->find($id);

        $commonRelations = $this->getRepository('friend')->getCommonFriends($user, $this->getUser(), false);

        return $this->render('pum://page/user/ajax-profil_commonrelationlist.html.twig', array(
            'user'               => $user,
            'commonRelations'    => $commonRelations
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Profil id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/profil_resourcelist/{id}", name="ajax_profil_resourcelist", defaults={"_project"="rpe"})
     */
    public function profilResourceListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $profil = $this->getRepository('user')->find($id);

        return $this->render('pum://page/user/ajax-profil_resourcelist.html.twig', array(
            'profil'    => $profil,
            'resources' => $this->get('rpe.object.fetcher')->getProfilResources($profil, $this->getUser(), false, null, null, $select='id, name, coverimage_id, file_id', true)
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Profil id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/profil_grouplist/{id}", name="ajax_profil_grouplist", defaults={"_project"="rpe"})
     */
    public function profilGroupListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $profil = $this->getRepository('user')->find($id);
        $groups = $this->get('rpe.object.fetcher')->getProfilGroups($profil, $user, false, null, null, $select = 'id, name, picture_id, picture_mime');

        return $this->render('pum://page/user/ajax-profil_grouplist.html.twig', array(
            'profil' => $profil,
            'groups' => $groups
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/my-api-keys", name="my_api_keys", defaults={"_project"="rpe"})
     */
    public function myApiKeysAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $userOauthKeys = $user->getOauthKeys();

        if (!$user->getCanCreateOauthKey()) {
            return $this->render('pum://page/user/edit/my_api_keys.html.twig', array(
                'oauthKeys'  => $userOauthKeys,
                'error'      => 'cannot_create'
            ));
        }

        $oauthKey = $this->createObject('social_oauth_key');

        $form = $this->createForm('pum_object', $oauthKey, array(
            'form_view' =>  $this->createFormViewByName('social_oauth_key', 'create', $update = false),
            'with_submit' => true
        ));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $apiKey = $this->getFreeApiKey();
            $apiSecret = $this->generateApiSecret();

            $oauthKey->setUser($user);
            $oauthKey->setActive(true);
            $oauthKey->setApiKey($apiKey);
            $oauthKey->setApiSecret($apiSecret);

            $this->persist($oauthKey);
            $this->flush();
        }

        return $this->render('pum://page/user/edit/my_api_keys.html.twig', array(
            'form'       => $form->createView(),
            'oauthKeys'  => $userOauthKeys
        ));
    }

    /**
     * Generate api keys
     * 
     * @access private
     * 
     * @return string Api key generated
     */
    private function getFreeApiKey()
    {
        $apiKey = rand(100000000000000, 999999999999999);

        if(null != ($oauthToken = $this->getRepository('social_oauth_key')->findOneByApiKey($apiKey))) {
            return $this->getFreeApiKey();
        }

        return $apiKey;
    }

    /**
     * Generate api secret keys
     * 
     * @access private
     * 
     * @return string Api key generated
     */
    private function generateApiSecret()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        $randomString = '';

        for ($i = 0; $i < 32; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }


    /**
     * Check confidentiality
     * 
     * @access protected
     * @param string  $type     Meta confidentiality key
     * @param User    $user     User object
     * @param User    $profil   Current profil user
     * @return boolean  Return if profil is secret for the current user
     */
    protected function getConfidentiality($type, $user, $profil)
    {
        $result          = false;
        $confidentiality = ($meta = $profil->getMeta($type)) ? $meta->getValue() : 'everybody';

        if ($user !== $profil) {
            switch($confidentiality) {
                case 'everybody':
                    $result = true;
                    break;

                case 'myfriends':
                    if ($user->isFriend($profil)) {
                        $result = true;
                    }
                    break;
            }
        } else {
            $result = true;
        }

        return $result;
    }
}
