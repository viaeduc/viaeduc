<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\UserInGroup;
use Rpe\PumBundle\Model\Social\User;

/**
 * @method UserInGroup|null     getUserInGroup($user, $group, $status = null)
 * @method array                getUserListInGroup($group)
 * @method array                getCommonGroups($user_main, $user_sec)
 * @method UserInGroup|null     getUserInGroup($user, $group, $status = null)
 * @method array                getUserListInChildGroups($user, $parentGroup, $status = null)
 * @method array                getActiveUserInGroup($idGroup)
 * @method array                getUserListByStatus($group, $status = null)
 */
class UserInGroupRepository extends ObjectRepository
{
    /**
     * get user in groups by status
     *
     * @access public
     * @param Group     $group  Group object
     * @param User      $user   User object
     * @param string    $status Status of user in group
     *
     * @return UserInGroup|null
     */
    public function getUserInGroup($user, $group, $status = null)
    {
        $user_in_group = $this
            ->createQueryBuilder('f')
            ->leftJoin('f.user', 'g')
            ->andWhere('f.user = :user')
            ->andWhere('f.group = :group')
            ->setParameters(array(
                'user'  => $user,
                'group' => $group,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $user_in_group;
    }
    
    /**
     * get common groups between 2 users
     *
     * @access public
     * @param User      $user_sec    User object
     * @param User      $user_main   User object
     *
     * @return array Array of user in group objects
     */
    public function getCommonGroups($user_main, $user_sec)
    {   
        $query_sec = $this->createQueryBuilder('sec')
            ->select('IDENTITY(sec.group)')
            ->andWhere('sec.user = :user_sec')
            ->andWhere('sec.status <= :in_group')
        ;
        $qb = $this->createQueryBuilder('main');
        $query_main = $qb
            ->select('main.id')
            ->andWhere('main.user = :user_main')
            ->andWhere('main.status <= :in_group')
            ->andWhere($qb->expr()->in('main.group', $query_sec->getDQL()))
            ->setParameters(array(
                'user_sec' => $user_sec,
                'user_main' => $user_main,
                'in_group' => UserInGroup::IN_GROUP
            ));    
        return $query_main->getQuery()->getArrayResult();
    }
    
    /**
     * get users in a group
     *
     * @access public
     * @param Group     $group  Group object
     *
     * @return array Array of user in group objects
     */
    public function getUserListInGroup($group)
    {
        $users_in_group = $this
            ->createQueryBuilder('uig')
            ->select('IDENTITY(uig.user)')
            ->andWhere('uig.group = :group')
            ->andWhere('uig.user IS NOT NULL')
            ->andWhere('uig.status <= :in_group')
            ->setParameters(array(
                'group' => $group,
                'in_group' => UserInGroup::IN_GROUP
            ))
            ->getQuery()  
            ->getArrayResult()
        ;
        return $users_in_group;
    }

    
    /**
     * get user_in_group by status
     *
     * @access public
     * @param Group     $group  Group object
     * @param string    $status Status of user in group
     *
     * @return array
     */
    public function getUserListByStatus($group, $status = null)
    {
        $parameters = array(
            'group' => $group
        );
        
        if (null !== $status) {
            $parameters['status'] = $status;
        }
        
        $users_in_group = $this
            ->createQueryBuilder('uig')
            ->andWhere('uig.group = :group')
        ;
        
        if (null !== $status) {
            $users_in_group->andWhere('uig.status = :status');
        }
        
        $users_in_group = $users_in_group->setParameters($parameters)
            ->getQuery()
            ->getResult()
        ;
        
        return $users_in_group;
    }

    /**
     * get user in the children groups
     *
     * @access public
     * @param Group     $parentGroup      Group object
     * @param User      $user             User object
     * @param string    $status Status of user in group
     *
     * @return array
     */
    public function getUserListInChildGroups($user, $parentGroup, $status = null)
    {
        $parameters = array(
            'user' => $user,
            'parent_group' => $parentGroup
        );

        if (null !== $status) {
            $parameters['status'] = $status;
        }

        $users_in_group = $this
            ->createQueryBuilder('uig')
            ->leftJoin('uig.group', 'g')
            ->andWhere('uig.user = :user')
            ->andWhere('g.parent = :parent_group')
        ;

        if (null !== $status) {
            $users_in_group->andWhere('uig.status = :status');
        }

        $users_in_group = $users_in_group->setParameters($parameters)
            ->getQuery()
            ->getResult()
        ;

        return $users_in_group;
    }

    /**
     * get active users in group
     *
     * @access public
     * @param string    $idGroup  Id of group
     *
     * @return array
     */
    public function getActiveUserInGroup($idGroup)
    {
        $user_in_group = $this
            ->createQueryBuilder('uig')
            ->select('u.id')
            ->andWhere('uig.group = :idGroup')
            ->andWhere('uig.status <= :uig_accepted')
            ->leftJoin('uig.user', 'u')
            ->andWhere('u.status = :status')
            ->setParameters(array('idGroup' => $idGroup, 'status' => 'ACTIVE', 'uig_accepted' => UserInGroup::IN_GROUP))
            ->getQuery()
            ->getArrayResult()
        ;

        return $user_in_group;
    }
}
