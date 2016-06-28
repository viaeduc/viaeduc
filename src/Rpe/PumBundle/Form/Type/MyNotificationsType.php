<?php

namespace Rpe\PumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Rpe\PumBundle\Model\Social\User;

class MyNotificationsType extends AbstractType
{
    private $context;

    public function __construct(SecurityContextInterface $context)
    {
        $this->context = $context;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $options['user'];
        $email_addr_disable = filter_var($user->getEmailPrivate(), FILTER_VALIDATE_EMAIL) ? false : true; 

        $myEmailAddressPro = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_ADDRESS_PRO)) ? (bool)$meta->getValue() : true;
        $myContentSomeonePublish = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_PUBLISH)) ? (bool)$meta->getValue() : true;
        $myContentSomeoneComment = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_COMMENT)) ? (bool)$meta->getValue() : true;
        $myContentSomeoneRecommend = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_RECOMMEND)) ? (bool)$meta->getValue() : true;
        $myContentSomeoneShare = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_SHARE)) ? (bool)$meta->getValue() : true;
        $myContentSomeonePublishResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_PUBLISH_RESOURCE)) ? (bool)$meta->getValue() : true;
        $myContentSomeoneCommentResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_COMMENT_RESOURCE)) ? (bool)$meta->getValue() : true;
        $myContentSomeoneRecommendResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_RECOMMEND_RESOURCE)) ? (bool)$meta->getValue() : true;
        $myContentSomeoneShareResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_SHARE_RESOURCE)) ? (bool)$meta->getValue() : true;
        $myContentCoRedactorEditResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_COREDACTOR_EDIT_RESOURCE)) ? (bool)$meta->getValue() : true;
        $collaborativeResource = ($meta = $user->getMeta(User::META_NOTIFICATION_COLLABORATIVE_RESOURCE_ACCESS)) ? (bool)$meta->getValue() : true;
        $myContentSomeoneAnswer = ($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_ANSWER)) ? (bool)$meta->getValue() : true;
        $groupSomeonePublish = ($meta = $user->getMeta(User::META_NOTIFICATION_GROUP_SOMEONE_PUBLISH)) ? (bool)$meta->getValue() : true;
        $groupSomeonePublishMsg = ($meta = $user->getMeta(User::META_NOTIFICATION_GROUP_SOMEONE_PUBLISH_MSG)) ? (bool)$meta->getValue() : true;

        $mailMyContentSomeonePublish = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_PUBLISH)) ? (bool)$meta->getValue() : true;
        $mailMyContentSomeoneComment = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_COMMENT)) ? (bool)$meta->getValue() : true;
        $mailMyContentSomeoneRecommend = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_RECOMMEND)) ? (bool)$meta->getValue() : true;
        $mailMyContentSomeoneShare = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_SHARE)) ? (bool)$meta->getValue() : true;
        $mailMyContentSomeonePublishResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_PUBLISH_RESOURCE)) ? (bool)$meta->getValue() : true;
        $mailMyContentSomeoneCommentResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_COMMENT_RESOURCE)) ? (bool)$meta->getValue() : true;
        $mailMyContentSomeoneRecommendResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_RECOMMEND_RESOURCE)) ? (bool)$meta->getValue() : true;
        $mailMyContentSomeoneShareResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_SHARE_RESOURCE)) ? (bool)$meta->getValue() : true;
        $mailMyContentCoRedactorEditResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_COREDACTOR_EDIT_RESOURCE)) ? (bool)$meta->getValue() : true;
        $mailCollaborativeResource = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_COLLABORATIVE_RESOURCE_ACCESS)) ? (bool)$meta->getValue() : true;
        $mailMyContentSomeoneAnswer = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_ANSWER)) ? (bool)$meta->getValue() : true;
        $mailGroupSomeonePublish = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_GROUP_SOMEONE_PUBLISH)) ? (bool)$meta->getValue() : true;
        $mailGroupSomeonePublishMsg = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_GROUP_SOMEONE_PUBLISH_MSG)) ? (bool)$meta->getValue() : true;
        
        $builder
            ->add('mail_address_pro', null, array('label' => "Mail adresse pro", 'mapped' => false, 'required' => false, 'data' => $myEmailAddressPro, 'disabled' => $email_addr_disable))
            ->add('mycontent_someone_publish', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentSomeonePublish))
            ->add('mail_mycontent_someone_publish', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentSomeonePublish))
            ->add('mycontent_someone_comment', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentSomeoneComment))
            ->add('mail_mycontent_someone_comment', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentSomeoneComment))
            ->add('mycontent_someone_recommend', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentSomeoneRecommend))
            ->add('mail_mycontent_someone_recommend', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentSomeoneRecommend))
            ->add('mycontent_someone_share', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentSomeoneShare))
            ->add('mail_mycontent_someone_share', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentSomeoneShare))
            ->add('mycontent_someone_publish_resource', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentSomeonePublishResource))
            ->add('mail_mycontent_someone_publish_resource', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentSomeonePublishResource))
            ->add('mycontent_someone_comment_resource', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentSomeoneCommentResource))
            ->add('mail_mycontent_someone_comment_resource', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentSomeoneCommentResource))
            ->add('mycontent_someone_recommend_resource', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentSomeoneRecommendResource))
            ->add('mail_mycontent_someone_recommend_resource', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentSomeoneRecommendResource))
            ->add('mycontent_someone_share_resource', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentSomeoneShareResource))
            ->add('mail_mycontent_someone_share_resource', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentSomeoneShareResource))
            ->add('mycontent_coredactor_edit_resource', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentCoRedactorEditResource))
            ->add('mail_mycontent_coredactor_edit_resource', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentCoRedactorEditResource))

            ->add('collaborative_resource_access', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $collaborativeResource))
            ->add('mail_collaborative_resource_access', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailCollaborativeResource))

            ->add('mycontent_someone_answer', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $myContentSomeoneAnswer))
            ->add('mail_mycontent_someone_answer', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailMyContentSomeoneAnswer))
            ->add('group_someone_publish', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $groupSomeonePublish))
            ->add('mail_group_someone_publish', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailGroupSomeonePublish))
            ->add('group_someone_publish_msg', 'checkbox', array('label' => "Notification sur Viaéduc", 'mapped' => false, 'required' => false, 'data' => $groupSomeonePublishMsg))
            ->add('mail_group_someone_publish_msg', 'checkbox', array('label' => "Recevoir par e-mail", 'mapped' => false, 'required' => false, 'data' => $mailGroupSomeonePublishMsg))
            ->add('submit', 'submit', array('label' => 'Sauvegarder'))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'user' => null
        ));
    }

    public function getName()
    {
        return 'rpe_my_notifications';
    }
}
