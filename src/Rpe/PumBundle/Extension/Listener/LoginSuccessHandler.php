<?php
namespace Rpe\PumBundle\Extension\Listener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Rpe\PumBundle\Extension\Service\Logs;
use Rpe\PumBundle\Model\Social\User;
use Pum\Bundle\CoreBundle\PumContext;
use Pum\Core\Extension\EmFactory\Doctrine\ObjectEntityManager;

/**
 * Login success
 * 
 * @method void __construct(HttpUtils $httpUtils, array $options, Logs $rpeLogger, ObjectEntityManager $oem)
 * @method void onAuthenticationSuccess(Request $request, TokenInterface $token)
 * @method void generateUserChatToken($user)
 */
class LoginSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    /**
     * @var Object $rpeLogger   Rpe logger instance
     */
    protected $rpeLogger;

    /**
     * Construct function
     * 
     * @access public
     * @param HttpKernelInterface $httpKernel   
     * @param HttpUtils $httpUtils
     * @param array $options
     * @param Logs $rpeLogger
     * @param LoggerInterface $logger
     * 
     * @return void
     */
    public function __construct(HttpUtils $httpUtils, array $options, Logs $rpeLogger, ObjectEntityManager $oem)
    {
        $this->rpeLogger = $rpeLogger;
        $this->oem       = $oem;

        parent::__construct($httpUtils, $options);
    }

    /**
     * Authenticate success event
     *
     * @access public
     * @param  Request                 $request     A request instance
     * @param  TokenInterface          $token       Token object
     *
     * @return void
     *
     * @see \Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler::onAuthenticationFailure()
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $this->setProviderKey('front_secured_area');

        $user = $token->getUser();

        // Generate token for chat
        $this->generateUserChatToken($user);

        // associer sso user if there is sso_info
        if($info_sso = $request->query->get('sso')){
            $info_sso = unserialize(urldecode($info_sso));
            
            if(isset($info_sso['user'])){
                $ent = $this->oem->createObject('social_user_ent');
                $ent->setIdSso($info_sso['user']);
                $ent->setProfile($info_sso['profile']);
                $ent->setUser($user);
                
                $this->oem->persist($ent, $user);
                $this->oem->flush();
            }
        }

        // get user timezone
        if ( ($user instanceof User) && (null !== $userTimezone = $user->getMeta($user::META_TIMEZONE)) ) {
            $userTimezone = $userTimezone->getValue();
        } else {
            $userTimezone = $this->rpeLogger->getPumContext()->getContainer()->getParameter('default_timezone');
        }
        // set var session
        $session = $request->getSession();
        $session->set('user.timezone', $userTimezone);
        
        // initialize the counters in session, (notification, message)
        $counter = array();
        $user_meta = $user->getMeta(User::META_NOTIFICATION_LAST_VIEW_ID);
        if (null === $user_meta) {
            $user_meta = $this->oem->createObject('user_meta')
                ->setUser($user)
                ->setType('activity')
                ->setMetaKey(User::META_NOTIFICATION_LAST_VIEW_ID);
           $user->addUserMeta($user_meta);
           $this->oem->persist($user_meta, $user);
           $this->oem->flush();
        }
        $counter['notification'] = array(
            'count' => $this->oem->getRepository('notification')->getCountUnreadNotifications($user, $user_meta->getValue()), 
            'time' => time());
        $counter['inbox'] = array(
            'count' => $this->oem->getRepository('discussion')->getCountUnreadDiscussions($user), 
            'time' => time());
        $session->set('counter', $counter);
        
        return parent::onAuthenticationSuccess($request, $token);
    }

    /**
     *  Generate a chat token
     *  
     * @access private
     * @param User $user
     * 
     * @return void
     */
    private function generateUserChatToken($user)
    {
        $metaKey   = $user::META_TOKEN_CHAT;
        $user_meta = $user->getMeta($metaKey);

        if (null === $user_meta) {
            $user_meta = $this->oem->createObject('user_meta')
                ->setUser($user)
            ;
            $user->addUserMeta($user_meta);
        }

        $ttl   = (string) (time() + (3600*24*3));
        $token = base64_encode(md5($user->getId().mt_rand().microtime().uniqid()).'#_#_#'.$ttl.'#_#_#'.$user->getId());

        $user_meta
            ->setType('authentification')
            ->setMetaKey($metaKey)
            ->setValue($token)
        ;

        $this->oem->persist($user_meta, $user);
        $this->oem->flush();
    }
}
