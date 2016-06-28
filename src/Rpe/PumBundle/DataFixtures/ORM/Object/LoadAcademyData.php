<?php

namespace Rpe\PumBundle\DataFixtures\ORM\Object;

use Rpe\PumBundle\DataFixtures\ORM\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadAcademyData extends Fixture
{
    public function getOrder()
    {
        return 7; // depends on project
    }

    public function load(ObjectManager $ma)
    {
        $em = $this->get('em_factory')->getManager($this->get('pum'), 'rpe');

        foreach (array('academy') as $entityName) {
            $repositories[$entityName] = $em->getRepository($entityName);
        }

        foreach ($this->getAcademyData() as $data) {
            $em->persist($this->createAcademy($em, $data, $repositories));
        }
        $em->flush();


    }


    public function getAcademyData()
    {
        return array(
            array(
                'name' => "Aix-Marseille",
                'description'  => "",
                'allowed_email' => "@ac-aix-marseille.fr"
            ),
            array(
                'name' => "Amiens",
                'description'  => "",
                'allowed_email' => "@ac-amiens.fr"
            ),
            array(
                'name' => "Besançon",
                'description'  => "",
                'allowed_email' => "@ac-besancon.fr"
            ),
            array(
                'name' => "Bordeaux",
                'description'  => "",
                'allowed_email' => "@ac-bordeaux.fr"
            ),
            array(
                'name' => "Caen",
                'description'  => "",
                'allowed_email' => "@ac-caen.fr"
            ),
            array(
                'name' => "Clermont-Ferrand",
                'description'  => "",
                'allowed_email' => "@ac-clermont.fr"
            ),
            array(
                'name' => "Corse",
                'description'  => "",
                'allowed_email' => "@ac.corse.fr"
            ),
            array(
                'name' => "Créteil",
                'description'  => "",
                'allowed_email' => "@ac-creteil.fr"
            ),
            array(
                'name' => "Dijon",
                'description'  => "",
                'allowed_email' => "@ac-dijon.fr"
            ),
            array(
                'name' => "Grenoble",
                'description'  => "",
                'allowed_email' => "@ac-grenoble.fr"
            ),
            array(
                'name' => "Guadeloupe",
                'description'  => "",
                'allowed_email' => "@ac-guadeloupe.fr"
            ),
            array(
                'name' => "Guyane",
                'description'  => "",
                'allowed_email' => "@ac-guyane.fr"
            ),
            array(
                'name' => "Lille",
                'description'  => "",
                'allowed_email' => "@ac-lille.fr"
            ),
            array(
                'name' => "Limoges",
                'description'  => "",
                'allowed_email' => "@ac-limoge.fr"
            ),
            array(
                'name' => "Lyon",
                'description'  => "",
                'allowed_email' => "@ac-lyon.fr"
            ),
            array(
                'name' => "Martinique",
                'description'  => "",
                'allowed_email' => "@ac-martinique.fr"
            ),
            array(
                'name' => "Montpellier",
                'description'  => "",
                'allowed_email' => "@ac-montpellier.fr"
            ),
            array(
                'name' => "Nancy-Metz",
                'description'  => "",
                'allowed_email' => "@ac-nancy-metz.fr"
            ),
            array(
                'name' => "Nantes",
                'description'  => "",
                'allowed_email' => "@ac-nantes.fr"
            ),
            array(
                'name' => "Nice",
                'description'  => "",
                'allowed_email' => "@ac-nice.fr"
            ),
            array(
                'name' => "Orléans-Tours",
                'description'  => "",
                'allowed_email' => "@ac-orleans-tours.fr"
            ),
            array(
                'name' => "Paris",
                'description'  => "",
                'allowed_email' => "@ac-paris.fr"
            ),
            array(
                'name' => "Poitiers",
                'description'  => "",
                'allowed_email' => "@ac-poitiers.fr"
            ),
            array(
                'name' => "Reims",
                'description'  => "",
                'allowed_email' => "@ac-reims.fr"
            ),
            array(
                'name' => "Rennes",
                'description'  => "",
                'allowed_email' => "@ac-rennes.fr"
            ),
            array(
                'name' => "Rouen",
                'description'  => "",
                'allowed_email' => "@ac-rouen.fr"
            ),
            array(
                'name' => "Strasbourg",
                'description'  => "",
                'allowed_email' => "@ac-strasbourg.fr"
            ),
            array(
                'name' => "Toulouse",
                'description'  => "",
                'allowed_email' => "@ac-toulouse.fr"
            ),
            array(
                'name' => "Versailles",
                'description'  => "",
                'allowed_email' => "@ac-versailles.fr"
            ),
            array(
                'name' => "Fontenay",
                'description'  => "",
                'allowed_email' => "@yopmail.com"
            ),
        );
    }



    public function createAcademy($em, $data, $repositories)
    {
        $item = $em
            ->createObject('academy')
            ->setName($data['name'])
            ->setDescription($data['description'])
            ->setAllowedEmail($data['allowed_email'])
        ;

        $em->persist($item);

        return $item;
    }
}
