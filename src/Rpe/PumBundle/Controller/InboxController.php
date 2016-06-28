<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Message;
use Rpe\PumBundle\Model\Social\Discussion;
use Rpe\PumBundle\Model\Social\UserInDiscussion;
use Rpe\PumBundle\Model\Social\Log;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Inbox messager controller
 *
 * @method Response inboxAction(Request $request, $recipient_id)
 * @method Response navDiscussionListAction(Request $request, $page)
 * @method Response ajaxDiscussionListAction(Request $request, $page, $active)
 * @method Response discussionAction(Request $request, $id, $recipient_id)
 * @method Response ajaxDiscussionAction(Request $request, $id)
 * @method Response deleteDiscussionAction(Request $request, $id)
 * @method Response ajaxNewMessageAction(Request $request, $recipient_id)
 * @method Response ajaxRecipientsMessageAction(Request $request)
 *
 */
class InboxController extends Controller
{
    /**
     * @access public
     * @param  Request $request      A request instance
     * @param  string  $recipient_id User id of recipient
     *
     * @return Response A Response instance
     *
     * @Route(path="/inbox/{recipient_id}", name="inbox", defaults={"_project"="rpe", "recipient_id"=null})
     */
    public function inboxAction(Request $request, $recipient_id)
    {
        return $this->discussionAction($request, null, $recipient_id);
    }

    /**
     * @access public
     * @param  Request $request      A request instance
     * @param  string  $page         Page number of discussion
     *
     * @return Response A Response instance
     *
     * @Route(path="/inbox/nav/discussionlist/{page}", name="ajax_discussionlist_nav", defaults={"_project"="rpe", "page"="1"})
     */
    public function navDiscussionListAction(Request $request, $page)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // Remettre le nombre de notifs à zero
        $user = $this->getUser();

        if (null !== $lastDiscussion = $this->getRepository('discussion')->getLastActiveDiscussion($user)) {
            $user_meta = $user->getMeta($user::META_MESSAGE_LAST_VIEW_DATE);
            if (null === $user_meta) {
                $user_meta = $this->createObject('user_meta')
                    ->setUser($user)
                    ->setType('activity')
                    ->setMetaKey($user::META_MESSAGE_LAST_VIEW_DATE)
                ;

                $user->addUserMeta($user_meta);
            }

            if ($lastDiscussion->getUpdateDate()->getTimestamp() != $user_meta->getValue()) {
                $user_meta->setValue($lastDiscussion->getUpdateDate()->getTimestamp());
                $this->persist($user, $user_meta)->flush();
            }
        }

        $byPage      = 9;
        $discussions = $this->getRepository('discussion')->getDiscussionFromUser($this->getUser(), true);

        $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($discussions, true, false);
        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        // reset counter of new message to 0 after checks
        $session = $request->getSession();
        $sessionData = $session->get('counter');
        $sessionData['inbox']['count'] = 0;
        $sessionData['inbox']['time'] = time();
        $session->set('counter', $sessionData);

