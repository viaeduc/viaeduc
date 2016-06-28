<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Friend;
use Rpe\PumBundle\Model\Social\UserInGroup;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query;
use Rpe\PumBundle\Model\Social\UserInBlog;

/**
 * @method array    getPotentialUsers($returnQuery = false, $maxResults = null, $firstResult = null)
 * @method array    getSuggestedFriends($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method array    getAcceptedFriends($user, $getQuery = true, $getResult = true, $maxResults = null, $firstResult = null, $select = null, $status = null, $arrayMode = false)
 * @method array    getAcceptedFriendsActive($user, $getQuery = true, $getResult = true, $maxResults = null, $firstResult = null, $select = null)
 * @method array    getFriendsNotInList($user, $list, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method array    getUsersCount($status = null, $type = null, $academy = null, $startDate = null, $endDate = null)
 * @method array    getRest($limit = 10, $offset = 0, $keyword = null)
 * @method User|null    getOneRest($id)
 * @method \Doctrine\ORM\mixed    getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null)
 * @method array    getAdmins($group, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true)
 * @method array    getMembers($group, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true)
 * @method array    getBlogMembers($blog, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true)
 */
class UserRepository extends ObjectRepository
{
    /**
     * Get list of potential users
     *
     * @access public
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return array Array contains user objects
     */
    public function getPotentialUsers($returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('r');
        $relations = $qb
            ->andWhere('r.status = :s_active')
            ->setParameters(array(
                's_active'    =>  User::STATUS_TYPE_ACTIVE
            ))
            ->orderBy('r.id', 'DESC')
        ;

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

    /**
     * Get list of suggested users
     *
     * @access public
     * @param User      $user           User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return array Array contains user objects
     */
    public function getSuggestedFriends($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $parameters = array(
                'me'       => $user,
                's_active' => User::STATUS_TYPE_ACTIVE,
                'types'     => array(User::TYPE_ADMIN, User::TYPE_COMMON, User::TYPE_PRIVILEGE)
        );

        $qb = $this->createQueryBuilder('r');
        $relations = $qb
            ->leftJoin('r.friends', 'u', 'WITH', 'u.friend = :me')
            ->leftJoin('r.friends', 'v', 'WITH', 'v.user = :me')
            ->andWhere('u IS NULL')
            ->andWhere('v IS NULL')
            ->andWhere('r <> :me')
            ->andWhere('r.status = :s_active')
            ->andWhere('r.type in (:types)')
        ;

        if ($user->isInvited()) {
            $inQuery = $this->createQueryBuilder('g')
                ->select('DISTINCT IDENTITY(uig_1.group)')
                ->join('g.groups', 'uig_1', 'WITH', 'uig_1.user = :me');
            $relations = $relations->join('r.groups', 'uig', 'WITH',
                $qb->expr()->in('uig.group', $inQuery->getDql()));
//             echo $relations->getQuery()->getSql();
//             die;
        }

        if ($maxResults !== null) {
            $parameters['academy'] = $user->getAcademy();
            $relations
            ->addSelect('(CASE WHEN r.academy = :academy THEN 1 ELSE 0 END) AS HIDDEN sameAC')
            ->orderBy('sameAC', 'DESC');
        }
        $relations->addOrderBy('r.id', 'DESC')
            ->setParameters($parameters);

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

    /**
     * Get list of accepted friends for users
     *
     * @access public
     * @param User      $user           User object
     * @param boolean   $getResult      Return result or not
     * @param boolean   $getQuery       Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param string    $select         Select fields
     * @param string    $status         Status of user
     *
     * @return array Array contains user objects
     */
    public function getAcceptedFriends($user, $getQuery = true, $getResult = true, $maxResults = null, $firstResult = null, $select = null, $status = null, $arrayMode = false)
    {
        $qb = $this->createQueryBuilder('u');

        if (null !== $select) {
            if ($select != 'count') {
                $qb->select('DISTINCT partial u.{'.$select.'}');
            } else {
                $qb->select('count(DISTINCT u.id)');
            }
        } else {
            $qb->select('DISTINCT u');
        }

        $qb->LeftJoin('u.friends', 'f', 'WITH', $qb->expr()->andX($qb->expr()->eq('f.friend', ':me'), $qb->expr()->eq('f.status', ':status')));
        $qb->LeftJoin('u.rfriends', 'rf', 'WITH', $qb->expr()->andX($qb->expr()->eq('rf.user', ':me'), $qb->expr()->eq('rf.status', ':status')));

        $parameters = array(
            'me'     => $user,
            'status' => Friend::STATUS_ACCEPTED,
//             'user_invited' => User::TYPE_INVITED
        );

        if (null !== $status) {
            $qb->andWhere($qb->expr()->eq('u.status', ':user_status'));
            $parameters['user_status'] = $status;
        }

        $qb
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNotNull('f'),
                $qb->expr()->isNotNull('rf')
            ))
            ->andWhere($qb->expr()->neq('u', ':me'))
//             ->andWhere($qb->expr()->neq('u.type', ':user_invited'))
            ->setParameters($parameters)
            ->orderBy('u.firstname')
        ;

//         var_dump(count($qb->getQuery()->getResult()));die;

        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }

