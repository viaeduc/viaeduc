<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\UserInEvent;
use Rpe\PumBundle\Model\Social\Notification;
use Pum\Bundle\TypeExtraBundle\Model\Coordinate;
use Rpe\PumBundle\Extension\Plugin\GoogleMapAPI;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Agenda controller.
 * Controller for users Agenda actions (view, add, etc.)
 *
 * @method Response agendaAction(Request $request, $year = null, $month = null, $mode = null)
 * @method Response agendaEventAction(Request $request, $event_id = null, $mode = null)
 * @method Response agendaGroupAction(Request $request, $group_id)
 * @method Response eventListAction(Request $request, $year = null, $month = null, $mode = null)
 * @method Response eventListGroupAction(Request $request, $year = null, $month = null, $group_id = null)
 * @method Response eventListEventAction(Request $request, $year = null, $month = null, $event_id = null)
 * @method Response eventDetailsAction(Request $request, $id)
 * @method Response eventRSVPAction(Request $request, $id, $answer, $style)
 * @method Response eventParticipantListAction(Request $request, $id)
 * @method Response eventRelationListAction(Request $request, $id)
 * @method Response editFormEventAction(Request $request, $id)
 * @method Response createFormEventAction(Request $request, $group_id = null)
 * @method array    postEvent(Request $request, $id = null, $group = null)
 * @method Response ajaxParticipantsEventAction(Request $request)
 * @method Response calendarAction(Request $request, $group_id = null)
 *
 */
class AgendaController extends Controller
{
    /** All the events */
    const LISTMODE_ALL      = "all";

   /** Private events */
    const LISTMODE_PRIVATE  = "private";

   /** Public events */
    const LISTMODE_PUBLIC   = "public";

   /** User personal events */
    const LISTMODE_PERSONAL = "personal";

   /** Group's events */
    const LISTMODE_GROUP    = "group";

    /**
     * Main Agenda action.
     * Display full month agenda view
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $year        Year
     * @param  string  $month       Month
     * @param  string  $mode        Mode (one of the LISTMODE_* constants)
     *
     * @return Response A Response instance
     *
     * @Route(path="/agenda/{year}/{month}/{mode}", name="agenda", defaults={"_project"="rpe"})
     */
    public function agendaAction(Request $request, $year = null, $month = null, $mode = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null === $year) {
            $year = date('Y');
        }

        if (null === $month) {
            $month = date('m');
        }

        if (null == $mode && !in_array($mode, array(self::LISTMODE_PRIVATE, self::LISTMODE_PUBLIC))) {
            $mode = self::LISTMODE_ALL;
        }
        $agendaFilters = array(
            self::LISTMODE_ALL,
            self::LISTMODE_PUBLIC,
            self::LISTMODE_PRIVATE
        );


        $currentMonth = new \DateTime();
        $currentMonth->modify('first day of '.$year.'-'.$month);

        $previousMonth = new \DateTime();
        $previousMonth->modify('first day of '.$year.'-'.$month.' - 1 month');

        $nextMonth = new \DateTime();
        $nextMonth->modify('first day of '.$year.'-'.$month.' + 1 month');

