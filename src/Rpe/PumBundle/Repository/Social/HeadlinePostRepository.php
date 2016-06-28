<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method \Doctrine\ORM\mixed getHeadline($post, $group)
 *
 */
class HeadlinePostRepository extends ObjectRepository
{
    /**
     * get headline
     * 
     * @access public
     * @param Post   $post        Post object
     * @param Group  $group       Group object
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getHeadline($post, $group)
    {
        $headline = $this
            ->createQueryBuilder('hl')
            ->andWhere('hl.post = :post')
            ->andWhere('hl.group = :group')
            ->setParameters(array(
                'post'  => $post,
                'group' => $group,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        return $headline;
    }
}
