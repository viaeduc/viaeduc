<?php
namespace Rpe\PumBundle\Extension\ElasticSearch;

use Symfony\Component\HttpFoundation\Request;
use Rpe\PumBundle\Model\Social\User as RpeUser;
use Pum\Core\Extension\Search\SearchEngine;
use Pum\Core\Extension\Search\Search;
use Pum\Core\Extension\Search\Query\Query;
use Pum\Core\Extension\Util\Namer;
use Pum\Core\Extension\Search\Highlight\Highlight;
use Pum\Core\Extension\Search\Result\Count;
use Pum\Core\Extension\Search\Facet\Facet;

/**
 * 
 * @method void         __construct(SearchEngine $searchEngine)
 * @method array        getQ(Request $request)
 * @method Search       createSearch()
 * @method Query        createQuery($type, $value = null)
 * @method Highlight    createHighlight($fields = array())
 * @method Facet        createFacet($type = 'terms', $field = null, $name = null)
 * @method Count        count(Request $request, RpeUser $user)
 * @method Query        getQuery(Request $request, RpeUser $user)
 * @method Query        getRequestQuery(Request $request, RpeUser $user)
 * @method Search       getSearch(Request $request)
 * @method Query        addFacetsFilters(Query $query, Request $request, $facets = array())
 * @method Search       addFacets(Search $search)
 * @method array        getSelectFields()
 * @method array        getHighlightFields()
 * @method array        getFacetsFields()
 * @method array        getSortField($field)
 * @method Search       addFacet(Search $search, $type, $name)
 * @method Query        createFacetQuery(Query $facetsFilter, $type, $key, $values)
 * @method array        perPageValues()
 * @method string       urlDecode($values)
 * @method int          getFuzziness($q)
 * @method string       parseStr($str)
 * 
 * @see SearchFactory
 *
 */
abstract class SearchFactory
{
    const DEFAULT_PER_PAGE = 10;
    const DEFAULT_SORT_BY  = 'asc';

    const SOLR_DATE_FORMAT = 'Y-m-d\TG:i:s\Z';

    /**
     * @var SearchEngine    $searchEngine
     */
    protected $searchEngine;

    /**
     * Construct function
     * 
     * @access public
     * @param SearchEngine $searchEngine
     * @return void
     */
    public function __construct(SearchEngine $searchEngine)
    {
        $this->searchEngine = $searchEngine;
    }

    /**
     * Get request query array
     * 
     * @access public
     * @param Request $request  A request instance
     * 
     * @return array 
     */
    public function getQ(Request $request)
    {
        return array(
            $this->parseStr($request->query->get('q', '')),
            $this->parseStr($request->query->get('q_all', '')),
            $this->parseStr($request->query->get('q_expr', '')),
            $this->parseStr($request->query->get('q_one', '')),
            $this->parseStr($request->query->get('q_exclude', ''))
        );
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
        return $this->searchEngine->createSearch();
    }

    /**
     * Create query instance for elastic
     * 
     * @access public
     * @param string $type
     * @param string $value
     * 
     * @return Query
     */
    public function createQuery($type, $value = null)
    {
        return $this->searchEngine->createQuery($type, $value);
    }

    /**
     * Return high light fields
     *
     * @access public
     * @param  array $fields Fields to highlight
     * 
     * @return Highlight
     *
     */
    public function createHighlight($fields = array())
    {
        return $this->searchEngine->createHighlight($fields);
    }

    /**
     * Return facet fields
     *
     * @access public
     * @param  array $fields Fields to highlight
     *
     * @return Facet
     *
     */
    public function createFacet($type = 'terms', $field = null, $name = null)
    {
        $name = (null === $name) ? $field : $name;

        return $this->searchEngine->createFacet($type, $name)->setField($field);
    }

    /**
     * Count the result number
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  RpeUser $user
     * @return Count 
     */
    public function count(Request $request, RpeUser $user)
    {
        return $this->createSearch()->setQuery($this->getRequestQuery($request, $user))->count();
    }

