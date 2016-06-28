<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Friend;
use Doctrine\ORM\Query;

/**
 * @method  \Doctrine\ORM\Query|multitype:      getHomePublications($user, $groups = array(), $returnQuery = false, $maxResults = null, $firstResult = null, $debug = 0)
 * @method  \Doctrine\ORM\Query|multitype:      getProfilPublications($user, $groups = array(), $me = null, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true, $debug = 0)
 * @method  \Doctrine\ORM\Query|multitype:      getUserPublications($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:      getGroupPublications($group, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true)
 * @method  \Doctrine\ORM\Query|multitype:      getGroupResources($group, $returnQuery = false, $maxResults = null, $firstResult = null, $select = 'id, name, coverimage_id, file_id, coverimage_mime, file_mime', $arrayMode = true)
 * @method  \Doctrine\ORM\Query|multitype:      getGroupPadResources($group, $user, $returnQuery = false, $maxResults = null, $select = null, $arrayMode = false)
 * @method  \Doctrine\ORM\Query|multitype:      getLastUserPublications($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:      getAllPublications($returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:      getPopularPublications($user, $groups = array(), $count, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:      getSinglePublication($id, $user, $returnQuery = false)
 * @method  \Doctrine\ORM\Query|multitype:      getSingleEditPublication($id, $user, $returnQuery = false)
 * @method  boolean                             hasFriendsCoAuthors($id, $me)
 * @method  int                                 getAveragePostsByDayCount($isResource = false, $startDate = null, $endDate = null)
 * @method  \Doctrine\ORM\mixed                 getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())
 * @method  Post|null                           getOneRest($id)
 * @method  array                               getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())
 * 
 */
