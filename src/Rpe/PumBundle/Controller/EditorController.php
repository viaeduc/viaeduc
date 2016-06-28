<?php

namespace Rpe\PumBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Rpe\PumBundle\Model\Social\UserInEditor;
use Rpe\PumBundle\Model\Social\Post;
use Pagerfanta\Pagerfanta;
use Rpe\PumBundle\Model\Social\Product;

/**
 * Controller for editors
 *
 * @method Response editorCreateAction(Request $request)
 * @method Response editorAction(Request $request, $id)
 * @method Response productEditAction(Request $request, $id)
 * @method Response editorEditAction(Request $request, $id)
 * @method Response editorListAction(Request $request, $page)
 * @method Response editorsAction($mode)
 *
 */
class EditorController extends Controller
{
    /** All editors */
    const LISTMODE_ALL      = 'all_editors';
    /** Followed editors */
    const LISTMODE_FOLLOWED = 'followed';

    /**
     * Action to create/edit Editor's page and handle submission.
     *
     * @access public
     * @param  Request $request     A request instance
     *
     * @throws AccessDeniedException if the user doing this is not and Editor
     *
     * @return Response A Response instance
     *
     * @Route(path="/create-editor", name="create-editor", defaults={"_project"="rpe"})
     */
    public function editorCreateAction(Request $request)
    {
        if (null != $check = $this->checkSecurity()) {
            return $check;
        }

        $user = $this->getUser();

        if (false === $user->isEditor()/* && false === $user->isAdmin() && false === $user->isPrivilege()*/) {
            $this->throwAccessDenied('error.editor.only_editor_allowed');
        }

        // user already have page editeur created
        if (null !== $editor = $user->getEditor()) {
            return $this->redirect($this->generateUrl('editor', array('id' => $editor->getId())));
        }

        $editor = $this->createObject('editor');
        $form   = $this->createNamedForm('editor', 'pum_object', $editor, array(
            'attr'          => array('class'    => 'create-form'),
            'form_view'     => $this->createFormViewByName('editor', 'create', $update = false),
            'with_submit'   => false
        ));

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            // avatar & cover
            $avatar = $form->get('picture')->getData();

            $colors = $this->get('tool.avatar')->getPaletteColorFromText($form->get('name')->getData(), false);
            if (!($avatar instanceof  Media)) {
                $editor->setPicture($this->get('tool.avatar')->getMaskedImage('users', $colors));
            }
            $editor->setCover($this->get('tool.avatar')->getMaskedImage('users', $colors, 837, 400, false));

            $user_in_editor = $this->createObject('user_in_editor')
                ->setUser($user)
                ->setStatus(UserInEditor::STATUS_OWNER)
                ->setEditor($editor)
                ->setDate(new \DateTime());

            $editor
                ->setCreateDate(new \DateTime())
                ->setUpdateDate(new \DateTime())
                ->addUser($user_in_editor);

            $user->addEditor($user_in_editor);

            $this->persist($editor, $user, $user_in_editor)->flush();

            $this->setUserMeta($user, $user::META_HAS_EDITOR, 1);

            return $this->redirect($this->generateUrl('editor', array('id' => $editor->getId())));
        }

        return $this->render('pum://page/editor/editor_edit.html.twig', array(
            'form' => $form->createView(),
            'mode' => 'create'
        ));
    }

    /**
     * Display editor's publications and handle short publication submission.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Editor id
     *
     * @return Response A Response instance
     *
     * @Route(path="/editor/{id}", name="editor", defaults={"_project"="rpe", "id"=null})
     */
    public function editorAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();

        if (null === $id) {
            $editor = $user->getEditor();
        } else {
            $editor = $this->getRepository('editor')->find($id);
        }

        if (null === $editor) {
            $this->throwNotFound('error.editor.not_found');
        }
        
        $userHasAccess = true;
        if ($editor->getOwner() !== $user) {
            $userHasAccess = false;
                
            if ($editor->getActive() === false) {
                return $this->redirect($this->generateUrl('home'));
            }
        }

        $user_in_editor = $this->getRepository('user_in_editor')->getUserInEditor($user, $editor);

