<?php

declare(strict_types=1);

namespace ForceHttpsModule\Middleware;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface;

class ForceHttpsFactory
{
    public function __invoke(ContainerInterface $container) : ForceHttps
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
