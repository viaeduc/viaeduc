<?php

namespace Rpe\PumBundle\Form\Type\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_username', 'text', array('attr' => array('class' => 'text', 'placeholder' => 'Adresse-email@ac-académie.fr')))
            ->add('_password', 'password', array('attr' => array('class' => 'text', 'placeholder' => 'Mot de passe')))
            ->add('_remember_me', 'checkbox', array('required' => false, 'label' => 'homepage.remember_me'))
            ->add('submit', 'submit', array('label' => 'Connexion'))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'translation_domain' => 'rpe'
        ));
    }

    public function getName()
    {
        return 'rpe_security_login';
    }
}
