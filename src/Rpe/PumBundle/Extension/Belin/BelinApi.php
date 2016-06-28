<?php
namespace Rpe\PumBundle\Extension\Belin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\ResponseInterface;
/**
 * class BelinSso to consume rest service
 *
 * @method void __construct(array $data, $url = null)
 * @method string   call($key)
 * @method array    treatResult(\SimpleXMLElement $response, $format = 'array')
 * @method array    cryptParam($params)
 * @method string   createAuth($login, $pwd)
 *
 */
class BelinApi
{
	/**
	 * @access public
	 * @var ParamCrypto     $crypter The crypter instance
	 */
	public static $crypter = null;
	/**
	 * @access public
     * @var Client          $client  The guzzle http client object
	 */
	private static $client = null;

	/**
	 * @access private
	 * @var $data
	 */
	private $data = null;
	/**
	 * @access private
	 * @var unknown
	 */
	private $api_url = 'API_URL_GOES_HERE';

	/**
	 * @access public
	 * @var $matiere   The maitiere filter
	 */
	public static $matiere = array(
	    "Anglais"                           => "Anglais",
	    "Chimie"                            => "Chimie",
	    "Geographie"                        => "Géographie",
	    "Francais"                          => "Français",
	    "Educationcivique"                  => "Education civique",
	    "Espagnol"                          => "Espagnol",
	    "Histoire"                          => "Histoire",
	    "HistoireGeographie"                => "Histoire Géographie",
	    "Italien"                           => "Italien",
	    "lsf"                               => "Langue des signes française",
	    "Mathematiques"                     => "Mathématiques",
	    "Sciences"                          => "Sciences",
	    "Scienceseconomiquesetsociales"     => "Sciences économiques et sociales",
	    "Sciencesdelavieetdelaterre"        => "Sciences de la vie et de la terre",
	    "Physique"                          => "Physique",
	    "PhysiqueChimie"                    => "Physique Chimie",
	    "Philosophie"                       => "Philosophie",
	    "Russe"                             => "Russe",
	);

	/**
	 * @access public
	 * @var $niveau   The niveau filter
	 */
	public static $niveau = array(
	    "CE1" => "CE1",
	    "CE2" => "CE2",
	    "CP" => "CP",
	    "CM1" => "CM1",
	    "CM2" => "CM2",
	    "premiere" => "1e",
	    "seconde" => "2e",
	    "troisieme" => "3",
	    "quatrieme" => "4",
	    "cinquieme" => "5",
	    "sixieme" => "6",
	    "Term" => "Term"
	);

	/**
	 * @access private
	 * @var $api_config   The api configuration
	 */
	private $api_config = array(
	    'Serveur/Info' => array(
	       'method'        => 'GET',
	       'parameters'    => null
	    ),
	    'Serveur/Connexion'    => array(
	        'method'        => 'GET',
	        'parameters'    => null,
            'auth'          => true
	    ),
		'Utilisateur/Creer' => array(
			'method'		=> 'GET',
			'parameters' 	=> array(
				'login'     => null,
				'pwd'       => null,
				'profil_id' => '1',    // 1 = Enseignant, 2 = Elève, 4 = Documentaliste, 5 = Conseiller Tice, 6 = Parent d'élève
				'support' 	=> '0'     // 0 = Web
			),
			'auth' => false
		),
		'Utilisateur/MajInfo' => array(
			'method'	=> 'GET',
			'parameters' => array(
				'utilsateur_id',
				'login',
				'pwd',
				'profil_id',
				'nom',
				'prenom',
				'civilite',
				'prof_id_unique',
				'courriel',
				'info_comp',
				'news_letter',
				'actif'
			),
			'auth' => true
		)
	);


	/**
	 * Construct api object
	 *
	 * @access public
	 * @param array $data  parameters
	 * @param string $url  Api url
	 *
	 * @return void
	 */
	public function __construct(array $data, $url = null)
	{
	    if($url != null){
	       $this->api_url = $url;
	    }

		if(null === self::$crypter){
		    self::$crypter = new ParamCrypto();
		}

		if(null === self::$client){
		    self::$client = new Client(array('base_url' => $this->api_url));
		}
		$this->data = $data;
	}

	/**
	 * Build context for post or get request and consume service
	 * @access public
	 * @param string $Key  Metho to call
	 *
	 * @return string  Return xml string
	 */
	public function call($key)
	{
		if(!isset($this->api_config[$key]))
			return false;

		$config = $this->api_config[$key];

		$query = $this->data;
		if(isset($config['parameters'])){
		    $query = array_merge($query, $this->cryptParam($config['parameters']));
		}
		else{
		  $query = array();
		}

	    if(true === $config['auth']){
	        if (isset($this->data['auth'])) {
	            $query['auth'] = $this->data['auth'];
	        } else {
    		    $query['auth'] = self::createAuth($this->data['login'], $this->data['pwd']);
	        }
		}

		$options = array();
		if($config['method'] === 'GET'){
		    $options['query'] = $query;
		}

		try {
		    $request = self::$client->createRequest($config['method'], $key, $options);
		    $response = self::$client->send($request);

		} catch (ClientException $e) {
            // in case of  login faied, ws return a 404 header which cause an clientexception, here
            // to avoid exception and out put response anyway
            $response = $e->getResponse();
		}

		// response always in xml format
		return $response->xml();
	}

	/**
	 * Treat the api return result
	 *
	 * @access public
	 * @param \SimpleXMLElement $response
	 * @param string $format    Format of string , default to array
	 *
	 * @return array return array or string if failed
	 */
	public function treatResult(\SimpleXMLElement $response, $format = 'array')
	{
	    $result = null;
		switch ($format)
		{
			case 'array':
			    $result = json_decode(json_encode($response), true);
			    break;
			default:
			    $result = $response;

		};
		return $result;
	}

	/**
	 * function to return parameters crypted by ParamCrypto
	 *
	 * @access public
	 * @param array $params Params to crypt
	 *
	 * @return array
	 */
	private function cryptParam($params)
	{
		foreach ($this->data as $param_key => $param_value){
		    $params[$param_key] = $param_value;
		}

		foreach ($params as $k => $v){
		    if($v === null)
		        unset($params[$k]);
		}

		array_walk($params, function (&$val, $key){
			$val = self::$crypter->getEncodedParam($val, $key);
			return $val;
		});
		return $params;
	}


	/**
	 * Generate an auth parameter
	 *
	 * @access public
	 * @param string $login Login name
	 * @param string $pwd   Login password
	 *
	 * @return string
	 */
	public static function createAuth($login, $pwd)
	{
	    $ts = time() * 1000;
	    $ts = number_format($ts, 0, '.', '');
	    $supportId = '0';  //(0 = WEB)
	    return base64_encode($login . ':' . $ts . ':' . md5($login . ':' . $pwd. ':' . $ts, false) . ':' . $supportId);
	}

}