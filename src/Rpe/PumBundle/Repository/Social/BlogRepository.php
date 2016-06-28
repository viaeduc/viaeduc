<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Blog;

/**
 * @author \Doctrine\ORM\Query|multitype:   getAllBlogs($returnQuery = false, $maxResults = null, $firstResult = null)
 * @author \Doctrine\ORM\Query|multitype:   getPublicBlogs($returnQuery = false, $maxResults = null, $firstResult = null)
 * @author \Doctrine\ORM\Query|multitype:   getPrivateBlogs($returnQuery = false, $maxResults = null, $firstResult = null)
 * @author \Doctrine\ORM\Query|multitype:   getFollowedBlogs($user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = 'b')
 */
class BlogRepository extends ObjectRepository
{
    
    /**
     * get all blogs
     * 
     * @access public
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getAllBlogs($returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('b');
        $blogs = $qb
            ->andWhere($qb->expr()->in('b.accesstype', ':accesstypes'))
            ->setParameters(array(
                'accesstypes' => array(Blog::ACCESS_PUBLIC, Blog::ACCESS_FRIENDS),
            ))
            ->orderBy('b.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $blogs->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $blogs->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $blogs->getQuery();
        }

        return $blogs->getQuery()->getResult();
    }

    /**
     * get public blogs
     *
     * @access public
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getPublicBlogs($returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('b');
        $blogs = $qb
            ->andWhere('b.accesstype = :accesstype')
            ->setParameters(array(
                'accesstype' => Blog::ACCESS_PUBLIC,
            ))
            ->orderBy('b.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $blogs->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $blogs->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $blogs->getQuery();
        }

        return $blogs->getQuery()->getResult();
    }

    /**
     * get private blogs
     *
     * @access public
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getPrivateBlogs($returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('b');
        $blogs = $qb
            ->andWhere('b.accesstype = :accesstype')
            ->setParameters(array(
                'accesstype' => Blog::ACCESS_FRIENDS,
            ))
            ->orderBy('b.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $blogs->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $blogs->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $blogs->getQuery();
        }

        return $blogs->getQuery()->getResult();
    }

    /**
     * get followed blogs by user
     *
     * @access public
     * @param user      $user           User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getFollowedBlogs($user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = 'b')
    {
        $qb = $this->createQueryBuilder('b');

        $blogs = $qb
            ->select($select)
            ->leftJoin('b.bookmarkby', 'bm')
            ->andWhere('bm.user = :me')
            ->andWhere('b.accesstype != :accesstype')
            ->setParameters(array(
                'me' => $user,
                'accesstype' => Blog::ACCESS_PRIVATE,
            ))
            ->orderBy('b.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $blogs->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $blogs->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $blogs->getQuery();
        }

        return $blogs->getQuery()->getResult();
    }
}
