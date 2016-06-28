<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method  \Doctrine\ORM\mixed  getMessagesCount($startDate = null, $endDate = null)
 * @method  array                getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())
 * @method  Message|null         getOneRest($id)
 * @method  \Doctrine\ORM\mixed  getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())
 * 
 */
class MessageRepository extends ObjectRepository
{
    /**
     * Get message count
     * 
     * @access public
     * @param string    $startDate      Start date of creation
     * @param string    $endDate        End date of creation
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getMessagesCount($startDate = null, $endDate = null)
    {
        $parameters = array();

        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $users = $this
            ->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->setParameters($parameters)
        ;

        if (null !== $startDate && null !== $endDate) {
            $users->andWhere('m.date >= :startDate');
            $users->andWhere('m.date <= :endDate');
        } elseif (null !== $startDate) {
            $users->andWhere('m.date >= :startDate');
        } elseif (null !== $endDate) {
            $users->andWhere('m.date <= :endDate');
        }

        return $users
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
    
    /**
     * Get rest folder
     * 
     * @access public
     * @param number $limit
     * @param number $offset
     * @param array  $restParameters  Other parameters
     * 
     * @return array 
     */
    public function getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        $parameters = array();
        
        if (null !== $keyword) {
            $parameters['keyword'] = '%'.$keyword.'%';
        }
        
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.author', 'u')
        ;
        
        if ($restParameters['format'] == 'rss') {
            $qb->select('m.id AS id');
            $qb->addSelect('m.content AS title');
            $qb->addSelect('m.date AS pubDate');
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
     * Get the one rest message by id
     * 
     * @access public
     * @param string    $id     Message id
     * 
     * @return Message|null 
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
     * @param array  $restParameters    Other parameters
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        if($link_type == 'user_messages') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('m')
                ->leftJoin('m.author', 'u')
                ->andWhere('m.author = :user')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('m.id AS id');
                $qb->addSelect('m.content AS title');
                $qb->addSelect('m.date AS pubDate');
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
}
