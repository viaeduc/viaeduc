<?php

namespace Rpe\PumBundle;

use Rpe\PumBundle\DependencyInjection\CompilerPass\AddRpeMetadataDriverPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Rpe\PumBundle\DependencyInjection\Compiler\RpeMediaCompilerPass;
use Rpe\PumBundle\DependencyInjection\Compiler\RpeParameterCompilerPass;

class RpePumBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RpeMediaCompilerPass());
        $container->addCompilerPass(new RpeParameterCompilerPass());
    }
}
