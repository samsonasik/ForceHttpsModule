<?php

namespace ForceHttpsModuleSpec;

use ForceHttpsModule\Listener\ForceHttps;
use Kahlan\Plugin\Double;
use Zend\Console\Console;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\MvcEvent;
use Zend\Uri\Uri;

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

        it('not attach on route on enable = false', function () {

            Console::overrideIsConsole(true);
            $listener = new ForceHttps([
                'enable'                => false,
            ]);

            $eventManager = Double::instance(['implements' => EventManagerInterface::class]);
            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_ROUTE, [$listener, 'forceHttpsScheme']);

            $listener->attach($eventManager);

        });

    });

    describe('->forceHttpsScheme()', function () {

        it('not redirect if uri already has https scheme', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $response = Double::instance(['extends' => Response::class]);

            allow($mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('https');
            allow($mvcEvent)->toReceive('getResponse')->andReturn($response);
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=0');
            expect($mvcEvent)->toReceive('getResponse');
            // no strict_transport_security config
            expect($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=0');

            $listener->forceHttpsScheme($mvcEvent);

        });

        it('not redirect if force_all_routes is false and route name not in force_specific_routes config', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => false,
                'force_specific_routes' => [
                    'checkout'
                ],
            ]);

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $response = Double::instance(['extends' => Response::class]);

            allow($mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('http');
            allow($mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('about');
            allow($mvcEvent)->toReceive('getResponse')->andReturn($response);
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=0');
            expect($mvcEvent)->toReceive('getResponse');

            $listener->forceHttpsScheme($mvcEvent);

        });

        it('redirect if force_all_routes is false and route name in force_specific_routes config', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => false,
                'force_specific_routes' => [
                    'checkout'
                ],
            ]);

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $response = Double::instance(['extends' => Response::class]);
            $request  = Double::instance(['extends' => Request::class]);
            $uri      = Double::instance(['extends' => Uri::class]);

            allow($mvcEvent)->toReceive('getRequest')->andReturn($request);
            allow($request)->toReceive('getUri')->andReturn($uri);
            allow($uri)->toReceive('getScheme')->andReturn('http');
            allow($mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('checkout');
            allow($uri)->toReceive('setScheme')->with('https')->andReturn($uri);
            allow($uri)->toReceive('toString')->andReturn('https://example.com/about');
            allow($mvcEvent)->toReceive('getResponse')->andReturn($response);
            allow($response)->toReceive('setStatusCode')->with(307)->andReturn($response);
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
            allow($response)->toReceive('send');

            expect($mvcEvent)->toReceive('getResponse');

            $listener->forceHttpsScheme($mvcEvent);

        });

        it('redirect if force_all_routes is true', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $response = Double::instance(['extends' => Response::class]);
            $request  = Double::instance(['extends' => Request::class]);
            $uri      = Double::instance(['extends' => Uri::class]);

            allow($mvcEvent)->toReceive('getRequest')->andReturn($request);
            allow($request)->toReceive('getUri')->andReturn($uri);
            allow($uri)->toReceive('getScheme')->andReturn('http');
            allow($mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('about');
            allow($uri)->toReceive('setScheme')->with('https')->andReturn($uri);
            allow($uri)->toReceive('toString')->andReturn('https://example.com/about');
            allow($mvcEvent)->toReceive('getResponse')->andReturn($response);
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');
            allow($response)->toReceive('setStatusCode')->with(307)->andReturn($response);
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
            allow($response)->toReceive('send');

            expect($mvcEvent)->toReceive('getResponse');

            $listener->forceHttpsScheme($mvcEvent);

        });

        it('not redirect with set strict_transport_security exists and uri already has https scheme', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
                'strict_transport_security' => [
                    'enable' => true,
                    'value' => 'max-age=31536000',
                ],
            ]);

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $response = Double::instance(['extends' => Response::class]);

            allow($mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('https');
            allow($mvcEvent)->toReceive('getResponse')->andReturn($response);
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');
            expect($mvcEvent)->toReceive('getResponse');
            expect($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');

            $listener->forceHttpsScheme($mvcEvent);

        });

        it('redirect if force_all_routes is true and strict_transport_security config exists', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
                'strict_transport_security' => [
                    'enable' => true,
                    'value' => 'max-age=31536000',
                ],
            ]);

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $response = Double::instance(['extends' => Response::class]);
            $request  = Double::instance(['extends' => Request::class]);
            $uri      = Double::instance(['extends' => Uri::class]);

            allow($mvcEvent)->toReceive('getRequest')->andReturn($request);
            allow($request)->toReceive('getUri')->andReturn($uri);
            allow($uri)->toReceive('getScheme')->andReturn('http');
            allow($mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('about');
            allow($uri)->toReceive('setScheme')->with('https')->andReturn($uri);
            allow($uri)->toReceive('toString')->andReturn('https://example.com/about');
            allow($mvcEvent)->toReceive('getResponse')->andReturn($response);
            allow($response)->toReceive('setStatusCode')->with(307)->andReturn($response);
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=0');
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
            allow($response)->toReceive('send');

            expect($mvcEvent)->toReceive('getResponse');
            // max-age still 0 as it not https yet
            expect($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=0');
            expect($response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');

            $listener->forceHttpsScheme($mvcEvent);

        });

        it('set Strict-Transport-Security if force_specific_routes has its value, match and strict_transport_security config exists', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => false,
                'force_specific_routes' => [
                    'login'
                ],
                'strict_transport_security' => [
                    'enable' => true,
                    'value' => 'max-age=31536000',
                ],
            ]);

            $mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $response = Double::instance(['extends' => Response::class]);
            $request  = Double::instance(['extends' => Request::class]);
            $uri      = Double::instance(['extends' => Uri::class]);

            allow($mvcEvent)->toReceive('getRequest')->andReturn($request);
            allow($request)->toReceive('getUri')->andReturn($uri);
            allow($uri)->toReceive('getScheme')->andReturn('https');
            allow($mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('login');
            allow($uri)->toReceive('setScheme')->with('https')->andReturn($uri);
            allow($uri)->toReceive('toString')->andReturn('https://example.com/login');
            allow($mvcEvent)->toReceive('getResponse')->andReturn($response);
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');
            allow($response)->toReceive('send');

            expect($mvcEvent)->toReceive('getResponse');
            expect($response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');
            expect($response)->not->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/login');

            $listener->forceHttpsScheme($mvcEvent);

        });

    });

});
