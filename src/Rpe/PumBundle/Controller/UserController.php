<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Rpe\PumBundle\Model\Social\Friend;
use Rpe\PumBundle\Model\Social\User;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\UserInGroup;
use Rpe\PumBundle\Model\Social\Notification;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Rpe\PumBundle\Model\Social\UserInBlog;
use Rpe\PumBundle\Model\Rpe\EmailDomain;

/**
 * User controller
 * 
 * @method Response emailConfirmationAction($id, $key)
 * @method Response respireDisableAction($id, $key)
 * @method Response respireDisableSuccessAction()
 * @method Response registerAction(Request $request, $respire_user_id, $respire_key)
 * @method Response sendEnableLinkAction(Request $request, $email)
 * @method Response invitedRegisterAction(Request $request, $id, $key, $group)
 * @method Response registerSuccessAction()
 * @method Response passwordResetAction()
 * @method Response registerMoreInfosAction(Request $request)
 * @method Response pageAction()
 * @method boolean  authorizedEmailDomain($form)
 * 
 */
class UserController extends Controller
{
    /**
     * @access public
     * @param  string  $id     User id
     * @param  string  $key    Confirmation key
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/user/confirmation/{id}/{key}", name="email_confirmation", defaults={"_project"="rpe"})
     */
    public function emailConfirmationAction($id, $key)
    {
        $user = $this->getRepository('user')->find($id);

        if (null !== $user && $user->getValidationKey() == $key && $user->getStatus() != $user::STATUS_TYPE_ACTIVE) {
            $respire = $user->getRespire();
            if($respire !== null && strpos($respire->getStatus(), "transfered") === false){
                return $this->redirect($this->generateUrl('register', array('respire_user_id' => $user->getId(), 'respire_key' => $key)));
            }
            
            $user->setStatus($user::STATUS_TYPE_ACTIVE);
            $user->setValidationKey(md5(mt_rand().uniqid().microtime()));

            $this->persist($user);
            $this->flush();

            // log user
            $token = new UsernamePasswordToken($user, $user->getPassword(), 'front_secured_area', $user->getRoles());

            $this->get('security.context')->setToken($token);
            $this->get('session')->set('_security_front_secured_area', serialize($token));
            
            return $this->redirect($this->generateUrl('register_more_infos'));
        }

        return $this->redirect($this->generateUrl('login'));
    }

    /**
     * @access public
     * @param  string  $id     User id
     * @param  string  $key    Confirmation key
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/user/respire_disable/{id}/{key}", name="respire_disable", defaults={"_project"="rpe"})
     */
    public function respireDisableAction($id, $key)
    {
        $user = $this->getRepository('user')->find($id);

        if (null !== $user && $user->getValidationKey() == $key) {
            $user->setStatus($user::STATUS_TYPE_AWAITING_CONFIRMATION);
            $user->setValidationKey(md5(mt_rand().uniqid().microtime()));

            $rpe_migration = $user->getRespire();
            $rpe_migration->setStatus($rpe_migration->getStatus() . "_disabled");

            $this->persist($user, $rpe_migration);
            $this->flush();

            return $this->redirect($this->generateUrl('respire_disable_success'));
        }
        return new Response('User not found!');
    }

    /**
     * @access public
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/respire_disable_success", name="respire_disable_success", defaults={"_project"="rpe"})
     */
    public function respireDisableSuccessAction()
    {
        return $this->render('pum://page/user/respire_disable_success.html.twig');
    }

