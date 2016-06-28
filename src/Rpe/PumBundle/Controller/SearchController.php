<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pagerfanta\Pagerfanta;
use Rpe\PumBundle\Extension\ElasticSearch\SearchFactory;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Extension\Belin\BelinApi;

/**
 * Search controller
 *  
 * @method Response suggestSearchAction(Request $request, $type)
 * @method Response advancedSearchAction(Request $request, $type)
 * @method Response SearchCountBelin(Request $request)
 * @method Response displayContentBelin(Request $request)
 * @method Response stdSearchAction(Request $request, $type)
 * @method array    getAuthBelin($user)
 * @method array    getParamBelin(Request $request, $count = false, $params = array())
 * @method string   urlContent($url)
 * @method string   getCacheBelin($url, $timeout = 86400)
 *
 */
class SearchController extends Controller
{

    const PAGINATION = 10;
    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $type        Type of search
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/search/suggest/{type}", name="suggest_search", defaults={"_project"="rpe"})
     */
    public function suggestSearchAction(Request $request, $type)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (!in_array($type, array('user', 'post', 'group', 'question', 'global', 'notice', 'blog'))) {
            return $this->redirect($this->generateUrl('suggest', array('type' => 'user')));
        }

        $user  = $this->getUser();
        $debug = $request->query->get('debug', false);

        $request->query->set('per_page', 5);

        $query  = $this->get('rpe.search.'.$type)->getQuery($request, $user);
        $result = $query->execute($debug);
          
        if (true === $result->isFailed()) {
            $reponse = array('error' => true);
        } else {
            $reponse = array();
            $descriptionTruncate = 180;

            $storage = $this->get('type_extra.media.storage.driver');

            foreach ($result->getRows() as $row) {
                switch ($row->getType()) {
                    case 'user':
                        $reponse[] = array(
                            'image'       => $storage->getWebPathFromId($row->get('image'), true, 140),
                            'title'       => $row->getHightlight('fullname'),
                            'description' => $this->get('translator')->trans('search.user.register_date', array(), 'rpe').date('d/m/Y', strtotime($row->get('date'))),
                            'href'        => $this->generateUrl('profil', array('id' => $this->get('rpe.hashid')->encode($row->getId()))),
                            'type'        => $row->getType()
                        );
                        break;

                    case 'question':
                        $reponse[] = array(
                            'image'         => $storage->getWebPathFromId($row->get('author_image'), true, 140),
                            'title'         => $row->getHightlight('name'),
                            'description'   => substr($row->getHightlight('description'), 0, $descriptionTruncate),
                            'href'          => $this->generateUrl('question', array('id' => $row->getId())),
                            'type'          => $row->getType()
                        );
                        break;

                    case 'group':
                        $reponse[] = array(
                            'image'         => $storage->getWebPathFromId($row->get('image'), true, 140),
                            'title'         => $row->getHightlight('name'),
                            'description'   => substr($row->getHightlight('description'), 0, $descriptionTruncate),
                            'href'          => $this->generateUrl('group', array('id' => $row->getId())),
                            'type'          => $row->getType()
                        );
                        break;

                    case 'post':
                        $reponse[] = array(
                            'image'         => $storage->getWebPathFromId($row->get('image'), true, 140),
                            'title'         => $row->getHightlight('name'),
                            'description'   => substr($row->getHightlight('description'), 0, $descriptionTruncate),
                            'href'          => $this->generateUrl('publication', array('id' => $row->getId())),
                            'type'          => $row->getType()
                        );
                        break;

                    case 'blog':
                        $reponse[] = array(
                            'image'         => $storage->getWebPathFromId($row->get('image'), true, 140),
                            'title'         => $row->getHightlight('name'),
                            'description'   => substr($row->getHightlight('description'), 0, $descriptionTruncate),
                            'href'          => $this->generateUrl('blog', array('id' => $row->getId())),
                            'type'          => $row->getType()
                        );
                        break;
                        
                    case 'external_notice':
                        $reponse[] = array(
                            'image'         => $storage->getWebPathFromId($row->get('image'), true, 140),
                            'title'         => $row->getHightlight('title'),
                            'description'   => $row->getHightlight('description'),
                            'href'          => $this->generateUrl('notice', array('id' => $row->getId())),
                            'type'          => $row->getType()
                        );
                        break;
                }
            }
        }

