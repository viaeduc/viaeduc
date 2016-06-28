<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Social\User;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;

class ApiUserController extends ApiOAuthController
{
    /**
     * @Post(path="/users", name="apiv1_users", defaults={"_project"="rpe"})
     */
    public function postUsersAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        $fields = array('title' => array('required' => true), 'lastname' => array('required' => true), 'firstname' => array('required' => true), 'emailPro' => array('required' => true), 'password' => array('required' => true), 'occupation' => array('required' => true), 'academy' => array('required' => true));
        
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        if (!in_array($fields['title']['value'], array('m', 'f'))) {
            return $this->getBadValueFieldError('title', array('m', 'f'));
        }
        
        if (!preg_match('#^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,6}$#', $fields['emailPro']['value'])) {
            return $this->getMalFormattedFieldError('emailPro');
        }
        
        if (null !== $this->getRepository('user')->findOneByEmailPro($fields['emailPro']['value'])) {
            return $this->getAlreadyExistsFieldError('emailPro');
        }
        
        $emailDomainMatch = false;

        $domains = $this->getRepository('email_domain')->findAll();

        // If email_domain is empty we accept all email domain
        if (empty($domains)) {
            $emailDomainMatch = true;
        }

        foreach ($domains as $domain) {
            if (false !== strpos($fields['emailPro']['value'], $domain->getDomain())) {
                $emailDomainMatch = true;
            }
        }

        if (!$emailDomainMatch) {
            return $this->getBadValueFieldError('emailPro');
        }
        
        if (null === ($occupation = $this->getRepository('occupation')->find($fields['occupation']['value']))) {
            return $this->getDoesntExistsFieldError('occupation');
        }
        
        if (null === ($academy = $this->getRepository('academy')->find($fields['academy']['value']))) {
            return $this->getDoesntExistsFieldError('academy');
        }
        
        $user = $this->createObject('user');
        $user->setSex(($fields['title']['value'] == 'm' ? 'Monsieur' : 'Madame'));
        $user->setLastname($fields['lastname']['value']);
        $user->setFirstname($fields['firstname']['value']);
        $user->setEmailPro($fields['emailPro']['value']);
        $user->setPassword($fields['password']['value'], $this->get('security.encoder_factory'));
        $user->setOccupation($occupation);
        $user->setAcademy($academy);
        $user->setStatus(User::STATUS_TYPE_ACTIVE);
        $user->setDate(new \DateTime());
        $this->persist($user);
        $this->flush();
        
