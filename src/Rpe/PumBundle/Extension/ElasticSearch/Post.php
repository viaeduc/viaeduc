<?php
namespace Rpe\PumBundle\Extension\ElasticSearch;

use Symfony\Component\HttpFoundation\Request;
use Rpe\PumBundle\Model\Social\User as RpeUser;

/**
 * @method array getSelectFields()
 * @method array getHighlightFields()
 * @method array getFacetsFields()
 * @method Search createSearch()
 *
 * @see SearchFactory
 *
 */
class Post extends SearchFactory
{
    const SEARCH_TYPE = 'post';

    /**
     * create search factory
     *
     * @access public
     * @return Search
     *
     * @see \Rpe\PumBundle\Extension\ElasticSearch\SearchFactory::createSearch()
     */
    public function createSearch()
    {
        return $this->searchEngine->createSearch()->type(self::SEARCH_TYPE);
    }

    /**
     * Return selected fields
     *
     * @access public
     * @return array
     *
     * @see \Rpe\PumBundle\Extension\ElasticSearch\SearchFactory::getSelectFields()
     */
    public function getSelectFields()
    {
        return array('name', 'description', 'recommend', 'share', 'date', 'update', 'image', 'status', 'author_name', 'author_image', 'author_id', 'group_name', 'group_id', 'resource', 'is_respire');
    }

    /**
     * Return high light fields
     *
     * @access public
     * @return array
     *
     * @see \Rpe\PumBundle\Extension\ElasticSearch\SearchFactory::getHighlightFields()
     */
    public function getHighlightFields()
    {
        return array('name','description');
    }
    
    /**
     * Return facet fields
     *
     * @access public
     * @return array
     *
     * @see \Rpe\PumBundle\Extension\ElasticSearch\SearchFactory::getFacetsFields()
     */
    public function getFacetsFields()
    {
        return array(
            'disciplines'     => 'terms',
            'teaching_levels' => 'terms',
            'language'        => 'terms',
            'date'            => 'date_histogram',
        );
    }
}
