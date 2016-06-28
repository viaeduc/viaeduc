<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Friend;
use Rpe\PumBundle\Model\Social\UserInEvent;

/**
 * @method UserInEvent|null     findByEventAndUser($event, $user, $status = false)
 * @method array                findByEventAndStatus($event, $status = false)
 * @method array                findEventsByUser($event, $user, $status = false)
 * @method array                findByEventAndUserRelationsAndStatus($event, $user, $status = false)
 */
class UserInEventRepository extends ObjectRepository
{
    /**
     * get one user in event
     * 
     * @access public
     * @param Event     $event  Event object
     * @param User      $user   User object
     * @param string    $status Status of user in event
     * 
     * @return UserInEvent|null
     */
    public function  findByEventAndUser($event, $user, $status = false)
    {
        $parameters = array(
            'event'  => $event,
            'user' => $user
        );

        $userInEvent = $this
            ->createQueryBuilder('uie')
            ->andWhere('uie.event = :event')
            ->andWhere('uie.user = :user')
        ;

        if ($status) {
            $userInEvent = $userInEvent->andWhere('uie.status = :status');

            $parameters['status'] = $status;
        }

        $userInEvent = $userInEvent->setParameters($parameters)
        ->getQuery()
        ->getOneOrNullResult();

        return $userInEvent;
    }

    /**
     * Find by event and status
     *
     * @access public
     * @param Event     $event  Event object
     * @param string    $status Status of user in event
     *
     * @return array    Array contains all user in event
     */
    public function findByEventAndStatus($event, $status = false)
    {
        $parameters = array(
            'event'  => $event
        );

        $usersInEvent = $this
            ->createQueryBuilder('uie')
            ->andWhere('uie.event = :event')
        ;

        if ($status) {
            $usersInEvent = $usersInEvent->andWhere('uie.status = :status');

            $parameters['status'] = $status;
        }

        $usersInEvent = $usersInEvent->setParameters($parameters)
        ->getQuery()
        ->getResult();

        return $usersInEvent;
    }

    /**
     * Find user in the event and is friend with the user
     * 
     * @access public
     * @param Event     $event  Event object
     * @param User      $user   User object
     * @param string    $status Status of user in event
     * 
     * @return array    Array contains all user in event
     */
    public function findByEventAndUserRelationsAndStatus($event, $user, $status = false)
    {
        $parameters = array(
            'event'  => $event,
            'user' => $user,
            'status_accepted' => Friend::STATUS_ACCEPTED
        );

        $usersInEvent = $this
            ->createQueryBuilder('uie')
            ->leftJoin('uie.user', 'u')
            ->leftJoin('u.friends', 'uf')
            ->andWhere('uf.friend = :user')
            ->andWhere('uf.status = :status_accepted')
            ->andWhere('uie.event = :event')
        ;

        if ($status) {
            $usersInEvent = $usersInEvent->andWhere('uie.status = :status');

            $parameters['status'] = $status;
        }

        $usersInEvent = $usersInEvent->setParameters($parameters)
        ->getQuery()
        ->getResult();

        return $usersInEvent;
    }

    /**
     * Find events by user
     * 
     * @access public
     * @param Event     $event  Event object
     * @param User      $user   User object
     * @param string    $status Status of user in event
     * 
     * @return array    Array contains all user in event
     */
    public function findEventsByUser($event, $user, $status = false)
    {
        $parameters = array(
            'event'  => $event,
            'user' => $user,
            'status_accepted' => Friend::STATUS_ACCEPTED
        );
    
        $usersInEvent = $this
        ->createQueryBuilder('uie')
        ->leftJoin('uie.user', 'u')
        ->leftJoin('u.friends', 'uf')
        ->andWhere('uf.friend = :user')
        ->andWhere('uf.status = :status_accepted')
        ->andWhere('uie.event = :event')
        ;
    
        if ($status) {
            $usersInEvent = $usersInEvent->andWhere('uie.status = :status');
    
            $parameters['status'] = $status;
        }
    
        $usersInEvent = $usersInEvent->setParameters($parameters)
        ->getQuery()
        ->getResult();
    
        return $usersInEvent;
    }
    
}
