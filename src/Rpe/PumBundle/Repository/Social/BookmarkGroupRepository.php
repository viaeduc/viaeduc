<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getBookmark($user, $group)
 *
 */
class BookmarkGroupRepository extends ObjectRepository
{
    /**
     * get bookmark
     * 
     * @access public
     * @param User   $user        User object
     * @param Group  $group       Group object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getBookmark($user, $group)
    {
        $bookmark = $this
            ->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->andWhere('b.group = :group')
            ->setParameters(array(
                'user'  => $user,
                'group' => $group,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $bookmark;
    }
}
