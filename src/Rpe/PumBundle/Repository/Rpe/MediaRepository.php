<?php
namespace Rpe\PumBundle\Repository\Rpe;

use Pum\Core\Object\ObjectRepository;

/**
 * @method array getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null)
 * @method Media getOneRest($id)
 * @method array getRest($limit = 10, $offset = 0, $keyword = null)
 * @method int   getMediasCount($startDate = null, $endDate = null)
 *
 */
class MediaRepository extends ObjectRepository
{
    /**
     * Get count number of medias
     * 
     * @access public
     * @param string $startDate         Start date of count
     * @param string $endDate           End date of count
     *
     * @return \Doctrine\ORM\mixed|int return count number
     */
    public function getMediasCount($startDate = null, $endDate = null)
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
     * Get rest medias
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
     * Get the one rest folder by id
     * 
     * @access public
     * @param string    $id     Folder id
     * @return Media|null 
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
     * Get linked user medias
     *
     * @access public
     * @param string $link_type         Type of link
     * @param string $main_object_id    Id of main object
     * @param number $limit
     * @param number $offset
     * @param string $keyword  Keyword for search if exist
     * @return array
     */
    public function getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null)
    {
        if($link_type == 'user_medias') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('m')
                ->leftJoin('m.user', 'u')
                ->andWhere('m.user = :user')
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
