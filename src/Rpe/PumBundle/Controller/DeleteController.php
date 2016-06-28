<?php

namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Comment;
use Rpe\PumBundle\Model\Social\Question;
use Rpe\PumBundle\Model\Social\Answer;
use Rpe\PumBundle\Model\Rpe\Folder;
use Rpe\PumBundle\Model\Rpe\Media;
use Rpe\PumBundle\Model\External\Notice;
use Rpe\PumBundle\Model\External\Comment as NoticeComment;
use Doctrine\ORM\PersistentCollection;
use Rpe\PumBundle\Model\Social\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Rpe\PumBundle\Model\Social\Blog;

/**
 * Delete controller.
 * All action methods to handle object deletion (group, blog, publication, etc.)
 *
 * @method Response deleteGroupAction(Request $request, $id)
 * @method Response deleteBlogAction(Request $request, $id)
 * @method Response deleteNoticeAction(Request $request, $id)
 * @method Response deletePostAction(Request $request, $id)
 * @method Response deleteCommentAction(Request $request, $id)
 * @method Response deleteNoticeCommentAction(Request $request, $id)
 * @method Response deleteQuestionAction(Request $request, $id)
 * @method Response deleteAnswerAction(Request $request, $id)
 * @method Response deleteFolderAction(Request $request, $id)
 * @method Response deleteEventAction(Request $request, $id)
 * @method Response deleteMediaAction(Request $request, $id)
 * @method Response deleteNotificationAction($id)
 * @method Response deleteProduct(Request $request, $id)
 * @method void removeGroup(Group $group)
 * @method void removeBlog(Blog $blog)
 * @method void removePost(Post $post)
 * @method void removeNotice(Notice $notice)
 * @method void removeComment(Comment $comment)
 * @method void removeNoticeComment( NoticeComment $comment)
 * @method void removeAnswer(Answer $answer)
 * @method void removeQuestion(Question $question)
 * @method void removeFolder(Folder $folder)
 * @method void removeMedia(Media $media)
 * @method void removeProduct(Product $product)
 * @method void removeCollection(PersistentCollection $collection)
 */
