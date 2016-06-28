<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Mailer controller
 * 
 * @method Response myMessagesAction()
 * @method Response myNewMessageAction()
 * @method Response myDiffusionListAction()
 * 
 */
class MyMailController extends Controller
{
    /**
     * @access public
     *
     * @return Response A Response instance
     *  
     * @Route(path="/my-messages", name="my_messages", defaults={"_project"="rpe"})
     */
    public function myMessagesAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/chat/inbox.html.twig');
    }


    /**
     * @access public
     *
     * @return Response A Response instance
     *  
     * @Route(path="/my-new-message", name="my_new_message", defaults={"_project"="rpe"})
     */
    public function myNewMessageAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/my_new_message.html.twig');
    }


    /**
     * @access public
     *
     * @return Response A Response instance
     * 
     * @Route(path="/my-diffusion-list", name="my_diffusion_list", defaults={"_project"="rpe"})
     */
    public function myDiffusionListAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/my_diffusion_list.html.twig');
    }
}
