<?php
namespace Rpe\PumBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Mail online controller
 * 
 * @method Response mailContactEditorAction($contactId, $userId)
 * @method Response mailInvitationGroupAction($invitationId, $groupId) 
 * @method Response mailInvitationBlogAction($invitationId, $blogId)
 * @method Response mailResetPasswordAction($token)
 * @method Response mailNotificationYournewsAction($contactId, $notifId) 
 * @method Response mailRegisterAction($userId) 
 * @method Response mailRespireActivateAction($userId, $validationKey)
 * @method Response mailRespireConfirmeUserAction($userId, $validationKey) 
 * @method Response mailResourcePadAccessAction($postId, $type)
 * 
 */
class MailonlineController extends Controller
{

    /**
     * @access public
     * @param  string  $contactId   The contact id
     * @param  string  $userId      The user id
     *  
     * @return Response A Response instance
     *  
     * @Route(path="/mail-contact-editor/{contactId}/{userId}", name="mail_contact_editor", defaults={"_project"="rpe"})
     */
    public function mailContactEditorAction($contactId, $userId) 
    {
        $user  = $this->getRepository('user')->find($userId);
        $contact = $this->getRepository('contact')->find($contactId);
        
        return $this->render('pum://emails/contact_editor.html.twig', array(
            'contact'        => $contact,
            'user'        => $user
        ));
    }

    /**
     * @access public
     * @param  string  $invitationId   The invitation id
     * @param  string  $groupId        Group id
     *  
     * @return Response A Response instance
     * 
     * @Route(path="/mail-invitation-group/{invitationId}/{groupId}", name="mail_invitation_group", defaults={"_project"="rpe","groupId"=null})
     */
    public function mailInvitationGroupAction($invitationId, $groupId) 
    {
        
        $invitation = $this->getRepository('invitation')->find($invitationId);
        $user = $invitation->getUser();
        $name_template = isset($groupId) ? 'pum://emails/invitation_group.html.twig' : 'pum://emails/invitation.html.twig';
        $group = isset($groupId) ? $this->getRepository('group')->find($groupId) : null;
        
        return $this->render($name_template, array(
            'invitation'        => $invitation,
            'group'             => $group,
            'confirmation_link' => $this->generateUrl('invited_register', array('id' => $invitation->getId(), 'group' => $groupId, 'key' => $user->getValidationKey()), UrlGeneratorInterface::ABSOLUTE_URL)
        ));
    }
    
    /**
     * @access public
     * @param  string  $invitationId   The invitation id
     * @param  string  $blogId         Blog id
     *  
     * @return Response A Response instance
     * 
     * @Route(path="/mail-invitation-blog/{invitationId}/{blogId}", name="mail_invitation_blog", defaults={"_project"="rpe","blogId"=null})
     */
    public function mailInvitationBlogAction($invitationId, $blogId) 
    {
        $invitation = $this->getRepository('invitation')->find($invitationId);
        $user = $invitation->getUser();
        $name_template = isset($blogId) ? 'pum://emails/invitation_blog.html.twig' : 'pum://emails/invitation.html.twig';
        $blog = isset($blogId) ? $this->getRepository('blog')->find($blogId) : null;
    
        return $this->render($name_template, array(
            'invitation'        => $invitation,
            'blog'             => $blog,
            'confirmation_link' => $this->generateUrl('invited_register', array('id' => $invitation->getId(), 'blog' => $blogId, 'key' => $user->getValidationKey()), UrlGeneratorInterface::ABSOLUTE_URL)
        ));
    }

