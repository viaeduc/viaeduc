<?php
namespace Rpe\PumBundle\Model\Social;

/**
 * @method array    serializeList($storage, $httpHost)  
 * @method array    serialize($storage, $httpHost) 
 *
 */
abstract class Message
{
    /**
     * Status
     */
    const STATUS_EDITED   = 'EDITED';
    
    /**
     * serializeList
     * 
     * @access public
     * @param Object      $storage   Storage object
     * @param string      $httpHost  Http host
     * 
     * @return array
     * 
     */
    public function serializeList($storage, $httpHost, $parameters = null)
    {
        $outObject = array(
            'message_id' => $this->getId(), 
            'author' => null, 
            'message' => $this->getContent(), 
            'date' => $this->getDate(), 
        );
        
        $author = $this->getAuthor();
        
        $outObject['author'] = array(
            'id' => $author->getId(), 
            'firstname' => $author->getFirstname(), 
            'lastname' => $author->getLastname(), 
            'profile_picture_url' => $httpHost.$storage->getWebPath($author->getAvatar(), true)
        );
        
        return $outObject;
    }
    
    /**
     * serialize
     * 
     * @access public
     * @param Object      $storage   Storage object
     * @param string      $httpHost  Http host
     * 
     * @return array
     * 
     */
    public function serialize($storage, $httpHost, $parameters = null)
    {
        $outObject = array(
            'message_id' => $this->getId(), 
            'author' => null, 
            'message' => $this->getContent(), 
            'date' => $this->getDate(), 
        );
        
        $author = $this->getAuthor();
        
        $outObject['author'] = array(
            'id' => $author->getId(), 
            'firstname' => $author->getFirstname(), 
            'lastname' => $author->getLastname(), 
            'profile_picture_url' => $httpHost.$storage->getWebPath($author->getAvatar(), true)
        );
        
        return $outObject;
    }
}
