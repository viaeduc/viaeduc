<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Rpe\PumBundle\Model\Social\User;

/**
 * Chat controller.
 * Action methods about Chat w/ Converse.JS & Prosody
 *
 * @method Response chatPrebindAction(Request $request)
 * @method string   generateUserChatToken($user)
 * @method array    setBOSHSession($username, $password)
 * @method Response chatAuthentificationAuthAction(Request $request)
 * @method Response chatAuthentificationUserAction(Request $request)
 * @method Response chatUserRosterUpdateAction(Request $request)
 * @method nul|object  getUserByRequestCredentials(Request $request)
 *
 */
class ChatController extends Controller
{
    /**
     * Handle prebind to Chat Server.
     * Return a JSON encoded response with informations from Prebind request to Chat Server
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @uses ChatController:generateUserChatToken() to generate a token used as password to auth user on Chat Server
     * @uses  ChatController:setBOSHSession() to set the BOSH Session to the Chat Server
     *
     * @return Response A Response instance
     *
     * @Route(path="chat/prebind", name="chat_prebind", defaults={"_project"="rpe"})
     */
    public function chatPrebindAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return new JsonResponse('ERROR', 401);
        }

        $user  = $this->getUser();
        $token = $this->generateUserChatToken($user);
        $token = $token->getValue();

        try {
            // Session tokens
            $sessionInfo = $this->setBOSHSession($user->getJabberId(), $token);

        } catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }

        $response = new JsonResponse($sessionInfo);

        return $response;
    }

    /**
     * Generate a private token for the user.
     * Private token generated for the user and used as a password to auth on Chat Server.
     *
     * @access private
     * @param  User $user     Instace of user
     *
     * @return string  Chat token for user
     */
    private function generateUserChatToken($user)
    {
        $metaKey   = $user::META_TOKEN_CHAT;
        $user_meta = $user->getMeta($metaKey);

        if (null === $user_meta) {
            $ttl   = (string) (time() + (3600*24*3));
            $token = base64_encode(md5($user->getId().mt_rand().microtime().uniqid()).'#_#_#'.$ttl.'#_#_#'.$user->getId());

            $user_meta = $this->oem->createObject('user_meta')
                ->setUser($user)
                ->setType('authentification')
                ->setMetaKey($metaKey)
                ->setValue($token)
            ;
            $user->addUserMeta($user_meta);

            $this->persist($user_meta, $user);
            $this->flush();
        }

        return $user_meta;
    }

    /**
     * Set Bosh Session on Chat Server.
     *
     * @access private
     * @param  string  $username     User name
     * @param  string  $password     Password for user
     *
     * @uses  \XmppPrebind to bind the user on Chat Server
     *
     * @return array   Array containing sid, rid and jid
     */
    private function setBOSHSession($username, $password)
    {
        $hostDomain = $this->get('rpe.utils')->getParameter('chat.host_domain');
        $hostPort   = $this->get('rpe.utils')->getParameter('chat.host_port');
        $httpBind   = 'http://' . $hostDomain . ($hostPort ? (':'.$hostPort) : '') . '/http-bind/';

        /**
         * Create a new XMPP Object with the required params
         *
         * @param string $jabberHost Jabber Server Host
         * @param string $boshUri    Full URI to the http-bind
         * @param string $resource   Resource identifier
         * @param bool   $useSsl     Use SSL (not working yet)
         * @param bool   $debug      Enable debug
         */
        $xmppPrebind = new \XmppPrebind($hostDomain, $httpBind, false, false, true);
        $xmppPrebind->connect($username, $password);
        $xmppPrebind->auth();
        return $xmppPrebind->getSessionInfo(); // array containing sid, rid and jid
    }


    /** Chat API */

    /**
     * Method to auth user from Chat Server.
     * Request called from Chat Server to validate user and auth it
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @uses ChatController:getUserByRequestCredentials() to check user auth validity
     *
     * @return Response A Response instance
     *
     * @Route(path="/api/chat/authentification/auth", name="chat_authentification_auth", defaults={"_project"="rpe"})
     */
    public function chatAuthentificationAuthAction(Request $request)
    {
        // Prepare response
        $response = new Response();
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('max-age', 0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-store', true);
        $response->setStatusCode(401);

        // Check request credentials
        if (null !== $this->getUserByRequestCredentials($request)) {
            $response->setStatusCode(200);
        }

        return $response;
    }

    /**
     * Method to check if user exists.
     * Request called from Chat Server to check if user exists
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/api/chat/authentification/isuser", name="chat_authentification_isuser", defaults={"_project"="rpe"})
     */
    public function chatAuthentificationUserAction(Request $request)
    {
        $email = $request->query->get('user', null);
        $host  = $request->query->get('host', null);

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (null !== $user = $this->getRepository('user')->findOneByEmailPro($email)) {
                if ($user->isEnabled()) {
                    return new Response();
                }
            }
        }

        return new Response(null, 401);
    }

    /**
     * Method to get the updated roster of user.
     * Request called from Chat Server to update the roster (relations list) of a user
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/api/chat/rosterupdate", name="chat_user_roster_update", defaults={"_project"="rpe"})
     */
    public function chatUserRosterUpdateAction(Request $request)
    {
        $contacts = array();

        if (null !== $user = $this->getUserByRequestCredentials($request)) {
            $chatHostDomain = $this->get('rpe.utils')->getParameter('chat.host_domain');
            foreach ($this->getRepository('user')->getAcceptedFriendsActive($user, true, true, null, null, null, false) as $friend) {
                $chatId = $friend->getJabberId($chatHostDomain);
                $contacts[$chatId] = array('name' => $friend->getFullname());
            }
        }

        $response = new JsonResponse();
        $response->setData($contacts);

        return $response;
    }

    /**
     * Get a user having credentials set in the request header "Authorization",
     * i.e.: Authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==
     *
     * @access private
     * @param  Request $request     A request instance
     * @return object|null
     */
    private function getUserByRequestCredentials(Request $request)
    {
        if (null !== $u = $request->get('u')) {
            $username = str_replace('\40', '@', $u);
            if (null !== $user = $this->getRepository('user')->findOneByEmailPro($username)) {
                return $user;
            }
        } else {
            $credentialsHeader = $request->headers->get('Authorization');
            $encodedCredentials = str_replace('Basic ', '', $credentialsHeader);
            if (null !== $userId = $this->get('session')->get('chat.auth.'.$encodedCredentials)) {
                return $this->getRepository('user')->find($userId);
            } else {
                if (false !== $credentialsHeader = base64_decode($encodedCredentials)) {
                    $credentialsHeader = explode(':', $credentialsHeader);
                    $username = isset($credentialsHeader[0]) ? $credentialsHeader[0] : '';
                    $username = str_replace('\40', '@', $username);
                    $password = isset($credentialsHeader[1]) ? $credentialsHeader[1] : '';
                }

                if (!empty($username) && !empty($password) && filter_var($username, FILTER_VALIDATE_EMAIL)) {
                    if (null !== $user = $this->getRepository('user')->findOneByEmailPro($username)) {
                        if ((null !== $userToken = $user->getMeta($user::META_TOKEN_CHAT)) && $userToken->getValue() === $password) {
                            if ($user->isEnabled()) {
                                $this->get('session')->set('chat.auth.'.$encodedCredentials, $user->getId());
                                return $user;
                            }
                        }
                    }
                }
            }
        }


        return null;
    }
}
