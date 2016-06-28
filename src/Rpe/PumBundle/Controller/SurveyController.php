<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Survey;
use Rpe\PumBundle\Model\Social\Notification;

/**
 * Survey controller
 * 
 * @method Response surveyAnswerAction(Request $request, $id)
 * @method Response createFormSurveyAction(Request $request, $group_id)
 * @method Response closeSurveyAction(Request $request, $id)
 * @method Response editFormSurveyAction(Request $request, $id)
 * @method array    postSurvey(Request $request, $id = null, $group = null)
 *
 */
class SurveyController extends Controller
{
    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Survey object id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/surveyanswer/q{id}", name="survey_answer", defaults={"_project"="rpe"})
     */
    public function surveyAnswerAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $survey = $this->getRepository('survey')->find($id);

        if (null === $survey) {
            return new Response('Error');
        }

        $userAnswers = $request->request->get('vote-answer');

        if(count($userAnswers)) {
            if(count($userAnswers) > 1 && !$survey->getMultiple()) {
                return new Response('Error');
            }

            foreach($userAnswers as $userAnswer) {
                $userSurveyAnswer = $this->getRepository('survey_answer')->find($userAnswer);

                if (null === $userSurveyAnswer) {
                    return new Response('Error');
                }
                
                $surveyUserAnswer = $this->createObject('user_in_survey_answer');

                $surveyUserAnswer
                    ->setUser($user)
                    ->setSurvey($survey)
                    ->setSurveyAnswer($userSurveyAnswer)
                    ->setDate(new \DateTime())
                ;
                
                $this->persist($surveyUserAnswer);
            }

            $this->flush();
        }

        return $this->redirect($this->generateUrl('group', array('id' => $survey->getOwnerGroup()->getId())));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $group_id    Group id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/create/survey/{group_id}", name="publish_survey", defaults={"_project"="rpe"})
     */
    public function createFormSurveyAction(Request $request, $group_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($group_id);

        if (!$group) {
            return new Response('Error');
        }

        $result = $this->postSurvey($request, null, $group);

        if ($result['result']) {
            return $this->redirect($this->generateUrl('group', array('id' => $group_id)));
        }

        return $this->render('pum://page/survey/form-publish-minimal.html.twig', array(
            'publishTypeActive' => 'survey',
            'edit'              => false,
            'form'              => $result['form']->createView(),
            'group'             => $group,
            'is_active'         => false
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Survey object id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/survey/close/{id}", name="close_survey", defaults={"_project"="rpe"})
     */
    public function closeSurveyAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $survey = $this->getRepository('survey')->find($id);
        $survey->setEndDate(new \DateTime());

        $this->persist($survey);
        $this->flush();

        return $this->redirect($this->generateUrl('group', array('id' => $survey->getOwnerGroup()->getId())));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Survey object id
     * 
     * @return Response A Response instance 
     * 
     * @Route(path="/edit/survey/{id}", name="edit_survey", defaults={"_project"="rpe"})
     */
    public function editFormSurveyAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $result = $this->postSurvey($request, $id);

        $group = $result['survey']->getOwnerGroup();

        if ($result['result']) {
            return $this->redirect($this->generateUrl('group', array('id' => $group->getId())));
        }

        return $this->render('pum://page/survey/form-publish-minimal.html.twig', array(
            'publishTypeActive' => 'survey',
            'edit'              => true,
            'form'              => $result['form']->createView(),
            'survey'            => $result['survey'],
            'is_active'         => $result['survey']->isActive()
        ));
    }

    /**
     * @access private
     * @param  Request $request     A request instance
     * @param  string  $id          Survey object id
     * @param  Group   $group       Group object
     *  
     * @return array An array contains survey form , result and survey object 
     */
    private function postSurvey(Request $request, $id = null, $group = null)
    {
        $user = $this->getUser();

        if (null !== $id) {
            $survey = $this->getRepository('survey')->find($id);

            $formName = 'survey_edit';
        }
        else {
            $survey = $this->createObject('survey');

            $formName = 'survey_publish';
        }
        
        $surveyForm = $this->createNamedForm($formName, 'pum_object', $survey, array(
            'attr'        => array('class' => $formName.'-form', 'id' => 'simple-'.$formName.'-form', 'data-name' => $formName),
            'form_view'   => $this->createFormViewByName('survey', 'create', $update = false)
        ));
        $surveyForm
            ->add('timezone', 'rpe_timezone', array('data' => $survey->getTimezone() ?: $this->getUserTimezone()))
        ;
        // set dates options
        $surveyForm
            ->add('startDate', 'date', array(
                'data' => $survey->getStartDate(),
                'label' => 'Début',
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'view_timezone' => $survey->getTimezone() ?: date_default_timezone_get()
            ))
            ->add('endDate', 'date', array(
                'data' => $survey->getEndDate(),
                'label' => 'Fin',
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'view_timezone' => $survey->getTimezone() ?: date_default_timezone_get()
            ))
        ;

        $result = false;

        if ($request->isMethod('POST')) {
            if ($surveyForm->handleRequest($request)->isValid()) {
                $survey->setDate(new \DateTime());
                // set dates with selected timezone and save them in default configuration timezone
                if (null === $id) {
                    $surveyTimezone = new \DateTimeZone($surveyForm->get('timezone')->getData());
                    foreach (array('startDate' => 'setStartDate', 'endDate' => 'setEndDate') as $fieldName => $setDate) {
                        if ($aDate = $surveyForm->get($fieldName)->getData()) {
                            $aDate = new \DateTime(date('Y-m-d H:i', $aDate->getTimestamp()), $surveyTimezone);
                            $aDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));

                            $survey->$setDate($aDate);
                        }
                    }
                }
                
                if (null !== $group) {
                    $survey->setOwnerGroup($group);
                }

                $answers = $request->request->get('answer');

                if(null !== $answers && count($answers) >= 2) {
                    if (null !== $id) {
                        $surveyAnwsers = $this->getRepository('survey_answer')->findBySurvey($survey);

                        foreach($surveyAnwsers as $surveyAnwser) {
                            $this->remove($surveyAnwser);
                        }
                    }

                    foreach($answers as $answer) {
                        $surveyAnswer = $this->createObject('survey_answer');
                        $surveyAnswer->setSurvey($survey);
                        $surveyAnswer->setName($answer);
                        $surveyAnswer->setDate(new \DateTime());
                        
                        $this->persist($surveyAnswer);
                    }
                }

                $this->persist($survey);
                $this->flush();

                $result = true;
            }
        }

        return array(
            'form'     => $surveyForm,
            'result'   => $result,
            'survey'   => $survey
        );
    }
}
