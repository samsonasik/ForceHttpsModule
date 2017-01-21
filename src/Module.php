<?php

namespace ForceHttpsModule;

use Zend\Mvc\MvcEvent;
use Zend\Uri\UriFactory;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        // handle chrome-extension like call via Postman
        // non-empty request->getContent()
        UriFactory::registerScheme('chrome-extension', 'Zend\Uri\Http');
    }

    public function getConfig()
    {
        return include __DIR__.'./../config/module.config.php';
    }
}
