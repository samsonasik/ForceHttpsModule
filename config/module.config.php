<?php

namespace ForceHttpsModule;

use ForceHttpsModule\Listener\ForceHttps;
use ForceHttpsModule\Listener\ForceHttpsFactory;
return [
    'service_manager' => [
        'factories' => [
            ForceHttps::class => ForceHttpsFactory::class,
        ],
    ],
    'listeners' => [
        ForceHttps::class,
    ],
];
