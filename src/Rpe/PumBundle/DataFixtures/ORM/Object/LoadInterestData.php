<?php

namespace Rpe\PumBundle\DataFixtures\ORM\Object;

use Rpe\PumBundle\DataFixtures\ORM\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadInterestData extends Fixture
{
    public function getOrder()
    {
        return 7; // depends on project
    }

    public function load(ObjectManager $ma)
    {
        $em = $this->get('em_factory')->getManager($this->get('pum'), 'rpe');

        foreach ($this->getInterestData() as $data) {
            $em->persist($this->createInterest($em, $data));
        }
        $em->flush();


    }


    public function getInterestData()
    {
        return array(
            array('name' => "accompagnement éducatif [accompagnement hors temps scolaire]"),
            array('name' => "accompagnement personnalisé [accompagnement pendant le temps scolaire]"),
            array('name' => "adaptation scolaire et scolarisation des élèves à besoins spécifiques"),
            array('name' => "aide individualisée"),
            array('name' => "dispositif école ouverte"),
            array('name' => "dispositifs de soutien et de remédiation scolaire"),
            array('name' => "scolarisation des élèves handicapés"),
            array('name' => "égalité filles-garçons"),
            array('name' => "lutte contre les discriminations"),
            array('name' => "prévention du décrochage scolaire"),
            array('name' => "prévention de l'illettrisme"),
            array('name' => "prévention et lutte contre la violence"),
            array('name' => "accueil des primo-arrivants"),
            array('name' => "CLIN classe d'initiation"),
            array('name' => "CLIS classe d'intégration scolaire"),
            array('name' => "dispositif ECLAIR"),
            array('name' => "dispositifs relais"),
            array('name' => "enseignement français à l'étranger"),
            array('name' => "internats d'excellence"),
            array('name' => "RAR réseau ambition réussite"),
            array('name' => "sections européennes ou internationales"),
            array('name' => "ULIS unité localisée pour l'inclusion scolaire"),
            array('name' => "UPI unité pédagogique d'intégration"),
            array('name' => "apprentissage et alternance"),
            array('name' => "éducation à l'orientation et au choix"),
            array('name' => "formation continue des adultes"),
            array('name' => "ouverture sur le monde des métiers"),
            array('name' => "parcours de découverte des métiers et des formations (PDMF)"),
            array('name' => "relation école-entreprise"),
            array('name' => "stages et tutorat"),
            array('name' => "concours et examens"),
            array('name' => "droits et obligations des personnels"),
            array('name' => "entrer dans le métier d'enseignant"),
            array('name' => "formation initiale des enseignants"),
            array('name' => "formation continue des enseignants"),
            array('name' => "mobilité professionnelle et évolution de carrière"),
            array('name' => "programmes et textes officiels"),
            array('name' => "actions internationales"),
            array('name' => "échanges pédagogiques de classes"),
            array('name' => "partenariats associatifs"),
            array('name' => "partenariats institutionnels"),
            array('name' => "partenariats avec les collectivités"),
            array('name' => "projet inter-établissements"),
            array('name' => "projet inter-établissements"),
            array('name' => "relations extérieures et partenariats internationaux"),
            array('name' => "séjours et classes de découverte"),
            array('name' => "éducation à la citoyenneté, aux valeurs républicaines et aux droits de l'homme"),
            array('name' => "éducation à la santé et à la sexualité"),
            array('name' => "éducation à la sécurité et aux risques majeurs"),
            array('name' => "éducation à la sécurité routière et à la mobilité"),
            array('name' => "éducation au développement durable"),
            array('name' => "éducation au développement et à la solidarité locale et internationale"),
            array('name' => "éducation aux médias"),
            array('name' => "enseignement de la défense et de la sécurité nationale"),
            array('name' => "laïcité et enseignement des faits religieux"),
            array('name' => "vie lycéenne"),
            array('name' => "gestion financière et matérielle des établissements"),
            array('name' => "liaison inter-cycles et inter-degrés"),
            array('name' => "organisation pédagogique des établissements d'enseignement"),
            array('name' => "parents d'élèves"),
            array('name' => "pilotage et gestion du système éducatif"),
            array('name' => "pilotage et gestion de l'EPLE"),
            array('name' => "rythmes scolaires"),
            array('name' => "vie scolaire"),
            array('name' => "Brevet, bac"),
            array('name' => "évaluation"),
            array('name' => "projet pluridisciplinaire à caractère professionnel (PPCP)"),
            array('name' => "socle commun des connaissances et compétences"),
            array('name' => "TIPE (travaux d'initiative personnelle encadrés)"),
            array('name' => "TPE (travaux personnels encadrés)"),
            array('name' => "validation des acquis de l'expérience"),
            array('name' => "B2i/C2i"),
            array('name' => "culture numérique et usages citoyens"),
            array('name' => "équipements et supports numériques"),
            array('name' => "enseignement à distance"),
            array('name' => "espaces numériques de travail (ENT)"),
            array('name' => "formation initiale et continue à distance"),
            array('name' => "manuels numériques"),
            array('name' => "médias sociaux"),
            array('name' => "ressources et contenus numériques"),
            array('name' => "usages et pratiques pédagogiques du numérique"),
            array('name' => "culture scientifique et technologique"),
            array('name' => "culture humaniste"),
            array('name' => "éducation à l'image, au cinéma et à l'audiovisuel"),
            array('name' => "éducation artistique et culturelle"),
            array('name' => "mémoire et histoire"),
            array('name' => "projet artistique et culturel (PAC)"),
            array('name' => "pratique sportive"),
            array('name' => "sections sportives scolaires"),
            array('name' => "approches transversales"),
            array('name' => "innovation pédagogique"),
            array('name' => "recherche en pédagogie"),
            array('name' => "sciences de l'éducation"),
            array('name' => "sciences cognitives"),
            array('name' => "veille"),
            array('name' => "bases de données"),
            array('name' => "cinéma"),
            array('name' => "édition scolaire et universitaire"),
            array('name' => "logiciels"),
            array('name' => "médias"),
            array('name' => "presse"),
            array('name' => "radio"),
            array('name' => "télévision"),
            array('name' => "web et forums"),
        );
    }

    public function createInterest($em, $data)
    {
        $item = $em
            ->createObject('interest')
            ->setName($data['name'])
        ;

        $em->persist($item);

        return $item;
    }
}
