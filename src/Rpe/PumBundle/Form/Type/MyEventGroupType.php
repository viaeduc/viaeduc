<?php

namespace Rpe\PumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Rpe\PumBundle\Model\Social\UserInGroup;

class MyEventGroupType extends AbstractType
{
    private $context;

    public function __construct(SecurityContextInterface $context)
    {
        $this->context = $context;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'translation_domain' => 'rpe',
            'empty_value'        => 'Dans mon agenda',
            'query_builder'      => function ($repo) {
                return $repo->createQueryBuilder('g')
                    ->leftJoin('g.groupMetas', 'gm')
                    ->leftJoin('g.users', 'uig')
                    ->leftJoin('uig.user', 'u')
                    ->andWhere('u = :me')
                    ->andWhere('uig.status <= :status')
                    ->andWhere('gm.type = :module_meta_type')
                    ->andWhere('gm.metaKey = :module_meta_key')
                    ->andWhere('gm.value = 1')
                    ->setParameters(array(
                        'me'                =>  $this->context->getToken()->getUser(),
                        'status'            =>  UserInGroup::IN_GROUP,
                        'module_meta_type'  => 'modules',
                        'module_meta_key'   => 'group.modules.enabled.agenda'
                    ))
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
        return 'rpe_my_event_group';
    }
}
