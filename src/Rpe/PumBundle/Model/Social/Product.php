<?php
namespace Rpe\PumBundle\Model\Social;

abstract class Product
{
    /**
     * Status
     */
    const STATUS_DRAFTING  = 'DRAFTING';
    const STATUS_PUBLISHED = 'PUBLISHED';
    
    /**
     * isVisible
     * @return boolean
     * @access public
     */
    public function isVisible()
    {
        return $this->getStatus() !== self::STATUS_DRAFTING;
    }
}
