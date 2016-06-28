<?php

namespace Rpe\PumBundle\Extension\ElasticSearch;

use Symfony\Component\HttpFoundation\Request;
use Rpe\PumBundle\Model\Social\User as RpeUser;
use Pum\Core\Extension\Search\SearchEngine;
use Rpe\PumBundle\Extension\ElasticSearch\Group;
use Rpe\PumBundle\Extension\ElasticSearch\Post;
use Rpe\PumBundle\Extension\ElasticSearch\Question;
use Rpe\PumBundle\Extension\ElasticSearch\User;

/**
 * @method void __construct(SearchEngine $searchEngine)
 * @method array getFacetsFields()
 * @method Search createSearch()
 * 
 * @see SearchFactory
 *
 */
class Overall extends SearchFactory
{
    /**
     * @var string  $SEARCH_TYPE
     */
    protected $SEARCH_TYPE;

    /**
     * initialize parameters
     *
     * @return void
     *
     * @see \Rpe\PumBundle\Extension\ElasticSearch\SearchFactory::createSearch()
     */
    public function __construct(SearchEngine $searchEngine)
    {
        parent::__construct($searchEngine);
        
        $this->SEARCH_TYPE = implode(',', array(
            Group::SEARCH_TYPE,
            Post::SEARCH_TYPE,
            Question::SEARCH_TYPE,
            User::SEARCH_TYPE,
            Blog::SEARCH_TYPE
        ));
    }

    
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
        return $this->searchEngine->createSearch()->type($this->SEARCH_TYPE);
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
            'date' => 'date_histogram',
        );
    }
}