/* BEGIN post form */
        $post = $this->createObject('post');
        $form  = $this->createNamedForm('post', 'pum_object', $post, array(
            'attr'        => array('class' => 'post-form', 'id' => "simple-post-form"),
            'form_view'   => $this->createFormViewByName('post', 'simple', $update = false),
            'with_submit' => false
        ));
        $form = $this->addLinkPreviewToForm($form);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $post
                ->setAuthor($user)
                ->setCommentStatus(true)
                ->setResource(false)
                ->setBroadcast(false)
                ->setGlobal(false)
                ->setImportant(false)
                ->setType(Post::TYPE_EDITOR)
                ->setStatus(Post::STATUS_PUBLISHED)
                ->setCreateDate(new \DateTime())
                ->setUpdateDate(new \DateTime());

            $editor->addPost($post);
            $user->addPost($post);
            $post->setPublishedEditor($editor);

            $post = $this->handleLinkPreview($form, $post, false);

            $this->persist($post, $editor, $user)->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->render('pum://includes/common/componants/publications/publications.html.twig', array(
                    'user'        => $user,
                    'post'        => $post
                ));
            }
            return $this->redirect($this->generateUrl('editor', array('id' => $editor->getId())));
        }
/* END post form */

/* BEGIN product form */
/* BEGIN cover and picture */
        $form_cover  = null;
        $form_picture = null;
        $form_product = null;
        $product = $this->createObject('product');

        if ($user->isEditorOwner($editor)) {
            $form_product = $this->createNamedForm('product', 'pum_object', $product, array(
                'attr'          => array('class' => 'product-form', 'id' => 'simple-product-form'),
                'form_view'     => $this->createFormViewByName('product', 'create', $update = false),
                'with_submit'   => false
            ));
            $form_product->add('submit_draft', 'submit');
            $form_product->add('submit', 'submit');
            
            if ($request->isMethod('POST') && $form_product->handleRequest($request)->isValid()) {
                $product
                    ->setEditor($editor)
                    ->setCreateDate(new \DateTime())
                    ->setStatus($form_product->get('submit_draft')->isClicked() ? Product::STATUS_DRAFTING : Product::STATUS_PUBLISHED);
                $editor->addProduct($product);
                
                $this->persist($product, $editor)->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->render('pum://includes/common/componants/products/products.html.twig', array(
                        'user'        => $user,
                        'product'     => $product,
                        'editor'      => $editor
                    ));
                }
                return $this->redirect($this->generateUrl('editor', array('id' => $editor->getId(), 'tab' => 'product')));
            }

            $form_cover  = $this->createNamedForm('cover', 'pum_object', $editor, array(
                'form_view'   => $this->createFormViewByName('editor', 'cover', $update = false),
            ));
            $form_cover = $this->addCroppedDataToForm($form_cover);
            if ($request->isMethod('POST') && $form_cover->handleRequest($request)->isValid()) {
                $cover = $form_cover->get('cover')->getData();
                if ($cover instanceof Media) {
                    $coords = $this->getCropCoordsFromForm($form_cover);

                    $editor->setCover($this->get('tool.avatar')->getCroppedImage($cover, $coords));
                    $this->setUserMeta($user, 'editor.cover.coords', json_encode($coords));

                    $this->flush();

                    if (!$request->isXmlHttpRequest()) {
                        return $this->redirect($this->generateUrl('editor', array('id' => $id)));
                    }

                    return new Response();
                }
            }

            $form_picture  = $this->createNamedForm('picture', 'pum_object', $editor, array(
                'form_view'   => $this->createFormViewByName('editor', 'picture', $update = false),
            ));
            $form_picture = $this->addCroppedDataToForm($form_picture);
            if ($request->isMethod('POST') && $form_picture->handleRequest($request)->isValid()) {
                $picture = $form_picture->get('picture')->getData();
                if ($picture instanceof Media) {
                    $coords = $this->getCropCoordsFromForm($form_picture);

                    $editor->setPicture($this->get('tool.avatar')->getCroppedImage($picture, $coords));

                    $this->flush();

                    if (!$request->isXmlHttpRequest()) {
                        return $this->redirect($this->generateUrl('editor', array('id' => $id)));
                    }
                    return new Response();
                }
            }
        }
