<?php

namespace Rpe\PumBundle\Controller;

use Pum\Bundle\AppBundle\Controller\Controller as AppController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Rpe\PumBundle\Model\Social\User;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Exercise\HTMLPurifier;

/**
 * basic controller
 *
 * @method RedirectResponse|NULL checkSecurity($roles = array())
 * @method User|NULL             getViaEducAdmin()         
 * @method Object                getContext()
 * @method Object                getFormViewFactory()
 * @method Object                getProject()
 * @method Object                getObjectDefinition($objectName)
 * @method Object                getOEM()
 * @method Object                getRepository($objectName)
 * @method string                getClassName($objectName)
 * @method Object                createObject($objectName)
 * @method void                  persist()
 * @method void                  remove()
 * @method void                  flush()
 * @method Formview              createFormView($objectName, $fields)
 * @method Formview              createFormViewByName($objectName, $name)
 * @method string                getConstant($entityName, $constName)
 * @method string                getVar($key, $default = null)
 * @method Form                  createNamedForm($name, $type, $data = null, array $options = array())   
 * @method string                getSenderEmail()
 * @method void                  setUserMeta($user, $metaKey, $metaValue, $metaType = 'default')
 * @method void                  setGroupMeta($group, $metaKey, $metaValue, $metaType = 'default')
 * @method void                  setPostMeta($post, $metaKey, $metaValue, $metaType = 'default')
 * @method Form                  addCroppedDataToForm($form)
 * @method array                 getCropCoordsFromForm($form)
 * @method Form                  addLinkPreviewToForm($form)
 * @method Object                handleLinkPreview($link_preview_id, $object)
 * @method array                 getInfoList($id)
 * @method string|boolean        checkMediaSize($media, $user_quota = null)
 * @method string                autoLinkText($text)
 * @method string                cleanContent($content)
 * @method Object                getUserTimezone()
 * @method Date                  toUserTimezone($object, $dateMethod, $timezoneMethod = 'getTimezone')
 */
class Controller extends AppController
{
    const ADMIN_ID = 1;

    /**
     * @var string $projectName Project name 
     */
    protected $projectName = 'rpe';

    /**
     * @access public public
     * @param array $roles Roles of user security
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|NULL 
     */
    public function checkSecurity($roles = array())
    {
        if (null === $user = $this->getUser()) {
            return $this->redirect($this->generateUrl('login'));
        }

        if ($user->getStatus() === $user::STATUS_TYPE_AWAITING_CONFIRMATION && $user->getType() === $user::TYPE_INVITED) {
            return $this->redirect($this->generateUrl('logout'));
        }

        // no need for now
        /*$roles = (array)$roles;
        array_push($roles, "ROLE_USER");

        foreach ($roles as $role) {
            if (!$this->get('security.context')->isGranted($role)) {
                throw new AccessDeniedException(sprintf('You need these permissions to acces this page : %s', implode(' ,', $roles)));
            }
        }*/

        return null;
    }

    /**
     * @access public public
     * @return User|NULL
     */
    public function getViaEducAdmin()
    {
         return $this->getRepository('user')->find(self::ADMIN_ID);
    }

    /**
     * shortcut to get context
     *
     * @return Object Pum context to return
     * @access public public
     */
    public function getContext()
    {
        return $this->get('pum.context');
    }

    /**
     * shortcut to get formview factory
     *
     * @return Object Form view factory object
     * @access public public
     */
    public function getFormViewFactory()
    {
        return $this->get('formview.factory');
    }

    /**
     * shortcut to get project
     *
     * @return Object Project instance
     * @access public
     */
    public function getProject()
    {
        return $this->getContext()->getProject();
    }

    /**
     * shortcut to get objectDefinition
     * 
     * @param string $objectName    Object name to get
     * @return Object               The object instance to get
     * @access public
     */
    public function getObjectDefinition($objectName)
    {
        return $this->getProject()->getObject($objectName);
    }

    /**
     * shortcut to get object entity manager
     *
     * @return Object Entity manager instance
     * @access public
     */
    public function getOEM()
    {
        return $this->getContext()->getProjectOEM();
    }

    /**
     * shortcut to get repository on pum object
     *
     * @param string $objectName Object name to get
     * @return Object $objectName repository
     * @access public
     */
    public function getRepository($objectName)
    {
        return $this->getOEM()->getRepository($objectName);
    }

