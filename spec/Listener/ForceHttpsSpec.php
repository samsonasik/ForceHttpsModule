<?php

namespace ForceHttpsModuleSpec;

use ForceHttpsModule\Listener\ForceHttps;
use Kahlan\Arg;
use Kahlan\Plugin\Double;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Zend\Console\Console;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Client;
use Zend\Http\Header\Origin;
use Zend\Http\Headers;
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
            allow($mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('https');
            expect($mvcEvent)->not->toReceive('getResponse');

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
            allow($mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('http');
            allow($mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('about');
            expect($mvcEvent)->not->toReceive('getResponse');

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
            allow($response)->toReceive('setStatusCode')->with(302)->andReturn($response);
            allow($response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
            allow($response)->toReceive('send');

            expect($mvcEvent)->toReceive('getResponse');

            $listener->forceHttpsScheme($mvcEvent);

        });

        it('keep request and method and re-call uri with httpsed scheme for non-empty request body', function () {

            skipIf(PHP_MAJOR_VERSION < 7);
            Quit::disable();

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            $mvcEvent       = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $response       = Double::instance(['extends' => Response::class]);
            $request        = Double::instance(['extends' => Request::class]);
            $uri            = Double::instance(['extends' => Uri::class]);
            $client         = Double::instance(['extends' => Client::class]);
            $clientResponse = Double::instance(['extends' => Response::class]);

            allow($mvcEvent)->toReceive('getRequest')->andReturn($request);
            allow($request)->toReceive('getUri')->andReturn($uri);
            allow($uri)->toReceive('getScheme')->andReturn('http');
            allow($mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('api');
            allow($uri)->toReceive('setScheme')->with('https')->andReturn($uri);
            allow($uri)->toReceive('toString')->andReturn('https://example.com/api');
            allow($mvcEvent)->toReceive('getResponse')->andReturn($response);

            $headers = new Headers();
            allow($headers)->toReceive('toArray')->andReturn([
                'Origin' => 'chrome-extension: //random',
                'Content-Type' => 'application/json',
            ]);

            allow($request)->toReceive('getContent')->andReturn('{"foo":"fooValue"}');
            allow($request)->toReceive('getMethod')->andReturn('POST');
            allow($request)->toReceive('getHeaders')->andReturn($headers);

            allow($client)->toReceive('setUri')->with('https://example.com/about');
            allow($client)->toReceive('setMethod')->with('POST');
            allow($client)->toReceive('setRawBody')->with('{"foo":"fooValue"}');
            allow($client)->toReceive('setHeaders')->with($request->getHeaders());

            allow($clientResponse)->toReceive('getBody')->andReturn('{}');
            allow($clientResponse)->toReceive('getStatusCode')->andReturn(200);
            allow($client)->toReceive('send')->andReturn($clientResponse);

            allow($response)->toReceive('setStatusCode')->with(200)->andReturn($response);
            allow($response)->toReceive('send');

            expect($mvcEvent)->toReceive('getResponse');
            $closure = function() use ($listener, $mvcEvent){
                $listener->forceHttpsScheme($mvcEvent);
            };
            expect($closure)->toThrow(new QuitException());

        });

    });

});
