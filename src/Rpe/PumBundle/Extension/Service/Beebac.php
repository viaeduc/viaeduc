<?php
namespace Rpe\PumBundle\Extension\Service;

use Pum\Bundle\CoreBundle\PumContext;
use GuzzleHttp\Client;

/**
 * @method void __construct($arguments)
 * @method string getNotices($offset = 0, $startDate = 0)
 *
 */
class Beebac
{
    /**
     * @var string $apiKey  Api key
     * @var string $baseUrl  Api base url
     */
    private $apiKey;
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
        $this->apiKey   = $arguments['api_key'];
        $this->baseUrl  = $arguments['base_url'];
    }

    /**
     * Get notices by jason format
     *
     * @param number    $offset   Start offset of items
     * @param string    $startDate      Start date of creation
     * @return string
     */
    public function getNotices($offset = 0, $startDate = 0)
    {
        $url = sprintf($this->baseUrl."?%s", http_build_query(array(
            'method'    => 'beebac.viaeduc.get.publications',
            'api_key'   => $this->apiKey,
            'offset'    => $offset,
            'afterdate' => $startDate
        )));

        $client = new Client();
        $response = $client->get($url)->json();

        $response['result']['offset'] = $offset + count($response['result']['ressources']);

        // if ($offset < $response['result']['count']) {
        //     $newData = $this->getNotices($offset, $startDate);
        //     $newData['result']['ressources'] = array_merge($response['result']['ressources'], $newData['result']['ressources']);

        //     return $newData;
        // }

        return $response;
    }
}
