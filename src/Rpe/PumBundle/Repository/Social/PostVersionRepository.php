<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\PostVersion;

/**
 * @method \Doctrine\ORM\Query|multitype:   getPostVersion($version_id, $post_id, $returnQuery = false)
 *
 */
class PostVersionRepository extends ObjectRepository
{
    /**
     * Get post versions of a post
     *
     * @access public
     * @param boolean   $returnQuery    Return query or not
     * @param int       $version_id     Version id
     * @param int       $post_id        Post id if exist
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getPostVersion($version_id, $post_id, $returnQuery = false)
    {
        $qb = $this->createQueryBuilder('v');
        $version = $qb
            ->select('v')
            ->andWhere($qb->expr()->eq('v.id', ':version_id'))
            ->andWhere($qb->expr()->eq('v.post', ':post_id'))
            ->setParameters(array(
                'version_id' => $version_id,
                'post_id'    => $post_id
            ))
            ->setMaxResults(1)
            ->getQuery()
        ;

        if ($returnQuery) {
            return $version;
        }

        return $version->getOneOrNullResult();

    }
}
