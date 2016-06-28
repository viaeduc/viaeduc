<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Social\UserInEvent;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;

class ApiEventController extends ApiOAuthController
{
    /**
     * @Post(path="/events", name="apiv1_events", defaults={"_project"="rpe"})
     */
    public function postEventsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $fields = array('title' => array('required' => true), 'startDate' => array('required' => true), 'endDate' => array('required' => true), 'placeName' => array('required' => true), 'placeAddress' => array('required' => true), 'description' => array('required' => true), 'privacy' => array('required' => true), 'owner_id' => array('required' => true), 'participants' => array());
        
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        if (!in_array($fields['privacy']['value'], array('public', 'private'))) {
            return $this->getBadValueFieldError('privacy', array('public', 'private'));
        }
        
        if (null === ($user = $this->getRepository('user')->find($fields['owner_id']['value']))) {
            return $this->getDoesntExistsFieldError('user');
        }
        
        if (null !== $fields['participants']['value']) {
            $rawParticipants = $fields['participants']['value'];
            $participantsId = explode(',', $rawParticipants);
            
            foreach ($participantsId as $participantId) {
                if (null === ($participant = $this->getRepository('user')->find($participantId))) {
                    return $this->getDoesntExistsFieldError('participants', $participantId);
                }
            }
        }

        $event = $this->createObject('event');
        $event->setTitle($fields['title']['value']);
        $event->setStartDate(new \DateTime($fields['startDate']['value']));
        $event->setEndDate(new \DateTime($fields['endDate']['value']));
        $event->setPlaceName($fields['placeName']['value']);
        $event->setPlaceAddress($fields['placeAddress']['value']);
        $event->setDescription($fields['description']['value']);
        $event->setPrivacy($fields['privacy']['value']);
        $event->setOwnerUser($user);
         
        $userInEvent = $this->createObject('user_in_event');
        $userInEvent->setEvent($event);
        $userInEvent->setUser($user);
        $userInEvent->setDate(new \DateTime());
        $userInEvent->setStatus(UserInEvent::STATUS_ACCEPT);
        $this->persist($userInEvent);
        
        if (null !== $fields['participants']['value']) {
            foreach ($participantsId as $participantId) {
                $participant = $this->getRepository('user')->find($participantId);

                $userInEvent = $this->createObject('user_in_event');
                $userInEvent->setEvent($event);
                $userInEvent->setUser($participant);
                $userInEvent->setDate(new \DateTime());
                $userInEvent->setStatus(UserInEvent::STATUS_ACCEPT);
                $this->persist($userInEvent);
            }
        }

        $this->persist($event);
        $this->flush();
        
        return $this->getInsertSuccess('event', $event->getId());
    }

    /**
     * @Put(path="/events/{event_id}", name="apiv1_events", defaults={"_project"="rpe"})
     */
    public function putEventsAction(Request $request, $event_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $event = $this->getRepository('event')->find($event_id);

            if (null === $event) {
                return $this->getNotFoundError();
            }

            if ($oAuthUser !== $event->setOwnerUser() && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $fields = array('title' => array(), 'startDate' => array(), 'endDate' => array(), 'placeName' => array(), 'placeAddress' => array(), 'description' => array(), 'privacy' => array(), 'owner_id' => array(), 'participants' => array());
        
        $fields = $this->initPostFields($request, $fields);
        
        if (null !== $fields['privacy']['value']) {
            if (!in_array($fields['privacy']['value'], array('public', 'private'))) {
                return $this->getBadValueFieldError('privacy', array('public', 'private'));
            }
        }
        
        if (null !== $fields['owner_id']['value']) {
            if (null === ($user = $this->getRepository('user')->find($fields['owner_id']['value']))) {
                return $this->getDoesntExistsFieldError('user');
            }
        }
        
        if (null !== $fields['participants']['value']) {
            $rawParticipants = $fields['participants']['value'];
            $participantsId = explode(',', $rawParticipants);
            
            foreach ($participantsId as $participantId) {
                if (null === ($participant = $this->getRepository('user')->find($participantId))) {
                    return $this->getDoesntExistsFieldError('participants', $participantId);
                }
            }
        }

        if (null !== $fields['title']['value']) {
            $event->setTitle($fields['title']['value']);
        }

        if (null !== $fields['startDate']['value']) {
            $event->setStartDate(new \DateTime($fields['startDate']['value']));
        }

        if (null !== $fields['endDate']['value']) {
            $event->setEndDate(new \DateTime($fields['endDate']['value']));
        }

        if (null !== $fields['placeName']['value']) {
            $event->setPlaceName($fields['placeName']['value']);
        }

        if (null !== $fields['placeAddress']['value']) {
            $event->setPlaceAddress($fields['placeAddress']['value']);
        }

        if (null !== $fields['description']['value']) {
            $event->setDescription($fields['description']['value']);
        }

        if (null !== $fields['privacy']['value']) {
            $event->setPrivacy($fields['privacy']['value']);
        }

        if (null !== $fields['owner_id']['value']) {
            $event->setOwnerUser($user);

            $userInEvent = $this->createObject('user_in_event');
            $userInEvent->setEvent($event);
            $userInEvent->setUser($user);
            $userInEvent->setDate(new \DateTime());
            $userInEvent->setStatus(UserInEvent::STATUS_ACCEPT);
            $this->persist($userInEvent);
        }
        
        if (null !== $fields['participants']['value']) {
            foreach ($participantsId as $participantId) {
                $participant = $this->getRepository('user')->find($participantId);

                $userInEvent = $this->createObject('user_in_event');
                $userInEvent->setEvent($event);
                $userInEvent->setUser($participant);
                $userInEvent->setDate(new \DateTime());
                $userInEvent->setStatus(UserInEvent::STATUS_ACCEPT);
                $this->persist($userInEvent);
            }
        }

        $this->persist($event);
        $this->flush();
        
        return $this->getUpdateSuccess('event', $event->getId());
    }

    /**
     * @Delete(path="/events/{event_id}", name="apiv1_events", defaults={"_project"="rpe"})
     */
    public function deleteEventsAction(Request $request, $event_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $event = $this->getRepository('event')->find($event_id);

            if (null === $event) {
                return $this->getNotFoundError();
            }
            
            if ($oAuthUser !== $event->getOwnerUser() && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }

        $this->remove($event);
        $this->flush();

        return $this->getDeleteSuccess('event', $event_id);
    }

    /**
     * @Get(path="/events", name="apiv1_events", defaults={"_project"="rpe"})
     */
    public function getEventsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'event');
    }
    
    /**
     * @Get(path="/events/{event_id}", name="apiv1_event", defaults={"_project"="rpe"})
     */
    public function getEventAction(Request $request, $event_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $event = $this->getRepository('event')->find($event_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInEvent($event)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectAction($request, 'event', $event_id);
    }
}
