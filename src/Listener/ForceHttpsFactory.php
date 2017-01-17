<?php

namespace ForceHttpsModule\Listener;

class ForceHttpsFactory
{
    public function __invoke($container)
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
