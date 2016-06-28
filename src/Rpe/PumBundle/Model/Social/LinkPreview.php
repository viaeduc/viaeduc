<?php
namespace Rpe\PumBundle\Model\Social;

/**
 * @method boolean isUpdated($ttl = 2419200)
 * @method array   getFirstImage()
 * @method array   getAllImage()
 * @method string  getVideoCode($with = '100%', $height = '400', $autostart = true)
 * @method array   getData()
 *
 */
abstract class LinkPreview
{
    /**
     * isUpdated check the last update is 3 days ago
     *
     * @access public
     * @param int $ttl TTL 
     *
     * @return boolean
     */
    public function isUpdated($ttl = 2419200)
    {
        if (time() < (int)($this->getLastUpdate()->getTimestamp() + $ttl)) {
            return true;
        }

        return false;
    }

    /**
     * getFirstImage
     *
     * @access public
     * @return array
     */
    public function getFirstImage()
    {
        $images = unserialize($this->getImages());

        if (!empty($images)) {
            return reset($images);
        }

        return null;
    }

    /**
     * getAllImage
     *
     * @access public
     * @return array  return all images in an array
     */
    public function getAllImage()
    {
        return unserialize($this->getImages());
    }

    /**
     * getVideoCode get video code with params
     *
     * @access public
     * @param string  $with
     * @param $height $height
     * @param boolean $autostart
     *
     * @return string 
     */
    public function getVideoCode($with = '100%', $height = '400', $autostart = true)
    {
        $autostart = ($autostart) ? '?autoplay=1' : '';

        return str_replace(array('%video_width%', '%video_height%', '%video_autostart%'), array($with, $height, $autostart), $this->getVideoIframe());
    }

    /**
     * getData
     *
     * @access public
     * @return array 
     *
     */
    public function getData()
    {
        return array(
            "id"           => $this->getId(),
            "title"        => $this->getTitle(),
            "url"          => $this->getUrl(),
            "canonicalUrl" => $this->getCanonicalUrl(),
            "description"  => $this->getDescription(),
            "images"       => unserialize($this->getImages()),
            "video"        => $this->getVideo(),
            "videoIframe"  => $this->getVideoIframe()
        );
    }
}
