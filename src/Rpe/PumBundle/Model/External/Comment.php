<?php
namespace Rpe\PumBundle\Model\External;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\Criteria;

/**
 * Abstract class Comment
 * 
 * @method boolean isLike($user)
 * @method Criteria handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 *
 */
abstract class Comment
{

    /**
     * status type
     */
    const STATUS_OK            = 'OK';
    const STATUS_EDITED        = 'EDITED';

    /**
     * Check if comment is liked by user
     *
     * @access public
     * @param User $user    User object
     *
     * @return boolean
     */
    public function isLike($user)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('user', $user));
        $criteria = $this->handleCriteria($criteria, array(), 1, null);

        if ($this->recommendby->matching($criteria)->count() === 0) {
            return false;
        }

        return true;
    }

    /**
     * Handle criterias 
     *
     * @access public
     * @param Criteria $criteria    Criteria object
     * @param array    $orderBy     Order array
     * @param int      $limite      Number of limit
     * @param int      $offset      Start offset for retrieve
     *
     * @return Criteria 
     */
    private function handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
    {
        if (null !== $limite) {
            $criteria->setMaxResults($limite);
        }

        if (null !== $offset) {
            $criteria->setFirstResult($offset);
        }

        if (null === $orderBy || empty($orderBy)) {
            $criteria->orderBy(array('id' => Criteria::DESC));
        } else {
            $criteria->orderBy($orderBy);
        }

        return $criteria;
    }
}
