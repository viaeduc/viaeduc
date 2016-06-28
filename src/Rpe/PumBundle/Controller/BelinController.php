<?php

namespace Rpe\PumBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Rpe\PumBundle\Extension\Belin\BelinApi;
use Rpe\PumBundle\Model\Social\User;

/**
 * Belin controller.
 * Action methods for Belin partner tool : Lib'
 *
 * @method Response pageLoginAction(Request $request)
 * @method Response pageLogoutAction(Request $request, $auth)
 * @method Response registerAction(Request $request)
 *
 */
class BelinController extends Controller
{

    /**
     * Show the login "Lib".
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance or null
     *
     * @Route(path="/lib/login", name="lib_login_page", defaults={"_project"="rpe"})
     */
    public function pageLoginAction(Request $request)
    {
        if (null != $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $resource_link = $request->query->get('resource', null);
        if ($resource_link != null) {
            $this->get('session')->set('belin_login_from_resource', $resource_link);
        }
        // if already associated
        $api_url = null;
        if ($this->get('pum.vars')->getValue('belin_sso_api')) {
            $api_url = $this->get('pum.vars')->getValue('belin_sso_api');
        }
        if ($belin_meta = $user->getMeta(User::META_BELINSSO_USER_ID)) {
            if ($belin_meta->getValue()) {
                $auth      = $belin_meta->getValue();
                $api_belin = new BelinApi(array('auth' => $auth), $api_url);
                $result    = $api_belin->call('Serveur/Connexion');
                $result    = $api_belin->treatResult($result, 'array');

                if (isset($result['utilisateur_id']) && $result['utilisateur_id']) {
                    $this->addInfo($this->get('translator')->trans('belin.sso.login.success', array(), 'rpe') . ' User id: ' . $result['utilisateur_id']);
                    return $this->render('pum://page/belin/login.html.twig', array(
                        'auth_belin'    => $auth,
                        'belin_app'     => $this->get('pum.vars')->getValue('belin_sso_app')
                    ));
                }
            }
        }

        // connextion form
        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->add('login', 'text')
            ->add('password', 'password')
            ->getForm();
        $form->handleRequest($request);

        $message = null;
        $result = null;

        if ($form->isValid()) {
            $data = $form->getData();
            $params = array('login' => $data['login'], 'pwd' => $data['password']);

            $api_belin = new BelinApi($params, $api_url);
            $auth = BelinApi::createAuth($params['login'], $params['pwd']);

            $result = $api_belin->call('Serveur/Connexion');
            $result = $api_belin->treatResult($result, 'array');

            // treate response
            if ($result['erreur'] == 0) {
                $this->setUserMeta($user, User::META_BELINSSO_USER_ID, $auth);
                if (null !== $resource_link = $this->get('session')->get('belin_login_from_resource')) {
                    $this->get('session')->remove('belin_login_from_resource');
                    return $this->redirect($this->generateUrl('display_content_belin', array('url' => $resource_link . $auth)));
                }
                $this->addInfo($this->get('translator')->trans('belin.sso.login.success', array(), 'rpe') . ' User id: ' . $result['utilisateur_id']);
                return $this->render('pum://page/belin/login.html.twig', array(
                    'auth_belin'    => $auth,
                    'belin_app'     => $this->get('pum.vars')->getValue('belin_sso_app')
                ));
            } else {
                $this->addError($this->get('translator')->trans('belin.sso.login.error', array(), 'rpe'));
            }
        }

        return $this->render('pum://page/belin/login.html.twig', array(
            'form'          => $form->createView()
        ));
    }

    /**
     * Action to logout from "Lib".
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $auth        Belin auth key*
     *
     * @return Response A Response instance
     *
     * @Route(path="/lib/logout/{auth}", name="lib_loginout_page", defaults={"_project"="rpe"})
     */
    public function pageLogoutAction(Request $request, $auth)
    {
        if (null != $check = $this->checkSecurity()) {
            return $check;
        }
        $user = $this->getUser();
        $belin_auth = $user->getMeta(User::META_BELINSSO_USER_ID);
        if (null != $belin_auth && $auth == $belin_auth->getValue()) {
            $this->setUserMeta($user, User::META_BELINSSO_USER_ID, null);
            $this->addInfo($this->get('translator')->trans('belin.sso.loginout.success', array(), 'rpe'));
        }
        return $this->redirect($this->generateUrl('lib_login_page'));
    }

    /**
     * Action to register the user "Lib".
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/lib/register", name="lib_register_page", defaults={"_project"="rpe"})
     */
    public function registerAction(Request $request)
    {
        if (null != $check = $this->checkSecurity()) {
            return $check;
        }
        $user = $this->getUser();

        $resource_link = $request->query->get('resource', null);
        if ($resource_link != null) {
            $this->get('session')->set('belin_login_from_resource', $resource_link);
        }

        // register form
        $defaultData = array();
        $form_register = $this->createFormBuilder($defaultData)
            ->add('login', 'text')
            ->add('password', 'password')
            ->add('password_confirm', 'password', array('label' => "Confirm password"))
            ->getForm();
        $form_register->handleRequest($request);

        $message = null;
        $result = null;
        if ($form_register->isValid()) {
            $data = $form_register->getData();

            $params = array('login' => $data['login'], 'pwd' => $data['password']);
            $api_url = null;
            if ($this->get('pum.vars')->getValue('belin_sso_api')) {
                $api_url = $this->get('pum.vars')->getValue('belin_sso_api');
            }
            $api_belin = new BelinApi($params, $api_url);

            $result = $api_belin->call('Utilisateur/Creer');
            $result = $api_belin->treatResult($result, 'array');

            if ($result['result']['creation']['result'] == 0) {   // fail
                $message = $result['result']['creation']['error_msg'];
                $this->addError($this->get('translator')->trans('belin.sso.register.error', array(), 'rpe') . ' ' . $message);
            } else {
                // save auth of belin to user_meta
                $auth = BelinApi::createAuth($params['login'], $params['pwd']);
                $this->setUserMeta($user, User::META_BELINSSO_USER_ID, $auth);

                if (null !== $resource_link = $this->get('session')->get('belin_login_from_resource')) {
                    $this->get('session')->remove('belin_login_from_resource');
                    return $this->redirect($this->generateUrl('display_content_belin', array('url' => $resource_link . $auth)));
                }
                $user_id = "USER_ID " . $result['result']['creation']['user_id'];
                $this->addInfo($this->get('translator')->trans('belin.sso.register.success', array(), 'rpe') . ' ' . $user_id);
                return $this->redirect($this->generateUrl('lib_login_page', array('from' => 'register')));
            }
        }
        return $this->render('pum://page/belin/register.html.twig', array(
            'form'          => $form_register->createView()
        ));
    }
}
