<?php

namespace ForceHttpsModule\Spec\Middleware;

use ForceHttpsModule\Middleware\ForceHttps;
use ForceHttpsModule\Middleware\ForceHttpsFactory;
use Interop\Container\ContainerInterface;
use Kahlan\Plugin\Double;
use Zend\Expressive\Router\RouterInterface;

describe('ForceHttpsFactory', function () {

    beforeAll(function () {
        $this->factory = new ForceHttpsFactory();
    });

    describe('->__invoke', function () {

        given('container', function () {
            return Double::instance(['implements' => ContainerInterface::class]);
        });

        given('router', function () {
            return Double::instance(['implements' => RouterInterface::class]);
        });

        it('returns ' . ForceHttps::class . ' instance with default config', function () {

            $config = [];
            allow($this->container)->toReceive('get')->with('config')->andReturn($config);
            allow($this->container)->toReceive('get')->with(RouterInterface::class)->andReturn($this->router);

            $actual = $this->factory->__invoke($this->container);

            expect($actual)->toBeAnInstanceOf(ForceHttps::class);

        });

        it('returns ' . ForceHttps::class . ' instance with module config', function () {

            $config = [
                'force-https-module' => [
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                ],
            ];
            allow($this->container)->toReceive('get')->with('config')->andReturn($config);
            allow($this->container)->toReceive('get')->with(RouterInterface::class)->andReturn($this->router);

            $actual = $this->factory->__invoke($this->container);

            expect($actual)->toBeAnInstanceOf(ForceHttps::class);

        });

    });

});
