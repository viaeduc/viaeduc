<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\UserWidget;

/**
 * dashboar controller
 *
 * @method Response  dashboardAction()
 * @method Response  dashboardSettingsAction(Request $request)
 * @method Response  dashboardValidateWidgetFormAction(Request $request)
 * @method Response  dashboardActiveWidgetAction(Request $request, $id)
 * @method Response  dashboardDesactiveWidgetAction(Request $request, $id)
 * @method Response  dashboardDeleteWidgetAction(Request $request, $id)
 * @method Response  dashboardPositionWidgetAction(Request $request, $id, $pos)
 * @method void      repositionWidget($id, $pos)
 * @method Response  parseRssUrl($id)
 * @method void      createBaseWidgets($user, $forceRecreate = false)
 * @method void      repositionWidgets($user)
 * @method void      widgetToReposition($type, $pos)
 */
class DashboardController extends Controller
{
    const META_CREATE_BASE_WIDGETS = 'user_base_widgets';

    /**
     * Display User Dashboard.
     * Show user dashboard with active widgets.
     *
     * @return Response A Response instance
     * @access public
     *
     * @Route(path="/dashboard", name="dashboard", defaults={"_project"="rpe"})
     */
    public function dashboardAction()
    {
        if (!$this->get('pum.vars')->getValue('dashboard_active')) {
            $this->throwAccessDenied('dashboard.inactiv');
        }

        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $this->createBaseWidgets($this->getUser());

        $repo    = $this->getRepository('social_user_widget');
        $widgets = $repo->getObjectsBy(array('user' => $this->getUser(), 'active' => true), 'position');

        return $this->render('pum://page/dashboard/home.html.twig', array(
            'activeWidgets' => $widgets
        ));
    }

    /**
     * Display Dashboard settings page.
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @throws AccessDeniedException if `dashboard_active` var is not defined or set to true in backoffice.
     *
     * @return Response             A Response instance
     *
     * @Route(path="/dashboard-settings", name="dashboard-settings", defaults={"_project"="rpe"})
     */
    public function dashboardSettingsAction(Request $request)
    {
        if (!$this->get('pum.vars')->getValue('dashboard_active')) {
            $this->throwAccessDenied('dashboard.inactiv');
        }

        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        $this->createBaseWidgets($user);
        
        $widget = $this
            ->createObject('social_user_widget')
            ->setUser($user)
            ->setActive(true)
        ;
        $formWidgetRss      = $this->createNamedForm('widget_rss', 'rpe_form_widget_rss', $widget);
        $formWidgetFacebook = $this->createNamedForm('widget_facebook', 'rpe_form_widget_facebook', $widget);
        //$formWidgetTwitter  = $this->createNamedForm('widget_twitter', 'rpe_form_widget_twitter', $widget);

        if ($request->isMethod('POST')) {
            // handle requests
            $validated = false;
            if ($formWidgetRss->handleRequest($request)->isValid()) {
                $validated = true;
                $widget = $formWidgetRss->getData();
            }
            if ($formWidgetFacebook->handleRequest($request)->isValid()) {
                $validated = true;
                $widget = $formWidgetFacebook->getData();
            }
            /*if ($formWidgetTwitter->handleRequest($request)->isValid()) {
                $validated = true;
                $widget = $formWidgetTwitter->getData();
            }*/
            if ($validated) {
                $pos = $this->getRepository('social_user_widget')->countBy(array('user' => $this->getUser(), 'active' => true)) + 1;
                $widget->setPosition($pos);
                $this->persist($widget)->flush();

                return $this->redirect($this->generateUrl('dashboard-settings'));
            }
        }

        return $this->render('pum://page/dashboard/settings.html.twig', array(
            'form_widget_rss'      => $formWidgetRss->createView(),
            'form_widget_facebook' => $formWidgetFacebook->createView(),
            //'form_widget_twitter'  => $formWidgetTwitter->createView(),
            'active_widgets'       => $this->getRepository('social_user_widget')->getObjectsBy(array('user' => $user, 'active' => true), 'position'),
            'inactive_widgets'     => $this->getRepository('social_user_widget')->getObjectsBy(array('user' => $user, 'active' => false), 'position')
        ));
    }

    /**
     * Handle Widget settings submission.
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @throws AccessDeniedException if `dashboard_active` var is not defined or set to true in backoffice.
     *
     * @return Response A Response instance (JSON encoded data)
     *
     * @Route(path="/dashboard-validate-widgetform", name="dashboard-validate-widgetform", defaults={"_project"="rpe"})
     */
    public function dashboardValidateWidgetFormAction(Request $request)
    {
        if (!$this->get('pum.vars')->getValue('dashboard_active')) {
            $this->throwAccessDenied('dashboard.inactiv');
        }

        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $widget = $this->createObject('social_user_widget');

        $formWidgetRss      = $this->createNamedForm('widget_rss', 'rpe_form_widget_rss', $widget);
        $formWidgetFacebook = $this->createNamedForm('widget_facebook', 'rpe_form_widget_facebook', $widget);

        $response = array('validated' => false);
        if ($request->isMethod('POST')) {
            // handle requests
            foreach (array('rss' => $formWidgetRss, 'facebook' => $formWidgetFacebook) as $key => $form) {
                $form->handleRequest($request);
                if ($form->isSubmitted()) {
                    if ($form->isValid()) {
                        $response['validated'] = true;
                    } else {
                        $response['form_type'] = $key;
                        $response['message']   = array();
                        foreach ($form['content']->getErrors() as $error) {
                            $response['message'][] = $error->getMessage();
                        }
                    }
                }
            }
        }
        return new Response(json_encode($response));
    }

