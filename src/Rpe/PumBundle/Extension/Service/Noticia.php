<?php
namespace Rpe\PumBundle\Extension\Service;

use Pum\Bundle\CoreBundle\PumContext;
use GuzzleHttp\Client;

/**
 * @method void __construct($arguments)
 * @method string getNotices($offset = 0, $startDate = 0)
 *
 */
class Noticia
{
    /**
     * @var string $user      User name for api
     * @var string $password  Password for api
     * @var string $baseUrl   Base url
     */
    private $user;
    private $password;
    private $baseUrl;

    /**
     * Construct function
     *
     * @access public
     * @param array $arguments
     * @return void
     */
    public function __construct($arguments)
    {
        $this->user     = $arguments['user'];
        $this->password = $arguments['password'];
        $this->baseUrl  = $arguments['base_url'];
    }

    /**
     * Get notices by jason format
     *
     * @param string    $recoveryToken
     * @param string    $startDate      Start date of creation
     * @param string    $endDate        End date of creation
     *
     * @return string
     */
    public function getNotices($recoveryToken = null, $startDate = null, $endDate = null)
    {
        $url = sprintf($this->baseUrl."getNotices?%s", http_build_query(array(
            'utilisateur'       => $this->user,
            'motDePasse'        => $this->password,
            'dateDebut'         => $startDate,
            'dateFin'           => $endDate,
            'jetonDeReprise'    => $recoveryToken
        )));

        $client = new Client();
        $response = $client->get($url)->json();

        if ($response['erreurs'] && !$response['traitementOK']) {
            return $response;
        }

        // if ($response['jetonDeReprise'] > 0) {
            // $newData = $this->getNotices($response['jetonDeReprise'], $startDate, $endDate);
            // $newData['donnees'] = array_merge($response['donnees'], $newData['donnees']);
            // return $newData;
        // }

        return $response;
    }
}
