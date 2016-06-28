<?php

namespace Rpe\PumBundle\Form\Type\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;

class LostPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', 'text', array(
                'attr'       => array('class' => 'text small-input clear-text'),
                'constraints' => array(new Email())
            ))
            ->add('Confirmer', 'submit')
        ;
    }

    public function getName()
    {
        return 'rpe_security_lost_password';
    }
}
