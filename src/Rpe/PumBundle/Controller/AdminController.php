<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Group;

/**
 * Administration Controller.
 * Controller methods for the front administration part of Viaeduc (statistics).
 *
 * @method Response statsAction(Request $request)
 *
 */
class AdminController extends Controller
{
    /**
     * Action for statistics
     *
     * @access public
     * @param  Request $request   A request instance
     * @return Response A Response instance
     *
     * @Route(path="/admin/stats", name="admin_stats", defaults={"_project"="rpe"})
     */
    public function statsAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $me = $this->getUser();

        if (!in_array($me->getType(), array(User::TYPE_PRIVILEGE, User::TYPE_ADMIN))) {
            return $this->redirect($this->generateUrl('home'));
        }

        $dateStart = $request->get('date_start');
        $dateEnd = $request->get('date_end');

        $dateTimeStart = null;

        if (!empty($dateStart)) {
            list($dateStartDay, $dateStartMonth, $dateStartYear) = explode("/", $dateStart);

            $dateTimeStart = new \DateTime($dateStartYear.'-'.$dateStartMonth.'-'.$dateStartDay, new \DateTimeZone($this->getUserTimezone()));
            $dateTimeStart->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        $dateTimeEnd = null;

        if (!empty($dateEnd)) {
            list($dateEndDay, $dateEndMonth, $dateEndYear) = explode("/", $dateEnd);

            $dateTimeEnd = new \DateTime($dateEndYear.'-'.$dateEndMonth.'-'.$dateEndDay, new \DateTimeZone($this->getUserTimezone()));
            $dateTimeEnd->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        $academy = $request->get('users-academy');
        $groupType = $request->get('groups-type');

        $userValidatedCount = $this->getRepository('user')->getUsersCount(User::STATUS_TYPE_ACTIVE, null, $academy, $dateTimeStart, $dateTimeEnd);
        $userAwaitingCount = $this->getRepository('user')->getUsersCount(User::STATUS_TYPE_AWAITING_CONFIRMATION, null, $academy, $dateTimeStart, $dateTimeEnd);

        $userInvitedCount = $this->getRepository('user')->getUsersCount(User::STATUS_TYPE_AWAITING_CONFIRMATION, User::TYPE_INVITED, $academy, $dateTimeStart, $dateTimeEnd);
        $userInvitedValidatedCount = $this->getRepository('user')->getUsersCount(User::STATUS_TYPE_ACTIVE, User::TYPE_INVITED, $academy, $dateTimeStart, $dateTimeEnd);

        $groupCount = $this->getRepository('group')->getGroupsCount($groupType, $dateTimeStart, $dateTimeEnd);

        $resourceCount = $this->getRepository('post')->getPostsCount(true, $dateTimeStart, $dateTimeEnd);
        $postCount = $this->getRepository('post')->getPostsCount(false, $dateTimeStart, $dateTimeEnd);
        $commentCount = $this->getRepository('comment')->getCommentsCount($dateTimeStart, $dateTimeEnd);
        $recommendCount = $this->getRepository('recommend_post')->getRecommendsCount($dateTimeStart, $dateTimeEnd);
        $shareCount = $this->getRepository('share_post')->getSharePostsCount($dateTimeStart, $dateTimeEnd);
        $questionCount = $this->getRepository('question')->getQuestionsCount($dateTimeStart, $dateTimeEnd);
        $answerCount = $this->getRepository('answer')->getAnswersCount($dateTimeStart, $dateTimeEnd);
        $eventCount = $this->getRepository('event')->getEventsCount($dateTimeStart, $dateTimeEnd);
        $messageCount = $this->getRepository('message')->getMessagesCount($dateTimeStart, $dateTimeEnd);
        $mediaCount = $this->getRepository('media')->getMediasCount($dateTimeStart, $dateTimeEnd);

        $academies = $this->getRepository('academy')->findAll();

        $groupTypes = array(
            'public' => Group::ACCESS_PUBLIC,
            'private' => Group::ACCESS_ON_DEMAND,
            'hidden' => Group::ACCESS_ON_INVITATION,
        );

        return $this->render('pum://page/admin/stats.html.twig', array(
            'userValidatedCount' => $userValidatedCount,
            'userAwaitingCount' => $userAwaitingCount,
            'userInvitedCount' => $userInvitedCount,
            'userInvitedValidatedCount' => $userInvitedValidatedCount,
            'groupCount' => $groupCount,
            'resourceCount' => $resourceCount,
            'postCount' => $postCount,
            'commentCount' => $commentCount,
            'recommendCount' => $recommendCount,
            'shareCount' => $shareCount,
            'questionCount' => $questionCount,
            'answerCount' => $answerCount,
            'eventCount' => $eventCount,
            'messageCount' => $messageCount,
            'mediaCount' => $mediaCount,
            'academies' => $academies,
            'groupTypes' => $groupTypes,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
        ));
    }
}
