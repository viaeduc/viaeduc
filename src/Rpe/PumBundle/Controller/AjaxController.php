<?php

namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Ajax controller.
 * Controller for global XHR requests.
 *
 * @method Response getListAction(Request $request, $list = null)
 * @method Response getFooterSuggestions(Request $request)
 *
 */
class AjaxController extends Controller
{
    /**
     * @var array $authorized_repo Authorized repositories
     */
    private $authorized_repo = array(
        'instructed_discipline',
        'teaching_level',
        // 'relation_type',
        'document_type',
        'academy',
        'occupation',
        'keyword',
    );

    /**
     * Display suggestions of relations.
     * Specific for the footer part with relations suggestions
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="ajax-footer-suggestions", name="ajax_footersuggestions", defaults={"_project"="rpe"})
     */
    public function getFooterSuggestions(Request $request)
    {
        /*if (!$request->isXmlHttpRequest()) {
            // $this->throwNotFound();
        }*/

        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user      = $this->getUser();
        $groups    = $this->getRepository('group')->getSuggestedGroups($user, false, 2);
        $relations = $relations = $this->getRepository('user')->getSuggestedFriends($user, false, 2);

        return $this->render('pum://includes/common/footer/sub_footer.html.twig', array(
            'suggested_groups'    => $groups,
            'suggested_relations' => $relations
        ));
    }

    /**
     * List of items in an object.
     * Return a JSON encoded array of items from a repository in $authorized_repo
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $list        Name of object in $authorized_repo
     *
     * @return Response A Response instance
     *
     * @Route(path="ajax-getlist/{list}", name="ajax_getlist", defaults={"_project"="rpe"})
     */
    public function getListAction(Request $request, $list = null)
    {
        /*if (!$request->isXmlHttpRequest()) {
            //return;
        }*/

        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null === $list || !in_array($list, $this->authorized_repo)) {
            $this->throwNotFound('Invalid Ajax path: '.$list);
        }

        $q       = $request->query->get('q');
        $results = $this->getRepository($list)->getSearchResult($q);

        try {
            /**
             * $result
             * Insert description here
             *
             *
             * @return
             *
             * @access
             * @static
             */
            $res = array_map(function ($result) {
                return array(
                    'id'    => $result->getId(),
                    'value' => $this->get('pum.view')->renderPumObject($result, 'search_row')
                );
            }, $results);
        } catch (\Exception $e) {
            $this->throwNotFound('Invalid Ajax path: '.$list. ' (missing renderer)');
        }

        return new Response(json_encode($res));
    }
}
