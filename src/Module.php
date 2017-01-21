<?php

namespace ForceHttpsModule;

use Zend\Mvc\MvcEvent;

class Module
{
    public function getConfig()
    {
        return include __DIR__.'./../config/module.config.php';
    }
}
