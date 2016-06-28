<?php

namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\Comment;
use Rpe\PumBundle\Model\Social\Notification;
use Rpe\PumBundle\Model\Social\Log;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\External\Comment as NoticeComment;
use Exercise\HTMLPurifier;

/**
 * Comment controller.
 * Action methods to handle comments on publications.
 *
 * @method Response createCommentAction(Request $request)
 * @method Response addCommentAction(Request $request, $form, $comment)
 * @method Response createCommentRawAction(Request $request)
 * @method Response createCommentForm(Request $request)
 *
 */
class CommentController extends Controller
{
    /**
     * @deprecated
     * @access public
     * @param  Request $request     A request instance
     * @return Response A Response instance
     *
     * @Route(path="/createform/type/comment_old", name="create_form_comment_old", defaults={"_project"="rpe"})
     */
    public function createCommentAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        list($form, $comment) = $this->createCommentForm($request);

        if ($response = $this->addCommentAction($request, $form, $comment)) {
            return $response;
        }

        return $this->render('pum://includes/common/componants/comments/comment-form.html.twig', array(
            'form'  => $form->createView()
        ));
    }


    /**
     * @deprecated
     * @access private
     * @param Request   $request      A request instance
     * @param Form      $form         Instance of form
     * @param Comment   $comment      Instance of comment
     *
     * @return Response|boolean       A Response instance
     */
    private function addCommentAction(Request $request, $form, $comment)
    {
        $user = $this->getuser();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $post_id    = $form->get('post_id')->getData();
            $comment_id = $form->get('parent_comment_id')->getData();

            if (null !== $post = $this->getRepository('post')->find($post_id)) {
                $canComment = true;
                // check the first level group first
                $group = $post->getPublishedGroup();

                if (null !== $group && (Group::ACCESS_PUBLIC !== $group->getAccessType())) {
                    $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group);
                    if (null === $user_in_group || !$user_in_group->isUserInGroup()) {
                        $canComment = false;
                    }
                }

                $parent_group = $group ? $group->getParent() : null;
                if ($canComment == false || $parent_group) {
                    // check parent group if exists
                    if (null !== $parent_group && (Group::ACCESS_PUBLIC !== $parent_group->getAccessType())) {
                        $user_in_parent_group = $this->getRepository('user_in_group')->getUserInGroup($user, $parent_group);
                        if (null == $user_in_parent_group || !$user_in_parent_group->isUserInGroup()) {
                            $canComment = false;
                        }
                    }
                }

                if ($canComment) {
                    $comment
                        ->setUser($user)
                        ->setPost($post)
                        ->setContent($this->cleanContent($comment->getContent()))
                        ->setStatus(Comment::STATUS_OK)
                        ->setDate(new \DateTime())
                    ;

                    $user->addComment($comment);
                    $post->addComment($comment);

                    if (null !== $comment_id) {
                        $parent_comment = $this->getRepository('comment')->find($comment_id);
                        if (null !== $parent_comment) {
                            $comment->setParent($parent_comment);
                            $parent_comment->addChild($comment);
                            $this->persist($parent_comment);
                        }
                    }

                    $comment = $this->handleLinkPreview($form->get('link_preview_id')->getData(), $comment);
                    $this->persist($user, $post, $comment)->flush();
                    $this->get('rpe.notifications')->wait(Notification::TYPE_COMMENT, $user, $comment);

                    if ($post->getResource()) {
                        $this->get('rpe.logs')->create($user, Log::TYPE_COMMENT_RESOURCE, $user, $post);
                    } else {
                        $this->get('rpe.logs')->create($user, Log::TYPE_COMMENT_PUBLICATION, $user, $post);
                    }

                    return $this->render('pum://includes/common/componants/publications/comment.html.twig', array(
                        'comment' => $comment,
                        'post'    => $post,
                        'user'    => $user
                    ));
                }

                $this->throwAccessDenied('error.comment.comment_denied');
            }
        }

        return false;
    }

    /**
     * @deprecated
     * @access private
     * @param Request   $request      A request instance
     *
     * @return array    An array contains form and comment instance
     */
    private function createCommentForm(Request $request)
    {
        $post_id    = $request->query->get('post', null);
        $comment_id = $request->query->get('comment', null);

        $comment = $this->createObject('comment');

        $form = $this->createNamedForm('comment', 'pum_object', $comment, array(
            'form_view'   => $this->createFormViewByName('comment', 'simple', $update = false),
            'with_submit' => true
        ));

        $form
            ->add('post_id', 'hidden', array('mapped' => false, 'data' => $post_id))
            ->add('parent_comment_id', 'hidden', array('mapped' => false, 'data' => $comment_id))
        ;

        $form = $this->addLinkPreviewToForm($form);

        return array($form, $comment);
    }

    /**
     * Display Comment form and handle its submission.
     *
     * @access public
     * @param Request   $request      A request instance
     *
     * @return Response A Response instance
     *
     * @Route(path="/createform/type/comment", name="create_form_comment", defaults={"_project"="rpe"})
     */
    public function createCommentRawAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return new Response();
        }

        $post_id         = $request->request->get('post_id', null);
        $notice_id       = $request->request->get('notice_id', null);
        $comment_id      = $request->request->get('parent_comment_id', null);
        $link_preview_id = $request->request->get('link_preview_id', null);

        if (!$post_id && !$notice_id) {
            if ($request->query->get('notice')) {
                return $this->render('pum://includes/common/componants/comments/comment-form-raw.html.twig', array(
                    'notice'    => $request->query->get('notice', null),
                    'comment'   => $request->query->get('comment', null)
                ));
            }
            return $this->render('pum://includes/common/componants/comments/comment-form-raw.html.twig', array(
                'post'    => $request->query->get('post', null),
                'comment' => $request->query->get('comment', null)
            ));
        }

        if (!$request->isMethod('POST')) {
            $this->throwAccessDenied('error.comment.only_post_authorized');
        }

        $user    = $this->getUser();

        if ($post_id !== null && null !== $post = $this->getRepository('post')->find($post_id)) {
            $comment = $this->createObject('comment');
            $canComment = true;
            // check the first level group first
            $group = $post->getPublishedGroup();

            if (null !== $group && (Group::ACCESS_PUBLIC !== $group->getAccessType())) {
                $user_in_group = $this->getRepository('user_in_group')->getUserInGroup($user, $group);
                if (null === $user_in_group || !$user_in_group->isUserInGroup()) {
                    $canComment = false;
                }
            }

            $parent_group = $group ? $group->getParent() : null;
            if ($canComment == false || $parent_group) {
                // check parent group if exists
                if (null !== $parent_group && (Group::ACCESS_PUBLIC !== $parent_group->getAccessType())) {
                    $user_in_parent_group = $this->getRepository('user_in_group')->getUserInGroup($user, $parent_group);
                    if (null == $user_in_parent_group || !$user_in_parent_group->isUserInGroup()) {
                        $canComment = false;
                    }
                }
            }

            if ($canComment) {
                $comment
                    ->setUser($user)
                    ->setPost($post)
                    ->setContent($this->cleanContent($request->request->get('content', null)))
                    ->setStatus(Comment::STATUS_OK)
                    ->setDate(new \DateTime())
                ;

                $user->addComment($comment);
                $post->addComment($comment);

                if (null !== $comment_id) {
                    $parent_comment = $this->getRepository('comment')->find($comment_id);
                    if (null !== $parent_comment) {
                        $comment->setParent($parent_comment);
                        $parent_comment->addChild($comment);
                        $this->persist($parent_comment);
                    }
                }

                $comment = $this->handleLinkPreview($link_preview_id, $comment);
                $this->persist($user, $post, $comment)->flush();
                $this->get('rpe.notifications')->wait(Notification::TYPE_COMMENT, $user, $comment);

                if ($post->getResource()) {
                    $this->get('rpe.logs')->create($user, Log::TYPE_COMMENT_RESOURCE, $user, $post);
                } else {
                    $this->get('rpe.logs')->create($user, Log::TYPE_COMMENT_PUBLICATION, $user, $post);
                }

                return $this->render('pum://includes/common/componants/publications/comment.html.twig', array(
                    'comment' => $comment,
                    'post'    => $post,
                    'user'    => $user
                ));
            }

            $this->throwAccessDenied('error.comment.comment_denied');
        }

        if ($notice_id !== null && null !== $notice = $this->getRepository('external_notice')->find($notice_id)) {
            $comment = $this->createObject('external_notice_comment');
            $comment
                ->setUser($user)
                ->setNotice($notice)
                ->setContent($this->cleanContent($request->request->get('content', null)))
                ->setStatus(NoticeComment::STATUS_OK)
                ->setDate(new \DateTime())
            ;

            $user->addNoticeComment($comment);
            $notice->addComment($comment);

            if (null !== $comment_id) {
                $parent_comment = $this->getRepository('external_notice_comment')->find($comment_id);
                if (null !== $parent_comment) {
                    $comment->setParent($parent_comment);
                    $parent_comment->addChild($comment);
                    $this->persist($parent_comment);
                }
            }

            $comment = $this->handleLinkPreview($link_preview_id, $comment);
            $this->persist($user, $notice, $comment)->flush();

            return $this->render('pum://includes/common/componants/notices/comment.html.twig', array(
                'comment' => $comment,
                'notice'  => $notice,
                'user'    => $user
            ));
        }
        $this->throwAccessDenied('error.comment.post_not_found');
    }
}
