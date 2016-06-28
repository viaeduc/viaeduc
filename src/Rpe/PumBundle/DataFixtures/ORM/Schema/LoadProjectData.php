<?php

namespace Rpe\PumBundle\DataFixtures\ORM\Schema;

use Rpe\PumBundle\DataFixtures\ORM\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Pum\Core\Definition\Project;

class LoadProjectData extends Fixture
{
    public function getOrder()
    {
        return 2; //depend on beam
    }

    public function load(ObjectManager $manager)
    {
        $manager = $this->get('pum');

        $project = new Project();
        $project
            ->setName('rpe')
            ->addBeam($manager->getBeam('cms'))
            ->addBeam($manager->getBeam('social'))
            ->addBeam($manager->getBeam('rpe'))
            ->addBeam($manager->getBeam('external'))
        ;

        $manager->saveProject($project);
    }
}
