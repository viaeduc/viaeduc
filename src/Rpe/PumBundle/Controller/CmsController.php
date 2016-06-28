<?php

namespace Rpe\PumBundle\Controller;

use Symfony\Component\Finder\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cms controller.
 * Actions to handle CMS Content (simple content page, FAQ, etc.)
 *
 * @method Response cmsPageAction(Request $request, $seo)
 * @method Response cmsFaqAction(Request $request, $type, $category)
 */
class CmsController extends Controller
{
    /**
     * Simple content page controller.
     * Handle displaying simple content added
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $seo         Seo key
     *
     * @return Response A Response instance
     *
     * @Route(path="/page/{seo}", name="cms_page", defaults={"_project"="rpe"}, requirements={"seo" = ".+"})
     */
    public function cmsPageAction(Request $request, $seo)
    {
        // if (null !== $check = $this->checkSecurity()) {
        //     return $check;
        // }

        // New Pum SEO :)
        list($template, $vars, $errors) = $this->get('pum.routing')->handleSeo($seo);

        if (!empty($errors)) {
            $message = reset($errors)['message'];

            $this->throwNotFound($message);
        }

        return $this->render($template, $vars);
    }

    /**
     * Display FAQs from CMS Content in Backoffice.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $type        FAQ type (invite, public, member)
     * @param  string  $type        FAQ category
     *
     * @return Response A Response instance
     *
     * @Route(path="/faq/{type}/{category}", name="faq", defaults={"_project"="rpe", "type"=null, "category"=null}, requirements={"type" = "|member|public|invite"})
     */
    public function cmsFaqAction(Request $request, $type, $category)
    {
        if (empty($type)) {
            return $this->redirect($this->generateUrl('faq', array('type' => 'member')));
        }

        /* Load correct content depending on page identifier */
        switch ($type) {
            case 'invite':
                $contentIdentifier = 'invite-faq-nav';
                $loggedOnly        = false;
                break;

            case 'public':
                $contentIdentifier = 'public-faq-nav';
                $loggedOnly        = false;
                break;

            default:
                $contentIdentifier = 'faq-nav';
                $loggedOnly        = true;
                break;
        }

        /* Check user logged depending on FAQ loaded */
        if ($loggedOnly && (null !== $check = $this->checkSecurity())) {
            return $check;
        }

        $page = null;

        if (null !== $category) {
            $id = $this->get('pum.routing')->getRoutingTable()->match($category);
            list($objClass, $objId) = explode(':', $id, 2);
            $page = $this->getRepository($objClass)->find($objId);
            if (null === $page) {
                $this->throwNotFound('error.page_not_found');
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('pum://page/cms/ajax-faq.html.twig', array(
                'type'              => $type,
                'contentIdentifier' => $contentIdentifier,
                'page'              => $page
            ));
        }

        return $this->render('pum://page/faq.html.twig', array(
            'type'              => $type,
            'contentIdentifier' => $contentIdentifier,
            'page'              => $page
        ));
    }
}
