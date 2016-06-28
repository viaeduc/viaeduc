<?php
namespace Rpe\PumBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Controller for sso ent
 * 
 * @method Response ssoLoginAction(Request $request)
 * @method User     getSsoUser($id_sso)
 * @method string   curlRequest($parameters)
 * @method string   treatResponse($text)
 */
class SsoController extends Controller
{
    /**
     * @access public
     * @param  Request $request     A request instance
     * 
     * @return Response A Response instance
     *
     * @Route(path="/sso-login", name="sso_login", defaults={"_project"="rpe"})
     */
    public function ssoLoginAction(Request $request)
    {   
        $ticket = $request->query->get('ticket');
        $cas_host = $request->query->get('hostCAS');
        $cas_host = empty($cas_host) ? "ialcas.edulog.fr" : $cas_host;
        $cas_port = 443;
        $cas_uri  = "";
        
        if($request->query->get('debug') != null){
            $log_file = $this->get('kernel')->getRootDir() . "/logs/cas.log";
            \phpCAS::setDebug($log_file);
        } else {
            \phpCAS::setDebug(false);
        }
        // initialise cas client
        \phpCAS::client(CAS_VERSION_3_0, $cas_host, $cas_port, $cas_uri, false);
        // force CAS authentication
        \phpCAS::setNoCasServerValidation();
        \phpCAS::setExtraCurlOption(CURLOPT_SSL_VERIFYHOST, false);
        \phpCAS::setExtraCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        
        if(\phpCAS::isSessionAuthenticated() && $ticket != null){
            unset($_SESSION['phpCAS']);
        }
        \phpCAS::forceAuthentication();
        // for logout action
        if ($request->query->get('logout')) {
            \phpCAS::logout();
        }
        
        $cas_user = \phpCAS::getUser();
        $cas_attributes = \phpCAS::getAttributes();
        $cas_attributes['user'] = $cas_user;
        
        $current_user = $this->getUser();
        $user = $this->getSsoUser($cas_user);
        
        if($user != null){
            // ent_id already associated with rpe, login the rpe user
            $loginToken = new UsernamePasswordToken($user, $user->getPassword(), 'front_secured_area', $user->getRoles());
            $this->get('security.context')->setToken($loginToken);
            $this->get('session')->set('_security_front_secured_area', serialize($loginToken));
            return $this->redirect($this->generateUrl('home'));
        } else {
            $form = $this->get('form.factory')->createNamed(null, 'rpe_security_login');
            $lost_pwd_form = $this->createNamedForm('lost_password', 'rpe_security_lost_password');
            $session = $request->getSession();
            // get the login error if there is one
            
            if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
                $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
            } else {
                $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
                $session->remove(SecurityContext::AUTHENTICATION_ERROR);
            }
        }
        
        return $this->render('pum://page/relog.html.twig', array(
             'form'  => $form->createView(),
             'info_sso' => urlencode(serialize($cas_attributes)),
             'lost_pwd_form' => $lost_pwd_form->createView(),
             'error' => $error
        ));
    }
    
    /**
     * @access private
     * @param  string  $id_sso     Id of sso
     * 
     * @return User|null return the user object with sso id
     */
    private function getSsoUser($id_sso)
    {
        $user = $this->getRepository('user')
            ->createQueryBuilder('u')
            ->select('DISTINCT u')
            ->join('u.ents', 'ent', 'WITH', 'ent.idSso = :id_sso')
            ->andWhere('u IS NOT NULL')
            ->setParameters(array(
                'id_sso'    => $id_sso
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return $user; 
    }
    
    /**
     * @access private
     * @param  array  $parameters     Parameters array
     * 
     * @return string    Return sso api response
     * 
     */
    private function curlRequest($parameters)
    {
        $host = "http://ialcas.edulog.fr/?";
        $url = $host . http_build_query($parameters);
        
        // is cURL installed yet?
        if (function_exists('curl_init')){
            // Create a new cURL resource handle
            $ch = curl_init();
            // Set Options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
            // Download the given URL, and return output
            $output = curl_exec($ch);
            // Close the cURL resource, and free system resources
            curl_close($ch);
            
            return $output;
        }
        $output = @file_get_contents($url);
        return $output;
    }
    
    /**
     * @access private
     * @param  string $text Response text 
     * 
     * @return string User id of sso api
     */
    private function treatResponse($text)
    {
        if(empty($text)){
            return false;
        }
        $text = str_replace("cas:", "", $text);
        $xml = simplexml_load_string($text);

        if($xml->authenticationSuccess->user !== null){
            return (string) $xml->authenticationSuccess->user;
        }
        return false;
    }
}