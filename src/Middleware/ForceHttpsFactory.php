<?php

declare(strict_types=1);

namespace ForceHttpsModule\Middleware;

use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

class ForceHttpsFactory
{
    public function __invoke(ContainerInterface $container): ForceHttps
    {
        $config = $container->get('config');
        /** @var RouterInterface $router */
        $router           = $container->get(RouterInterface::class);
        $forceHttpsConfig = (array) ($config['force-https-module'] ?? ['enable' => false]);

        return new ForceHttps($forceHttpsConfig, $router);
    }
}
