<?php
namespace Rpe\PumBundle\Repository\Rpe;

use Pum\Core\Object\ObjectRepository;

/**
 * @method array getSuggestedPosts($orderBy = null, $limit = 7)
 * @method array getPastPosts()
 *
 */
class SuggestedPostRepository extends ObjectRepository
{
    /**
     * Get posts to be displayed
     * 
     * @access public
     * @param array       $orderBy   Order array
     * @param int         $limit     Limit result
     * 
     * @return array  Array containing suggested post
     */ 
    public function getSuggestedPosts($orderBy = null, $limit = 7)
    {
        $posts = $this
            ->createQueryBuilder('sp')
            ->andWhere('sp.status = :published')
            ->andWhere('sp.startDate <= :now')
            ->andWhere('sp.endDate >= :now')
            ->setParameters(array(
                'published' => 'published',
                'now' => date('Y-m-d H:i:s'),
            ))
            ->setMaxResults($limit)
        ;
        if (is_null($orderBy)) {
            $orderBy = array('startDate' => 'DESC');
        }
        foreach ((array)$orderBy as $sort => $order) {
            $posts->orderBy('sp.'.$sort, $order);
        }

        return $posts->getQuery()->getResult();
    }

    /**
     * Get suggested post already expired
     * 
     * @access public
     * 
     * @return array  Array containing suggested post
     */ 
    public function getPastPosts()
    {
        $posts = $this
            ->createQueryBuilder('sp')
            ->andWhere('sp.status = :published')
            ->andWhere('sp.endDate < :now')
            ->setParameters(array(
                'published' => 'published',
                'now' => date('Y-m-d'),
            ))
        ;

        return $posts->getQuery()->getResult();
    }
}