<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed  getRecommend($user, $recommended)
 * 
 *
 */
class RecommendUserRepository extends ObjectRepository
{
    /**
     * get recommend
     *
     * @access public
     * @param User      $user   User object
     * @param User      $recommended User object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getRecommend($user, $recommended)
    {
        $recommend = $this
            ->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.recommended = :recommended')
            ->setParameters(array(
                'user'  => $user,
                'recommended' => $recommended,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $recommend;
    }
}
