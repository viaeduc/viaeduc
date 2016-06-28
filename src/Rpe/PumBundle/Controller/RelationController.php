<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Friend;
use Rpe\PumBundle\Model\Social\Notification;
use Rpe\PumBundle\Model\Social\Log;
use Pagerfanta\Pagerfanta;

/**
 * Library controller
 *
 * @method Response indexAction()
 * @method Response relationsListAction(Request $request, $page)
 * @method Response relationsAction($mode)
 * @method Response relationDetailAction(Request $request)
 * @method Response relationPendingAction(Request $request)
 * @method Response relationAddAction(Request $request)
 * @method Response relationAcceptAction(Request $request)
 * @method Response relationRejectAction(Request $request)
 *
 */
class RelationController extends Controller
{
    /**
     * Relations list mode
     */
    const LISTMODE_ALLRELATIONS         =   'all';
    const LISTMODE_SUGGESTEDRELATIONS   =   'suggested';
    const LISTMODE_MYRELATIONS          =   'my_relations';

    /**
     * @access public
     *
     * @return Response A Response instance
     *
     * @Route(path="/my-relations", name="my_relations", defaults={"_project"="rpe"})
     */
    public function indexAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        return $this->render('pum://page/my_relationships.html.twig', array());
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $page        The page number
     *
     * @return Response A Response instance
     *
     * @Route(path="/relationslist/{page}", name="ajax_relationslist", defaults={"_project"="rpe", "page"="1"})
     */
    public function relationsListAction(Request $request, $page)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // Page
        $byPage  = 24;

        // Mode
        $mode = $request->query->get('mode', self::LISTMODE_ALLRELATIONS);