/* END cover and picture */

/* BEGIN contact form */
        $contact = $this->createObject('contact');
        $form_contact = null;

        $form_contact = $this->createNamedForm('contact', 'pum_object', $contact, array(
            'attr'          => array('class' => 'contact-form', 'id' => 'simple-contact-form'),
            'form_view'     => $this->createFormViewByName('contact', 'create', $update = false),
            'with_submit'   => false
        ));

        if ($request->isMethod('POST') && $form_contact->handleRequest($request)->isValid()) {
            $contact
                ->setEditor($editor)
                ->setCreateTime(new \DateTime());

            $editor->addContact($contact);

            $this->persist($contact, $editor)->flush();

            // send contact Email
            $result = $this->get('rpe.mailer')->send(array(
                'subject'      => $this->get('translator')->trans('createEditor.contact.subject', array('%subject%' => $contact->getSubject()), 'rpe'),
                'from'         => $contact->getMailFrom(),
                'to'           => $editor->getEmail(),
                'template' => array(
                    'name'     => 'pum://emails/contact_editor.html.twig',
                    'vars'     => array(
                        'contact' => $contact,
                        'user'    => $user
                    )
                ),
                'type'         => 'text/html'
            ));

            return $this->redirect($this->generateUrl('editor', array('id' => $editor->getId())));
        }
