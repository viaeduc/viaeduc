<?php

namespace Rpe\PumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Rpe\PumBundle\Model\Social\Friend;

class MyFriendsType extends AbstractType
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
            'empty_value'        => ' ',
            'query_builder'      => function ($repo) {
                return $repo->getAcceptedFriends($this->context->getToken()->getUser(), false, false, 2000, null, 'id, firstname, lastname', 'ACTIVE', true);
            }
        ));
    }

    public function getParent()
    {
        return 'pum_object_entity';
    }

    public function getName()
    {
        return 'rpe_my_friends';
    }
}
