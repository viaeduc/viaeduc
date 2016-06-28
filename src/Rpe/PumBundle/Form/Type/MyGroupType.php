<?php

namespace Rpe\PumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Rpe\PumBundle\Model\Social\UserInGroup;

class MyGroupType extends AbstractType
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
            'empty_value'        => 'Sélectionner un groupe',
            'property'           => 'indentedName',
            'query_builder'      => function ($repo) {
                return $repo->createQueryBuilder('g')
                    ->select('g')
                    ->addSelect('CONCAT(COALESCE(p.name, g.name), g.name) AS HIDDEN path_name')
                    ->leftJoin('g.parent', 'p')
                    ->leftJoin('g.users', 'uig')
                    ->leftJoin('uig.user', 'u')
                    ->andWhere('u = :me')
                    ->andWhere('uig.status <= :status')
                    ->orderBy('path_name', 'ASC')
                    ->setParameters(array(
                        'me'     =>  $this->context->getToken()->getUser(),
                        'status' => UserInGroup::IN_GROUP
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
        return 'rpe_my_group';
    }
}