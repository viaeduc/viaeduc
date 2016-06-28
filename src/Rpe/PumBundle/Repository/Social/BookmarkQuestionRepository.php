<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getBookmark($user, $question)
 *
 */
class BookmarkQuestionRepository extends ObjectRepository
{
    /**
     * get bookmark
     * 
     * @access public
     * @param User   $user        User object
     * @param Question  $question       Question object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getBookmark($user, $question)
    {
        $bookmark = $this
            ->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->andWhere('b.question = :question')
            ->setParameters(array(
                'user'  => $user,
                'question' => $question,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $bookmark;
    }
}
