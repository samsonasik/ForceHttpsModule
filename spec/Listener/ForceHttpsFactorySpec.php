<?php

namespace ForceHttpsModuleSpec;

use ForceHttpsModule\Listener\ForceHttps;
use ForceHttpsModule\Listener\ForceHttpsFactory;
use Interop\Container\ContainerInterface;
use Kahlan\Plugin\Double;

describe('ForceHttpsFactory', function () {

    beforeAll(function () {
        $this->factory = new ForceHttpsFactory();
    });

    describe('->__invoke', function () {

        it('returns ' . ForceHttps::class . ' instance with default config', function () {

            $config = [];
            $container = Double::instance(['implements' => ContainerInterface::class]);
            allow($container)->toReceive('get')->with('config')->andReturn($config);

            $actual = $this->factory->__invoke($container);
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
            $container = Double::instance(['implements' => ContainerInterface::class]);
            allow($container)->toReceive('get')->with('config')->andReturn($config);

            $actual = $this->factory->__invoke($container);
            expect($actual)->toBeAnInstanceOf(ForceHttps::class);

        });

    });

});
