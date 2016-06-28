<?php
namespace Rpe\PumBundle\Model\Rpe;

/**
 * Folder class
 * 
 * @method array serializeList($storage, $httpHost)
 * @method array serialize($storage, $httpHost)
 *
 */
abstract class Folder
{
    /**
     * folder Type
     */
    const TYPE_MEDIA    = 'MEDIA';
    const TYPE_RESOURCE = 'RESOURCE';
    
    /**
     * serializeList
     *
     * @access public
     * @param object $storage The storage object
     * @param string $httpHost  Http host string
     *
     * @return array Array of datas
     */
    public function serializeList($storage, $httpHost)
    {
        $outObject = array(
            'folder_id' => $this->getId(), 
            'name' => $this->getName(), 
            'date' => null, // @TODO
        );
        
        return $outObject;
    }
    
    /**
     * serialize
     *
     * @access public
     * @param $storage
     * @param $httpHost
     *
     * @return array
     */
    public function serialize($storage, $httpHost)
    {
        $outObject = array(
            'folder_id' => $this->getId(), 
            'name' => $this->getName(), 
            'date' => null, // @TODO
        );
        
        return $outObject;
    }
}
