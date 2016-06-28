<?php
namespace Rpe\PumBundle\Extension\Service;

use Hashids\Hashids;
use Pum\Bundle\CoreBundle\PumContext;


/**
 * Service for hash user id
 * 
 * @method boolean|string checkHash($id, $object)
 * @method boolean|string encode($id)
 * @method Object         getRepository($objectName)
 */
class RpeHashId 
{
    /**
     * @var HashIds $hashTool
     * @var string  $hashKey
     * @var PumContext  $context
     */
    private $hashTool;
    private $hashKey;
    private $context;
    
    public function __construct($hashkey, PumContext $context)
    {
        $this->hashKey = $hashkey;
        $this->context = $context;
        
        if (is_null($this->hashTool)) {
            $this->hashTool = new Hashids($this->hashKey, 6);
        }
    } 
    
    /**
     * Check a string hashed is available or not
     * 
     * @param string $id
     * @param string $object
     * 
     * @return boolean|string
     */
    public function checkHash($id, $object)
    {
        $pair = explode('-', $id);
        if (count($pair) == 2) {
            if ($pair[1] === $this->hashTool->encode($pair[0])) {
                return $pair[0];
            }
        }
        return false;
    }
    
    /**
     * Encode an id
     * 
     * @param unknown $id
     */
    public function encode($id)
    {
        if (is_null($id)) {
            return null;
        }
        
        if (filter_var($id, FILTER_VALIDATE_INT)) {
            
            return $id . "-" . $this->hashTool->encode($id);
        }
        return false; 
    }
    
    /**
     * shortcut to get repository on pum object
     *
     * @param string $objectName Object name to get
     * @return Object $objectName repository
     * @access private
     */
    private function getRepository($objectName)
    {
        return $this->context->getProjectOEM()->getRepository($objectName);
    }
}