        return $this->render('pum://page/chat/ajax-popover-list.html.twig', array(
            'discussions' => $pager
        ));
    }

    /**
     * @access public
     * @param  Request $request      A request instance
     * @param  string  $page         Page number of discussion
     * @param  string  $active       Whether active the list
     *
     * @return Response A Response instance
     *
     * @Route(path="inbox/ajax/discussionlist/{page}/{active}", name="ajax_discussionlist", defaults={"_project"="rpe", "page"="1", "active"=null})
     */
    public function ajaxDiscussionListAction(Request $request, $page, $active)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $byPage      = 9;
        $discussions = $this->getRepository('discussion')->getDiscussionFromUser($this->getUser(), true);

        $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($discussions, true, false);
        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/ajax/messages/messages-side.html.twig', array(
            'active' => $active,
            'pager'  => $pager
        ));
    }

    /**
     * @access public
     * @param  Request $request      A request instance
     * @param  string  $id           Discussion id
     * @param  string  $recipient_id The recipient id
     *
     * @return Response A Response instance
     *
     * @Route(path="/inbox/discussion/{id}/{recipient_id}", name="discussion", defaults={"_project"="rpe", "id"=null, "recipient_id"=null})
     */
    public function discussionAction(Request $request, $id, $recipient_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($id == null && $recipient_id == null) {
            $user = $this->getUser();
            $discussions = $user->getDiscussions();
            if (count($discussions)) {
                $id = $discussions[0]->getDiscussion()->getId();
            }
        }

        return $this->render('pum://page/chat/inbox.html.twig', array(
            'discussion_id' => $id,
            'recipient_id' => $recipient_id,
            'group_id'     => $request->query->get('group_id', null)
        ));
    }

    /**
     * XHR Method to retrieve a discussion between users.
     *
     * @access public
     * @param  Request $request      A request instance
     * @param  string  $id           Discussion id
     *
     * @return Response A Response instance
     *
     * @Route(path="/inbox/ajax/discussion/{id}", name="ajax_discussion", defaults={"_project"="rpe"})
     */
    public function ajaxDiscussionAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        if (null !== $discussion = $this->getRepository('discussion')->find($id)) {
            if (null !== $user_in_discussion = $this->getRepository('user_in_discussion')->getUserInDiscussion($user, $discussion)) {
                $lastMessage       = $discussion->getLastMessage();
                $lastAuthor        = $lastMessage->getAuthor();
                $lastUpdateMessage = $lastMessage->getDate();

                if ($user !== $lastAuthor && $lastUpdateMessage > $user_in_discussion->getViewDate()) {
                    $user_in_discussion->setViewDate(new \DateTime());

                    $this->persist($user_in_discussion)->flush();
                }

                // Reply form
                $message = $this->createObject('message');
                $form    = $this->createNamedForm('message', 'pum_object', $message, array(
                    'form_view'   => $this->createFormViewByName('message', 'simple', $update = false),
                    'with_submit' => true
                ));

                if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                    $datetime = new \DateTime();
                    $message
                        ->setAuthor($user)
                        ->setDiscussion($discussion)
                        ->setDate($datetime)
                    ;

                    $user->addMessage($message);
                    $discussion->addMessage($message);
                    $discussion->setUpdateDate($datetime);
                    $user_in_discussion->setViewDate($datetime);

                    $this->persist($user, $message, $discussion, $user_in_discussion)->flush();

                    if ($request->isXmlHttpRequest()) {
                        return $this->render('pum://includes/common/componants/messages/messages-single.html.twig', array(
                            'message' => $message
                        ));
                    }

                    return $this->redirect($this->generateUrl('discussion', array('id' => $discussion->getId())));
                }

                return $this->render('pum://page/ajax/messages/messages-main.html.twig', array(
                    'discussion' => $discussion,
                    'form'       => $form->createView()
                ));
            }

            $this->throwAccessDenied('error.inbox.access_denied');
        }

        $this->throwNotFound('error.inbox.not_found');
    }

    /**
     * XHR Method to delete a discussion.
     *
     * @access public
     * @param  Request $request      A request instance
     * @param  string  $id           Discussion id
     *
     * @return Response A Response instance
     *
     * @Route(path="/inbox/discussion/{id}/delete", name="delete_discussion", defaults={"_project"="rpe"})
     */
    public function deleteDiscussionAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            $user = $this->getUser();

            if (null !== $discussion = $this->getRepository('discussion')->find($id)) {
                if (null !== $user_in_discussion = $this->getRepository('user_in_discussion')->getUserInDiscussion($user, $discussion)) {
                    $user->removeDiscussion($discussion);
                    $this->remove($user_in_discussion);

                    $this->flush();

                    return new Response('OK');
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * XHR Method to create a new message.
     *
     * @access public
     * @param  Request $request         A request instance
     * @param  string  $recipient_id    Recipient id
     *
     * @return Response A Response instance
     *
     * @Route(path="/inbox/ajax/new-message/{recipient_id}", name="new_message", defaults={"_project"="rpe", "recipient_id"=null})
     */
    public function ajaxNewMessageAction(Request $request, $recipient_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $me = $this->getUser();
        $friends = array();
        foreach ($this->getRepository('user')->getAcceptedFriends($me) as $friend) {
            $u_id = $friend->getId();
            $showContact = $friend->canContactMe($me);
            if($showContact) {
                $friends[$u_id] = $friend->getFullname();
            }
        }

        $recipientOptions = array(
            'mapped'    => false,
            'allow_add' => true,
            'type'      => 'choice',
            'options'   => array(
                'required'  => false,
                'choices'   => $friends
            )
        );

        $group_id = $request->query->get('group_id', null);
        if (null !== $recipient_id) {
            if ($group_id && !array_key_exists($recipient_id, $friends)) {
                $group = $this->getRepository('group')->find($group_id);
                $recipient = $this->getRepository('user')->find($recipient_id);

                if ($group && $recipient) {
                    $showContact = $recipient->canContactMe($me);
                    if($showContact) {
                        $friends[$recipient_id] = $recipient->getFullname();
                    }
                }
            }
            $recipientOptions['options']['choices'] = $friends;
            // Default choice index in the friends array
            $recipientOptions['data'] = array($recipient_id);
        }

        $discussion = $this->createObject('discussion');
        $form       = $this->createNamedForm('discussion', 'pum_object', $discussion, array(
            'form_view'   => $this->createFormViewByName('discussion', 'create', $update = false),
            'with_submit' => false
        ));
        $form
            ->add('destinataires', 'collection', $recipientOptions)
            ->add('message', 'textarea', array(
                'mapped' => false,
            ))
            ->add('submit', 'submit')
        ;

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $ids = $form->get('destinataires')->getData();
            if (empty($ids)) {
                throw new \RuntimeException('You have to set at least one recipient');
            }

            // Create discussion
            $datetime = new \DateTime();
            $discussion
                ->setStatus(Discussion::STATUS_OPENED)
                ->setType(Discussion::TYPE_ACTIV)
                ->setCreateDate($datetime)
                ->setUpdateDate($datetime)
            ;

            // Add myself in discussion
            $me_in_discussion = $this->createObject('user_in_discussion')
                ->setUser($me)
                ->setDiscussion($discussion)
                ->setStatus(UserInDiscussion::STATUS_OWNER)
                ->setDate($datetime)
                ->setViewDate($datetime);
            ;
            $me->addDiscussion($me_in_discussion);

            // Add recipients to discussion
            foreach ($ids as $id) {
                if (null !== $user = $this->getRepository('user')->find($id)) {
                    $user_in_discussion = $this->createObject('user_in_discussion')
                        ->setUser($user)
                        ->setDiscussion($discussion)
                        ->setStatus(UserInDiscussion::STATUS_INVITED)
                        ->setDate($datetime)
                    ;
                    $discussion->addUser($user_in_discussion);
                    $user->addDiscussion($user_in_discussion);

                    $this->persist($user_in_discussion, $user);
                }
            }

            // Add your message to discussion
            $message = $this->createObject('message')
                ->setAuthor($me)
                ->setDiscussion($discussion)
                ->setContent($form->get('message')->getData())
                ->setDate($datetime)
            ;
            $discussion->addMessage($message);
            $me->addMessage($message);

            $this->persist($me, $discussion, $me_in_discussion, $message)->flush();

            $discussionUsers = $this->getRepository('user_in_discussion')->findByDiscussion($discussion);
            foreach ($ids as $id) {
                if (null !== $contact = $this->getRepository('user')->find($id)) {
                    $commonRelations = $this->getRepository('friend')->getCommonFriends($me, $contact);
                    // get email address
                    $useMailPro = ($meta = $contact->getMeta(User::META_NOTIFICATION_MAIL_ADDRESS_PRO)) ? (bool)$meta->getValue() : true;
                    $email = $useMailPro ? $contact->getEmailPro() : $contact->getEmailPrivate();
                    if (null !== $email) {
                        $this->get('rpe.mailer')->send(array(
                            'subject' => 'Vous avez un nouveau message sur Viaeduc',
                            'from' => 'no-reply@viaeduc.fr',
                            'to' => $email,
                            'template' => array(
                                'name' => 'pum://emails/message.html.twig',
                                'vars' => array(
                                    'message'             => $message,
                                    'contact'             => $contact,
                                    'commonRelations'     => $commonRelations
                                )
                            )
                        ));
                    }
                }
            }

            foreach ($discussionUsers as $discussionUser) {
                $this->get('rpe.logs')->create($me, Log::TYPE_SEND_MESSAGE, $discussionUser, $message);
            }

            return $this->redirect($this->generateUrl('discussion', array('id' => $discussion->getId())));
        }

        return $this->render('pum://page/ajax/messages/messages-new-message.html.twig', array(
            'form'     => $form->createView(),
            'friends'  => $friends,
            'group_id' => $group_id,
            'recipient_id' => $recipient_id
        ));
    }

    /**
     * XHR Method to get a list of relations that can be contacted.
     *
     * @access public
     * @param  Request $request         A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/inbox/ajax/recipients-message", name="ajax_recipients_message", defaults={"_project"="rpe"})
     */
    public function ajaxRecipientsMessageAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (!$request->isXmlHttpRequest()) {
            return new Response('ERROR');
        }

        $results = $this->getRepository('user')->getAcceptedFriends($this->getUser());

        $group_id = $request->query->get('group_id', null);
        $recipient_id = $request->query->get('recipient_id', null);
        if ($group_id && $recipient_id) {
            $group = $this->getRepository('group')->find($group_id);
            $recipient = $this->getRepository('user')->find($recipient_id);
            if ($group && $recipient) {
                $showContact = $recipient->canContactMe($this->getUser());
                if($showContact) {
                    $results[] = $recipient;
                }
            }
        }

        $existed = array();
        $res = array_map(function ($result) use (&$existed) {
            $me = $this->getUser();
            $showContact = $result->canContactMe($me);

            if ($showContact && !in_array($result->getId(), $existed)) {
                $existed[] = $result->getId();
                return array(
                    'id'    => $result->getId(),
                    'value' => $this->get('pum.view')->renderPumObject($result, 'search_row')
                );
            } else {
                return false;
            }
        }, $results);

        $res = array_filter($res);
        return new Response(json_encode(array_values($res)));
    }
}
