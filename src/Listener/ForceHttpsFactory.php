<?php

namespace ForceHttpsModule\Listener;

class ForceHttpsFactory
{
    public function __invoke($container)
    {
        return new ForceHttps($container->get('config')['force-https-module']);
    }
}
