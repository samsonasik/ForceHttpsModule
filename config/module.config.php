<?php

namespace ForceHttpsModule;

return [
    'service_manager' => [
        'factories' => [
            Listener\ForceHttps::class => Listener\ForceHttpsFactory::class,
        ],
    ],
    'listeners' => [
        Listener\ForceHttps::class,
    ],
];
