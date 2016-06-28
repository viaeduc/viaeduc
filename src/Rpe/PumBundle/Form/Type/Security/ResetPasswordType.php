<?php

namespace Rpe\PumBundle\Form\Type\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', 'pum_password', array('label' => 'Mot de passe*'))
            ->add('password_confirm', 'pum_password', array('label' => 'Mot de passe (Confirmation)*', 'mapped' => false))

            ->add('Confirmer', 'submit')
        ;
    }

    public function getName()
    {
        return 'rpe_security_reset_password';
    }
}