        return new JsonResponse($reponse);
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $type        Type of search
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/search/advanced/{type}", name="advanced_search", defaults={"_project"="rpe"})
     */
    public function advancedSearchAction(Request $request, $type)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (!in_array($type, array('global', 'user', 'post', 'group', 'question', 'notice', 'blog'))) {
            $defaultSearch = (true !== $this->get('pum.vars')->getValue('active_global_search')) ? 'user' : 'global';

            return $this->redirect($this->generateUrl('advanced_search', array('type' => $defaultSearch)));
        }

        $user  = $this->getUser();
        $debug = $request->query->get('debug', false);
        $noticeSource = $request->query->get('source');

        $request->query->set('q', null);

        $result         = null;
        $user_count     = 0;
        $post_count     = 0;
        $group_count    = 0;
        $question_count = 0;
        $noticia_count  = 0;
        $beebac_count   = 0;

        list($q, $q_all, $q_expr, $q_one, $q_exclude) = $this->get('rpe.search.'.$type)->getQ($request);

        $allow_no_query = true;

        if ($q_all || $q_expr || $q_one || $q_exclude || $allow_no_query) {
            $query  = $this->get('rpe.search.'.$type)->getQuery($request, $user);
            $result = $query->execute($debug);

            $global_count   = $this->get('rpe.search.global')->count($request, $user)->getCount();
            $user_count     = $this->get('rpe.search.user')->count($request, $user)->getCount();
            $post_count     = $this->get('rpe.search.post')->count($request, $user)->getCount();
            $group_count    = $this->get('rpe.search.group')->count($request, $user)->getCount();
            $question_count = $this->get('rpe.search.question')->count($request, $user)->getCount();
            $notice_count   = $this->get('rpe.search.notice')->count($request, $user)->getCount();
            $blog_count     = $this->get('rpe.search.blog')->count($request, $user)->getCount();
            $belin_count    = null;
            
            foreach ($result->getFacets() as $facetKey => $facet) {
                if ($facetKey == 'source') {
                    foreach ($facet->getRows() as $row) {
                        switch ($row->getTerm()) {
                            case 'noticia':
                                $noticia_count = $row->getCount();
                                break;
                            case 'beebac':
                                $beebac_count = $row->getCount();
                                break;
                        }
                    }
                }
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('pum://page/ajax/search/'.$type.'.html.twig', array(
                'search_type'    => 'user',
                'result'         => $result,
            ));
        }

        return $this->render('pum://page/search/search.html.twig', array(
            'path_name'      => 'advanced_search',
            'search_type'    => $type,
            'result'         => $result,
            'user_count'     => $user_count,
            'post_count'     => $post_count,
            'group_count'    => $group_count,
            'question_count' => $question_count,
            'notice_count'   => $notice_count,
            'blog_count'   => $blog_count,
            'global_count'   => $global_count,
            'belin_count'    => $belin_count,
            'noticeSource'   => $noticeSource[0],
            'noticia_count'  => $noticia_count,
            'beebac_count'   => $beebac_count,
            'saved_search'   => $this->getRepository('social_search')->getSavedSearch($user)
        ));
    }

    /**
     * Count the belin resource 
     * 
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/search/belin/count", name="search_belin_count", defaults={"_project"="rpe"})
     */
    public function SearchCountBelin(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user = $this->getUser();
        list($belin_user, $auth) = $this->getAuthBelin($user);
        $belinResult = $this->getParamBelin($request, false, array('user' => $belin_user, 'auth' => $auth, 'stat' => 1));
        
        $belin_count = null;
        if ($belinResult && isset($belinResult['total'])) {
            $belin_count = $belinResult['total'];
        }
        return new Response($belin_count);
    }
    
    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/search/belin", name="display_content_belin", defaults={"_project"="rpe"})
     */
    public function displayContentBelin(Request $request)
    {
        $content = urldecode($this->getRequest()->query->get('url'));
        
        return $this->render('pum://page/search/documentationWs.html.twig', array(
            'content'      => $content,
            'search_type' => 'belin'
        ));
    }
    
