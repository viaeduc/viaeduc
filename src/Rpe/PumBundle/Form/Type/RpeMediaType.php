<?php

namespace Rpe\PumBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;

class RpeMediaType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('media', 'pum_media');
    }

    /**
     * @param OptionsResolverInterface $resolver
    */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'pum_object' => 'media'
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
        return 'rpe_media';
    }

}