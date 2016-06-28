<?php

namespace Rpe\PumBundle\DataFixtures\ORM\Object;

use Rpe\PumBundle\DataFixtures\ORM\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadOccupationData extends Fixture
{
    public function getOrder()
    {
        return 7; // depends on project
    }

    public function load(ObjectManager $ma)
    {
        $em = $this->get('em_factory')->getManager($this->get('pum'), 'rpe');

        foreach ($this->getOccupationData() as $data) {
            $em->persist($this->createOccupation($em, $data));
        }
        $em->flush();


    }


    public function getOccupationData()
    {
        return array(
            array(
                'name' => "Professeur des écoles",
                'description'  => "",
                'group_name' => "Enseignement, éducation et orientation"
            ),
            array(
                'name' => "Professeur certifié",
                'description'  => "",
                'group_name' => "Enseignement, éducation et orientation"
            ),
            array(
                'name' => "Professeur agrégé",
                'description'  => "Professeur de lycée professionnel",
                'group_name' => "Enseignement, éducation et orientation"
            ),
            array(
                'name' => "Professeur d’éducation physique et sportive",
                'description'  => "",
                'group_name' => "Enseignement, éducation et orientation"
            ),
            array(
                'name' => "Maîtres contractuels des établissements privés sous contrat",
                'description'  => "",
                'group_name' => "Enseignement, éducation et orientation"
            ),
            array(
                'name' => "Conseiller principal d’éducation",
                'description'  => "",
                'group_name' => "Enseignement, éducation et orientation"
            ),
            array(
                'name' => "Conseiller d’orientation-psychologique",
                'description'  => "",
                'group_name' => "Enseignement, éducation et orientation"
            ),
            array(
                'name' => "Infirmer(e)",
                'description'  => "",
                'group_name' => "Santé – social"
            ),
            array(
                'name' => "Assistant(e) de service social",
                'description'  => "",
                'group_name' => "Santé – social"
            ),
            array(
                'name' => "Conseiller(e)s techniques de service social",
                'description'  => "",
                'group_name' => "Santé – social"
            ),
            array(
                'name' => "Médecin",
                'description'  => "",
                'group_name' => "Santé – social"
            ),
            array(
                'name' => "Secrétaire administratif de l’éducation nationale et de l’enseignement supérieur",
                'description'  => "",
                'group_name' => "Administration, comptabilité, gestion et finances"
            ),
            array(
                'name' => "Attaché d’administration de l’éducation nationale et de l’enseignement supérieur (A.A.E.N)",
                'description'  => "",
                'group_name' => "Administration, comptabilité, gestion et finances"
            ),
            array(
                'name' => "Adjoint administratif",
                'description'  => "",
                'group_name' => "Administration, comptabilité, gestion et finances"
            ),
            array(
                'name' => "Technicien de l’éducation nationale",
                'description'  => "",
                'group_name' => "Technique"
            ),
            array(
                'name' => "Technicien de laboratoire",
                'description'  => "",
                'group_name' => "Technique"
            ),
            array(
                'name' => "Adjoint technique principal des établissements d’enseignement",
                'description'  => "",
                'group_name' => "Technique"
            ),
            array(
                'name' => "Adjoint technique de laboratoire",
                'description'  => "",
                'group_name' => "Technique"
            ),
            array(
                'name' => "Personnels de direction",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Inspecteur de l’éducation (I.E.N)",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Inspecteur d’académie – Inspecteur pédagogique régional (I.A-I.P.R)",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Administrateur civil",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Conseiller d’administration scolaire et universitaire (CASU)",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Directeur académique des services de l’éducation nationale (DA-SEN)",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Secrétaire général d’académie (S.G.A)",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Administrateur de l’éducation nationale, de l’enseignement supérieur et de la recherche (AENESR)",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Agent comptable",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Secrétaire d’établissement public à caractère administratif",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Inspecteur général de l’éducation",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
            array(
                'name' => "Inspecteur général de l’administration de l’éducation et de la recherche",
                'description'  => "",
                'group_name' => "Encadrement (direction et inspection)"
            ),
        );
    }

    public function createOccupation($em, $data)
    {
        $item = $em
            ->createObject('occupation')
            ->setName($data['name'])
            ->setDescription($data['description'])
            ->setGroupName($data['group_name'])
        ;

        $em->persist($item);

        return $item;
    }
}
