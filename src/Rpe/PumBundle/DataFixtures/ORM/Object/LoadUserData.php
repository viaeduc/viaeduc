<?php

namespace Rpe\PumBundle\DataFixtures\ORM\Object;

use Doctrine\Common\Persistence\ObjectManager;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Rpe\PumBundle\DataFixtures\ORM\Fixture;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\UserInGroup;

class LoadUserData extends Fixture
{
    protected $userContant = array();

    public function getOrder()
    {
        return 4; // depends on project
    }

    public function load(ObjectManager $manager)
    {
        $em = $this->get('em_factory')->getManager($this->get('pum'), 'rpe');

        foreach (array('user', 'group') as $entityName) {
            $repositories[$entityName] = $em->getRepository($entityName);
        }

        foreach ($this->getUserData() as $data) {
            $em->persist($this->createUser($em, $data));
        }
        $em->flush();

        $reflection = new \ReflectionClass('Rpe\PumBundle\RpePumBundle');
        $imagePath  = dirname($reflection->getFilename()).'/Resources/fixtures';

        if (!is_dir($imagePath)) {
            throw new \RuntimeException('No image path!?');
        }

        foreach ($this->getUserData() as $data) {
            $this->updateUser($em, $data, $repositories, $imagePath);
        }
        $em->flush();
    }

