<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Rpe\Folder;
use Rpe\PumBundle\Model\Rpe\Media as RpeMedia;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Rpe\PumBundle\Model\Social\User;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Media Library controller action methods.
 *
 * @method Response createFolderAction(Request $request, $folder_id)
 * @method Response ajaxMoveMediaToFolderAction(Request $request, $media_id, $folder_id)
 * @method Response libraryIndexAction(Request $request, $folder_id)
 * @method Response ajaxLibraryIndexAction(Request $request, $folder_id)
 * @method Response editMediaAction(Request $request, $media_id)
 * @method Response addFolder(Request $request, $form, $folder)
 * @method Response editFolder(Request $request, $form, $folder)
 * @method Response createFolderForm(Request $request, $folder_id)
 * @method Response addMedia(Request $request, $form, $media)
 * @method Response editMedia(Request $request, $form, $media)
 * @method array    createMediaForm(Request $request, $media_id = null, $folder_id = null)
 * @method void     corrigeUserDiskQuota($user)
 *
 */
class LibraryController extends Controller
{
    /**
     * Display the form to create a media folder.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $folder_id   The folder id
     *
     * @uses LibraryController:createFolderForm() to create the form
     *
     * @return Response A Response instance
     *
     * @Route(path="/createform/type/library_folder/{folder_id}", name="create_form_library_folder", defaults={"_project"="rpe", "folder_id"=null})
     */
    public function createFolderAction(Request $request, $folder_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        list($form, $folder) = $this->createFolderForm($request, $folder_id);

        if (null !== $folder_id && $response = $this->editFolder($request, $form, $folder)) {
            return $response;
        } elseif ($response = $this->addFolder($request, $form, $folder)) {
            return $response;
        }

        return $this->render('pum://includes/common/componants/library/folder-form.html.twig', array(
            'form'  => $form->createView()
        ));
    }

    /**
     * XHR Method to move a media to another folder.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $media_id    The media id
     * @param  string  $folder_id   The folder id
     *
     * @return Response A Response instance
     *
     * @Route(path="/library/ajax-media-move/{media_id}/{folder_id}", name="ajax_move_media_to_folder", defaults={"_project"="rpe", "folder_id"=null})
     */
    public function ajaxMoveMediaToFolderAction(Request $request, $media_id, $folder_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        if ($request->isXmlHttpRequest()) {
            if ((null !== $media = $this->getRepository('media')->find($media_id)) && $user === $media->getUser()) {
                if ((null !== $folder_id) && (null !== $folder = $this->getRepository('folder')->find($folder_id))) {
                    if ($user !== $folder->getUser()) {
                        return new Response('ERROR'); // Pas le propriétaire du folder
                    }

                    $media->setFolder($folder);
                } else {
                    $media->setFolder(null);
                }

                $this->flush();

                return new Response('OK');
            }
        }

        return new Response('ERROR');
    }

    /**
     * List media in folder, or in root folder.
     * Method available both in XHR or not.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $folder_id   The folder id
     *
     * @return Response A Response instance
     *
     * @Route(path="/library/{folder_id}", name="library", defaults={"_project"="rpe", "folder_id"=null})
     */
    public function libraryIndexAction(Request $request, $folder_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $user_quota = $user->getMeta(User::META_MEDIA_DISK_QUOTA);
        if (null === $user_quota || (int)$user_quota->getValue() < 0) {
            $this->corrigeUserDiskQuota($user);
        }

        // if AJAX request : return only media from folder instead of all library page
        if ($request->isXmlHttpRequest()) {
            // Get medias from folder
            if (null !== $folder_id) {
                $folder = $this->getRepository('folder')->find($folder_id);
            } else {
                $folder = null;
            }

            $medias = $this->getUser()->getMediasInFolder($folder);

            return $this->render('pum://page/library/ajax-media_list.html.twig', array(
               'folder' => $folder,
               'medias' => $medias,
               'folder_id' => $folder_id
            ));
        }

        // Get user folders
        $folders = $this->getRepository('folder')->getUserFolders($user);

        return $this->render('pum://page/library/library.html.twig', array(
            'folders'   => $folders,
            'folder_id' => $folder_id,
            'disk_quota_m' => number_format($user->getDiskQuota() / 1024 / 1024, 2),
            'disk_quota_p' => number_format($user->getDiskQuota() * 100 / 1024 / 1024 / 1024, 2)
        ));
    }

