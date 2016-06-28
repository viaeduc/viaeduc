<?php
namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;
use Rpe\PumBundle\Model\Social\UserInDiscussion;

/**
 * 
 *
 */
abstract class Invitation
{
    const INVITATION_COUNTER  = 5;

    /**
     * Status
     */
    const STATUS_AWAITING  = 'AWAITING';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_CANCELLED = 'CANCELLED';
}
