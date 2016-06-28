<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\Question;
use Pagerfanta\Pagerfanta;
use Rpe\PumBundle\Model\Social\UserInGroup;
use Rpe\PumBundle\Model\Social\Friend;

/**
 * Questions controller 
 *
 * @method Response questionAction(Request $request, $id)
 * @method Response questionListAction(Request $request, $page)
 * @method array    getAllQuestionsForUser($user, $returnQuery = false, $mode = self::LISTMODE_ALL)
 * @method Response questionsAction($mode)
 * @method Response questionsGroupAction($group_id)
 * @method Response publishFormQuestionAction(Request $request, $group_id = null)
 * @method Response editFormQuestionAction(Request $request, $id)
 */
class QuestionController extends Controller
{
    /**
     * Question list mode
     */
    const LISTMODE_ALL      = 'all_questions';
    const LISTMODE_FRIENDS  = 'friends_questions';
    const LISTMODE_MYGROUPS = 'groups_questions';
    const LISTMODE_MINE     = 'my_questions';


    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          The question id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/question/{id}", name="question", defaults={"_project"="rpe"})
     */
    public function questionAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user          = $this->getUser();
        $question      = $this->getRepository('question')->find($id);
        $goodAnswers   = $this->getRepository('answer')->getGoodAnswers($question);

        $userHasAccess = true;

        if (null === $question) {
            $this->throwNotFound('error.question.not_found');
        }

