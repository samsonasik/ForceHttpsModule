<?php

declare(strict_types=1);

namespace ForceHttpsModule\Listener;

use Psr\Container\ContainerInterface;

class ForceHttpsFactory
{
    public function __invoke(ContainerInterface $container) : ForceHttps
    {
        $config = $container->get('config');
        if (empty($config['force-https-module'])) {
            $forceHttpsModuleConfig = [
                'enable'                => false,
            ];
            return new ForceHttps($forceHttpsModuleConfig);
        }

        return new ForceHttps($config['force-https-module']);
    }
}
