<?php
namespace Rpe\PumBundle\Model\Rpe;

/**
 * @method string getObject($type = null)
 * @method boolean setObject($object, $type = null)
 *
 */
abstract class RespireMigration
{   
    /**
     * getObject
     * 
     * @access public
     * @param string $type  Name of the field
     *
     * @return string
     */
    public function getObject($type = null)
    {
        $method = 'get'.$type;
        
        return $this->$method();
    }
    
    /**
     * set object value
     * 
     * @access public
     * @param string $object  Name of the object
     * @param string $type    Name of the field
     *
     * @return boolean
     */
    public function setObject($object, $type = null)
    {
        $method = 'set'.$type;
        
        if ($this->$method($object))
        {
            return true;
        }
        
        return false;
    }
}