    /**
     * shortcut to get object clazs
     *
     * @param string $objectName Object name to get
     * @return string  Classname of the object
     * @access public
     */
    public function getClassName($objectName)
    {
        return $this->get('pum')->getClassName($this->getContext()->getProjectName(), $objectName);
    }

    /**
     * shortcut to create pum object
     *
     * @param string $objectName Object name to get
     * @return Object  Object created
     * @access public
     */
    public function createObject($objectName)
    {
        return $this->getOEM()->createObject($objectName);
    }

    /**
     * Persit object
     * 
     * @return void
     * @access public
     */
    public function persist()
    {
        $objects = func_get_args();
        foreach ($objects as $object) {
            $this->getOEM()->persist($object);
        }

        return $this->getOEM();
    }

    /**
     * remove object
     *
     * @return void
     * @access public
     */
    public function remove()
    {
        $objects = func_get_args();
        foreach ($objects as $object) {
            $this->getOEM()->remove($object);
        }

        return $this->getOEM();
    }

    /**
     * flush operations
     * 
     * @return void
     * @access public
     */
    public function flush()
    {
        return $this->getOEM()->flush();
    }

    /**
     * create dynamic formview
     *
     * @param string $objectName  Object name to get
     * @param array  $fields      Array of fields for form
     *
     * @return FormView
     * @access public
     */
    public function createFormView($objectName, $fields)
    {
        return $this->getFormViewFactory()->create($objectName, $fields);
    }

    /**
     * get dynamic formview
     *
     * @param string $objectName  Object name to get
     * @param string $name        Formview name to get
     *
     * @return FormView
     * @access public
     */
    public function createFormViewByName($objectName, $name)
    {
        return $this->getFormViewFactory()->getFormViewByName($objectName, $name);
    }

    /**
     * get a constant value of an object
     *
     * @param string $entityName        Name of the entity
     * @param string $constName         Name of the constant
     *
     * @return string    Constant value
     * @access public
     */
    public function getConstant($entityName, $constName)
    {
        return constant($this->getOEM()->getObjectFactory()->getClassName($this->projectName, $entityName).'::'.$constName);
    }

    /**
     * get var value
     * 
     * @param string $key      Key of the var
     * @param string $default  Default value
     * @return string
     * @access public
     */
    public function getVar($key, $default = null)
    {
        return $this->get('pum.context')->getProjectVars()->getValue($key, $default);
    }

    /**
     * createNamedForm
     *
     * @param string $name    name of the form
     * @param string $type    Type of the form
     * @param array  $data    Data for form fields 
     * @param array  $options Options for form
     * @return Form
     * @access public
     */
    public function createNamedForm($name, $type, $data = null, array $options = array())
    {
        return $this->get('form.factory')->createNamed($name, $type, $data, $options);
    }

    /**
     * getSenderEmail
     * 
     * @return string email returned
     * @access public
     */
    public function getSenderEmail()
    {
        return 'no-reply@viaeduc.fr';
    }

    /**
     * setUserMeta
     * 
     * @param User      $user
     * @param string    $metaKey
     * @param string    $metaValue
     * @param string    $metaType
     *
     * @return void
     * @access public
     */
    public function setUserMeta($user, $metaKey, $metaValue, $metaType = 'default')
    {
        $user_meta = $user->getMeta($metaKey);

        if (null === $user_meta) {
            $user_meta = $this->createObject('user_meta')
                ->setUser($user)
            ;
            $user->addUserMeta($user_meta);
        }

        $user_meta
            ->setType($metaType)
            ->setMetaKey($metaKey)
            ->setValue($metaValue)
        ;

        $this->persist($user, $user_meta);
        $this->flush();
    }

    /**
     * setGroupMeta
     *
     * @param Group     $group
     * @param string    $metaKey
     * @param string    $metaValue
     * @param string    $metaType
     *
     * @return void
     * @access public
     */
    public function setGroupMeta($group, $metaKey, $metaValue, $metaType = 'default')
    {
        $group_meta = $group->getMeta($metaKey);

        if (null === $group_meta) {
            $group_meta = $this->createObject('group_meta')
                ->setGroup($group)
            ;
            $group->addGroupMeta($group_meta);
        }

        $group_meta
            ->setType($metaType)
            ->setMetaKey($metaKey)
            ->setValue($metaValue)
        ;

        $this->persist($group, $group_meta);
        $this->flush();
    }

