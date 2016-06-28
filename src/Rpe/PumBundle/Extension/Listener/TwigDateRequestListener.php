<?php
namespace Rpe\PumBundle\Extension\Listener;

use Pum\Bundle\CoreBundle\PumContext;
use Rpe\PumBundle\Model\Social\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @method  PumContext  __construct(PumContext $pumContext)
 * @method  void        onRequest(GetResponseEvent $event)
 * @method  array       getSubscribedEvents()
 *
 */
class TwigDateRequestListener implements EventSubscriberInterface
{
    /**
     * @var PumContext  $pumContext   Pum context object
     */
    private $pumContext;

    /**
     * @var Twig_Environment $twig    Twig object
     */
    private $twig;

    /**
     * @var SecurityContextInterface   Security context
     */
    private $securityContext;


    /**
     * Construct function
     * 
     * @access public 
     * @param PumContext $pumContext    Pum context object
     * @return void
     */
    public function __construct(PumContext $pumContext)
    {
        $this->pumContext      = $pumContext;
        $this->twig            = $pumContext->getContainer()->get('twig');
        $this->securityContext = $pumContext->getContainer()->get('security.context');
    }

    /**
     * On request event
     * 
     * @access public
     * @param GetResponseEvent $event
     * 
     * @return void
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (null !== $token = $this->securityContext->getToken()) {
            // get user
            $user = $token->getUser();

            // user must be a front user
            if ($user instanceof User) {
                $session = new Session();
                if (null === $userTimeZone = $session->get('user.timezone')) {
                    $userTimeZone = $this->pumContext->getContainer()->getParameter('default_timezone');
                }
                $this->twig->getExtension('core')->setTimezone($userTimeZone);
            }
        }
    }

    /**
     * Get subscribed events
     * 
     * @access public
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onRequest',
        );
    }
}
