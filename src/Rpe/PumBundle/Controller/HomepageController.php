<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Rpe\PumBundle\Model\Social\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use GuzzleHttp\json_decode;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Homepage controller
 *
 * @method Response relogAction()
 * @method Response loginCheckAction()
 * @method Response logoutAction()
 * @method Response homePostsListAction(Request $request, $page)
 * @method Response loginAction(Request $request)
 * @method Response homeAction(Request $request)
 * @method Response homePostsListAction(Request $request, $page)
 * @method Response isSuggestedPostCardDisplayed($page)
 * @method Response discoverPageAction($type, $redirect)
 * @method Response resetPwdAction(Request $request, $token)
 * @method boolean  checkFirstConnection($user)
 * @method string   createPwd($length = 6)
 * @method Response phpInfoAction($id, $confirmcode)
 *
 */
class HomepageController extends Controller
{
    /** Discover page mode: Go to profile page  */
    const DISCOVER_MODE_SEE_PROFIL      = 'profil';
    /** Discover page mode: Suggested Groups page  */
    const DISCOVER_MODE_REJOIN_GROUP    = 'rejoin_group';
    /** Discover page mode: Publish publication  */
    const DISCOVER_MODE_SHARE_RESOURCE  = 'share_post';
    /** Discover page mode: User account  */
    const DISCOVER_MODE_COMPLETE_PROFIL = 'complete_profil';
    /** Discover page mode: Publications search */
    const DISCOVER_MODE_SEARCH          = 'search';
    /** Discover page mode : Account */
    const DISCOVER_MODE_START           = 'start';

    /**
     * Relog Action
     *
     * @access public
     * @return Response A Response instance
     *
     * @Route(path="/relog", name="relog", defaults={"_project"="rpe"})
     */
    public function relogAction()
    {
        return $this->render('pum://page/relog.html.twig');
    }

    /**
     * Login check Action
     *
     * @access public
     * @return Response A Response instance
     *
     * @Route(path="/login-check", name="login_check", defaults={"_project"="rpe"})
     */
    public function loginCheckAction()
    {
        throw new \RuntimeException('Looks like security layer is missing...');
    }

    /**
     * Logout action
     *
     * @access public
     * @return Response A Response instance
     *
     * @Route(path="/logout", name="logout", defaults={"_project"="rpe"})
     */
    public function logoutAction()
    {
        throw new \RuntimeException('Looks like security layer is missing...');
    }

    /**
     * Login page (public homepage)
     *
     * @access public
     * @param  Request $request     A request instance
     * @return Response             A Response instance
     *
     * @Route(path="/login", name="login", defaults={"_project"="rpe"})
     */
    public function loginAction(Request $request)
    {
        $form = $this->get('form.factory')->createNamed(null, 'rpe_security_login');

        $session = $request->getSession();
        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        if ($error != null) {
            switch (get_class($error)){
                case 'Symfony\Component\Security\Core\Exception\DisabledException':
                    $error->type = "disabled";
                    $error->lastlogin = $session->get(SecurityContext::LAST_USERNAME);
                    break;
                case 'Symfony\Component\Security\Core\Exception\BadCredentialsException':
                default:
                    $error->type = "failed";
                    break;
            }
        }

        $lost_pwd_form = $this->createNamedForm('lost_password', 'rpe_security_lost_password');

        if ($request->isMethod('POST') && $lost_pwd_form->handleRequest($request)->isValid()) {
            $email = $lost_pwd_form["email"]->getData();
            $user = $this->getRepository('user')->findOneByEmailPro($email);

            if (null !== $user) {

                if ($user->getStatus() == $user::STATUS_TYPE_ACTIVE) {
                    $ttl   = (string) (time() + (3600*24*3));
                    $token = md5($user->getId().$email.mt_rand().microtime().uniqid()).'#_#_#'.$ttl.'#_#_#'.$user->getId();

                    // New password Email
                    $this->get('rpe.mailer')->send(array(
                        'subject' => 'Mot de passe oublié',
                        'from'    => $this->getSenderEmail(),
                        'to'      => $user->getEmailPro(),
                        'template' => array(
                            'name' => 'pum://emails/lost_password.html.twig',
                            'vars' => array(
                                'resetlink' => $this->generateUrl('reset_password', array('token' => base64_encode($token)), UrlGeneratorInterface::ABSOLUTE_URL),
                                'user'      => $user,
                                'token'     => base64_encode($token)
                            )
                        ),
                        'type'         => 'text/html'
                    ));

                    $this->setUserMeta($user, $metaKey = $user::META_TOKEN_RESET_PWD, $metaValue = $token, $metaType = 'authentification');

                    $this->persist($user);
                    $this->flush();

                    return new Response('ok');
                } else {
                    return new Response('inactive');
                }
            }
            return new Response('noexist');
        }

        $userValidatedCount = $this->getRepository('user')->getUsersCount(User::STATUS_TYPE_ACTIVE);
        $groupCount = $this->getRepository('group')->getGroupsCount();
        $resourceCount = $this->getRepository('post')->getPostsCount(true);
        $resourceAverageCount = $this->getRepository('post')->getAveragePostsByDayCount(true, new \DateTime('today - 30 days'), new \DateTime());

        return $this->render('pum://page/homepage.html.twig', array(
            'form'                  => $form->createView(),
            'lost_pwd_form'         => $lost_pwd_form->createView(),
            'error'                 => $error,
            'userValidatedCount'    => $userValidatedCount,
            'groupCount'            => $groupCount,
            'resourceCount'         => $resourceCount,
            'resourceAverageCount'  => ceil($resourceAverageCount),
        ));
    }

