<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\UserInEditor;

/**
 * @method \Doctrine\ORM\Query|multitype: getAllEditors($returnQuery = false, $maxResults = null, $firstResult = null)
 * @method \Doctrine\ORM\Query|multitype: getFollowedEditors($user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = 'e')
 * @method array                          getRest($limit = 10, $offset = 0, $keyword = null, $restParameters)
 * @method Editor|null                    getOneRest($id)
 */
class EditorRepository extends ObjectRepository
{
    
    /**
     * get all editors
     * 
     * @access public
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getAllEditors($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('e');
        $editors = $qb
            ->leftJoin('e.users', 'owner', 'WITH', $qb->expr()->eq('owner.user', ':user'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('owner.status', ':status'),
                    $qb->expr()->eq('e.active', 1)
                )
            )
            ->setParameters(array(
                'user'   => $user,
                'status' => UserInEditor::STATUS_OWNER
            ))
            ->orderBy('e.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $editors->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $editors->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $editors->getQuery();
        }

        return $editors->getQuery()->getResult();
    }

    /**
     * get followed editors
     *
     * @access public
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param string    $select         Select item
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getFollowedEditors($user, $returnQuery = false, $maxResults = null, $firstResult = null, $select = 'e')
    {
        $qb = $this->createQueryBuilder('e');
        $editors = $qb
            ->select($select)
            ->leftJoin('e.users', 'owner', 'WITH', $qb->expr()->eq('owner.user', ':me'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('owner.status', ':status'),
                    $qb->expr()->eq('e.active', 1)
                )
            )
            ->leftJoin('e.bookmarkby', 'bm')
            ->andWhere('bm.user = :me')
            ->setParameters(array(
                'me' => $user,
                'status' => UserInEditor::STATUS_OWNER
            ))
            ->orderBy('e.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $editors->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $editors->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $editors->getQuery();
        }

        return $editors->getQuery()->getResult();
    }

    /**
     * Get rest fields
     *
     * @access public
     * @param number $limit
     * @param number $offset
     * @param string $keyword  Keyword for search if exist
     * @param string $restParameters  Other parameters
     * 
     * @return array
     */
    public function getRest($limit = 10, $offset = 0, $keyword = null, $restParameters)
    {
        $parameters = array();
        
        if (null !== $keyword) {
            $parameters['keyword'] = '%'.$keyword.'%';
        }
        
        $qb = $this->createQueryBuilder('p');
        
        if ($restParameters['format'] == 'rss') {
            $qb->select('p.id AS id');
            $qb->addSelect('p.name AS title');
            $qb->addSelect('p.description AS description');
            $qb->addSelect('p.createDate AS pubDate');
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
     * Get only one editor
     * 
     * @access public
     * @param string    $id     Editor id
     * 
     * @return Editor|null
     */
    public function getOneRest($id)
    {
        $qb = $this
            ->createQueryBuilder('r')
            ->andWhere('r.id = :id')
            ->setParameters(array('id' => $id))
            ->getQuery()
            ->getOneOrNullResult()
        ;
        
        return $qb;
    }
}
