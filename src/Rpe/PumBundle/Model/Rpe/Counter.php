<?php
namespace Rpe\PumBundle\Model\Rpe;

/**
 * Count class
 * 
 * @method Count increment()
 * @method Count decrement()
 */
abstract class Counter
{
    /**
     * @access public
     * @return Counter 
     *
     */
    public function increment()
    {
        $this->value++;

        return $this;
    }

    /**
     * @access public
     * @return Counter 
     *
     */
    public function decrement()
    {
        $this->value--;

        return $this;
    }
}
