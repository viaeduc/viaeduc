<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Notification;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Notification controller
 *
 * @method Response notificationListAction()
 * @method Response notificationGlobalViewAction()
 * @method Response notificationViewAction($id)
 * @method Response messageNotificationMailAction($message_id, $contact_id)
 * @method Response requestNotificationMailAction($notification_id, $contact_id)
 *
 */
class NotificationController extends Controller
{
    /**
     * @access public
     *
     * @return Response A Response instance
     *
     * @Route(path="/menu/notifications", name="menu_notifications", defaults={"_project"="rpe"})
     */
    public function notificationListAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        # $notifications = $this->getRepository('notification')->findByUser($this->getUser());
        $user = $this->getUser();

        return $this->render('pum://page/notifications/ajax-popover-list.html.twig', array(
            'notifications' => $user->getReversedNotification(20)
        ));
    }

    /**
     * @access public
     * @param  Request $request         A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/menu/notification/globalview", name="menu_notification_globalview", defaults={"_project"="rpe"})
     */
    public function notificationGlobalViewAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        if (null !== $lastNotification = $this->getRepository('notification')->getLastNotification($user)) {
            $user_meta = $user->getMeta($user::META_NOTIFICATION_LAST_VIEW_ID);
            if (null === $user_meta) {
                $user_meta = $this->createObject('user_meta')
                    ->setUser($user)
                    ->setType('activity')
                    ->setMetaKey($user::META_NOTIFICATION_LAST_VIEW_ID)
                ;

                $user->addUserMeta($user_meta);
            }

            if ($lastNotification->getId() != $user_meta->getValue()) {
                $user_meta->setValue($lastNotification->getId());
                $this->persist($user, $user_meta)->flush();
            }
        }
        // reset counter of new notif to 0 after checks
        $session = $request->getSession();
        $sessionData = $session->get('counter');
        $sessionData['notification']['count'] = 0;
        $sessionData['notification']['time'] = time();
        $session->set('counter', $sessionData);

        return $this->render('pum://page/notifications/ajax-popover-list.html.twig', array(
            'notifications' => $user->getReversedNotification(20)
        ));
    }

    /**
     * @access public
     * @param  string  $id   The notification id
     *
     * @return Response A Response instance
     *
     * @Route(path="/menu/notification/view/{id}", name="menu_notification_view", defaults={"_project"="rpe"})
     */
    public function notificationViewAction($id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $notification = $this->getRepository('notification')->find($id);

        if (null === $notification) {
            $this->throwNotFound('error.notification.not_found');
        }

        $notification->treat();

        $this->persist($notification);
        $this->flush();

        $target = $this->getRepository($notification->getTargetType())->find($notification->getTargetId());
        $actor = $this->getRepository($notification->getActorType())->find($notification->getActorId());

        if ($target === null || $actor === null) {
            return $this->redirect($this->generateUrl('home'));
        }

        switch($notification->getType()) {
            case Notification::TYPE_PUBLICATION:
            case Notification::TYPE_SHARE_PUBLICATION:
            case Notification::TYPE_RESOURCE:
            case Notification::TYPE_RESOURCE_EDIT:
            case Notification::TYPE_SHARE_RESOURCE:
                return $this->redirect($this->generateUrl('publication', array('id' => $target->getId())));
                break;

            case Notification::TYPE_RECOMMEND:
                if ($notification->getTargetType() == 'post') {
                    return $this->redirect($this->generateUrl('publication', array('id' => $target->getId())));
                } elseif ($notification->getTargetType() == 'comment') {
                    return $this->redirect($this->generateUrl('publication', array('id' => $target->getPost()->getId(), 'comment_id' => $target->getId())));
                } else {
                    throw new \RuntimeException('Unknow type');
                }
                break;

            case Notification::TYPE_COMMENT:
                return $this->redirect($this->generateUrl('publication', array('id' => $target->getPost()->getId(), 'comment_id' => $target->getId())));
                break;

            case Notification::TYPE_RELATION_REQUEST:
                return $this->redirect($this->generateUrl('profil', array('id' => $this->get('rpe.hashid')->encode($target->getId()))));
                break;

            case Notification::TYPE_RELATION_ACCEPT:
                return $this->redirect($this->generateUrl('profil', array('id' => $this->get('rpe.hashid')->encode($target->getId()))));
                break;

            case Notification::TYPE_JOIN_REQUEST:
            case Notification::TYPE_BECAME_ADMIN:
                if ($notification->getTargetType() == "group") {
                    return $this->redirect($this->generateUrl('group', array('id' => $target->getId())));
                }
                return $this->redirect($this->generateUrl('profil', array('id' => $this->get('rpe.hashid')->encode($actor->getId()))));
                break;

            case Notification::TYPE_JOIN_INVITE:
                if ($notification->getTargetType() == "group") {
                    return $this->redirect($this->generateUrl('group', array('id' => $target->getId())));
                }
                if ($notification->getTargetType() == "blog") {
                    return $this->redirect($this->generateUrl('blog', array('id' => $target->getId())));
                }
                break;

            case Notification::TYPE_JOIN_USER_ACCEPT:
                return $this->redirect($this->generateUrl('group', array('id' => $target->getId())));
                break;

            case Notification::TYPE_JOIN_GROUP_ACCEPT:
                return $this->redirect($this->generateUrl('group', array('id' => $target->getId())));
                break;

            case Notification::TYPE_EVENT_INVITATION:
                return $this->redirect($this->generateUrl('agenda'));
                break;

            case Notification::TYPE_ANSWER:
                return $this->redirect($this->generateUrl('question', array('id' => $target->getId())));
                break;

            case Notification::TYPE_COAUTHOR:
                return $this->redirect($this->generateUrl('publication', array('id' => $target->getId())));
                break;

            case Notification::TYPE_FREE:
                return $this->redirect($notification->getUrl());
                break;
        }
    }


    /**
     * @access public
     * @param  string  $message_id   The message id
     * @param  string  $contact_id   The contact id
     *
     * @return Response A Response instance
     *
     * @Route(path="/mail/notification/message/{message_id}/{contact_id}", name="mail_notification_message", defaults={"_project"="rpe"})
     */
    public function messageNotificationMailAction($message_id, $contact_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $message = $this->getRepository('message')->find($message_id);
        $contact = $this->getRepository('user')->find($contact_id);

        if (null === $message || null === $contact) {
            return new Response('Error');
        }

        $commonRelations = $this->getRepository('friend')->getCommonFriends($message->getAuthor(), $contact);

        return $this->render('pum://emails/message.html.twig', array(
            'contact'            => $contact,
            'commonRelations'    => $commonRelations,
            'message'            => $message,
        ));
    }

    /**
     * Request notification content
     *
     * @access public
     * @param  string  $notification_id   The notification id
     * @param  string  $contact_id   The contact id
     *
     * @return Response A Response instance
     *
     * @Route(path="/mail/notification/request/{notification_id}/{contact_id}", name="mail_notification_request", defaults={"_project"="rpe"})
     */
    public function requestNotificationMailAction($notification_id, $contact_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $notification = $this->getRepository('notification')->find($notification_id);
        $contact = $this->getRepository('user')->find($contact_id);

        if (null === $notification || null === $contact) {
            return new Response('Error');
        }

        $target = $this->getRepository($notification->getTargetType())->find($notification->getTargetId());
        $actor = $this->getRepository($notification->getActorType())->find($notification->getActorId());

        return $this->render('pum://emails/waiting_list.html.twig', array(
            'contact'           => $contact,
            'notification'      => $notification,
        ));
    }

    /**
     * XHR Counter for countable fields
     *
     * @access public
     * @param  Request $request         A request instance
     * @param  string  $fields          fields to count
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax/notification/count", name="ajax_counter", defaults={"_project"="rpe"})
     */
    public function ajxInboxCounter(Request $request)
    {
        $fields = $request->query->get('fields', null);
        $response = new JsonResponse();
        $result = array(
            'time' => time(),
            'fields' => array()
        );

        if (!$request->isXmlHttpRequest()) {
            $response->setData(array(
                'error' => 'not ajax request'
            ));
            return $response;
        }

        if (null === $user = $this->getUser()) {
            $response->setData(array(
                'error' => 'user not defined'
            ));
            return $response;
        }

        if (null === $fields) {
            $response->setData(array(
                'error' => 'no field defined'
            ));
            return $response;
        }

        $fields = explode(',', $fields);
        $session = $request->getSession();
        foreach ($fields as $field) {
            $result['fields'][$field] = $this->checkCountInSession($session, $user, $field);
        }

        $response->setData($result);
        return $response;
    }


    /**
     * check if the counter already saved in session
     *
     * @param unknown $user
     * @param unknown $field
     * @param unknown $timeStamp
     */
    private function checkCountInSession ($session, $user, $field)
    {
       $sessionData = $session->get('counter');
       $current_time = time();
       $counter = array();

       $interval = $this->get('pum.vars')->getValue('counter_interval');
       $interval = (int) ($interval ? $interval : 120);

       if (is_null($sessionData) || !array_key_exists($field, $sessionData) || (($current_time - $sessionData[$field]['time']) > $interval) ) {

           switch ($field) {
               case 'notification':
                   $user_meta = $user->getMeta(User::META_NOTIFICATION_LAST_VIEW_ID);
                   if (null === $user_meta) {
                       $user_meta = $this->createObject('user_meta')
                        ->setUser($user)
                        ->setType('activity')
                        ->setMetaKey(User::META_NOTIFICATION_LAST_VIEW_ID);
                       $user->addUserMeta($user_meta);
                       $this->persist($user_meta, $user)->flush();
                   }
                   $count = $this->getRepository('notification')->getCountUnreadNotifications($user, $user_meta->getValue());
                   break;

               case 'inbox':
                   $count = $this->getRepository('discussion')->getCountUnreadDiscussions($user);
                   break;
           }
           $counter[$field] = array('count' => $count, 'time' => $current_time);

           // add to session
           $session->set('counter', is_null($sessionData) ? $counter : array_merge($sessionData, $counter));

           return $counter[$field];
       } else {
           return $sessionData[$field];
       }
    }

}
