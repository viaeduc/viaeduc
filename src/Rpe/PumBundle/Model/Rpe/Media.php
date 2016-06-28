<?php
namespace Rpe\PumBundle\Model\Rpe;

/**
 * Media class
 *
 * @method array serializeList($storage, $httpHost)
 * @method array serialize($storage, $httpHost)
 *
 */
abstract class Media
{
    /**
     * Media Type
     */
    const TYPE_POST  = 'POST';
	
    
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
            'media_id' => $this->getId(), 
            'name' => $this->getName(), 
            'description' => $this->getDescription(), 
            'date' => $this->getDate(), 
            'file' => array('url' => $httpHost.$storage->getWebPath($this->getMedia(), false), 'type' => $this->getMedia()->getMime()), 
            'folder' => array('id' => $this->getFolder()->getId(), 'name' => $this->getFolder()->getName()), 
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
            'media_id' => $this->getId(), 
            'name' => $this->getName(), 
            'description' => $this->getDescription(), 
            'date' => $this->getDate(), 
            'file' => array('url' => $httpHost.$storage->getWebPath($this->getMedia(), false), 'type' => $this->getMedia()->getMime()), 
            'folder' => array('id' => $this->getFolder()->getId(), 'name' => $this->getFolder()->getName()), 
        );

        return $outObject;
    }
}
