<?php
namespace Rpe\PumBundle\Model\Social;

/**
 * @method boolean isReported()
 * @method boolean isRejected()
 * @method boolean isConfirmed()
 * @method boolean isTreated()
 * 
 */
abstract class Report
{
    /**
     * Status
     */
    const STATUS_REPORTED  = 1;
    const STATUS_REJECTED  = 2;
    const STATUS_CONFIRMED  = 3;
    const STATUS_TREADTED  = 4;

    /**
     * If is reported
     * 
     * @return boolean
     * @access public
     */
    public function isReported()
    {
        if ($this->getStatus() == self::STATUS_REPORTED) {
            return true;
        }

        return false;
    }

    /**
     * If is rejected
     * 
     * @return boolean
     * @access public
     */
    public function isRejected()
    {
        if ($this->getStatus() == self::STATUS_REJECTED) {
            return true;
        }

        return false;
    }

    /**
     * If report is confirmed
     * 
     * @return boolean
     * @access public
     */
    public function isConfirmed()
    {
        if ($this->getStatus() == self::STATUS_CONFIRMED) {
            return true;
        }

        return false;
    }

    /**
     * If report is treated
     * 
     * @return boolean
     * @access public
     */
    public function isTreated()
    {
        if ($this->getStatus() == self::STATUS_TREADTED) {
            return true;
        }

        return false;
    }
}
