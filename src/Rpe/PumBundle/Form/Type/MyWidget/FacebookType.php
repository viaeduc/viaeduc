<?php

namespace Rpe\PumBundle\Form\Type\MyWidget;

use Rpe\PumBundle\Model\Social\UserWidget;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class FacebookType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'hidden', array('data' => UserWidget::TYPE_FACEBOOK))
            ->add('name', 'text')
            ->add('content', 'text', array(
                'constraints' => array(
                    new NotBlank(),
                    new Url()
               ),
            ))
            ->add('valider', 'submit')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'pum_object' => 'social_user_widget'
        ));
    }

    public function getParent()
    {
        return 'pum_object';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'rpe_form_widget_facebook';
    }

}
