<?php

namespace ForceHttpsModule\Spec\Middleware;

use ForceHttpsModule\Middleware\ForceHttps;
use ForceHttpsModule\Middleware\ForceHttpsFactory;
use Kahlan\Plugin\Double;
use Psr\Container\ContainerInterface;
use Mezzio\Router\RouterInterface;

describe('ForceHttpsFactory', function (): void {

    beforeAll(function (): void {
        $this->factory = new ForceHttpsFactory();
    });

    describe('->__invoke', function (): void {

        given('container', fn(): object => Double::instance(['implements' => ContainerInterface::class]));

        given('router', fn(): object => Double::instance(['implements' => RouterInterface::class]));

        it('returns ' . ForceHttps::class . ' instance with default config', function (): void {

            $config = [];
            allow($this->container)->toReceive('get')->with('config')->andReturn($config);
            allow($this->container)->toReceive('get')->with(RouterInterface::class)->andReturn($this->router);

            $actual = $this->factory->__invoke($this->container);

            expect($actual)->toBeAnInstanceOf(ForceHttps::class);

        });

        it('returns ' . ForceHttps::class . ' instance with module config', function (): void {

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