/* END contact form */

        return $this->render('pum://page/editor/editor.html.twig', array(
            'editor'            => $editor,
            'form'              => $form->createView(),
            'form_product'      => (null === $form_product) ?  null : $form_product->createView(),
            'form_contact'      => $form_contact->createView(),
            'form_picture'      => (null === $form_picture) ? null : $form_picture->createView(),
            'form_cover'        => (null === $form_cover) ? null : $form_cover->createView(),
            'products'          => $this->getRepository('product')->getProductsInEditor($user, $editor),
            'userHasAccess'     => $userHasAccess,
            'userInEditor'      => $user_in_editor
        ));
    }


    /**
     * Handle Editor's product modifications
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Product id
     *
     * @throws AccessDeniedException if user is not the owner of the page
     *
     * @return Response A Response instance
     *
     * @Route(path="/product/{id}/edit", name="edit-product", defaults={"_project"="rpe"})
     */
    public function productEditAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();
        $product = $this->getRepository('product')->find($id);
        if (null !== $product) {
            $editor = $product->getEditor();
            if (null !== $editor && null !== $user_in_editor = $this->getRepository('user_in_editor')->getUserInEditor($this->getUser(), $editor)) {
                if ($editor->getOwner() == $user) {
                    $form  = $this->createNamedForm('product', 'pum_object', $product, array(
                        'attr'        => array(
                            'class'     => 'product-form',
                            'id'        => 'simple-product-form'),
                        'action'    => $this->generateUrl('edit-product', array('id' => $id)),
                        'form_view'   => $this->createFormViewByName('product', 'edit', $update = false),
                        'with_submit' => false
                    ));

                    if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                        $this->persist($product)->flush();
                        return $this->redirect($this->generateUrl('editor', array('id' => $editor->getId(), 'tab' => 'product')));
                    }
                    return $this->render('pum://page/editor/product_edit.html.twig', array(
                        'form'  => $form->createView(),
                        'editor' => $editor,
                        'product' => $product,
                        'mode'  => 'edit',
                    ));
                }
            }
            $this->throwAccessDenied('error.editor.manage_access_denied');
        }
        $this->throwNotFound('error.editor.product.not_found');
    }

    /**
     * Handle Editor's product status
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Product id
     * @param  string  $statis      Product status
     * 
     * @throws AccessDeniedException if user is not the owner of the page
     *
     * @return Response A Response instance
     *
     * @Route(path="/product/{id}/{status}", name="publish-product", defaults={"_project"="rpe"})
     */
    public function productPublishAction(Request $request, $id, $status)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
    
        $user  = $this->getUser();
        $product = $this->getRepository('product')->find($id);
        if (null !== $product) {
            $editor = $product->getEditor();
            if (null !== $editor && null !== $user_in_editor = $this->getRepository('user_in_editor')->getUserInEditor($this->getUser(), $editor)) {
                if ($editor->getOwner() == $user) {
                    if (in_array($status, array(Product::STATUS_DRAFTING, product::STATUS_PUBLISHED))) {
                        $product->setStatus($status);
                        $this->persist($product)->flush();
                        return $this->redirect($this->generateUrl('editor', array('id' => $editor->getId(), 'tab' => 'product')));
                    }
                }
            }
            $this->throwAccessDenied('error.editor.manage_access_denied');
        }
        $this->throwNotFound('error.editor.product.not_found');
    }    

    /**
     * Handle Editor's page modifications
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Editor id
     *
     * @throws AccessDeniedException if user is not the owner of the page
     *
     * @return Response A Response instance
     *
     * @Route(path="/editor/{id}/edit", name="edit_editor", defaults={"_project"="rpe"})
     */
    public function editorEditAction(Request $request, $id)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        $user  = $this->getUser();
        if (null !== $editor = $this->getRepository('editor')->find($id)) {
            if (null !== $user_in_editor = $this->getRepository('user_in_editor')->getUserInEditor($this->getUser(), $editor)) {
                if ($editor->getOwner() == $user) {

                    $form  = $this->createNamedForm('editor', 'pum_object', $editor, array(
                        'attr'        => array('class' => 'create-form'),
                        'form_view'   => $this->createFormViewByName('editor', 'edit', $update = false),
                        'with_submit' => false
                    ));

                    if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
                        $editor->setActive($form->get('active')->getData());
                        $editor->setUpdateDate(new \DateTime());
                        $this->persist($editor)->flush();
                        return $this->redirect($this->generateUrl('editor', array('id' => $id)));
                    }

                    return $this->render('pum://page/editor/editor_edit.html.twig', array(
                        'form'  => $form->createView(),
                        'editor' => $editor,
                        'mode'  => 'edit',
                    ));
                }
            }

            $this->throwAccessDenied('error.editor.manage_access_denied');
        }

        $this->throwNotFound('error.editor.not_found');
    }

    /**
     * XHR Method to show the list of editors
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $page        Editors page number
     *
     * @return Response A Response instance
     *
     * @Route(path="/editorlist/{page}", name="ajax_editorlist", defaults={"_project"="rpe", "page"="1"})
     */
    public function editorListAction(Request $request, $page)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }
        $user = $this->getUser();
        
        // Page
        $byPage  = 9;

        // Mode
        $mode = $request->query->get('mode', self::LISTMODE_ALL);

        // Get Groups
        switch ($mode) {
            case self::LISTMODE_ALL:
                $editors = $this->getRepository('editor')->getAllEditors($user, true);
                $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($editors, true, false);
                break;

            case self::LISTMODE_FOLLOWED:
                $editors = $this->getRepository('editor')->getFollowedEditors($user, true);
                $pager = new \Pagerfanta\Adapter\DoctrineORMAdapter($editors, true, false);
                break;
        }

        $pager = new Pagerfanta($pager);
        $pager = $pager->setMaxPerPage($byPage);
        $pager = $pager->setCurrentPage($page);

        return $this->render('pum://page/editor/ajax-editorlist.html.twig', array(
            'mode'  => $mode,
            'pager'  => $pager
        ));
    }

    /**
     * Display the page with list of editors.
     *
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $mode        Editors list mode (based on LISTMODE_* constants)
     *
     * @return Response A Response instance
     *
     * @Route(path="/editors/{mode}", name="editors", defaults={"_project"="rpe", "mode"= null})
     */
    public function editorsAction($mode)
    {
        if (null !== $check = $this->checkSecurity()) {
            return $check;
        }

        if ($this->get('pum.vars')->getValue('active_editors') == false) {
            return $this->redirect($this->generateUrl('home'));
        }
        
        $filters = array(
            self::LISTMODE_ALL,
            self::LISTMODE_FOLLOWED
        );

        if (null == $mode) {
            $mode = self::LISTMODE_ALL;
        } elseif (!in_array($mode, $filters)) {
            $this->throwNotFound('error.filters.not_found');
        }

        return $this->render('pum://page/editor/editors.html.twig', array(
            'filters' => $filters,
            'mode'    => $mode
        ));
    }
}
