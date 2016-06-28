<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Discussion;

/**
 * @method  Dicussion|null      getLastActiveDiscussion($user)
 * @method  \Doctrine\ORM\mixed getDiscussionFromUser($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\mixed getCountUnreadDiscussions($user)
 * @method  \Doctrine\ORM\mixed getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null) *
 * 
 */
class DiscussionRepository extends ObjectRepository
{
    /**
     * Get the last active discuttion
     *
     * @access public
     * @param User      $user   User object
     *
     * @return Dicussion|null
     */
    public function getLastActiveDiscussion($user)
    {
        $last = $this->createQueryBuilder('d')
            ->leftJoin('d.users', 'users')
            ->where('users.user = :user')
            ->orderBy('d.updateDate', "DESC")
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $last;
    }

    /**
     * Get duscussion from user
     *
     * @access public
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\mixed  
     */
    public function getDiscussionFromUser($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('d');

        $discussions = $qb
            ->leftJoin('d.users', 'uid')
            ->andWhere($qb->expr()->eq('uid.user', ':user'))
            ->andWhere($qb->expr()->eq('d.type', ':type'))
            ->setParameters(array(
                'user' => $user,
                'type' => Discussion::TYPE_ACTIV,
            ))
            ->orderBy('d.updateDate', 'DESC')
        ;

        if (null !== $maxResults) {
            $discussions->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $discussions->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $discussions->getQuery();
        }

        return $discussions->getQuery()->getResult();
    }


    /**
     * Get count number of unread discussion
     * 
     * @access public
     * @param User      $user   User object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getCountUnreadDiscussions($user)
    {
        $qb = $this->createQueryBuilder('d');

        $last_seen_discussion_date = $user->getMeta($user::META_MESSAGE_LAST_VIEW_DATE);
        if (null !== $last_seen_discussion_date) {
            $date = new \DateTime();
            $date->setTimestamp($last_seen_discussion_date->getValue());

            $last_seen_discussion_date = $date;
        }

        $discussions = $qb
            ->select('count(d.id)')
            ->join('d.users', 'uid', 'WITH', $qb->expr()->andx($qb->expr()->eq('uid.user', ':user'), $qb->expr()->orX(
                $qb->expr()->gt('d.updateDate', 'uid.viewDate'),
                $qb->expr()->isNull('uid.viewDate')
            )))
            ->andWhere($qb->expr()->eq('d.type', ':type'))
            ->andWhere(':last_seen_discussion_date IS NULL OR d.updateDate > :last_seen_discussion_date')
            ->setParameters(array(
                'user' => $user,
                'type' => Discussion::TYPE_ACTIV,
                'last_seen_discussion_date' => $last_seen_discussion_date
            ))
        ;

        return $discussions->getQuery()->getSingleScalarResult();
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
        if($link_type == 'user_discussions') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('d')
                ->leftJoin('d.users', 'uid')
                ->leftJoin('uid.user', 'u')
                ->andWhere('u.id = :user')
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
        }
    }
}
