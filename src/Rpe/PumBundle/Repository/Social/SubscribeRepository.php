<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getSubscribe($user, $followed)
 *
 */
class SubscribeRepository extends ObjectRepository
{
    /**
     *  get recommend
     * 
     * @param User      $user   User object
     * @param object     $followed Followed object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getSubscribe($user, $followed)
    {
        $subscribe = $this
            ->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.followed = :followed')
            ->setParameters(array(
                'user'  => $user,
                'followed' => $followed,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $subscribe;
    }
}
