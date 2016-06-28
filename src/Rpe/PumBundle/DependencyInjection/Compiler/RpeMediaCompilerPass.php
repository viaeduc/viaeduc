<?php

namespace Rpe\PumBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RpeMediaCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('type_extra.media.subscriber');

        $definition->replaceArgument(0, new Reference('rpe.type_extra.media.storage.driver'));
    }
}