    /**
     * @access public
     * @param  string  $token          Token to set password
     *  
     * @return Response A Response instance
     *  
     * @Route(path="/mail-reset-password/{token}", name="mail_reset_password", defaults={"_project"="rpe"})
     */
    public function mailResetPasswordAction($token) 
    {
        list($userToken, $ttl, $userId) = explode('#_#_#', base64_decode($token));

        if (null !== $user  = $this->getRepository('user')->find($userId)) {
            $resetlink = $this->generateUrl('reset_password', array('token' => $token), UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->render('pum://emails/lost_password.html.twig', array(
                'resetlink' => $resetlink,
                'user'      => $user,
                'token'     => $token
            ));
        }
    }

    /**
     * @access public
     * @param  string  $contactId   The contact id
     * @param  string  $notifId     The notification id
     *  
     * @return Response A Response instance
     *  
     * @Route(path="/mail-notification-yournews/{contactId}/{notifId}", name="mail_notification_yournews", defaults={"_project"="rpe"})
     */
    public function mailNotificationYournewsAction($contactId, $notifId) 
    {
        $notification  = $this->getRepository('notification')->find($notifId);
        if ($notification == null) {
            return $this->redirect($this->generateUrl('home'));
        }
        $type = $notification->getActorType();
        $contact  = $this->getRepository($type)->find($contactId);
        
        return $this->render('pum://emails/your_news.html.twig', array(
            'contact'           => $contact,
            'notification'      => $notification,
        ));
    }
    
    /**
     * @access public
     * @param  string  $userId      The user id
     *  
     * @return Response A Response instance
     *  
     * @Route(path="/mail-register/{userId}", name="mail_register", defaults={"_project"="rpe"})
     */
    public function mailRegisterAction($userId) 
    {
        $user  = $this->getRepository('user')->find($userId);
        $confirmation_link = $this->generateUrl('email_confirmation', array('id' => $userId, 'key' => $user->getValidationKey()), UrlGeneratorInterface::ABSOLUTE_URL);
        
        return $this->render('pum://emails/register.html.twig', array(
            'user'           => $user,
            'confirmation_link'      => $confirmation_link,
        ));
    }
    
    /**
     * @access public
     * @param  string  $userId          The user id
     * @param  string  $validationKey   Validation key of user
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/mail-respire-activate/{userId}/{validationKey}", name="mail_respire_activate", defaults={"_project"="rpe"})
     */
    public function mailRespireActivateAction($userId, $validationKey) 
    {
        $user  = $this->getRepository('user')->find($userId);
        
        $activeLink = $this->generateUrl('register', array('respire_user_id' => $userId, 'respire_key' => $validationKey), UrlGeneratorInterface::ABSOLUTE_URL);
        $inactiveLink = $this->generateUrl('respire_disable', array('id' => $userId, 'key' => $validationKey), UrlGeneratorInterface::ABSOLUTE_URL);
        
        return $this->render('pum://emails/respire_activate_user.html.twig', array(
            'activeLink' => $activeLink,
            'inactiveLink' => $inactiveLink,
            'user'    => $user,
            'baseUrl' => 'http://'.$this->getRequest()->getHost().'/'
        ));
    }
    
    /**
     * @access public
     * @param  string  $userId          The user id
     * @param  string  $validationKey   Validation key of user
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/mail-respire-confirme/{userId}/{validationKey}", name="mail_respire_confirme", defaults={"_project"="rpe"})
     */
    public function mailRespireConfirmeUserAction($userId, $validationKey) 
    {
        $user  = $this->getRepository('user')->find($userId);
        return $this->render('pum://emails/respire_confirme_user.html.twig', array(
            'user'              => $user,
            'confirmation_link' => $this->generateUrl('email_confirmation', array('id' => $userId, 'respire_key' => $validationKey), UrlGeneratorInterface::ABSOLUTE_URL)
        ));
    }
    
    /**
     * 
     * @access public
     * @param  string  $postId          The post id
     * @param  string  $type            Type of post
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/mail-resourcepad-access/{postId}/{type}", name="mail_resource_pad_access", defaults={"_project"="rpe"})
     */
    public function mailResourcePadAccessAction($postId, $type) 
    {
        $post  = $this->getRepository('post')->find($postId);
        return $this->render('pum://emails/etherpad_activation.html.twig', array(
            'type'         => $type,
            'post'         => $post,
            'notification' => $this->createObject('notification')
        ));
    }
}
