<?php
namespace Rpe\PumBundle\Extension\Belin;

define('EDULIB_CRYPTO_KEY_MAISON', '____CRYPTO_KEY_GOES_HERE____');

/**
 * @method string processString($string, $key)
 * @method string getKey($paramName, $optKeyParam=NULL)
 * @method string getRandomString($srcParam)
 * @method string getStringWithoutRandom($randParam)
 * @method string getEncodedParam($param, $paramName, $optKeyParam=NULL)
 * @method string getDecodedParam($paramName, $optKeyParam=NULL, $paramMode="GET")
 * @method string getDecodedParamValue($paramValue, $paramName, $optKeyParam=NULL, $urlDecode=false)
 * @method string getDecodedParamTmp($param, $paramName, $optKeyParam=NULL)
 *
 */
class ParamCrypto
{
	/**
	 * @access protected
	 * @var string $minStrLength
	 */
	protected $minStrLength = 9;

	/**
	 * @access protected
	 * @var string $addedCharsSize
	 */
	protected $addedCharsSize = array('S','7','j','3','P','a','V','n','0','4','x','p','Y');

    /**
     * Process string add key
     *
     * @access protected
     * @param string    $string
     * @param string    $key
     *
     * @return string
     */
	protected function processString($string, $key)
	{
		$iKey=0;
		$processedStr="";
		for ($i = 0, $j = strlen($string); $i < $j; $i++) {
			$processedStr=$processedStr.($string[$i]^$key[$iKey]);
			$iKey++;
			if($iKey == strlen($key))
				$iKey=0;
		}
		return $processedStr;
	}

	/**
	 * Get key of a parameter
	 *
	 * @access protected
	 * @param string    $paramName Parameter name
	 * @param string    $optKeyParam   Optional key
	 *
	 * @return string
	 */
	protected function getKey($paramName, $optKeyParam=NULL)
	{
		$key = EDULIB_CRYPTO_KEY_MAISON . $paramName;
		//$key=_conf("CryptoRootKey").$paramName;//On n'utilise pas le fichier de config pour pouvoir diffuser ce code plus facilement
		if(!empty($optKeyParam))
			$key=$optKeyParam."_".$key;
		return $key;
	}

	/**
	 * Generate a random string
	 *
	 * @access protected
	 * @param string    $srcParam      Source paramater
	 * @param string    $optKeyParam   Optional key
	 *
	 * @return string
	 */
	protected function getRandomString($srcParam)
	{
		$randParam = "";
		for ($i = 0, $j = strlen($srcParam); $i < $j; $i++) {
			$randChar=chr(rand(33,125));
			$randParam=$randParam.($srcParam[$i]^$randChar).$randChar;
		}
		return $randParam;
	}

	/**
	 * Get string without random key
	 *
	 * @access protected
	 * @param string    $randParam
	 *
	 * @return string
	 */
	protected function getStringWithoutRandom($randParam)
	{
		$srcParam = "";
		for ($i = 0; $i <  strlen($randParam); $i=$i+2) {
			if(!isset($randParam[$i+1])) continue;
			$randChar=$randParam[$i+1];
			$srcParam=$srcParam.($randParam[$i]^$randChar);
		}
		return $srcParam;
	}

	/**
	 * Get encoded param
	 *
	 * @access protected
	 * @param string    $param Param value
	 * @param string    $paramName Param name
	 * @param string    $optKeyParam Param key if need
	 *
	 * @return string
	 */
	public function getEncodedParam($param, $paramName, $optKeyParam=NULL)
	{
		//Création de la clé de cryptage
		$key=$this->getKey($paramName, $optKeyParam);

		$randString = $this->getRandomString($param);
		//Création d'une chaîne de caractères d'au moins 6 caractères (avec ajout de caractères aléatoires)
		if( strlen($param) < $this->minStrLength )
			$nAddedChars = rand(6, count($this->addedCharsSize)-1);
		else $nAddedChars = rand(0, count($this->addedCharsSize)-1);
		$addedString = "";
		for($i=0; $i<$nAddedChars; $i++){
			$addedString = $addedString.chr(rand(33,125));
		}
		$param = $addedString.$randString;

		$codedStr=$this->processString($param, $key);

		$codedStr = base64_encode($codedStr);
		$codedStr = urlencode($codedStr);

		return $this->addedCharsSize[$nAddedChars].$codedStr;
	}

