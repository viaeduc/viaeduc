<?php

namespace Rpe\PumBundle\Form\Type\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $objectDefinition = $options['objectDefinition'];

        $builder
            ->add('sex', 'choice', array('label' => 'Civilité', 'choices' => $objectDefinition->getField('sex')->getTypeOption('choices')))
            ->add('lastname', 'text', array('label' => 'Nom*'))
            ->add('firstname', 'text', array('label' => 'Prénom*'))
            ->add('occupation', 'text', array('label' => 'Activité professionnelle'))
            ->add('academy', 'choice', array('label' => 'Académie*', 'choices' => $objectDefinition->getField('academy')->getTypeOption('choices')))
            ->add('email_pro', 'text', array('label' => 'Email académique*'))
            ->add('email_pro_confirm', 'text', array('label' => 'Email académique (Confirmation)*', 'mapped' => false))
            ->add('password', 'pum_password', array('label' => 'Mot de passe*'))
            ->add('password_confirm', 'pum_password', array('label' => 'Mot de passe (Confirmation)*', 'mapped' => false))
            ->add('cgu', 'checkbox', array('mapped' => false))
            ->add('charte', 'checkbox', array('mapped' => false))
            ->add('Confirmer', 'submit')
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($options) {
            $user = $event->getData();
            $user
                ->setStatus($user::STATUS_TYPE_AWAITING_CONFIRMATION)
                ->setDate(new \Datetime())
                ->setValidationKey(md5(mt_rand().uniqid().microtime()))
            ;
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'objectDefinition' => null,
        ));
    }

    public function getName()
    {
        return 'rpe_security_register';
    }
}
