<?php
namespace Rpe\PumBundle\Extension\Service;

use Pum\Bundle\CoreBundle\PumContext;

/**
 * @method void create($users, $type, $actor, $target)
 *
 */
class Logs extends ContextFactory
{
    /**
     * Create a Log entry
     * 
     * @access public
     * @param array       $users
     * @param string      $type
     * @param PUM_OBJECT  $actor
     * @param PUM_OBJECT  $target
     * 
     * @return void
     */
    public function create($users, $type, $actor, $target)
    {
        if (!is_array($users)) {
            $users = array($users);
        }

        foreach ($users as $user) {
            $log = $this->createObject('log');
            $log->setUser($user);
            $log->setType($type);
            $log->setDate(new \DateTime());

            $log->setActorType($actor::PUM_OBJECT);
            $log->setActorId($actor->getId());

            $log->setTargetType($target::PUM_OBJECT);
            $log->setTargetId($target->getId());

            $this->persist($log);
        }

        $this->flush();
    }
}
