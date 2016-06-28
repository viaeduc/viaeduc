<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\Query|multitype: getWaitingNotifs($returnQuery = false, $maxResults = null, $firstResult = null)
 *
 */
class NotificationWaitRepository extends ObjectRepository
{
    /**
     * Get all notification waiting
     * 
     * @access public
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getWaitingNotifs($returnQuery = false, $maxResults = null, $firstResult = null, $types = null, $operator = 'in')
    {
        $qb = $this->createQueryBuilder('rm');
        
        if (is_array($types)) {
            if ($operator === 'in') {
                $qb->where($qb->expr()->in('rm.type', $types));
            }
            if ($operator === 'notin') {
                $qb->where($qb->expr()->notIn('rm.type', $types));
            }
        }
        
        $collection = $qb
            ->orderBy('rm.id', 'ASC');
        
            if (null !== $maxResults) {
                $collection->setMaxResults($maxResults);
            }
            
            if (null !== $firstResult) {
                $collection->setFirstResult($firstResult);
            }            
            if ($returnQuery) {
                return $collection->getQuery();
            }
            return $collection->getQuery()->getResult();
    }
}
