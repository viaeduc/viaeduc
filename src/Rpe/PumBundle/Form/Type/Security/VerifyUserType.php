<?php

namespace Rpe\PumBundle\Form\Type\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class VerifyUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', 'password', array(
                'attr'  => array('class' => 'text small-input clear-text')
            ))
            ->add('Valider', 'submit')
        ;
    }

    public function getName()
    {
        return 'rpe_security_verify_user';
    }
}
