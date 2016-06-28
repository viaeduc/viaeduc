<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Rpe\PumBundle\Model\Social\Report;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Question;
use Rpe\PumBundle\Model\Social\Comment;
use Rpe\PumBundle\Model\Social\User;
use Pagerfanta\Pagerfanta;

/**
 * Report controller
 * 
 * @method Response reportPublicationAction(Request $request, $type, $id)
 *
 */
class ReportController extends Controller
{
    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $type        Object name
     * @param  string  $id          Object id
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/report/{type}/{id}", name="report", defaults={"_project"="rpe"})
     */
    public function reportPublicationAction(Request $request, $type, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $report = $this->createObject('report');

        $form  = $this->createNamedForm('report', 'pum_object', $report, array(
            'attr'        => array('class' => 'report-form', 'id' => 'simple-report-form', 'data-async' => '', 'data-target' => '#modal-report .modal-content'),
            'form_view'   => $this->createFormViewByName('report', 'create', $update = false),
            'with_submit' => false
        ));

        $sent = false;

        if ($request->isMethod('POST')) {
            if ($form->handleRequest($request)->isValid()) {
                $user = $this->getUser();
                $post = $this->getRepository($type)->find($id);

                $report->setUser($user);
                $report->setDate(new \DateTime());
                $report->setStatus(Report::STATUS_REPORTED);
                $report->setTargetType($type);
                $report->setTargetId($id);

                $this->persist($report);
                $this->flush();

                $sent = true;
            }
        }

        return $this->render('pum://includes/common/header/form/report.html.twig', array(
            'form' => $form->createView(),
            'type' => $type,
            'id' => $id,
            'sent' => $sent
        ));
    }
}
