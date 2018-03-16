<?php

namespace ForceHttpsModuleSpec\Listener;

use ForceHttpsModule\Listener\ForceHttps;
use ForceHttpsModule\Listener\ForceHttpsFactory;
use Kahlan\Plugin\Double;
use Psr\Container\ContainerInterface;

describe('ForceHttpsFactory', function () {

    beforeAll(function () {
        $this->factory = new ForceHttpsFactory();
    });

    describe('->__invoke', function () {

        given('container', function () {
            return Double::instance(['implements' => ContainerInterface::class]);
        });

        it('returns ' . ForceHttps::class . ' instance with default config', function () {

            $config = [];
            allow($this->container)->toReceive('get')->with('config')->andReturn($config);

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

            $actual = $this->factory->__invoke($this->container);

            expect($actual)->toBeAnInstanceOf(ForceHttps::class);

        });

    });

});
