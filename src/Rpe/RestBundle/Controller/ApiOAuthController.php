<?php

namespace Rpe\RestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rpe\PumBundle\Model\Social\Notification;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations\Get;

class ApiOAuthController extends ApiController
{
    private $oAuthToken = null;

    /**
     * @Get(path="/oauth/token", name="apiv1_oauth_token", defaults={"_project"="rpe"})
     */
    public function getOAuthTokenAction(Request $request)
    {
        if(null === ($apiKey = $request->get('api_key'))) {
            return $this->getError('01', 'No API Key specified.', 'Please fill the \'api_key\' GET field.');
        }

        if(null === ($apiSecret = $request->get('api_secret'))) {
            return $this->getError('04', 'No API Secret specified.', 'Please fill the \'api_secret\' GET field.');
        }

        $oauthKey = $this->getRepository('social_oauth_key')->findOneByApiKey($apiKey);
        
        if(null === $oauthKey) {
            return $this->getError('02', 'Unknown API Key.', 'Please fill the \'api_key\' GET field with a valid API Key.');
        }

        if($oauthKey->getApiSecret() === $apiSecret) {
            if(false === $oauthKey->getActive()) {
                return $this->getError('03', 'API Key blocked.', 'Please contact us.');
            }
            
            $oauthToken = $this->createObject('social_oauth_token');
            $oauthToken->setDatetime(new \DateTime());
            $oauthToken->setOauthKey($oauthKey);
            $oauthToken->setIp($request->getClientIp());
            $oauthToken->setToken($this->getFreeToken($oauthKey));
            $this->persist($oauthToken);
            $this->flush();
            
            return $this->getView(array(
                'result' => 'oauth_success', 
                'api_key' => $oauthKey->getApiKey(), 
                'token' => $oauthToken->getToken()
            ));
        }

        return $this->getError('05', 'Wrong API credential.');
    }
    
    private function getFreeToken($oauthKey)
    {
        $token = bin2hex(mhash(MHASH_ADLER32, rand(10, 99).$oauthKey->getApiSecret().time()));
        
        if(null != ($oauthToken = $this->getRepository('social_oauth_token')->findOneByToken($token))) {
            return $this->getFreeToken($oauthKey);
        }
        
        return $token;
    }
    
    protected function authenticateUser(Request $request)
    {
        if(null === ($apiKey = $request->get('api_key'))) {
            return $this->getError('01', 'No API Key specified.', 'Please fill the \'api_key\' GET field.');
        }

        $oauthKey = $this->getRepository('social_oauth_key')->findOneByApiKey($apiKey);
        
        if(null === $oauthKey) {
            return $this->getError('02', 'Unknown API Key.', 'Please fill the \'api_key\' GET field with a valid API Key.');
        }

        if(null === ($token = $request->get('token'))) {
            return $this->getError('10', 'No Token specified.', 'Please fill the \'token\' GET field.');
        }
        
        $token = $this->getRepository('social_oauth_token')->findOneByToken($token);
        
        if(null === $token || $token->getOauthKey()->getApiKey() !== $apiKey) {
            return $this->getError('11', 'Unknown Token.', 'Please generate a valid Token with your API Key and API Secret.');
        }
        
        /*$now = new \DateTime();
        
        $tokenAgeSeconds = $now->diff($token->getDatetime())->s;
        
        if($tokenAgeSeconds > 10) {
            return $this->getError('12', 'Token expired.', 'Please generate a new Token with your API Key and API Secret.');
        }*/

        $this->oAuthToken = $token;
        
        return true;
    }
    
    protected function getOAuthUser()
    {
        return $this->oAuthToken->getOAuthKey()->getUser();
    }
    
    protected function getSuccess($code, $message, $solution = '')
    {
        return $this->getView(array(
            'result' => 'oauth_success', 
            'code' => $code, 
            'message' => $message, 
            'solution' => $solution
        ));
    }
    
    protected function getInsertSuccess($resourceName, $resourceId)
    {
        return $this->getSuccess('40', 'Resource \''.$resourceName.'\' was successfully created with ID '.$resourceId.'.');
    }
    
    protected function getUpdateSuccess($resourceName, $resourceId)
    {
        return $this->getSuccess('41', 'Resource \''.$resourceName.'\' with ID '.$resourceId.' was successfully updated.');
    }
    
    protected function getDeleteSuccess($resourceName, $resourceId)
    {
        return $this->getSuccess('42', 'Resource \''.$resourceName.'\' with ID '.$resourceId.' was successfully deleted.');
    }
    
    protected function getError($code, $message, $solution = '')
    {
        return $this->getView(array(
            'result' => 'oauth_failure', 
            'code' => $code, 
            'message' => $message, 
            'solution' => $solution
        ));
    }
    
    protected function getNotFoundError()
    {
        return $this->getError('404', 'This resource doesn\'t exists.');
    }
    
    protected function getAccessRightError()
    {
        return $this->getError('20', 'You don\'t have the rights to access this resource.');
    }
    
    protected function getEmptyFieldError($fieldName)
    {
        return $this->getError('30', 'The field \''.$fieldName.'\' is required but empty.', 'Fill the field \''.$fieldName.'\'.');
    }
    
    protected function getNorEmptyFieldsError($fieldsName)
    {
        return $this->getError('30', 'One of the fields \''.implode(', ', $fieldsName).'\' is required but empty.', 'Fill one of the fields \''.implode(', ', $fieldsName).'\'.');
    }
    
    protected function getMalFormattedFieldError($fieldName, $solution = '')
    {
        return $this->getError('31', 'The value of the field \''.$fieldName.'\' is mal formatted.', $solution);
    }
    
    protected function getBadValueFieldError($fieldName, $possibleValues = array())
    {
        if (count($possibleValues)) {
            return $this->getError('32', 'The value of the field \''.$fieldName.'\' is incorrect.', 'Possible values are: '.implode(', ', $possibleValues).'.');
        } else {
            return $this->getError('32', 'The value of the field \''.$fieldName.'\' is incorrect.');
        }
    }
    
    protected function getDoesntExistsFieldError($fieldName, $value = null)
    {
        if (null === $value) {
            return $this->getError('33', 'The value of the field \''.$fieldName.'\' doesn\'t exists.');
        } else {
            return $this->getError('33', 'The value \''.$value.'\' for the field \''.$fieldName.'\' doesn\'t exists.');
        }
    }
    
    protected function getAlreadyExistsFieldError($fieldName)
    {
        return $this->getError('34', 'The value of the field \''.$fieldName.'\' is already used.');
    }
}
