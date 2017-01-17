<?php

namespace ForceHttpsModule\Listener;

class ForceHttpsListenerFactory
{
    public function __invoke($container)
    {
        return new ForceHttpsListener($container->get('config')['force-https-module'])
    }
}
