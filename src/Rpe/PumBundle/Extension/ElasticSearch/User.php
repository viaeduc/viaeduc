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
class User extends SearchFactory
{
    const SEARCH_TYPE = 'user';

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
        return array('q', 'fullname', 'occupation', 'school', 'academy', 'date', 'sex', 'image', 'instructed_disciplines', 'is_respire');
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
        return array('fullname');
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
            'sex'                    => 'terms',
            'academy'                => 'terms',
            'occupation'             => 'terms',
            'interests'              => 'terms',
            'instructed_disciplines' => 'terms',
            'teaching_levels'        => 'terms',
            'date'                   => 'date_histogram',
        );
    }
}
