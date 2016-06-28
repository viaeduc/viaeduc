<?php

namespace Rpe\PumBundle\DataFixtures\ORM\Object;

use Rpe\PumBundle\DataFixtures\ORM\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadDiscussionData extends Fixture
{
    public function getOrder()
    {
        return 5; // depends on project
    }

    public function load(ObjectManager $ma)
    {
        /*$em = $this->get('em_factory')->getManager($this->get('pum'), 'rpe');

        foreach (array('user', 'discussion') as $entityName) {
            $repositories[$entityName] = $em->getRepository($entityName);
        }

        foreach ($this->getDiscussionData() as $data) {
            $em->persist($this->createDiscussion($em, $data, $repositories));
        }
        $em->flush();

        foreach ($this->getMessageData() as $data) {
            $em->persist($this->createMessage($em, $data, $repositories));
        }
        $em->flush();*/
    }

    public function getDiscussionData()
    {
        return array(
            array(
                'owner' => 1,
                'name'  => "Proposition d'un groupe de discussion",
                'users' => array(2,3)
            ),
            array(
                'owner' => 2,
                'name'  => "Proposition d'une loi sur la tenue vestimentaire",
                'users' => array(3,4)
            ),
            array(
                'owner' => 2,
                'name'  => "Article sur le théorème de pythagore",
                'users' => array(3,5,6)
            ),
        );
    }

    public function getMessageData()
    {
        return array(
            array(
                'discussion' => 1,
                'user'       => 2,
                'content'    => "<p>Bonjour,</p><p>Je suis partant pour le groupe de discussion</p>",
            ),
            array(
                'discussion' => 1,
                'user'       => 1,
                'content'    => "<p>Ok,</p><p>Je vais créer le groupe de discussion</p>",
            ),
            array(
                'discussion' => 2,
                'user'       => 3,
                'content'    => "<p>Bonjour,</p><p>Très bonne proposition</p>",
            ),
            array(
                'discussion' => 2,
                'user'       => 4,
                'content'    => "<p>Bonsoir,</p><p>Oui en effet, très bonne proposition</p>",
            ),
            array(
                'discussion' => 3,
                'user'       => 2,
                'content'    => "<p>Bonsoir,</p><p>pythagore?</p>",
            ),
        );
    }

    public function createDiscussion($em, $data, $repositories)
    {
        $item = $em
            ->createObject('discussion')
            ->setName($data['name'])
            ->setOwner($owner = $repositories['user']->find($data['owner']))
            ->setCreateDate(new \Datetime())
            ->setUpdateDate(new \Datetime())
        ;

        $owner->addDiscussion($item);
        if (!empty($data['users'])) {
            foreach ($data['users'] as $user) {
                $repositories['user']->find($user)->addDiscussion($item);
            }
        }

        $em->persist($item);

        return $item;
    }

    public function createMessage($em, $data, $repositories)
    {
        $item = $em
            ->createObject('message')
            ->setUser($repositories['user']->find($data['user']))
            ->setDiscussion($repositories['discussion']->find($data['discussion']))
            ->setContent($data['content'])
            ->setDate(new \Datetime())
        ;

        $em->persist($item);

        return $item;
    }
}
