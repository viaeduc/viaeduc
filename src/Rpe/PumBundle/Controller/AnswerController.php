<?php

namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\Question;
use Rpe\PumBundle\Model\Social\Notification;
use Symfony\Component\Form\Form;
use Rpe\PumBundle\Model\Social\Answer;

/**
 * Answer controller.
 * Controller dedicated to the answers of the "Questions" part of social network
 *
 * @method Response requestManageAnswerAction(Request $request, $mode, $id)
 * @method Response createAnswerAction(Request $request)
 * @method Response addAnswerAction(Request $request, $form, $answer)
 * @method array    createAnswerForm(Request $request)
 *
 */
class AnswerController extends Controller
{
    /**
     * Answer actions
     */
    const ACTION_VALID   = 'set_valid';
    const ACTION_INVALID = 'set_invalid';

    /**
     * Action to vote for an answer.
     * Manage the vote set to the answer (by adding or removing it)
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $mode        Manage mode ("add" or "remove")
     * @param  string  $id          Id of the answer
     *
     * @return Response A Response instance
     *
     * @Route(path="/answer-action/manage/{mode}/{id}", name="answer_action_manage_request", defaults={"_project"="rpe"})
     */
    public function requestManageAnswerAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            $redirect_url = $request->get('redirect_url', null);
            $user         = $this->getUser();

            if (null !== $answer = $this->getRepository('answer')->find($id)) {
                $question     = $answer->getQuestion();
                if ($user === $question->getAuthor()) {
                    if ($mode == 'add') {
                        $answer->setIsGood(true);

                        $this->persist($answer)->flush();
                    } elseif ($mode == 'remove') {
                        $answer->setIsGood(false);

                        $this->persist($answer)->flush();
                    }

                    $this->get('rpe.search.index.factory')->update($question);

                    return $this->render('pum://page/ajax/action/like-request.html.twig', array(
                        'id'        => $id,
                        'object'    => $answer,
                        'type'      => 'answer_select'
                    ));
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * Action to display the answer form
     * Display the answer form and delegate submission to addAnswerAction()
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @uses AnswerController:addAnswerAction() to process the submission of the form
     *
     * @return Response A Response instance
     *
     * @Route(path="/createform/type/answer", name="create_form_answer", defaults={"_project"="rpe"})
     */
    public function createAnswerAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        list($form, $answer) = $this->createAnswerForm($request);

        if ($response = $this->addAnswerAction($request, $form, $answer)) {
            return $response;
        }

        return $this->render('pum://includes/common/componants/comments/question-comment-form.html.twig', array(
            'form'  => $form->createView()
        ));
    }

    /**
     * Process the post submission of an answer.
     * Create an answer based on form submission
     *
     * @access private
     * @param  Request $request         A request instance
     * @param  Form    $form            Form instance for answer form
     * @param  Answer  $answer          Answer instance
     *
     * @return Response A Response instance or false if failed
     *
     */
    private function addAnswerAction(Request $request, $form, $answer)
    {
        $user          = $this->getuser();
        $canPostAnswer = true;

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $question_id = $form->get('question_id')->getData();
            $answer_id   = $form->get('parent_answer_id')->getData();

            if (null !== $question = $this->getRepository('question')->find($question_id)) {
                if (false === $question->isPublic() && $user !== $question->getAuthor()) {
                    if (true === $question->isPrivate()) {
                        $relation = $this->getRepository('friend')->getRelation($user, $question->getAuthor());

                        if (null === $relation || false === $relation->isFriend()) {
                            $canPostAnswer = false;
                        }
                    } elseif (true === $question->isInGroup() && null !== $group = $question->getPublishedGroup()) {
                        $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group);

                        if (null === $user_in_group || false === $user_in_group->isUserInGroup()) {
                            $canPostAnswer = false;
                        }
                    }
                }

                if ($canPostAnswer) {
                    $answer
                        ->setAuthor($user)
                        ->setQuestion($question)
                        ->setIsGood(false)
                        ->setDate(new \DateTime())
                    ;

                    $user->addAnswer($answer);
                    $question->addAnswer($answer);

                    if (null !== $answer_id) {
                        $parent_answer = $this->getRepository('answer')->find($answer_id);
                        if (null !== $parent_answer) {
                            $answer->setParent($parent_answer);
                            $parent_answer->addChild($answer);
                            $this->persist($parent_answer);
                        }
                    }

                    $this->persist($user, $question, $answer)->flush();

                    if ($user !== $question->getAuthor()) {
                        $this
                            ->get('rpe.notifications')
                            ->create($question->getAuthor(), Notification::TYPE_ANSWER, $user, $question)
                        ;
                    }

                    $this->get('rpe.search.index.factory')->update($question);

                    return $this->render('pum://includes/common/componants/questions/answer.html.twig', array(
                        'answer'   => $answer,
                        'question' => $question,
                        'user'     => $user
                    ));
                }
            }

            $this->throwAccessDenied('error.anwser.answer_denied');
        }

        return false;
    }

    /**
     * Action to create and process the anwser form.
     * Create and display the answer (comment) form, and process it when submitted
     *
     * @access private
     * @param  Request $request         A request instance
     *
     * @return array        An array contains the form instance and answer
     */
    private function createAnswerForm(Request $request)
    {
        $question_id = $request->query->get('question', null);
        $answer_id   = $request->query->get('answer', null);

        $answer = $this->createObject('answer');

        $form = $this->createNamedForm('answer', 'pum_object', $answer, array(
            'form_view'   => $this->createFormViewByName('answer', 'simple', $update = false),
            'with_submit' => true
        ));
        $form
            ->add('question_id', 'hidden', array('mapped' => false, 'data' => $question_id))
            ->add('parent_answer_id', 'hidden', array('mapped' => false, 'data' => $answer_id))
        ;

        return array($form, $answer);
    }
}
