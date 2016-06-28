<?php
namespace Rpe\PumBundle\Extension\Listener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Rpe\PumBundle\Extension\Service\Logs;

use Pum\Bundle\CoreBundle\PumContext;

/**
 * @method void __construct(HttpUtils $httpUtils, Logs $rpeLogger)
 * @method void onLogoutSuccess(Request $request)
 *
 */
class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    /**
     * @var Object $rpeLogger   Rpe logger instance
     */
    protected $rpeLogger;

    /**
     * Construct function
     *
     * @access public
     * @param HttpUtils $httpUtils
     * @param Logs $rpeLogger       
     *
     * @return void
     */
    public function __construct(HttpUtils $httpUtils, Logs $rpeLogger)
    {
        $this->rpeLogger = $rpeLogger;

        parent::__construct($httpUtils);
    }

    /**
     * After logout action event
     * 
     * @access public
     * @param  Request                 $request     A request instance
     * 
     * @return void
     * 
     * @see \Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler::onLogoutSuccess()
     */
    public function onLogoutSuccess(Request $request)
    {
        // unset var session
        $session = new Session();
        $session->set('user.timezone', null);

        return parent::onLogoutSuccess($request);
    }
}
