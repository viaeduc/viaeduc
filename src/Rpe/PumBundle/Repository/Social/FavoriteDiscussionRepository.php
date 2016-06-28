<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Post;

/**
 * @method \Doctrine\ORM\mixed getSavedFavorite($user)
 * @method \Doctrine\ORM\mixed getExistSearch($user, $keyword)
 *
 */
class FavoriteDiscussionRepository extends ObjectRepository
{
    /**
     * get searches 
     * 
     * @access public
     * @param User   $user        User object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getSavedFavorite($user)
    {
        $favorites = $this
            ->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameters(array(
                'user'  => $user
            ))
            ->getQuery()
            ->getResult();
        ;
        return $favorites;
    }
    
    
    /**
     * get search
     *
     * @access public
     * @param User   $user             User object
     * @param Post   $discussion       Post object which contains list of comments
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getFavorite($user, $discussion)
    {
        $favorite = $this
            ->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.discussion = :discussion')
            ->setParameters(array(
                'user'  => $user,
                'discussion' => $discussion,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
        return $favorite;
    }
}