        return $this->render('pum://page/agenda.html.twig', array(
            'currentMonth' => $currentMonth,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
            'agendaFilters' => $agendaFilters,
            'mode'          => $mode
        ));
    }

    /**
     * Agenda events action.
     * Display a list of events (depending on filters/mode & specific event) for Group module & Dashboard widget
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $event_id    Id of event
     * @param  string  $mode        Mode (one of the LISTMODE_* constants)
     *
     * @return Response A Response instance
     *
     * @Route(path="/agenda-event/{event_id}/{mode}", name="agenda_event", defaults={"_project"="rpe"})
     */
    public function agendaEventAction(Request $request, $event_id = null, $mode = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $event = $this->getRepository('event')->find($event_id);

        $year = date('Y');
        $month = date('m');

        $currentMonth = new \DateTime();
        $currentMonth->modify('first day of '.$year.'-'.$month);

        $previousMonth = new \DateTime();
        $previousMonth->modify('first day of '.$year.'-'.$month.' - 1 month');

        $nextMonth = new \DateTime();
        $nextMonth->modify('first day of '.$year.'-'.$month.' + 1 month');

        if (null == $mode && !in_array($mode, array(self::LISTMODE_PRIVATE, self::LISTMODE_PUBLIC))) {
            $mode = self::LISTMODE_ALL;
        }
        $agendaFilters = array(
            self::LISTMODE_PUBLIC,
            self::LISTMODE_PRIVATE,
            self::LISTMODE_ALL
        );

        return $this->render('pum://page/agenda.html.twig', array(
            'currentMonth' => $currentMonth,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
            'event' => $event,
            'agendaFilters' => $agendaFilters,
            'mode'          => $mode
        ));
    }

    /**
     * Agenda of a group.
     * Display the agenda of a group with activated module
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $group_id    Id of group
     *
     * @return Response A Response instance
     *
     * @Route(path="/agenda_group/{group_id}", name="agenda_group", defaults={"_project"="rpe"})
     */
    public function agendaGroupAction(Request $request, $group_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($group_id);

        if (!$group) {
            return new Response('Error');
        }

        $year = date('Y');
        $month = date('m');

        $currentMonth = new \DateTime();
        $currentMonth->modify('first day of '.$year.'-'.$month);

        $previousMonth = new \DateTime();
        $previousMonth->modify('first day of '.$year.'-'.$month.' - 1 month');

        $nextMonth = new \DateTime();
        $nextMonth->modify('first day of '.$year.'-'.$month.' + 1 month');

        $agendaFilters = array(
            self::LISTMODE_PUBLIC,
            self::LISTMODE_PRIVATE,
            self::LISTMODE_ALL
        );

        return $this->render('pum://page/agenda.html.twig', array(
            'currentMonth'  => $currentMonth,
            'previousMonth' => $previousMonth,
            'nextMonth'     => $nextMonth,
            'agendaFilters' => $agendaFilters,
            'mode'          => self::LISTMODE_ALL,
            'group'         => $group
        ));
    }

    /**
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $year        Year
     * @param  string  $month       Month
     * @param  string  $mode        Mode (one of the LISTMODE_* constants)
     *
     * @uses AgendaController:eventListGlobalAction to return events
     *
     * @return Response A Response instance
     *
     * @Route(path="/eventlist/{year}/{month}/{mode}", name="ajax_eventlist", defaults={"_project"="rpe"})
     */
    public function eventListAction(Request $request, $year = null, $month = null, $mode = null)
    {
        return($this->eventListGlobalAction($request, $year, $month, null, null, $mode));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $year        Year
     * @param  string  $month       Month
     * @param  string  $group_id    Id of group
     *
     * @uses AgendaController:eventListGlobalAction to return events
     *
     * @return Response A Response instance
     *
     * @Route(path="/eventlist_group/{year}/{month}/{group_id}", name="ajax_eventlist_group", defaults={"_project"="rpe"})
     */
    public function eventListGroupAction(Request $request, $year = null, $month = null, $group_id = null)
    {
        return($this->eventListGlobalAction($request, $year, $month, $group_id));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $year        Year
     * @param  string  $month       Month
     * @param  string  $event_id    Id of event
     *
     * @uses AgendaController:eventListGlobalAction to return events
     *
     * @return Response A Response instance
     *
     * @Route(path="/eventlist_event/{year}/{month}/{event_id}", name="ajax_eventlist_event", defaults={"_project"="rpe"})
     */
    public function eventListEventAction(Request $request, $year = null, $month = null, $event_id = null)
    {
        return($this->eventListGlobalAction($request, $year, $month, null, $event_id));
    }

    /**
     * Display a list of events from specific filters (groups, events, etc.).
     *
     * @access private
     * @param  Request $request     A request instance
     * @param  string  $year        Year
     * @param  string  $month       Month
     * @param  string  $group_id    Id of group
     * @param  string  $event_id    Id of event
     * @param  string  $mode        Mode (one of the LISTMODE_* constants)
     *
     * @return Response A Response instance
     */
    private function eventListGlobalAction(Request $request, $year = null, $month = null, $group_id = null, $event_id = null, $mode = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        if (null === $year) {
            $year = date('Y');
        }

        if (null === $month) {
            $month = date('m');
        }

        if (null !== $group_id) {
            $group = $this->getRepository('group')->find($group_id);

            if (!$group) {
                return new Response('Error');
            }

            $events = $this->getRepository('event')->findByOwnerGroup($group);
        } else {
            if ($user->isInvited()) {
                $groups = array();
                foreach ($user->getMyGroups() as $uig) {
                    $groups[] = $uig->getGroup()->getId();
                }
                $events = $this->getRepository('event')->findByYearMonth($year, $month, $mode, $groups);
            } else {
                $events = $this->getRepository('event')->findByYearMonth($year, $month, $mode);
            }
        }

        $event = null;

        if (null !== $event_id) {
            $event = $this->getRepository('event')->find($event_id);
        }

        $currentMonth = new \DateTime();
        $currentMonth->modify('first day of '.$year.'-'.$month);

        $previousMonth = new \DateTime();
        $previousMonth->modify('first day of '.$year.'-'.$month.' - 1 month');

        $nextMonth = new \DateTime();
        $nextMonth->modify('first day of '.$year.'-'.$month.' + 1 month');

        return $this->render('pum://page/agenda/ajax-eventlist.html.twig', array(
            'user' => $user,
            'events' => $events,
            'currentMonth' => $currentMonth,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
            'deployEvent' => $event
        ));
    }

    /**
     * Display details of an event.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of event
     *
     * @return Response A Response instance
     *
     * @Route(path="/event_details/{id}", name="ajax_eventdetails", defaults={"_project"="rpe"})
     */
    public function eventDetailsAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $event = $this->getRepository('event')->find($id);
        $userRelations = $this->getRepository('user_in_event')->findByEventAndUserRelationsAndStatus($event, $user, UserInEvent::STATUS_ACCEPT);

        return $this->render('pum://page/agenda/ajax-eventdetails.html.twig', array(
            'user' => $user,
            'event' => $event
        ));
    }

    /**
     * Action to accept or reject an invitation to an event.
     *
     * @access public
     * @param  Request  $request     A request instance
     * @param  integer  $id          Id of event
     * @param  string   $answer      Action to perform, accept|reject
     * @param  string   $style       Style of buttons
     *
     * @return Response A Response instance
     *
     * @Route(path="/eventrsvp/{id}/{answer}/{style}", name="ajax_event_rsvp", defaults={"_project"="rpe"})
     */
    public function eventRSVPAction(Request $request, $id, $answer, $style)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $event = $this->getRepository('event')->find($id);

        if (!$event) {
            return new Response('Error');
        }

        $status = false;

        switch($answer) {
            case 'accept':
                $status = UserInEvent::STATUS_ACCEPT;
                break;

            case 'reject':
                $status = UserInEvent::STATUS_REJECT;
                break;
        }

        if (!$status) {
            return new Response('Error');
        }

        if ($userInEvent = $this->getRepository('user_in_event')->findByEventAndUser($event, $user)) {
            $userInEvent->setStatus($status);
        } else {
            $userInEvent = $this->createObject('user_in_event')
                ->setEvent($event)
                ->setUser($user)
                ->setStatus($status)
                ->setDate(new \DateTime())
            ;
        }

        $this->persist($userInEvent);
        $this->flush();

        if ($style == 'mail') {
            return $this->redirect($this->generateUrl('agenda_event', array('event_id' => $id)));
        } else {
            return $this->render('pum://includes/common/componants/events/buttons-rsvp.html.twig', array(
                'user' => $user,
                'event' => $event,
                'style' => $style
            ));
        }
    }

    /**
     * Display the list of participants to an event.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of event
     *
     * @return Response A Response instance
     *
     * @Route(path="/event_participantlist/{id}", name="ajax_event_participantlist", defaults={"_project"="rpe"})
     */
    public function eventParticipantListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $event = $this->getRepository('event')->find($id);

        $participants = $this->getRepository('user_in_event')->findByEventAndStatus($event, UserInEvent::STATUS_ACCEPT);
        $userRelations = $this->getRepository('user_in_event')->findByEventAndUserRelationsAndStatus($event, $user, UserInEvent::STATUS_ACCEPT);

        return $this->render('pum://page/agenda/ajax-event_participantlist.html.twig', array(
            'user' => $user,
            'event' => $event,
            'participants' => $participants,
            'userRelations' => $userRelations
        ));
    }

    /**
     * Display a list of relations participating to the event.
     * The list of relations of the current user logged in that are participating to the event
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of event
     *
     * @return Response A Response instance
     *
     * @Route(path="/event_relationlist/{id}", name="ajax_event_relationlist", defaults={"_project"="rpe"})
     */
    public function eventRelationListAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $event = $this->getRepository('event')->find($id);
        $userRelations = $this->getRepository('user_in_event')->findByEventAndUserRelationsAndStatus($event, $user, UserInEvent::STATUS_ACCEPT);

        return $this->render('pum://page/agenda/ajax-event_relationlist.html.twig', array(
            'user' => $user,
            'event' => $event,
            'userRelations' => $userRelations
        ));
    }

    /**
     * Action to create and process the event's form.
     * Create and display the event's form, and process it when submitted.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $group_id    Id of group
     *
     * @uses AgendaController:postEvent() to process the submission of the form
     *
     * @return Response A Response instance
     *
     * @Route(path="/create/event/{group_id}", name="publish_event", defaults={"_project"="rpe"})
     */
    public function createFormEventAction(Request $request, $group_id = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = null;

        if (null !== $group_id) {
            $group = $this->getRepository('group')->find($group_id);

            if (!$group) {
                return new Response('Error');
            }
        }

        $result = $this->postEvent($request, null, $group);

        if ($result['result']) {
            // return $this->redirect($this->generateUrl('edit_event', array('id' => $result['event']->getId())));
            return $this->redirect($this->generateUrl('agenda_event', array('event_id' => $result['event']->getId())));
        }

        if (null !== $group_id && !$result['formIsSubmitted']) {
            return $this->render('pum://page/agenda/form-publish-minimal.html.twig', array(
                'publishTypeActive' => 'event',
                'form'              => $result['form']->createView(),
                'group'             => $group
            ));
        } else {
            return $this->render('pum://page/agenda/form-publish.html.twig', array(
                'publishTypeActive' => 'event',
                'form'              => $result['form']->createView()
            ));
        }
    }

    /**
     * Action to edit an event.
     * Display the event's edition form and process it when submitted
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of event
     *
     * @uses AgendaController:postEvent() to process the submission of the form
     *
     * @return Response A Response instance
     *
     * @Route(path="/edit/event/{id}", name="edit_event", defaults={"_project"="rpe"})
     */
    public function editFormEventAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $result = $this->postEvent($request, $id);

        if ($result['result']) {
            // return $this->redirect($this->generateUrl('edit_event', array('id' => $result['event']->getId())));
            return $this->redirect($this->generateUrl('agenda_event', array('event_id' => $result['event']->getId())));
        }

        return $this->render('pum://page/agenda/form-publish.html.twig', array(
            'publishTypeActive' => 'event',
            'edit'              => true,
            'form'              => $result['form']->createView(),
            'id'                => $id
        ));
    }

    /**
     * Process the post submission of an event.
     * Create or update an Event based on form submission
     *
     * @access private
     * @param  Request $request     A request instance
     * @param  string  $group      Id of group
     * @param  string  $id          Id of event
     *
     * @return array   Array containing treated result
     */
    private function postEvent(Request $request, $id = null, $group = null)
    {
        $user = $this->getUser();

        if (null !== $id) {
            $event = $this->getRepository('event')->find($id);
        } else {
            $event = $this->createObject('event');
        }

        $eventForm = $this->createNamedForm('event', 'pum_object', $event, array(
            'attr'        => array('class' => 'event-form', 'id' => 'simple-event-form'),
            'form_view'   => $this->createFormViewByName('event', 'create', $update = false)
        ));
        $eventForm
            ->add('participants', 'collection', array(
                'mapped'    => false,
                'allow_add' => true,
                'type'      => 'number',
                'required'  => false,
            ))
            ->add('timezone', 'rpe_timezone', array('data' => $event->getTimezone() ?: $this->getUserTimezone()))
        ;
        // set dates options
        $eventForm
            ->add('startDate', 'date', array(
                'data' => $event->getStartDate(),
                'label' => 'Début',
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'view_timezone' => $event->getTimezone() ?: date_default_timezone_get(),
                'constraints' => array(
                    new NotBlank(),
                ),
            ))
            ->add('endDate', 'date', array(
                'data' => $event->getEndDate(),
                'label' => 'Fin',
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'view_timezone' => $event->getTimezone() ?: date_default_timezone_get(),
                'constraints' => array(
                    new NotBlank(),
                ),
            ))
        ;

        $result = false;

        if ($request->isMethod('POST')) {
            $formIsSubmitted = $eventForm->handleRequest($request)->isSubmitted();
            if ($eventForm->isValid()) {
                if ($placeAddress = $eventForm->get('placeAddress')->getData()) {
                    $gmap = new GoogleMapAPI();
                    list($status, $null, $addressLat, $addressLng) = $gmap->geocoding($placeAddress);

                    $event->setPlace(new Coordinate($addressLat, $addressLng));
                }

                $event->setOwnerUser($user);
                $event->setDate(new \DateTime());
                // set dates with selected timezone and save them in default configuration timezone
                if (null === $id) {
                    $eventTimezone = new \DateTimeZone($eventForm->get('timezone')->getData());
                    foreach (array('startDate' => 'setStartDate', 'endDate' => 'setEndDate') as $fieldName => $setDate) {
                        if ($aDate = $eventForm->get($fieldName)->getData()) {
                            $aDate = new \DateTime(date('Y-m-d H:i', $aDate->getTimestamp()), $eventTimezone);
                            $aDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));

                            $event->$setDate($aDate);
                        }
                    }
                }

                if (null !== $group) {
                    $event->setOwnerGroup($group);
                }

                // If this is a new event, add the owner in the participants
                if (null === $id) {
                    $userInEvent = $this->createObject('user_in_event')
                        ->setEvent($event)
                        ->setUser($user)
                        ->setStatus(UserInEvent::STATUS_ACCEPT)
                        ->setDate(new \DateTime())
                    ;

                    $this->persist($userInEvent);
                }

                foreach ($eventForm->get("participants")->getData() as $participantId) {
                    $participant = $this->getRepository('user')->find($participantId);

                    $userInEvent = $this->createObject('user_in_event')
                        ->setEvent($event)
                        ->setUser($participant)
                        ->setStatus(UserInEvent::STATUS_INVITED)
                        ->setDate(new \DateTime())
                    ;

                    $this->persist($userInEvent);
                }

                $this->persist($event);
                $this->flush();

                $this->get('rpe.notifications')->wait(Notification::TYPE_EVENT_INVITATION, $user, $event);
                $result = true;
            }
        }

        return array(
            'form'     => $eventForm,
            'result'   => $result,
            'event'    => $event,
            'formIsSubmitted' => !empty($formIsSubmitted),
        );
    }

    /**
     * XHR Method to display event participants.
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax/participants-event", name="ajax_participants_event", defaults={"_project"="rpe"})
     */
    public function ajaxParticipantsEventAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (!$request->isXmlHttpRequest()) {
            return new Response('ERROR');
        }

        $results = $this->getRepository('user')->getAcceptedFriends($this->getUser());

        /**
         * $result
         * Insert description here
         *
         *
         * @return
         *
         * @access
         * @static
         */
        $res = array_map(function ($result) {
            return array(
                'id'    => $result->getId(),
                'value' => $this->get('pum.view')->renderPumObject($result, 'search_row')
            );
        }, $results);

        return new Response(json_encode($res));
    }

    /**
     * Display a group calendar.
     * Show the calendar of a group with activated module
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $group_id    Id of group
     *
     * @return JsonResponse         A Response instance
     *
     * @Route(path="/calendar/{group_id}", name="ajax_calendar", defaults={"_project"="rpe"})
     */
    public function calendarAction(Request $request, $group_id = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        if (null !== $group_id) {
            $group = $this->getRepository('group')->find($group_id);
            if (!$group) {
                return new Response('Error');
            }
            $events = $this->getRepository('event')->findByOwnerGroup($group);
        } else {
            $events = $this->getRepository('event')->findAll();
        }

        $storage = $this->get('type_extra.media.storage.driver');
        $aryEvent = array();
        foreach ($events as $event) {
            // we need event dates
            if (!$event->getStartDate() || !$event->getEndDate()) {
                continue;
            }

            $item = array();
            if ($user !== $event->getOwnerUser()) {
                if ($group = $event->getOwnerGroup()) {
                    if (!$user->isInGroup($group)) {
                        continue;
                    }
                } else {
                    continue;
                }
            } else {
                $item['status'] = "editor";
            }

            $userInEvent = $this->getRepository('user_in_event')->findByEventAndUser($event, $user);
            $status      = $userInEvent ? $userInEvent->getStatus() : 'notin';

            switch ($status){
                case UserInEvent::STATUS_ACCEPT:
                    $item['status'] = "validated";
                    break;
                case UserInEvent::STATUS_INVITED:
                    $item['status'] = "waiting";
                    break;
                case UserInEvent::STATUS_REJECT:
                    $item['status'] = "refused";
                    break;
            }

            if ($owner = $event->getOwnerUser()) {
                if ($avatar = $owner->getAvatar()) {
                    $item['img_user'] = $this->get('request')->getSchemeAndHttpHost() . $storage->getWebPath($avatar, 20, 20);
                }
            }

            // get event dates in the user timezone
            $startDateTz = $this->toUserTimezone($event, 'getStartDate');
            $endDateTz   = $this->toUserTimezone($event, 'getEndDate');

            $item['title']   = $event->getTitle();
            $item['state']   = $event->getPrivacy();
            $item['start']   = $startDateTz->format(\DateTime::ISO8601);
            $item['ajx_url'] = $this->generateUrl('ajax_eventdetails', array('id' => $event->getId()));

            if ($event->getEndDate()) {
                $item['end'] = $endDateTz->format(\DateTime::ISO8601);
                $days = intval(date_diff($event->getStartDate(), $event->getEndDate())->format("%a"));
            }

            // creating events for displaying on each day
            $dayStartDate = new \DateTime($startDateTz->format('Y-m-d'));
            $dayEndDate   = new \DateTime($startDateTz->format('Y-m-d'));

            if (isset($days) && $days > 0) {
                for ($i=0; $i<=$days; $i++) {
                    $temp = $item;
                    if ($i != 0) {
                        $temp['start'] = $dayStartDate->modify('+1 days')->format(\DateTime::ISO8601);
                    }
                    if ($i != $days) {
                        $temp['end'] = $dayEndDate->modify('+1 days')->format(\DateTime::ISO8601);
                    }
                    $temp['start_base'] = $startDateTz->format(\DateTime::ISO8601);
                    $temp['end_base'] = $endDateTz->format(\DateTime::ISO8601);
                    $aryEvent[] = $temp;
                }
            } else {
                $aryEvent[] = $item;
            }
        }

        $json = array(
            'events'    => $aryEvent,
            'translate' => array(
                'monthnames'    => array('janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'),
                'daynames'      => array('dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'),
                'buttontext'    => array(
                    'today' =>  $this->get('translator')->trans('calendar.today', array(), 'rpe'),
                    'month' =>  $this->get('translator')->trans('calendar.month', array(), 'rpe'),
                    'week'  =>  $this->get('translator')->trans('calendar.week', array(), 'rpe'),
                    'day'   =>  $this->get('translator')->trans('calendar.day', array(), 'rpe')
                ),
                'more'          => $this->get('translator')->trans('calendar.more', array(), 'rpe'),
                'from'          => $this->get('translator')->trans('calendar.from', array(), 'rpe'),
                'to'            => $this->get('translator')->trans('calendar.to', array(), 'rpe')
            )
        );

        return new JsonResponse($json);
    }
}