        // Get Groups
        if (self::LISTMODE_MYRELATIONS == $mode) {
            $relations = $this->getRepository('user')->getAcceptedFriendsActive($this->getUser(), true, false);
            //$relations = $this->getUser()->getAcceptedFriends();
           // $pager = new \Pagerfanta\Adapter\DoctrineCollectionAdapter($relations);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($relations, true, false);
        } elseif (self::LISTMODE_SUGGESTEDRELATIONS == $mode) {
            $relations = $this->getRepository('user')->getSuggestedFriends($this->getUser(), true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($relations, true, false);
        } else {
            $relations = $this->getRepository('user')->getPotentialUsers(true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($relations, true, false);
        }

        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/relations/ajax-relationslist.html.twig', array(
            'mode'  => $mode,
            'pager'  => $pager
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $mode        The page number
     *
     * @return Response A Response instance
     *
     * @Route(path="/relations/{mode}", name="relations", defaults={"_project"="rpe", "mode"= null})
     */
    public function relationsAction($mode)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null == $mode) {
            $mode = self::LISTMODE_ALLRELATIONS;
        } elseif (!in_array($mode, array(self::LISTMODE_ALLRELATIONS, self::LISTMODE_MYRELATIONS, self::LISTMODE_SUGGESTEDRELATIONS))) {
            $this->throwNotFound('error.filters.not_found');
        }

        return $this->render('pum://page/relations/relations.html.twig', array(
            'mode'  =>  $mode
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/relation-detail", name="relation_detail", defaults={"_project"="rpe"})
     */
    public function relationDetailAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $me = $this->getUser();

        if ($request->isXmlHttpRequest()) {
            $id = $request->query->get('id', null);
            if (null !== $id) {
                $user = $this->getRepository('user')->find($id);

                $canContactMe = ($meta = $user->getMeta(User::META_CONFIDENTIAL_CONTACT_ME)) ? $meta->getValue() : 'myfriends';

                $showContact = false;
                if ($user !== $me) {
                    switch($canContactMe) {
                        case 'everybody':
                            $showContact = true;
                            break;
                    }
                    if ($me->isFriend($user)) {
                        $showContact = true;
                    }
                } else {
                    $showContact = true;
                }

                // if (null !== $friendship_detail && $friendship_detail->getStatus() == Friend::STATUS_ACCEPTED) {
                    return $this->render('pum://page/ajax/relation/relation-popin-content.html.twig', array(
                        'friend'        => $user,
                        'user'          => $me,
                        'showContact'   => $showContact,
                        'relation_detail' => $this->getRepository('friend')->getRelation($me, $user, true)
                    ));
                // }
            }
        }

        return new Response('ERROR');
    }

    /**
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/relation/pending", name="relation_pending", defaults={"_project"="rpe"})
     */
    public function relationPendingAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $page      = 1;
        $limit     = 50;
        $offset    = ($page - 1) * $limit;
        $orderby   = 'id';
        $direction = "DESC";

        $user = $this->getUser();
        $pending_requests = $this->getRepository('friend')
            ->createQueryBuilder('f')
            ->andWhere('f.friend = :user')
            ->andWhere('f.status = :status')
            ->setParameters(array(
                'user'   => $user,
                'status' => Friend::STATUS_ON_HOLD,
            ))
            ->addOrderBy('f.'.$orderby, $direction)
            //->setFirstResult($offset)
            //->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        $suggested_relations = $this->getRepository('user')->getSuggestedFriends($this->getUser(), false, 8);

        return $this->render('pum://page/relationships_waiting_list.html.twig', array(
            'suggested_relations'   => $suggested_relations,
            'pending_requests'      => $pending_requests
        ));
    }

    /**
     * Add a relation
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/relation-add", name="relation_add", defaults={"_project"="rpe"})
     */
    public function relationAddAction(Request $request)
    {
        $id = $request->query->get('id', null);
        $redirect = $request->query->get('redirect', false);
        if (null !== $id) {
            $user   = $this->getUser();
            $friend = $this->getRepository('user')->find($id);
            if (null !== $friend) {
                $friendship_detail = $this->getRepository('friend')->getRelation($this->getUser(), $friend, true);

                if (null === $friendship_detail) {
                    // Create relation
                    $relation = $this->createObject('friend')
                        ->setUser($user)
                        ->setFriend($friend)
                        ->setStatus(Friend::STATUS_ON_HOLD)
                        ->setDate(new \Datetime())
                    ;
                    $user->addFriend($relation);
                    $this->persist($relation, $user)->flush();

                    $this->get('rpe.notifications')->wait(Notification::TYPE_RELATION_REQUEST, $user, $user, $id);
                    return $this->render('pum://page/ajax/action/profile-request.html.twig', array(
                        'id'     => $id,
                        'relation_detail' => $relation,
                        'style'  => $request->query->get('style', null)
                    ));
                } else if(Friend::STATUS_ON_HOLD === $friendship_detail->getStatus()){
                    // resend request
                    $this->get('rpe.notifications')->wait(Notification::TYPE_RELATION_REQUEST, $user, $user, $id);
                    if($redirect != false){
                        return $this->redirect($this->generateUrl($redirect));
                    }
                }
            }
        }
        return new Response('ERROR');
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/relation-accept", name="relation_accept", defaults={"_project"="rpe"})
     */
    public function relationAcceptAction(Request $request)
    {
        $id = $request->query->get('id', null);
        if (null !== $id) {
            $friend = $this->getRepository('friend')->find($id);

            $user = $this->getUser();
            if (null !== $friend && $friend->getFriend() === $user) {
                // Accept current demand
                $friend->setStatus(Friend::STATUS_ACCEPTED);

                // Create reverse relation
                $inverse_friend = $this->createObject('friend')
                    ->setUser($user)
                    ->setFriend($friend->getUser())
                    ->setStatus(Friend::STATUS_ACCEPTED)
                    ->setDate(new \Datetime())
                ;
                $user->addFriend($inverse_friend);
                $this->persist($friend, $inverse_friend, $user)->flush();

                $this->get('rpe.notifications')->wait(Notification::TYPE_RELATION_ACCEPT, $user, $user, $friend->getUser()->getId());
                $this->get('rpe.logs')->create($friend->getUser(), Log::TYPE_BECAME_FRIEND, $user, $friend->getUser());

                // Request Chat roster update
                $this->get('rpe.chat')->requestRosterUpdate($user, $friend->getUser());

                if ($request->isXmlHttpRequest()){
                    if (null === $request->query->get('action')) {
                        return new Response('Vous avez accepté la demande');
                    } else {
                        return $this->render('pum://page/ajax/action/profile-request.html.twig', array(
                            'id'     => $id,
                            'relation_detail' => $friend,
                            'style'  => $request->query->get('style', null)
                        ));
                    }
                } else{
                    return $this->redirect($this->generateUrl('relations', array('mode' => self::LISTMODE_MYRELATIONS)));
                }
            }
        }

        if ($request->isXmlHttpRequest()){
            return new Response('ERROR');
        }
        else{
            return $this->redirect($this->generateUrl('login'));
        }
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/relation-reject", name="relation_reject", defaults={"_project"="rpe"})
     */
    public function relationRejectAction(Request $request)
    {
        $id = $request->query->get('id', null);
        $redirect = $request->query->get('redirect', false);
        if (null !== $id) {
            $relation_detail = $this->getRepository('friend')->find($id);

            $user = $this->getUser();
            if (null !== $relation_detail && ($relation_detail->getFriend() === $user || $relation_detail->getUser() === $user)) {
                $asker = $relation_detail->getUser();
                if ($user === $relation_detail->getUser()) {
                    $friend = $relation_detail->getFriend();
                    $id = $friend->getId();
                } else {
                    $friend = $relation_detail->getUser();
                    $id = $friend->getId();
                }

                $asker->removeFriend($relation_detail);
                $this->persist($asker);

                // Request Chat roster update
                $this->get('rpe.chat')->requestRosterUpdate($user, $friend);

                $relation_reverse = $this->getRepository('friend')->getRelation($friend, $user, true);
                if (null !== $relation_reverse) {
                    $friend->removeFriend($relation_reverse);
                    $this->persist($friend);
                }
                $this->flush();

                if($redirect != false){
                    return $this->redirect($this->generateUrl($redirect));
                }
                // return new Response('OK');
                return $this->render('pum://page/ajax/action/profile-request.html.twig', array(
                    'id'     => $id,
                    'style'  => $request->query->get('style', null)
                ));
            }
        }

        return new Response('ERROR');
    }
}
