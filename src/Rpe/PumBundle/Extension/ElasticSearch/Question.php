<?php
namespace Rpe\PumBundle\Extension\ElasticSearch;


use Symfony\Component\HttpFoundation\Request;
use Rpe\PumBundle\Model\Social\User as RpeUser;
use Pum\Core\Extension\Search\Search;

/**
 * @method array getSelectFields()
 * @method array getHighlightFields()
 * @method array getFacetsFields()
 * @method Search createSearch()
 *
 * @see SearchFactory
 */
class Question extends SearchFactory
{
    const SEARCH_TYPE = 'question';

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
        return array('name', 'description', 'answers', 'keywords', 'question_access', 'date', 'author_name', 'author_image', 'author_id');
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
            'question_access'        => 'terms',
            'instructed_disciplines' => 'terms',
            'keywords'               => 'terms',
            'date'                   => 'date_histogram',
            'answers'                => 'range',
        );
    }
}
