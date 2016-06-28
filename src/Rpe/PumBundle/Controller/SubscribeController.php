<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Subscribe controller
 * 
 * @method Response subscribeUserAction(Request $request, $mode, $id)
 * @method boolean  checkVisibility($item, $type)
 */
class SubscribeController extends Controller
{
    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $mode        Mode of subscription
     * @param  string  $id          User id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/subscribe-action/user/{mode}/{id}", name="subscribe_user", defaults={"_project"="rpe"})
     */
    public function subscribeUserAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            $id = $request->query->get('id', null);
            if (null !== $id) {
                if (null !== $followed = $this->getRepository('user')->find($id)) {
                    if ($this->checkVisibility($followed, 'user')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('subscribe')->getSubscribe($user, $followed)) {
                                $subscribe = $this->createObject('subscribe')
                                    ->setUser($user)
                                    ->setFollowed($followed)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addFollowed($subscribe);
                                $followed->addFollower($subscribe);

                                $this->persist($subscribe, $followed, $user)->flush();

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $subscribe = $this->getRepository('subscribe')->getSubscribe($user, $followed)) {
                                $user->removeFollowed($subscribe);
                                $followed->removeFollower($subscribe);
                                $this->remove($subscribe);

                                $this->persist($followed, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/subscribe-request.html.twig', array(
                                'id'        => $id,
                                'subscribe' => $subscribe,
                                'object'    => $followed,
                                'type'      => 'user'
                            ));
                        }
                    }
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * checkVisibility
     *
     * @param Object $item Item object
     * @param string $type Type of item
     *
     * @return boolean
     */
    private function checkVisibility($item, $type)
    {
        return true;
    }
}