    /**
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/favorite-search", name="search_favorites", defaults={"_project"="rpe"})
     */
    public function favoriteSearches(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user = $this->getUser();
        
        $searches = $this->getRepository('social_search')->getSavedSearch($user);
    
        return $this->render('pum://page/search/favorite_search.html.twig', array(
            'user'      => $user,
            'searches'  => $searches
        ));
    }
    

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $type        Type of search
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/search/std/{type}", name="search", defaults={"_project"="rpe"})
     */
    public function stdSearchAction(Request $request, $type)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (!in_array($type, array('global', 'user', 'post', 'group', 'question', 'belin', 'notice', 'blog'))) {
            $defaultSearch = (true !== $this->get('pum.vars')->getValue('active_global_search')) ? 'user' : 'global';
            return $this->redirect($this->generateUrl('search', array('type' => $defaultSearch)));
        }

        $user  = $this->getUser();
        $debug = $request->query->get('debug', false);
        $noticeSource = $request->query->get('source');

        $request->query->set('q_all', null);
        $request->query->set('q_expr', null);
        $request->query->set('q_one', null);
        $request->query->set('q_exclude', null);

        $global_count   = $this->get('rpe.search.global')->count($request, $user)->getCount();
        $user_count     = $this->get('rpe.search.user')->count($request, $user)->getCount();
        $post_count     = $this->get('rpe.search.post')->count($request, $user)->getCount();
        $group_count    = $this->get('rpe.search.group')->count($request, $user)->getCount();
        $question_count = $this->get('rpe.search.question')->count($request, $user)->getCount();
        $notice_count   = $this->get('rpe.search.notice')->count($request, $user)->getCount();
        $blog_count     = $this->get('rpe.search.blog')->count($request, $user)->getCount();
        $noticia_count  = 0;
        $beebac_count   = 0;

        $belinResult = null;
        $has_extern = in_array($type, array('belin', 'post', 'notice', 'global'));
        if($this->get('pum.vars')->getValue('active_belin') && $has_extern) {
            if ($type == "belin") {
                list($belin_user, $auth) = $this->getAuthBelin($user);
                $belinResult = $this->getParamBelin($request, false, array('user' => $belin_user, 'auth' => $auth));
            }
        }
        
        if ($type == "belin") {
            $result = $belinResult;
            if($this->getRequest()->query->get('debug')){
                echo '<pre>' . print_r($result, true) . '</pre>';
                die;
            }
        } else {
            $query  = $this->get('rpe.search.'.$type)->getQuery($request, $user);
            $result = $query->execute($debug);       
        }

