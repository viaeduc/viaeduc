<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Social\Message;
use Rpe\PumBundle\Model\Social\Discussion;
use Rpe\PumBundle\Model\Social\UserInDiscussion;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;

class ApiMessageController extends ApiOAuthController
{
    /**
     * @Post(path="/messages", name="apiv1_messages", defaults={"_project"="rpe"})
     */
    public function postMessagesAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        $fields = array('recipients' => array(), 'discussion_id' => array(), 'content' => array('required' => true));
        
        $fields = $this->initPostFields($request, $fields);
        
        if (null === $fields['recipients']['value'] && null === $fields['discussion_id']['value']) {
            return $this->getNorEmptyFieldsError(array('recipients', 'discussion_id'));
        }
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }

        $rawRecipients = $fields['recipients']['value'];
        $recipientsId = explode(',', $rawRecipients);
        
        foreach ($recipientsId as $recipientId) {
            if (null === ($recipient = $this->getRepository('user')->find($recipientId))) {
                return $this->getDoesntExistsFieldError('recipients', $recipientId);
            }
        }

        $datetime = new \DateTime();

        if (null === $fields['discussion_id']['value'] || null === ($discussion = $this->getRepository('discussion')->find($fields['discussion_id']['value']))) {
            $discussion = $this->createObject('discussion')
                ->setStatus(Discussion::STATUS_OPENED)
                ->setType(Discussion::TYPE_ACTIV)
                ->setCreateDate($datetime)
                ->setUpdateDate($datetime)
            ;
            
            $senderInDiscussion = $this->createObject('user_in_discussion')
                ->setUser($oAuthUser)
                ->setDiscussion($discussion)
                ->setStatus(UserInDiscussion::STATUS_OWNER)
                ->setDate($datetime)
                ->setViewDate($datetime);
            ;
            
            $oAuthUser->addDiscussion($senderInDiscussion);
            
            $this->persist($senderInDiscussion);

            foreach ($recipientsId as $recipientId) {
                if (null !== ($recipient = $this->getRepository('user')->find($recipientId))) {
                    $userInDiscussion = $this->createObject('user_in_discussion')
                        ->setUser($recipient)
                        ->setDiscussion($discussion)
                        ->setStatus(UserInDiscussion::STATUS_INVITED)
                        ->setDate($datetime)
                    ;

                    $discussion->addUser($userInDiscussion);
                    $recipient->addDiscussion($userInDiscussion);

                    $this->persist($userInDiscussion, $recipient);
                }
            }
        }

        $message = $this->createObject('message')
            ->setAuthor($oAuthUser)
            ->setDiscussion($discussion)
            ->setContent($fields['content']['value'])
            ->setDate($datetime)
        ;

        $discussion->addMessage($message);
        $oAuthUser->addMessage($message);

        $this->persist($discussion, $message);
        $this->flush();
        
        return $this->getInsertSuccess('message', $message->getId());
    }
    
    /**
     * @Delete(path="/messages/{message_id}", name="apiv1_messages", defaults={"_project"="rpe"})
     */
    public function deleteMessagesAction(Request $request, $message_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $message = $this->getRepository('message')->find($message_id);
            
            if (null === $message) {
                return $this->getNotFoundError();
            }
            
            if ($oAuthUser !== $message->getOwnerUser() && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $this->remove($message);
        $this->flush();
        
        return $this->getDeleteSuccess('message', $message_id);
    }
    
    /**
     * @Get(path="/discussions", name="apiv1_discussions", defaults={"_project"="rpe"})
     */
    public function getDiscussionsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'discussion');
    }
    
    /**
     * @Get(path="/messages", name="apiv1_messages", defaults={"_project"="rpe"})
     */
    public function getMessagesAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'message', array('title' => 'Viaeduc - Messages', 'description' => 'List of Viaeduc messages', 'pathname' => 'message'));
    }
    
    /**
     * @Get(path="/messages/{message_id}", name="apiv1_message", defaults={"_project"="rpe"})
     */
    public function getMessageAction(Request $request, $message_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $message = $this->getRepository('message')->find($message_id);
            $discussion = $message->getDiscussion();
            
            if (!$oAuthUser->isAdmin()) {
                if (null === $discussion->getUserInDiscussion($oAuthUser)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectAction($request, 'message', $message_id);
    }
}
