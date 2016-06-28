<?php

namespace Rpe\PumBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RpeParameterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // Etherpad parameters
        if (!$container->hasParameter('etherpad')) {
            $etherpadParams = array(
                'base_url'      => '_DEFAULT_ETHERPAD_BASE_URL_',
                'api_key'       => '_DEFAULT_ETHERPAD_API_KEY_',
                'domain_cookie' => '_DEFAULT_ETHERPAD_COOKIE_DOMAIN_',
            );

            $container->setParameter('etherpad', $etherpadParams);
        }

        // Default timezone
        if (!$container->hasParameter('default_timezone')) {
            $container->setParameter('default_timezone', 'Europe/Brussels');
        }

        // Chat parameters
        if (!$container->hasParameter('chat')) {
            $chatParams = array(
                'host_domain' => '_DEFAULT_XMPP_HOST_DOMAIN_',
                'host_port'   => '_DEFAULT_XMPP_HOST_PORT_',
            );

            $container->setParameter('chat', $chatParams);
        }
    }
}
