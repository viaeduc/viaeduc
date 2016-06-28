<?php

namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Controller for media downloads.
 *
 * @method void downloadMediaAction($id, $objetType, $token, $media = null)
 *
 */
class DownloadController extends Controller
{

    /**
     * Handle access control to media file download
     *
     * @param int       $id         Id of object to use
     * @param string    $objetType  Type of object to use, "media" or "post"
     * @param string    $token
     *
     * @throws NotFoundException if the some parameters are missing or wrong
     *
     * @return FilesystemStorage  Return FilesystemStorage instance which corresponds to the wanted file
     *
     * @Route(path="/download/{id}/{objetType}/{token}", name="media_download", defaults={"_project"="rpe"})
     */
    public function downloadMediaAction($id, $objetType, $token, $media = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (!$id || !$objetType || !$token) {
            $this->throwNotFound();
        }

        switch ($objetType)
        {
            case 'media':
                if (null !== $object = $this->getRepository('media')->find($id)) {
                    $media = $object->getMedia();

                    if ($object->getName()) {
                        $filename = $object->getName();
                    } else {
                        $filename = $media->getName($extension = false);
                    }
                }
                break;

            case 'post':
                if (null !== $object = $this->getRepository('post')->find($id)) {
                    $media    = $object->getFile();
                    $filename = $media->getName($extension = false);
                }
                break;
        }

        if (null === $media || $token != $media->getToken()) {
            $this->throwNotFound();
        }

        $storage = $this->container->get('type_extra.media.storage.driver');

        if (false !== $storage->exists($media)) {
            $storage->getFile($media, $filename);
        }

        $this->throwNotFound();
    }
}
