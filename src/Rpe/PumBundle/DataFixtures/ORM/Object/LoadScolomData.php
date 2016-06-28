<?php

namespace Rpe\PumBundle\DataFixtures\ORM\Object;

use Rpe\PumBundle\DataFixtures\ORM\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadScolomData extends Fixture
{
    public function getOrder()
    {
        return 6; // depends on project
    }

    public function load(ObjectManager $ma)
    {
        $em = $this->get('em_factory')->getManager($this->get('pum'), 'rpe');

        // langue in resource
        foreach ($this->getLangues() as $value) {
            $item = $em->createObject('language')
                ->setName($value['name'])
            ;
            $em->persist($item);
        }
        $em->flush();

        // document type in resource
        foreach ($this->getDocumentType() as $value) {
            $item = $em->createObject('document_type')
                ->setName($value['name'])
            ;
            $em->persist($item);
        }
        $em->flush();

        // disciplines in resource
        // $xml = $this->getDisciplines();
        // foreach ($xml->TERME as $terme) {
        //     foreach ($terme->TS as $value) {
        //         $item = $em->createObject('instructed_discipline')
        //             ->setName((string) $value->TERME->INTITULE)
        //         ;
        //         $em->persist($item);
        //     }
        // }
        $disciplines = array(
            'Agir et s’exprimer avec son corps',
            'Découverte professionnelle',
            'Découvrir le monde : la matière, les objets, le vivant',
            'Découvrir le monde : les formes, les grandeurs, les quantités, les nombres',
            'Découvrir le monde : l’espace, le temps',
            'Économie et gestion',
            'Éducation au développement durable',
            'Éducation aux médias et à l’information, TUIC',
            'Éducation physique et sportive',
            'Enseignement technologique',
            'Enseignements artistiques',
            'Français langue étrangère, seconde et de scolarisation',
            'Français, Lettres',
            'Histoire géographie',
            'Histoire géographie, Éducation civique, ECJS',
            'Instruction civique et morale',
            'Langage oral',
            'Langage écrit',
            'Langues et cultures de l’antiquité',
            'Langues vivantes',
            'Mathématiques',
            'Orientation et découverte professionnelle',
            'Philosophie',
            'Sciences économiques et sociales',
            'Sciences expérimentales et technologies',
            'Sciences physiques et chimiques',
            'Techniques usuelles de l’information et de la communication'
        );

        foreach ($disciplines as $discipline) {
            $disciplineObject = $em->createObject('instructed_discipline')
                        ->setName($discipline);

            $em->persist($disciplineObject);
        }

        $em->flush();

        // relation type in resource
        // $xml = $this->getRelationType();
        // foreach ($xml->TERME as $value) {
        //     $item = $em->createObject('relation_type')
        //         ->setName((string) $value->INTITULE)
        //         ->setDescription((string) $value->NA)
        //     ;
        //     $em->persist($item);
        // }
        // $em->flush();

        // teaching level
        // $xml = $this->getTeachingLevel();
        // foreach ($xml->TERME as $terme) {
        //     foreach ($terme->TS as $value) {
        //         $item = $em->createObject('teaching_level')
        //             ->setName((string) $value->TERME->INTITULE)
        //             ->setDescription(isset($value->TERME->TERME_TE) ? (string) $value->TERME->TERME_TE->INTITULE : (string) $value->TERME->INTITULE)
        //         ;
        //         $em->persist($item);
        //     }
        // }
        $levels = array(
            'collège',
            'collège professionnel',
            'école maternelle',
            'école élémentaire',
            'lycée général et technologique',
            'lycée professionnel',
            'adaptation scolaire et scolarisation des élèves handicapés',
            'enseignement supérieur en lycée',
            'formation professionnelle'
        );

        foreach ($levels as $level) {
            $levelObject = $em->createObject('teaching_level')
                        ->setName($level)
                        ->setDescription($level);

            $em->persist($levelObject);
        }

        $em->flush();
    }

    public function getDocumentType()
    {
        return array(
            array('name' => 'Collection'),
            array('name' => 'Ensemble de données'),
            array('name' => 'Image'),
            array('name' => 'Image en mouvement'),
            array('name' => 'Image en fixe'),
            array('name' => 'Logiciel'),
            array('name' => 'Objet physique'),
            array('name' => 'Ressource interactive'),
            array('name' => 'Service'),
            array('name' => 'Son'),
            array('name' => 'Texte'),
            array('name' => 'Evènement'),
        );
    }

    public function getRelationType()
    {
        // return simplexml_load_string(file_get_contents("http://www.lom-fr.fr/scolomfr/fiches_vocabulaire/scolomfr-voc-009_XML.xml"));
    }

    public function getTeachingLevel()
    {
        // return simplexml_load_string(file_get_contents("http://www.lom-fr.fr/scolomfr/fiches_vocabulaire/scolomfr-voc-022_XML.xml"));
    }

    public function getDisciplines()
    {
        // return simplexml_load_string(file_get_contents("http://www.lom-fr.fr/scolomfr/fiches_vocabulaire/scolomfr-voc-015_XML.xml"));
    }

    public function getLangues()
    {
        return array(
            array('name' => 'français'),
            array('name' => 'allemand'),
            array('name' => 'anglais'),
            array('name' => 'arabe'),
            array('name' => 'chinois'),
            array('name' => 'espagnol'),
            array('name' => 'hindi'),
            array('name' => 'italien'),
            array('name' => 'japonais'),
            array('name' => 'néerlandais'),
            array('name' => 'polonais'),
            array('name' => 'portugais'),
            array('name' => 'roumain'),
            array('name' => 'russe'),
            array('name' => 'vietnamien'),
        );

        //return simplexml_load_string(file_get_contents("http://www.lom-fr.fr/scolomfr/fiches_vocabulaire/scolomfr-voc-001_XML.xml"));
    }
}
