<?php

namespace Rpe\PumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class AcademyType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'translation_domain' => 'rpe',
            'empty_value'        => ' ',
            'query_builder'      => function ($repo) {
                return $repo->createQueryBuilder('a')
                    ->orderBy('a.name', 'ASC');
                ;
            }
        ));
    }

    public function getParent()
    {
        return 'pum_object_entity';
    }

    public function getName()
    {
        return 'rpe_academy';
    }
}