    /**
     * @access public
     * @param  Request $request             A request instance
     * @param  string  $respire_user_id     Respire user id if exist
     * @param  string  $respire_key         Respire key if is respire user
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/register/{respire_user_id}/{respire_key}", name="register", defaults={"_project"="rpe","respire_user_id"=null,"respire_key"=null})
     */
    public function registerAction(Request $request, $respire_user_id, $respire_key)
    {
        $user         = $this->createObject('user');
        $exist_user_id = $request->query->get('user_id', null);
        
        // associer sso user if there is sso_info
        if($info_sso = $request->query->get('sso')){
            $info_sso = unserialize(urldecode($info_sso));
            
            if(isset($info_sso['user'])){
                $user->setFirstName($info_sso['firstname']);
                $user->setLastName($info_sso['lastname']);
                $user->setEmailPro($info_sso['mail']);
                $ent = $this->createObject('social_user_ent');
                $ent->setIdSso($info_sso['user']);
                $ent->setProfile($info_sso['profile']);
                if(isset($info_sso['sex'])){
                    switch ($info_sso['sex']){
                        case 'F':
                            $user->setSex('Madame');
                        break;
                        case 'M':
                            $user->setSex('Monsieur');
                        break;
                    }
                }
            }
        }
        
        if (null !== $respire_user_id) {
            if (null !== $user = $this->getRepository('user')->find($respire_user_id)) {
                if ($user->getValidationKey() != $respire_key){
                    return new Response("Your validation key is invalid!");
                }

                if ($user->getStatus() == $user::STATUS_TYPE_ACTIVE){
                    return new Response("Respire user already activated!");
                }

                $oldEmail = $user->getEmailPro();
            } else {
                return new Response("Unknown user!");
            }
        }
        
        if ($exist_user_id !== null) {
            $exist_user = $this->getRepository('user')->find($exist_user_id);
            $user = isset($exist_user) ? $exist_user : $user;
        }
        $form = $this->createNamedForm(null, 'pum_object', $user, array(
            'form_view' => $this->createFormViewByName('user', 'register', $update = false),
            'with_submit' => false
        ));
        $form
            ->add('timezone', 'rpe_timezone', array(
                'data'   => $this->getUserTimezone(),
                'label'  => $this->get('translator')->trans('common.field.fuseau_horaire', array(), 'rpe').'*',
                'mapped' => false
            ))
            ->add('cgu', 'checkbox', array('mapped' => false))
            ->add('charte', 'checkbox', array('mapped' => false))
            ->add('Confirmer', 'submit')
        ;
            
        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            if ($exist_user_id === null 
                    && (null === $respire_user_id || (isset($oldEmail) && $oldEmail != $user->getEmailPro()))) 
            {
                if (null !== $this->getRepository('user')->findOneByEmailPro($user->getEmailPro())) {

                    $form->get('emailPro')->addError(new FormError($this->get('translator')->trans('register.email_already_existed', array(), 'rpe')));

                    return $this->render('pum://page/user/register.html.twig', array(
                        'form' => $form->createView()
                    ));
                }
            }
            
            if (null !== $exist_user_id || null !== $respire_user_id || isset($ent) || $this->authorizedEmailDomain($form)) {
				$colors = $this->get('tool.avatar')->getPaletteColorFromText($user->getFirstname() . ' ' . $user->getLastname(), false, array(5,6));

                $user
                    ->setStatus(User::STATUS_TYPE_AWAITING_CONFIRMATION)
                    ->setType($user::TYPE_COMMON)
                    ->setDate(new \Datetime())
                    ->setValidationKey(md5(mt_rand().uniqid().microtime()))
                    ->setAvatar($this->get('tool.avatar')->getAutoAvatar($user->getFirstname() . ' ' . $user->getLastname(), 300, 300, $colors))
                    ->setCover($this->get('tool.avatar')->getMaskedImage('user', $colors, 837, 400, false));
                
                if(null !== $respire_user_id){
                    // set respire_migration status if from respire
                    $check_user = $user->getRespire();
                    $old_status = $check_user->getStatus();
                    $check_user->setStatus($old_status . "_transfered");
                    $this->persist($check_user);
                }
                $this->setUserMeta($user, User::META_TIMEZONE, $form['timezone']->getData());
                if(isset($ent)){
                    $ent->setUser($user);
                    $user->addEnt($ent);
                    $this->persist($ent, $user)->flush();
                    return $this->redirect($this->generateUrl('email_confirmation', array('id' => $user->getId(), 'key' => $user->getValidationKey())));
                    
                }
                $this->persist($user)->flush();
                
                // mail au respire user
                if ($respire_user_id != null){
                    // Confirmation Email
                    $this->get('rpe.mailer')->send(array(
                        'subject'      => "Confirmation du transfert de votre compte RESPIRE vers Viaéduc",
                        'from'         => $this->getSenderEmail(),
                        'to'           => $user->getEmailPro(),
                        'template' => array(
                            'name'     => 'pum://emails/respire_confirme_user.html.twig',
                            'vars'     => array(
                                'user'              => $user,
                                'confirmation_link' => $this->generateUrl('email_confirmation', array('id' => $user->getId(), 'key' => $user->getValidationKey()), UrlGeneratorInterface::ABSOLUTE_URL)
                            )
                        ),
                        'type'         => 'text/html'
                    ));
                } else {
                    // Confirmation Email
                    $this->get('rpe.mailer')->send(array(
                        'subject'      => $this->get('translator')->trans('register.confirm_email_subject', array(), 'rpe'),
                        'from'         => $this->getSenderEmail(),
                        'to'           => $user->getEmailPro(),
                        'template' => array(
                            'name'     => 'pum://emails/register.html.twig',
                            'vars'     => array(
                                'user'              => $user,
                                'confirmation_link' => $this->generateUrl('email_confirmation', array('id' => $user->getId(), 'key' => $user->getValidationKey()), UrlGeneratorInterface::ABSOLUTE_URL)
                            )
                        ),
                        'type'         => 'text/html'
                    ));
                }
                
                return $this->redirect($this->generateUrl('register_success'));
            }
        }

