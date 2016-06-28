<?php
namespace Rpe\PumBundle\Controller;

use Symfony\Component\Finder\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Link preview controller
 * 
 * @method Response linkPreviewAction(Request $request)
 * @method Response linkPreviewVideoAction(Request $request, $id)
 * @method File     getFileFromUrl($url)
 * @method string   getExtension($mime)
 * 
 */
class LinkPreviewController extends Controller
{
    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response             A Response instance
     * 
     * @Route(path="/link_preview", name="link_preview", defaults={"_project"="rpe"})
     */
    public function linkPreviewAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return new Response(json_encode(array('result' => 'error')));
        }

        $url           = $request->query->get('url', '');
        $imageQuantity = $request->query->get('imagequantity', 1);
        $header        = $request->query->get('header', '');

        if (null !== $link_preview = $this->getRepository('link_preview')->findOneByUrl($url)) {
            if ($link_preview->isUpdated()) {
                return new JsonResponse(array(
                    'result' => 'OK',
                    'id'     => $link_preview->getId(),
                    'html'   => $this->render('pum://page/linkpreview/index.html.twig', array(
                        'link' => $link_preview
                    ))->getContent()
                ));
            }
        }

        if (null !== $result = $this->get('rpe.link.preview')->crawl($url, $imageQuantity, $header)) {
            if (null === $link_preview) {
                $link_preview = $this->createObject('link_preview');
            }

            $link_preview
                ->setTitle($result['title'])
                ->setUrl($url)
                ->setCanonicalUrl($result['canonicalUrl'])
                ->setDescription($result['description'])
                ->setImages(serialize($result['images']))
                ->setVideo($result['video'])
                ->setVideoIframe($result['videoIframe'])
                ->setLastUpdate(new \DateTime())
            ;

            if (null !== $file = $this->getFileFromUrl($link_preview->getFirstImage())) {
                $link_preview->setPreview(new \Pum\Bundle\TypeExtraBundle\Model\Media(null, 'preview', new \Symfony\Component\HttpFoundation\File\File($file)));
            }

            $this->persist($link_preview)->flush();

            return new JsonResponse(array(
                'result' => 'OK',
                'id'     => $link_preview->getId(),
                'html'   => $this->render('pum://page/linkpreview/index.html.twig', array(
                    'link' => $link_preview
                ))->getContent()
            ));
        }

        return new Response(json_encode(array('result' => 'error')));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Link preview object id
     * 
     * @return Response             A Response instance
     *  
     * @Route(path="/link_preview_video/{id}", name="link_preview_video", defaults={"_project"="rpe"})
     */
    public function linkPreviewVideoAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return new Response(json_encode(array('result' => 'error')));
        }

        return $this->render('pum://page/linkpreview/video.html.twig', array(
            'link' => $this->getRepository('link_preview')->find($id)
        ));
    }

    /**
     * @access private
     * @param  Request $request     A request instance
     * @param  string  $url         Url to get 
     * 
     * @return File             Symfony file object
     */
    private function getFileFromUrl($url)
    {
        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        # the request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_exec($ch);

        # get the content type
        if (null === $ext = $this->getExtension($mime = @curl_getinfo($ch, CURLINFO_CONTENT_TYPE))) {
            return null;
        }

        $filename = md5($url.time()).'.'.$ext;
        $src = sys_get_temp_dir().DIRECTORY_SEPARATOR.$filename;

        if (false === @copy($url, $src)) {
            return null;
        }

        register_shutdown_function(function () use ($src) {
            @unlink($src);
        });

        return new \Symfony\Component\HttpFoundation\File\File($src);
    }

    /**
     * @access private
     * @param  string  $mime        The mime type to process
     * 
     * @return string  The extention string
     */
    private function getExtension($mime)
    {
        $mimes = array(
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/svg'  => 'svg',
        );

        if (isset($mimes[$mime])) {
            return $mimes[$mime];
        }

        return null;
    }
}
