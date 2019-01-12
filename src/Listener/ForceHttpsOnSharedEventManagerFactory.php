<?php

declare(strict_types=1);

namespace ForceHttpsModule\Listener;

use Psr\Container\ContainerInterface;

class ForceHttpsOnSharedEventManagerFactory
{
    public function __invoke(ContainerInterface $container) : ForceHttps
    {
        $config           = $container->get('config');
        $forceHttpsConfig = $config['force-https-module'] ?? ['enable' => false];

        return new ForceHttpsOnSharedEventManager($forceHttpsConfig);
    }
}
