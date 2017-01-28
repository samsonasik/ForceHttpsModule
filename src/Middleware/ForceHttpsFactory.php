<?php

namespace ForceHttpsModule\Middleware;

use Interop\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface;

class ForceHttpsFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $router = $container->get(RouterInterface::class);

        if (empty($config['force-https-module'])) {
            $forceHttpsModuleConfig = [
                'enable'                => false,
            ];
            return new ForceHttps($forceHttpsModuleConfig, $router);
        }

        return new ForceHttps($config['force-https-module'], $router);
    }
}
