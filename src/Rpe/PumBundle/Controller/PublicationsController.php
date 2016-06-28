<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\PostVersion;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Notification;
use Rpe\PumBundle\Model\Social\Log;
use Rpe\PumBundle\Model\Rpe\Media as RpeMedia;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Pagerfanta\Pagerfanta;
use Exercise\HTMLPurifier;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Extension\Belin\BelinApi;
use Rpe\PumBundle\Extension\EtherpadLiteClient\EtherpadLiteClient;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Library controller
 *
 * @method Response publicationsListAction(Request $request, $page)
 * @method Response publicationsAction($mode)
 * @method Response mesLibsAction()
 * @method Response publicationsFavoritesAction()
 * @method Response singlePublicationAction($id)
 * @method Response sharePublicationAction(Request $request, $id)
 * @method Response publishPublicationAction(Request $request, $id)
 * @method Response publishFormPublicationAction(Request $request)
 * @method Response editFormPublicationAction(Request $request, $id, $version)
 * @method array    checkBelinId($user)
 * @method Form     createResourceForm($post, $formType = 'create')
 * @method Response postResource($request, $resourceForm, $post)
 * @method Response editResource($request, $resourceForm, $post, $version, $isLastVersion)
 * @method array    getUserFavoritePublications($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method Response coEditAction(Request $request, $postid)
 * @method Response ajaxRefreshPadContentAction(Request $request, $padID)
 */
class PublicationsController extends Controller
{
    /** List mode : All publications */
    const LISTMODE_ALLPUBLICATIONS      =   'all';
    /** List mode : Publications where user is author or co-author only */
    const LISTMODE_MYPUBLICATIONS       =   'my_publications';
    /** List mode : Publications drafts where user is author or co-author only */
    const LISTMODE_MYDRAFTS             =   'my_drafts';
    /** List mode : Publications from partner Belin */
    const LISTMODE_MYBELINPUBLICATIONS  =   'my_belin';
    /** List mode : Favorites publications of the user */
    const LISTMODE_MYFAVORITES          =   'my_favorites';
    /** List mode : Favorites discussions of the user */
    const LISTMODE_FAVDISCUSSIONS       =   'my_discussions';

    /**
     * XHR Method to show a list of publications.
     * Publications list based on `LISTMODE_*` constants.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $page        The page number
     *
     * @uses  PublicationsController:getUserFavoritePublications() to get favorite publications added by the user
     *
     * @return Response A Response instance
     *
     * @Route(path="/publicationslist/{page}", name="ajax_publicationslist", defaults={"_project"="rpe", "page"="1"})
     */
    public function publicationsListAction(Request $request, $page)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // Page
        $byPage  = 9;

        // Mode
        $mode = $request->query->get('mode', self::LISTMODE_ALLPUBLICATIONS);

        // Get Groups
        if (self::LISTMODE_MYDRAFTS == $mode) {
            $publications = $this->getUser()->getDraftsPosts();
            $pager = new \Pagerfanta\Adapter\DoctrineCollectionAdapter($publications);
        } elseif (self::LISTMODE_MYPUBLICATIONS == $mode) {
            $publications = $this->getRepository('post')->getUserPublications($this->getUser(), true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($publications, true, false);
        } elseif (self::LISTMODE_MYFAVORITES == $mode) {
            $publications = $this->getUserFavoritePublications($this->getUser(), true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($publications, true, false);
        } elseif (self::LISTMODE_FAVDISCUSSIONS == $mode) {
            $publications = $this->getUserFavoriteDiscussions($this->getUser(), true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($publications, true, false);
        } else {
            $publications = $this->getRepository('post')->getAllPublications(true);
            $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($publications, true, false);
        }

        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/publications/ajax-publicationslist.html.twig', array(
            'mode'  => $mode,
            'pager'  => $pager
        ));
    }

    /**
     * Action to show a list of publications with filters.
     * List of publications and filters based on `LISTMODE_*` constants
     *
     * @access public
     * @param  string  $mode        Mode of list (based on `LISTMODE_*` constants)
     *
     * @return Response A Response instance
     *
     * @Route(path="/publications/{mode}", name="publications", defaults={"_project"="rpe", "mode"= null})
     */
    public function publicationsAction($mode)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        if ($user->isInvited()) {
            $publicationFilters = array(
                self::LISTMODE_MYPUBLICATIONS,
                self::LISTMODE_MYDRAFTS,
                self::LISTMODE_MYFAVORITES,
                self::LISTMODE_FAVDISCUSSIONS
            );
            $mode_default = self::LISTMODE_MYPUBLICATIONS;
        } else {
            $publicationFilters = array(
                self::LISTMODE_ALLPUBLICATIONS,
                self::LISTMODE_MYPUBLICATIONS,
                self::LISTMODE_MYDRAFTS,
                self::LISTMODE_MYFAVORITES,
                self::LISTMODE_FAVDISCUSSIONS
            );
            $mode_default = self::LISTMODE_ALLPUBLICATIONS;
        }

        if (null == $mode) {
            $mode = $mode_default;
        } elseif (!in_array($mode, $publicationFilters)) {
            $this->throwNotFound('error.filters.not_found');
        }

        return $this->render('pum://page/publications/publications.html.twig', array(
            'publicationFilters'    => $publicationFilters,
            'mode'                  => $mode
        ));
    }

    /**
     * @access public
     * @return Response A Response instance
     *
     * @Route(path="/publications/libs/mes_libs", name="mes_libs", defaults={"_project"="rpe", "mode"= null})
     */
    public function mesLibsAction()
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user = $this->getUser();

        $pager = array(
                             array( 'id'    => '1',
                                    'file'  => 'http://t1.gstatic.com/images?q=tbn:ANd9GcQgcq9SyoWuiUD96ovKPN9Va1hSZaOuVnHbFSNyFEcBGOorG0mX719C-rvqSw',
                                    'name'  => 'Name'
                                  )
            );
        $mode = self::LISTMODE_MYBELINPUBLICATIONS;

        return $this->render('pum://page/publications/mes_libs.html.twig', array(
            'mode'                  => $mode,
            'pager'                 => $pager,
            'user'                  => $user,
        ));
    }

    /**
     * View publication
     *
     * @access public
     * @param  string $id     The post id
     *
     * @throws NotFoundException if the publication doesn't exists or the user doesn't have right permissions to see it
     *
     * @return Response A Response instance
     *
     * @Route(path="/publication/{id}", name="publication", defaults={"_project"="rpe", "id": null})
     */
    public function singlePublicationAction($id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $postFullAccess = $this->getRepository('post')->find($id);
        // full access for post edited with etherpad
        if (null !== $postFullAccess && $postFullAccess->isCollaborative() && Post::STATUS_DELETED != $postFullAccess->getStatus()) {
            $post = $postFullAccess;
        } else {
            $post = $this->getRepository('post')->getSinglePublication($id, $user);
        }
        $friendsCoAuthors = $this->getRepository('post')->hasFriendsCoAuthors($id, $user);

        if (null !== $postFullAccess) {
            if ($postFullAccess->getResource()) {
                $this->get('rpe.logs')->create($user, Log::TYPE_SEE_RESOURCE, $user, $postFullAccess);
            } else {
                $this->get('rpe.logs')->create($user, Log::TYPE_SEE_PUBLICATION, $user, $postFullAccess);
            }

            /*if($post->getAuthor() !== $user) {
                if($post->getResource()) {
                    $this->get('rpe.logs')->create($user, Log::TYPE_SEE_SOMEONE_RESOURCE, $post->getAuthor(), $post);
                }
                else {
                    $this->get('rpe.logs')->create($user, Log::TYPE_SEE_SOMEONE_PUBLICATION, $post->getAuthor(), $post);
                }
            }*/

            return $this->render('pum://page/publications/ressource.html.twig', array(
                'user'              => $user,
                'post'              => $post,
                'friendsCoAuthors'  => $friendsCoAuthors,
                'storage'           => $this->container->get('type_extra.media.storage.driver')
            ));
        }

        $this->throwNotFound('error.publication.not_found');
    }

    /**
     * Action to share a publication on user wall or group wall
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string $id     The post id
     *
     * @return Response A Response instance
     *
     * @Route(path="/publication/share/{id}", name="publication_share", defaults={"_project"="rpe"})
     */
    public function sharePublicationAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $post = $this->getRepository('post')->find($id);

        $postShare = $this->createObject('post');

        $form  = $this->createNamedForm('share_post', 'pum_object', $postShare, array(
            'attr'        => array('class' => 'share_post-form', 'id' => 'simple-share_post-form', 'data-async' => '', 'data-target' => '#modal-share .modal-content'),
            'form_view'   => $this->createFormViewByName('post', 'share_post', $update = false),
            'with_submit' => false
        ));

        $sent = false;

        if ($request->isMethod('POST')) {
            if ($form->handleRequest($request)->isValid()) {
                $postShare->setAuthor($user);
                $postShare->setCreateDate(new \DateTime());
                $postShare->setUpdateDate(new \DateTime());
                $postShare->setStatus(Post::STATUS_PUBLISHED);
                $postShare->setType(Post::TYPE_WALL);
                $postShare->setTargetUser($user);

                $user->addPost($postShare);

                $sharePost = $this->createObject('share_post');
                $sharePost->setSourcePost($post);
                $sharePost->setTargetPost($postShare);

                $post->addShareby($sharePost);
                $postShare->addSharePost($sharePost);

                $this->persist($sharePost, $user, $postShare, $post);
                $this->flush();

                if ($post->getResource()) {
                    $this->get('rpe.notifications')->wait(Notification::TYPE_SHARE_RESOURCE, $user, $post);
                    $this->get('rpe.logs')->create($user, Log::TYPE_SHARE_RESOURCE, $user, $post);
                } else {
                    $this->get('rpe.notifications')->wait(Notification::TYPE_SHARE_PUBLICATION, $user, $post);
                    $this->get('rpe.logs')->create($user, Log::TYPE_SHARE_PUBLICATION, $user, $post);
                }

                $shareCount = $post->getShareBy()->count();

                if (!$shareCount) {
                    $shareCount = '';
                }

                $sent = true;

                $this->get('rpe.search.index.factory')->update($post);

                // return new Response($shareCount);
            }
        }

        return $this->render('pum://includes/common/header/form/share.html.twig', array(
            'form' => $form->createView(),
            'post' => $post,
            'id'   => $id,
            'sent' => $sent
        ));
    }

    /**
     * Action to add a publication to headlines of a group
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string $id     The post id
     * @param  string $group  The group id
     *
     * @return Response A Response instance
     *
     * @Route(path="/publication/headline/{id}/{group}", name="publication-headline", defaults={"_project"="rpe"})
     */
    public function headlinePostAction(Request $request, $id, $group)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $post = $this->getRepository('post')->find($id);
        $group = $this->getRepository('group')->find($group);

        $result = "";

        if ($post && $group) {
            $headline_post = $this->getRepository('social_headline_post')->getHeadline($post, $group);

            if ($headline_post !== null) {
                $group->removeHeadline($headline_post);
                $post->removeHeadline($headline_post);
                $this->remove($headline_post);
                $this->flush();
                $result = "removed";
            } else {
                $headline_post = $this->createObject('social_headline_post')
                                    ->setGroup($group)
                                    ->setPost($post)
                                    ->setDate(new \DateTime())
                ;
                $post->addHeadline($headline_post);
                $group->addHeadline($headline_post);
                $this->persist($headline_post, $post, $group)->flush();
                $result = "added";
            }
        }

        return $this->render('pum://includes/common/componants/headlines/headlines.html.twig', array(
            'post' => $post,
            'group'   => $group,
            'result' => $result,

        ));
    }
    
    /**
     * Action to add a discussion (list of comments) to favorite of user
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string $id     The post id
     *
     * @return Response A Response instance
     *
     * @Route(path="/discussion/favorite/{id}", name="favorite-discussion", defaults={"_project"="rpe"})
     */
    public function fDiscussionAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();
        $discussion       = $this->getRepository('post')->find($id);
        $saved_discussion = $this->getRepository('social_favorite_discussion')->getFavorite($user, $discussion);
        
        
        if ($discussion === null || isset($saved_discussion)) {
            return false;
        }
        
        $form_favorite_discussion = $this->createNamedForm('rpe_favorite_discussion', 'form')
            ->add('title', 'text')
        ;
        
        if ($request->isMethod('post') && $form_favorite_discussion->handleRequest($request)->isValid()) {
            $favorite = $this->createObject('social_favorite_discussion')
                ->setTitle($form_favorite_discussion->get('title')->getData())
                ->setDate(new \DateTime())
                ->setUser($user)
                ->setDiscussion($discussion);
            $user->addFavoriteDiscussion($favorite);
            $discussion->addFavoriteDiscussion($favorite);
            $this->persist($favorite, $user, $discussion)->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->render('pum://page/user/ajax-user_fdiscussion_button.html.twig', array(
                    'post'          => $discussion,
                    'user'          => $user
                ));
            } else {
                return $this->redirect($this->generateUrl('publication', array('id' => $discussion->getId())));
            }
        }
        
        return $this->render('pum://page/user/ajax-user_fdiscussion.html.twig', array(
            'form_favorite' => $form_favorite_discussion->createView(),
            'post'          => $discussion
        ));
    }

    /**
     * Action to change a publication from "draft" state to "published".
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string $id     The post id
     *
     * @return Response A Response instance
     *
     * @Route(path="/publication/{id}/publish", name="publication_publish", defaults={"_project"="rpe", "id": null})
     */
    public function publishPublicationAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $post = $this->getRepository('post')->getSinglePublication($id, $this->getUser());

        if (null !== $post) {
            $post
                ->setUpdateDate(new \DateTime())
                ->setStatus(Post::STATUS_PUBLISHED)
            ;

            $this->persist($post)->flush();

            return $this->redirect($this->generateUrl('publication', array('id' => $post->getId())));
        }

        $this->throwAccessDenied('error.publication.manage_access_denied');
    }

    /**
     * Action to display the form to create a publication.
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @uses PublicationsController:createResourceForm() to generate publication form
     * @uses PublicationsController:postResource() to handle publication submission
     *
     * @return Response A Response instance
     *
     * @Route(path="/create/publication", name="publish_publications", defaults={"_project"="rpe"})
     */
    public function publishFormPublicationAction(Request $request)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        
        $user = $this->getUser();
        if (false === $user->isEditor()) {
            $where_choices = array(
                Post::TYPE_WALL => 'Sur mon profil'
            );

            if (null !== $user->getBlog()) {
                $where_choices[Post::TYPE_BLOG] = 'Sur mon blog';
            }
        } else {
            $where_choices = array(
                Post::TYPE_EDITOR => 'Sur ma page éditeur'
            );
        }
        if ($user->getAcceptedGroups()->count() > 0) {
            $where_choices[Post::TYPE_GROUP] = 'Dans un groupe';
        }
        if ($user->isInvited()) {
            unset($where_choices[Post::TYPE_WALL]);
        }

        $friends = $this->getRepository('user')->getAcceptedFriends($user, true, true, null, null, "id, firstname, lastname", User::STATUS_TYPE_ACTIVE, true);
        $friends_array = array();
        array_walk($friends, function ($val) use (&$friends_array) {
            $friends_array[$val['id']] = $val['firstname'] . ' ' . $val['lastname'];
        });
        unset($friends);
        
        $post         = $this->createObject('post');
        $resourceForm = $this->createResourceForm($post);
        $resourceForm->add('where', 'choice', array(
            'label'   => 'Publié dans :',
            'mapped'  => false,
            'choices' => $where_choices
        ));

        if ($response = $this->get('pum.form_ajax')->handleForm($resourceForm, $request)) {
            return $response;
        }
        if ($response = $this->postResource($request, $resourceForm, $post)) {
            return $response;
        }
        
        return $this->render('pum://page/publications/form-publish.html.twig', array(
            'publishTypeActive' => 'publications',
            'friends'           => $friends_array,
            'form'              => $resourceForm->createView(),
            'belinParam'        => $this->checkBelinId($this->getUser())
        ));
    }

    /**
     * Action to display the form to edit a publication
     *
     * @access public
     * @param  Request      $request     A request instance
     * @param  string       $id          The post id
     * @param  PostVersion  $version     Post version
     *
     * @uses PublicationsController:createResourceForm() to generate publication form
     * @uses PublicationsController:editResource() to handle form submission
     * @uses PublicationsController:checkBelinId() to check if the user has a Belin partner id associated
     *
     * @return Response A Response instance
     *
     * @Route(path="/publication/{id}/edit/{version}", name="publication_edit", defaults={"_project"="rpe", "id": null, "version": null})
     */
    public function editFormPublicationAction(Request $request, $id, $version)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $postFullAccess = $this->getRepository('post')->find($id);
        // full access for post edited with etherpad
        if ((null !== $postFullAccess) && $postFullAccess->isCollaborative() && Post::STATUS_DELETED != $postFullAccess->getStatus()) {
            $post = $postFullAccess;
        } else {
            $post = $this->getRepository('post')->getSingleEditPublication($id, $this->getUser());
        }

        if (null !== $post) {
            $lastVersion = $post->getLastVersion();
            if (null !== $lastVersion) {
                $lastVersionID = $lastVersion->getId();
            }
            if (null === $version) {
                $version = $lastVersion;
            } else {
                $version = $this->getRepository('post_version')->getPostVersion($version, $id);
            }
            $isLastVersion = false;

            if (null === $version || ($version->getId() === $lastVersionID)) {
                $isLastVersion = true;
            }

            if (null === $version) {
                throw new \RuntimeException('Version of publication is null');
            }

            // Override POST data by POST_VERSION data
            $post->setName($version->getName());
            $post->setContent($version->getContent());
            foreach ($post->getMedias() as $media) {
                $post->removeMedia($media);
            }
            foreach ($version->getMedias() as $media) {
                $post->addMedia($media);
            }

            if (false === $this->getUser()->isEditor()) {
                $where_choices = array(
                    Post::TYPE_WALL  => 'Sur mon profil',
                );

                if (null !== $this->getUser()->getBlog()) {
                    $where_choices[Post::TYPE_BLOG] = 'Sur mon blog';
                }
            } else {
                $where_choices = array(
                    Post::TYPE_EDITOR  => 'Sur ma page éditeur',
                );
            }

            if ($this->getUser()->getAcceptedGroups()->count() > 0) {
                $where_choices[Post::TYPE_GROUP] = 'Dans un groupe';
            }

            $resourceForm = $this->createResourceForm($post, 'edit');
            $resourceForm->add('where', 'choice', array(
                'data' => $post->getType(),
                'label'   => 'Publié dans :',
                'mapped'  => false,
                'choices' => $where_choices
            ));
            if ($padID = $post->getMeta('pad_id')) {
                // post is edited with etherpad
                $padID = $padID->getValue();
                $resourceForm->add('pad_close', 'checkbox', array(
                    'mapped'   => false,
                    'required' => false,
                    'data'     => $padIsClosed = ($postMeta = $post->getMeta('pad_is_closed')) ? (bool)$postMeta->getValue() : false
                ));
            }

            $friends = $this->getRepository('user')->getAcceptedFriends($this->getUser(), true, true, null, null, "id, firstname, lastname", User::STATUS_TYPE_ACTIVE, true);
            $friends_array = array();
            array_walk($friends, function ($val) use (&$friends_array) {
                $friends_array[$val['id']] = $val['firstname'] . ' ' . $val['lastname'];
            });
            unset($friends);
            
            foreach ($post->getCoAuthors() as $friend) {
                $friends_array[$friend->getId()] = $friend->getFullname();
            }

            if ($response = $this->get('pum.form_ajax')->handleForm($resourceForm, $request)) {
                return $response;
            }

            if ($response = $this->editResource($request, $resourceForm, $post, $version, $isLastVersion)) {
                return $response;
            }

            return $this->render('pum://page/publications/form-publish.html.twig', array(
                'publishTypeActive' => 'publications',
                'edit'              => true,
                'post'              => $post,
                'user'              => $this->getUser(),
                'current_version'   => $version,
                'isLastVersion'     => $isLastVersion,
                'form'              => $resourceForm->createView(),
                'friends'           => $friends_array,
                'belinParam'        => $this->checkBelinId($this->getUser()),
                'pad_id'            => $padID,
                'pad_is_closed'     => isset($padIsClosed) ? $padIsClosed : null,
            ));
        }

        $this->throwAccessDenied('error.publication.manage_access_denied');
    }

    /**
     * Action to check if the specified user has a associated Belin ID.
     *
     * @access private
     * @param  User       $user     User object
     *
     * @return array|null Return belin user info or null
     */
    private function checkBelinId($user)
    {
        if ($this->get('pum.vars')->getValue('active_belin')) {
            if ($belin_meta = $user->getMeta(User::META_BELINSSO_USER_ID)) {
                if ($belin_meta->getValue()) {
                    $auth      = $belin_meta->getValue();
                    $api_belin = new BelinApi(array('auth' => $auth), $this->get('pum.vars')->getValue('belin_sso_api'));
                    $result    = $api_belin->call('Serveur/Connexion');
                    $result    = $api_belin->treatResult($result, 'array');
                    $belin_user = isset($result['utilisateur_id']) ? $result['utilisateur_id'] : null;
                }
            }
            if (isset($belin_user) && $belin_user) {
                return array(
                    'id' => $belin_user,
                    'auth' => $auth
                );

            }
        }
        return null;
    }

    /**
     * Create the form used for Publication's creation and edition.
     *
     * @access private
     * @param  Post       $post     Post object
     * @param  string     $formType Type of form
     *
     * @return Form Form for create or edit post
     */
    private function createResourceForm($post, $formType = 'create')
    {
        $resourceForm = $this->createNamedForm('resource', 'pum_object', $post, array(
            'form_view' => $this->createFormViewByName('post', 'create', $update = false)
        ));

        $resourceForm->add('publish_date', 'datetime', array(
            'label'  => 'Date de publication :',
            'widget' => 'single_text',
            'format' => 'dd/MM/yyyy HH:mm',
            'view_timezone' => $this->getUserTimezone()
        ));
        $resourceForm->add('medias', 'collection', array('type' => 'rpe_media', 'allow_add' => true, 'allow_delete' => true));
        $resourceForm->add('library_medias', 'collection', array('mapped' => false, 'allow_add' => true, 'allow_delete' => true));

        //$resourceForm->add('library_medias_file', 'rpe_media', array('mapped' => false));
        //$resourceForm->add('linkedPosts', 'collection', array('type' => 'rpe_linked_posts', 'allow_add' => true, 'allow_delete' => true));

        if ('edit' === $formType) {
            $resourceForm->add('submit_new', 'submit');
        }
            $resourceForm->add('submit_draft', 'submit');

        return $resourceForm;
    }

    /**
     * Handle publication submission (create).
     *
     * @access private
     * @param  Request    $request      A request instance
     * @param  Post       $post         Post object
     * @param  Form       $resourceForm Post form
     *
     * @return Response A Response instance
     */
    private function postResource($request, $resourceForm, $post)
    {
        if ($request->isMethod('POST')) {
            if ($resourceForm->handleRequest($request)->isValid()) {
                $em = $this->getOEM();
                $em->getConnection()->beginTransaction();

                $postDate = new \DateTime();
                $postContent = $post->getContent();
                // Purify HTML Content (remove scripts, onclick, etc.)
                $config = \HTMLPurifier_Config::createDefault();
                $purifier = new \HTMLPurifier($config);
                // $config->set('HTML.TargetBlank', true);
                $config->set('Attr.AllowedFrameTargets', array('_blank'));
                $config->set('HTML.SafeIframe', true);
                $config->set('URI.SafeIframeRegexp', '%^(http:|https:)?//(www.youtube.com/embed/|player.vimeo.com/video/|www.flickr.com/services/oembed|www.hulu.com/embed|www.google.com/maps/embed|www.dailymotion.com/embed|w.soundcloud.com/player|www.slideshare.net|prezi.com|webtv.ac-versailles.fr|scolawebtv.crdp-versailles.fr|www.audio-lingua.eu|www.xmind.net)%');
                $config->set('Cache.SerializerPermissions', 0775);
                $config->set('Cache.SerializerPath', $this->get('kernel')->getCacheDir().'/htmlpurifier');
                $cleanPostContent = $purifier->purify($postContent);

                // User
                $user = $this->getUser();

                // Post
                $post
                    ->setContent($cleanPostContent)
                    ->setAuthor($user)
                    ->setCommentStatus(true)
                    ->setResource(true)
                    ->setBroadcast(false)
                    ->setGlobal(false)
                    ->setImportant(false)
                    ->setCreateDate($postDate)
                    ->setUpdateDate($postDate)
                    ->setStatus($resourceForm->get('submit_draft')->isClicked() ? Post::STATUS_DRAFTING : Post::STATUS_PUBLISHED)
                ;

                // Version
                $postVersion = $this->createObject('post_version');
                $postVersion
                    ->setAuthor($user)
                    ->setStatus($resourceForm->get('submit_draft')->isClicked() ? PostVersion::STATUS_DRAFTING : PostVersion::STATUS_PUBLISHED)
                    ->setName($post->getName())
                    ->setContent($cleanPostContent)
                    ->setCreateDate($postDate)
                    ->setUpdateDate($postDate)
                ;

                // Group
                $group = false;
                $type  = $resourceForm->get('where')->getData();

                switch ($type) {
                    case Post::TYPE_WALL:
                        $post
                            ->setType(Post::TYPE_WALL)
                            ->setTargetUser($user)
                            ->setPublishedGroup(null)
                            ->setPublishedBlog(null)
                            ->setPublishedEditor(null)
                        ;
                        break;

                    case Post::TYPE_EDITOR:
                        if (null !== $editor = $user->getEditor()) {
                            $editor->addPost($post);
                            $post
                                ->setType(Post::TYPE_BLOG)
                                ->setPublishedGroup(null)
                                ->setPublishedBlog(null)
                                ->setPublishedEditor($editor)
                            ;
                        } else {
                            return $this->redirect($this->generateUrl('publish_publications'));
                        }
                        break;

                    case Post::TYPE_BLOG:
                        if (null !== $blog = $user->getBlog()) {
                            $blog->addPost($post);
                            $post
                                ->setType(Post::TYPE_BLOG)
                                ->setPublishedGroup(null)
                                ->setPublishedBlog($blog)
                                ->setPublishedEditor(null)
                            ;
                        } else {
                            return $this->redirect($this->generateUrl('publish_publications'));
                        }
                        break;

                    case Post::TYPE_GROUP:
                        if (null !== $group = $post->getPublishedGroup()) {
                            $post
                                ->setType(Post::TYPE_GROUP)
                                ->setPublishedBlog(null)
                                ->setPublishedEditor(null)
                            ;
                            $group->addPost($post);
                            $this->persist($group);

                            $this->get('rpe.logs')->create($user, Log::TYPE_POST_RESOURCE, $user, $group);

                            break;
                        }
                    // No else

                    default:
                        return $this->redirect($this->generateUrl('publish_publications'));
                }

                // Medias
                foreach ($post->getMedias() as $media) {
                    $media
                        ->setUser($user)
                        ->addPost($post)
                        ->setType(RpeMedia::TYPE_POST)
                        ->setDate($postDate)
                        // ->setDescription($post->getName())
                    ;

                    $user->addMedia($media);
                    $media->addPostVersion($postVersion);
                    // $postVersion->addMedia($media);
                };

                // Medias added from Library
                $libraryMedias = array_unique($resourceForm->get('library_medias')->getData());
                $userLibraryMedias = $user->getMediasFromIDs($libraryMedias);
                foreach ($userLibraryMedias as $media) {
                    $media
                        ->addPost($post)
                        ->addPostVersion($postVersion)
                    ;
                };

                // Add post to User
                $user->addPost($post);
                $postVersion->setPost($post);

                // Coverfile
                $coverfile = $resourceForm->get('file')->getData();
                $color = $this->get('tool.avatar')->getPaletteColorFromText($resourceForm->get('name')->getData(), false);
                if (!($coverfile instanceof Media)) {
                    $post->setFile($this->get('tool.avatar')->getMaskedImage('books', $color));
                }
                // Persist & Flush
                $this->persist($post, $user, $postVersion)->flush();

                // check media quota
                $user_quota = $user->getDiskQuota();
                foreach ($post->getMedias() as $media) {
                    $check_media = $this->checkMediaSize($media, $user_quota);
                    if ($check_media === false) {
                        $em->getConnection()->rollback();
                        return false;
                    } else {
                        $user_quota += $check_media;
                    }
                }
                $this->setUserMeta($user, User::META_MEDIA_DISK_QUOTA, $user_quota);
                $em->getConnection()->commit();

                if (false === $resourceForm->get('submit_draft')->isClicked()) {
                    if ($group) {
                        $this->get('rpe.notifications')->wait(Notification::TYPE_RESOURCE, $user, $post);
                    }
                    $this->get('rpe.notifications')->wait(Notification::TYPE_COAUTHOR, $user, $post);
                }
                return $this->redirect($this->generateUrl('publication', array('id' => $post->getId())));

            } else {
                throw new \RuntimeException('Form is not valid...');
            }

        }

        return false;
    }


    /**
     * Handle publication submission (edit).
     *
     * @access private
     * @param  Request      $request            A request instance
     * @param  Post         $post               Post object
     * @param  PostVersion  $version            Post object
     * @param  Form         $resourceForm       Post form
     * @param  boolean      $isLastVersion      If is the last version
     *
     * @return Response A Response instance
     */
    private function editResource($request, $resourceForm, $post, $version, $isLastVersion)
    {
        $coAuthors = null;
        $sendNotif = true;
        if (count($post->getCoAuthors())) {
            $coAuthors = clone $post->getCoAuthors();
        }
        if ($request->isMethod('POST')) {
            if ($resourceForm->handleRequest($request)->isValid()) {
                $em = $this->getOEM();
                $em->getConnection()->beginTransaction();
                $postDate = new \DateTime();
                $create = true;

                $postContent = $post->getContent();
                // Purify HTML Content (remove scripts, onclick, etc.)
                $config = \HTMLPurifier_Config::createDefault();
                // $config->set('HTML.TargetBlank', true);
                $config->set('Attr.AllowedFrameTargets', array('_blank'));
                $config->set('HTML.SafeIframe', true);
                $config->set('URI.SafeIframeRegexp', '%^(http:|https:)?//(www.youtube.com/embed/|player.vimeo.com/video/|www.flickr.com/services/oembed|www.hulu.com/embed|www.google.com/maps/embed|www.dailymotion.com/embed|w.soundcloud.com/player|www.slideshare.net|prezi.com|webtv.ac-versailles.fr|scolawebtv.crdp-versailles.fr|www.audio-lingua.eu|www.xmind.net)%');
                $config->set('Cache.SerializerPermissions', 0775);
                $config->set('Cache.SerializerPath', $this->get('kernel')->getCacheDir().'/htmlpurifier');
                $purifier = new \HTMLPurifier($config);
                $cleanPostContent = $purifier->purify($postContent);

                // User
                $user = $this->getUser();

                // Post
                $post
                    ->setContent($cleanPostContent)
                    ->setStatus($resourceForm->get('submit_draft')->isClicked() ? PostVersion::STATUS_DRAFTING : PostVersion::STATUS_PUBLISHED);
                // reset coauthor if not author of post
                if ($user != $post->getAuthor()) {
                    foreach ($coAuthors as $coAuthor) {
                        $post->addCoAuthor($coAuthor);
                    }
                }

                // New version or current ( current if last edit done less than 30 min ago)
                $recentUpdate = ($postDate->getTimestamp() - $version->getUpdateDate()->getTimestamp()) <= 1800;
                if ($isLastVersion && $recentUpdate && $version->getAuthor() == $user) {
                    $postVersion = $version;
                    $create = false;
                    $sendNotif = false;
                } else {
                    $postVersion = $this->createObject('post_version');
                }
                $postVersion
                    ->setAuthor($user)
                    ->setStatus($post->getStatus())
                    ->setName($post->getName())
                    ->setContent($cleanPostContent)
                    ->setUpdateDate($postDate)
                    ->setPost($post)
                ;

                if ($create) {
                    $postVersion->setCreateDate($postDate);
                }

                // Group
                $group = false;
                $type  = $resourceForm->get('where')->getData();

                switch ($type) {
                    case Post::TYPE_WALL:
                        $post
                            ->setType(Post::TYPE_WALL)
//                             ->setTargetUser($user)
                            ->setPublishedGroup(null)
                            ->setPublishedBlog(null)
                            ->setPublishedEditor(null)
                        ;
                        break;

                    case Post::TYPE_EDITOR:
                        if (null !== $editor = $user->getEditor()) {
                            $editor->addPost($post);
                            $post
                                ->setType(Post::TYPE_BLOG)
                                ->setPublishedGroup(null)
                                ->setPublishedBlog(null)
                                ->setPublishedEditor($editor)
                            ;
                        } else {
                            return $this->redirect($this->generateUrl('publish_publications'));
                        }
                        break;

                    case Post::TYPE_BLOG:
                        if (null !== $blog = $user->getBlog()) {
                            $blog->addPost($post);
                            $post
                                ->setType(Post::TYPE_BLOG)
                                ->setPublishedGroup(null)
                                ->setPublishedBlog($blog)
                                ->setPublishedEditor(null)
                            ;
                        } else {
                            return $this->redirect($this->generateUrl('publication_edit', array('id' => $post->getId())));
                        }
                        break;

                    case Post::TYPE_GROUP:
                        if (null !== $group = $post->getPublishedGroup()) {
                            $post
                                ->setType(Post::TYPE_GROUP)
                                ->setPublishedBlog(null)
                                ->setPublishedEditor(null)
                            ;
                            $group->addPost($post);
                            $this->persist($group);

                            $this->get('rpe.logs')->create($user, Log::TYPE_POST_RESOURCE, $user, $group);
                        } else {
                            return $this->redirect($this->generateUrl('publication_edit', array('id' => $post->getId())));
                        }
                        break;

                    default:
                        return $this->redirect($this->generateUrl('publication_edit', array('id' => $post->getId())));
                }

                // MEDIAS
                // if (!$create) {
                //     foreach ($postVersion->getMedias() as $media) {
                //         $postVersion->removeMedia($media);
                //     }
                // }

                foreach ($post->getMedias() as $media) {
                    // Post
                    $media
                        ->setUser($user)
                        ->setType(RpeMedia::TYPE_POST)
                        ->setDate($postDate)
                        // ->setDescription($post->getName())
                    ;

                    if (false === $media->getPosts()->contains($post)) {
                        $media->addPost($post);
                        // Version
                    }
                    $media->addPostVersion($postVersion);
                    if (false === $user->getMedias()->contains($media)) {
                        $user->addMedia($media);
                    }
                };

                // Medias added from Library
                $libraryMedias = array_unique($resourceForm->get('library_medias')->getData());
                $userLibraryMedias = $user->getMediasFromIDs($libraryMedias);

                foreach ($userLibraryMedias as $media) {
                    $media
                        ->addPost($post)
                        ->addPostVersion($postVersion)
                    ;
                };

                $post->setUpdateDate($postDate);

                if ($post->isCollaborative()) {
                    // close or re-open pad editing
                    if ($resourceForm->get('pad_close')->getData() or true) {
                        // Send "close" notifications to members of the group
                        $this->get('rpe.notifications')->wait(Notification::TYPE_RESOURCE_PAD_CLOSE, $user, $post);
                    } elseif ($padOldStatus = $post->getMeta('pad_is_closed')) {
                        if ($padOldStatus->getValue()) {
                            // Send "re-open" notifications to members of the group
                            $this->get('rpe.notifications')->wait(Notification::TYPE_RESOURCE_PAD_REOPEN, $user, $post);
                        }
                    }
                    $this->setPostMeta($post, 'pad_is_closed', $resourceForm->get('pad_close')->getData(), 'etherpad');
                }

                // Persis & Flush
                $this->persist($user, $post, $postVersion)->flush();

                // check media quota
                $user_quota = $user->getDiskQuota();
                foreach ($post->getMedias() as $media) {
                    $check_media = $this->checkMediaSize($media, $user_quota);
                    if ($check_media === false) {
                        $em->getConnection()->rollback();
                        return false;
                    } else {
                        $user_quota += $check_media;
                    }
                }
                $this->setUserMeta($user, User::META_MEDIA_DISK_QUOTA, $user_quota);
                $em->getConnection()->commit();

                if (false === $resourceForm->get('submit_draft')->isClicked() && $create && $sendNotif) {
                    $this->get('rpe.notifications')->wait(Notification::TYPE_RESOURCE_EDIT, $user, $post);
                }
                return $this->redirect($this->generateUrl('publication', array('id' => $post->getId())));

            } else {
                throw new \RuntimeException('Form is not valid...');
            }
        }
        return false;
    }

    /**
     * Method to get the favorite discussions of the user.
     *
     * @access private
     * @param User       $user    User object
     * @param boolean    $returnQuery       Whether return query
     * @param int        $maxResults        Maximum result to return
     * @param int        $firstResult       Get firstresult or not
     *
     * @return array An array contains the publciations
     */
    private function getUserFavoriteDiscussions($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $publications = $this->getRepository('post')->createQueryBuilder('post');
        $publications
            ->leftJoin('post.favoriteDiscussions', 'fd', 'WITH', 'fd.user = :user')
            ->andWhere($publications->expr()->isNotNull('fd'))
            ->andWhere($publications->expr()->eq('post.resource', 0))
            ->andWhere($publications->expr()->in('post.status', ':status'))
            ->setParameters(array(
                'user'  => $user,
                'status' => array(Post::STATUS_PUBLISHED, Post::STATUS_DRAFTING, Post::STATUS_ARCHIVED)
            ));
    
        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }
    
        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }
    
        if ($returnQuery) {
            return $publications->getQuery();
        }
        return $publications->getQuery()->getResult();
    }
    
    /**
     * Method to get the favorite publications of the user.
     *
     * @access private
     * @param User       $user    User object
     * @param boolean    $returnQuery       Whether return query
     * @param int        $maxResults        Maximum result to return
     * @param int        $firstResult       Get firstresult or not
     *
     * @return array An array contains the publciations
     */
    private function getUserFavoritePublications($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $bookmark_post = $this->getRepository('bookmark_post')
            ->createQueryBuilder('bmp');
        $bookmark_post
            ->select('p.id')
            ->leftJoin('bmp.post', 'p')
            ->andWhere($bookmark_post->expr()->eq('bmp.user', ':user'));

        $publications = $this->getRepository('post')
            ->createQueryBuilder('post');
        $publications
            ->andWhere($publications->expr()->eq('post.resource', true))
            ->andWhere($publications->expr()->in('post.status', ':status'))
            ->andWhere($publications->expr()->in('post.id', $bookmark_post->getDql()))
            ->setParameters(array(
                'user'  => $user,
                'status' => array(Post::STATUS_PUBLISHED, Post::STATUS_DRAFTING, Post::STATUS_ARCHIVED)
            ));

        if (null !== $maxResults) {
            $publications->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $publications->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $publications->getQuery();
        }

        return $publications->getQuery()->getResult();
    }

    /**
     * Etherpad: create the etherpad instance.
     * Create the user on etherpad (if not exists), the group (if not exists) and session to edit
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $postid      The post id
     *
     * @return Response A Response instance
     *
     * @Route(path="/co-edit/{postid}", name="co_edit", defaults={"_project"="rpe", "postid"=null})
     */
    public function coEditAction(Request $request, $postid)
    {
        // Security : blocks user if not logged
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        // User
        $user = $this->getUser();

        // Create pad
        $padID = null;
        $etherpadBaseUrl = $this->get('rpe.utils')->getParameter('etherpad.base_url');
        $etherpadDomainCookie = $this->get('rpe.utils')->getParameter('etherpad.domain_cookie');
        if (!is_null($postid)) {
            if ((null !== $post = $this->getRepository('post')->find($postid)) && Post::STATUS_DELETED != $post->getStatus()) {
                $group = $post->getPublishedGroup();
                if ($user->checkGroup($group)->isEmpty()) {
                    // user is not member of the group
                    throw new \RuntimeException(sprintf("You must be a member of the group %d to create this resource", $group->getId()));
                }

                $etherpad = $this->get('rpe.etherpad_client');

                // Create group of users who will have access
                $groupPad = $etherpad->createGroupIfNotExistsFor($group->getId());

                // Create user to associate to the group
                $authorPad = $etherpad->createAuthorIfNotExistsFor($user->getId(), $user->getFirstname().' '.$user->getLastname());

                // Create session of user on group
                // todo: beware, the sessionID can contain more than one session ID
                $validUntil = mktime(0, 0, 0, date("m"), date("d")+1, date("y")); // One day in the future
                $sessionID  = $etherpad->createSession($groupPad->groupID, $authorPad->authorID, $validUntil);
                $sessionID  = $sessionID->sessionID;
                setcookie("sessionID", $sessionID, $validUntil, '/', $etherpadDomainCookie);

                // Create post pad for the group
                if (null == $padID = $post->getMeta('pad_id')) {
                    $newPad = $etherpad->createGroupPad($groupPad->groupID, uniqid('_padPost').$postid, "");
                    $padID  = $newPad->padID;

                    $this->setPostMeta($post, 'pad_id', $padID, 'etherpad');
                } else {
                    $padID = $padID->getValue();
                }
            } else {
                // throw new \RuntimeException("Post $postid can't be found...");
            }
        } else {
            $post = $this->createObject('post');
        }


        // Create forms and handle post requests
        $createCoContentForm = $this->createNamedForm('rpe_create_co_content', 'form')
            ->add('title', 'text')
            ->add('group_id', 'hidden')
        ;

        $coContentForm = $this->createNamedForm('rpe_co_content', 'pum_object', $post);

        if ($request->isMethod('POST')) {
            // popin form
            if ($createCoContentForm->handleRequest($request)->isSubmitted()) {
                if ($createCoContentForm->isValid()) {
                    $postDate = new \DateTime();

                    // Post
                    $post
                        ->setName($createCoContentForm->get('title')->getData())
                        ->setAuthor($user)
                        ->setCommentStatus(true)
                        ->setResource(true)
                        ->setBroadcast(false)
                        ->setGlobal(false)
                        ->setImportant(false)
                        ->setCreateDate($postDate)
                        ->setUpdateDate($postDate)
                        ->setStatus(Post::STATUS_DRAFTING)
                        ->setType(Post::TYPE_GROUP)
                        ->setPublishedBlog(null)
                        ->setPublishedEditor(null)
                    ;
                    // Auto Coverfile for this post
                    $color = $this->get('tool.avatar')->getPaletteColorFromText($post->getName(), false);
                    $post->setFile($this->get('tool.avatar')->getMaskedImage('books', $color));

                    // Version
                    $postVersion = $this->createObject('post_version')
                        ->setAuthor($user)
                        ->setStatus(PostVersion::STATUS_DRAFTING)
                        ->setName($post->getName())
                        ->setCreateDate($postDate)
                        ->setUpdateDate($postDate)
                    ;

                    // Group
                    $groupid = $createCoContentForm->get('group_id')->getData();
                    if (null == $group = $this->getRepository('group')->find($groupid)) {
                        throw new \RuntimeException("Group $groupid can't be found");
                    }
                    // check if user is member of the group posted
                    if ($user->checkGroup($group)->isEmpty()) {
                        throw new \RuntimeException(sprintf("You must be a member of the group %d to create this resource", $group->getId()));
                    }

                    // Add post to User, set post version and group
                    $user->addPost($post);
                    $postVersion->setPost($post);
                    $group->addPost($post);
                    $this->get('rpe.logs')->create($user, Log::TYPE_POST_RESOURCE, $user, $group);

                    // Send notifications to members of the group
                    // $this->notifyPadActivation(Notification::TYPE_RESOURCE_PAD_CREATE, $group->getMembers(), $user, $post);
                    $this->get('rpe.notifications')->wait(Notification::TYPE_RESOURCE_PAD_CREATE, $user, $post);

                    // Persist & Flush
                    $this->persist($post, $user, $postVersion, $group)->flush();

                    return $this->redirect($this->generateUrl('co_edit', array('postid' => $post->getId())));

                } else {
                    // todo: change this
                    throw new \RuntimeException('Form is not valid...');
                }
            }
            // pad form
            if ($coContentForm->handleRequest($request)->isSubmitted()) {
                if ($coContentForm->isValid()) {
                    // check if resource is not closed
                    if (null !== $meta = $post->getMeta('pad_is_closed')) {
                        if ($meta->getValue()) {
                            if ($user !== $post->getAuthor()) {
                                throw new \RuntimeException('You cannot access the resource pad as it is closed...');
                            }
                        }
                    }

                    // set post meta data
                    $padRevision   = $this->get('rpe.etherpad_client')->getRevisionsCount($padID);
                    $padUsersCount = $this->get('rpe.etherpad_client')->padUsersCount($padID);

                    $this->setPostMeta($post, 'pad_revision', $padRevision->revisions, 'etherpad');
                    $this->setPostMeta($post, 'pad_user_count', $padUsersCount->padUsersCount, 'etherpad');

                    return $this->redirect($this->generateUrl('publication_edit', array('id' => $post->getId())));

                } else {
                    throw new \RuntimeException('Form pad is not valid...');
                }
            }
        }


        // Render
        return $this->render('pum://page/publications/co-edit_ressource.html.twig', array(
            'etherpad_base_url' => $etherpadBaseUrl,
            'co_content_form'   => $coContentForm->createView(),
            'pad_id'            => $padID,
            'post'              => isset($post) ? $post : null,
        ));
    }

    /**
     * XHR method to get the last updated content from an Etherpad publication.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $padID       The Etherpad id
     *
     * @return Response A Response instance
     *
     * @Route(path="/ajax-refresh-pad-content/{padID}", name="ajax_refresh_pad_content", defaults={"_project"="rpe"})
     */
    public function ajaxRefreshPadContentAction(Request $request, $padID)
    {
        $padContent = $this->get('rpe.etherpad_client')->getHTML($padID);
        $padContent = $padContent->html;
        return $this->render('pum://page/publications/ajax-pad-content.html.twig', array(
            'pad_content'  => $padContent,
        ));
    }
}
