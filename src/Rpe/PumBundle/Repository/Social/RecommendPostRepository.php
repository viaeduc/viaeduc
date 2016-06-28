<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getRecommend($user, $post)
 * @method \Doctrine\ORM\mixed getRecommendsCount($startDate = null, $endDate = null)
 * @method \Doctrine\ORM\mixed getRecommendsByRelation($user, $post)
 */
class RecommendPostRepository extends ObjectRepository
{
    /**
     * get recommend
     * 
     * @access public
     * @param User      $user   User object
     * @param Post      $post   Post object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getRecommend($user, $post)
    {
        $recommend = $this
            ->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.post = :post')
            ->setParameters(array(
                'user'  => $user,
                'post' => $post,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $recommend;
    }

    /**
     * get recommends ordered by friends then non-friends
     * 
     * @access public
     * @param User      $user   User object
     * @param Post      $post   Post object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getRecommendsByRelation($user, $post)
    {
        $recommend = $this
            ->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('u.friends', 'f', 'WITH', 'f.friend = :me')
            ->andWhere('r.post = :post')
            ->setParameters(array(
                'me' => $user,
                'post' => $post
            ))
            ->orderBy('f.status', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $recommend;
    }

    /**
     * Get recommend count
     * 
     * @access public
     * @param string    $startDate      Start date of creation
     * @param string    $endDate        End date of creation
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getRecommendsCount($startDate = null, $endDate = null)
    {
        $parameters = array();

        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $recommends = $this
            ->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->setParameters($parameters)
        ;

        if (null !== $startDate && null !== $endDate) {
            $recommends->andWhere('r.date >= :startDate');
            $recommends->andWhere('r.date <= :endDate');
        } elseif (null !== $startDate) {
            $recommends->andWhere('r.date >= :startDate');
        } elseif (null !== $endDate) {
            $recommends->andWhere('r.date <= :endDate');
        }

        return $recommends
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
