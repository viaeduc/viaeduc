<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Social\Notification;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;

class ApiNotificationController extends ApiOAuthController
{
    /**
     * @Post(path="/notifications", name="apiv1_notifications", defaults={"_project"="rpe"})
     */
    public function postNotificationsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $fields = array('content' => array('required' => true), 'url' => array('required' => true), 'user_id' => array('required' => true));
        
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        if (null === ($user = $this->getRepository('user')->find($fields['user_id']['value']))) {
            return $this->getDoesntExistsFieldError('user');
        }
        
        $notification = $this->createObject('notification');
        $notification->setContent($fields['content']['value']);
        $notification->setUrl($fields['url']['value']);
        $notification->setUser($user);
        $notification->setType(Notification::TYPE_FREE);
        $notification->setDate(new \DateTime());
        $this->persist($notification);
        $this->flush();
        
        return $this->getInsertSuccess('notification', $notification->getId());
    }

    /**
     * @Delete(path="/notifications/{notification_id}", name="apiv1_notifications", defaults={"_project"="rpe"})
     */
    public function deleteNotificationsAction(Request $request, $event_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $notification = $this->getRepository('notification')->find($notification_id);

            if (null === $notification) {
                return $this->getNotFoundError();
            }
            
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $this->remove($notification);
        $this->flush();

        return $this->getDeleteSuccess('notification', $notification_id);
    }

    /**
     * @Get(path="/notifications", name="apiv1_notifications", defaults={"_project"="rpe"})
     */
    public function getNotificationsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'notification', array('title' => 'Viaeduc - Notifications', 'description' => 'List of Viaeduc notifications', 'pathname' => 'notification'));
    }
    
    /**
     * @Get(path="/notifications/{notification_id}", name="apiv1_notification", defaults={"_project"="rpe"})
     */
    public function getNotificationAction(Request $request, $notification_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $notification = $this->getRepository('notification')->find($notification_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $notification->getUser()) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectAction($request, 'notification', $notification_id);
    }
}