        return $this->getInsertSuccess('user', $user->getId());
    }
    
    /**
     * @Put(path="/users/{user_id}", name="apiv1_users", defaults={"_project"="rpe"})
     */
    public function putUsersAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $user = $this->getRepository('user')->find($user_id);
            
            if (null === $user) {
                return $this->getNotFoundError();
            }
            
            if ($oAuthUser !== $user && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $fields = array('title' => array(), 'lastname' => array(), 'firstname' => array(), 'emailPro' => array(), 'password' => array(), 'occupation' => array(), 'academy' => array());
        
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        if (null !== $fields['title']['value']) {
            if (!in_array($fields['title']['value'], array('m', 'f'))) {
                return $this->getBadValueFieldError('title', array('m', 'f'));
            }
        }
        
        if (null !== $fields['emailPro']['value']) {
            if (!preg_match('#^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,6}$#', $fields['emailPro']['value'])) {
                return $this->getMalFormattedFieldError('emailPro');
            }
            
            if (null !== $this->getRepository('user')->findOneByEmailPro($fields['emailPro']['value'])) {
                return $this->getAlreadyExistsFieldError('emailPro');
            }

            $emailDomainMatch = false;

            $domains = $this->getRepository('email_domain')->findAll();

            // If email_domain is empty we accept all email domain
            if (empty($domains)) {
                $emailDomainMatch = true;
            }

            foreach ($domains as $domain) {
                if (false !== strpos($fields['emailPro']['value'], $domain->getDomain())) {
                    $emailDomainMatch = true;
                }
            }

            if (!$emailDomainMatch) {
                return $this->getBadValueFieldError('emailPro');
            }
        }
        
        if (null !== $fields['occupation']['value']) {
            if (null === ($occupation = $this->getRepository('occupation')->find($fields['occupation']['value']))) {
                return $this->getDoesntExistsFieldError('occupation');
            }
        }
        
        if (null !== $fields['academy']['value']) {
            if (null === ($academy = $this->getRepository('academy')->find($fields['academy']['value']))) {
                return $this->getDoesntExistsFieldError('academy');
            }
        }
        
        if (null !== $fields['title']['value']) {
            $user->setSex(($fields['title']['value'] == 'm' ? 'Monsieur' : 'Madame'));
        }

        if (null !== $fields['lastname']['value']) {
            $user->setLastname($fields['lastname']['value']);
        }

        if (null !== $fields['firstname']['value']) {
            $user->setFirstname($fields['firstname']['value']);
        }

        if (null !== $fields['emailPro']['value']) {
            $user->setEmailPro($fields['emailPro']['value']);
        }

        if (null !== $fields['password']['value']) {
            $user->setPassword($fields['password']['value'], $this->get('security.encoder_factory'));
        }

        if (null !== $fields['occupation']['value']) {
            $user->setOccupation($occupation);
        }

        if (null !== $fields['academy']['value']) {
            $user->setAcademy($academy);
        }

        $this->persist($user);
        $this->flush();
        
        return $this->getUpdateSuccess('user', $user->getId());
    }
    
    /**
     * @Get(path="/users", name="apiv1_users", defaults={"_project"="rpe"})
     */
    public function getUsersAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'user');
    }
    
    /**
     * @Get(path="/users/{user_id}", name="apiv1_user", defaults={"_project"="rpe"})
     */
    public function getUserAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }

            $user = $this->getRepository('user')->find($user_id);

            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user && !$oAuthUser->isFriend($user)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectAction($request, 'user', $user_id);
    }
    
    /**
     * @Get(path="/users/{user_id}/groups", name="apiv1_user_groups", defaults={"_project"="rpe"})
     */
    public function getUserGroupsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user && !$oAuthUser->isFriend($user)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'group', 'user_groups', $user_id, array('title' => 'Viaeduc - User\'s group List', 'description' => 'List of '.$user->getFullname().' groups', 'pathname' => 'group'));
    }
    
    /**
     * @Get(path="/users/{user_id}/events", name="apiv1_user_events", defaults={"_project"="rpe"})
     */
    public function getUserEventsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user && !$oAuthUser->isFriend($user)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'event', 'user_events', $user_id);
    }
    
    /**
     * @Get(path="/users/{user_id}/friends", name="apiv1_user_friends", defaults={"_project"="rpe"})
     */
    public function getUserFriendsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user && !$oAuthUser->isFriend($user)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'user', 'friends', $user_id);
    }
    
    /**
     * @Get(path="/users/{user_id}/publications", name="apiv1_user_publications", defaults={"_project"="rpe"})
     */
    public function getUserPublicationsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user && !$oAuthUser->isFriend($user)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'post', 'user_publications', $user_id, array('type' => 'resources', 'title' => 'Viaeduc - User\'s publications', 'description' => 'List of '.$user->getFullname().' publications', 'pathname' => 'post'));
    }
    
    /**
     * @Get(path="/users/{user_id}/questions", name="apiv1_user_questions", defaults={"_project"="rpe"})
     */
    public function getUserQuestionsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user && !$oAuthUser->isFriend($user)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'question', 'user_questions', $user_id, array('title' => 'Viaeduc - User\'s questions', 'description' => 'List of '.$user->getFullname().' questions', 'pathname' => 'question'));
    }
    
    /**
     * @Get(path="/users/{user_id}/medias", name="apiv1_user_medias", defaults={"_project"="rpe"})
     */
    public function getUserMediasAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user && !$oAuthUser->isFriend($user)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'media', 'user_medias', $user_id);
    }
    
    /**
     * @Get(path="/users/{user_id}/notifications", name="apiv1_user_notifications", defaults={"_project"="rpe"})
     */
    public function getUserNotificationsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'notification', 'user_notifications', $user_id, array('title' => 'Viaeduc - User\'s notifications', 'description' => 'List of '.$user->getFullname().' notifications', 'pathname' => 'notification'));
    }
    
    /**
     * @Get(path="/users/{user_id}/activities", name="apiv1_user_activities", defaults={"_project"="rpe"})
     */
    public function getUserActivitiesAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user && !$oAuthUser->isFriend($user)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'post', 'user_activities', $user_id, array('type' => 'activities', 'title' => 'Viaeduc - User\'s activities', 'description' => 'List of '.$user->getFullname().' activities', 'pathname' => 'post'));
    }
    
    /**
     * @Get(path="/users/{user_id}/discussions", name="apiv1_user_discussions", defaults={"_project"="rpe"})
     */
    public function getUserDiscussionsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'discussion', 'user_discussions', $user_id);
    }
    
    /**
     * @Get(path="/users/{user_id}/messages", name="apiv1_user_messages", defaults={"_project"="rpe"})
     */
    public function getUserMessagesAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'message', 'user_messages', $user_id, array('title' => 'Viaeduc - User\'s messages', 'description' => 'List of '.$user->getFullname().' messages', 'pathname' => 'message'));
    }
    
    /**
     * @Get(path="/users/{user_id}/friends/groups", name="apiv1_user_friends_groups", defaults={"_project"="rpe"})
     */
    public function getUserFriendsGroupsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
        }

        // No access for anybody (even admin)
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            // if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            // }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'group', 'user_friends_groups', $user_id);
    }
    
    /**
     * @Get(path="/users/{user_id}/friends/events", name="apiv1_user_friends_events", defaults={"_project"="rpe"})
     */
    public function getUserFriendsEventsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
        }
        
        // No access for anybody (even admin)
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            // if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            // }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'event', 'user_friends_events', $user_id);
    }
    
    /**
     * @Get(path="/users/{user_id}/friends/publications", name="apiv1_user_friends_publications", defaults={"_project"="rpe"})
     */
    public function getUserFriendsPublicationsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }

            $user = $this->getRepository('user')->find($user_id);
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'post', 'user_friends_publications', $user_id, array('type' => 'resources', 'title' => 'Viaeduc - User\'s friends publications', 'description' => 'List of '.$user->getFullname().' friends publications', 'pathname' => 'post'));
    }
    
    /**
     * @Get(path="/users/{user_id}/friends/questions", name="apiv1_user_friends_questions", defaults={"_project"="rpe"})
     */
    public function getUserFriendsQuestionsAction(Request $request, $user_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($user_id == 'me') {
                $user_id = $oAuthUser->getId();
            }
            
            $user = $this->getRepository('user')->find($user_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $user && !$oAuthUser->isFriend($user)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'question', 'user_friends_questions', $user_id, array('title' => 'Viaeduc - User\'s friends questions', 'description' => 'List of '.$user->getFullname().' friends questions', 'pathname' => 'question'));
    }
}