    /**
     * Get query object
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  RpeUser $user
     * @return Query
     */
    public function getQuery(Request $request, RpeUser $user)
    {
        // Query
        $query = $this->getRequestQuery($request, $user);

        // Add Facets Filters to Query
        $query = $this->addFacetsFilters($query, $request, $this->getFacetsFields());

        // Search
        $search = $this->getSearch($request)->setQuery($query);

        // Add Facets to Search
        $search = $this->addFacets($search);

        return $search;
    }

    /**
     * Get query object filtered by parameter
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  RpeUser $user
     * 
     * @return Query
     */
    public function getRequestQuery(Request $request, RpeUser $user)
    {
        // Request
        list($q, $q_all, $q_expr, $q_one, $q_exclude) = $this->getQ($request);

        // Query
        $query     = $this->createQuery('filtered');
        $boolQuery = $this->createQuery('bool');

        if ($q) { // Search

            $shouldQuery = $this->createQuery('match')->setField('q')->setMatch($q)->setBoost(500);
            $boolQuery->addShould($shouldQuery);

            $shouldQuery = $this->createQuery('match')->setField('q_bis')->setMatch($q)->setBoost(499);
            $boolQuery->addShould($shouldQuery);

            $shouldQuery = $this->createQuery('match')->setField('q_ter')->setMatch($q)->setBoost(498);
            $boolQuery->addShould($shouldQuery);

            $shouldQuery = $this->createQuery('wildcard')->setField('q')->setMatch('*'.$q.'*')->setBoost(1000);
            $boolQuery->addShould($shouldQuery);

            $shouldQuery = $this->createQuery('wildcard')->setField('q_bis')->setMatch('*'.$q.'*')->setBoost(999);
            $boolQuery->addShould($shouldQuery);

            $shouldQuery = $this->createQuery('wildcard')->setField('q_ter')->setMatch('*'.$q.'*')->setBoost(998);
            $boolQuery->addShould($shouldQuery);

            $fuzziness = $this->getFuzziness($q);

            /* Deprecated fuzzy and not boost match */
            /*$shouldQuery = $this->createQuery('fuzzy_like_this')->addField('q')->setMatch($q)->setFuzziness($fuzziness)->setBoost(1);
            $boolQuery->addShould($shouldQuery);

            $shouldQuery = $this->createQuery('fuzzy_like_this')->addField('q_bis')->setMatch($q)->setFuzziness($fuzziness)->setBoost(0.9);
            $boolQuery->addShould($shouldQuery);

            $shouldQuery = $this->createQuery('fuzzy_like_this')->addField('q_ter')->setMatch($q)->setFuzziness($fuzziness)->setBoost(0.8);
            $boolQuery->addShould($shouldQuery);*/

        } elseif ($q_all || $q_expr || $q_one || $q_exclude) { // Advanced Search

            if ($q_all) {
                $words = str_replace(",", " ", $q_all);
                $words = preg_replace("/\s+/", " ", $words);
                $words = explode(' ', $words);

                foreach ($words as $word) {
                    $mustQuery = $this->createQuery('multi_match')->addField('q^3')->addField('q_bis^2')->addField('q_ter')->setMatch($word);

                    $boolQuery->addMust($mustQuery);
                }
            }

            if ($q_expr) {
                $expr = preg_replace("/\s+/", " ", $q_expr);

                $mustQuery = $this->createQuery('multi_match')->addField('q^3')->addField('q_bis^2')->addField('q_ter')->setMatch($expr)->setType('phrase');

                $boolQuery->addMust($mustQuery);
            }

            if ($q_one) {
                $words = str_replace(",", " ", $q_one);
                $words = preg_replace("/\s+/", " ", $words);

                $mustQuery = $this->createQuery('multi_match')->addField('q^3')->addField('q_bis^2')->addField('q_ter')->setMatch($words);

                $boolQuery->addMust($mustQuery);
            }

            if ($q_exclude) {
                $words = str_replace(",", " ", $q_exclude);
                $words = preg_replace("/\s+/", " ", $words);
                $words = explode(' ', $words);

                foreach ($words as $word) {
                    $mustNotQuery = $this->createQuery('multi_match')->addField('q^3')->addField('q_bis^2')->addField('q_ter')->setMatch($word);

                    $boolQuery->addMustNot($mustNotQuery);
                }
            }

        }

        // Set Query
        $query->setQuery($boolQuery);

        // Filter by visibility
        $visibleQuery    = $this->createQuery('term')->setField('visible')->setTerm(true);
        $visibilityQuery = $this->createQuery('terms')->setField('visibility')->addTerms($user->getAccess());

        $filterBoolQuery = $this->createQuery('bool')->addMust($visibleQuery)->addMust($visibilityQuery);

        $query->setFilter($filterBoolQuery);

        return $query;
    }