    /**
     * setPostMeta
     *
     * @param Post $post
     * @param string    $metaKey
     * @param string    $metaValue
     * @param string    $metaType
     *
     * @return void
     * @access public
     */
    public function setPostMeta($post, $metaKey, $metaValue, $metaType = 'default')
    {
        $post_meta = $post->getMeta($metaKey);

        if (null === $post_meta) {
            $post_meta = $this->createObject('social_post_meta');
            $post->addPostMeta($post_meta);
        }

        $post_meta
            ->setType($metaType)
            ->setMetaKey($metaKey)
            ->setValue($metaValue)
        ;

        $this->persist($post, $post_meta);
        $this->flush();
    }

    /**
     * addCroppedDataToForm
     *
     * @param Form $form
     * @return Form $form
     *
     * @access public
     */
    public function addCroppedDataToForm($form)
    {
        $form
            ->add('x', 'hidden', array('mapped' => false))
            ->add('y', 'hidden', array('mapped' => false))
            ->add('w', 'hidden', array('mapped' => false))
            ->add('h', 'hidden', array('mapped' => false))
        ;

        return $form;
    }

    /**
     * getCropCoordsFromForm
     *
     * @param Form $form
     * @return array
     * @access public
     */
    public function getCropCoordsFromForm($form)
    {
        return array(
            'x' => $form->get('x')->getData(),
            'y' => $form->get('y')->getData(),
            'w' => $form->get('w')->getData(),
            'h' => $form->get('h')->getData()
        );
    }

    /**
     * addLinkPreviewToForm
     *
     * @param Form $form
     * @return Form $form
     * @access public
     */
    public function addLinkPreviewToForm($form)
    {
        $form
            ->add('link_preview_id', 'hidden', array('mapped' => false))
        ;

        return $form;
    }

    /**
     * handleLinkPreview
     *
     * @param string $link_preview_id
     * @param string $object          Name of the object
     * @param boolean $autolink       Auto link hypertext
     * @return Object
     * @access public
     */
    public function handleLinkPreview($link_preview_id, $object, $autolink = true)
    {
        if (is_object($link_preview_id)) {
            $link_preview_id = $link_preview_id->get('link_preview_id')->getData();
        }

        if (null !== $link_preview_id) {
            if (null !== $link_preview = $this->getRepository('link_preview')->find($link_preview_id)) {
                switch (true) {
                    case $object instanceof Rpe\PumBundle\Model\Social\Post:
                        $link_preview->addPost($object);
                        break;

                    case $object instanceof Rpe\PumBundle\Model\Social\Comment:
                        $link_preview->addComment($object);
                        break;
                }

                if ($autolink === true) {
                    $content = $this->autoLinkText($object->getContent());
                } else {
                    $content = $object->getContent();
                }
                $object->setContent($content);
                $object->setLinkPreview($link_preview);
                $this->persist($link_preview);
            }
        }

        return $object;
    }


    /**
     * @access public
     * @param string $id    id of object
     * @return array array contains the info list
     */
    public function getInfoList($id)
    {
        $queryBuilder = $this->getRepository($id)
            ->createQueryBuilder('repo');
        $result = $queryBuilder->andWhere('repo.parent IS NULL')
            ->orderBy('repo.sequence', 'ASC')
            ->orderBy('repo.name', 'ASC');
        $parents = $result->getQuery()->getResult();
        $bubbleInfo = array();
        foreach ($parents as $parent) {
            $item               = array();
            $item['id']         = $parent->getId();
            $item['name']       = $parent->getName();
            $item['children']   = array();

            $subQueryBuilder = $this->getRepository($id)
               ->createQueryBuilder('subrepo');
            $subResult = $subQueryBuilder->andWhere('subrepo.parent = :parent')
               ->orderBy('subrepo.sequence', 'ASC')
               ->orderBy('subrepo.name', 'ASC')
               ->setParameter('parent', $parent);
            $children =  $subResult->getQuery()->getResult();
            if (count($children)) {
                foreach ($children as $child) {
                    $subItem = array();
                    $subItem['id'] = $child->getId();
                    $subItem['name'] = $child->getName();
                    $item['children'][] = $subItem;
                }
            }
            $bubbleInfo[] = $item;
        }
        return $bubbleInfo;
    }