	/**
	 * Get decoded param
	 *
	 * @access public
	 * @param string    $param Param value
	 * @param string    $paramName Param name
	 * @param string    $paramMode
	 *
	 * @return string
	 */
	public function getDecodedParam($paramName, $optKeyParam=NULL, $paramMode="GET")
	{
		$paramFound = false;
		if($paramMode === "POST"){
			$paramFound = isset($_POST["{$paramName}"]);
			$param = $_POST["{$paramName}"];
		}
		else{
			$paramFound = isset($_GET["{$paramName}"]);
			$param = $_GET["{$paramName}"];
		}
		//$urlDecode=false car _GET fait déjà appel à urldecode()
		return $this->getDecodedParamValue($param, $paramName, $optKeyParam, false);
	}

	/**
	 * Get decoded param value
	 *
	 * @access public
	 * @param string    $paramValue Param value
	 * @param string    $paramName Param name
	 * @param string    $optKeyParam
	 * @param boolean   $urlDecode
	 *
	 * @return string
	 */
	public function getDecodedParamValue($paramValue, $paramName, $optKeyParam=NULL, $urlDecode=false)
	{
		if($urlDecode)
			$param = urldecode($paramValue);
		else $param = $paramValue;
		// if(isset($_GET["{$paramName}"])){
		// $param = $_GET["{$paramName}"];

		//on récupère la chaîne sans l'indicateur d'ajout de caractères
		$firstChar = array_search($param[0],$this->addedCharsSize);
		//if(!$firstChar)
		//	Throw new Exception("Invalid encrypted PHP Parameter {$paramName}={$param}");//"#LIB#lib_invalid_encrypted_php_param");//Invalid encrypted PHP Parameter {$paramName}={$param}");
		$nAddedChars = (int)array_search($param[0],$this->addedCharsSize);

		$param = substr($param, 1);

		//Création de la clé de décryptage
		$key=$this->getKey($paramName, $optKeyParam);

		$param = base64_decode($param);

		$decodedStr=$this->processString($param, $key);

		//on récupère la chaîne sans les caractères insérés au début
		if($nAddedChars > 0)
			$decodedStr = substr($decodedStr, $nAddedChars);
		//Enfin, on récupère la chaîne sans les caractères insérés entre deux
		$decodedStr = $this->getStringWithoutRandom($decodedStr);

		return $decodedStr;
	}

	/**
	 * Get decoded param temp value
	 *
	 * @access private
	 * @param string    $param      Param value
	 * @param string    $paramName  Param name
	 * @param string    $optKeyParam
	 *
	 * @return string
	 */
	private function getDecodedParamTmp($param, $paramName, $optKeyParam=NULL)
	{

		//on récupère la chaîne sans l'indicateur d'ajout de caractères
		$nAddedChars = (int)array_search($param[0],$this->addedCharsSize);
		$param = substr($param, 1);
		$decodedStr = urldecode($param);//Présente uniquement pour cette version debug de la méthode

		//Création de la clé de décryptage
		$key=$this->getKey($paramName, $optKeyParam);

		$decodedStr = base64_decode($decodedStr);

		$decodedStr=$this->processString($decodedStr, $key);

		//on récupère la chaîne sans les caractères insérés au début
		if($nAddedChars > 0)
			$decodedStr = substr($decodedStr, $nAddedChars);
		//Enfin, on récupère la chaîne sans les caractères insérés entre deux
		$decodedStr = $this->getStringWithoutRandom($decodedStr);

		return $decodedStr;
	}
}