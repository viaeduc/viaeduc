<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\UserInEditor;
use Rpe\PumBundle\Model\Social\Product;

/**
 * @see Pum\Core\Object\ObjectRepository
 *
 */
class ProductRepository extends ObjectRepository
{
    /**
     * Get list of products in eidtor
     *
     * @access public
     * @param User      $user           User object
     * @param Editor    $editor         Editor object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getProductsInEditor($user, $editor, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('p');
    
        $qb = $qb->leftJoin('p.editor', 'e', 'WITH', $qb->expr()->eq('e', ':editor'))
                 ->leftJoin('e.users', 'u', 'WITH', $qb->expr()->eq('u.user', ':me'))
                 ->andWhere($qb->expr()->isNotNull('e'))
                 ->andWhere(
                     $qb->expr()->orX(
                         $qb->expr()->eq('u.status', ':status_owner'),
                         $qb->expr()->neq('p.status', ':status_draft')
                 ))
                 ->setParameters(array(
                     'me' => $user,
                     'editor' => $editor,
                     'status_owner' => UserInEditor::STATUS_OWNER,
                     'status_draft' => Product::STATUS_DRAFTING
                 ))
        ;
            
    
        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }
    
        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }
    
        if ($returnQuery) {
            return $qb->getQuery();
        }
        
//         echo $user->getId();
        
//         echo $qb->getQuery()->getSQL();die;
        return $qb->getQuery()->getResult();
    }
}