    public function getUserData()
    {
        return array(
            array(
                'email_pro'  => 'admin',
                'firstname'  => 'Albert',
                'lastname'   => 'Dipanda',
                'sex'        => 'Monsieur',
                'birthdate'  => new \Datetime("1965-10-05"),
                'occupation' => 'Directeur de lycée',
                'city'       => 'Paris',
                'status'     => 'ACTIVE',
                'password'   => '__PASSWORD__',
                'formation'  => array(
                    array('name' => 'admin v.2'),
                    array('name' => 'admin v.3'),
                    ),
                'experience' => array(
                    array('name' => 'delia'),
                    ),
                'comment' => array(
                    array('content' => 'Merci pour les infos'),
                    ),
                'meta' => array(
                    array('key' => 'slow', 'type' => 'sort', 'value' => '+100 ban list'),
                    ),
                'notification' => array(
                    array('subject' => 'Meeting des profs de Maths', 'content' => 'Meeting des profs de Mathématiques'),
                    ),
                'discipline' => array(
                    ),
                'interest' => array(
                    /*array('name' => 'Littérature'),
                    array('name' => 'Géométrie'),
                    array('name' => 'Maths'),
                    array('name' => 'Brevet'),
                    array('name' => 'Théorème'),*/
                    ),
                'post' => array(
                    array('name' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) ", 'status' => 'PUBLISHED', 'content' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) ", 'group' => array('name' => 'Pack Primaire', 'description' => 'groupe de réflexion sur le programme de Mathématiques','status' => Group::ACCESS_PUBLIC)),
                    array('name' => "Conseil aux nouveaux professeurs des écoles ", 'status' => 'PUBLISHED', 'content' => "Conseil aux nouveaux professeurs des écoles  ", 'group' => array('name' => 'LE BREVET DES COLLÈGES', 'description' => "Fortes de leur longue histoire, les éditions Belin, fondées en 1777, s'attachent à apporter leur contribution éditoriale dans de nombreux secteurs de la connaissance et du savoir et notamment dans le domaine scolaire. Elles sont aujourd'hui un des derniers éditeurs scolaires indépendant", 'status' => Group::ACCESS_PUBLIC)
                        ),
                    array('name' => "Evaluation des connaissances en chimie...  ", 'status' => 'PUBLISHED', 'content' => " Evaluation des connaissances en chimie...  ", 'group' => array('name' => 'Pack Collège', 'description' => 'évaluation des connaissances en chimie', 'status' => Group::ACCESS_PUBLIC)),
                    array('name' => "proposition de groupes", 'status' => 'DRAFTING', 'content' => " proposition de groupes en chimie...  ", 'group' => array('name' => 'Pack Collège', 'description' => 'évaluation des connaissances en chimie', 'status' => Group::ACCESS_PUBLIC),),
                    ),
                'follower' => array(
                    array('id' => 4),
                    array('id' => 5),
                    array('id' => 6),
                    ),
                'friend' => array(
                    array('friend' => 3, 'status' => 'ON_HOLD'),
                    array('friend' => 4, 'status' => 'ON_HOLD'),
                    array('friend' => 5, 'status' => 'ACCEPTED'),
                    ),
                'avatar' => 'ponaih.jpg',
            ),
            array(
                'email_pro'  => 'inactive',
                'firstname'  => 'Jean',
                'lastname'   => 'Dipanda',
                'sex'        => 'Monsieur',
                'birthdate'  => new \Datetime("1985-06-13"),
                'occupation' => 'Remplaçant inactif',
                'city'       => 'Paris',
                'status'     => 'AWAITING_CONFIRMATION',
                'password'   => '__PASSWORD__',
                'formation'  => array(
                    array('name' => 'Symfony 2'),
                    array('name' => 'RPE v0.1'),
                    ),
                'experience' => array(
                    array('name' => 'Pum'),
                    array('name' => 'symfony 2'),
                    ),
                'comment' => array(
                    array('content' => 'Merci pour les infos'),
                    ),
                'meta' => array(
                    array(
                        'key' => 'slow',
                        'type' => 'sort',
                        'value' => '-100 movement speed'
                    ),
                    ),
                'notification' => array(
                    array(
                        'subject' => 'Meeting des profs de Maths',
                        'content' => 'Meeting des profs de Mathématiques'
                    ),
                ),
                'discipline' => array(),
                'interest' => array(),
                'post' => array(
                    array(
                        'name' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) ",
                        'status' => 'PUBLISHED',
                        'content' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) ",
                        'group' => array(
                            'name' => 'Pack Maternelle',
                            'description' => 'groupe de réflexion sur le programme des jeux',
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                    array(
                        'name' => "Conseil aux nouveaux professeurs des écoles ",
                        'status' => 'PUBLISHED',
                        'content' => "Conseil aux nouveaux professeurs des écoles  ",
                        'group' => array(
                            'name' => "L'avenir des mathématiques en 5ème",
                            'description' => "L'avenir des mathématiques en 5ème",
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                    array(
                        'name' => "Evaluation des connaissances en chimie...  ",
                        'status' => 'PUBLISHED',
                        'content' => " Evaluation des connaissances en chimie...  ",
                        'group' => array(
                            'name' => "L'avenir des mathématiques en 5ème",
                            'description' => "L'avenir des mathématiques en 5ème",
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                    array(
                        'name' => "proposition de groupes",
                        'status' => 'DRAFTING',
                        'content' => " proposition de groupes en chimie...  ",
                        'group' => array(
                            'name' => "L'avenir des mathématiques en 5ème",
                            'description' => "L'avenir des mathématiques en 5ème",
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                ),
                'follower' => array(
                    array('id' => 4),
                    array('id' => 5),
                    array('id' => 6),
                ),
                'friend' => array(),
            ),
            array(
                'email_pro'  => 'pr@les-argonautes.fr',
                'firstname'  => 'Julie',
                'lastname'   => 'Verdier',
                'sex'        => 'Madame',
                'birthdate'  => new \Datetime("1968-01-01"),
                'occupation' => 'Professeur des écoles',
                'city'       => 'Buxerolles',
                'status'     => 'ACTIVE',
                'password'   => '__PASSWORD__',
                'formation'  => array(
                    array(
                        'degree' => 'IUFM d\'Aquitaine',
                        'startdate' => new \Datetime("2001-01-01"),
                        'enddate' => new \Datetime("2003-01-01"),
                    ),
                ),
                'experience' => array(
                    array('company' => 'École élémentaire publique d\'application Carle Vernet ',
                          'title' => 'Directeur',
                          'startdate' => new \Datetime("2006-01-01"),
                          'enddate' => new \Datetime("2009-01-01"),
                          'description' => 'Classe de CE2',
                    ),
                    array('company' => 'École élémentaire publique Jules Michelet',
                          'title' => 'Professeur de Français',
                          'startdate' => new \Datetime("2006-01-01"),
                          'enddate' => new \Datetime("2009-01-01"),
                          'description' => 'Classe de CP',
                    ),
                    array('company' => 'École élémentaire publique d\'application Flornoy',
                          'title' => 'Professeur',
                          'startdate' => new \Datetime("2006-01-01"),
                          'enddate' => new \Datetime("2009-01-01"),
                          'description' => 'Classe de CE1,CE2,CM1-CM2',
                    )
                ),
                'comment' => array(
                    array('content' => 'Bon courage'),
                ),
                'meta' => array(
                    array(
                        'key' => 'slow', 'type' => 'sort',
                        'value' => '-100 movement speed'
                    ),
                ),
                'notification' => array(
                    array(
                        'subject' => 'Meeting des profs de Maths',
                        'content' => 'Meeting des profs de Mathématiques'
                    ),
                ),
                'discipline' => array(),
                'interest' => array(),
                'post' => array(
                    array(
                        'name' => '',
                        'status' => 'PUBLISHED',
                        'content' => 'Nous avons mis en place depuis l’année dernière dans l’école, le livret de compétences simplifié et nous nous sommes servis en ce début d’année, des attestations de palier 1 (CE1) pour vérifier les acquis des élèves. Avez-vous utilisé ces attestations ou le livret simplifié ? Si c’est le cas pouvez-vous nous dire ce que vous en pensez par rapport au livret précédent ? Le nombre d’items à renseigner est-il suffisant par rapport à l’ancien livret ? Y a-t-il des manques par rapport à votre programmation ? Cela vous a –t-il demandé de faire évoluer  vos programmations ? Merci de rejoindre ce groupe pour en parler.',
                        'group' => array(
                            'name' => 'Evaluation scolaire 2.0',
                            'description' => '',
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                    array(
                        'name' => "",
                        'status' => 'PUBLISHED',
                        'content' => 'Nous avons aussi mis en œuvre le livret et cela nous a simplifié la tâche ! Beaucoup moins d’items à remplir et  les domaines sont assez faciles à renseigner. Nous cherchons plutôt comment',
                        'group' => array(
                            'name' => 'Evaluation scolaire 2.0',
                            'description' => '',
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                ),
                'follower' => array(
                    array('id' => 4),
                    array('id' => 5),
                    array('id' => 6),
                ),
                'friend' => array(
                    array(
                        'friend' => 4,
                        'status' => 'ON_HOLD'
                    ),
                    array(
                        'friend' => 5,
                        'status' => 'ACCEPTED'
                    ),
                ),
            ),
            array(
                'email_pro'  => 'alex@sensio.lab',
                'firstname'  => 'Alex',
                'lastname'   => 'Salome',
                'sex'        => 'Monsieur',
                'birthdate'  => new \Datetime("1988-04-10"),
                'occupation' => 'Consultant Symfony 2',
                'city'       => 'Paris',
                'status'     => 'ACTIVE',
                'password'   => '__PASSWORD__',
                'formation'  => array(
                    array('name' => 'Earth 2.0'),
                    array('name' => 'S.A.R.L Life'),
                ),
                'experience' => array(
                    array(
                        'name' => 'symfony 1.4'
                    ),
                ),
                'comment' => array(
                    array('content' => 'No more mana'),
                    array('content' => 'kill it'),
                ),
                'meta' => array(
                    array(
                        'key' => 'stunt',
                        'type' => 'sort',
                        'value' => 'causes 100 damage'
                    ),
                ),
                'notification' => array(
                    array(
                        'subject' => 'Meeting des profs de Maths',
                        'content' => 'Meeting des profs de Mathématiques'
                    ),
                ),
                'discipline' => array(
                    array('name' => 'Histoire'),
                    array('name' => 'Géographie'),
                ),
                'interest' => array(),
                'post' => array(
                    array(
                        'name' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) ",
                        'status' => 'PUBLISHED',
                        'content' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) ",
                        'group' => array(
                            'name' => 'Pack lycée',
                            'description' => 'évaluation des connaissances en chimie',
                            'status' => Group::ACCESS_PUBLIC
                        ),
                    ),
                    array(
                        'name' => "Conseil aux nouveaux professeurs des écoles ",
                        'status' => 'PUBLISHED',
                        'content' => "Conseil aux nouveaux professeurs des écoles ",
                        'group' => array(
                            'name' => 'Pack delia',
                            'description' => 'évaluation des connaissances en dota',
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                    array(
                        'name' => "Evaluation des connaissances en chimie...  ",
                        'status' => 'PUBLISHED',
                        'content' => " Evaluation des connaissances en chimie...  ",
                        'group' => array(
                            'name' => 'Pack Collège',
                            'description' => 'évaluation des connaissances en chimie',
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                    array(
                        'name' => "proposition de groupes",
                        'status' => 'DRAFTING',
                        'content' => " proposition de groupes en chimie...  ",
                        'group' => array(
                            'name' => 'Pack delia',
                            'description' => 'évaluation des connaissances en dota',
                            'status' => Group::ACCESS_PUBLIC,
                        )
                    ),
                ),
                'friend' => array(),
            ),
            array(
                'email_pro'  => 'pa@les-argos.fr',
                'firstname'  => 'Philippe',
                'lastname'   => 'Autier',
                'sex'        => 'Monsieur',
                'birthdate'  => new \Datetime("1978-01-01"),
                'occupation' => 'President établissement',
                'city'       => 'Paris',
                'status'     => 'ACTIVE',
                'password'   => '__PASSWORD__',
                'formation'  => array(
                    array('name' => 'Dota 2'),
                    array('name' => 'Starcraft'),
                    ),
                'experience' => array(
                    ),
                'comment' => array(
                    array('content' => 'No more mana'),
                    array('content' => 'kill it'),
                ),
                'meta' => array(
                    array(
                        'key' => 'stunt',
                        'type' => 'sort',
                        'value' => 'causes 100 damage'
                    ),
                ),
                'notification' => array(
                    array(
                        'subject' => 'Meeting des profs de Maths',
                        'content' => 'Meeting des profs de Mathématiques'
                    ),
                ),
                'discipline' => array(
                    array('name' => 'Mathématiques'),
                ),
                'interest' => array(),
                'post' => array(
                    array(
                        'name' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) ",
                        'status' => 'PUBLISHED',
                        'content' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) ",
                        'group' => array(
                            'name' => 'Pack pelias',
                            'description' => 'évaluation des connaissances en navire',
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                    array(
                        'name' => "Conseil aux nouveaux professeurs des écoles ",
                        'status' => 'PUBLISHED',
                        'content' => "Conseil aux nouveaux professeurs des écoles  ",
                        'group' => array(
                            'name' => 'Pack pelias',
                            'description' => 'évaluation des connaissances en navire',
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                    array(
                        'name' => "Evaluation des connaissances en chimie...  ",
                        'status' => 'PUBLISHED',
                        'content' => " Evaluation des connaissances en chimie...  ",
                        'group' => array(
                            'name' => 'Pack pelias',
                            'description' => 'évaluation des connaissances en navire',
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                    array(
                        'name' => "proposition de groupes",
                        'status' => 'DRAFTING',
                        'content' => " proposition de groupes en chimie...  ",
                        'group' => array(
                            'name' => 'Pack pelias',
                            'description' => 'évaluation des connaissances en navire',
                            'status' => Group::ACCESS_PUBLIC
                        )
                    ),
                ),
                'friend' => array(
                    array('friend' => 1, 'status' => 'ACCEPTED'),
                    array('friend' => 3, 'status' => 'ACCEPTED'),
                ),
            ),
            array(
                'email_pro'  => 'ww@les-argos.fr',
                'firstname'  => 'Jean luc',
                'lastname'   => 'Bernard',
                'sex'        => 'Monsieur',
                'birthdate'  => new \Datetime("1980-12-21"),
                'occupation' => 'Professeur de Collège',
                'city'       => 'Bordeaux',
                'status'     => 'ACTIVE',
                'password'   => '__PASSWORD__',
                'formation'  => array(
                    array('name' => 'Agrégation Université de Bordeaux'),
                    ),
                'experience' => array(
                    ),
                'comment' => array(
                    ),
                'meta' => array(
                    ),
                'notification' => array(
                    array('subject' => 'Meeting des profs de Maths', 'content' => 'Meeting des profs de Mathématiques'),
                    ),
                'discipline' => array(
                    array('name' => 'Mathématiques'),
                    ),
                'interest' => array(
                    ),
                'post' => array(
                    array('name' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) ", 'status' => 'PUBLISHED', 'content' => " Evaluation des connaissances en chimie pour des étudiants entrant à l'université (vidéo) "),
                    array('name' => "Conseil aux nouveaux professeurs des écoles ", 'status' => 'PUBLISHED', 'content' => "Conseil aux nouveaux professeurs des écoles  ", 'group' => array('name' => "L'avenir des mathématiques en 5ème", 'description' => "L'avenir des mathématiques en 5ème",
                            'status' => Group::ACCESS_PUBLIC)),
                    array('name' => "Evaluation des connaissances en chimie...  ", 'status' => 'PUBLISHED', 'content' => " Evaluation des connaissances en chimie...  ", 'group' => array('name' => "L'avenir des mathématiques en 5ème", 'description' => "L'avenir des mathématiques en 5ème",
                            'status' => Group::ACCESS_PUBLIC)),
                    array('name' => "proposition de groupes", 'status' => 'DRAFTING', 'content' => " proposition de groupes en chimie...  ", 'group' => array('name' => "L'avenir des mathématiques en 5ème", 'description' => "L'avenir des mathématiques en 5ème",
                            'status' => Group::ACCESS_PUBLIC)),
                    ),
                'friend' => array(
                    array('friend' => 1, 'status' => 'ON_HOLD'),
                    ),
                'avatar' => 'ponaih.jpg',
            ),
        );
    }

    public function createUser($em, $data)
    {
        $user = $em->createObject('user')
            ->setEmailPro($data['email_pro'])
            ->setFirstname($data['firstname'])
            ->setLastname($data['lastname'])
            ->setSex($data['sex'])
            ->setBirthdate($data['birthdate'])
            //->setOccupation($data['occupation'])
            ->setCity($data['city'])
            ->setStatus($data['status'])
            // ->setGuestCount(5)
            ->setPassword($data['password'], $this->get('security.encoder_factory'))
            ->setDate(new \Datetime())
            ->setValidationKey(md5(mt_rand().uniqid().microtime()))
        ;
        if (!empty($data['formation'])) {
            foreach ($data['formation'] as $formation) {
                $user->addFormation($this->createFormation($em, $formation, $user));
            }
        }
        if (!empty($data['experience'])) {
            foreach ($data['experience'] as $experience) {
                $user->addExperience($this->createExperience($em, $experience, $user));
            }
        }
        if (!empty($data['comment'])) {
            foreach ($data['comment'] as $comment) {
                $user->addComment($this->createComment($em, $comment, $user));
            }
        }
        if (!empty($data['meta'])) {
            foreach ($data['meta'] as $meta) {
                $user->addUserMeta($this->createMeta($em, $meta, $user));
            }
        }
        /*if (!empty($data['notification'])) {
            foreach ($data['notification'] as $data) {
                $user->addNotification($this->createNotification($em, $data, $user));
            }
        }*/
        /*if (!empty($data['group'])) {
            foreach ($data['group'] as $group) {
                $user->addGroup($this->getGroup($em, $group, $repositories['group']));
            }
        }*/

        return $user;
    }

    /**
     * add friend + other relations
     */
    public function updateUser($em, $data, $repositories, $imagePath)
    {
        if (null == ($user = $repositories['user']->findOneByEmailPro($data['email_pro']))) {
            throw new \RuntimeException('Failed miserably fetching user by email_pro.');
        }

        /*if (!empty($data['discipline'])) {
            foreach ($data['discipline'] as $discipline) {
                $user->addInstructedDiscipline($this->createDiscipline($em, $discipline));
            }
        }*/
        /*if (!empty($data['interest'])) {
            foreach ($data['interest'] as $interest) {
                $user->addInterest($this->createInterest($em, $interest));
            }
        }*/
        if (!empty($data['post'])) {
            foreach ($data['post'] as $post) {
                $user->addPost($this->createPost($em, $post, $user, $repositories['group']));
            }
        }
        if (!empty($data['friend'])) {
            foreach ($data['friend'] as $friend) {
                $user->addFriend($this->createFriendShip($em, $friend, $user, $repositories['user']));
            }
        }
        /*if (!empty($data['follower'])) {
            foreach ($data['follower'] as $follower) {
                $user->addFollower($repositories['user']->find($follower['id']));
            }
        }*/

        // Get Color
        $colors = $this->get('tool.avatar')->getPaletteColorFromText($data['firstname'] . ' ' . $data['lastname'], false, array(5,6));

        // Create Avatar
        $user->setAvatar($this->get('tool.avatar')->getAutoAvatar($data['firstname'] . ' ' . $data['lastname'], 300, 300, $colors));

        // Create Cover
        $user->setCover($this->get('tool.avatar')->getMaskedImage('user', $colors, 837, 400, false));

        if ($imagePath) {
            if (empty($data['avatar'])) {
            }
        } elseif (!empty($data['avatar'])) {
            throw new \RuntimeException('Data contains an avatar, but I miss an image path.');
        }
        return true;
    }

    public function createFormation($em, $data, $user)
    {
        $item = $em
            ->createObject('formation')
            ->setUser($user)
        ;
        if (isset($data['name'])) {
            $item->setName($data['name']);
        }
        if (isset($data['degree'])) {
            $item->setDegree($data['degree']);
        }
        if (isset($data['startdate'])) {
            $item->setStartdate($data['startdate']);
        }
        if (isset($data['enddate'])) {
            $item->setEnddate($data['enddate']);
        }

        $em->persist($item);

        return $item;
    }

    public function createExperience($em, $data, $user)
    {
        $item = $em
            ->createObject('experience')
            ->setUser($user)
        ;
        if (isset($data['title'])) {
            $item->setTitle($data['title']);
        }
        if (isset($data['company'])) {
            $item->setCompany($data['company']);
        }
        if (isset($data['startdate'])) {
            $item->setStartdate($data['startdate']);
        }
        if (isset($data['enddate'])) {
            $item->setEnddate($data['enddate']);
        }
        if (isset($data['description'])) {
            $item->setDescription($data['description']);
        }

        $em->persist($item);

        return $item;
    }

    public function createComment($em, $data, $user)
    {
        $item = $em
            ->createObject('comment')
            ->setUser($user)
            ->setContent($data['content'])
        ;

        $em->persist($item);

        return $item;
    }

    public function createMeta($em, $data, $user)
    {
        $item = $em
            ->createObject('user_meta')
            ->setUser($user)
            ->setMetaKey(isset($data['key']) ? $data['key'] : null)
            ->setType(isset($data['type']) ? $data['type'] : null)
            ->setValue(isset($data['value']) ? $data['value'] : null)
        ;

        $em->persist($item);

        return $item;
    }

    public function createNotification($em, $data, $user)
    {
        $item = $em
            ->createObject('notification')
            ->setUser($user)
            ->setDate(new \Datetime())
            ->setSubject($data['subject'])
            ->setContent($data['content'])
        ;

        $em->persist($item);

        return $item;
    }

    public function createDiscipline($em, $data)
    {
        $item = $em
            ->createObject('instructed_discipline')
            ->setName($data['name'])
        ;

        $em->persist($item);

        return $item;
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

    public function getGroup($em, $data, $user, $groupRepository)
    {
        if (null == ($group = $groupRepository->findOneByName($data['name']))) {
            $group = $this->createGroup($em, $data, $user);
        }

        return $group;
    }

    public function createGroup($em, $data, $user)
    {
        $group = $em
            ->createObject('group')
        ;

        $item = $group
            ->setName($data['name'])
            ->setDescription($data['description'])
            ->setCreateDate(new \Datetime())
            ->setUpdateDate(new \Datetime())
        ;

        $user_in_group = $em->createObject('user_in_group')
                ->setUser($user)
                ->setStatus(UserInGroup::STATUS_OWNER)
                ->setGroup($group)
                ->setDate(new \DateTime())
            ;

        $color = $this->get('tool.avatar')->getPaletteColorFromText($data['name'], false);
        $item->setPicture($this->get('tool.avatar')->getMaskedImage('users', $color));
        $item->setCover($this->get('tool.avatar')->getMaskedImage('users', $color, 837, 400, false));

        $user->addGroup($user_in_group);


        $em->persist($item, $user_in_group, $user);

        return $item;
    }

    public function createPost($em, $data, $user, $groupRepository)
    {
        $item = $em
            ->createObject('post')
            ->setAuthor($user)
            ->setCreateDate(new \Datetime())
            ->setUpdateDate(new \Datetime())
            ->setName($data['name'])
            ->setContent($data['content'])
            ->setStatus($data['status'])
            ->setResource(true)
            ->setType(2)
        ;
        if (!empty($data['group'])) {
            $item->setPublishedGroup($this->getGroup($em, $data['group'], $user, $groupRepository));
            $item->setType(4);
        }

        $em->persist($item);

        return $item;
    }

    public function createFriendShip($em, $data, $user, $userRepository)
    {
        $item = $em
            ->createObject('friend')
            ->setUser($user)
            ->setFriend($userRepository->find($data['friend']))
            ->setStatus($data['status'])
            ->setDate(new \Datetime())
        ;

        $em->persist($item);

        return $item;
    }

    /**
     * error on creating media
     */
    public function createAvatar($path)
    {
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Path "%s" does not exist or is not a file.', $path));
        }

        $media = new Media();
        $media->setFile(new File($path));

        return $media;
    }
}
