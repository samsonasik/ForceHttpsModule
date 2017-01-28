<?php

namespace ForceHttpsModuleSpec\Listener;

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

        beforeEach(function () {
            $this->eventManager =  Double::instance(['implements' => EventManagerInterface::class]);
        });

        it('not attach on route on console', function () {

            Console::overrideIsConsole(true);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            $listener->attach($this->eventManager);

            expect($this->eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_ROUTE, [$listener, 'forceHttpsScheme']);

        });

        it('attach on route event on non-console and enable', function () {

            $eventManager =  Double::instance(['implements' => EventManagerInterface::class]);

            Console::overrideIsConsole(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            $listener->attach($eventManager);

            expect($eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_ROUTE, [$listener, 'forceHttpsScheme']);

        });

    });

    describe('->forceHttpsScheme()', function () {

        beforeEach(function () {
            $this->mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $this->response = Double::instance(['extends' => Response::class]);
            $this->request  = Double::instance(['extends' => Request::class]);
            $this->uri      = Double::instance(['extends' => Uri::class]);
        });

        context('not enabled', function () {

            it('returns early', function () {

                $listener = new ForceHttps([
                    'enable'                => false,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                ]);
                $actual = $listener->forceHttpsScheme($this->mvcEvent);

                expect($actual)->toBe(null);

            });

        });

        context('on current scheme is https', function () {

            it('not redirect if uri already has https scheme and without strict_transport_security', function () {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                ]);

                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('https');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                expect($this->mvcEvent)->toReceive('getResponse');

                $listener->forceHttpsScheme($this->mvcEvent);
                expect($this->response)->not->toReceive('getHeaders');

            });

        });

        context('on current scheme is http', function () {

            it('not redirect if force_all_routes is false and route name not in force_specific_routes config', function () {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => false,
                    'force_specific_routes' => [
                        'checkout'
                    ],
                ]);

                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=0');

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');

            });

            it('redirect if force_all_routes is false and route name in force_specific_routes config', function () {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => false,
                    'force_specific_routes' => [
                        'checkout'
                    ],
                ]);

                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('checkout');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('setStatusCode')->with(307)->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
                allow($this->response)->toReceive('send');

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');

            });

            it('redirect if force_all_routes is true', function () {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                ]);

                $this->mvcEvent = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
                $this->response = Double::instance(['extends' => Response::class]);
                $this->request  = Double::instance(['extends' => Request::class]);
                $this->uri      = Double::instance(['extends' => Uri::class]);

                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('about');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');
                allow($this->response)->toReceive('setStatusCode')->with(307)->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
                allow($this->response)->toReceive('send');

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');

            });

            it('redirect if force_all_routes is true and strict_transport_security config exists', function () {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                    'strict_transport_security' => [
                        'enable' => true,
                        'value' => 'max-age=31536000',
                    ],
                ]);

                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('about');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('setStatusCode')->with(307)->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
                allow($this->response)->toReceive('send');

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');

            });

            it('not redirect with set strict_transport_security exists and uri already has https scheme', function () {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                    'strict_transport_security' => [
                        'enable' => true,
                        'value' => 'max-age=31536000',
                    ],
                ]);

                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('https');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');

            });

            it('set Strict-Transport-Security if force_specific_routes has its value, match and strict_transport_security config exists', function () {

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

                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('https');
                allow($this->mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('login');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/login');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');
                allow($this->response)->toReceive('send');

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');
                expect($this->response)->not->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/login');

            });

            it('set Strict-Transport-Security to expire if force_specific_routes has its value, match and strict_transport_security config exists', function () {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => false,
                    'force_specific_routes' => [
                        'login'
                    ],
                    'strict_transport_security' => [
                        'enable' => false,
                        'value' => 'max-age=31536000',
                    ],
                ]);

                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('https');
                allow($this->mvcEvent)->toReceive('getRouteMatch', 'getMatchedRouteName')->andReturn('login');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/login');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=0');
                allow($this->response)->toReceive('send');

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=0');
                expect($this->response)->not->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/login');

            });

        });

    });

});
