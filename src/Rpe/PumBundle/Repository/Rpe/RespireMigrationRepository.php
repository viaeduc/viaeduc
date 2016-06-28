<?php
namespace Rpe\PumBundle\Repository\Rpe;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\User;

/**
 * @method  array   getRpeByType($id = null, $type = null)
 * @method  array   getRespireByType($id = null, $type = null)
 * @method  \Doctrine\ORM\mixed              getCountRespireUsers()
 * @method  \Doctrine\ORM\Query|multitype:   getRespireUsers($returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  array   getRpeByType($id = null, $type = null)
 * @method  \Doctrine\ORM\mixed              getCountRespireRappelUsers()
 */
class RespireMigrationRepository extends ObjectRepository
{
    /**
     * Get respire by type
     *
     * @access public
     * @param string $id        Rpe object id
     * @param string $type      Respire migration type
     *
     * @return array 
     */
    public function getRpeByType($id = null, $type = null)
    {
        $qb = $this->createQueryBuilder('rm');
        $relations = $qb
            ->andWhere('rm.type = :type')
            ->andWhere('rm.rpeId = :id')
            ->setParameters(array(
                'type'    =>  $type,
                'id'    =>  $id
            ))
        ;

        return $relations->getQuery()->getResult();
    }

    /**
     * Get respire by type
     *
     * @access public
     * @param string $id        Respire migration id
     * @param string $type      Respire migration type
     *
     * @return array 
     */
    public function getRespireByType($id = null, $type = null)
    {
        $qb = $this->createQueryBuilder('rm');

        $relations = $qb
                ->andWhere('rm.type = :type')
                ->andWhere('rm.respireId = :id')
                ->setParameters(array(
                    'type'    =>  $type,
                    'id'    =>  $id
                ))
            ;

        return $relations->getQuery()->getResult();
    }

    
    /**
     * get count all respire users with status awaiting confirmation
     * 
     * @access public
     * @return \Doctrine\ORM\mixed
     */
    public function getCountRespireUsers()
    {
        $qb = $this->createQueryBuilder('rm');
        $collection = $qb
            ->select('COUNT(rm.id)')
            ->andWhere('rm.type = :type')
            ->andWhere('rm.status NOT LIKE :status')
            ->setParameters(array(
                'type'    =>  "user",
                'status'   =>  "%mailed%"
            ))
        ;

        return $collection
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    
    /**
     * get all respire users with status awaiting confirmation
     * 
     * @access public
     * @param string $returnQuery
     * @param string $maxResults
     * @param string $firstResult
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getRespireUsers($returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('rm');
        $collection = $qb
            ->andWhere('rm.type = :type')
            ->andWhere('rm.status NOT LIKE :status')
            ->setParameters(array(
                'type'    =>  "user",
                'status'   =>  "%mailed%"
            ))
            ->orderBy('rm.id', 'ASC')
        ;

        if (null !== $maxResults) {
            $collection->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $collection->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $collection->getQuery();
        }

        return $collection->getQuery()->getResult();
    }
    
    
    /**
     * get count all respire users not transfered
     * 
     * @access public
     * @return \Doctrine\ORM\mixed
     */
    public function getCountRespireRappelUsers()
    {
        $qb = $this->createQueryBuilder('rm');
        $collection = $qb
            ->select('COUNT(rm.id)')
            ->leftJoin('rm.user', 'u')
            ->andWhere('rm.type = :type')
            ->andWhere('rm.status NOT LIKE :status')
            ->andWhere('u.status != :status_active')
            ->setParameters(array(
                'type'    =>  "user",
                'status'   =>  "%transfered%",
                'status_active' => User::STATUS_TYPE_ACTIVE
            ))
        ;
    
        return $collection
        ->getQuery()
        ->getSingleScalarResult()
        ;
    }
    
    
    /**
     * get all respire users not transfered
     *
     * @access public
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getRespireRappelUsers($returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('rm');
        $collection = $qb
            ->leftJoin('rm.user', 'u')
            ->andWhere('rm.type = :type')
            ->andWhere('rm.status NOT LIKE :status')
            ->andWhere('rm.status NOT LIKE :status_rappel')
            ->andWhere('u.status != :status_active')
            ->setParameters(array(
                'type'    =>  "user",
                'status'   =>  "%transfered%",
                'status_rappel' => "%rappel%",
                'status_active' => User::STATUS_TYPE_ACTIVE
                
            ))
            ->orderBy('rm.id', 'ASC')
        ;
    
        if (null !== $maxResults) {
            $collection->setMaxResults($maxResults);
        }
    
        if (null !== $firstResult) {
            $collection->setFirstResult($firstResult);
        }
    
        if ($returnQuery) {
            return $collection->getQuery();
        }
    
        return $collection->getQuery()->getResult();
    }
}
