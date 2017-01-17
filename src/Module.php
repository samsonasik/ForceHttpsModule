<?php

namespace ForceHttpsModule;

class Module
{
    public function getConfig()
    {
        return include __DIR__.'./../module.config.php';
    }
}
