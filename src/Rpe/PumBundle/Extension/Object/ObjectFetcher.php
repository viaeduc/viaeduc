<?php
namespace Rpe\PumBundle\Extension\Object;

use Pum\Bundle\CoreBundle\PumContext;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Friend;
use Rpe\PumBundle\Model\Social\UserInGroup;
use Doctrine\ORM\Query;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @method ObjectManager    getOEM()
 * @method Object           getObjectClass($name)
 * @method QueryBuilder     createQueryBuilder()
 * @method Query|array      getHomePublications($user, $groups = array(), $returnQuery = false, $maxResults = null, $firstResult = null, $debug = 0)
 * @method Query|array      getProfilGroups($profil, $user,  $returnQuery = false, $maxResults = null, $firstResult = null, $select = null)
 * @method Query|array      getProfilResources($profil, $user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = null, $arrayMode = false)
 * @method int              countRelation($objectName, $relationName, $id)
 * @method int              isLike($user_id, $objectName, $id)
 * @method int              isBookmark($user_id, $objectName, $id)
 * @method Object|null      getRelation($objectName, $relationName, $id, $select = null)
 * @method Object|null      getEntity($objectName, $id, $select = null)
 *
 */
class ObjectFetcher
{
    /**
     * @var PumContext  $context    Pum context object
     */
    private $context;

    public function __construct(PumContext $context)
    {
        $this->context = $context;
    }

    /**
     * Get object manager
     *
     * @access public
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getOEM()
    {
        return $this->context->getProjectOEM();
    }

    /**
     * Get object class name
     *
     * @access public
     * @param  string $name Name of the class
     *
     * @return Object
     */
    public function getObjectClass($name)
    {
        return $this->getOEM()->getObjectClass($name);
    }

    /**
     * Get query builder object
     *
     * @access public
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->getOEM()->createQueryBuilder();
    }


    /**
     * Get publications at home page
     *
     * @access public
     * @param User      $user
     * @param array     $groups
     * @param boolean   $returnQuery
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param int       $debug          Debug label
     *
     * @return Query|array
     */
    public function getHomePublications($user, $groups = array(), $returnQuery = false, $maxResults = null, $firstResult = null, $debug = 0)
    {
        $qb = $this->createQueryBuilder();

        $postFields       = 'id, name, file_id, file_mime, broadcast, updateDate, publishDate';
        $authorFields     = 'id, avatar_id, avatar_mime, firstname, lastname';
        $groupFields      = 'id, name';
        $occupationFields = 'id, name';

        $publications = $qb
            ->select('partial p.{'.$postFields.'}', 'partial a.{'.$authorFields.'}', 'partial pg.{'.$groupFields.'}', 'partial o.{'.$occupationFields.'}')
            ->from($this->getObjectClass('post'), 'p')
            ->leftJoin('p.author', 'a', 'WITH', $qb->expr()->andX($qb->expr()->neq('a', ':user'), $qb->expr()->eq('a.status', ':u_active')))
            ->leftJoin('p.publishedGroup', 'pg', 'WITH', $qb->expr()->in('p.publishedGroup', ':groups'))
            ->leftJoin('a.occupation', 'o')
            ->leftJoin('a.friends', 'f', 'WITH', $qb->expr()->andX($qb->expr()->eq('f.friend', ':user'), $qb->expr()->eq('f.status', ':f_active')))
            ->groupBy('p.id')
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

        $module4 = $qb->expr()->andX();
        $module4->add($qb->expr()->neq('a', ':user'));
        $module4->add($qb->expr()->in('p.publishedGroup', ':groups'));

        $module3 = $qb->expr()->andX();
        $module3->add($qb->expr()->eq('f.status', ':f_active'));
        $module3->add($qb->expr()->isNull('p.publishedGroup'));

        $module2 = $qb->expr()->orX();
        $module2->add($module3);
        $module2->add($module4);

        $module1 = $qb->expr()->andX();
        $module1->add($qb->expr()->andX(
            $qb->expr()->eq('p.status', ':p_active'),
            $qb->expr()->orX(
                $qb->expr()->isNull('p.publishDate'),
                $qb->expr()->lte('p.publishDate', ':now')
            )
        ));
        $module1->add($qb->expr()->eq('p.resource', ':is_resource'));
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

        if ($debug == true) {
            echo($publications->getQuery()->getDQL());
            exit;
        }

        if ($returnQuery) {
            return $publications->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY);
        }

