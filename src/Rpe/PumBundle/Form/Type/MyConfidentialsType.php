<?php

namespace Rpe\PumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Rpe\PumBundle\Model\Social\User;

class MyConfidentialsType extends AbstractType
{
    private $context;

    public function __construct(SecurityContextInterface $context)
    {
        $this->context = $context;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $options['user'];

        $choices = array(
            'everybody' => 'Tout le monde',
            'myfriends' => 'Mes relations',
            'me' => 'Moi'
        );
        
        $choices_contact = array(
            'everybody' => 'Tout le monde',
            'myfriends' => 'Mes relations',
            'nobody' => 'Personne'
        );
        
        $findSearch = ($meta = $user->getMeta(User::META_CONFIDENTIAL_FIND_SEARCH)) ? $meta->getValue() : false;
        $viewMyProfil = ($meta = $user->getMeta(User::META_CONFIDENTIAL_VIEW_MY_PAGE)) ? $meta->getValue() : false;
        $viewMyResources = ($meta = $user->getMeta(User::META_CONFIDENTIAL_VIEW_MY_RESOURCES)) ? $meta->getValue() : false;
        $viewMyRelations = ($meta = $user->getMeta(User::META_CONFIDENTIAL_VIEW_MY_RELATIONS)) ? $meta->getValue() : false;
        $viewMyJoinedGroups = ($meta = $user->getMeta(User::META_CONFIDENTIAL_VIEW_MY_JOINED_GROUPS)) ? $meta->getValue() : false;
        $viewMyInformations = ($meta = $user->getMeta(User::META_CONFIDENTIAL_VIEW_MY_INFORMATIONS)) ? $meta->getValue() : false;
        $viewMyFormationAndExperiences = ($meta = $user->getMeta(User::META_CONFIDENTIAL_VIEW_MY_FORMATION_AND_EXPERIENCES)) ? $meta->getValue() : false;
        $viewMyInterests = ($meta = $user->getMeta(User::META_CONFIDENTIAL_VIEW_MY_INTERESTS)) ? $meta->getValue() : false;
        $contactMe = ($meta = $user->getMeta(User::META_CONFIDENTIAL_CONTACT_ME)) ? $meta->getValue() : false;
        
        $builder
            ->add('find_search', 'choice', array('label' => "Qui peut me trouver sur le moteur de recherche ?", 'choices' => $choices, 'mapped' => false, 'required' => false, 'data' => $findSearch))
            ->add('view_my_page', 'choice', array('label' => "Qui peut voir ma page perso ?", 'choices' => $choices, 'mapped' => false, 'required' => false, 'data' => $viewMyProfil))
            // ->add('view_my_resources', 'choice', array('label' => "Qui peut voir mes ressources ?", 'choices' => $choices, 'mapped' => false, 'required' => false, 'data' => $viewMyResources))
            ->add('view_my_relations', 'choice', array('label' => "Qui peut voir mes relations ?", 'choices' => $choices, 'mapped' => false, 'required' => false, 'data' => $viewMyRelations))
            ->add('view_my_joined_groups', 'choice', array('label' => "Qui peut voir les groupes auxquels j'appartiens ?", 'choices' => $choices, 'mapped' => false, 'required' => false, 'data' => $viewMyJoinedGroups))
            ->add('view_my_informations', 'choice', array('label' => "Qui peut voir mes informations personnelles ?", 'choices' => $choices, 'mapped' => false, 'required' => false, 'data' => $viewMyInformations))
            ->add('view_my_formation_and_experiences', 'choice', array('label' => "Qui peut voir mes formations et mes expériences ?", 'choices' => $choices, 'mapped' => false, 'required' => false, 'data' => $viewMyFormationAndExperiences))
            ->add('view_my_interests', 'choice', array('label' => "Qui peut voir mes centres d'intérêt ?", 'choices' => $choices, 'mapped' => false, 'required' => false, 'data' => $viewMyInterests))
            ->add('contact_me', 'choice', array('label' => "Qui peut me contacter ?", 'choices' => $choices_contact, 'mapped' => false, 'required' => false, 'data' => $contactMe))
            
            ->add('submit', 'submit', array('label' => 'Sauvegarder'))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'user' => null
        ));
    }

    public function getName()
    {
        return 'rpe_my_confidentials';
    }
}
