<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Friend;
use Rpe\PumBundle\Model\Social\User;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @method \Doctrine\ORM\mixed  getRelation($user_a, $user_b)
 * @method ArrayCollection      getActiveAcceptedFriends($user, $maxResults = null)
 * @method \Doctrine\ORM\mixed  getPendingFriends($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method \Doctrine\ORM\mixed  getCommonFriends($user_a, $user_b, $returnIds = true)
 * @method \Doctrine\ORM\mixed  getRelationOneSided($user_a, $user_b)
 * 
 */
class FriendRepository extends ObjectRepository
{
    /**
     * get relation status
     * 
     * @access public
     * @param User $user_a  User a
     * @param User $user_b  User b
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getRelation($user_a, $user_b , $one_direction = false)
    {
        $friendship = $this
            ->createQueryBuilder('f')
            ->andWhere('f.user = :user_a AND f.friend = :user_b');
        if ($one_direction === false) {
            $friendship->orWhere('f.user = :user_b AND f.friend = :user_a');
        }
        $result = $friendship
            ->setParameters(array(
                'user_a' => $user_a,
                'user_b' => $user_b,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
            
        return $result;
    }
    
    /**
     * get relation status
     * 
     * @access public
     * @param User $user  User object
     * @param int       $maxResults     Maximum return result
     * 
     * @return ArrayCollection
     */
    public function getActiveAcceptedFriends($user, $maxResults = null)
    {
        $qb = $this->createQueryBuilder('f');
        
        $friends = $qb
            ->select('f')
            ->leftJoin('f.friend', 'u')
            ->andWhere('f.user = :me')
            ->andWhere('f.status = :friend_status')
            ->andWhere('u.status = :active_status')
            ->setParameters(array(
                'me'            => $user,
                'friend_status' => Friend::STATUS_ACCEPTED,
                'active_status' => User::STATUS_TYPE_ACTIVE
            ))
        ;
        if (null !== $maxResults) {
            $friends->setMaxResults($maxResults);
        }
        
        return new ArrayCollection($friends->getQuery()->getResult());
    }
    
    /**
     * get one side relation
     *
     * @access public
     * @param User $user_a  User a
     * @param User $user_b  User b
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getRelationOneSided($user_a, $user_b)
    {
        $friendship = $this
            ->createQueryBuilder('f')
            ->andWhere('f.user = :user_a AND f.friend = :user_b')
            ->setParameters(array(
                'user_a' => $user_a,
                'user_b' => $user_b,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $friendship;
    }

    /**
     * get common friends between 2 users
     *
     * @access public
     * @param User $user_a  User a
     * @param User $user_b  User b
     * @parap boolean $returnIds Whether to return id
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getCommonFriends($user_a, $user_b, $returnIds = true)
    {
        $profilRelations = $this
            ->createQueryBuilder('f')
            ->select('IDENTITY(f.friend)')
            ->andWhere('f.user = :profil')
            ->andWhere('f.status = :status')
            ->setParameters(array(
                'profil' => $user_a,
                'status' => Friend::STATUS_ACCEPTED,
             ))
            ->getQuery()
            ->getArrayResult()
        ;

        if (!$profilRelations) {
            return array();
        }

        $qb = $this->createQueryBuilder('f');
        
        $commonRelation = $qb;

        if($returnIds) {
            $commonRelation->select('IDENTITY(f.friend)');
        }
        
        $commonRelation = $commonRelation
            ->andWhere('f.user = :user')
            ->andWhere('f.status = :status')
            ->andWhere($qb->expr()->in('f.friend', ':list'))
            ->setParameters(array(
                'user' => $user_b,
                'status' => Friend::STATUS_ACCEPTED,
                'list' => $profilRelations,
            ))
            ->getQuery()
            ->getResult()
        ;
        return $commonRelation;
    }

    /**
     * get pending friends of a user
     *
     * @access public
     * @param User $user  User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getPendingFriends($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('f');
        $relations = $qb
            ->andWhere('f.status = :on_hold')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('f.user', ':me'),
                    $qb->expr()->eq('f.friend', ':me')
                )
            )
            ->setParameters(array(
                'me'      => $user,
                'on_hold' => Friend::STATUS_ON_HOLD,
            ))
            ->orderBy('f.id', 'DESC')
        ;

        //echo($relations->getQuery()->getSQL());die;

        if (null !== $maxResults) {
            $relations->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $relations->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $relations->getQuery();
        }

        return $relations->getQuery()->getResult();
    }
}
