<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Post;

/**
 * @method \Doctrine\ORM\mixed  getSharePostsCount($startDate = null, $endDate = null)
 *
 */
class SharePostRepository extends ObjectRepository
{
    /**
     * Get shared post count number
     * 
     * @access public
     * @param string    $startDate      Start date of creation
     * @param string    $endDate        End date of creation
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getSharePostsCount($startDate = null, $endDate = null)
    {
        $parameters = array();

        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $users = $this
            ->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->setParameters($parameters)
        ;

        if (null !== $startDate && null !== $endDate) {
            $users->andWhere('sp.date >= :startDate');
            $users->andWhere('sp.date <= :endDate');
        } elseif (null !== $startDate) {
            $users->andWhere('sp.date >= :startDate');
        } elseif (null !== $endDate) {
            $users->andWhere('sp.date <= :endDate');
        }

        return $users
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
