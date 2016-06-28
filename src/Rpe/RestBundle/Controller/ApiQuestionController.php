<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Social\Question;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;

class ApiQuestionController extends ApiOAuthController
{
    /**
     * @Post(path="/questions", name="apiv1_questions", defaults={"_project"="rpe"})
     */
    public function postQuestionsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
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
     * @Put(path="/questions/{question_id}", name="apiv1_questions", defaults={"_project"="rpe"})
     */
    public function putQuestionsAction(Request $request, $question_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $question = $this->getRepository('question')->find($question_id);
            
            if (null === $question) {
                return $this->getNotFoundError();
            }
            
            if ($oAuthUser !== $question->getAuthor() && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $fields = array('name' => array(), 'description' => array(), 'disciplines' => array(), 'keywords' => array(), 'publishedGroup_id' => array(), 'accessType' => array(), 'owner_id' => array());
        
        $fields = $this->initPostFields($request, $fields);
        
        if (null !== $fields['accessType']['value']) {
            if (!in_array($fields['accessType']['value'], array('public', 'my_friends', 'my_groups'))) {
                return $this->getBadValueFieldError('accessType', array('public', 'my_friends', 'my_groups'));
            }
        }
        
        if (null !== $fields['owner_id']['value']) {
            if (null === ($user = $this->getRepository('user')->find($fields['owner_id']['value']))) {
                return $this->getDoesntExistsFieldError('user');
            }
        }
        
        if (null !== $fields['publishedGroup_id']['value']) {
            if (null === ($group = $this->getRepository('group')->find($fields['publishedGroup_id']['value']))) {
                return $this->getDoesntExistsFieldError('group');
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

        $question->setAuthor($user);

        if (null !== $fields['name']['value']) {
            $question->setName($fields['name']['value']);
        }

        if (null !== $fields['description']['value']) {
            $question->setDescription($fields['description']['value']);
        }

        if (null !== $fields['keywords']['value']) {
            $question->setKeywords($fields['keywords']['value']);
        }

        if (null !== $fields['publishedGroup_id']['value']) {
            $question->setPublishedGroup($group);
        }

        if (null !== $fields['accessType']['value']) {
            $accessType = '';

            if ($fields['accessType']['value'] == 'public') {
                $accessType = Question::ACCESS_PUBLIC;
            } elseif ($fields['accessType']['value'] == 'my_friends') {
                $accessType = Question::ACCESS_FRIENDS;
            } elseif ($fields['accessType']['value'] == 'my_groups') {
                $accessType = Question::ACCESS_GROUP;
            }

            $question->setAccessType($accessType);
        }
        
        if (null !== $fields['disciplines']['value']) {
            foreach ($disciplinesId as $disciplineId) {
                $discipline = $this->getRepository('instructed_discipline')->find($disciplineId);

                $question->addInstructedDiscipline($discipline);
            }
        }

        $this->persist($question);
        $this->flush();
        
        return $this->getUpdateSuccess('question', $question->getId());
    }

    /**
     * @Delete(path="/questions/{question_id}", name="apiv1_questions", defaults={"_project"="rpe"})
     */
    public function deleteQuestionsAction(Request $request, $question_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $question = $this->getRepository('question')->find($question_id);
            
            if (null === $question) {
                return $this->getNotFoundError();
            }
            
            if ($question->getAuthor() !== $oAuthUser && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }

        $this->remove($question);
        $this->flush();
        
        return $this->getDeleteSuccess('question', $question_id);
    }

    /**
     * @Get(path="/questions", name="apiv1_questions", defaults={"_project"="rpe"})
     */
    public function getQuestionsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'question', array('title' => 'Viaeduc - Questions', 'description' => 'List of Viaeduc questions', 'pathname' => 'question'));
    }
    
    /**
     * @Get(path="/questions/{question_id}", name="apiv1_question", defaults={"_project"="rpe"})
     */
    public function getQuestionAction(Request $request, $question_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $question = $this->getRepository('question')->find($question_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $question->getAuthor() && !$oAuthUser->isFriend($question->getAuthor())) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectAction($request, 'question', $question_id);
    }
}
