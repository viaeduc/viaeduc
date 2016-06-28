<?php
namespace Rpe\PumBundle\Extension\Service;

use Pum\Bundle\CoreBundle\PumContext;

use Rpe\PumBundle\Model\Social\Notification;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Comment;
use Rpe\PumBundle\Model\Social\Post;

/**
 * @method void __construct($pumContext, $mailer)
 * @method void wait($type, $actor, $target, $receiver_id = null)
 * @method void create($users, $type, $actor, $target)
 *
 */
class Notifications extends ContextFactory
{
    /**
     * @var string $mailer 
     */
    private $mailer;

    /**
     * Construct function
     *
     * @access public
     * @param PumContext $pumContext
     * @param Mailer     $mailer
     * 
     * @return void
     */
    public function __construct($pumContext, $mailer)
    {
        parent::__construct($pumContext);

        $this->mailer = $mailer;
    }


    /**
     * Create a Notification Waiting entry
     * 
     * @access public
     * @param string      $type
     * @param PUM_OBJECT  $actor
     * @param PUM_OBJECT  $target
     * @param string      $receiver_id
     * 
     * @return void
     */
    public function wait($type, $actor, $target, $receiver_id = null)
    {
        $wait_notif = $this->createObject('social_notification_wait');
        $wait_notif->setTargetType($target::PUM_OBJECT);
        $wait_notif->setTargetId($target->getId());
        $wait_notif->setActorType($actor::PUM_OBJECT);
        $wait_notif->setActorId($actor->getId());
        $wait_notif->setType($type);
        $wait_notif->setReceiverId($receiver_id);

        $this->persist($wait_notif)->flush();
    }

