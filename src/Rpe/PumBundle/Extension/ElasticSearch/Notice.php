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
class Notice extends SearchFactory
{
    const SEARCH_TYPE = 'external_notice';

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
        return array('title', 'description', 'date', 'levels', 'disciplines', 'update_date', 'issn', 'notice_category', 'commercial_catches', 'url', 'picture', 'source');
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
        return array('title', 'description');
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
            'source'          => 'terms',
            'levels'          => 'terms',
            'disciplines'     => 'terms',
            'notice_category' => 'terms',
            'date'            => 'date_histogram',
        );
    }
}
