<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\UserInBlog;
use Rpe\PumBundle\Model\Social\User;
use Doctrine\ORM\ORMException;

/**
 * @method \Doctrine\ORM\mixed getUserInBlog($user, $blog, $status = null)
 * @method array               getUserListInBlog($blog)
 * @method array               getUserListByStatus($blog, $status = null)
 * @method array               getActiveUserInBlog($idBlog)
 */
class UserInBlogRepository extends ObjectRepository
{
    /**
     * get user_in_group status
     * 
     * @access public
     * @param User      $user   User object
     * @param Blog      $blog   Blog object
     * @param string $status    In blog status
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getUserInBlog($user, $blog, $status = null)
    {
        $user_in_blog = $this
            ->createQueryBuilder('f')
            ->leftJoin('f.user', 'g')
            ->andWhere('f.user = :user')
            ->andWhere('f.blog = :blog')
            ->setParameters(array(
                'user'  => $user,
                'blog' => $blog,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $user_in_blog;
    }

    /**
     * get user_list in blog
     * 
     * @access public
     * @param Blog      $blog   Blog object
     * @param string $status    In blog status
     * 
     * @return array Array contain user in blog
     */
    public function getUserListInBlog($blog)
    {
        $users_in_blog = $this
            ->createQueryBuilder('uig')
            ->select('IDENTITY(uig.user)')
            ->andWhere('uig.blog = :blog')
            ->setParameters(array(
                'blog' => $blog,
            ))
            ->getQuery()
            ->getArrayResult()
        ;

        return $users_in_blog;
    }

    /**
     * get user_list by status
     * 
     * @access public
     * @param Blog      $blog   Blog object
     * @param string $status    In blog status
     * 
     * @return array Array contain user in blog
     */
    public function getUserListByStatus($blog, $status = null)
    {
        $parameters = array(
            'blog' => $blog
        );
        
        if (null !== $status) {
            $parameters['status'] = $status;
        }
        
        $users_in_blog = $this
            ->createQueryBuilder('uib')
            ->andWhere('uib.blog = :blog')
        ;
        
        if (null !== $status) {
            $users_in_blog->andWhere('uig.status = :status');
        }
        
        $users_in_blog = $users_in_blog->setParameters($parameters)
            ->getQuery()
            ->getResult()
        ;
        
        return $users_in_blog;
    }

    /**
     * get active user in blog
     * 
     * @access public
     * @param string $idBlog    id of blog
     * 
     * @return array Array contain user in blog
     */
    public function getActiveUserInBlog($idBlog)
    {
        $user_in_blog = $this
            ->createQueryBuilder('uib')
            ->select('u.id')
            ->andWhere('uib.blog = :idBlog')
            ->andWhere('uib.status <= :uib_inblog')
            ->leftJoin('uib.user', 'u')
            ->andWhere('u.status = :status')
            ->setParameters(array('idBlog' => $idBlog, 'status' => 'ACTIVE', 'uib_inblog' => UserInBlog::IN_BLOG))
            ->getQuery()
            ->getArrayResult()
        ;
        return $user_in_blog;
    }
}
