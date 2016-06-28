<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Social\Post as SocialPost;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;

use Pum\Bundle\TypeExtraBundle\Model\Media as MediaType;

class ApiPublicationController extends ApiOAuthController
{
    /**
     * @Post(path="/publications", name="apiv1_publications", defaults={"_project"="rpe"})
     */
    public function postPublicationsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
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
        $publication->setType(SocialPost::TYPE_WALL);
        $publication->setAuthor($oAuthUser);
        $publication->setTargetUser($oAuthUser);
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
        
        $this->persist($publication);
        $this->flush();
        
        return $this->getInsertSuccess('publication', $publication->getId());
    }

    /**
     * @Put(path="/publications/{publication_id}", name="apiv1_publications", defaults={"_project"="rpe"})
     */
    public function putPublicationsAction(Request $request, $publication_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $publication = $this->getRepository('post')->find($publication_id);
            
            if (null === $publication) {
                return $this->getNotFoundError();
            }
            
            if ($oAuthUser !== $publication->getAuthor() && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $fields = array('name' => array(), 'content' => array(), 'illustration' => array(), 'description' => array(), 'disciplines' => array(), 'teachingLevels' => array(), 'coAuthors' => array());
        $fields = $this->initPostFields($request, $fields);
        
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

        if (null !== $fields['coAuthors']['value']) {
            $rawCoAuthors = $fields['coAuthors']['value'];
            $coAuthorsId = explode(',', $rawCoAuthors);

            foreach ($coAuthorsId as $coAuthorId) {
                if (null === ($coAuthor = $this->getRepository('teaching_level')->find($coAuthorId))) {
                    return $this->getDoesntExistsFieldError('coAuthors', $coAuthorId);
                }
            }
        }

        $publication->setUpdateDate(new \DateTime());

        if (null !== $fields['name']['value']) {
            $publication->setName($fields['name']['value']);
        }

        if (null !== $fields['content']['value']) {
            $publication->setContent($fields['content']['value']);
        }

        if (null !== $fields['illustration']['value']) {
            $file = new MediaType();
            $file->setFile($fields['illustration']['value']);

            $publication->setMedia($file);
        }

        if (null !== $fields['description']['value']) {
            $publication->setDescription($fields['description']['value']);
        }
        
        if (null !== $fields['disciplines']['value']) {
            foreach ($disciplinesId as $disciplineId) {
                $discipline = $this->getRepository('instructed_discipline')->find($disciplineId);

                $publication->addInstructedDiscipline($discipline);
            }
        }
        
        if (null !== $fields['teachingLevels']['value']) {
            foreach ($teachingLevelsId as $teachingLevelId) {
                $teachingLevel = $this->getRepository('teaching_level')->find($teachingLevelId);

                $publication->addTeachingLevel($teachingLevel);
            }
        }
        
        if (null !== $fields['coAuthors']['value']) {
            foreach ($coAuthorsId as $coAuthorId) {
                $coAuthor = $this->getRepository('user')->find($coAuthorId);

                $publication->addCoAuthor($coAuthor);
            }
        }

        $this->persist($publication);
        $this->flush();
        
        return $this->getUpdateSuccess('publication', $publication->getId());
    }

    /**
     * @Delete(path="/publications/{event_id}", name="apiv1_publications", defaults={"_project"="rpe"})
     */
    public function deletePublicationsAction(Request $request, $publication_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $publication = $this->getRepository('post')->find($publication_id);
            
            if (null === $publication) {
                return $this->getNotFoundError();
            }
            
            if ($publication->getAuthor() !== $oAuthUser && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }

        $this->remove($publication);
        $this->flush();

        return $this->getDeleteSuccess('publication', $publication_id);
    }

    /**
     * @Get(path="/publications", name="apiv1_publications", defaults={"_project"="rpe"})
     */
    public function getPublicationsAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'post', array('type' => 'resources', 'title' => 'Viaeduc - Publications', 'description' => 'List of Viaeduc publications', 'pathname' => 'post'));
    }
    
    /**
     * @Get(path="/publications/{post_id}", name="apiv1_publication", defaults={"_project"="rpe"})
     */
    public function getPublicationAction(Request $request, $post_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $post = $this->getRepository('post')->find($post_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $post->getAuthor() && !$oAuthUser->isFriend($post->getAuthor())) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectAction($request, 'post', $post_id, array('type' => 'resources'));
    }
    
    /**
     * @Get(path="/publications/{post_id}/{version_id}", name="apiv1_publication_version", defaults={"_project"="rpe"})
     */
    public function getPublicationVersionAction(Request $request, $post_id, $version_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $post = $this->getRepository('post')->find($post_id);
            
            if (!$oAuthUser->isAdmin()) {
                if ($oAuthUser !== $post->getAuthor() && !$oAuthUser->isFriend($post->getAuthor())) {
                    return $this->getAccessRightError();
                }
            }
        }
        
        return $this->getObjectAction($request, 'post', $post_id, array('version_id' => $version_id));
    }
}
