<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\UserInGroup;
use Rpe\PumBundle\Model\Social\Friend;
use Doctrine\ORM\Query;

/**
 * @method  array                           getIdentityAcceptedGroups($user)
 * @method  \Doctrine\ORM\Query|multitype:  getacceptedgroups($user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = 'g')
 * @method  \Doctrine\ORM\Query|multitype:  getSuggestedGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:  getActifGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:  getFavoriteGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:  getGroupByAccess($user, $accesstype = Group::ACCESS_PUBLIC, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:  getAllGroups($returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:  getGroupsCount($accessType = null, $startDate = null, $endDate = null)
 * @method  array                           getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())
 * @method  Group|null                      getOneRest($id)
 * @method  \Doctrine\ORM\mixed             getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())
 * @method  \Doctrine\ORM\AbstractQuery|\Doctrine\ORM\Query|null    getLastGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = null, $arrayMode = false) *
 */
class GroupRepository extends ObjectRepository
{
    /**
     * get accepted groups
     * 
     * @access public
     * @param User      $user   User object
     * @return array
     */
    public function getIdentityAcceptedGroups($user)
    {
        $result = $this->getAcceptedGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = 'g.id');
        return array_map('current', $result);
    }

    /**
     * Accepted groups
     * 
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param string    $select         select fields
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getAcceptedGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = 'g')
    {
        $qb = $this->createQueryBuilder('g');

        $groups = $qb
            ->select($select)
            ->leftJoin('g.users', 'uig', 'WITH', 'uig.user = :me')
            ->andWhere('uig.status <= :uig_accepted')
            ->setParameters(array(
                'me' => $user,
                'uig_accepted' => UserInGroup::IN_GROUP,
            ))
        ;

        if (null !== $maxResults) {
            $groups->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $groups->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $groups->getQuery();
        }

        return $groups->getQuery()->getResult();
    }

    /**
     * Suggested groups
     * 
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getSuggestedGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('g');
        $groups = $qb
            ->leftJoin('g.users', 'u', 'WITH', 'u.user = :me')
            ->andWhere($qb->expr()->in('g.accesstype', ':accesstypes'))
            ->andWhere('u IS NULL')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNull('g.parent')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->eq('g.accesstype', ':accesstype_public')
                    )
                )
            )
            ->setParameters(array(
                'me' => $user,
                'accesstypes' => array(Group::ACCESS_PUBLIC, Group::ACCESS_ON_DEMAND),
                'accesstype_public' => Group::ACCESS_PUBLIC,
            ))
            ->orderBy('g.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $groups->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $groups->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $groups->getQuery();
        }

        return $groups->getQuery()->getResult();
    }

    /**
     * get actif groups
     *
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getActifGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('g');
        $groups = $qb
            ->leftJoin('g.users', 'u')
            ->andWhere('u.user = :me')
            ->andWhere('u.status <= :in_status')
            ->andWhere($qb->expr()->in('g.accesstype', ':accesstypes'))
            ->setParameters(array(
                'me' => $user,
                'accesstypes' => array(Group::ACCESS_PUBLIC, Group::ACCESS_ON_DEMAND, Group::ACCESS_ON_INVITATION),
                'in_status' => UserInGroup::IN_GROUP
            ))
            ->orderBy('g.updateDate', 'DESC')
        ;

        if (null !== $maxResults) {
            $groups->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $groups->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $groups->getQuery();
        }
        
        return $groups->getQuery()->getResult();
    }

    /**
     * get favorite groups
     *
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getFavoriteGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {        
        $qb = $this->createQueryBuilder('g');
        $groups = $qb
            ->leftJoin('g.bookmarkby', 'bm', 'WITH', 'bm.user = :me')
            ->andWhere('bm IS NOT NULL')
            ->setParameters(array(
                'me' => $user
            ))
            ->orderBy('g.updateDate', 'DESC')
            ;
        if (null !== $maxResults) {
            $groups->setMaxResults($maxResults);
        }
    
        if (null !== $firstResult) {
            $groups->setFirstResult($firstResult);
        }
    
        if ($returnQuery) {
            return $groups->getQuery();
        }
        return $groups->getQuery()->getResult();
    }  
    
    /**
     * get groups by users
     *
     * @param User      $user   User object
     * @param string    $accesstype     Access type
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getGroupByAccess($user, $accesstype = Group::ACCESS_PUBLIC, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('g');
        $groups = $qb
            // ->leftJoin('g.users', 'u', 'WITH', 'u.user = :me')
            ->andWhere($qb->expr()->eq('g.accesstype', ':access_filter'))
            // ->andWhere('u IS NULL')
            ->setParameters(array(
                // 'me' => $user,
                'access_filter' => $accesstype,
            ))
            ->orderBy('g.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $groups->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $groups->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $groups->getQuery();
        }

        return $groups->getQuery()->getResult();
    }

    
    /**
     * get all groups
     *
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getAllGroups($returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('g');
        $groups = $qb
            ->andWhere($qb->expr()->in('g.accesstype', ':accesstypes'))
            ->setParameters(array(
                'accesstypes' => array(Group::ACCESS_PUBLIC, Group::ACCESS_ON_DEMAND),
            ))
            ->orderBy('g.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $groups->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $groups->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $groups->getQuery();
        }

        return $groups->getQuery()->getResult();
    }

    
    /**
     * get groups count
     *
     * @param string    $accesstype     Access type
     * @param boolean   $returnQuery    Return query or not
     * @param string    $startDate      Start date of creation
     * @param string    $endDate        End date of creation
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getGroupsCount($accessType = null, $startDate = null, $endDate = null)
    {
        $parameters = array();

        if (null !== $accessType) {
            $parameters['accessType'] = $accessType;
        }

        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $users = $this
            ->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->setParameters($parameters)
        ;

        if (null !== $accessType) {
            $users->andWhere('g.accesstype = :accessType');
        }

        if (null !== $startDate && null !== $endDate) {
            $users->andWhere('g.createDate >= :startDate');
            $users->andWhere('g.createDate <= :endDate');
        } elseif (null !== $startDate) {
            $users->andWhere('g.createDate >= :startDate');
        } elseif (null !== $endDate) {
            $users->andWhere('g.createDate <= :endDate');
        }

        return $users
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
    
    /**
     * Get rest groups
     *
     * @access public
     * @param number $limit
     * @param number $offset
     * @param array  $restParameters  Other parameters
     * @return array
     */
    public function getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        $parameters = array();
        $parameters['uig_status'] = UserInGroup::STATUS_OWNER;
        
        if (null !== $keyword) {
            $parameters['keyword'] = '%'.$keyword.'%';
        }
        
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.users', 'uig')
            ->andWhere('uig.status = :uig_status')
            ->leftJoin('uig.user', 'u')
        ;

        if ($restParameters['format'] == 'rss') {
            $qb->select('g.id AS id');
            $qb->addSelect('g.name AS title');
            $qb->addSelect('g.description AS description');
            $qb->addSelect('g.createDate AS pubDate');
            $qb->addSelect('g.accesstype AS comments');
            $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
        }
        
        if (null !== $keyword) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.firstname', ':keyword'),
                    $qb->expr()->like('u.lastname', ':keyword')
                )
            );
        }
        
        $qb = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameters($parameters)
            ->getQuery()
            ->getResult()
        ;
        
        return $qb;
    }
    
    
    /**
     * Get the one rest group by id
     * 
     * @access public
     * @param string    $id     Folder id
     * @return Group|null 
     */
    public function getOneRest($id)
    {
        $user = $this
            ->createQueryBuilder('u')
            ->andWhere('u.id = :id')
            ->setParameters(array('id' => $id))
            ->getQuery()
            ->getOneOrNullResult()
        ;
        
        return $user;
    }
    
    
    /**
     * get all, linked objects
     *
     * @access public
     * @param string $link_type         Link type
     * @param string $main_object_id    Name of the object
     * @param string $limit             Limit of result
     * @param string $offset            Start offset for search
     * @param string $restParameters    Other parameters
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        if($link_type == 'user_groups') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            $parameters['status'] = UserInGroup::STATUS_USER;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('g')
                ->leftJoin('g.users', 'uig')
                ->leftJoin('uig.user', 'u')
                ->andWhere('uig.user = :user')
                ->andWhere('uig.status <= :status')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('g.id AS id');
                $qb->addSelect('g.name AS title');
                $qb->addSelect('g.description AS description');
                $qb->addSelect('g.createDate AS pubDate');
                $qb->addSelect('g.accesstype AS comments');
                $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
            }
            
            if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        } elseif($link_type == 'user_friends_groups') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            $parameters['useringroup_status'] = UserInGroup::STATUS_USER;
            $parameters['friend_status'] = Friend::STATUS_ACCEPTED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('g')
                ->leftJoin('g.users', 'uig')
                ->leftJoin('uig.user', 'u')
                ->leftJoin('u.friends', 'f')
                ->andWhere('f.friend = :user')
                ->andWhere('uig.status <= :useringroup_status')
                ->andWhere('f.status = :friend_status')
            ;
            
            if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        }
    }

    public function getSubGroups($user, $group, $returnQuery = false, $maxResults = null, $firstResult = null, $select = null, $arrayMode = false)
    {
        $qb = $this->createQueryBuilder('g');

        if (null !== $select) {
            if ($select != 'count') {
                $qb->select('partial g.{'.$select.'}');
            } else {
                $qb->select('count(g.id)');
            }
        } else {
            $qb->select('g');
        }
        
        $secretsGroups = $this->createQueryBuilder('sg')
            ->select('sg.id')
            ->join('sg.users', 'sg_uig', 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq('sg_uig.user', ':user'),
                    $qb->expr()->lte('sg_uig.status', ':uig_accepted')
                )
            )
            ->andWhere($qb->expr()->eq('sg.accesstype', ':type_secret'))
            ->andWhere($qb->expr()->eq('sg.parent', ':group'))
            ->setParameters(array(
                'user'         => $user,
                'uig_accepted' => UserInGroup::IN_GROUP,
                'type_secret'   => Group::ACCESS_ON_INVITATION,
                'group'  => $group
            ))
            ->getQuery()
            ->getResult()
        ;

        $qb
            ->leftJoin('g.parent', 'gp', 'WITH', 'g.parent = :group')
            ->leftJoin('gp.users', 'gp_u', 'WITH', 'gp_u.user = :user')
            ->andWhere($qb->expr()->isNotNull('gp'))
            ->andWhere($qb->expr()->isNotNull('gp_u'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->neq('g.accesstype', ':type_secret'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('g.accesstype', ':type_secret'),
                        $qb->expr()->in('g.id', ':secretsGroups')
                    )
                )
            )
            ->setParameters(array(
                'group'  => $group,
                'user'         => $user,
                'secretsGroups' => $secretsGroups,
                'type_secret'   => Group::ACCESS_ON_INVITATION
            ))
            ->orderBy('g.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            if ($arrayMode) {
                return $qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY);
            }

            return $qb->getQuery();
        }

        $qb = $qb->getQuery();

        if ($select != 'count') {
            if (false === $arrayMode) {
                $qb = $qb->getResult();
            } else {
                $qb = $qb->getResult(Query::HYDRATE_ARRAY);
            }
        } else {
            $qb = $qb->getSingleScalarResult();
        }

        return $qb;
    }
    
    /**
     * Get last groups
     * 
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param string    $select         Select fields
     * @param boolean   $arrayMode      return an array or not
     * 
     * @return \Doctrine\ORM\AbstractQuery|\Doctrine\ORM\Query|null
     */
    public function getLastGroups($user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = null, $arrayMode = false)
    {
        $qb = $this->createQueryBuilder('g');
    
        if (null !== $select) {
            if ($select != 'count') {
                $qb->select('partial g.{'.$select.'}');
            } else {
                $qb->select('count(g.id)');
            }
        } else {
            $qb->select('g');
        }
    
        $secretsGroups = $this->createQueryBuilder('sg')
            ->select('sg.id')
            ->join('sg.users', 'sg_uig', 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq('sg_uig.user', ':user'),
                    $qb->expr()->lte('sg_uig.status', ':uig_accepted')
                )
            )
            ->andWhere($qb->expr()->isNotNull('sg_uig'))
            ->andWhere($qb->expr()->eq('sg.accesstype', ':type_secret'))
            ->setParameters(array(
                'user'         => $user,
                'uig_accepted' => UserInGroup::IN_GROUP,
                'type_secret'   => Group::ACCESS_ON_INVITATION
            ))
            ->getQuery()
            ->getResult()
        ;
    
        $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->neq('g.accesstype', ':type_secret'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('g.accesstype', ':type_secret'),
                        $qb->expr()->in('g.id', ':secretsGroups')
                    )
                )
            )
            ->setParameters(array(
                'secretsGroups' => $secretsGroups,
                'type_secret'   => Group::ACCESS_ON_INVITATION
            ))
            ->orderBy('g.updateDate', 'DESC')
        ;
    
        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }
    
        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }
    
        if ($returnQuery) {
            if ($arrayMode) {
                return $qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY);
            }
    
            return $qb->getQuery();
        }
    
        $qb = $qb->getQuery();
    
        if ($select != 'count') {
            if (false === $arrayMode) {
                $qb = $qb->getResult();
            } else {
                $qb = $qb->getResult(Query::HYDRATE_ARRAY);
            }
        } else {
            $qb = $qb->getSingleScalarResult();
        }
    
        return $qb;
    }
}