        if ($getQuery) {
            $qb = $qb->getQuery();
        }

        if ($getResult) {
            if ($select != 'count') {
                if (false === $arrayMode) {
                    $qb = $qb->getResult();
                } else {
                    $qb = $qb->getResult(Query::HYDRATE_ARRAY);
                }
            } else {
                $qb = $qb->getSingleScalarResult();
            }
        }

        return $qb;
    }


    /**
     * Get list of accepted friends which are active for users
     *
     * @access public
     * @param User      $user           User object
     * @param boolean   $getResult      Return result or not
     * @param boolean   $getQuery       Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param string    $select         Select fields
     *
     * @return array Array contains user objects
     */
    public function getAcceptedFriendsActive($user, $getQuery = true, $getResult = true, $maxResults = null, $firstResult = null, $select = null, $arrayMode = true)
    {
        return $this->getAcceptedFriends($user, $getQuery, $getResult, $maxResults, $firstResult, $select, User::STATUS_TYPE_ACTIVE, $arrayMode);
    }

    /**
     * Get friends not in a specific list
     *
     * @access public
     * @param User      $user           User object
     * @param array     $list           array of user list
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return array Array contains user objects
     */
    public function getFriendsNotInList($user, $list, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb1 = $this->createQueryBuilder('qb1')
            ->select('IDENTITY(f.friend)')
            ->leftJoin('qb1.friends', 'f', 'WITH', 'f.user = :me')
            ->andWhere('f.status = :status');
        $qb2 = $this->createQueryBuilder('qb2')
            ->select('qb2.id')
            ->leftJoin('qb2.friends', 'v')
            ->andWhere('v.friend = :me')
            ->andWhere('v.status = :status');

        $friendsBuilder = $this->createQueryBuilder('user');
        $friends = $friendsBuilder->andWhere('user.status = :status_active')
            ->andWhere($friendsBuilder->expr()->in('user.id', $qb1->getDQL()))
            ->orWhere($friendsBuilder->expr()->in('user.id', $qb2->getDQL()))
            ->orderBy('user.firstname', 'ASC')
            ->setParameters(array(
                'me'     => $user,
                'status' => Friend::STATUS_ACCEPTED,
                'status_active' => User::STATUS_TYPE_ACTIVE,
            ));
        if(count($list)){
            $friends->andWhere($friendsBuilder->expr()->notIn('user.id', ':list'))
            ->setParameter('list', $list);
        }

        if (null !== $maxResults) {
            $friends->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $friends->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $friends->getQuery();
        }
        return $friends->getQuery()->getResult();
    }

    /**
     * Get user count number
     *
     * @access public
     * @param object    $academy        Academy object
     * @param string    $startDate      Start date of creation
     * @param string    $endDate        End date of creation
     * @param string    $status         User status
     * @param string    $type           User type
     *
     * @return array Array contains user objects
     */
    public function getUsersCount($status = null, $type = null, $academy = null, $startDate = null, $endDate = null)
    {
        $parameters = array();

        if (null !== $status) {
            $parameters['status'] = $status;
        }

        if (null !== $type) {
            $parameters['type'] = $type;
        }

        if (null !== $academy) {
            $parameters['academy'] = $academy;
        }

        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $users = $this
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->setParameters($parameters)
        ;

        if (null !== $status) {
            $users->andWhere('u.status = :status');
        }

        if (null !== $type) {
            $users->andWhere('u.type = :type');
        }

        if (null !== $academy) {
            $users->andWhere('u.academy = :academy');
        }

        if (null !== $startDate && null !== $endDate) {
            $users->andWhere('u.date >= :startDate');
            $users->andWhere('u.date <= :endDate');
        } elseif (null !== $startDate) {
            $users->andWhere('u.date >= :startDate');
        } elseif (null !== $endDate) {
            $users->andWhere('u.date <= :endDate');
        }

        return $users
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Get rest users
     *
     * @access public
     * @param number $limit    Limit of items
     * @param number $offset   Start offset of items
     * @param string $keyword  Keyword for search if exist
     *
     * @return array
     */
    public function getRest($limit = 10, $offset = 0, $keyword = null)
    {
        $parameters = array();

        if (null !== $keyword) {
            $parameters['keyword'] = '%'.$keyword.'%';
        }

        $qb = $this->createQueryBuilder('u');

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
     * Get the one rest user by id
     *
     * @access public
     * @param string    $id     Folder id
     * @return User|null
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
     * @param string $keyword           Search keywork if exist
     * @param array  $restParameters  Other parameters
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null)
    {
        if($link_type == 'friends') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            $parameters['status'] = Friend::STATUS_ACCEPTED;

            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }

            $qb = $this->createQueryBuilder('u')
                ->leftJoin('u.friends', 'f')
                ->andWhere('f.friend = :user')
                ->andWhere('f.status = :status')
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
        } elseif($link_type == 'page_friends') {
            $parameters = array();
            $parameters['user'] = $main_object_id;

            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }

            $qb = $this->createQueryBuilder('u')
                ->leftJoin('u.editors', 'e')
                ->andWhere('e.user = :user')
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

    /**
     * Get admin users of group
     *
     * @access public
     * @param Group     $group          Group object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param boolean   $arrayMode      Return array or not
     *
     * @return array Array contains user objects
     */
    public function getAdmins($group, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true)
    {
        $qb     = $this->createQueryBuilder('u');
        $fields = 'id, firstname, lastname, avatar_id, avatar_mime';

        $qb
            ->select('partial u.{'.$fields.'}')
            ->join('u.groups', 'uig', 'WITH', $qb->expr()->andX($qb->expr()->eq('uig.group', ':group'), $qb->expr()->lte('uig.status', ':status')))
            ->setParameters(array(
                'group'  => $group,
                'status' => UserInGroup::IS_ADMIN
            ))
            ->orderBy('uig.id', 'DESC')
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

        if ($arrayMode) {
            return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get members of group
     *
     * @access public
     * @param Group     $group          Group object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param boolean   $arrayMode      Return array or not
     *
     * @return array Array contains user objects
     */
    public function getMembers($group, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true)
    {
        $qb     = $this->createQueryBuilder('u');
        $fields = 'id, firstname, lastname, avatar_id, avatar_mime, status';

        $qb
            ->select('partial u.{'.$fields.'}')
            ->join('u.groups', 'uig', 'WITH', $qb->expr()->andX($qb->expr()->eq('uig.group', ':group'), $qb->expr()->lte('uig.status', ':status')))
            ->setParameters(array(
                'group'  => $group,
                'status' => UserInGroup::STATUS_USER
            ))
            ->orderBy('uig.id', 'DESC')
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

        if ($arrayMode) {
            return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        }

        return $qb->getQuery()->getResult();
    }
    
    /**
     * Get members of blog
     *
     * @access public
     * @param Blog      $blog           blog object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param boolean   $arrayMode      Return array or not
     *
     * @return array Array contains user objects
     */
    public function getBlogMembers($blog, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true)
    {
        $qb     = $this->createQueryBuilder('u');
        $fields = 'id, firstname, lastname, avatar_id, avatar_mime, status';
    
        $qb
            ->select('partial u.{'.$fields.'}')
            ->join('u.blogs', 'uib', 'WITH', $qb->expr()->andX($qb->expr()->eq('uib.blog', ':blog'), $qb->expr()->lte('uib.status', ':status')))
            ->setParameters(array(
                'blog'  => $blog,
                'status' => UserInBlog::STATUS_USER
            ))
            ->orderBy('uib.id', 'DESC')
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
    
        if ($arrayMode) {
            return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        }
    
        return $qb->getQuery()->getResult();
    }
}