    /**
     * List media in folder, or in root folder.
     * Used in CKEditor plugin and Publication form attachment.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $folder_id   The folder id
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-library/{folder_id}", name="ajax_library", defaults={"_project"="rpe", "folder_id"=null})
     */
    public function ajaxLibraryIndexAction(Request $request, $folder_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();
        $modal = $request->query->get('modal', false);

        // if AJAX request : return only media from folder instead of all library page
        if ($request->isXmlHttpRequest() && !$modal) {
            // Get medias from folder
            if (null !== $folder_id) {
                $folder = $this->getRepository('folder')->find($folder_id);
            } else {
                $folder = null;
            }
            $medias = $this->getUser()->getMediasInFolder($folder);

            return $this->render('pum://page/library/ajax-media_list.html.twig', array(
                'folder' => $folder,
                'medias' => $medias
            ));
        }

        // Get user folders
        $folders = $this->getRepository('folder')->getUserFolders($user);

        return $this->render(($modal) ? 'pum://page/library/ajax-modal-library.html.twig' : 'pum://page/library/ajax-library.html.twig', array(
            'folders'   => $folders,
            'folder_id' => $folder_id
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $media_id    The media id
     *
     * @return Response A Response instance
     *
     * @Route(path="/createform/type/library_media/{media_id}", name="create_form_library_media", defaults={"_project"="rpe", "media_id"=null})
     */
    public function editMediaAction(Request $request, $media_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $folder_id = $request->query->get('folder_id', null);

        $user = $this->getUser();
        list($form, $media) = $this->createMediaForm($request, $media_id, $folder_id);

        if (null !== $media_id && $response = $this->editMedia($request, $form, $media)) {
            return $response;
        } elseif ($response = $this->addMedia($request, $form, $media)) {
            return $response;
        }

        $form_template = (isset($media_id)) ? "media-form-edit.html.twig" : "media-form.html.twig";

        return $this->render('pum://includes/common/componants/library/' . $form_template, array(
            'form'      => $form->createView(),
            'folder_id' => $folder_id
        ));
    }

    /**
     * Add folder
     *
     * @access private
     * @param  Request $request     A request instance
     * @param  Form    $form        Form object
     * @param  Folder  $folder      Folder object
     *
     * @return Response|boolean A Response instance
     */
    private function addFolder(Request $request, $form, $folder)
    {
        $user = $this->getUser();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $folder
                ->setUser($user)
                ->setType(Folder::TYPE_MEDIA)
            ;
            $user->addFolder($folder);

            $this->persist($user, $folder)->flush();

            return $this->render('pum://includes/common/componants/library/folder-form.html.twig', array(
                'user'   => $user,
                'folder' => $folder
            ));
        }

        return false;
    }

    /**
     * @access private
     * @param  Request $request     A request instance
     * @param  Form    $form        Form object
     * @param  Folder  $folder      Folder object
     *
     * @return Response|boolean A Response instance
     *
     */
    private function editFolder(Request $request, $form, $folder)
    {
        $user = $this->getUser();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $this->persist($folder)->flush();

            return $this->render('pum://includes/common/componants/library/folder-form.html.twig', array(
                'user'   => $user,
                'folder' => $folder
            ));
        }

        return false;
    }

    /**
     * @access private
     * @param  Request $request     A request instance
     * @param  string  $folder_id   Folder id
     *
     * @return array Array contain the form and folder object
     */
    private function createFolderForm(Request $request, $folder_id)
    {
        if (null === $folder_id || ((null === $folder = $this->getRepository('folder')->find($folder_id)) || $folder->getUser() !== $this->getUser())) {
            $folder = $this->createObject('folder');
        }

        $form = $this->createNamedForm('folder', 'pum_object', $folder, array(
            'form_view'   => $this->createFormViewByName('folder', 'simple', $update = false),
            'with_submit' => true
        ));

        return array($form, $folder);
    }

     /**
     * @access private
     * @param  Request $request     A request instance
     * @param  Form    $form        Form object
     * @param  Media   $media       Media object
     *
     * @return Response A Response instance
     */
    private function addMedia(Request $request, $form, $media)
    {
        $user = $this->getUser();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $folder_id = $form->get('folder_id')->getData();

            $em = $this->getOEM();
            $em->getConnection()->beginTransaction();

            $media
                ->setUser($user)
                ->setType(RpeMedia::TYPE_POST)
                ->setDate(new \DateTime())
            ;
            if (null !== $folder_id) {
                $folder = $this->getRepository('folder')->find($folder_id);
                $media->setFolder($folder);
            }
            $this->persist($media)->flush();

            $user_quota = $user->getDiskQuota();
            $check_media = $this->checkMediaSize($media);
            $uploadFile = $media->getMedia()->getFile();
            $storage = $this->get('type_extra.media.storage.driver');

            if ($check_media === false) {
                $em->getConnection()->rollback();
                $result = array(
                    "name" => $uploadFile->getClientOriginalName(),
                    "size" => $uploadFile->getClientSize(),
                    "error" => $this->get('translator')->trans('library.nospace', array(), 'rpe')
                );
            } else {
                $user_quota += $check_media;
                $this->setUserMeta($user, User::META_MEDIA_DISK_QUOTA, $user_quota);
                $em->getConnection()->commit();

                $pum_media = $media->getMedia();
                $result = array(
                    "name" => $uploadFile->getClientOriginalName(),
                    "size" => $uploadFile->getClientSize(),
                    "url"  => $this->get('request')->getSchemeAndHttpHost() . $storage->getWebPath($pum_media),
                    "deleteUrl" => $this->generateUrl('delete_media', array('id' => $media->getId(), 'fromupload' => true)),
                    "deleteType" => "POST"
                );
                if ($pum_media->isImage()) {
                    $result["thumbnailUrl"] = $this->get('request')->getSchemeAndHttpHost() . $storage->getWebPath($pum_media, 70, 50);
                }
            }

            $response = new JsonResponse();
            $response->setData(array('files' => array($result)));
            return $response;
        }
        return false;
    }

