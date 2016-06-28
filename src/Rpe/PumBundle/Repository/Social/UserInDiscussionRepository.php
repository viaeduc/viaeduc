<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Discussion;

/**
 * @method \Doctrine\ORM\mixed getUserInDiscussion($user, $discussion)
 *
 */
class UserInDiscussionRepository extends ObjectRepository
{
    /**
     * get user_in_discussion status
     * 
     * @param User      $user   User object
     * @param Discussion $discussion    discussion object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getUserInDiscussion($user, $discussion)
    {
        $user_in_discussion = $this
            ->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.discussion = :discussion')
            ->setParameters(array(
                'user'       => $user,
                'discussion' => $discussion,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $user_in_discussion;
    }
}