    /**
     * Member homepage
     *
     * @access public
     * @param  Request $request     A request instance
     * @return Response             A Response instance
     *
     * @Route(path="/", name="home", defaults={"_project"="rpe"})
     */
    public function homeAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user             = $this->getUser();
        $tutorial_enabled = $this->checkFirstConnection($user);

        //$posts = $this->getRepository('post')->getHomePublications($user, $this->getRepository('group')->getIdentityAcceptedGroups($this->getUser()), true);
        $posts = $this->get('rpe.object.fetcher')->getHomePublications($user, $this->getRepository('group')->getIdentityAcceptedGroups($this->getUser()), true);
        $pager = new DoctrineORMAdapter($posts, true, false);

        // Page
        $byPage = 15 - (int)$this->isSuggestedPostCardDisplayed($page = 1);
        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        $suggestedPost = $this->getRepository('rpe_suggested_post')->getSuggestedPosts();

        return $this->render('pum://page/user/home.html.twig', array(
            'mode'             => 'array',
            'posts'            => $pager,
            'tutorial_enabled' => $tutorial_enabled,
            'suggestedPost'    => $suggestedPost,
            'page'             => $page,
        ));
    }

    /**
     * XHR Method to show pages of homepage publications list.
     *
     * @access public
     * @param  Request $request     A request instance
     * @return Response             A Response instance
     *
     * @Route(path="/homepostslist/{page}", name="ajax_homelist", defaults={"_project"="rpe", "page"="1"})
     */
    public function homePostsListAction(Request $request, $page)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        //$posts = $this->getRepository('post')->getHomePublications($user, $this->getRepository('group')->getIdentityAcceptedGroups($this->getUser()), true);
        $posts = $this->get('rpe.object.fetcher')->getHomePublications($this->getUser(), $this->getRepository('group')->getIdentityAcceptedGroups($this->getUser()), true);
        $pager = new DoctrineORMAdapter($posts, true, false);

        // Page
        $byPage = 15 - (int)$this->isSuggestedPostCardDisplayed($page);
        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/user/ajax-homepostslist-array.html.twig', array(
            'posts'  => $pager,
            'page'   => $page,
        ));
    }

    /**
     *
     * @access private
     * @param  string $page     Page number
     * @return Response             A Response instance
     */
    private function isSuggestedPostCardDisplayed($page)
    {
        return $this->getVar('suggested_posts') && ($page <= 1);
    }

    /**
     * Display discover page based wanted content type.
     *
     * @access public
     * @param  string   $type     Discover mode (based on `DISCOVER_MODE_*` constants)
     * @param  string   $redirect     whether to redirect
     * @return Response         A Response instance
     *
     * @Route(path="/discover_mode/{type}/{redirect}", name="discover", defaults={"_project"="rpe", "redirect"=true})
     */
    public function discoverPageAction($type, $redirect)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        $redirect = (bool) $redirect;

        switch ($type) {
            case self::DISCOVER_MODE_SEE_PROFIL:

                $redirect_url = $this->generateUrl('profil');
                break;

            case self::DISCOVER_MODE_REJOIN_GROUP:
                $redirect_url = $this->generateUrl('groups', array('mode' => GroupController::LISTMODE_SUGGESTEDGROUPS));
                break;

            case self::DISCOVER_MODE_SHARE_RESOURCE:
                $redirect_url = $this->generateUrl('publish_publications');
                break;

            case self::DISCOVER_MODE_COMPLETE_PROFIL:
                $redirect_url = $this->generateUrl('my_account');
                break;

            case self::DISCOVER_MODE_SEARCH:
                $redirect_url = $this->generateUrl('search', array('type' => 'post'));
                break;

            case self::DISCOVER_MODE_START:
                $redirect_url = $this->generateUrl('my_account');
                break;

            default:
                return $this->redirect($this->generateUrl('home'));
        }

        $this->setUserMeta($user, 'user.discover.'.$type, true, $metaType = 'user');

        if ($redirect) {
            return $this->redirect($redirect_url);
        } else {
            return new Response('OK');
        }
    }


    /**
     * Action to reset password.
     *
     * @access public
     * @param  string   $token     Password token to reset
     * @return Response         A Response instance
     *
     * @Route(path="/reset-password/{token}", name="reset_password", defaults={"_project"="rpe"})
     */
    public function resetPwdAction(Request $request, $token)
    {
        $token = base64_decode($token);
        list($usertoken, $ttl, $user_id) = explode('#_#_#', $token);
        $ttl = (int) $ttl;

        $reset_pwd_form = $this->createNamedForm('reset_password', 'rpe_security_reset_password');

        $error = null;
        if ($request->isMethod('POST') && $reset_pwd_form->handleRequest($request)->isValid()) {
            if (null !== $user = $this->getRepository('user')->find($user_id)) {
                $passwordMeta = $this->getRepository('user_meta')->findOneBy(array('metaKey' => $user::META_TOKEN_RESET_PWD, 'value' => $token, 'user' => $user));

                if (null !==  $passwordMeta) {
                    if ($ttl >= time()) {
                        $user->removeUsermeta($passwordMeta);
                        $this->remove($passwordMeta);

                        $new_pwd = $reset_pwd_form['password']->getData()['single'];

                        $user->setPassword($new_pwd, $this->get('security.encoder_factory'));
                        $this->persist($user);
                        $this->flush();

                        $loginToken = new UsernamePasswordToken($user, $user->getPassword(), 'front_secured_area', $user->getRoles());

                        $this->get('security.context')->setToken($loginToken);
                        $this->get('session')->set('_security_front_secured_area', serialize($loginToken));

                        return $this->redirect($this->generateUrl('home'));
                    } else {
                        $error = "Le token n'est plus valide";
                    }
                } else {
                    $error = "Votre token n'est pas valide";
                }
            } else {
                $error = "Utilisateur non valide";
            }
        }

        return $this->render('pum://page/user/edit/password_reset.html.twig', array(
            'reset_pwd_form'        => $reset_pwd_form->createView(),
            'resetToken'            => $token,
            'error'                 => $error
        ));
    }

    /**
     * Check first connection (and store if already went once)
     *
     * @access protected
     * @param  User   $user     User instance to check
     * @return boolean          If its the first connection
     */
    protected function checkFirstConnection($user)
    {
        $user_meta = $user->getMeta($user::META_TUTORIAL_ENABLED);
        if (null === $user_meta) {
            $this->setUserMeta($user, $user::META_TUTORIAL_ENABLED, false, 'config');

            return true;
        }

        return (bool)$user_meta->getValue();
    }

    /**
     * Generate a default password
     *
     * @access protected
     * @param  string   $length     Password length
     * @return string               Password generated
     */
    protected function createPwd($length = 6)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr(str_shuffle($chars), 0, $length);

        return $password;
    }
}