class PostRepository extends ObjectRepository
{
    /**
     * Get list of homepage publications
     *
     * @access public
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param int       $debug          Debug label
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getHomePublications($user, $groups = array(), $returnQuery = false, $maxResults = null, $firstResult = null, $debug = 0)
    {
        $qb = $this->createQueryBuilder('p');

        $publications = $qb
            ->leftJoin('p.author', 'a')
            ->leftJoin('a.friends', 'f')
            ->setParameters(array(
                'user'        => $user,
                'groups'      => $groups,
                'p_active'    => Post::STATUS_PUBLISHED,
                'u_active'    => $user::STATUS_TYPE_ACTIVE,
                'f_active'    => Friend::STATUS_ACCEPTED,
                'is_resource' => true,
                'now'         => new \DateTime()
            ))
            ->orderBy('p.updateDate', 'DESC')
        ;

        $module4 = $qb->expr()->andx();
        $module4->add($qb->expr()->neq('a', ':user'));
        $module4->add($qb->expr()->in('p.publishedGroup', ':groups'));

        $module3 = $qb->expr()->andx();
        $module3->add($qb->expr()->eq('f.friend', ':user'));
        $module3->add($qb->expr()->eq('f.status', ':f_active'));
        $module3->add($qb->expr()->isNull('p.publishedGroup'));

        $module2 = $qb->expr()->orx();
        $module2->add($module3);
        $module2->add($module4);

        $module1 = $qb->expr()->andx();
        $module1->add($qb->expr()->andX(
            $qb->expr()->eq('p.status', ':p_active'),
            $qb->expr()->orX(
                $qb->expr()->isNull('p.publishDate'),
                $qb->expr()->lte('p.publishDate', ':now')
            )
        ));
        
        $module1->add($qb->expr()->eq('p.resource', ':is_resource'));
        $module1->add($qb->expr()->eq('a.status', ':u_active'));
        $module1->add($module2);

        $publications
            ->andWhere($module1);
        ;

        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $publications->getQuery();
        }

        if ($debug == true) {
            echo($publications->getQuery()->getDQL());
            exit;
        }

        return $publications->getQuery()->getResult();
    }

    /**
     * Get list of profil publications
     *
     * @access public
     * @param User      $user   User object
     * @param array     $groups array of groups
     * @param Object    $me     Current user object, default to null
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param int       $debug          Debug label
     * @param boolean   $arrayMode      Whether to return array
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getProfilPublications($user, $groups = array(), $me = null, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true, $debug = 0)
    {
        $qb = $this->createQueryBuilder('p');

        // :POST with :RESOURCE(false) from :USER to a :FRIEND of :ME is not displayed for the moment
        $postFields   = 'id, name, coverimage_id, coverimage_mime, file_id, file_mime, file_name, resource, content, description, createDate';
        $authorFields = 'id, firstname, lastname, avatar_id, avatar_mime';

        $publications = $qb
            ->select('partial p.{'.$postFields.'}', 'partial a2.{'.$authorFields.'}', 'partial lp.{id}', 'partial tu.{'.$authorFields.'}')
            ->leftJoin('p.author', 'a', 'WITH', 'a = :user')
            ->leftJoin('p.coAuthors', 'ca', 'WITH', 'ca = :user')
            ->leftJoin('p.publishedGroup', 'g', 'WITH', 'g.accesstype = :g_public')
            ->leftJoin('g.parent', 'gp')
            ->leftJoin('a.friends', 'f', 'WITH', 'f.friend = :me')
            ->leftJoin('p.linkPreview', 'lp')
            ->leftJoin('p.targetUser', 'tu')
            ->join('p.author', 'a2')
            ->andWhere($qb->expr()->andX(
                $qb->expr()->eq('p.status', ':p_active'),
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.publishDate'),
                    $qb->expr()->lte('p.publishDate', ':now')
                )
            ))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        // post without published group or published in a group public
                        $qb->expr()->orX(
                            $qb->expr()->isNull('p.publishedGroup'),
                            $qb->expr()->isNotNull('g')
                        ),
                        // post targeted for the user or i'm friend with the ahtor
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull('f'),
                                $qb->expr()->eq('f.status', ':f_active')
                                ),
                            $qb->expr()->eq('p.targetUser', ':user')
                        ),
                        // post is not resource, if it is, user should be author
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->eq('p.resource', true),
                                $qb->expr()->isNotNull('a')
                                ),
                            $qb->expr()->neq('p.resource', true),
                            $qb->expr()->isNull('p.resource')
                        )
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->orX(
                            $qb->expr()->isNotNull('a'),
                            $qb->expr()->isNotNull('ca')
                        ),
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->eq('p.resource', true),
                                $qb->expr()->eq('p.author', ':user'),
                                $qb->expr()->orX(
                                    $qb->expr()->in('p.publishedGroup', ':groups'),
                                    $qb->expr()->isNotNull('g'),
                                    $qb->expr()->isNull('p.publishedGroup')
                                )
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->andX(
                                    $qb->expr()->eq('p.targetUser', ':me'),
                                    $qb->expr()->orX(
                                        $qb->expr()->in('p.publishedGroup', ':groups'),
                                        $qb->expr()->isNotNull('g'),
                                        $qb->expr()->isNull('p.publishedGroup')
                                    )
                                ),
                                $qb->expr()->neq('p.resource', true)
                            )
                        )
                    )
                )
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('gp'),
                    $qb->expr()->orX(
                        $qb->expr()->eq('gp.accesstype', ':g_public'),
                        $qb->expr()->in('gp', ':groups')
                    )
                )
            )
            ->setParameters(array(
                'me'          => $me,
                'user'        => $user,
                'groups'      => $groups,
                'g_public'    => Group::ACCESS_PUBLIC,
                'p_active'    => Post::STATUS_PUBLISHED,
                'f_active'    => Friend::STATUS_ACCEPTED,
                'now'         => new \DateTime()
                // 'u_active'    => $user::STATUS_TYPE_ACTIVE,
            ))
            //->orderBy('p.updateDate', 'DESC')
            ->orderBy('p.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            if ($arrayMode) {
                return $publications->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY);
            }

            return $publications->getQuery();
        }

        if ($debug == true) {
            echo($publications->getQuery()->getDQL());
            exit;
        }

        if ($arrayMode) {
            return $publications->getQuery()->getResult(Query::HYDRATE_ARRAY);
        }

        return $publications->getQuery()->getResult();
    }

    /**
     * Get list of user publications
     *
     * @access public
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getUserPublications($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('p');
        $publications = $qb
            ->leftJoin('p.coAuthors', 'u', 'WITH', 'u = :user')
            ->andWhere($qb->expr()->eq('p.resource', true))
            ->andWhere($qb->expr()->in('p.status', ':status'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNotNull('u'),
                $qb->expr()->eq('p.author', ':user')
            ))
            ->setParameters(array(
                'user' => $user,
                'status' => array(Post::STATUS_PUBLISHED, Post::STATUS_DRAFTING, Post::STATUS_ARCHIVED),
            ))
            ->orderBy('p.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $publications->getQuery();
        }

        return $publications->getQuery()->getResult();
    }

    /**
     * Get list of group publications
     *
     * @access public
     * @param Group     $group          Group object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param boolean   $arrayMode      Whether to return array
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getGroupPublications($group, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true)
    {
        $qb = $this->createQueryBuilder('p');

        $postFields   = 'id, name, coverimage_id, coverimage_mime, file_id, file_mime, file_name, resource, content, description, createDate';
        $authorFields = 'id, firstname, lastname, avatar_id, avatar_mime';

        $publications = $qb
            ->select('partial p.{'.$postFields.'}', 'partial a.{'.$authorFields.'}', 'partial lp.{id}')
            ->join('p.publishedGroup', 'g', 'WITH', $qb->expr()->eq('g', ':group'))
            ->join('p.author', 'a')
            ->leftJoin('p.linkPreview', 'lp')
            ->andWhere($qb->expr()->eq('p.type', Post::TYPE_GROUP))
            ->andWhere($qb->expr()->andX(
                $qb->expr()->eq('p.status', ':status'),
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.publishDate'),
                    $qb->expr()->lte('p.publishDate', ':now')
                )
            ))
            ->setParameters(array(
                'group'  => $group,
                'status' => array(Post::STATUS_PUBLISHED),
                'now'         => new \DateTime()
            ))
            ->orderBy('p.createDate', 'DESC')
            ->addOrderBy('p.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            if ($arrayMode) {
                return $publications->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY);
            }

            return $publications->getQuery();
        }

        if ($arrayMode) {
            return $publications->getQuery()->getResult(Query::HYDRATE_ARRAY);
        }

        return $publications->getQuery()->getResult();
    }
    
    /**
     * Get list of group publications not classed
     *
     * @access public
     * @param Group     $group          Group object
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getGroupPublicationsWithoutTheme($group)
    {
        $qb = $this->createQueryBuilder('p');
        $postFields   = 'id, name, resource, content, description, createDate';
        
        $publications = $qb
            ->select('partial p.{'.$postFields.'}')
            ->join('p.publishedGroup', 'g', 'WITH', $qb->expr()->eq('g', ':group'))
            ->join('p.author', 'a')
            ->leftJoin('p.theme', 'theme')
            ->andWhere($qb->expr()->isNull('theme'))
            ->andWhere($qb->expr()->eq('p.type', Post::TYPE_GROUP))
            ->andWhere($qb->expr()->in('p.status', ':status'))
            ->andWhere($qb->expr()->eq('p.resource', true))
            ->setParameters(array(
                'group'  => $group,
                'status' => array(Post::STATUS_PUBLISHED),
            ))
            ->orderBy('p.createDate', 'DESC')
            ->addOrderBy('p.id', 'DESC');
    
        return $publications->getQuery()->getResult();
    }
    
    /**
     * Get list of group headlines
     *
     * @access public
     * @param Group     $group          Group object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param boolean   $arrayMode      Whether to return array
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getGroupHeadlines($group, $returnQuery = false, $maxResults = null, $firstResult = null, $arrayMode = true, $count = false)
    {
        
        $qb = $this->createQueryBuilder('p');
    
        $postFields   = 'id, name, coverimage_id, coverimage_mime, file_id, file_mime, file_name, resource, content, description, createDate';
        $authorFields = 'id, firstname, lastname, avatar_id, avatar_mime';
        
        if ($count === true) {
            $qb->select('count(p.id)');
        } else {
            $qb->select('partial p.{'.$postFields.'}', 'partial a.{'.$authorFields.'}', 'partial lp.{id}');
        }
    
        $publications = $qb
            ->join('p.publishedGroup', 'g', 'WITH', $qb->expr()->eq('g', ':group'))
            ->leftJoin('p.headlines', 'h', 'WITH', $qb->expr()->eq('h.group', ':group'))
            ->join('p.author', 'a')
            ->leftJoin('p.linkPreview', 'lp')
            ->andWhere($qb->expr()->isNotNull('h'))
            ->andWhere($qb->expr()->eq('p.type', Post::TYPE_GROUP))
            ->andWhere($qb->expr()->in('p.status', ':status'))
            ->setParameters(array(
                'group'  => $group,
                'status' => array(Post::STATUS_PUBLISHED),
            ))
            ->orderBy('p.createDate', 'DESC')
            ->addOrderBy('p.id', 'DESC')
        ;
    
        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }
    
        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            if ($arrayMode) {
                return $publications->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY);
            }    
            return $publications->getQuery();
        }
    
        if ($arrayMode) {
            return $publications->getQuery()->getResult(Query::HYDRATE_ARRAY);
        }

        if ($count === true) {
            return $qb->getQuery()->getSingleScalarResult();   
        }
        return $publications->getQuery()->getResult();
    }

    /**
     * Get list of group publications
     *
     * @access public
     * @param Group     $group          Group object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param boolean   $arrayMode      Whether to return array
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getGroupResources($group, $user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = 'id, name, coverimage_id, file_id, coverimage_mime, file_mime', $arrayMode = true)
    {
        $qb = $this->createQueryBuilder('p');

        if (null !== $select) {
            if ($select != 'count') {
                $qb->select('partial p.{'.$select.'}');
            } else {
                $qb->select('count(p.id)');
            }
        } else {
            $qb->select('p');
        }

        $qb = $qb
            ->leftJoin('p.postMetas', 'pm', 'WITH', $qb->expr()->eq('pm.metaKey', ':pad_id'))
            ->leftJoin('p.postMetas', 'pms', 'WITH', $qb->expr()->eq('pms.metaKey', ':pad_is_closed'))
            ->leftJoin('p.author', 'a')
            ->andWhere($qb->expr()->eq('p.publishedGroup', ':group'))
            ->andWhere($qb->expr()->eq('p.type', Post::TYPE_GROUP))
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('p.status', ':status'),
                    $qb->expr()->orX(
                        $qb->expr()->isNull('p.publishDate'),
                        $qb->expr()->lte('p.publishDate', ':now')
                    )
            ))
            ->andWhere($qb->expr()->eq('p.resource', true))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('pm'),
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('pm'),
                    $qb->expr()->neq('a', ':user'),
                    $qb->expr()->eq('pms.value', 1)
                )
            ))
            ->setParameters(array(
                'group'  => $group,
                'user'   => $user,
                'status' => array(Post::STATUS_PUBLISHED),
                'pad_id' => 'pad_id',
                'pad_is_closed' => 'pad_is_closed',
                'now'         => new \DateTime()
            ))
            ->orderBy('p.id', 'DESC')
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
     * Get list of group etherpad publications
     *
     * @access public
     * @param Group     $group          Group object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param boolean   $arrayMode      Whether to return array
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getGroupPadResources($group, $user, $returnQuery = false, $maxResults = null, $select = null, $arrayMode = false)
    {
        $qb = $this->createQueryBuilder('p');

        if (null !== $select) {
            if ($select != 'count') {
                $qb->select('partial p.{'.$select.'}');
            } else {
                $qb->select('count(p.id)');
            }
        } else {
            $qb->select('p');
        }

        // we get resources pad published in the group and its sub-groups
        $groups = array($group);
//         foreach ($group->getSubGroups() as $g) {
//             $groups[] = $g;
//         }

        $qb = $qb
            ->leftJoin('p.postMetas', 'pm', 'WITH', $qb->expr()->eq('pm.metaKey', ':pad_id'))
            ->leftJoin('p.postMetas', 'pms', 'WITH', $qb->expr()->eq('pms.metaKey', ':pad_is_closed'))
            ->andWhere($qb->expr()->in('p.publishedGroup', ':groups'))
            ->andWhere($qb->expr()->eq('p.type', Post::TYPE_GROUP))
            ->andWhere($qb->expr()->eq('p.resource', true))
            ->andWhere($qb->expr()->neq('p.status', ':deleted'))
            ->andWhere($qb->expr()->isNotNull('pm.value'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('p.author', ':user'),
                    $qb->expr()->isNull('pms.value'),
                    $qb->expr()->eq('pms.value', 0)
                )
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('p.author', ':user'),   
                    $qb->expr()->orX(
                        $qb->expr()->isNull('p.publishDate'),
                        $qb->expr()->lte('p.publishDate', ':now')
                    )
                    
                )
            )
            ->setParameters(array(
                'groups'  => $groups,
                'pad_id'  => 'pad_id',
                'pad_is_closed' => 'pad_is_closed',
                'user'    => $user,
                'deleted' => Post::STATUS_DELETED,
                'now'         => new \DateTime()
            ))
            ->orderBy('p.id', 'DESC')
        ;

//         echo $qb->getQuery()->getSQL();die;
            
        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
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
     * Get the last user publications
     *
     * @access public
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getLastUserPublications($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('p');
        $publications = $qb
            ->leftJoin('p.coAuthors', 'u', 'WITH', 'u = :user')
            ->andWhere($qb->expr()->eq('p.resource', true))
            ->andWhere($qb->expr()->in('p.status', ':status'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNotNull('u'),
                $qb->expr()->eq('p.author', ':user')
            ))
            ->setParameters(array(
                'user' => $user,
                'status' => array(Post::STATUS_PUBLISHED, Post::STATUS_DRAFTING, Post::STATUS_ARCHIVED),
            ))
            ->orderBy('p.updateDate', 'DESC')
        ;

        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $publications->getQuery();
        }

        return $publications->getQuery()->getResult();
    }

    /**
     * get all publications
     *
     * @access public
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getAllPublications($returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('p');
        $publications = $qb
            ->andWhere($qb->expr()->andX(
                $qb->expr()->in('p.status', ':status'),
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.publishDate'),
                    $qb->expr()->lte('p.publishDate', ':now')
                )
            ))
            
            ->andWhere($qb->expr()->eq('p.resource', true))
            ->setParameters(array(
                'status' => array(Post::STATUS_PUBLISHED, Post::STATUS_ARCHIVED),
                'now'         => new \DateTime()
            ))
            ->orderBy('p.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $publications->getQuery();
        }

        return $publications->getQuery()->getResult();
    }

    /**
     * get popular posts
     *
     * @access public
     * @param User      $user   User object
     * @param array     $groups array of groups
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getPopularPublications($user, $groups = array(), $count, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('p');
        $publications = $qb
            ->leftJoin('p.comments', 'c')
            ->leftJoin('p.publishedGroup', 'g', 'WITH', 'g.accesstype = :g_public')
            ->andWhere('p.resource  = :resource')
            ->andWhere($qb->expr()->andX(
                $qb->expr()->eq('p.status', ':status'),
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.publishDate'),
                    $qb->expr()->lte('p.publishDate', ':now')
                )
            ))
            ->groupBy('p.id')
            ->having('COUNT(c.id) >= :count')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNotNull('g'),
                    $qb->expr()->in('g', ':groups')
                )
            )
            ->setParameters(array(
                'count'    => $count,
                'g_public' => Group::ACCESS_PUBLIC, 
                'resource' => true,
                'status'   => Post::STATUS_PUBLISHED,
                'groups'   => $groups,
                'now'         => new \DateTime()
            ))
            ->orderBy('p.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $publications->getQuery();
        }

        return $publications->getQuery()->getResult();
    }

    /**
     * get single publications
     *
     * @access public
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $id             Id of
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getSinglePublication($id, $user, $returnQuery = false)
    {
        // Get the correct Resource
        $qb = $this->createQueryBuilder('p');
        $publication = $qb
            ->leftJoin('p.coAuthors', 'u', 'WITH', 'u = :user')
            ->andWhere($qb->expr()->eq('p.id', ':id'))
            // ->andWhere($qb->expr()->eq('p.resource', true))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('p.status', ':status_published'),
                        $qb->expr()->orX(
                            $qb->expr()->isNull('p.publishDate'),
                            $qb->expr()->lte('p.publishDate', ':now')
                        )
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->eq('p.author', ':user'),
                        $qb->expr()->isNotNull('u')
                    )
                )
            )
            ->setParameters(array(
                'id'                => $id,
                'user'              => $user,
                'status_published'  => Post::STATUS_PUBLISHED,
                'now'               => new \DateTime()
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $publication;
    }
    
    /**
     * get single edit publications
     *
     * @access public
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $id             Id of post
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getSingleEditPublication($id, $user, $returnQuery = false)
    {
        // Get the correct Resource
        $qb = $this->createQueryBuilder('p');
        $publication = $qb
            ->leftJoin('p.coAuthors', 'u', 'WITH', 'u = :user')
            ->andWhere($qb->expr()->eq('p.id', ':id'))
            ->andWhere($qb->expr()->eq('p.resource', true))
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('p.status', ':status_published'),
                        $qb->expr()->eq('p.status', ':status_drafting')
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->eq('p.author', ':user'),
                        $qb->expr()->isNotNull('u')
                    )
                )
            )
            ->setParameters(array(
                'id'                => $id,
                'user'              => $user,
                'status_published'  => Post::STATUS_PUBLISHED,
                'status_drafting'   => Post::STATUS_DRAFTING
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $publication;
    }

    /**
     * Has friends who is coauthor of post
     *
     * @access public
     * @param User      $me             User object
     * @param int       $id             Id of post
     *
     * @return boolean 
     */
    public function hasFriendsCoAuthors($id, $me)
    {
        $qb = $this->createQueryBuilder('p');
        $friendsCoAuthors = $qb
            ->leftJoin('p.coAuthors', 'ca')
            ->leftJoin('ca.friends', 'f', 'WITH', 'f.friend = :me')
            ->andWhere($qb->expr()->eq('p.id', ':id'))
            ->andWhere($qb->expr()->eq('p.resource', true))
            ->andWhere($qb->expr()->eq('f.status', ':f_active'))
            ->setParameters(array(
                'id' => $id,
                'me' => $me,
                'f_active'    => Friend::STATUS_ACCEPTED,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return (null !== $friendsCoAuthors);
    }

    /**
     * get single edit publications
     *
     * @access public
     * @param boolean   $isResource     Whether is resource label
     * @param string    $startDate      Start date of creation
     * @param string    $endDate        End date of creation
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getPostsCount($isResource = false, $startDate = null, $endDate = null)
    {
        $parameters = array();
        $parameters['status'] = Post::STATUS_PUBLISHED;
        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $users = $this
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :status')
            ->setParameters($parameters)
        ;

        if ($isResource) {
            $users->andWhere('p.resource = true');
        } else {
            $users->andWhere('p.resource = false');
        }

        if (null !== $startDate && null !== $endDate) {
            $users->andWhere('p.createDate >= :startDate');
            $users->andWhere('p.createDate <= :endDate');
        } elseif (null !== $startDate) {
            $users->andWhere('p.createDate >= :startDate');
        } elseif (null !== $endDate) {
            $users->andWhere('p.createDate <= :endDate');
        }

        return $users
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * get average post number by day
     *
     * @access public
     * @param boolean   $isResource     Whether is resource label
     * @param string    $startDate      Start date of creation
     * @param string    $endDate        End date of creation
     *
     * @return int
     */
    public function getAveragePostsByDayCount($isResource = false, $startDate = null, $endDate = null)
    {
        $parameters = array();
        $parameters['status'] = Post::STATUS_PUBLISHED;
        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $theBigBang = new \DateTime('today - 30 days');
        $elapsedTime = $theBigBang->diff(new \DateTime());

        $posts = $this
            ->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->select('COUNT(p.id) / '.$elapsedTime->days)
            ->setParameters($parameters)
        ;

        if ($isResource) {
            $posts->andWhere('p.resource = true');
        } else {
            $posts->andWhere('p.resource = false');
        }

        if (null !== $startDate && null !== $endDate) {
            $posts->andWhere('p.createDate >= :startDate');
            $posts->andWhere('p.createDate <= :endDate');
        } elseif (null !== $startDate) {
            $posts->andWhere('p.createDate >= :startDate');
        } elseif (null !== $endDate) {
            $posts->andWhere('p.createDate <= :endDate');
        }
                
        return $posts
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
    
    /**
     * Get rest notifications
     *
     * @access public
     * @param number $limit    Limit of items
     * @param number $offset   Start offset of items
     * @param string $keyword  Keyword for search if exist
     * @param array  $restParameters  Other params
     * 
     * @return array
     */
    public function getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        $parameters = array();
        
        if (null !== $keyword) {
            $parameters['keyword'] = '%'.$keyword.'%';
        }
        
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'u')
        ;
        
        if ($restParameters['format'] == 'rss') {
            $qb->select('p.id AS id');
            $qb->addSelect('p.name AS title');
            $qb->addSelect('p.description AS description');
            $qb->addSelect('p.createDate AS pubDate');
            $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
        }
        
        if ($restParameters['type'] == 'resources') {
            $qb->andWhere('p.resource = true');
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
     * Get the one rest post by id
     * 
     * @access public
     * @param string    $id     Folder id
     * @return Post|null 
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
    public function getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        if($link_type == 'user_publications') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            $parameters['status'] = Post::STATUS_PUBLISHED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.author', 'u')
                ->andWhere('p.resource = true')
                ->andWhere('p.author = :user')
                ->andWhere('p.status = :status')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('p.id AS id');
                $qb->addSelect('p.name AS title');
                $qb->addSelect('p.description AS description');
                $qb->addSelect('p.createDate AS pubDate');
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
        } elseif($link_type == 'user_friends_publications') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            $parameters['status'] = Post::STATUS_PUBLISHED;
            $parameters['friend_status'] = Friend::STATUS_ACCEPTED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('p')
                ->andWhere('p.resource = true')
                ->leftJoin('p.author', 'u')
                ->leftJoin('u.friends', 'f')
                ->andWhere('f.friend = :user')
                ->andWhere('f.status = :friend_status')
                ->andWhere('p.status = :status')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('p.id AS id');
                $qb->addSelect('p.name AS title');
                $qb->addSelect('p.description AS description');
                $qb->addSelect('p.createDate AS pubDate');
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
        } elseif($link_type == 'group_publications') {
            $parameters = array();
            $parameters['group'] = $main_object_id;
            $parameters['status'] = Post::STATUS_PUBLISHED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('p')
                ->andWhere('p.resource = true')
                ->andWhere('p.publishedGroup = :group')
                ->andWhere('p.status = :status')
                ->leftJoin('p.author', 'u')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('p.id AS id');
                $qb->addSelect('p.name AS title');
                $qb->addSelect('p.description AS description');
                $qb->addSelect('p.createDate AS pubDate');
                $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
            }
            
            /*if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }*/
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        } elseif($link_type == 'page_publications') {
            $parameters = array();
            $parameters['page'] = $main_object_id;
            $parameters['status'] = Post::STATUS_PUBLISHED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('p')
                ->andWhere('p.resource = true')
                ->andWhere('p.publishedEditor = :page')
                ->andWhere('p.status = :status')
                ->leftJoin('p.author', 'u')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('p.id AS id');
                $qb->addSelect('p.name AS title');
                $qb->addSelect('p.description AS description');
                $qb->addSelect('p.createDate AS pubDate');
                $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
            }
            
