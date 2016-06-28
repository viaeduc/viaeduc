<?php
namespace Rpe\PumBundle\Extension\Service;

use Rpe\PumBundle\Extension\EtherpadLiteClient\EtherpadLiteClient as BaseEtherpadLiteClient;

/**
 * @method void __construct($arguments)
 *
 */
class EtherpadLiteClient extends BaseEtherpadLiteClient
{
    /**
     * Construct function
     *
     * @access public
     * @param array $arguments
     * @return void
     */
    public function __construct($arguments)
    {
        $apiKey  = isset($arguments['api_key']) ? $arguments['api_key'] : '';
        $baseUrl = isset($arguments['base_url']) ? $arguments['base_url'] . 'api' : null;

        return parent::__construct($apiKey, $baseUrl);
    }
}
