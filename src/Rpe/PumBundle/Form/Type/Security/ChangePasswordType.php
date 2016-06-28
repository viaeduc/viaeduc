<?php

namespace Rpe\PumBundle\Form\Type\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email_private', 'text', array('label' => 'Adresse mail personnelle', 'constraints' => array(new Email())))
            ->add('email_pro', 'text', array('label' => 'Adresse mail Prefessionel', 'constraints' => array(new Email())))
            ->add('password_current', 'pum_password', array('label' => 'Mot de passe actuel (Confirmation)*', 'mapped' => false, 'required' => false))
            ->add('password_new', 'pum_password', array('label' => 'Nouveau mot de passe*', 'mapped' => false, 'required' => false))
            ->add('password_confirm', 'pum_password', array('label' => 'Nouveau mot de passe (Confirmation)*', 'mapped' => false, 'required' => false))
        ;
    }

    public function getName()
    {
        return 'rpe_security_change_password';
    }
}