        if ($user !== $question->getAuthor()) {
            if (null !== $group = $question->getPublishedGroup()) {
                if (false === $group->isPublic()) {
                    $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group);

                    if (null === $user_in_group || false === $user_in_group->isUserInGroup()) {
                        $userHasAccess = false;
                    }
                }
            } elseif (false === $question->isPublic()) {
                if (true === $question->isPrivate()) {
                    $relation = $this->getRepository('friend')->getRelation($user, $question->getAuthor());

                    if (null === $relation || false === $relation->isFriend()) {
                        $userHasAccess = false;
                    }
                }
            }
        }

        if ($userHasAccess && $user !== $question->getAuthor()) {
            $question->incrementViewed();
            $this->persist($question)->flush();
        }

        $lastQuestionsList = $this->getRepository('question')->getAllOtherQuestionsForUser($user, $question, false, 3);

        $session = $request->getSession();
        if($answerOrder = $request->query->get('answerOrder')){
            $session->set('answerOrder', $answerOrder);
        }
        else {
            $answerOrder = $session->get('answerOrder') ? $session->get('answerOrder') : 'ASC';
        }

        return $this->render('pum://page/question/question.html.twig', array(
            'question'          => $question,
            'lastQuestionsList' => $lastQuestionsList,
            'goodAnswers'       => $goodAnswers,
            'userHasAccess'     => $userHasAccess,
            'answerOrder'       => $answerOrder
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $page        The page number
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/questionlist/{page}", name="ajax_questionlist", defaults={"_project"="rpe", "page"="1"})
     */
    public function questionListAction(Request $request, $page)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // Page
        $byPage = 9;
        $user   = $this->getUser();

        $group = null;
        $mode = null;

        if (null !== ($group_id = $request->query->get('group_id', null))) { // Group?
            $group = $this->getRepository('group')->find($group_id);

            $questions = $this->getRepository('question')->getAllQuestionsForGroup($group, true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($questions, true, false);
        } elseif ($mode = $request->query->get('mode', self::LISTMODE_ALL)) { // Mode
            // Get Groups
            if (self::LISTMODE_FRIENDS == $mode) {
                $questions = $this->getAllQuestionsForUser($user, true, $mode);
                $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($questions, true, false);
            } elseif (self::LISTMODE_MYGROUPS == $mode) {
                $questions = $this->getAllQuestionsForUser($user, true, $mode);
                $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($questions, true, false);
            } elseif (self::LISTMODE_MINE == $mode) {
                $questions = $user->getQuestions();
                $pager = new \Pagerfanta\Adapter\DoctrineCollectionAdapter($questions);
            } else {
                $questions = $this->getAllQuestionsForUser($user, true);
                $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($questions, true, false);
            }
        }

        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/question/ajax-questionlist.html.twig', array(
            'mode'  => $mode,
            'group' => $group,
            'pager' => $pager
        ));
    }

    /**
     * @access private
     * @param  User     $user           User object
     * @param  boolean  $returnQuery    Return query or not
     * @param  string   $mode           List mode of questions
     * 
     * @return array  Array contains all questions
     */
    private function getAllQuestionsForUser($user, $returnQuery = false, $mode = self::LISTMODE_ALL)
    {
        // check in group
        $uigQuery = $this->getRepository('user_in_group')
            ->createQueryBuilder('uig')
            ->leftJoin('uig.group', 'g')
            ->select('g.id')
            ->andWhere('uig.user = :user')
            ->andWhere('uig.status = :uigStatus');

        // check in relation
        $friendQuery = $this->getRepository('friend')
            ->createQueryBuilder('f')
            ->leftJoin('f.friend', 'friend')
            ->select('friend.id')
            ->andWhere('f.user = :user')
            ->andWhere('f.status = :friendStatus');

        $qb = $this->getRepository('question')->createQueryBuilder('q');

        $filter_relation = $qb->expr()->andX(
            $qb->expr()->eq('q.accesstype', ':acces_friend'),
//             $qb->expr()->neq('q.author', ':user'),
            $qb->expr()->in('q.author', $friendQuery->getDQL())
        );
        $filter_group = $qb->expr()->andX(
            $qb->expr()->eq('q.accesstype', ':acces_group'),
//             $qb->expr()->neq('q.author', ':user'),
            $qb->expr()->in('q.publishedGroup', $uigQuery->getDQL())
        );
        $filter_publicAndAuthor = $qb->expr()->orX(
            $qb->expr()->eq('q.accesstype', ':acces_public'),
            $qb->expr()->eq('q.author', ':user')
        );

        switch ($mode)
        {
            case self::LISTMODE_FRIENDS:
                $filter = $filter_relation;
                $params = array(
                    'acces_friend' => question::ACCESS_FRIENDS,
                    'user'         => $user,
                    'friendStatus' => Friend::STATUS_ACCEPTED
                );
                break;
            case self::LISTMODE_MYGROUPS:
                $filter = $filter_group;
                $params = array(
                    'acces_group'  => Question::ACCESS_GROUP,
                    'user'         => $user,
                    'uigStatus'    => UserInGroup::IN_GROUP,
                );
                break;
            default:
                $filter = $qb->expr()->orX($filter_publicAndAuthor, $filter_group, $filter_relation);
                $params = array(
                    'user'         => $user,
                    'acces_public' => Question::ACCESS_PUBLIC,
                    'acces_group'  => Question::ACCESS_GROUP,
                    'acces_friend' => question::ACCESS_FRIENDS,
                    'uigStatus'    => UserInGroup::IN_GROUP,
                    'friendStatus' => Friend::STATUS_ACCEPTED
                );
                break;
        }

        $questions = $qb
            ->andWhere($filter)
            ->setParameters($params)
            ->orderBy('q.id', 'DESC');

        if ($returnQuery) {
            return $questions->getQuery();
        }

        return $questions->getQuery()->getResult();
    }

    /**
     * @access public
     * @param  string   $mode           List mode of questions
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/questions/{mode}", name="questions", defaults={"_project"="rpe", "mode"=null})
     */
    public function questionsAction($mode)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        if($user->isInvited()){
            $mode = self::LISTMODE_MYGROUPS;
        }

        if (null == $mode) {
            $mode = self::LISTMODE_ALL;
        } elseif (!in_array($mode, array(self::LISTMODE_ALL, self::LISTMODE_FRIENDS, self::LISTMODE_MYGROUPS, self::LISTMODE_MINE))) {
            $this->throwNotFound('error.filters.not_found');
        }

        return $this->render('pum://page/question/questions.html.twig', array(
            'mode' => $mode
        ));
    }

    /**
     * @access public
     * @param  string   $group_id       Group id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/questions_group/{group_id}", name="questions_group", defaults={"_project"="rpe"})
     */
    public function questionsGroupAction($group_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $group = $this->getRepository('group')->find($group_id);

        return $this->render('pum://page/question/questions.html.twig', array(
            'group'   => $group
        ));
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string   $group_id       Group id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/create/question/{group_id}", name="publish_question", defaults={"_project"="rpe"})
     */
    public function publishFormQuestionAction(Request $request, $group_id = null)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user   = $this->getUser();

        $question     = $this->createObject('question');

        $questionForm = $this->createNamedForm('question', 'pum_object', $question, array(
            'form_view' => $this->createFormViewByName('question', 'create', $update = false)
        ));

        if ($response = $this->get('pum.form_ajax')->handleForm($questionForm, $request)) {
            return $response;
        }

        if(null !== $group_id) {
            $group = $this->getRepository('group')->find($group_id);
        }

        if ($request->isMethod('POST') && $questionForm->handleRequest($request)->isValid()) {
            $counter = $this->createObject('counter')->setValue(0);
            $question
                ->setAuthor($user)
                ->setDate(new \DateTime())
                ->setViewed($counter);
            ;

            if(null !== $group_id) {
                $question->setPublishedGroup($group);
            }

            if (null !== $question->getPublishedGroup()) {
                $question->setAccesstype($question::ACCESS_GROUP);
            } elseif ($question->isInGroup()) {
                $question->setAccesstype($question::ACCESS_PUBLIC);
            }

            $user->addQuestion($question);

            $this->persist($counter, $question, $user)->flush();

            return $this->redirect($this->generateUrl('question', array('id' => $question->getId())));
        }

        if(null !== $group_id) {
            return $this->render('pum://page/question/form-publish-minimal.html.twig', array(
                'publishTypeActive' => 'question',
                'form'              => $questionForm->createView(),
                'group'             => $group
            ));
        }
        else {
            return $this->render('pum://page/question/form-publish.html.twig', array(
                'publishTypeActive' => 'question',
                'form'              => $questionForm->createView()
            ));
        }
    }

    /**
     * @access public
     * @param  Request  $request     A request instance
     * @param  string   $id          Question id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/question/{id}/edit", name="edit_question", defaults={"_project"="rpe"})
     */
    public function editFormQuestionAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user   = $this->getUser();

        $question     = $this->getRepository('question')->getSingleQuestion($id, $this->getUser());
        if (null !== $question) {
            $questionForm = $this->createNamedForm('question', 'pum_object', $question, array(
                'form_view' => $this->createFormViewByName('question', 'create', $update = false)
            ));

            if ($response = $this->get('pum.form_ajax')->handleForm($questionForm, $request)) {
                return $response;
            }

            if ($request->isMethod('POST') && $questionForm->handleRequest($request)->isValid()) {
                $question
                    ->setDate(new \DateTime())
                ;

                $this->persist($question)->flush();

                return $this->redirect($this->generateUrl('question', array('id' => $question->getId())));
            }


            return $this->render('pum://page/question/form-publish.html.twig', array(
                'publishTypeActive' => 'question',
                'edit'              => true,
                'form'              => $questionForm->createView()
            ));
        }

        $this->throwAccessDenied('error.question.manage_access_denied');
    }
}
