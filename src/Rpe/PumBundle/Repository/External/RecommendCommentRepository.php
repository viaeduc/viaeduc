<?php
namespace Rpe\PumBundle\Repository\External;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\External\Comment;

/**
 * @method Comment getRecommend($user, $comment)
 *
 */
class RecommendCommentRepository extends ObjectRepository
{
    /**
     * Get recommend comment object
     *
     * @access public
     * @param User $user   User object
     * @param Comment $comment Comment object
     *
     * @return Comment
     *
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
