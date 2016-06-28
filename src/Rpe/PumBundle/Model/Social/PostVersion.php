<?php
namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;

abstract class PostVersion
{
    /**
     * Status
     */
    const STATUS_DRAFTING  = 'DRAFTING';
    const STATUS_PUBLISHED = 'PUBLISHED';
    const STATUS_ARCHIVED  = 'ARCHIVED';
    const STATUS_DELETED   = 'DELETED';
}
