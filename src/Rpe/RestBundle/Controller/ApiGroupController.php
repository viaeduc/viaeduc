<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\UserInGroup;
use Rpe\PumBundle\Model\Social\Post as SocialPost;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;

use Pum\Bundle\TypeExtraBundle\Model\Media as MediaType;

class ApiGroupController extends ApiOAuthController
{
    /**
     * @Post(path="/groups", name="apiv1_groups", defaults={"_project"="rpe"})
     */
    public function postGroupsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        $fields = array('name' => array('required' => true), 'description' => array('required' => true), 'disciplines' => array('required' => true), 'teachingLevels' => array('required' => true), 'interests' => array('required' => true), 'accessType' => array('required' => true), 'subgroupLevel' => array('required' => true), 'owner_id' => array('required' => true));
        
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        if (!in_array($fields['accessType']['value'], array('public', 'on_demand', 'on_invitation'))) {
            return $this->getBadValueFieldError('accessType', array('public', 'on_demand', 'on_invitation'));
        }
        
        if (!in_array($fields['subgroupLevel']['value'], array('admin', 'moderator', 'everybody'))) {
            return $this->getBadValueFieldError('subgroupLevel', array('admin', 'moderator', 'everybody'));
        }
        
        if (null === ($user = $this->getRepository('user')->find($fields['owner_id']['value']))) {
            return $this->getDoesntExistsFieldError('user');
        }
        
        $rawDisciplines = $fields['disciplines']['value'];
        $disciplinesId = explode(',', $rawDisciplines);
        
        foreach ($disciplinesId as $disciplineId) {
            if (null === ($discipline = $this->getRepository('instructed_discipline')->find($disciplineId))) {
                return $this->getDoesntExistsFieldError('disciplines', $disciplineId);
            }
        }

        $rawTeachingLevels = $fields['teachingLevels']['value'];
        $teachingLevelsId = explode(',', $rawTeachingLevels);

        foreach ($teachingLevelsId as $teachingLevelId) {
            if (null === ($teachingLevel = $this->getRepository('teaching_level')->find($teachingLevelId))) {
                return $this->getDoesntExistsFieldError('teachingLevels', $teachingLevelId);
            }
        }

        $rawInterests = $fields['interests']['value'];
        $interestsId = explode(',', $rawInterests);

        foreach ($interestsId as $interestId) {
            if (null === ($interest = $this->getRepository('interest')->find($interestId))) {
                return $this->getDoesntExistsFieldError('interests', $interestId);
            }
        }

        $accessType = '';

        if ($fields['accessType']['value'] == 'public') {
            $accessType = Group::ACCESS_PUBLIC;
        } elseif ($fields['accessType']['value'] == 'on_demand') {
            $accessType = Group::ACCESS_ON_DEMAND;
        } elseif ($fields['accessType']['value'] == 'on_invitation') {
            $accessType = Group::ACCESS_ON_INVITATION;
        }

        $subgroupLevel = '';

        if ($fields['subgroupLevel']['value'] == 'admin') {
            $subgroupLevel = 2;
        } elseif ($fields['subgroupLevel']['value'] == 'moderator') {
            $subgroupLevel = 3;
        } elseif ($fields['subgroupLevel']['value'] == 'everybody') {
            $subgroupLevel = 4;
        }

        $group = $this->createObject('group');
        $group->setName($fields['name']['value']);
        $group->setDescription($fields['description']['value']);
        $group->setAccessType($accessType);
        $group->setSubgroupLevel($subgroupLevel);
        $group->setCreateDate(new \DateTime());
        
        foreach ($disciplinesId as $disciplineId) {
            $discipline = $this->getRepository('instructed_discipline')->find($disciplineId);

            $group->addInstructedDiscipline($discipline);
        }
        
        foreach ($teachingLevelsId as $teachingLevelId) {
            $teachingLevel = $this->getRepository('teaching_level')->find($teachingLevelId);

            $group->addTeachingLevel($teachingLevel);
        }
        
        foreach ($interestsId as $interestId) {
            $interest = $this->getRepository('interest')->find($interestId);

            $group->addInterest($interest);
        }

        $ownerInGroup = $this->createObject('user_in_group');
        $ownerInGroup->setGroup($group);
        $ownerInGroup->setUser($user);
        $ownerInGroup->setStatus(UserInGroup::STATUS_OWNER);
        $ownerInGroup->setDate(new \DateTime());