    /**
     * Get search object according to query parameters
     *
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Search
     */
    public function getSearch(Request $request)
    {
        $sort       = $request->query->get('sort', null);
        $sortby     = $request->query->get('sortby', self::DEFAULT_SORT_BY);
        $page       = $request->query->get('p', 1);
        $per_page   = $request->query->get('per_page', self::DEFAULT_PER_PAGE);
        $fields     = $this->getSelectFields();
        $highlights = $this->getHighlightFields();

        if (!in_array($per_page, $this->perPageValues())) {
            $request->query->set('per_page', self::DEFAULT_PER_PAGE);
            $per_page = self::DEFAULT_PER_PAGE;
        }

        $order = null;
        if (null !== $sort = $this->getSortField($sort)) {
            $order = array($sort => $sortby);
        }

        if (!$page) {
            $page = 1;
        }

        $search = $this->createSearch()
            ->perPage($per_page)
            ->page($page)
        ;

        if (!empty($fields)) {
            $search->select($fields);
        }

        if (!empty($highlights)) {
            $search->setHighlight($this->createHighlight($highlights)->setTagsSchema('styled'));
        }

        if (null !== $order) {
            $search->addSort($order);
        }

        return $search;
    }

    /**
     * Add filters to facet
     * 
     * @param Query $query
     * @param Request $request
     * @param array   $facets
     * @return Query
     */
    public function addFacetsFilters(Query $query, Request $request, $facets = array())
    {
        if (!$query instanceof \Pum\Core\Extension\Search\Query\Filtered) {
            return $query;
        }

        if (null === $facetsFilter = $query->getFilter()) {
            $facetsFilter = $this->createQuery('bool');
        }

        $hasQuery = false;

        foreach ($facets as $facet_key => $type) {
            $values = $request->query->get($facet_key, null);

            if (null !== $values) {
                $hasQuery     = true;
                $values       = $this->urlDecode($values);
                $facetsFilter = $this->createFacetQuery($facetsFilter, $type, $facet_key, $values);
            }
        }

        if ($hasQuery) {
            $query->setFilter($facetsFilter);
        }

        return $query;
    }

    /**
     * Add filters to facet
     *
     * @access public
     * @param Search $search
     * 
     * @return Search
     */
    public function addFacets(Search $search)
    {
        foreach ($this->getFacetsFields() as $name => $type) {
            $search = $this->addFacet($search, $type, $name);
        }

        return $search;
    }

    /**
     * Return selected fields
     *
     * @access public
     * @return array
     */
    public function getSelectFields()
    {
        return array();
    }

    /**
     * Return high lighted fields
     *
     * @access public
     * @return array
     */
    public function getHighlightFields()
    {
        return array();
    }

    /**
     * Return facet fields
     *
     * @access public
     * @return array
     */
    public function getFacetsFields()
    {
        return array();
    }

