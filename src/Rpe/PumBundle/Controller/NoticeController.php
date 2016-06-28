<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for notice external resource
 * 
 * @method singleNoticeAction($id)
 * @method shareNoticeAction(Request $request, $id)
 *
 */
class NoticeController extends Controller
{
    /**
     * @access public
     * @param  string  $id          The notice id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/notice/{id}", name="notice", defaults={"_project"="rpe", "id": null})
     */
    public function singleNoticeAction($id)
    {
        $notice = $this->getRepository('external_notice')->find($id);

        if ($notice) {
            return $this->render('pum://page/notices/ressource.html.twig', array(
                'notice'              => $notice,
                'storage'           => $this->container->get('type_extra.media.storage.driver')
            ));
        }

        $this->throwNotFound('error.notice.not_found');

    }

    /**
     * @access public
     * @param  string  $id          The notice id
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/notice/share/{id}", name="notice_share", defaults={"_project"="rpe"})
     */
    public function shareNoticeAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $notice = $this->getRepository('external_notice')->find($id);

        $noticeShare = $this->createObject('external_notice');

        $form  = $this->createNamedForm('external_share_notice', 'pum_object', $noticeShare, array(
            'attr'        => array('class' => 'share_notice-form', 'id' => 'simple-share_notice-form', 'data-async' => '', 'data-target' => '#modal-share .modal-content'),
            'form_view'   => $this->createFormViewByName('external_notice', 'external_share_notice', $update = false),
            'with_submit' => false
        ));

        $sent = false;

        if ($request->isMethod('POST')) {
            if ($form->handleRequest($request)->isValid()) {
                $noticeShare->setCreateDate(new \DateTime())
                            ->setUpdateDate(new \DateTime())
                            ->setTargetUser($user)
                ;

                $shareNotice = $this->createObject('external_share_notice');
                $shareNotice->setSourceNotice($notice);
                $shareNotice->setTargetNotice($noticeShare);

                $notice->addShareby($shareNotice);
                $noticeShare->addShareNotice($shareNotice);

                $this->persist($shareNotice, $user, $noticeShare, $notice);
                $this->flush();
                

                $shareCount = $notice->getShareBy()->count();

                if (!$shareCount) {
                    $shareCount = '';
                }

                $sent = true;

                $this->get('rpe.search.index.factory')->update($notice);

                // return new Response($shareCount);
            }
        }

        return $this->render('pum://includes/common/header/form/share.html.twig', array(
            'form'   => $form->createView(),
            'notice' => $notice,
            'id'     => $id,
            'sent'   => $sent
        ));
    }

}