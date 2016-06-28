<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getRecommend($user, $comment)
 *
 */
class RecommendCommentRepository extends ObjectRepository
{
    /**
     * get recommend
     * 
     * @access public
     * @param User      $user   User object
     * @param Comment    $comment comment object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getRecommend($user, $comment)
    {
        $recommend = $this
            ->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.comment = :comment')
            ->setParameters(array(
                'user'  => $user,
                'comment' => $comment,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $recommend;
    }
}
