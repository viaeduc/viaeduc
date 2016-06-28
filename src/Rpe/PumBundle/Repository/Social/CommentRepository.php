<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getCommentsCount($startDate = null, $endDate = null)
 *
 */
class CommentRepository extends ObjectRepository
{
    
    /**
     * Get comment count form a period
     * 
     * @access public
     * @param string $startDate     Start date of comment
     * @param string $endDate       End date of comment
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getCommentsCount($startDate = null, $endDate = null)
    {
        $parameters = array();

        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $comments = $this
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->setParameters($parameters)
        ;

        if (null !== $startDate && null !== $endDate) {
            $comments->andWhere('c.date >= :startDate');
            $comments->andWhere('c.date <= :endDate');
        } elseif (null !== $startDate) {
            $comments->andWhere('c.date >= :startDate');
        } elseif (null !== $endDate) {
            $comments->andWhere('c.date <= :endDate');
        }

        return $comments
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