    /**
     * @param Media $media          rpe_media to add
     * @param string $user_quota    User quota value
     * @return string|false         User media quota size calculated
     */
    public function checkMediaSize($media, $user_quota = null)
    {
        $maximum_size = 1024 * 1024 * 1024;
        if (null === $user = $this->getUser()) {
            return false;
        }
        if ($user_quota === null) {
            $user = $this->getUser();
            if (null === $user->getMeta(User::META_MEDIA_DISK_QUOTA)) {
                $this->setUserMeta($user, User::META_MEDIA_DISK_QUOTA, 0);
            }
            $user_quota = (int)$user->getMeta(User::META_MEDIA_DISK_QUOTA)->getValue();
        }

        $media_size = 0;
        if ($media instanceof Media) {
            if ($media->getFile() instanceof UploadedFile) {
                $media_size = $media->getFile()->getClientSize();
            }
        }
        if ($media instanceof \Rpe\PumBundle\Model\Rpe\Media) {
            if ($media->getMedia() && $media->getMedia()->getFile() instanceof UploadedFile) {
                $media_size = $media->getMedia()->getFile()->getClientSize();
            }
        }
        $user_quota += $media_size;

        if ($user_quota < $maximum_size) {
            return $media_size;
        } else {
            return false;
        }
    }

    /**
     * Replace links in text with html links
     *
     * @param  string $text
     * @return string       Treated content
     */
    public function autoLinkText($text)
    {
        $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,5}(\/\S*)?/";

        if (preg_match($reg_exUrl, $text, $url)) {
            return preg_replace($reg_exUrl, '<a target="_blank" rel="nofollow" href="'.$url[0].'">'.$url[0].'</a>', $text);
        }

        return $text;
    }

    /**
     * cleanContent
     *
     * @param string $content 
     *
     * @return string
     * @access public
     */
    public function cleanContent($content)
    {
        // Purify HTML Content (remove scripts, onclick, etc.)
        $config = \HTMLPurifier_Config::createDefault();
        $purifier = new \HTMLPurifier($config);
        // $config->set('HTML.TargetBlank', true);
        $config->set('Attr.AllowedFrameTargets', array('_blank'));
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^(http:|https:)?//(www.youtube.com/embed/|player.vimeo.com/video/|www.flickr.com/services/oembed|www.hulu.com/embed|www.google.com/maps/embed|www.dailymotion.com/embed|w.soundcloud.com/player|www.slideshare.net|prezi.com|webtv.ac-versailles.fr|scolawebtv.crdp-versailles.fr|www.audio-lingua.eu|www.xmind.net)%');
        $config->set('Cache.SerializerPermissions', 0775);
        $config->set('Cache.SerializerPath', $this->get('kernel')->getCacheDir().'/htmlpurifier');

        return $purifier->purify($content);
    }

    /**
     * getUserTimezone
     *
     * @return Object  User time zone
     * @access public
     */
    public function getUserTimezone()
    {
        if (null === $userTimezone = $this->get('session')->get('user.timezone')) {
            $userTimezone = $this->get('rpe.utils')->getParameter('default_timezone');
        }

        return $userTimezone;
    }

    /**
     * convert an object date to user timezone
     * 
     * @param string $object
     * @param string $dateMethod
     * @param string $timezoneMethod
     * @return DateTime  Datime object according to timezone
     * @access public
     */
    public function toUserTimezone($object, $dateMethod, $timezoneMethod = 'getTimezone')
    {
        $objectDateTimezone = $object->$timezoneMethod() ? new \DateTimeZone($object->$timezoneMethod()) : null;

        // check null date
        $objectDateTime = $object->$dateMethod() ? $object->$dateMethod()->getTimestamp() : time();

        $objectDate = new \DateTime(date(\DateTime::ISO8601, $objectDateTime), $objectDateTimezone);
        $objectDate->setTimezone(new \DateTimeZone($this->getUserTimezone()));

        return $objectDate;
    }
}
