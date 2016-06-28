<?php

namespace Rpe\PumBundle\Form\Type\MyWidget;

use Rpe\PumBundle\Model\Social\UserWidget;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class TwitterType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'hidden', array('data' => UserWidget::TYPE_TWITTER))
            ->add('name', 'text', array())
            ->add('content', 'text', array(
                'constraints' => array(
                    new NotBlank()
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
        return 'rpe_form_widget_twitter';
    }

}
