<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\Notification;
use Rpe\PumBundle\Model\Social\Log;

/**
 * Recommend controller
 *
 * @method Response recommendAnswerAction(Request $request, $mode, $id)
 * @method Response recommendPostAction(Request $request, $mode, $id)
 * @method Response recommendNoticeAction(Request $request, $mode, $id)
 * @method Response recommendCommentAction(Request $request, $mode, $id)
 * @method Response recommendNoticeCommentAction(Request $request, $mode, $id)
 * @method Response recommendUserAction(Request $request, $mode, $id)
 * @method Response recommendUsersAction(Request $request, $id)
 * @method boolean checkVisibility($item, $type)
 *
 */
class RecommendController extends Controller
{
    /**
     * recommend an answer
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  string  mode         action mode
     * @param  string  $id          The answer id
     * 
     * @return Response A Response instance
     *
     * @Route(path="/recommend-action/answer/{mode}/{id}", name="recommend_answer", defaults={"_project"="rpe", "id": null})
     */
    public function recommendAnswerAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $answer = $this->getRepository('answer')->find($id)) {
                    if ($this->checkVisibility($answer, 'answer')) {
                        $user = $this->getUser();


                        if ($mode == 'add') {
                            if (null === $this->getRepository('recommend_answer')->getRecommend($user, $answer)) {
                                $recommend = $this->createObject('recommend_answer')
                                    ->setUser($user)
                                    ->setAnswer($answer)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addRecommendAnswer($recommend);
                                $answer->addRecommendby($recommend);

                                $this->persist($recommend, $answer, $user)->flush();

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $recommend = $this->getRepository('recommend_answer')->getRecommend($user, $answer)) {
                                $user->removeRecommendAnswer($recommend);
                                $answer->removeRecommendby($recommend);
                                $this->remove($recommend);

                                $this->persist($answer, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/like-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $recommend,
                                'object'    => $answer,
                                'type'      => 'answer'
                            ));
                        }
                    }
                }
            }

        }

        return new Response('ERROR');
    }

    /**
     * recommend a post
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  string  mode         action mode
     * @param  string  $id          The post id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/recommend-action/post/{mode}/{id}", name="recommend_post", defaults={"_project"="rpe", "id": null})
     */
    public function recommendPostAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $post = $this->getRepository('post')->find($id)) {
                    if ($this->checkVisibility($post, 'post')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('recommend_post')->getRecommend($user, $post)) {
                                $recommend = $this->createObject('recommend_post')
                                    ->setUser($user)
                                    ->setPost($post)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addRecommendPost($recommend);
                                $post->addRecommendby($recommend);

                                $this->persist($recommend, $post, $user)->flush();
                                $this->get('rpe.notifications')->wait(Notification::TYPE_RECOMMEND, $user, $post);

                                if ($post->getResource()) {
                                    $this->get('rpe.logs')->create($user, Log::TYPE_RECOMMEND_RESOURCE, $post->getAuthor(), $post);
                                } else {
                                    $this->get('rpe.logs')->create($user, Log::TYPE_RECOMMEND_PUBLICATION, $post->getAuthor(), $post);
                                }

                                /*if($post->getAuthor() !== $user) {
                                    if($post->getResource()) {
                                        $this->get('rpe.logs')->create($user, Log::TYPE_RECOMMEND_SOMEONE_RESOURCE, $post->getAuthor(), $post);
                                    }
                                    else {
                                        $this->get('rpe.logs')->create($user, Log::TYPE_RECOMMEND_SOMEONE_PUBLICATION, $post->getAuthor(), $post);
                                    }
                                }*/

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $recommend = $this->getRepository('recommend_post')->getRecommend($user, $post)) {
                                $user->removeRecommendPost($recommend);
                                $post->removeRecommendby($recommend);
                                $this->remove($recommend);

                                $this->persist($post, $user)->flush();

                                $return = true;
                            }
                        }

                        $this->get('rpe.search.index.factory')->update($post);

                        if ($return) {
                            return $this->render('pum://page/ajax/action/like-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $recommend,
                                'object'    => $post,
                                'type'      => 'post'
                            ));
                        }
                    }
                }
            }

        }

        return new Response('ERROR');
    }

    /**
     * recommend a notice
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  string  mode         action mode
     * @param  string  $id          The notice id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/recommend-action/notice/{mode}/{id}", name="external_recommend_notice", defaults={"_project"="rpe", "id": null})
     */
    public function recommendNoticeAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $notice = $this->getRepository('external_notice')->find($id)) {
                    if ($this->checkVisibility($notice, 'notice')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('external_recommend_notice')->getRecommend($user, $notice)) {
                                $recommend = $this->createObject('external_recommend_notice')
                                    ->setUser($user)
                                    ->setNotice($notice)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addRecommendNotice($recommend);
                                $notice->addRecommendby($recommend);

                                $this->persist($recommend, $notice, $user)->flush();
                                $this->get('rpe.notifications')->wait(Notification::TYPE_RECOMMEND, $user, $notice);

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $recommend = $this->getRepository('external_recommend_notice')->getRecommend($user, $notice)) {
                                $user->removeRecommendNotice($recommend);
                                $notice->removeRecommendby($recommend);
                                $this->remove($recommend);

                                $this->persist($notice, $user)->flush();

                                $return = true;
                            }
                        }

                        $this->get('rpe.search.index.factory')->update($notice);

                        if ($return) {
                            return $this->render('pum://page/ajax/action/like-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $recommend,
                                'object'    => $notice,
                                'type'      => 'external_notice'
                            ));
                        }
                    }
                }
            }

        }

        return new Response('ERROR');
    }

    /**
     * recommend a notice
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  string  mode         Action mode
     * @param  string  $id          The comment id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/recommend-action/comment/{mode}/{id}", name="recommend_comment", defaults={"_project"="rpe", "id": null})
     */
    public function recommendCommentAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $comment = $this->getRepository('comment')->find($id)) {
                    if ($this->checkVisibility($comment, 'comment')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('recommend_comment')->getRecommend($user, $comment)) {
                                $recommend = $this->createObject('recommend_comment')
                                    ->setUser($user)
                                    ->setComment($comment)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addRecommendComment($recommend);
                                $comment->addRecommendby($recommend);

                                $this->persist($recommend, $comment, $user)->flush();

                                $this->get('rpe.notifications')->wait(Notification::TYPE_RECOMMEND, $user, $comment);
                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $recommend = $this->getRepository('recommend_comment')->getRecommend($user, $comment)) {
                                $user->removeRecommendComment($recommend);
                                $comment->removeRecommendby($recommend);
                                $this->remove($recommend);

                                $this->persist($comment, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/like-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $recommend,
                                'object'    => $comment,
                                'type'      => 'comment'
                            ));
                        }
                    }
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * recommend a notice comment
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  string  mode         Action mode
     * @param  string  $id          The comment id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/recommend-action/comment-notice/{mode}/{id}", name="recommend_notice_comment", defaults={"_project"="rpe", "id": null})
     */
    public function recommendNoticeCommentAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $comment = $this->getRepository('external_notice_comment')->find($id)) {
                    if ($this->checkVisibility($comment, 'external_notice_comment')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('external_recommend_comment')->getRecommend($user, $comment)) {
                                $recommend = $this->createObject('external_recommend_comment')
                                    ->setUser($user)
                                    ->setComment($comment)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addNoticeRecommendComment($recommend);
                                $comment->addRecommendby($recommend);

                                $this->persist($recommend, $comment, $user)->flush();

                                $this->get('rpe.notifications')->wait(Notification::TYPE_RECOMMEND, $user, $comment);
                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $recommend = $this->getRepository('external_recommend_comment')->getRecommend($user, $comment)) {
                                $user->removeNoticeRecommendComment($recommend);
                                $comment->removeRecommendby($recommend);
                                $this->remove($recommend);

                                $this->persist($comment, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/like-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $recommend,
                                'object'    => $comment,
                                'type'      => 'comment'
                            ));
                        }
                    }
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * Recommand a user 
     * 
     * @access public
     * @param  Request $request     A request instance
     * @param  string  mode         Action mode
     * @param  string  $id          The comment id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/recommend-action/user/{mode}/{id}", name="recommend_user", defaults={"_project"="rpe", "id": null})
     */
    public function recommendUserAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $recommended = $this->getRepository('user')->find($id)) {
                    if ($this->checkVisibility($recommended, 'user')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('recommend_user')->getRecommend($user, $recommended)) {
                                $recommend = $this->createObject('recommend_user')
                                    ->setUser($user)
                                    ->setRecommended($recommended)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addRecommendUser($recommend);
                                $recommended->addRecommendby($recommend);

                                $this->persist($recommend, $recommended, $user)->flush();

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $recommend = $this->getRepository('recommend_user')->getRecommend($user, $recommended)) {
                                $user->removeRecommendUser($recommend);
                                $recommended->removeRecommendby($recommend);
                                $this->remove($recommend);

                                $this->persist($recommended, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/like-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $recommend,
                                'object'    => $user,
                                'type'      => 'user'
                            ));
                        }
                    }
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          The post id
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/ajax/recommend/users/{id}", name="recommend_users", defaults={"_project"="rpe", "id"})
     */
    public function recommendUsersAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            $recommends = $this->getRepository('recommend_post')->getRecommendsByRelation($this->getUser(), $id);
            
            return $this->render('pum://includes/common/componants/likes/users.html.twig', array(
                'recommends' => $recommends
            ));
        }

        return new Response('ERROR');
    }

    /**
     * @access private
     * @param  Object $item Item to check
     * @param  string $type Item type
     * 
     * @return boolean
     */
    private function checkVisibility($item, $type)
    {
        return true;
    }
}
