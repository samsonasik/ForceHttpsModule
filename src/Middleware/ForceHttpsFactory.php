<?php

declare(strict_types=1);

namespace ForceHttpsModule\Middleware;

use Psr\Container\ContainerInterface;
use Mezzio\Router\RouterInterface;

class ForceHttpsFactory
{
    public function __invoke(ContainerInterface $container) : ForceHttps
    {
        $config = $container->get('config');
        $router = $container->get(RouterInterface::class);
        $forceHttpsConfig = $config['force-https-module'] ?? ['enable' => false];

        return new ForceHttps($forceHttpsConfig, $router);
    }
}
