<?php

namespace Rpe\PumBundle\Controller;

use Symfony\Component\Finder\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Rpe\PumBundle\Model\Rpe\Media as RpeMedia;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\PostVersion;
use Rpe\PumBundle\Model\Social\Log;

/**
 *  Share controller 
 *
 * @method Response shareLink(Request $request, $type = NULL)
 * @method Response saveShare(Request $request, $id = NULL)
 */
class shareController extends Controller
{

    /**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $type        Type of share
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/share/{type}", name="share", defaults={"_project"="rpe"})
     */
	public function shareLink(Request $request, $type = NULL)
	{

	      if (null !== $check = $this->checkSecurity()) {
	            return $check;
	        }

	        $user = $this->getUser();

	        $param = $this->getRequest()->query->all();
	        if(array_diff_key(array_flip(array('title', 'image', 'description')), $param) && $type != 'isFailed'){

		        return $this->redirect($this->generateUrl('share', array('type' => 'isFailed')));  
		    }

		    foreach ($param as $key => $value) {
		    	$share[$key] = $value;
		    } 
		    $share['isFailed'] = $type;

		    $group = $this->getRepository('group')->getAcceptedGroups($user); 

	        return $this->render('pum://page/share/share.html.twig', array(
	            'share'                => $share,
	            'group'                => $group,
 	        ));
	}

	/**
     * @access public
     * @param  Request $request     A request instance
     * @param  string  $id          Id of object
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/share/save/{id}", name="share_save", defaults={"_project"="rpe"})
     */
	public function saveShare(Request $request, $id = NULL)
	{  

	        if (null !== $check = $this->checkSecurity()) {
	            return $check;
	        }

	            $user = $this->getUser();

	            $param = $this->getRequest()->query->all();

	            if(array_diff_key(array_flip(array('title', 'image', 'description', 'type', 'link')), $param)){

		             return $this->redirect($this->generateUrl('share', array('type' => 'isFailed')));  
		        }
		        $content = "<a href='".$this->getRequest()->query->get('link')."'>".$this->getRequest()->query->get('description')."</a>";

		        $postDate = new \DateTime();

	            $post = $this->createObject('post');

				    $post->setAuthor($user)
				        ->setCreateDate($postDate)
				        ->setUpdateDate($postDate)
				        ->setTargetUser($user)
				        ->setCommentStatus(true)
                        ->setResource(true)
                        ->setGlobal(false)
                        ->setImportant(false)
				        ->setDescription($this->getRequest()->query->get('description'))
				        ->setContent($content)
				        ->setName($this->getRequest()->query->get('title'))
				        ->setBroadcast(0)
				        ->setStatus(Post::STATUS_PUBLISHED)
				    ;

				// Version
	            $postVersion = $this->createObject('post_version');
	                $postVersion
	                    ->setAuthor($user)
	                    ->setStatus(PostVersion::STATUS_PUBLISHED)
	                    ->setName($this->getRequest()->query->get('title'))
	                    ->setContent($this->getRequest()->query->get('description'))
	                    ->setCreateDate($postDate)
	                    ->setUpdateDate($postDate)
	                ;

				switch ($this->getRequest()->query->get('type')) {
                    case Post::TYPE_WALL:
                        $post
                            ->setType(Post::TYPE_WALL)
                            ->setPublishedGroup(null)
                            ->setPublishedBlog(null)
                            ->setPublishedEditor(null)
                        ;
                        break;

                    case Post::TYPE_GROUP:
                        $group = $this->getRepository('group')->find($id);
                        $post
                            ->setPublishedGroup($group)
                            ->setType(Post::TYPE_GROUP)
                            ->setPublishedBlog(null)
                            ->setPublishedEditor(null)
                        ;
                        $group->addPost($post);
                        $this->persist($group);

                        $this->get('rpe.logs')->create($user, Log::TYPE_POST_RESOURCE, $user, $group);

                        break;

                    default:

                      return $this->redirect($this->generateUrl('share', array('type' => 'isFailed')));
                }

                    $input = $this->getRequest()->query->get('image');

                    $nameOutput = md5(mt_rand().uniqid().microtime()).".".substr(strrchr($input,'.'),1);
                    $output = sys_get_temp_dir().DIRECTORY_SEPARATOR.$nameOutput;
                    file_put_contents($output, file_get_contents($input));

                    $media = new Media();
			        $media
			            ->setFile(new \Symfony\Component\HttpFoundation\File\File($output))
			            ->setName($nameOutput)
			        ;

					$post->setFile($media);

                    $user->addPost($post);
                    $postVersion->setPost($post);

			        $this->persist($post, $user, $postVersion);
			        $this->flush();                   

			        if($post->getId()){
			        	return $this->redirect($this->generateUrl('publication', array('id' => $post->getId())));
			        }else{
			        	return $this->redirect($this->generateUrl('share', array('type' => 'isFailed')));
			        }
	}
}