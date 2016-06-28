<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;

class ApiActivityController extends ApiOAuthController
{
    /**
     * @Get(path="/activities", name="apiv1_activities", defaults={"_project"="rpe"})
     */
    public function getActivitysAction(Request $request)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        if (null !== ($oAuthUser = $this->getOAuthUser())) {
            if (!$oAuthUser->isAdmin()) {
                return $this->getAccessRightError();
            }
        }
        
        return $this->getObjectsAction($request, 'post', array('type' => 'activities', 'title' => 'Viaeduc - Activities', 'description' => 'List of Viaeduc activities', 'pathname' => 'post'));
    }
    
    /**
     * @Get(path="/activities/{activity_id}", name="apiv1_activity", defaults={"_project"="rpe"})
     */
    public function getActivityAction(Request $request, $activity_id)
    {
        if (true !== ($authenticateUser = $this->authenticateUser($request))) {
            return $authenticateUser;
        }
        
        return $this->getObjectAction($request, 'post', $activity_id, array('type' => 'activities'));
    }
}