        if ($has_extern) {
            $newRequest = clone $request;
            $newRequest->query->set('source', null);
            $query  = $this->get('rpe.search.notice')->getQuery($newRequest, $user);
            $noticeResult = $query->execute();
            foreach ($noticeResult->getFacets() as $facetKey => $facet) {
                if ($facetKey == 'source') {
                    foreach ($facet->getRows() as $row) {
                        switch ($row->getTerm()) {
                            case 'noticia':
                                $noticia_count = $row->getCount();
                                break;
                            case 'beebac':
                                $beebac_count = $row->getCount();
                                break;
                        }
                    }
                }
            }
        }
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('pum://page/ajax/search/'.$type.'.html.twig', array(
                'search_type'    => $type,
                'result'         => $result,
                'belin_user'     => isset($belin_user) && $belin_user ? $belin_user : null
            ));
        }
        return $this->render('pum://page/search/search.html.twig', array(
            'path_name'      => 'search',
            'search_type'    => $type,
            'result'         => $result,
            'user_count'     => $user_count,
            'post_count'     => $post_count,
            'group_count'    => $group_count,
            'question_count' => $question_count,
            'notice_count'   => $notice_count,
            'blog_count'     => $blog_count,
            'global_count'   => $global_count,
            'belin_user'     => isset($belin_user) && $belin_user ? $belin_user : null,
            'noticeSource'   => $noticeSource[0],
            'noticia_count'  => $noticia_count,
            'beebac_count'   => $beebac_count,
            'has_extern'     => $has_extern,
            'saved_search'   => $this->getRepository('social_search')->getSavedSearch($user)
        ));
    }
    
    /**
     * To save a search of user
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $type        Type of search
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/search-save/{type}/{path_name}", name="search-save", defaults={"_project"="rpe","type"="global","path"="search"})
     */
    public function saveSearch(Request $request, $type, $path_name)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        
        if (!in_array($type, array('global', 'user', 'post', 'group', 'question', 'belin', 'notice', 'blog'))) {
            $type = 'global';
        }
        if (!isset($path_name) || !in_array($path_name, array('search', 'advanced_search'))) {
            $path_name = "search";
        }
        
        $user  = $this->getUser();
        $query = $request->query->get('q', '');
        
        
        $search = $this->getRepository('social_search')->getExistSearch($user, $query);
        
        $new = false;
        if ($search === null) {
            $new = true;
            $search = $this->createObject('social_search')
                ->setDate(new \DateTime())
                ->setIsAdvance(($path_name == "advanced_search"))
                ->setKeyWord($query)
                ->setType($type)
                ->setUser($user)
            ;
            $user->addSavedSearch($search);
            $this->persist($search, $user)->flush();
        }
        
        return $this->render('pum://includes/common/componants/search/save_search.html.twig', array(
            'path' => $path_name,
            'type' => $type,
            'search' => $search,
            'new'   => $new
        ));
    }

    /**
     * @access private
     * @param  User  $user     User object
     * 
     * @return array An arrray contain belin user id and auth key
     * 
     */
    private function getAuthBelin($user)
    {
        $belin_user = $auth = null;
        if ($belin_meta = $user->getMeta(User::META_BELINSSO_USER_ID)) {
            if ($belin_meta->getValue()) {
                $auth      = $belin_meta->getValue();
                $api_belin = new BelinApi(array('auth' => $auth), $this->get('pum.vars')->getValue('belin_sso_api'));
                $result    = $api_belin->call('Serveur/Connexion');
                $result    = $api_belin->treatResult($result, 'array');
                $belin_user = isset($result['utilisateur_id']) ? $result['utilisateur_id'] : null;
            }
        }
        return array($belin_user, $auth);
    }
    
    /**
     * @access private
     * @param  Request $request     A request instance
     * @param  boolean $count       Get count or not
     * @param  array   $params      Parameter of belin
     * 
     * @return array An arrray contain search result
     * 
     */
    private function getParamBelin(Request $request, $count = false, $params = array())
    {
        $result['total'] = 0;
        if (true !== $this->get('pum.vars')->getValue('active_belin')) {
            return $result;
        }

        $page = $request->query->get('p') == null ? 1 :  $this->getRequest()->query->get('p');
        $from = ($page-1) * self::PAGINATION;
        $q    = $request->query->get('q');

        $belin_sso_api  = $this->get('pum.vars')->getValue('belin_sso_api');
        $base = $belin_sso_api ? $belin_sso_api : "http://api-lib-v3-alpha-preprod.immanens.com";
        $buildUrl = array(
             "base"      => $base . "/Manuel/SearchGate?",
             "keySearch" => "&search=".urlencode($q),
             "bookID"    => "&bookID=",
             "chapitre"  => "&chapitre=All",
             "start"     => "&debut=".$from,
             "number"    => "&nombre=".self::PAGINATION
        );

        if($count === true){
            $buildUrl["stat"] = 1;
        }
        if(in_array($request->query->get('type'), array("image", "texte", "video", "son", "animation"))){
            $buildUrl['type'] = "&type=" . $request->query->get('type');
        }
        if(in_array($request->query->get('matiere'), BelinApi::$matiere)){
            $buildUrl['matiere'] = "&user_matiere=" . $request->query->get('matiere')
                                    . "&matiere=" .  $request->query->get('matiere');
        }
        if(in_array($request->query->get('niveau'), BelinApi::$niveau)){
            $buildUrl['niveau'] = "&user_niveau=" . $request->query->get('niveau');
        }

        // belin user id
        if (isset($params['user'])) {
            $buildUrl['user'] = "&user=" . $params['user'];
            $buildUrl['user_auth'] = "&user_auth=" . $params['auth'];
        }
        if (isset($params['stat'])) {
            $buildUrl['stat'] = "&stat=" . $params['stat'];
        }
        $url = implode($buildUrl);
        
        $result = $this->getCacheBelin($url);
        if (isset($params['stat']) && $params['stat'] == 1) {
            if( preg_match( '!\(([^\)]+)\)!', $result, $match ) ) {
                $result = $match[1];
            }
        }
        $result = json_decode($result, true);
        if($result){
            $result['total']       = $result['NumRecords'];

            if($count !== true){
                $result['currentPage'] = $page;
                $result['nbPages']     = (int)($result['total']/self::PAGINATION);

                $facet_type = array();
                array_push($facet_type, array('term' => "Animation", 'count' => $result['Animation'], 'value' => 'animation'));
                array_push($facet_type, array('term' => "Audio", 'count' => $result['Audio'], 'value' => 'son'));
                array_push($facet_type, array('term' => "Image", 'count' => $result['Image'], 'value' => 'image'));
                array_push($facet_type, array('term' => "Texte", 'count' => $result['Texte'], 'value' => 'texte'));
                array_push($facet_type, array('term' => "Vidéo associée", 'count' => $result['Video'], 'value' => 'video'));

                $facet_matiere = array();
                $facet_matiere_vars = BelinApi::$matiere;
                foreach ($facet_matiere_vars as $k => $v){
                    if(isset($result[$k]) && $result[$k] > 0){
                        array_push($facet_matiere, array('term' => $v, 'count' => $result[$k], 'value' => $k));
                    }
                }
                $facet_niveau = array();
                $facet_niveau_vars = BelinApi::$niveau;
                foreach ($facet_niveau_vars as $k => $v){
                    if(isset($result[$k]) && $result[$k] > 0){
                        array_push($facet_niveau, array('term' => $v, 'count' => $result[$k], 'value' => $k));
                    }
                }

                $result['facets']      = array(
                    "type"  =>  array(
                        "total"    => $result['NumRecords'],
                        "terms"    =>  $facet_type
                    ),
                    "matiere" => array(
                        "total"     => $result['NumRecords'],
                        "terms"     =>  $facet_matiere
                    ),
                    "niveau" => array(
                        "total"     => $result['NumRecords'],
                        "terms"     =>  $facet_niveau
                    )
                );
            }
        }
        return $result;
    }

    /**
     * @access private
     * @param  string  $url     Url of belin api request
     * 
     * @return string Belin api return
     */
    private function urlContent($url)
    {
        $output = false;
        // is cURL installed yet?
        if (function_exists('curl_init')){
            // Create a new cURL resource handle
            $ch = curl_init();

            // Set Options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            // Download the given URL, and return output
            $output = curl_exec($ch);
            $curl_errno = curl_errno($ch);
            $curl_error = curl_error($ch);
            // Close the cURL resource, and free system resources
            curl_close($ch);

            if ($curl_errno > 0) {
                return false;
            } else {
                return $output;
            }
        }
        $output = @file_get_contents($url);
        return $output;
    }

    /**
     * @access private
     * @param  string  $url     Url of belin api request
     * @param  int     $timeout Time ttl of cache
     * 
     * @return string Belin cache return
     */
    private function getCacheBelin($url, $timeout = 86400)
    {
        $storage = $this->get('type_extra.media.storage.driver');
        $folder = $this->get('kernel')->getCacheDir() . "/cacheBelin/";

        $key = md5($url);
        $dest_file = $folder . $key;
//         var_dump($folder, is_dir($folder), getcwd(), $dest_file, file_exists($dest_file));
//         die("/r/n ##############");

        if(!$storage->exists($dest_file) || filemtime($dest_file) < (time()-$timeout)) {
            if ($storage->exists($dest_file) && filemtime($dest_file) < (time()-$timeout)) {
                @unlink($dest_file);
            }
            $data = $this->urlContent($url);
            if ($data === false)
                return false;
            $tmpf = tempnam('/tmp','YWS');
            $fp = fopen($tmpf,"w");
            fwrite($fp, $data);
            fclose($fp);

            if (!is_dir($folder)) {
                @mkdir($folder, 0777, true);
            }
            @copy($tmpf, $dest_file);
        }else {
    		return file_get_contents($dest_file);
    	}
    	return($data);
    }
}
