<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getBookmark($user, $editor)
 *
 */
class BookmarkEditorRepository extends ObjectRepository
{
    /**
     * get bookmark editor
     * 
     * @access public
     * @param User $user        User object
     * @param Editor $editor    Editor object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getBookmark($user, $editor)
    {
        $bookmark = $this
            ->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->andWhere('b.editor = :editor')
            ->setParameters(array(
                'user'  => $user,
                'editor' => $editor,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $bookmark;
    }
}
