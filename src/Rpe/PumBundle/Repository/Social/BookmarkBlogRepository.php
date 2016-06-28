<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getBookmark($user, $blog)
 *
 */
class BookmarkBlogRepository extends ObjectRepository
{
    
    /**
     * get bookmark
     * 
     * @access public
     * @param User $user        User object
     * @param Blog $blog        Blog object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getBookmark($user, $blog)
    {
        $bookmark = $this
            ->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->andWhere('b.blog = :blog')
            ->setParameters(array(
                'user'  => $user,
                'blog' => $blog,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $bookmark;
    }
}
