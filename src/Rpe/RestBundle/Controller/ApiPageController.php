<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Social\UserInEditor;
use Rpe\PumBundle\Model\Social\Post as SocialPost;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;

use Pum\Bundle\TypeExtraBundle\Model\Media as MediaType;

class ApiPageController extends ApiOAuthController
{
    /**
     * @Post(path="/pages", name="apiv1_pages", defaults={"_project"="rpe"})
     */
    public function postPagesAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $fields = array('name' => array('required' => true), 'description' => array('required' => true), 'email' => array('required' => true), 'website' => array('required' => true), 'owner_id' => array('required' => true));
        
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        if (null === ($user = $this->getRepository('user')->find($fields['owner_id']['value']))) {
            return $this->getDoesntExistsFieldError('user');
        }
        
        $editor = $this->createObject('editor');
        $editor->setName($fields['name']['value']);
        $editor->setDescription($fields['description']['value']);
        $editor->setEmail($fields['email']['value']);
        $editor->setWebsite($fields['website']['value']);
        $editor->setCreateDate(new \DateTime());

        $ownerInEditor = $this->createObject('user_in_editor');
        $ownerInEditor->setEditor($editor);
        $ownerInEditor->setUser($user);
        $ownerInEditor->setStatus(UserInEditor::STATUS_OWNER);
        $ownerInEditor->setDate(new \DateTime());
        
        $this->persist($editor, $ownerInEditor);
        $this->flush();
        
        return $this->getInsertSuccess('editor', $editor->getId());
    }

    /**
     * @Put(path="/pages/{page_id}", name="apiv1_pages", defaults={"_project"="rpe"})
     */
    public function putPagesAction(Request $request, $page_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if ($oAuthUser !== $page->getOwner() && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }

        $editor = $this->getRepository('editor')->find($page_id);

        if (null === $editor) {
            return $this->getNotFoundError();
        }
        
        $fields = array('name' => array(), 'description' => array(), 'email' => array(), 'website' => array(), 'owner_id' => array());
        
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        if (null !== $fields['owner_id']['value']) {
            if (null === ($user = $this->getRepository('user')->find($fields['owner_id']['value']))) {
                return $this->getDoesntExistsFieldError('user');
            }
        }
        
        if (null !== $fields['name']['value']) {
            $editor->setName($fields['name']['value']);
        }

        if (null !== $fields['description']['value']) {
            $editor->setDescription($fields['description']['value']);
        }

        if (null !== $fields['email']['value']) {
            $editor->setEmail($fields['email']['value']);
        }

        if (null !== $fields['website']['value']) {
            $editor->setWebsite($fields['website']['value']);
        }

        $editor->setUpdateDate(new \DateTime());

        $this->persist($editor);
        $this->flush();
        
        return $this->getUpdateSuccess('editor', $editor->getId());
    }

    /**
     * @Get(path="/pages", name="apiv1_pages", defaults={"_project"="rpe"})
     */
    public function getPagesAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'editor', array('title' => 'Viaeduc - Pages', 'description' => 'List of Viaeduc pages', 'pathname' => 'page'));
    }
    
    /**
     * @Get(path="/pages/{page_id}", name="apiv1_page", defaults={"_project"="rpe"})
     */
    public function getPageAction(Request $request, $page_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $page = $this->getRepository('editor')->find($page_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInEditor($page)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectAction($request, 'editor', $page_id);
    }
    
    /**
     * @Get(path="/pages/{page_id}/friends", name="apiv1_page_friends", defaults={"_project"="rpe"})
     */
    public function getPageFriendsAction(Request $request, $page_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $page = $this->getRepository('editor')->find($page_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInEditor($page)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'user', 'page_friends', $page_id);
    }
    
    /**
     * @Get(path="/pages/{page_id}/publications", name="apiv1_page_publications", defaults={"_project"="rpe"})
     */
    public function getPagePublicationsAction(Request $request, $page_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $page = $this->getRepository('editor')->find($page_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInEditor($page)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'post', 'page_publications', $page_id, array('title' => 'Viaeduc - Page\'s publications', 'description' => 'List of '.$page->getName().' publications', 'pathname' => 'post'));
    }

    /**
     * @Post(path="/pages/{page_id}/publications", name="apiv1_group_publications", defaults={"_project"="rpe"})
     */
    public function postPagePublicationsAction(Request $request, $page_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $page = $this->getRepository('page')->find($page_id);
            
            if (null === $page) {
                return $this->getNotFoundError();
            }
            
            if ($oAuthUser !== $page->getOwner() && !$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInPage($page)) {
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
        $publication->setType(SocialPost::TYPE_EDITOR);
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

        $publication->setPublishedEditor($page);
        
        $this->persist($publication);
        $this->flush();
        
        return $this->getInsertSuccess('publication', $publication->getId());
    }
    
    /**
     * @Get(path="/pages/{page_id}/questions", name="apiv1_page_questions", defaults={"_project"="rpe"})
     */
    public function getPageQuestionsAction(Request $request, $page_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $page = $this->getRepository('editor')->find($page_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInEditor($page)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'question', 'page_questions', $page_id, array('title' => 'Viaeduc - Page\'s questions', 'description' => 'List of '.$page->getName().' questions', 'pathname' => 'question'));
    }

    /**
     * @Post(path="/pages/{page_id}/questions", name="apiv1_pages_questions", defaults={"_project"="rpe"})
     */
    public function postPageQuestionsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $page = $this->getRepository('page')->find($page_id);
            
            if ($oAuthUser !== $page->getOwner() && !$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInEditor($page)) {
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
        $question->setPublishedPage($page);
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
     * @Get(path="/pages/{page_id}/activities", name="apiv1_page_activities", defaults={"_project"="rpe"})
     */
    public function getPageActivitiesAction(Request $request, $page_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $page = $this->getRepository('editor')->find($page_id);
            
            if (!$oAuthUser->isAdmin()) {
                if (!$oAuthUser->isInEditor($page)) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectLinkedObjectsAction($request, 'question', 'page_activities', $page_id, array('title' => 'Viaeduc - Page\'s activities', 'description' => 'List of '.$page->getName().' activities', 'pathname' => 'post'));
    }
}
