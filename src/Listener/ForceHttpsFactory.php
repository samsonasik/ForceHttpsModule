<?php

namespace ForceHttpsModule\Listener;

class ForceHttpsFactory
{
    public function __invoke($container)
    {
        $config = $container->get('config');
        if (empty($config['force-https-module'])) {
            $forceHttpsModuleConfig = [
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ];
            return new ForceHttps($forceHttpsModuleConfig);
        }

        return new ForceHttps($config['force-https-module']);
    }
}
