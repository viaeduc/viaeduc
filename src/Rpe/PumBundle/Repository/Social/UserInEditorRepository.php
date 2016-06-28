<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\UserInGroup;
use Rpe\PumBundle\Model\Social\Editor;

/**
 * @method \Doctrine\ORM\mixed getUserInEditor($user, $editor)
 *
 */
class UserInEditorRepository extends ObjectRepository
{
    /**
     * get user_in_editor status
     * 
     * @param User      $user   User object
     * @param Editor    $editor Editor object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getUserInEditor($user, $editor)
    {
        $user_in_editor = $this
            ->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.editor = :editor')
            ->setParameters(array(
                'user'  => $user,
                'editor' => $editor,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $user_in_editor;
    }
}