    /**
     * Create a Notification entry
     * 
     * @access public
     * @param array       $users
     * @param string      $type
     * @param PUM_OBJECT  $actor
     * @param PUM_OBJECT  $target
     * 
     * @return void
     */
    public function create($users, $type, $actor, $target)
    {
        $mailType = null;

        switch($type) {
            case Notification::TYPE_PUBLICATION:
            case Notification::TYPE_RESOURCE:
            case Notification::TYPE_RESOURCE_EDIT:
            case Notification::TYPE_RECOMMEND:
            case Notification::TYPE_COMMENT:
            case Notification::TYPE_RELATION_ACCEPT:
            case Notification::TYPE_JOIN_USER_ACCEPT:
            case Notification::TYPE_JOIN_GROUP_ACCEPT:
            case Notification::TYPE_SHARE_PUBLICATION:
            case Notification::TYPE_SHARE_RESOURCE:
            case Notification::TYPE_ANSWER:
            case Notification::TYPE_BECAME_ADMIN:
                $mailType = 'your_news';
                break;

            case Notification::TYPE_RELATION_REQUEST:
            case Notification::TYPE_JOIN_REQUEST:
            case Notification::TYPE_JOIN_INVITE:
            case Notification::TYPE_EVENT_INVITATION:
            case Notification::TYPE_COAUTHOR:
            case Notification::TYPE_EDIT_PUBLICATION:
                $mailType = 'waiting_list';
                break;

            case Notification::TYPE_RESOURCE_PAD_CLOSE:
            case Notification::TYPE_RESOURCE_PAD_REOPEN:
            case Notification::TYPE_RESOURCE_PAD_CREATE:
                $mailType = 'etherpad_activation';
                break;
        }

        if (!is_array($users)) {
            $users = array($users);
        }

        foreach ($users as $user) {

            $notify = true;
            $sendMail = true;
            if (!($user instanceof User)) {
                continue;
            }
            switch($type) {
                case Notification::TYPE_PUBLICATION:
                    if ($target->getType() == Post::TYPE_WALL && !(($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_PUBLISH)) ? $meta->getValue() : true)) {
                        $notify = false;
                    }
                    if ($target->getType() == Post::TYPE_WALL && !(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_PUBLISH)) ? $meta->getValue() : true)) {
                        $sendMail = false;
                    }
                    if ($target->getType() == Post::TYPE_GROUP && !(($meta = $user->getMeta(User::META_NOTIFICATION_GROUP_SOMEONE_PUBLISH_MSG)) ? $meta->getValue() : true)) {
                        $notify = false;
                    }
                    if ($target->getType() == Post::TYPE_GROUP && !(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_GROUP_SOMEONE_PUBLISH_MSG)) ? $meta->getValue() : true)) {
                        $sendMail = false;
                    }
                    break;

                case Notification::TYPE_RESOURCE:
                case Notification::TYPE_RESOURCE_EDIT:
                    if ($target->getType() == Post::TYPE_WALL && !(($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_PUBLISH_RESOURCE)) ? $meta->getValue() : true)) {
                        $notify = false;
                    }
                    if ($target->getType() == Post::TYPE_WALL && !(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_PUBLISH_RESOURCE)) ? $meta->getValue() : true)) {
                        $sendMail = false;
                    }
                    if ($target->getType() == Post::TYPE_GROUP && !(($meta = $user->getMeta(User::META_NOTIFICATION_GROUP_SOMEONE_PUBLISH)) ? $meta->getValue() : true)) {
                        $notify = false;
                    }
                    if ($target->getType() == Post::TYPE_GROUP && !(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_GROUP_SOMEONE_PUBLISH)) ? $meta->getValue() : true)) {
                        $sendMail = false;
                    }
                    break;

                case Notification::TYPE_RECOMMEND:
                    if ($target instanceof Comment) {
                        $author = $target->getUser();
                    } else {
                        $author = $target->getAuthor();
                    }
                    if ($user === $author) {
                        if ($target instanceof Post && $target->getResource()) {
                            if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_RECOMMEND_RESOURCE)) ? $meta->getValue() : true)) {
                                $notify = false;
                            }
                            if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_RECOMMEND_RESOURCE)) ? $meta->getValue() : true)) {
                                $sendMail = false;
                            }
                        } else {
                            if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_RECOMMEND)) ? $meta->getValue() : true)) {
                                $notify = false;
                            }
                            if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_PUBLISH)) ? $meta->getValue() : true)) {
                                $sendMail = false;
                            }
                        }
                    }
                    break;

                case Notification::TYPE_COMMENT:
                    if ($user === $target->getPost()->getAuthor()) {
                        if ($target->getPost()->getResource()) {
                            if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_COMMENT_RESOURCE)) ? $meta->getValue() : true)) {
                                $notify = false;
                            }
                            if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_COMMENT_RESOURCE)) ? $meta->getValue() : true)) {
                                $sendMail = false;
                            }
                        } else {
                            if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_COMMENT)) ? $meta->getValue() : true)) {
                                $notify = false;
                            }
                            if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_COMMENT)) ? $meta->getValue() : true)) {
                                $sendMail = false;
                            }
                        }
                    }
                    break;

                case Notification::TYPE_SHARE_PUBLICATION:
                    if ($user === $target->getAuthor()) {
                        if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_SHARE)) ? $meta->getValue() : true)) {
                            $notify = false;
                        }
                        if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_SHARE)) ? $meta->getValue() : true)) {
                            $sendMail = false;
                        }
                    }
                    break;

                case Notification::TYPE_SHARE_RESOURCE:
                    if ($user === $target->getAuthor()) {
                        if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_SHARE_RESOURCE)) ? $meta->getValue() : true)) {
                            $notify = false;
                        }
                        if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_SHARE_RESOURCE)) ? $meta->getValue() : true)) {
                            $sendMail = false;
                        }
                    }
                    break;

                case Notification::TYPE_ANSWER:
                    if ($user === $target->getAuthor()) {
                        if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MYCONTENT_SOMEONE_ANSWER)) ? $meta->getValue() : true)) {
                            $notify = false;
                        }
                        if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_MYCONTENT_SOMEONE_ANSWER)) ? $meta->getValue() : true)) {
                            $sendMail = false;
                        }
                    }
                    break;

                case Notification::TYPE_RESOURCE_PAD_CLOSE:
                case Notification::TYPE_RESOURCE_PAD_REOPEN:
                case Notification::TYPE_RESOURCE_PAD_CREATE:
                    if ($user === $target->getAuthor()) {
                        if (!(($meta = $user->getMeta(User::META_NOTIFICATION_COLLABORATIVE_RESOURCE_ACCESS)) ? $meta->getValue() : true)) {
                            $notify = false;
                        }
                        if (!(($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_COLLABORATIVE_RESOURCE_ACCESS)) ? $meta->getValue() : true)) {
                            $sendMail = false;
                        }
                    }
                    break;
            }

            if ($notify) {
                $notification = $this->createObject('notification');
                $notification->setUser($user);
                $notification->setType($type);
                $notification->setDate(new \DateTime());
                $notification->setTreated(false);

                $notification->setActorType($actor::PUM_OBJECT);
                $notification->setActorId($actor->getId());

                $notification->setTargetType($target::PUM_OBJECT);
                $notification->setTargetId($target->getId());

                $this->persist($notification);
                $this->flush();

                if (null !== $mailType && $sendMail == true) {
                    // get email address
                    $useMailPro = ($meta = $user->getMeta(User::META_NOTIFICATION_MAIL_ADDRESS_PRO)) ? (bool)$meta->getValue() : true;
                    $email = $useMailPro ? $user->getEmailPro() : $user->getEmailPrivate();

                    if (null !== $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) { // && strpos($email, "argonautes") !== false) {
                            $this->mailer->send(array(
                                'subject' => 'Votre actualité sur Viaeduc',
                                'from' => 'no-reply@viaeduc.fr',
                                'to' => $email,
                                'template' => array(
                                    'name' => 'pum://emails/'.$mailType.'.html.twig',
                                    'vars' => array(
                                        'notification'   => $notification,
                                        'contact'        => $user,
                                        'type'           => $type,
                                        'post'           => $target,
                                    )
                                )
                            ));
                        }
                    }
                }
            }
        }
    }
}
