<?php

namespace ForceHttpsModule;

return [
    'service_manager' => [
        'factories' => [
            Listener\ForceListener::class => Listener\ForceHttpsFactory::class,
        ],
    ],
    'listeners' => [
        Listener\ForceListener::class,
    ],
];
