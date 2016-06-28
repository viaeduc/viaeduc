<?php
namespace Rpe\PumBundle\Repository\Rpe;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Rpe\Folder;

/**
 * @method \Doctrine\ORM\Query|array    getUserFolders($user, $type = Folder::TYPE_MEDIA, $returnQuery = false, $maxResults = null, $firstResult = null, $debug = 0)
 * @method array                        getRest($limit = 10, $offset = 0, $keyword = null)
 * @method array                        getUserFolderByName($user, $folder_name)
 * @method Folder|null                  getOneRest($id)
 */
class FolderRepository extends ObjectRepository
{
    /**
     * Get list of user folders
     * 
     * @access public
     * @param User      $user   User object
     * @param string    $type   Type of media
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param int       $debug          Debug label
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getUserFolders($user, $type = Folder::TYPE_MEDIA, $returnQuery = false, $maxResults = null, $firstResult = null, $debug = 0)
    {
        $qb = $this->createQueryBuilder('f');
        $folders = $qb
            ->andWhere($qb->expr()->eq('f.user', ':user'))
            ->setParameters(array(
                'user'  =>  $user
            ))
            ->orderBy('f.sequence', 'ASC')
            ->addOrderBy('f.id', 'ASC')
        ;

        if (null !== $maxResults) {
            $folders->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $folders->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $folders->getQuery();
        }

        return $folders->getQuery()->getResult();
    }
    
    /**
     * Get user folder by name
     * 
     * @param User      $user   User object
     * @param string    $folder_name   Name of the folder
     * @return array 
     */
    public function getUserFolderByName($user, $folder_name)
    {
        $qb = $this->createQueryBuilder('f');
        $folders = $qb
            ->andWhere($qb->expr()->eq('f.user', ':user'))
            ->andWhere('f.name = :folder_name')
            ->setParameters(array(
                'user'  =>  $user,
                'folder_name'  =>  $folder_name,
            ))
            ->orderBy('f.sequence', 'ASC')
            ->addOrderBy('f.id', 'ASC')
        ;
        $folder = $folders->getQuery()->getResult();
        return reset($folder);
    }
    
    
    /**
     * Get rest folder
     * 
     * @access public
     * @param number $limit
     * @param number $offset
     * @param string $keyword  Keyword for search if exist
     * @return array 
     */
    public function getRest($limit = 10, $offset = 0, $keyword = null)
    {
        $parameters = array();
        
        if (null !== $keyword) {
            $parameters['keyword'] = '%'.$keyword.'%';
        }
        
        $qb = $this->createQueryBuilder('f');
        
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
    
    
    /**
     * Get the one rest folder by id
     * 
     * @access public
     * @param string    $id     Folder id
     * @return Folder|null 
     */
    public function getOneRest($id)
    {
        $user = $this
            ->createQueryBuilder('f')
            ->andWhere('f.id = :id')
            ->setParameters(array('id' => $id))
            ->getQuery()
            ->getOneOrNullResult()
        ;
        
        return $user;
    }
}
