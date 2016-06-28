<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getRecommend($user, $answer)
 *
 */
class RecommendAnswerRepository extends ObjectRepository
{
    /**
     * get recommend
     * 
     * @access public
     * @param User      $user   User object
     * @param Answer    $answer Answer object
     * @return \Doctrine\ORM\mixed
     */
    public function getRecommend($user, $answer)
    {
        $recommend = $this
            ->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.answer = :answer')
            ->setParameters(array(
                'user'  => $user,
                'answer' => $answer,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $recommend;
    }
}
