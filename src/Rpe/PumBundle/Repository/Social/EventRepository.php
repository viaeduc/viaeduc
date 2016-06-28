<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Friend;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @method array findByYearMonth($year, $month, $mode = null, $groups = false)
 * @method int   getEventsCount($startDate = null, $endDate = null)
 * @method array getRest($limit = 10, $offset = 0, $keyword = null)
 * @method Event|null getOneRest($id)
 * @method \Doctrine\ORM\mixed  getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null)
 * @method string getUserTimezone()
 */
class EventRepository extends ObjectRepository
{
    /**
     * get events
     *
     * @access public
     * @param string    $year      Year
     * @param string    $month     Month
     * @param string    $mode      Mode
     * @param array     $groups    Groups array
     *
     * @return array 
     */
    public function findByYearMonth($year, $month, $mode = null, $groups = false)
    {
        // convert dates of request to the user timezone
        $timezone = $this->getUserTimezone();
        $dateTimezone = new \DateTimeZone($timezone ?: date_default_timezone_get());

        $firstDay = new \DateTime("", $dateTimezone);
        $firstDay->modify(date('Y-m-d 00:00:00', strtotime('first day of '.$year.'-'.$month)));
        $firstDay->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        $lastDay = new \DateTime("", $dateTimezone);
        $lastDay->modify(date('Y-m-d 23:59:59', strtotime('last day of '.$year.'-'.$month)));
        $lastDay->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        
        $qb = $this->createQueryBuilder('e');
        $qb
            ->andWhere('e.startDate >= :first_day AND e.startDate <= :last_day')
            ->orWhere('e.endDate >= :first_day AND e.endDate <= :last_day');
        
        $parameters = array(
            'first_day'  => $firstDay,
            'last_day' => $lastDay
        );
        if($mode != null && in_array($mode, array('public', 'private'))){
            $parameters['mode'] = $mode;
            $qb->andWhere('e.privacy = :mode');
        }

        if(is_array($groups)){
            $qb->andWhere($qb->expr()->in('e.ownerGroup', $groups));
        }
        
        $event = $qb
            ->setParameters($parameters)
            ->getQuery()->getResult();
        
        return $event;
    }

    /**
     * Get count of events
     *
     * @access public
     * @param string $startDate Start date
     * @param string $endDate   End date
     *
     * @return int
     */
    public function getEventsCount($startDate = null, $endDate = null)
    {
        $parameters = array();

        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $users = $this
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->setParameters($parameters)
        ;

        if (null !== $startDate && null !== $endDate) {
            $users->andWhere('e.startDate >= :startDate');
            $users->andWhere('e.endDate <= :endDate');
        } elseif (null !== $startDate) {
            $users->andWhere('e.startDate >= :startDate');
        } elseif (null !== $endDate) {
            $users->andWhere('e.endDate <= :endDate');
        }

        return $users
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
    
    /**
     * Get rest event
     * 
     * @access public
     * @param number $limit
     * @param number $offset
     * @param string $keyword  Keyword for search if exist
     * @return array 
     */
    
    public function getRest($limit = 10, $offset = 0, $keyword = null)
    {
        $parameters = array();
        
        if (null !== $keyword) {
            $parameters['keyword'] = '%'.$keyword.'%';
        }
        
        $qb = $this->createQueryBuilder('u');
        
        /*if (null !== $keyword) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.firstname', ':keyword'),
                    $qb->expr()->like('u.lastname', ':keyword')
                )
            );
        }*/
        
        $qb = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameters($parameters)
            ->getQuery()
            ->getResult()
        ;
        
        return $qb;
    }
    
    /**
     * Get the one rest event by id
     * 
     * @access public
     * @param string    $id     Folder id
     * 
     * @return Event|null 
     */
    public function getOneRest($id)
    {
        $user = $this
            ->createQueryBuilder('u')
            ->andWhere('u.id = :id')
            ->setParameters(array('id' => $id))
            ->getQuery()
            ->getOneOrNullResult()
        ;
        
        return $user;
    }
    
    /**
     * get all, linked objects
     *
     * @access public
     * @param string $link_type         Link type
     * @param string $main_object_id    Name of the object
     * @param string $limit             Limit of result
     * @param string $offset            Start offset for search
     * @param string $keyword           Search keywork if exist
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null)
    {
        if($link_type == 'user_events') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('e')
                ->leftJoin('e.ownerUser', 'u')
                ->andWhere('e.ownerUser = :user')
            ;
            
            if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        } elseif($link_type == 'user_friends_events') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            $parameters['friend_status'] = Friend::STATUS_ACCEPTED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('e')
                ->leftJoin('e.ownerUser', 'u')
                ->leftJoin('u.friends', 'f')
                ->andWhere('f.friend = :user')
                ->andWhere('f.status = :friend_status')
            ;
            
            if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;

            return $qb;
        } elseif($link_type == 'group_events') {
            $parameters = array();
            $parameters['group'] = $main_object_id;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('e')
                ->leftJoin('e.ownerGroup', 'g')
                ->andWhere('e.ownerGroup = :group')
            ;
            
            if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('g.name', ':keyword')
                    )
                );
            }
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        }
    }

    /**
     * getUserTimezone
     *
     * @access public
     *
     * @return string
     */
    public function getUserTimezone()
    {
        $session = new Session();
        return $session->get('user.timezone');
    }
}
