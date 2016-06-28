<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Rpe\Theme;

/**
 *
 */
class ThemeRepository extends ObjectRepository
{

    /**
     * Get list of group theme
     *
     * @access public
     * @param Group     $group          Group object
     * @param string    $type   Type of media
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * @param int       $debug          Debug label
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getGroupThemes($group, $returnQuery = false, $maxResults = null, $firstResult = null, $debug = 0)
    {
        $qb = $this->createQueryBuilder('th');
        $thems = $qb
            ->andWhere($qb->expr()->eq('th.group', ':group'))
            ->setParameters(array(
                'group'  =>  $group
            ));

        if (null !== $maxResults) {
            $thems->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $thems->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $thems->getQuery();
        }

        return $thems->getQuery()->getResult();
    }
}
