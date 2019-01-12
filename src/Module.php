<?php

declare(strict_types=1);

namespace ForceHttpsModule;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $app           = $e->getApplication();
        $eventManager  = $app->getEventManager();
        $services      = $app->getServiceManager();
        $sharedManager = $eventManager->getSharedManager();

        $sharedManager->attach(
            AbstractActionController::class,
            MvcEvent::EVENT_DISPATCH,
            $services->get(Listener\ForceHttpsOnSharedEventManager::class),
            1000
        );
    }

    public function getConfig() : array
    {
        return include __DIR__.'./../config/module.config.php';
    }
}
