<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\Log;

/**
 * Bookmark controller.
 * Action methods about bookmarking items (Post, Group, Question, etc.)
 *
 * @method Response bookmarkPostAction(Request $request, $mode, $id)
 * @method Response bookmarkGroupAction(Request $request, $mode, $id)
 * @method Response bookmarkQuestionAction(Request $request, $mode, $id)
 * @method Response bookmarkBlogAction(Request $request, $mode, $id)
 * @method Response bookmarkEditorAction(Request $request, $mode, $id)
 * @method boolean  checkVisibility($item, $type)
 *
 */
class BookmarkController extends Controller
{
    /**
     * Action to bookmark a post/publication.
     * XHR request to handle add or remove bookmark on a post/publicaton
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $mode        Mode
     * @param  string  $id          Id of post
     *
     * @return Response A Response instance
     *
     * @Route(path="/bookmark-action/post/{mode}/{id}", name="bookmark_post", defaults={"_project"="rpe", "id": null})
     */
    public function bookmarkPostAction(Request $request, $mode, $id)
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
                            if (null === $this->getRepository('bookmark_post')->getBookmark($user, $post)) {
                                $bookmark = $this->createObject('bookmark_post')
                                    ->setUser($user)
                                    ->setPost($post)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addBookmarkPost($bookmark);
                                $post->addBookmarkby($bookmark);

                                $this->persist($bookmark, $post, $user)->flush();

                                if ($post->getResource()) {
                                    $this
                                        ->get('rpe.logs')
                                        ->create($user, Log::TYPE_BOOKMARK_RESOURCE, $post->getAuthor(), $post)
                                    ;
                                } else {
                                    $this
                                        ->get('rpe.logs')
                                        ->create($user, Log::TYPE_BOOKMARK_PUBLICATION, $post->getAuthor(), $post)
                                    ;
                                }

                                /*if($post->getAuthor() !== $user) {
                                    if($post->getResource()) {
                                        $this
                                            ->get('rpe.logs')
                                            ->create($user, Log::TYPE_BOOKMARK_SOMEONE_RESOURCE, $post->getAuthor(), $post)
                                        ;
                                    }
                                    else {
                                        $this
                                            ->get('rpe.logs')
                                            ->create($user, Log::TYPE_BOOKMARK_SOMEONE_PUBLICATION, $post->getAuthor(), $post)
                                        ;
                                    }
                                }*/

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $bookmark = $this->getRepository('bookmark_post')->getBookmark($user, $post)) {
                                $user->removeBookmarkPost($bookmark);
                                $post->removeBookmarkby($bookmark);
                                $this->remove($bookmark);

                                $this->persist($post, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/bookmark-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $bookmark,
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
     * Action to bookmark a group.
     * XHR request to handle add or remove bookmark on a group
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $mode        Mode
     * @param  string  $id          Id of group
     *
     * @return Response A Response instance
     *
     * @Route(path="/bookmark-action/group/{mode}/{id}", name="bookmark_group", defaults={"_project"="rpe", "id": null})
     */
    public function bookmarkGroupAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $group = $this->getRepository('group')->find($id)) {
                    if ($this->checkVisibility($group, 'group')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('bookmark_group')->getBookmark($user, $group)) {
                                $bookmark = $this->createObject('bookmark_group')
                                    ->setUser($user)
                                    ->setGroup($group)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addBookmarkGroup($bookmark);
                                $group->addBookmarkby($bookmark);

                                $this->persist($bookmark, $group, $user)->flush();

                                $this
                                    ->get('rpe.logs')
                                    ->create($user, Log::TYPE_BOOKMARK_GROUP, $group->getOwner(), $group)
                                ;

                                /*if($group->getAuthor() !== $user) {
                                    $this
                                        ->get('rpe.logs')
                                        ->create($user, Log::TYPE_BOOKMARK_SOMEONE_GROUP, $group->getAuthor(), $group)
                                    ;
                                }*/

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $bookmark = $this->getRepository('bookmark_group')->getBookmark($user, $group)) {
                                $user->removeBookmarkGroup($bookmark);
                                $group->removeBookmarkby($bookmark);
                                $this->remove($bookmark);

                                $this->persist($group, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/bookmark-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $bookmark,
                                'object'    => $group,
                                'type'      => 'group'
                            ));
                        }
                    }
                }
            }

        }

        return new Response('ERROR');
    }

    /**
     * Action to bookmark a question.
     * XHR request to handle add or remove bookmark on a question
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $mode        Mode
     * @param  string  $id          Id of question
     *
     * @return Response A Response instance
     *
     * @Route(path="/bookmark-action/question/{mode}/{id}", name="bookmark_question", defaults={"_project"="rpe", "id": null})
     */
    public function bookmarkQuestionAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $question = $this->getRepository('question')->find($id)) {
                    if ($this->checkVisibility($question, 'question')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('bookmark_question')->getBookmark($user, $question)) {
                                $bookmark = $this->createObject('bookmark_question')
                                    ->setUser($user)
                                    ->setQuestion($question)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addBookmarkQuestion($bookmark);
                                $question->addBookmarkby($bookmark);

                                $this->persist($bookmark, $question, $user)->flush();

                                $this
                                    ->get('rpe.logs')
                                    ->create($user, Log::TYPE_BOOKMARK_QUESTION, $question->getAuthor(), $question)
                                ;

                                /*if($question->getAuthor() !== $user) {
                                    $this
                                        ->get('rpe.logs')
                                        ->create($user, Log::TYPE_BOOKMARK_SOMEONE_QUESTION, $question->getAuthor(), $question)
                                    ;
                                }*/

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $bookmark = $this->getRepository('bookmark_question')->getBookmark($user, $question)) {
                                $user->removeBookmarkQuestion($bookmark);
                                $question->removeBookmarkby($bookmark);
                                $this->remove($bookmark);

                                $this->persist($question, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/bookmark-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $bookmark,
                                'object'    => $question,
                                'type'      => 'question'
                            ));
                        }
                    }
                }
            }

        }

        return new Response('ERROR');
    }

    /**
     * Action to bookmark a blog.
     * XHR request to handle add or remove bookmark on a blog
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $mode        Mode
     * @param  string  $id          Id of blog
     *
     * @return Response A Response instance
     *
     * @Route(path="/bookmark-action/blog/{mode}/{id}", name="bookmark_blog", defaults={"_project"="rpe", "id": null})
     */
    public function bookmarkBlogAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if ($request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $blog = $this->getRepository('blog')->find($id)) {
                    if ($this->checkVisibility($blog, 'blog')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('bookmark_blog')->getBookmark($user, $blog)) {
                                $bookmark = $this->createObject('bookmark_blog')
                                    ->setUser($user)
                                    ->setBlog($blog)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addBookmarkBlog($bookmark);
                                $blog->addBookmarkby($bookmark);

                                $this->persist($bookmark, $blog, $user)->flush();

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $bookmark = $this->getRepository('bookmark_blog')->getBookmark($user, $blog)) {
                                $user->removeBookmarkBlog($bookmark);
                                $blog->removeBookmarkby($bookmark);
                                $this->remove($bookmark);

                                $this->persist($blog, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/bookmark-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $bookmark,
                                'object'    => $blog,
                                'type'      => 'blog'
                            ));
                        }
                    }
                }
            }

        }

        return new Response('ERROR');
    }

    /**
     * Action to bookmark an editor.
     * XHR request to handle add or remove bookmark on a editor
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $mode        Mode
     * @param  string  $id          Id of editor
     *
     * @return Response A Response instance
     *
     * @Route(path="/bookmark-action/editor/{mode}/{id}", name="bookmark_editor", defaults={"_project"="rpe", "id": null})
     */
    public function bookmarkEditorAction(Request $request, $mode, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $return = false;

        if (1 or $request->isXmlHttpRequest()) {
            if (null !== $id) {
                if (null !== $editor = $this->getRepository('editor')->find($id)) {
                    if ($this->checkVisibility($editor, 'editor')) {
                        $user = $this->getUser();

                        if ($mode == 'add') {
                            if (null === $this->getRepository('bookmark_editor')->getBookmark($user, $editor)) {
                                $bookmark = $this->createObject('bookmark_editor')
                                    ->setUser($user)
                                    ->setEditor($editor)
                                    ->setDate(new \DateTime())
                                ;
                                $user->addBookmarkEditor($bookmark);
                                $editor->addBookmarkby($bookmark);

                                $this->persist($bookmark, $editor, $user)->flush();

                                $return = true;
                            }
                        } elseif ($mode == 'remove') {
                            if (null !== $bookmark = $this->getRepository('bookmark_editor')->getBookmark($user, $editor)) {
                                $user->removeBookmarkEditor($bookmark);
                                $editor->removeBookmarkby($bookmark);
                                $this->remove($bookmark);

                                $this->persist($editor, $user)->flush();

                                $return = true;
                            }
                        }

                        if ($return) {
                            return $this->render('pum://page/ajax/action/bookmark-request.html.twig', array(
                                'id'        => $id,
                                'like'      => $bookmark,
                                'object'    => $editor,
                                'type'      => 'editor'
                            ));
                        }
                    }
                }
            }

        }

        return new Response('ERROR');
    }

    /**
     * @deprecated
     * @access private
     * @param  Object  $item          Object of item
     * @param  string  $type          Id of editor
     *
     * @return boolean Visible or not
     */
    private function checkVisibility($item, $type)
    {
        return true;
    }
}
