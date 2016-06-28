<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Question;

/**
 * Navigation controller
 * 
 * @method Response profileAction()
 * @method Response contentsAction()
 * @method Response groupsAction()
 * @method Response relationAction()
 *
 */
class NavController extends Controller
{
    /**
     * @access public
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/submenu-profile", name="submenu_profile", defaults={"_project"="rpe"})
     */
    public function profileAction()
    {  
         $userPublication = $this->getRepository('post')->getLastUserPublications($this->getUser(), false, 3);
         $lastResponse = $this->getRepository('question')->getLastResponseQuestion(3);
     
         return $this->render('pum://includes/common/header/menu/middle/profile-middle.html.twig', array('postRecent' => $userPublication, 'lastResponse' => $lastResponse));
    }

    /**
     * @access public
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/submenu-contents", name="submenu_contents", defaults={"_project"="rpe"})
     */
    public function contentsAction()
    {
       $user   = $this->getUser();

        return $this->render('pum://includes/common/header/menu/middle/content-middle.html.twig', array(
            'posts'=> $this->getRepository('post')->getHomePublications($user, $this->getRepository('group')->getIdentityAcceptedGroups($user), false, 3),
            'questions'=> $this->getRepository('question')->getSuggestedQuestions($user, false, 3)
        ));
    }

    /**
     * @access public
     * 
     * @return Response A Response instance
     *  
     * @Route(path="/submenu-groups", name="submenu_groups", defaults={"_project"="rpe"})
     */
    public function groupsAction()
    {
    	$activeGroups = $this->getRepository('group')->getActifGroups($this->getUser(), false, 3);

        $groups = $this->getRepository('group')->getSuggestedGroups($this->getUser(), false, 3);

        return $this->render('pum://includes/common/header/menu/middle/groups-middle.html.twig', array(
            'suggested_groups' => $groups,
            'active_groups'     => $activeGroups
        ));
    }

    /**
     * @access public
     * 
     * @return Response A Response instance
     * 
     * @Route(path="/submenu-relation", name="submenu_relation", defaults={"_project"="rpe"})
     */
    public function relationAction()
    {
        $relations = $this->getRepository('user')->getSuggestedFriends($this->getUser(), false, 6);

        return $this->render('pum://includes/common/header/menu/middle/relation-middle.html.twig', array(
            'suggested_relations'   =>  $relations
        ));
    }
}
