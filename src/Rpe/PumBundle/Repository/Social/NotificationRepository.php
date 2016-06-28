<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method  \Doctrine\ORM\mixed             getLastNotification($user)
 * @method  \Doctrine\ORM\Query|multitype:  getAllNotifications($user, $returnQuery = false)
 * @method  \Doctrine\ORM\Query|multitype:  getLastNotifications($user, $limit = 20, $offset = 0)
 * @method  \Doctrine\ORM\Query|multitype:  findUnreadNotifications($user)
 * @method  \Doctrine\ORM\Query|multitype:  getCountUnreadNotifications($user, $last_seen_notification_id)
 * @method  \Doctrine\ORM\mixed             getLastNotificationByParams($user, $target_type, $target_id, $type)
 * @method  array                           getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())
 * @method  Notification|null               getOneRest($id)
 * @method  \Doctrine\ORM\mixed             getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())
 * @method  \Doctrine\ORM\mixed             getNotificationsBefore($date = null, $limit = null, $offset = null, $count = false)
 * 
 */
class NotificationRepository extends ObjectRepository
{
    /**
     * Get notif by descend date order
     * 
     * @access public
     * @param User      $user   User object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getLastNotification($user)
    {
        $last = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->orderBy('n.id', 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $last;
    }

    /**
     * Get all notifications
     *  
     * @access public
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getAllNotifications($user, $returnQuery = false)
    {
        $notifs = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->orderBy('n.date', 'DESC')
            ->setParameter('user', $user)
            ->getQuery();
        
        if ($returnQuery) {
            return $notifs;
        }
        
        return $notifs->getResult();
    }

    /**
     * Get last number of notifications
     *
     * @access public
     * @param User      $user   User object
     * @param string $limit             Limit of result
     * @param string $offset            Start offset for search
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getLastNotifications($user, $limit = 20, $offset = 0)
    {
        $last = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->orderBy('n.id', 'DESC')
            ->setParameter('user', $user)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
        ;

        return $last->getResult();
    }

    /**
     * Get all unread notifications
     *
     * @access public
     * @param User      $user   User object
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function findUnreadNotifications($user)
    {
        $unreads = $this->createQueryBuilder('un')
            ->andWhere('un.user = :user')
            ->andWhere('un.treated = :treated')
            ->andWhere(':last_seen_notification_id IS NULL OR un.id > :last_seen_notification_id')
            ->setParameters(array(
                'user'   => $user,
                'treated' => false,
                'last_seen_notification_id' => $user->getMeta($user::META_NOTIFICATION_LAST_VIEW_ID)->getValue()
            ))
            ->addOrderBy('un.id', 'desc')
            ->getQuery()
            ->getResult()
        ;

        return $unreads;
    }

    /**
     * Get count number of unread notifications
     *
     * @param User       $user   User object
     * @param string     $last_seen_notification_id
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getCountUnreadNotifications($user, $last_seen_notification_id)
    {
        $unreads = $this->createQueryBuilder('un')
            ->select('count(un.id)')
            ->andWhere('un.user = :user')
            ->andWhere('un.treated = :treated')
            ->andWhere(':last_seen_notification_id IS NULL OR un.id > :last_seen_notification_id')
            ->setParameters(array(
                'user'   => $user,
                'treated' => false,
                'last_seen_notification_id' => $last_seen_notification_id
            ))
        ;

        return $unreads->getQuery()->getSingleScalarResult();
    }

    /**
     * Get last notification
     * 
     * @param User       $user   User object
     * @param string     $target_type           Notif target type
     * @param string     $target_id             Notif target id
     * @param string     $type                  Notif type
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getLastNotificationByParams($user, $target_type, $target_id, $type)
    {
        $last = $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.targetType = :target_type')
            ->andWhere('n.targetId = :target_id')
            ->andWhere('n.type = :type')
            ->orderBy('n.id', "DESC")
            ->setParameters(array(
                'user'   => $user,
                'target_type' => $target_type,
                'target_id' => $target_id,
                'type' => $type,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $last;
    }
    
    /**
     * Get rest notifications
     *
     * @access public
     * @param number $limit    Limit of items
     * @param number $offset   Start offset of items
     * @param string $keyword  Keyword for search if exist
     * @param array  $restParameters  Other params
     * 
     * @return array
     */
    public function getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        $parameters = array();

        if (null !== $keyword) {
            $parameters['keyword'] = '%'.$keyword.'%';
        }

        $qb = $this->createQueryBuilder('n')
            ->leftJoin('n.user', 'u')
        ;

        if ($restParameters['format'] == 'rss') {
            $qb->select('n.id AS id');
            $qb->addSelect('n.content AS title');
            $qb->addSelect('n.url AS description');
            $qb->addSelect('n.date AS pubDate');
        }

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
    }

    /**
     * Get the one rest folder by id
     * 
     * @access public
     * @param string    $id     Notif id
     * 
     * @return Notification|null 
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
     * @param array  $restParameters  Other parameters
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        if ($link_type == 'user_notifications') {
            $parameters = array();
            $parameters['user'] = $main_object_id;

            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }

            $qb = $this->createQueryBuilder('n')
                ->leftJoin('n.user', 'u')
                ->andWhere('n.user = :user')
            ;

            if ($restParameters['format'] == 'rss') {
                $qb->select('n.id AS id');
                $qb->addSelect('n.content AS title');
                $qb->addSelect('n.url AS description');
                $qb->addSelect('n.date AS pubDate');
            }

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
        }
    }
    
    /**
     * Get notification before a date
     *
     * @param User      $user   User object
     * @param string    $limit             Limit of result
     * @param string    $offset            Start offset for search
     * @param boolean   $count             Count the number or not
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getNotificationsBefore($date = null, $limit = null, $offset = null, $count = false)
    {
        $last = $this->createQueryBuilder('n');
        if ($count === true) {
            $last->select('count(n.id)');
        } else {
            $last->select('n');
        }
        
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $last->where('n.date < :date')
            ->orderBy('n.user', 'DESC')
            ->addOrderBy('n.id', 'DESC')
            ->setParameter('date', $date);
        
        if ($limit !== null) {
            $last->setMaxResults($limit);
        }
        if ($offset !== null) {
            $last->setFirstResult($offset);
        }
        // echo $last = $last->getQuery()->getSQL();die;
        $last = $last->getQuery();
        
        if ($count === true) {
            $last = $last->getSingleScalarResult();
        } else {
            $last = $last->getResult();
        }
        return $last;
    }
    
}