    /**
     * @access private
     * @param  Request $request     A request instance
     * @param  Form    $form        Form object
     * @param  Media   $media       Media object
     *
     * @return Response A Response instance
     */
    private function editMedia(Request $request, $form, $media)
    {
        $user = $this->getUser();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $folder_id = $form->get('folder_id')->getData();

            $this->persist($media)->flush();

            return $this->redirect($this->generateUrl('library', array('folder_id' => $folder_id)));
        }

        return false;
    }

    /**
     * @access private
     * @param  Request $request     A request instance
     * @param  string  $media_id    Media id
     * @param  string  $folder_id   Folder id
     *
     * @return array  Array contains form and media object
     */
    private function createMediaForm(Request $request, $media_id = null, $folder_id = null)
    {
        $form_type = "simple";
        if (null === $media_id || ((null === $media = $this->getRepository('media')->find($media_id)) || $media->getUser() !== $this->getUser())) {
            $media = $this->createObject('media');
            $form_type = "upload";
        }

        $form = $this->createNamedForm('media', 'pum_object', $media, array(
            'form_view'   => $this->createFormViewByName('media', $form_type, $update = false),
            'with_submit' => false
        ));
        $form
            ->add('folder_id', 'hidden', array('mapped' => false, 'data' => $folder_id))
        ;
        return array($form, $media);
    }

    /**
     * Modify user disk quota if it's not initialized
     *
     * @access private
     * @param  User  $user  User object
     *
     * @return void
     */
    private function corrigeUserDiskQuota($user)
    {
        $quota = 0;
        $directory  = $this->container->getParameter('pum_type_extra.media.storage.filesystem.directory');
        $storage    = $this->get('type_extra.media.storage.driver');

        $medias = $user->getMedias();
        foreach ($medias as $media) {
            $pum_media = $media->getMedia();
            if ($pum_media && $pum_media->exists()) {
                $media_url = $pum_media->getMediaUrl($storage);
                $file = $directory . $media_url;
                if (file_exists($file)) {
                    $size = filesize($file);
                    $quota += $size;
                }
            }
        }
        $this->setUserMeta($user, User::META_MEDIA_DISK_QUOTA, $quota);
    }
}
