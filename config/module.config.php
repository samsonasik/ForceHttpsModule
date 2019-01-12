<?php

namespace ForceHttpsModule;

return [
    'service_manager' => [
        'factories' => [
            Listener\ForceHttps::class                                  => Listener\ForceHttpsFactory::class,
            Listener\NotFoundLoggingListenerOnSharedEventManager::class => Listener\NotFoundLoggingListenerOnSharedEventManagerFactory::class,
        ],
    ],
    'listeners' => [
        Listener\ForceHttps::class,
    ],
];
