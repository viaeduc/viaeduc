<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Rpe\Media;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;

use Pum\Bundle\TypeExtraBundle\Model\Media as MediaType;

class ApiMediaController extends ApiOAuthController
{
    /**
     * @Post(path="/medias", name="apiv1_medias", defaults={"_project"="rpe"})
     */
    public function postMediasAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        $fields = array('name' => array('required' => true), 'description' => array('required' => true), 'type' => array(), 'media' => array('required' => true), 'folder_id' => array('required' => true), 'owner_id' => array('required' => true));
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }

        if (null !== $fields['type']['value']) {
            if (!in_array($fields['type']['value'], array('post'))) {
                return $this->getBadValueFieldError('type', array('post'));
            }
        }
        
        if (null === ($folder = $this->getRepository('folder')->find($fields['folder_id']['value']))) {
            return $this->getDoesntExistsFieldError('folder');
        }
        
        if (null === ($user = $this->getRepository('user')->find($fields['owner_id']['value']))) {
            return $this->getDoesntExistsFieldError('user');
        }

        if ($user !== $oAuthUser && !$oAuthUser->isAdmin()) {
            return $this->getAccessRightError();
        }

        $type = '';

        if ($fields['type']['value'] == 'post') {
            $type = Media::TYPE_POST;
        }

        $media = $this->createObject('media');
        $media->setName($fields['name']['value']);
        $media->setDescription($fields['description']['value']);
        $media->setType($type);

        $file = new MediaType();
        $file->setFile($fields['media']['value']);

        $media->setMedia($file);

        $media->setFolder($folder);
        $media->setUser($user);
        $media->setDate(new \DateTime());
        $this->persist($media);
        $this->flush();
        
        return $this->getInsertSuccess('media', $media->getId());
    }

    /**
     * @Put(path="/medias/{media_id}", name="apiv1_medias", defaults={"_project"="rpe"})
     */
    public function putMediasAction(Request $request, $media_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $media = $this->getRepository('media')->find($media_id);

            if (null === $media) {
                return $this->getNotFoundError();
            }

            if ($oAuthUser !== $media->getUser() && !$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $fields = array('name' => array(), 'description' => array(), 'type' => array(), 'media' => array(), 'folder_id' => array());
        $fields = $this->initPostFields($request, $fields);
        
        if (true !== ($checkRequiredPostFields = $this->checkRequiredPostFields($fields))) {
            return $checkRequiredPostFields;
        }
        
        if (null !== $fields['type']['value']) {
            if (!in_array($fields['type']['value'], array('post'))) {
                return $this->getBadValueFieldError('type', array('post'));
            }
        }
        
        if (null !== $fields['folder_id']['value']) {
            if (null === ($folder = $this->getRepository('folder')->find($fields['folder_id']['value']))) {
                return $this->getDoesntExistsFieldError('folder');
            }
        }

        $type = '';

        if ($fields['type']['value'] == 'post') {
            $type = Media::TYPE_POST;
        }
        
        if (null !== $fields['name']['value']) {
            $media->setName($fields['name']['value']);
        }

        if (null !== $fields['description']['value']) {
            $media->setDescription($fields['description']['value']);
        }

        if (null !== $fields['type']['value']) {
            $media->setType($type);
        }

        if (null !== $fields['media']['value']) {
            $file = new MediaType();
            $file->setFile($fields['media']['value']);

            $media->setMedia($file);
        }

        if (null !== $fields['folder_id']['value']) {
            $media->setFolder($folder);
        }

        $this->persist($media);
        $this->flush();
        
        return $this->getUpdateSuccess('media', $media->getId());
    }

    /**
     * @Delete(path="/medias/{event_id}", name="apiv1_medias", defaults={"_project"="rpe"})
     */
    public function deleteMediasAction(Request $request, $event_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            $media = $this->getRepository('media')->find($media_id);
            
            if (null === $media) {
                return $this->getNotFoundError();
            }
            
            if ($oAuthUser !== $media->getUser() && $oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        $this->remove($media);
        $this->flush();

        return $this->getDeleteSuccess('media', $media_id);
    }

    /**
     * @Get(path="/medias", name="apiv1_medias", defaults={"_project"="rpe"})
     */
    public function getMediasAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'media');
    }
    
    /**
     * @Get(path="/medias/{media_id}", name="apiv1_media", defaults={"_project"="rpe"})
     */
    public function getMediaAction(Request $request, $media_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectAction($request, 'media', $media_id);
    }
    
    /**
     * @Get(path="/medias-folders", name="apiv1_media_folders", defaults={"_project"="rpe"})
     */
    public function getMediaFoldersAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'folder');
    }
    
    /**
     * @Get(path="/medias-folders/{folder_id}", name="apiv1_media_folder", defaults={"_project"="rpe"})
     */
    public function getMediaFolderAction(Request $request, $folder_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectAction($request, 'folder', $folder_id);
    }
}
