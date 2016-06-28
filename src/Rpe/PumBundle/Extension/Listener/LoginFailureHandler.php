<?php
namespace Rpe\PumBundle\Extension\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Rpe\PumBundle\Extension\Service\Logs;

use Pum\Bundle\CoreBundle\PumContext;

/**
 * @method void __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, array $options, Logs $rpeLogger, LoggerInterface $logger = null)
 * @method AuthenticationException onAuthenticationFailure(Request $request, AuthenticationException $exception)
 *
 */
class LoginFailureHandler extends DefaultAuthenticationFailureHandler
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
    public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, array $options, Logs $rpeLogger, LoggerInterface $logger = null)
    {
        $this->rpeLogger = $rpeLogger;

        parent::__construct($httpKernel, $httpUtils, $options, $logger);
    }

    /**
     * Authenticate fail event
     * 
     * @access public
     * @param  Request                 $request     A request instance
     * @param  AuthenticationException $exception
     * 
     * @return AuthenticationException
     * 
     * @see \Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler::onAuthenticationFailure()
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return parent::onAuthenticationFailure($request, $exception);
    }
}