        return $publications->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    /**
     * Get groups for a profil
     *
     * @access public
     * @param User      $profil
     * @param User      $user
     * @param boolean   $returnQuery
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param string    $select         Select fields
     *
     * @return Query|array
     */
    public function getProfilGroups($profil, $user,  $returnQuery = false, $maxResults = null, $firstResult = null, $select = null)
    {
        $qb = $this->createQueryBuilder();

        if (null !== $select) {
            if ($select != 'count') {
                $qb->select('DISTINCT partial g.{'.$select.'}');
            } else {
                $qb->select('count(DISTINCT g.id)');
            }
        } else {
            $qb->select('DISTINCT g');
        }

        if ($profil !== $user) {
            $secretsGroups = $this->createQueryBuilder('g')
                ->select('g.id')
                ->from($this->getObjectClass('group'), 'g')
                ->join('g.users', 'uig', 'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq('uig.user', ':user'),
                        $qb->expr()->lte('uig.status', ':uig_accepted')
                    )
                )
                ->andWhere($qb->expr()->eq('g.accesstype', ':accesstype'))
                ->setParameters(array(
                    'user'         => $user,
                    'uig_accepted' => UserInGroup::IN_GROUP,
                    'accesstype'   => Group::ACCESS_ON_INVITATION,
                ))
                ->getQuery()
                ->getResult()
            ;

            $qb
                ->from($this->getObjectClass('group'), 'g')
                ->join('g.users', 'uig', 'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq('uig.user', ':profil'),
                        $qb->expr()->lte('uig.status', ':uig_accepted')
                    )
                )
                ->leftJoin('g.parent', 'gp')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->neq('g.accesstype', ':accesstype'),
                        $qb->expr()->in('g.id', ':secretsGroups')
                    )
                )
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('gp'),
                        $qb->expr()->andX(
                            $qb->expr()->isNotNull('gp'),
                            $qb->expr()->orX(
                                $qb->expr()->neq('gp.accesstype', ':accesstype'),
                                $qb->expr()->in('gp.id', ':secretsGroups')
                            )
                        )
                    )
                )
                ->setParameters(array(
                    'profil'        => $profil,
                    'uig_accepted'  => UserInGroup::IN_GROUP,
                    'accesstype'    => Group::ACCESS_ON_INVITATION,
                    'secretsGroups' => $secretsGroups
                ))
            ;

        } else {
            $qb
                ->from($this->getObjectClass('group'), 'g')
                ->join('g.users', 'uig', 'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq('uig.user', ':profil'),
                        $qb->expr()->lte('uig.status', ':uig_accepted')
                    )
                )
                ->setParameters(array(
                    'profil'        => $profil,
                    'uig_accepted'  => UserInGroup::IN_GROUP,
                ))
            ;
        }

        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY);
        }

        if ($select != 'count') {
            $qb = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        } else {
            $qb = $qb->getQuery()->getSingleScalarResult();
        }

        return $qb;
    }

    /**
     * Get resources for a profil
     *
     * @access public
     * @param User      $profil
     * @param User      $user
     * @param boolean   $returnQuery
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param string    $select         Select fields
     * @param boolean   $arrayMode      Return array or not
     *
     * @return Query|array
     */

    public function getProfilResources($profil, $user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = null, $arrayMode = false)
    {
        $qb = $this->createQueryBuilder('p');

        if (null !== $select) {
            if ($select != 'count') {
                $qb->select('DISTINCT partial p.{'.$select.'}');
            } else {
                $qb->select('count(DISTINCT p.id)');
            }
        } else {
            $qb->select('DISTINCT p');
        }

        if ($profil === $user) {
            $qb
                ->from($this->getObjectClass('post'), 'p')
                ->leftJoin('p.coAuthors', 'ca', 'WITH', 'ca = :profil')
                ->andWhere($qb->expr()->eq('p.status', ':p_active'))
                ->andWhere($qb->expr()->eq('p.resource', true))
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->eq('p.author', ':profil'),
                        $qb->expr()->isNotNull('ca')
                    )
                )
                ->setParameters(array(
                    'profil'   => $profil,
                    'p_active' => Post::STATUS_PUBLISHED,
                ))
                ->orderBy('p.id', 'DESC')
            ;
        } else {
            $groups = $this->createQueryBuilder('g')
                ->select('g.id')
                ->from($this->getObjectClass('group'), 'g')
                ->leftJoin('g.users', 'uig', 'WITH', 'uig.user = :me')
                ->andWhere('uig.status <= :uig_accepted')
                ->setParameters(array(
                    'me' => $user,
                    'uig_accepted' => UserInGroup::IN_GROUP,
                ))
                ->getQuery()
                ->getResult()
            ;

            $qb
                ->from($this->getObjectClass('post'), 'p')
                ->leftJoin('p.publishedGroup', 'g', 'WITH', 'g.accesstype = :g_public')
                ->leftJoin('p.coAuthors', 'ca', 'WITH', 'ca = :profil')
                ->andWhere($qb->expr()->eq('p.status', ':p_active'))
                ->andWhere($qb->expr()->eq('p.resource', true))
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->eq('p.author', ':profil'),
                        $qb->expr()->isNotNull('ca')
                    )
                );

            if ($user->isInvited()) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        // Dans aucun group
                        $qb->expr()->isNull('p.publishedGroup'),
                        // Dans un de mes groups
                        $qb->expr()->andX(
                            $qb->expr()->isNull('g'),
                            $qb->expr()->in('p.publishedGroup', ':groups')
                        )
                    )
                );
            } else {
                $qb->andWhere(
                    $qb->expr()->orX(
                        // Dans un group public
                        $qb->expr()->isNotNull('g'),
                        // Dans aucun group
                        $qb->expr()->isNull('p.publishedGroup'),
                        // Dans un de mes groups
                        $qb->expr()->andX(
                            $qb->expr()->isNull('g'),
                            $qb->expr()->in('p.publishedGroup', ':groups')
                        )
                    )
                );
            }
            $qb->setParameters(array(
                    'profil'   => $profil,
                    'groups'   => $groups,
                    'g_public' => Group::ACCESS_PUBLIC,
                    'p_active' => Post::STATUS_PUBLISHED,
                ))
                ->orderBy('p.id', 'DESC')
            ;
        }

        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }

        if ($returnQuery) {
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
     * count relation of a object
     *
     * @access public
     * @param string $objectName    name of object
     * @param string $relationName  name of relation
     * @param string $id
     *
     * @return int
     */
    public function countRelation($objectName, $relationName, $id)
    {
        $qb = $this->createQueryBuilder();

        return $qb
            ->select('COUNT(r.id)')
            ->from($this->getObjectClass($objectName), 'o')
            ->join('o.'.$relationName, 'r')
            ->andWhere($qb->expr()->eq('o.id', ':id'))
            ->setParameters(array(
                'id' => $id
            ))
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Is object liked by user
     *
     * @access public
     * @param string $user_id       Id of user
     * @param string $objectName    Name of the object
     * @param string $id
     *
     * @return int
     */
    public function isLike($user_id, $objectName, $id)
    {
        $qb = $this->createQueryBuilder();

        return $qb
            ->select('COUNT(r.id)')
            ->from($this->getObjectClass($objectName), 'o')
            ->join('o.recommendby', 'r', 'WITH', $qb->expr()->eq('r.user', ':user_id'))
            ->andWhere($qb->expr()->eq('o.id', ':id'))
            ->setParameters(array(
                'id'      => $id,
                'user_id' => $user_id
            ))
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Is object bookmarked by user
     *
     * @access public
     * @param string $user_id       Id of user
     * @param string $objectName    Name of the object
     * @param string $id
     *
     * @return int
     */
    public function isBookmark($user_id, $objectName, $id)
    {
        $qb = $this->createQueryBuilder();

        return $qb
            ->select('COUNT(r.id)')
            ->from($this->getObjectClass($objectName), 'o')
            ->join('o.bookmarkby', 'r', 'WITH', $qb->expr()->eq('r.user', ':user_id'))
            ->andWhere($qb->expr()->eq('o.id', ':id'))
            ->setParameters(array(
                'id'      => $id,
                'user_id' => $user_id
            ))
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * get relation of object
     *
     * @access public
     * @param string $relationName  Name of relation
     * @param string $objectName    Name of the object
     * @param string $id
     * @param string $select        Select fields
     *
     * @return Object|null
     */
    public function getRelation($objectName, $relationName, $id, $select = null)
    {
        $qb = $this->createQueryBuilder();

        if (null !== $select) {
            $qb->select('partial o.{'.$select.'}');
        } else {
            $qb->select('o');
        }

        $qb = $qb
            ->from($this->getObjectClass($objectName), 'o')
            ->join('o.'.$relationName, 'r', 'WITH', $qb->expr()->eq('r.id', ':id'))
            ->setParameters(array(
                'id' => $id
            ))
            ->getQuery()
        ;

        return $qb->getOneOrNullResult(Query::HYDRATE_ARRAY);
    }

    /**
     * Get an entity instance
     *
     * @access public
     * @param string $relationName  Name of relation
     * @param string $objectName    Name of the object
     * @param string $id
     * @param string $select        Select fields
     *
     * @return Object|null
     */
    public function getEntity($objectName, $id, $select = null)
    {
        $qb = $this->createQueryBuilder();

        if (null !== $select) {
            $qb->select('partial o.{'.$select.'}');
        } else {
            $qb->select('o');
        }

        $qb = $qb
            ->from($this->getObjectClass($objectName), 'o')
            ->andWhere($qb->expr()->eq('o.id', ':id'))
            ->setParameters(array(
                'id' => $id
            ))
            ->getQuery()
        ;

        return $qb->getOneOrNullResult(Query::HYDRATE_ARRAY);
    }
}
