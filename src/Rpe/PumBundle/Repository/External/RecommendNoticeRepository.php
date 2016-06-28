<?php
namespace Rpe\PumBundle\Repository\External;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\External\Notice;

/**
 * @method Notice getRecommend($user, $notice)
 *
 */
class RecommendNoticeRepository extends ObjectRepository
{
    /**
     * Get recommend notice by user and notice
     *
     * @access public
     * @param User $user   User object
     * @param Notice $notice   Notice object
     *
     * @return Notice 
     */
    public function getRecommend($user, $notice)
    {
        $recommend = $this
            ->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.notice = :notice')
            ->setParameters(array(
                'user'  => $user,
                'notice' => $notice,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $recommend;
    }
}