        return $this->render('pum://page/user/register.html.twig', array(
            'form' => $form->createView()
        ));
    }
    
    /**
     * @access public
     * @param  Request $request             A request instance
     * @param  string  $email               Respire user id if exist
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/resend-enable-link/{email}", name="resend_enable_link", defaults={"_project"="rpe"})
     */
    public function sendEnableLinkAction(Request $request, $email)
    {
        if(null !== $this->getRepository('user')->findOneByEmailPro($email)){
            $user = $this->getRepository('user')->findOneByEmailPro($email);
            
            if ($user->getStatus() == User::STATUS_TYPE_AWAITING_CONFIRMATION) {
                $confirm_link = $this->generateUrl('email_confirmation', array('id' => $user->getId(), 'key' => $user->getValidationKey()), UrlGeneratorInterface::ABSOLUTE_URL);
                $user_sex       = $user->getSex();
                $user_firstname = $user->getFirstName();
                $user_lastname  = $user->getLastName();
                $user_occupation = $user->getOccupation();
                $user_academy   = $user->getAcademy();
                $user_password = $user->getPassword();
                if(empty($user_sex) || empty($user_firstname) || empty($user_lastname) || empty($user_occupation) || empty($user_academy) || empty($user_password)) {
                    $confirm_link = $this->generateUrl('register', array('user_id' => $user->getId()), UrlGeneratorInterface::ABSOLUTE_URL);
                }
            }
            
            $this->get('rpe.mailer')->send(array(
                'subject'      => $this->get('translator')->trans('register.confirm_email_subject', array(), 'rpe'),
                'from'         => $this->getSenderEmail(),
                'to'           => $user->getEmailPro(),
                'template' => array(
                    'name'     => 'pum://emails/register.html.twig',
                    'vars'     => array(
                        'user'              => $user,
                        'confirmation_link' => $confirm_link
                    )
                ),
                'type'         => 'text/html'
            ));
            if($request->isXmlHttpRequest() || true){
                return $this->render('pum://page/user/ajax-resend_enable.html.twig', array(
                    'mail' => $email,
                ));
            }
        }
        return new Response('User not found');
    }
    

    /**
     * @access public
     * @param  Request $request             A request instance
     * @param  string  $id                  User id
     * @param  string  $key                 Validation key of user
     * @param  Group   $group               Group object if exist
     *  
     * @return Response A Response instance
     *  
     * @Route(path="/invited-register/{id}/{key}/{group}", name="invited_register", defaults={"_project"="rpe","group"=null})
     */
    public function invitedRegisterAction(Request $request, $id, $key, $group)
    {
        $user       = $this->getRepository('user')->findOneByValidationKey($key);
        $invitation = $this->getRepository('invitation')->find($id);
        if (null !== $user && null !== $invitation && $user === $invitation->getuser()) {
            if ($user->getStatus() === $user::STATUS_TYPE_AWAITING_CONFIRMATION) {
                // log invited
                $token = new UsernamePasswordToken($user, $user->getPassword(), 'front_secured_area', $user->getRoles());

                $this->get('security.context')->setToken($token);
                $this->get('session')->set('_security_front_secured_area', serialize($token));

                $form = $this->createNamedForm(null, 'pum_object', $user, array(
                    'form_view' => $this->createFormViewByName('user', 'register', $update = false),
                    'with_submit' => false
                ));
                $form->remove('academy');
                $form
                    ->add('timezone', 'rpe_timezone', array(
                        'data'   => $this->getUserTimezone(),
                        'label'  => $this->get('translator')->trans('common.field.fuseau_horaire', array(), 'rpe').'*',
                        'mapped' => false
                    ))
                    ->add('cgu', 'checkbox', array('mapped' => false))
                    ->add('charte', 'checkbox', array('mapped' => false))
                    ->add('Confirmer', 'submit')
                ;
                    
                $emailPro             = $user->getEmailPro();
                $field                = $form->get('emailPro')->getConfig();        // get the field
                $options              = $field->getOptions();                       // get the options
                $type                 = $field->getType()->getName();               // get the name of the type
                $options['read_only'] = true;                                       // change the option
                $form->add('emailPro', $type, $options);                            // replace the field

                if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

                    $colors = $this->get('tool.avatar')->getPaletteColorFromText($user->getFirstname() . ' ' . $user->getLastname(), false, array(5,6));

                    $user
                        ->setEmailPro($emailPro)
                        ->setStatus($user::STATUS_TYPE_ACTIVE)
                        ->setDate(new \Datetime())
                        ->setValidationKey(md5(mt_rand().uniqid().microtime()))
                        ->setAvatar($this->get('tool.avatar')->getAutoAvatar($user->getFirstname() . ' ' . $user->getLastname(), 300, 300, $colors))
                        ->setCover($this->get('tool.avatar')->getMaskedImage('user', $colors, 837, 400, false));
                    ;
                    $invitation->setStatus($invitation::STATUS_CONFIRMED);
                    
                    if($this->authorizedEmailDomain($form)){
                        $user->setType(User::TYPE_COMMON);
                    }

                    // Make them friends
                    $inviteby = $invitation->getInviteby();
                    $friend = $this->createObject('friend')
                        ->setUser($inviteby)
                        ->setFriend($user)
                        ->setStatus(Friend::STATUS_ACCEPTED)
                        ->setDate(new \Datetime())
                    ;
                    $inviteby->addFriend($friend);

                    $inverse_friend = $this->createObject('friend')
                        ->setUser($user)
                        ->setFriend($inviteby)
                        ->setStatus(Friend::STATUS_ACCEPTED)
                        ->setDate(new \Datetime())
                    ;
                    $user->addFriend($inverse_friend);
                    $this->persist($invitation, $user, $inviteby, $friend, $inverse_friend)->flush();
                    
                    // add to group if invited from a group
                    if ($group != null && $group = $this->getRepository('group')->find($group)){
                        
                        $group_tmp_intervenant = explode(',', $this->get('pum.vars')->getValue('group_tmp_intervenant'));
                        if ($group_tmp_intervenant && in_array($group->getId(), $group_tmp_intervenant)) {
                            $user->setType(User::TYPE_COMMON);
                            $this->setUserMeta($user, User::META_TMP_INTERVENANT, 1);
                        }
                        
                        if(null === $this->getRepository('user_in_group')->getUserInGroup($user, $group)) {
                            $user_in_group = $this->createObject('user_in_group')
                                ->setUser($user)
                                ->setStatus(UserInGroup::STATUS_USER)
                                ->setGroup($group)
                                ->setDate(new \DateTime())
                            ;
                            $group->addUser($user_in_group);
                            $user->addGroup($user_in_group);
                    
                            $this->persist($user_in_group, $user)->flush();
                            $this->get('rpe.notifications')->wait(Notification::TYPE_JOIN_GROUP_ACCEPT, $group->getOwner(), $group, $user->getId());
                            $this->get('rpe.search.index.factory')->update($group);
                        }
                    }
                    // add to blog if invited from a blog
                    $id_blog = $request->query->get('blog', null);
                    // add to group if invited from a group
                    if ($id_blog != null && $blog = $this->getRepository('blog')->find($id_blog)){
                        if(null === $this->getRepository('social_user_in_blog')->getUserInBlog($user, $blog)) {
                            $user_in_blog = $this->createObject('social_user_in_blog')
                                ->setUser($user)
                                ->setStatus(UserInBlog::STATUS_USER)
                                ->setBlog($blog)
                                ->setDate(new \DateTime())
                            ;
                            $blog->addUser($user_in_blog);
                            $user->addBlog($user_in_blog);
                            $this->persist($user_in_blog, $user)->flush();
                            $this->get('rpe.search.index.factory')->update($blog);
                        }
                    }
                    return $this->redirect($this->generateUrl('register_more_infos'));
                }

                return $this->render('pum://page/user/register.html.twig', array(
                    'form' => $form->createView()
                ));
            }
        }

        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * @access public
     *  
     * @return Response A Response instance
     *  
     * @Route(path="/register_success", name="register_success", defaults={"_project"="rpe"})
     */
    public function registerSuccessAction()
    {
        return $this->render('pum://page/user/register_success.html.twig');
    }

    /**
     * @access public
     * @return Response A Response instance
     *  
     * @Route(path="/password_reset", name="password-reset", defaults={"_project"="rpe"})
     */
    public function passwordResetAction()
    {
        return $this->render('pum://page/user/edit/password_reset.html.twig');
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/register_more_infos", name="register_more_infos", defaults={"_project"="rpe"})
     */
    public function registerMoreInfosAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        $form = $this->createForm('pum_object', $user, array(
            'form_view' =>  $this->createFormViewByName('user', 'register_more_infos', $update = false),
            'with_submit' => false
        ));
        $form
            ->add('x', 'hidden', array('mapped' => false))
            ->add('y', 'hidden', array('mapped' => false))
            ->add('w', 'hidden', array('mapped' => false))
            ->add('h', 'hidden', array('mapped' => false))
        ;

        
        if ($user->isInvited()) {
            $form->remove('instructedDisciplines');
            $form->remove('teachingLevels');
            
            $institutName = $form->get('institutionName');
            $options = $institutName->getConfig()->getOptions();
            $type = $institutName->getConfig()->getType()->getName();
            $options['label'] = $this->get('translator')->trans('register.invited.institution_name.label', array(), 'rpe');          
            $form->add('institutionName', $type, $options);
        }
        
        if ($response = $this->get('pum.form_ajax')->handleForm($form, $request)) {
            return $response;
        }

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            
            $avatar = $form->get('avatar')->getData();
            if ($avatar instanceof Media) {
                $coords = array(
                    'x' => $form->get('x')->getData(),
                    'y' => $form->get('y')->getData(),
                    'w' => $form->get('w')->getData(),
                    'h' => $form->get('h')->getData()
                );
                $user->setAvatar($this->get('tool.avatar')->getCroppedAvatar($avatar, $coords));
            }

            $this->persist($user);
            $this->flush();
            return $this->redirect($this->generateUrl('home'));
        }

        return $this->render('pum://page/user/register_more_infos.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @access public 
     * @return Response A Response instance
     *  
     * @Route(path="/pages", name="pages", defaults={"_project"="rpe"})
     */
    public function pageAction()
    {
        return $this->render('pum://page/page.html.twig');
    }

    /**
     * Check if the doamin is accepted
     * 
     * @access private
     * @param  Form  $form     The register form object
     * 
     * @return boolean  Whether the domain is valid
     */
    private function authorizedEmailDomain($form)
    {
        $domaines = $this->getRepository('email_domain')->findAll();
        // If email_domain is empty we accept all email domain
        if (empty($domaines)) {
            return true;
        }
        
        foreach ($domaines as $domain) {
            switch ($domain->getType()) {
                case EmailDomain::TYPE_ENTITY:
                    if (trim($form->get('emailPro')->getData()) === $domain->getDomain()) {
                        return true;
                    }
                    break;
                case EmailDomain::TYPE_DOMAIN:
                default:
                    if (false !== strpos($form->get('emailPro')->getData(), $domain->getDomain())) {
                        return true;
                    }
                    break;
            }
        }
        $form->get('emailPro')->addError(new FormError($this->get('translator')->trans('register.email_domain_unautorized', array(), 'rpe')));
        return false;
    }
}