        $this->persist($group, $ownerInGroup);
        $this->flush();
        
        return $this->getInsertSuccess('group', $group->getId());
    }

    /**
     * @Put(path="/groups/{group_id}", name="apiv1_groups", defaults={"_project"="rpe"})
     */
    public function putGroupsAction(Request $request, $group_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);

            if (null === $group) {
                return $this->getNotFoundError();
            }

            if ($oAuthUser !== $group->getOwner() && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $fields = array('name' => array(), 'description' => array(), 'disciplines' => array(), 'teachingLevels' => array(), 'interests' => array(), 'accessType' => array(), 'subgroupLevel' => array(), 'owner_id' => array());
        
        $fields = $this->initPostFields($request, $fields);
        
        if (null !== $fields['accessType']['value']) {
            if (!in_array($fields['accessType']['value'], array('public', 'on_demand', 'on_invitation'))) {
                return $this->getBadValueFieldError('accessType', array('public', 'on_demand', 'on_invitation'));
            }
        }
        
        if (null !== $fields['subgroupLevel']['value']) {
            if (!in_array($fields['subgroupLevel']['value'], array('admin', 'moderator', 'everybody'))) {
                return $this->getBadValueFieldError('subgroupLevel', array('admin', 'moderator', 'everybody'));
            }
        }
        
        if (null !== $fields['owner_id']['value']) {
            if (null === ($user = $this->getRepository('user')->find($fields['owner_id']['value']))) {
                return $this->getDoesntExistsFieldError('user');
            }
        }
        
        if (null !== $fields['disciplines']['value']) {
            $rawDisciplines = $fields['disciplines']['value'];
            $disciplinesId = explode(',', $rawDisciplines);
            
            foreach ($disciplinesId as $disciplineId) {
                if (null === ($discipline = $this->getRepository('instructed_discipline')->find($disciplineId))) {
                    return $this->getDoesntExistsFieldError('disciplines', $disciplineId);
                }
            }
        }

        if (null !== $fields['teachingLevels']['value']) {
            $rawTeachingLevels = $fields['teachingLevels']['value'];
            $teachingLevelsId = explode(',', $rawTeachingLevels);

            foreach ($teachingLevelsId as $teachingLevelId) {
                if (null === ($teachingLevel = $this->getRepository('teaching_level')->find($teachingLevelId))) {
                    return $this->getDoesntExistsFieldError('teachingLevels', $teachingLevelId);
                }
            }
        }

        if (null !== $fields['interests']['value']) {
            $rawInterests = $fields['interests']['value'];
            $interestsId = explode(',', $rawInterests);

            foreach ($interestsId as $interestId) {
                if (null === ($interest = $this->getRepository('interest')->find($interestId))) {
                    return $this->getDoesntExistsFieldError('interests', $interestId);
                }
            }
        }

        if (null !== $fields['name']['value']) {
            $group->setName($fields['name']['value']);
        }

        if (null !== $fields['description']['value']) {
            $group->setDescription($fields['description']['value']);
        }

        if (null !== $fields['accessType']['value']) {
            $accessType = '';

            if ($fields['accessType']['value'] == 'public') {
                $accessType = Group::ACCESS_PUBLIC;
            } elseif ($fields['accessType']['value'] == 'on_demand') {
                $accessType = Group::ACCESS_ON_DEMAND;
            } elseif ($fields['accessType']['value'] == 'on_invitation') {
                $accessType = Group::ACCESS_ON_INVITATION;
            }

            $group->setAccessType($accessType);
        }

        if (null !== $fields['subgroupLevel']['value']) {
            $subgroupLevel = '';

            if ($fields['subgroupLevel']['value'] == 'admin') {
                $subgroupLevel = 2;
            } elseif ($fields['subgroupLevel']['value'] == 'moderator') {
                $subgroupLevel = 3;
            } elseif ($fields['subgroupLevel']['value'] == 'everybody') {
                $subgroupLevel = 4;
            }

            $group->setSubgroupLevel($subgroupLevel);
        }
        
        if (null !== $fields['disciplines']['value']) {
            foreach ($disciplinesId as $disciplineId) {
                $discipline = $this->getRepository('instructed_discipline')->find($disciplineId);

                $group->addInstructedDiscipline($discipline);
            }
        }
        
        if (null !== $fields['teachingLevels']['value']) {
            foreach ($teachingLevelsId as $teachingLevelId) {
                $teachingLevel = $this->getRepository('teaching_level')->find($teachingLevelId);

                $group->addTeachingLevel($teachingLevel);
            }
        }
        
        if (null !== $fields['interests']['value']) {
            foreach ($interestsId as $interestId) {
                $interest = $this->getRepository('interest')->find($interestId);

                $group->addInterest($interest);
            }
        }

        $this->persist($group);
        $this->flush();
        
        return $this->getUpdateSuccess('group', $group->getId());
    }

    /**
     * @Delete(path="/groups/{group_id}", name="apiv1_groups", defaults={"_project"="rpe"})
     */
    public function deleteGroupsAction(Request $request, $group_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);
            
            if (null === $group) {
                return $this->getNotFoundError();
            }
            
            if ($group->getOwner() !== $oAuthUser && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $this->remove($group);
        $this->flush();
        
        return $this->getDeleteSuccess('group', $group_id);
    }

    /**
     * @Get(path="/groups", name="apiv1_groups", defaults={"_project"="rpe"})
     */
    public function getGroupsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'group', array('title' => 'Viaeduc - Group List', 'description' => 'List of Viaeduc groups', 'pathname' => 'group'));
    }
    
    /**
     * @Get(path="/groups/{group_id}", name="apiv1_group", defaults={"_project"="rpe"})
     */
    public function getGroupAction(Request $request, $group_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$group->isPublic() && !$oAuthUser->isInGroup($group)) {
                    return $this->getAccessRightError();
                }
            }
        }

        return $this->getObjectAction($request, 'group', $group_id);
    }
    
    /**
     * @Get(path="/groups/{group_id}/events", name="apiv1_group_events", defaults={"_project"="rpe"})
     */
    public function getGroupEventsAction(Request $request, $group_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInGroup($group)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'event', 'group_events', $group_id);
    }

    /**
     * @Post(path="/groups/{groups_id}/events", name="apiv1_group_events", defaults={"_project"="rpe"})
     */
    public function postGroupEventsAction(Request $request, $group_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInGroup($group)) {
                    return $this->getAccessRightError();
                }
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

        $event->setOwnerGroup($group);
        
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
     * @Get(path="/groups/{group_id}/publications", name="apiv1_group_publications", defaults={"_project"="rpe"})
     */
    public function getGroupPublicationsAction(Request $request, $group_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInGroup($group)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'post', 'group_publications', $group_id, array('type' => 'activities', 'title' => 'Viaeduc - Group\'s publications', 'description' => 'List of '.$group->getName().' publications', 'pathname' => 'post'));
    }

    /**
     * @Post(path="/groups/{group_id}/publications", name="apiv1_group_publications", defaults={"_project"="rpe"})
     */
    public function postGroupPublicationsAction(Request $request, $group_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);

            if (null === $group) {
                return $this->getNotFoundError();
            }
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInGroup($group)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        $fields = array('name' => array('required' => true), 'content' => array('required' => true), 'illustration' => array('required' => true), 'description' => array('required' => true), 'disciplines' => array('required' => true), 'teachingLevels' => array('required' => true), 'coAuthors' => array());
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        $rawDisciplines = $fields['disciplines']['value'];
        $disciplinesId = explode(',', $rawDisciplines);
        
        foreach ($disciplinesId as $disciplineId) {
            if (null === ($discipline = $this->getRepository('instructed_discipline')->find($disciplineId))) {
                return $this->getDoesntExistsFieldError('disciplines', $disciplineId);
            }
        }

        $rawTeachingLevels = $fields['teachingLevels']['value'];
        $teachingLevelsId = explode(',', $rawTeachingLevels);

        foreach ($teachingLevelsId as $teachingLevelId) {
            if (null === ($teachingLevel = $this->getRepository('teaching_level')->find($teachingLevelId))) {
                return $this->getDoesntExistsFieldError('teachingLevels', $teachingLevelId);
            }
        }

        if (null !== $fields['coAuthors']['value']) {
            $rawCoAuthors = $fields['coAuthors']['value'];
            $coAuthorsId = explode(',', $rawCoAuthors);

            foreach ($coAuthorsId as $coAuthorId) {
                if (null === ($coAuthor = $this->getRepository('teaching_level')->find($coAuthorId))) {
                    return $this->getDoesntExistsFieldError('coAuthors', $coAuthorId);
                }
            }
        }

        $publication = $this->createObject('post');
        $publication->setCreateDate(new \DateTime());
        $publication->setResource(true);
        $publication->setStatus(SocialPost::STATUS_PUBLISHED);
        $publication->setType(SocialPost::TYPE_GROUP);
        $publication->setAuthor($oAuthUser);
        $publication->setName($fields['name']['value']);
        $publication->setContent($fields['content']['value']);

        $file = new MediaType();
        $file->setFile($fields['illustration']['value']);

        $publication->setFile($file);

        $publication->setDescription($fields['description']['value']);
        
        foreach ($disciplinesId as $disciplineId) {
            $discipline = $this->getRepository('instructed_discipline')->find($disciplineId);

            $publication->addDiscipline($discipline);
        }
        
        foreach ($teachingLevelsId as $teachingLevelId) {
            $teachingLevel = $this->getRepository('teaching_level')->find($teachingLevelId);

            $publication->addTeachingLevel($teachingLevel);
        }
        
        if (null !== $fields['coAuthors']['value']) {
            foreach ($coAuthorsId as $coAuthorId) {
                $coAuthor = $this->getRepository('user')->find($coAuthorId);

                $publication->addCoAuthor($coAuthor);
            }
        }

        $publication->setPublishedGroup($group);
        
        $this->persist($publication);
        $this->flush();
        
        return $this->getInsertSuccess('publication', $publication->getId());
    }
    
    /**
     * @Get(path="/groups/{group_id}/questions", name="apiv1_group_questions", defaults={"_project"="rpe"})
     */
    public function getGroupQuestionsAction(Request $request, $group_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInGroup($group)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'question', 'group_questions', $group_id, array('title' => 'Viaeduc - Group\'s Questions', 'description' => 'List of '.$group->getName().' questions', 'pathname' => 'question'));
    }

    /**
     * @Post(path="/groups/{page_id}/questions", name="apiv1_groups_questions", defaults={"_project"="rpe"})
     */
    public function postGroupsQuestionsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInGroup($group)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        $fields = array('name' => array('required' => true), 'description' => array('required' => true), 'disciplines' => array('required' => true), 'keywords' => array('required' => true), 'accessType' => array('required' => true), 'owner_id' => array('required' => true));
        
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        if (!in_array($fields['accessType']['value'], array('public', 'my_friends', 'my_groups'))) {
            return $this->getBadValueFieldError('accessType', array('public', 'my_friends', 'my_groups'));
        }
        
        if (null === ($user = $this->getRepository('user')->find($fields['owner_id']['value']))) {
            return $this->getDoesntExistsFieldError('user');
        }
        
        $rawDisciplines = $fields['disciplines']['value'];
        $disciplinesId = explode(',', $rawDisciplines);
        
        foreach ($disciplinesId as $disciplineId) {
            if (null === ($discipline = $this->getRepository('instructed_discipline')->find($disciplineId))) {
                return $this->getDoesntExistsFieldError('disciplines', $disciplineId);
            }
        }
        
        $accessType = '';
        
        if ($fields['accessType']['value'] == 'public') {
            $accessType = Question::ACCESS_PUBLIC;
        } elseif ($fields['accessType']['value'] == 'my_friends') {
            $accessType = Question::ACCESS_FRIENDS;
        } elseif ($fields['accessType']['value'] == 'my_groups') {
            $accessType = Question::ACCESS_GROUP;
        }
        
        $question = $this->createObject('question');
        $question->setAuthor($user);
        $question->setName($fields['name']['value']);
        $question->setDescription($fields['description']['value']);
        $question->setKeywords($fields['keywords']['value']);
        $question->setAccessType($accessType);
        $question->setPublishedGroup($group);
        $question->setDate(new \DateTime());
        
        foreach ($disciplinesId as $disciplineId) {
            $discipline = $this->getRepository('instructed_discipline')->find($disciplineId);

            $question->addInstructedDiscipline($discipline);
        }

        $this->persist($question);
        $this->flush();
        
        return $this->getInsertSuccess('question', $question->getId());
    }
    
    /**
     * @Get(path="/groups/{group_id}/activities", name="apiv1_group_activities", defaults={"_project"="rpe"})
     */
    public function getGroupActivitiesAction(Request $request, $group_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $group = $this->getRepository('group')->find($group_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInGroup($group)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'post', 'group_activities', $group_id, array('type' => 'activities', 'title' => 'Viaeduc - Group\'s activities', 'description' => 'List of '.$group->getName().' activities', 'pathname' => 'post'));
    }
}
