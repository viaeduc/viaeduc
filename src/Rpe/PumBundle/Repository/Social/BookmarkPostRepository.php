<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getBookmark($user, $post)
 *
 */
class BookmarkPostRepository extends ObjectRepository
{
    /**
     * get bookmark
     * 
     * @access public
     * @param User $user        User object
     * @param Post $post        Post object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getBookmark($user, $post)
    {
        $bookmark = $this
            ->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->andWhere('b.post = :post')
            ->setParameters(array(
                'user'  => $user,
                'post' => $post,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $bookmark;
    }
}
