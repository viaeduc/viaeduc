<?php
namespace Rpe\PumBundle\Repository\External;

use Pum\Core\Object\ObjectRepository;
use Doctrine\ORM\Query;

/**
 * @method array getSharedNotices($user)
 * @method array getNoticesBySource($source)
 *
 */
class NoticeRepository extends ObjectRepository
{
    /**
     * Get shared notices with the user
     * 
     * @access public
     * @param User $user   User object
     * 
     * @return array  return array containing 
     */
    public function getSharedNotices($user)
    {
        $sharedNotices = $this->createQueryBuilder('n')
                              ->leftJoin('n.targetUser', 'u')
                              ->addSelect('u')
                              ->where('u = :user')
                              ->setParameters(array(
                                  'user' => $user
                              ))
                              ->getQuery()
                              ->getResult(Query::HYDRATE_ARRAY);
                              ;

        return $sharedNotices;
    }

    /**
     * Get notices from specific source
     * 
     * @access public
     * @param string  $source   Source of notice
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param string    $select         Select fields
     * 
     * @return array Array containing notices
     */
    public function getNoticesBySource($source, $maxResults = null, $firstResult = null, $select = null)
    {
        $qb = $this->createQueryBuilder('n');
        if ('count' === $select) {
            $qb->select('count(DISTINCT n.id)');
        } else {
            $qb->select('n.idNoticia, n.idBeebac, n.source');
        }
        
        $qb
           ->andWhere('n.source = :source')
           ->setParameters(array(
                'source'     => $source
            ));
                       
        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }
        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }
        
        if ('count' === $select) {
            
            $qb = $qb->getQuery()
                     ->getSingleScalarResult();
        } else {
            $qb = $qb->getQuery()
                     ->getResult();
        }
        return $qb;
    }
    
    /**
     * Find notice from specific source with identifiant
     *
     * @access public
     * @param string  $source   Source of notice
     * @param string  $id       Id of notice
     * 
     * @return null|Notice Null or notice found
     */
    public function findNoticesBySourceIdentifier($source, $id)
    {
        $result = $this->createQueryBuilder('n')
            ->select('n.idNoticia, n.idBeebac, n.updateDate, n.source')
            ->andWhere('n.source = :source')
            ->andWhere('n.id'.ucfirst($source) . ' = :id')
            ->setParameters(array(
                'source' => $source,
                'id'     => $id
            ))
            ->setMaxResults(1)
            ->getQuery()   
            ->getOneOrNullResult();
        
        return $result;
    }
}
