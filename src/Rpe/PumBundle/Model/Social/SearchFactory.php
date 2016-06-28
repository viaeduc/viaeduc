<?php
namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;
use Pum\Core\Extension\Validation\Constraints\Date;

/**
 * Search basic factory class
 * 
 * @method null     getSearchQ()
 * @method null     getSearchQBis()
 * @method null     getSearchQTer()
 * @method string   getFieldname($field, $property = 'name', $default = null)
 * @method string   getImageField($field, $property = 'name', $default = null)
 * @method array    getArrayFieldname($field, $property = 'name', $default = null)
 * @method Date     getFormattedDate($field, $default = null)
 * @method string   getFormattedText($field, $default = null)
 * @method string   getFormattedSort($field, $default = null)
 * @method boolean  isVisible()
 * 
 */
abstract class SearchFactory
{
    const SOLR_DATE_FORMAT = 'Y-m-d\TG:i:s\Z';

    /**
     * Get search query parameter, to be extended
     *
     * @return null
     */
    public function getSearchQ()
    {
        return null;
    }

    /**
     * Get search query bis parameter, to be extended
     *
     * @return null
     */
    public function getSearchQBis()
    {
        return null;
    }

    /**
     * Get search query tier parameter, to be extended
     *
     * @return null
     */
    public function getSearchQTer()
    {
        return null;
    }

    /**
     * getFieldname of search
     * 
     * @access public
     * @param string $field    Field name
     * @param string $property Property name
     * @param string $default  Default name if null
     *
     * @return string
     */
    public function getFieldname($field, $property = 'name', $default = null)
    {
        $method = 'get'.ucfirst($field);

        if (null === $obj = $this->$method()) {
            return $default;
        }

        $method = 'get'.ucfirst($property);

        return $obj->$method();
    }

    /**
     * Get the image field value
     * 
     * @access public
     * @param string $field    Field name
     * @param string $property Property name
     * @param string $default  Default name if null
     *
     * @return string
     */
    public function getImageField($field, $property = 'name', $default = null)
    {
        $method = 'get'.ucfirst($field);
        if ((null === $obj = $this->$method()) || (false === $obj->isImage())) {
            return $default;
        }

        $method = 'get'.ucfirst($property);

        return $obj->$method();
    }

    /**
     * Get field value as array
     * 
     * @access public
     * @param string $field    Field name
     * @param string $property Property name
     * @param string $default  Default name if null
     *
     * @return array 
     */
    public function getArrayFieldname($field, $property = 'name', $default = null)
    {
        $method = 'get'.ucfirst($field);

        if ($this->$method()->count() === 0) {
            return $default;
        }

        $results = array();
        $_method  = 'get'.ucfirst($property);
        foreach ($this->$method() as $obj) {
            $results[] = $obj->$_method();
        }

        return $results;
    }

    /**
     * Get date field in formatted string
     * 
     * @access public
     * @param string $field    Field name
     * @param string $default  Default name if null
     *
     * @return Date 
     */
    public function getFormattedDate($field, $default = null)
    {
        $method = 'get'.ucfirst($field);

        if (null === $obj = $this->$method()) {
            return $default;
        }

        return date(self::SOLR_DATE_FORMAT, $obj->getTimestamp());
    }

    /**
     * Format the text, trip tags
     * 
     * @access public
     * @param string $field    Field name
     * @param string $default  Default name if null
     *
     * @return string 
     */
    public function getFormattedText($field, $default = null)
    {
        $method = 'get'.ucfirst($field);

        if (null === $obj = $this->$method()) {
            return $default;
        }

        return strip_tags($obj);
    }


    /**
     * Formatted sort
     * 
	 * @access public
     * @param string  $field       Field name to get
     * @param string  $default     Default value if null
     * 
     * @return string Return formatted string
     */
    public function getFormattedSort($field, $default = null)
    {
        $method = 'get'.ucfirst($field);

        if (null === $obj = $this->$method()) {
            return $default;
        }

        return trim(mb_strtolower($obj, 'UTF-8'));
    }

    /**
	 * @access public
     * @return boolean
     */
    public function isVisible()
    {
        return true;
    }
}