    /**
     * Handle activation of a widget.
     * Handle activation of a widget and then redirect to Dashboard settings
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Widget id
     * @return Response A Response instance (redirect)
     *
     * @Route(path="/dashboard-active-widget/{id}", name="dashboard-active-widget", defaults={"_project"="rpe"})
     */
    public function dashboardActiveWidgetAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($id) {
            $repo   = $this->getRepository('social_user_widget');
            $widget = $repo->getObjectsBy(array('user' => $this->getUser(), 'id' => $id, 'active' => false));
            $pos    = $repo->countBy(array('user' => $this->getUser(), 'active' => true)) + 1;

            if (!empty($widget)) {
                $widget = reset($widget);
                $widget
                    ->setActive(true)
                    ->setPosition($pos)
                ;

                $this->flush();
            }
        }

        return $this->redirect($this->generateUrl('dashboard-settings'));
    }

    /**
     * Handle deactivation of a widget.
     * Handle the deactivation of a widget and then redirect to settings page.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Widget id
     * @return Response A Response instance (redirect)
     *
     * @Route(path="/dashboard-desactive-widget/{id}", name="dashboard-desactive-widget", defaults={"_project"="rpe"})
     */
    public function dashboardDesactiveWidgetAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($id) {
            $repo   = $this->getRepository('social_user_widget');
            $widget = $repo->getObjectsBy(array('user' => $this->getUser(), 'id' => $id, 'active' => true));

            if (!empty($widget)) {
                $widget  = reset($widget);
                $old_pos =  $widget->getPosition();
                $widget
                    ->setActive(false)
                    ->setPosition(null)
                ;

                $this->flush();

                $qb = $repo->createQueryBuilder('o');
                $qb
                    ->update()
                    ->set('o.position', 'o.position - 1')
                    ->andWhere('o.position > :old_pos')
                    ->andWhere('o.user = :user')
                    ->andWhere('o.active = :active')
                    ->setParameters(array(
                        'old_pos' => $old_pos,
                        'user'    => $this->getUser(),
                        'active'  => true
                    ))
                    ->getQuery()
                    ->execute()
                ;
            }
        }

        return $this->redirect($this->generateUrl('dashboard-settings'));
    }

    /**
     * Handle removal of a widget.
     * Handle removal of a widget and then redirect to settings page
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Widget id
     * @return Response A Response instance
     *
     * @Route(path="/dashboard-delete-widget/{id}", name="dashboard-delete-widget", defaults={"_project"="rpe"})
     */
    public function dashboardDeleteWidgetAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($id) {
            $repo   = $this->getRepository('social_user_widget');
            $widget = $repo->getObjectsBy(array('user' => $this->getUser(), 'id' => $id, 'active' => false));

            if (!empty($widget)) {
                $widget  = reset($widget);
                if ($widget->canDelete()) {
                    $this->remove($widget)->flush();
                }
            }
        }

        return $this->redirect($this->generateUrl('dashboard-settings'));
    }

    /**
     * Handle positioning change of a widget.
     * Handle the change of widget's position and then redirect to settings page
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Widget id
     * @param  string  $pos         Position of widget
     *
     * @uses DashboardController:repositionWidget() to update position of the changed widget
     *
     * @return Response A Response instance
     *
     * @Route(path="/dashboard-position-widget/{id}/{pos}", name="dashboard-position-widget", defaults={"_project"="rpe"})
     */
    public function dashboardPositionWidgetAction(Request $request, $id, $pos)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $this->repositionWidget($id, $pos);

        return $this->redirect($this->generateUrl('dashboard-settings'));
    }

    /**
     * Update widgets position when changing one
     *
     * @access private
     * @param  string  $id          Widget id
     * @param  string  $pos         Position of widget
     * @return void
     *
     */
    private function repositionWidget($id, $pos)
    {
        if ($id) {
            $repo   = $this->getRepository('social_user_widget');
            $widget = $repo->getObjectsBy(array('user' => $this->getUser(), 'id' => $id, 'active' => true));

            if (!empty($widget)) {
                $widget  = reset($widget);
                $old_pos =  $widget->getPosition();

                if ($pos > $old_pos) {
                    $qb = $repo->createQueryBuilder('o');
                    $qb
                        ->update()
                        ->set('o.position', 'o.position - 1')
                        ->andWhere('o.position <= :new_pos')
                        ->andWhere('o.position > :old_pos')
                        ->andWhere('o.user = :user')
                        ->andWhere('o.active = :active')
                        ->setParameters(array(
                            'new_pos' => $pos,
                            'old_pos' => $old_pos,
                            'user'    => $this->getUser(),
                            'active'  => true
                        ))
                        ->getQuery()
                        ->execute()
                    ;
                } else {
                    $qb = $repo->createQueryBuilder('o');
                    $qb
                        ->update()
                        ->set('o.position', 'o.position + 1')
                        ->andWhere('o.position >= :new_pos')
                        ->andWhere('o.position < :old_pos')
                        ->andWhere('o.user = :user')
                        ->andWhere('o.active = :active')
                        ->setParameters(array(
                            'new_pos' => $pos,
                            'old_pos' => $old_pos,
                            'user'    => $this->getUser(),
                            'active'  => true
                        ))
                        ->getQuery()
                        ->execute()
                    ;
                }

                $widget
                    ->setPosition($pos)
                ;

                $this->flush();
            }
        }
    }

    /**
     * Parse and display content from an RSS Feed.
     *
     * @access public
     * @param  string  $id          Widget id
     * @return Response A Response instance
     *
     * @Route(path="/dashboard-rss-parser/{id}", name="dashboard-rss-parser", defaults={"_project"="rpe"})
     */
    public function parseRssUrl($id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($id && null !== $widget = $this->getRepository('social_user_widget')->findOneBy(array('user' => $this->getUser(), 'id' => $id, 'active' => true, 'type' => UserWidget::TYPE_RSS))) {
            return $this->render('pum://page/dashboard/widgets/ajax/rss.html.twig', array(
                'data' => $this->get('rpe.rss.parser')->get($widget->getContent())
            ));
        }

        return new Response();
    }

    /**
     * Create default widgets for a new Dashboard.
     *
     * @access private
     * @param  User      $user              User
     * @param  boolean   $forceRecreate
     * @return void
     *
     */
    private function createBaseWidgets($user, $forceRecreate = false)
    {
        $repo = $this->getRepository('social_user_widget');

        if ($this->getVar('suggested_posts') && !$repo->countBy(array('user' => $user, 'type' => 'suggested_posts'))) {
            $forceRecreate = true;
        }

        $position = 1;
        $nbInsert = 0;
        $widgets  = UserWidget::$defaultWidgets;
        if ($this->getVar('suggested_posts')) {
            $widgets = array_merge($widgets, array('suggested_posts'));
        }

        // for invited users, exclude widget "resources active"
        if ($user->isInvited()) {
            $widgets_to_delete = array("popular_posts", "last_groups");
            $widgets = array_diff($widgets, $widgets_to_delete);
            
            foreach ($widgets_to_delete as $value) {
                $to_delete = $repo->getObjectsBy(array('user' => $user, 'type' => $value));
                if (!empty($to_delete)) {
                    $to_delete = reset($to_delete);
                    $this->remove($to_delete)->flush();
                }
            }
        }
        
        foreach ($widgets as $type) {
            if (0 == $count = $repo->countBy(array('user' => $user, 'type' => $type))) {
                $widget = $this->createObject('social_user_widget');
                $widget
                    ->setPosition($position++)
                    ->setType($type)
                    ->setName($this->get('translator')->trans('dashboard.'.$type, array(), 'rpe'))
                    ->setActive(true)
                    ->setUser($user)
                ;

                $this->persist($widget);
                $nbInsert++;
            }
        }
        $this->flush();
        
        // Reposition.
        if ($nbInsert > 0 && $nbInsert != count($widgets)) {
            $position = 1;

            foreach ($user->getWidgets() as $widget) {
                $widget->setPosition($position++);
            }

            $this->flush();
        }

        // special widget position
        if (null !== $widgetToReposition = $this->widgetToReposition('suggested_posts', $suggestedPostsPosition = 3)) {
            $this->repositionWidget($widgetToReposition->getId(), $suggestedPostsPosition);
        }

        $this->setUserMeta($user, self::META_CREATE_BASE_WIDGETS, true);
    }

    /**
     * Method to get all widget that require position update.
     *
     * @deprecated Will remain but moved to `social_user_widget` repository class
     *
     * @access private
     *
     * @param  User      $user              User
     * @param  boolean   $forceRecreate
     *
     * @return void
     *
     */
    private function widgetToReposition($type, $pos)
    {
        $repo = $this->getRepository('social_user_widget');
        $widgetToReposition = $repo
            ->createQueryBuilder('uw')
            ->andWhere('uw.type = :type')
            ->andWhere('uw.user = :user')
            ->andWhere('uw.position <> :position')
            ->setParameters(array(
                'type' => $type,
                'user' => $this->getUser(),
                'position' => $pos,
            ))
            ->setMaxResults(1)
        ;
        return $widgetToReposition->getQuery()->getOneOrNullResult();
    }

    /**
     * Method to update all widgets position.
     *
     * @access private
     * @param  User      $user              User
     * @return void
     *
     */
    private function repositionWidgets($user)
    {
        $position  = 1;
        $repo      = $this->getRepository('social_user_widget');
        $widgets   = $repo->getObjectsBy(array('user' => $user, 'active' => true), 'position');

        foreach ($widgets as $widget) {
            $widget->setPosition($position++);
        }

        $this->flush();
    }
}