class DeleteController extends Controller
{
    /**
     * Delete a group and display confirmation message.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of group to delete
     *
     * @uses DeleteController:removeGroup() to delete group from database
     *
     * @return Response A Response instance
     *
     * @Route(path="/group-action/delete/{id}", name="delete_group", defaults={"_project"="rpe"})
     */
    public function deleteGroupAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null !== $group = $this->getRepository('group')->find($id)) {
            if ($this->getUser() === $group->getOwner()) {
                $groupName = $group->getName();
                $this->removeGroup($group);

                $this->flush();

                $this->get('rpe.search.index.factory')->delete($group);

                $this->addSuccess($this->get('translator')->trans('common.action.delete.group.success', array('%name%' => $groupName), 'rpe'));
                return $this->redirect($this->generateUrl('groups'));
            } else {
                $this->addError($this->get('translator')->trans('common.action.delete.group.error_owner', array(), 'rpe'));
                return $this->redirect($this->generateUrl('group', array('id' => $id)));
            }
        } else {
            $this->addError($this->get('translator')->trans('common.action.delete.group.error_notfound', array(), 'rpe'));
            return $this->redirect($this->generateUrl('groups'));
        }

        $this->addError($this->get('translator')->trans('common.action.delete.group.error_generic', array(), 'rpe'));
        return $this->redirect($this->generateUrl('group', array('id' => $id)));
    }

    /**
     * Delete a blog and display confirmation message
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeBlog() to delete blog from database
     *
     * @return Response A Response instance
     *
     * @Route(path="/blog-action/delete/{id}", name="delete_blog", defaults={"_project"="rpe"})
     */
    public function deleteBlogAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null !== $blog = $this->getRepository('blog')->find($id)) {
            $user = $this->getUser();
            if ($user === $blog->getOwner()) {
                $blogName = $blog->getName();
                $this->removeBlog($blog);

                $this->flush();

                $this->get('rpe.search.index.factory')->delete($blog);
                $this->setUserMeta($user, User::META_HAS_BLOG, 0);

                $this->addSuccess($this->get('translator')->trans('common.action.delete.blog.success', array('%name%' => $blogName), 'rpe'));
                return $this->redirect($this->generateUrl('profil', array('id' => $this->get('rpe.hashid')->encode($user->getId()))));
            } else {
                $this->addError($this->get('translator')->trans('common.action.delete.blog.error_owner', array(), 'rpe'));
                return $this->redirect($this->generateUrl('blog', array('id' => $id)));
            }
        } else {
            $this->addError($this->get('translator')->trans('common.action.delete.blog.error_notfound', array(), 'rpe'));
            return $this->redirect($this->generateUrl('home'));
        }

        $this->addError($this->get('translator')->trans('common.action.delete.blog.error_generic', array(), 'rpe'));
        return $this->redirect($this->generateUrl('blog', array('id' => $id)));
    }

    /**
     * Delete a publication and display confirmation message.
     * Unassign author & co-author and re-assign publication to superadmin, and mark it as deleted.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removePost() to unassign author/co-author, assign to superadmin and mark as deleted in database
     *
     * @return Response A Response instance
     *
     * @Route(path="/post-action/delete/{id}", name="delete_post", defaults={"_project"="rpe"})
     */
    public function deletePostAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            if (null !== $post = $this->getRepository('post')->find($id)) {
                if ($this->getUser() === $post->getAuthor()
                    || ($this->getUser() === $post->getTargetUser())
                    || $post->isCoAuthor($this->getUser())) {

                    $postName = $post->getName();

                    $this->removePost($post);
                    $this->flush();
                    $this->get('rpe.search.index.factory')->delete($post);

//                     $this->addSuccess($this->get('translator')->trans('common.action.delete.post.success', array('%name%' => $postName), 'rpe'));
                    return new Response('OK');
                } else {
                    $this->addSuccess($this->get('translator')->trans('common.action.delete.post.error_owner', array(), 'rpe'));
                }
            } else {
                $this->addSuccess($this->get('translator')->trans('common.action.delete.post.error_notfound', array(), 'rpe'));
            }
        }

        return new Response('ERROR');
    }

    /**
     * Delete an external publication ("notice") and display confirmation message.
     * Unlike to standard publications, external publication are completely removed
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeNotice() to delete external publication from database
     *
     * @return Response A Response instance
     *
     * @Route(path="/notice-action/delete/{id}", name="delete_notice", defaults={"_project"="rpe"})
     */
    public function deleteNoticeAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            if (null !== $notice = $this->getRepository('external_notice')->find($id)) {
                if ($this->getUser() === $notice->getTargetUser()) {
                    $noticeTitle = $notice->getTitle();

                    $this->removeNotice($notice);
                    $this->flush();
                    $this->get('rpe.search.index.factory')->delete($notice);

                    $this->addSuccess($this->get('translator')->trans('common.action.delete.notice.success', array('%title%' => $noticeTitle), 'rpe'));
                    return new Response('OK');
                } else {
                    $this->addSuccess($this->get('translator')->trans('common.action.delete.notice.error_owner', array(), 'rpe'));
                }
            } else {
                $this->addSuccess($this->get('translator')->trans('common.action.delete.notice.error_notfound', array(), 'rpe'));
            }
        }

        return new Response('ERROR');
    }

    /**
     * Delete a comment and display confirmation message
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeComment() to delete comment from database
     *
     * @return Response A Response instance
     *
     * @Route(path="/comment-action/delete/{id}", name="delete_comment", defaults={"_project"="rpe"})
     */
    public function deleteCommentAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            if (null !== $comment = $this->getRepository('comment')->find($id)) {
                $user = $this->getUser();
                $comment_user = $comment->getUser();
                $target_user = $comment->getPost()->getAuthor();
                if ($user === $comment_user || $user === $target_user) {
                    $this->removeComment($comment);

                    $this->flush();

                    return new Response('OK');
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * Delete an external publication comment and display confirmation message
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeNoticeComment() to delete comment from database
     *
     * @return Response A Response instance
     *
     * @Route(path="/notice-comment-action/delete/{id}", name="delete_notice_comment", defaults={"_project"="rpe"})
     */
    public function deleteNoticeCommentAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            if (null !== $comment = $this->getRepository('external_notice_comment')->find($id)) {
                $user = $this->getUser();
                $comment_user = $comment->getUser();
                if ($user === $comment_user) {
                    $this->removeNoticeComment($comment);

                    $this->flush();

                    return new Response('OK');
                }
            }
        }

        return new Response('ERROR');
    }
    
    /**
     * Remove favorite discussion added by user
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $discussion_id
     *
     * @return Response A Response instance
     *
     * @Route(path="/favorite-discussion/delete/{discussion_id}", name="delete_favorite_discussion", defaults={"_project"="rpe"})
     */
    public function removeFavoriteDiscussionAction(Request $request, $discussion_id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user = $this->getUser();
        $discussion = $this->getRepository('post')->find($discussion_id);
        $saved_discussion = $this->getRepository('social_favorite_discussion')->getFavorite($user, $discussion);
        
        if ($discussion === null || $saved_discussion === null) {
            $this->addError($this->get('translator')->trans('messages.favorite.error.delete', array(), 'rpe'));
            return $this->redirect($this->generateUrl('home'));
        }
        
        $user->removeFavoriteDiscussion($saved_discussion);
        $discussion->removeFavoriteDiscussion($saved_discussion);
        $this->persist($user, $discussion);
        $this->remove();
        $this->flush();
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('pum://page/user/ajax-user_fdiscussion_button.html.twig', array(
                'post'          => $discussion,
                'user'          => $user
            ));
        } else {
            return $this->redirect($this->generateUrl('publication', array('id' => $discussion->getId())));
        }
        
    }
    

    /**
     * Delete a question and display confirmation message
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeQuestion() to delete question from database
     *
     * @return Response A Response instance
     *
     * @Route(path="/question-action/delete/{id}", name="delete_question", defaults={"_project"="rpe"})
     */
    public function deleteQuestionAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null !== $question = $this->getRepository('question')->find($id)) {
            if ($this->getUser() === $question->getAuthor()) {
                $questionName = $question->getName();
                $this->removeQuestion($question);

                $this->flush();

                $this->get('rpe.search.index.factory')->delete($question);

                $this->addSuccess($this->get('translator')->trans('common.action.delete.question.success', array('%name%' => $questionName), 'rpe'));
                return $this->redirect($this->generateUrl('questions'));
            } else {
                $this->addError($this->get('translator')->trans('common.action.delete.question.error_owner', array(), 'rpe'));
                return $this->redirect($this->generateUrl('question', array('id' => $id)));
            }
        } else {
            $this->addError($this->get('translator')->trans('common.action.delete.question.error_notfound', array(), 'rpe'));
            return $this->redirect($this->generateUrl('questions'));
        }

        $this->addError($this->get('translator')->trans('common.action.delete.question.error_generic', array(), 'rpe'));
        return $this->redirect($this->generateUrl('questions', array('id' => $id)));
    }

    /**
     * Delete an answer and display confirmation message
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeAnswer() to delete answer from database
     *
     * @return Response A Response instance
     *
     * @Route(path="/answer-action/delete/{id}", name="delete_answer", defaults={"_project"="rpe"})
     */
    public function deleteAnswerAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            if (null !== $answer = $this->getRepository('answer')->find($id)) {
                $user = $this->getUser();
                $answer_user = $answer->getAuthor();
                $question_user = $answer->getQuestion()->getAuthor();
                if ($user === $answer_user || $user === $question_user) {
                    $this->removeAnswer($answer);

                    $this->flush();

                    return new Response('OK');
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * Delete a media folder and display confirmation message
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeFolder() to delete folder from database
     *
     * @return Response A Response instance
     * @Route(path="/folder-action/delete/{id}", name="delete_folder", defaults={"_project"="rpe"})
     */
    public function deleteFolderAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            if (null !== $folder = $this->getRepository('folder')->find($id)) {
                $user = $this->getUser();
                $folder_user = $folder->getUser();
                if ($user === $folder_user) {
                    $this->removeFolder($folder);

                    $this->flush();

                    return new Response('OK');
                }
            }
        }

        return new Response('ERROR');
    }

    /**
     * Delete a group theme
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeFolder() to delete folder from database
     *
     * @return Response A Response instance
     * @Route(path="/theme-action/delete/{id}", name="delete_theme", defaults={"_project"="rpe"})
     */
    public function deleteThemeAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
    
        if ($request->isXmlHttpRequest()) {
            if (null !== $theme = $this->getRepository('social_group_theme')->find($id)) {
                $group = $theme->getGroup();
                if ($group) {
                    $group->removeTheme($theme);
                    $this->remove($theme);
                    $this->flush();
                    return new Response('OK');
                }
            }
        }
    
        return new Response('ERROR');
    }
    
    /**
     * Delete an event and display confirmation message
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @return Response A Response instance
     *
     * @Route(path="/event-action/delete/{id}", name="delete_event", defaults={"_project"="rpe"})
     */
    public function deleteEventAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if (null !== $event = $this->getRepository('event')->find($id)) {
            $user = $this->getUser();
            $event_user = $event->getOwnerUser();
            if ($user === $event_user) {
                $this->remove($event);
                $this->flush();
            }
        }

        return $this->redirect($this->generateUrl('agenda'));
    }

    /**
     * Delete a media and display confirmation message
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeMedia() to delete media from database
     *
     * @return Response A Response instance
     *
     * @Route(path="/media-action/delete/{id}", name="delete_media", defaults={"_project"="rpe"})
     */
    public function deleteMediaAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {
            if (null !== $media = $this->getRepository('media')->find($id)) {
                $user = $this->getUser();
                $media_user = $media->getUser();

                if ($user === $media_user) {
                    $this->removeMedia($media);

                    $this->flush();

                    $from_upload = $request->query->get('fromupload', null);
                    if ($from_upload != null && $from_upload) {
                        $name = $media->getMedia()->getName();
                        $response = new JsonResponse();
                        $response->setData(array('files' => array($name => true)));
                        return  $response;
                    }
                    return new Response('OK');
                }
            }
        }
        return new Response('ERROR');
    }

    /**
     * Delete a notification.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @return Response A Response instance
     *
     * @Route(path="/notification-action/delete/{id}", name="delete_notification", defaults={"_project"="rpe"})
     */
    public function deleteNotificationAction($id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $me = $this->getUser();

        $notification = $this->getRepository('notification')->find($id);

        if (null === $notification) {
            $this->throwNotFound('error.notification.not_found');
        }

        if ($me === $notification->getUser()) {
            $this->remove($notification);
            $this->flush();

            return new Response('OK');
        }

        return new Response('ERROR');
    }
    
    /**
     * Delete a saved search
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @return Response A Response instance
     * @Route(path="/save-search/delete/{id}", name="delete_search", defaults={"_project"="rpe"})
     */
    public function deleteSearchAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
    
        $user = $this->getUser();
        if ($request->isXmlHttpRequest()) {
            if (null !== $search = $this->getRepository('social_search')->find($id)) {
                if ($user === $search->getUser()) {
                    $user->removeSavedSearch($search);
                    $this->remove($search);
                    $this->flush();
                    return new Response('OK');
                }
            }
        }
        return new Response('ERROR');
    }

    /**
     * Delete a product.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id
     *
     * @uses DeleteController:removeProduct() to delete product from database
     *
     * @return Response A Response instance
     *
     * @Route(path="/product-action/delete/{id}", name="delete_product", defaults={"_project"="rpe"})
     */
    public function deleteProduct(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($request->isXmlHttpRequest()) {

            if (null !== $product = $this->getRepository('product')->find($id)) {
                    $this->removeProduct($product);
                    $this->flush();
                    return new Response('OK');
            }
        }
        return new Response('ERROR');
    }

    /**
     * @access private
     * @param  Group   $group
     *
     * @return Response A Response instance
     *
     */
    private function removeGroup(Group $group)
    {
        /*
            bookmarkby
            interests
            posts
            users
            instructed_disciplines
            medias
            teaching_levels
        */
        $this->removeCollection($group->getBookmarkby());
//         $this->removeCollection($group->getInterests());
        $this->removeCollection($group->getUsers());
//         $this->removeCollection($group->getInstructedDisciplines());
        $this->removeCollection($group->getMedias());
//         $this->removeCollection($group->getTeachingLevels());
        foreach ($group->getSimplePosts() as $post) {
            $this->removePost($post);
        }

        $this->remove($group);
    }

    /**
     * @access private
     * @param  Blog    $blog
     *
     * @return void
     */
    private function removeBlog(Blog $blog)
    {
        $this->removeCollection($blog->getBookmarkby());
        $this->removeCollection($blog->getUsers());
        foreach ($blog->getPosts() as $post) {
            $this->removePost($post);
        }

        $this->remove($blog);
    }

    /**
     * @access private
     * @param  Post    $post
     *
     * @return void
     */
    private function removePost(Post $post)
    {
        /*
            bookmarkby
            co_authors
            comments
            linkby
            linked_postsk
            recommendby
            disciplines
            medias
            teaching_levels
        */

        if (true == $post->getResource()) {
            if (false === $new_author = $post->getCoAuthors()->first()) {
                $new_author = $this->getViaEducAdmin();
            }

            $post->setAuthor($new_author);
            if ($post->getCoAuthors()->contains($new_author)) {
                $post->removeCoAuthor($new_author);
            }
            $post->setTargetUser($new_author);
            $post->setStatus(Post::STATUS_DELETED);

            $this->persist($post);
        } else {
            $this->removeCollection($post->getBookmarkby());
//             $this->removeCollection($post->getCoAuthors());
            $this->removeCollection($post->getLinkby());
            $this->removeCollection($post->getLinkedPosts());
            $this->removeCollection($post->getRecommendby());
//             $this->removeCollection($post->getDisciplines());
            $this->removeCollection($post->getMedias());
//             $this->removeCollection($post->getTeachingLevels());
            foreach ($post->getComments() as $child) {
                $this->removeComment($child);
            }

            $this->remove($post);
        }
    }

    /**
     * @access private
     * @param  Notice  $notice
     *
     * @return void
     */
    private function removeNotice(Notice $notice)
    {
        $this->removeCollection($notice->getRecommendby());
//         $this->removeCollection($notice->getDisciplines());
//         $this->removeCollection($notice->getLevels());
        foreach ($notice->getComments() as $child) {
            $this->removeComment($child);
        }

        $this->remove($notice);
    }

    /**
     * @access private
     * @param  Comment $comment
     *
     * @return void
     */
    private function removeComment(Comment $comment)
    {
        /*
            children
            recommendby
        */

        foreach ($comment->getChildren() as $child) {
            $this->removeComment($child);
        }
        $this->removeCollection($comment->getRecommendby());

        $this->remove($comment);
    }

    /**
     * @access private
     * @param  NoticeComment  $comment
     *
     * @return void
     */
    private function removeNoticeComment(NoticeComment $comment)
    {
        /*
            children
            recommendby
        */

        foreach ($comment->getChildren() as $child) {
            $this->removeNoticeComment($child);
        }
        $this->removeCollection($comment->getRecommendby());

        $this->remove($comment);
    }

    /**
     * @access private
     * @param  Question  $question
     *
     * @return void
     */
    private function removeQuestion(Question $question)
    {
        /*
            answers
            bookmarkby
            instructed_disciplines
            viewed
        */

        $this->removeCollection($question->getBookmarkby());
//         $this->removeCollection($question->getInstructedDisciplines());
        if (null !== $question->getViewed()) {
            $this->remove($question->getViewed());
        }
        foreach ($question->getAnswers() as $child) {
            $this->removeAnswer($child);
        }

        $this->remove($question);
    }

    /**
     * @access private
     * @param  Answer  $answer
     *
     * @return void
     */
    private function removeAnswer(Answer $answer)
    {
        /*
            children
            recommendby
        */

        foreach ($answer->getChildren() as $child) {
            $this->removeAnswer($child);
        }
        $this->removeCollection($answer->getRecommendby());

        $this->remove($answer);
    }

    /**
     * @access private
     * @param  Request   $request     A request instance
     * @param  Folder  $folder
     *
     * @return void
     */
    private function removeFolder(Folder $folder)
    {
            $this->remove($folder);
    }

    /**
     * @access private
     * @param  Media     $media
     *
     * @return void
     */
    private function removeMedia(Media $media)
    {
        if (null !== $user = $media->getUser()) {
            $disk_quota = $user->getDiskQuota();

            $pum_media = $media->getMedia();
            $storage = $this->container->get('type_extra.media.storage.driver');
            $file =  $storage->getUploadFolder() . $pum_media->getId();
            if (file_exists($file)) {
                $quota = $disk_quota - (int)filesize($file);
                $this->setUserMeta($user, User::META_MEDIA_DISK_QUOTA, $quota);
            }
        }
        $this->remove($media);
    }

    /**
     * @access private
     * @param  Product   $product
     *
     * @return void
     */
    private function removeProduct(Product $product)
    {
        $this->remove($product);
    }

    /**
     * @access private
     * @param  PersistentCollection   $collection
     *
     * @return void
     */
    private function removeCollection(PersistentCollection $collection)
    {
        foreach ($collection as $single) {
            $this->remove($single);
        }
    }
}
