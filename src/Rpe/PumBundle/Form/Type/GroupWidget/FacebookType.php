<?php

namespace Rpe\PumBundle\Form\Type\GroupWidget;

use Rpe\PumBundle\Model\Social\GroupWidget;
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
            ->add('type', 'hidden', array('data' => GroupWidget::TYPE_FACEBOOK))
            ->add('name', 'text')
            ->add('content', 'text', array(
                'constraints' => array(
                    new NotBlank(),
                    new Url()
                ),
                'error_bubbling' => true
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
            'pum_object' => 'social_group_widget'
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
        return 'rpe_form_groupwidget_facebook';
    }

}