    /**
     * Return facet fields
     *
     * @access public
     * @return array
     */
    public function getSortField($field)
    {
        if (!in_array($field, array('name', 'fullname', 'date', 'create', 'update', 'answer'))) {
            return null;
        }

        if (in_array($field, array('name', 'fullname'))) {
            return $field.'_raw';
        }

        return $field;
    }

    
    /**
     * Add a facet for search object
     * 
     * @access public
     * @param Search $search    The search object
     * @param string $type      Type of search
     * @param string $name      Type of search
     * 
     * @return Search
     */
    public function addFacet(Search $search, $type, $name)
    {
        $facet = $this->createFacet($type, $name);

        switch ($type) {
            case 'range':
                switch ($name) {
                    default:
                        $facet
                            ->addRange(array('from' => 0, 'to' => 10))
                            ->addRange(array('from' => 10, 'to' => 25))
                            ->addRange(array('from' => 25, 'to' => 50))
                            ->addRange(array('from' => 50, 'to' => 100))
                            ->addRange(array('from' => 100))
                        ;
                        break;
                }
                break;

            case 'date_histogram':
                switch ($name) {
                    default:
                        $facet
                            ->setInterval('year')
                        ;
                }
                break;
            case 'terms':
                $facet->setSize(999);
                break;
        }

        $search->addFacet($facet);

        return $search;
    }

    /**
     * Create a query containing facet
     * 
     * @param Query     $facetsFilter
     * @param string    $type   Facet type  
     * @param string    $key    Facet key
     * @param string    $values Facet value
     * 
     * @return Query
     */
    public function createFacetQuery(Query $facetsFilter, $type, $key, $values)
    {
        switch ($type) {
            case 'terms':
                    $facetQuery = $this->createQuery($type)->setField($key)->addTerms(array_values($values))->matchAll();

                    $facetsFilter->addMust($facetQuery);
                break;

            case 'range':
                    foreach ($values as $value) {
                        $facetQuery = $this->createQuery($type)->setField($key);
                        $ranges     = explode('_', $value);

                        if (false !== strpos($value, 'from')) {
                            $facetQuery->addRange('gte', (int) explode('from', $ranges[0])[1]);
                        }
                         
                        if (false !== strpos($value, 'from')) {
                            $facetQuery->addRange('lt', (int) explode('to', $ranges[1])[1]);
                        }

                        $facetsFilter->addMust($facetQuery);
                    }
                break; 

            case 'date_histogram':
                    foreach ($values as $value) {
                        $dates = explode('-', $value);
                        $from  = date(self::SOLR_DATE_FORMAT, mktime(0, 0, 0, (int)$dates[0], 1, (int)$dates[1]));
                        $to    = date(self::SOLR_DATE_FORMAT, mktime(0, 0, 0, (int)$dates[0]+1, 1, (int)$dates[1]));

                        $facetQuery = $this->createQuery('range')->setField($key);

                        $facetQuery->addRange('gte', $from);
                        $facetQuery->addRange('lt', $to);

                        $facetsFilter->addMust($facetQuery);
                    }
                break;
        }

        return $facetsFilter;
    }

    
    /**
     * Return per page number
     * 
     * @access public
     * @return array 
     */
    public function perPageValues()
    {
        return array(5, 10, 15, 20, 25, 50, 75, 100);
    }

    /**
     * Retrun url encoded value
     * 
     * @access protected
     * @param array $values Array of values to encode
     * 
     * @return string
     */
    protected function urlDecode($values)
    {
        if (!is_array($values)) {
            return urldecode($values);
        }

        foreach ($values as $key => $value) {
            $values[$key] = urldecode($value);
        }

        return $values;
    }

    /**
     * Retrun fuzzine
     *
     * @access protected
     * @param string $q
     *
     * @return int
     */
    protected function getFuzziness($q)
    {
        $length = strlen($q);

        switch (true) {
            case $length < 7:
                return 1;
                break;

            case $length < 12:
                return 2;
                break;

            case $length >= 12:
                return 3;
                break;

            default:
                return 1;
                break;
        }
    }

    /**
     * Parse string 
     * 
     * @access protected
     * @param string $str   String to parse
     * 
     * @return string
     */
    protected function parseStr($str)
    {
        if (!$str) {
            return $str;
        }
        return $str;
    }
}
