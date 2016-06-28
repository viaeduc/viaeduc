<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getSavedSearch($user)
 * @method \Doctrine\ORM\mixed getExistSearch($user, $keyword)
 *
 */
class SavedSearchRepository extends ObjectRepository
{
    /**
     * get searches 
     * 
     * @access public
     * @param User   $user        User object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getSavedSearch($user)
    {
        $searchs = $this
            ->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameters(array(
                'user'  => $user
            ))
            ->getQuery()
            ->getResult();
        ;
        return $searchs;
    }
    
    
    /**
     * get search
     *
     * @access public
     * @param User   $user        User object
     * @param string $query       Query string
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getExistSearch($user, $keyword)
    {
        $search = $this
            ->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.keyword = :keyword')
            ->setParameters(array(
                'user'  => $user,
                'keyword' => $keyword,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
        return $search;
    }
}
