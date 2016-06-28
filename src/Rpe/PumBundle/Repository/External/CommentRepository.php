<?php
namespace Rpe\PumBundle\Repository\External;

use Pum\Core\Object\ObjectRepository;

/**
 * Comment repostory
 * 
 * @method int getCommentsCount($startDate = null, $endDate = null)
 *
 */
class CommentRepository extends ObjectRepository
{
    /**
     * Get count number of comment
     * 
     * @access public
     * @param string $startDate         Start date of count
     * @param string $endDate           End date of count
     *
     * @return \Doctrine\ORM\mixed|int return count number
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
