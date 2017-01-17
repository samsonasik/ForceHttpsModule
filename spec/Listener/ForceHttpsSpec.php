<?php

namespace ForceHttpsModuleSpec;

use ForceHttpsModule\Listener\ForceHttps;
use Kahlan\Plugin\Double;
use Zend\Console\Console;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;

describe('ForceHttps', function () {

    describe('->attach()', function () {

        it('attach on route event on non-console', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            $eventManager = Double::instance(['implements' => EventManagerInterface::class]);
            expect($eventManager)->toReceive('attach')->with(MvcEvent::EVENT_ROUTE, [$listener, 'forceHttpsScheme']);

            $listener->attach($eventManager);

        });

        it('not attach on route on console', function () {

            Console::overrideIsConsole(true);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            $eventManager = Double::instance(['implements' => EventManagerInterface::class]);
            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_ROUTE, [$listener, 'forceHttpsScheme']);

            $listener->attach($eventManager);

        });

    });

});