            /*if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }*/
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        } elseif($link_type == 'user_activities') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            $parameters['status'] = Post::STATUS_PUBLISHED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.author', 'u')
                ->andWhere('p.author = :user')
                ->andWhere('p.status = :status')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('p.id AS id');
                $qb->addSelect('p.name AS title');
                $qb->addSelect('p.description AS description');
                $qb->addSelect('p.createDate AS pubDate');
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
        } elseif($link_type == 'group_activities') {
            $parameters = array();
            $parameters['group'] = $main_object_id;
            $parameters['status'] = Post::STATUS_PUBLISHED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('p')
                ->andWhere('p.publishedGroup = :group')
                ->andWhere('p.status = :status')
                ->leftJoin('p.author', 'u')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('p.id AS id');
                $qb->addSelect('p.name AS title');
                $qb->addSelect('p.description AS description');
                $qb->addSelect('p.createDate AS pubDate');
                $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
            }
            
            /*if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }*/
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        } elseif($link_type == 'page_activities') {
            $parameters = array();
            $parameters['page'] = $main_object_id;
            $parameters['status'] = Post::STATUS_PUBLISHED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('p')
                ->andWhere('p.publishedEditor = :page')
                ->andWhere('p.status = :status')
            ;
            
            /*if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }*/
            
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
}
