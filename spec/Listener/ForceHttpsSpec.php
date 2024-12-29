<?php

namespace ForceHttpsModule\Spec\Listener;

use ForceHttpsModule\Listener\ForceHttps;
use Kahlan\Plugin\Double;
use Kahlan\Plugin\Quit;
use Kahlan\QuitException;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Uri\Uri;

describe('ForceHttps', function (): void {

    beforeAll(function (): void {
        $this->eventManager =  Double::instance(['implements' => EventManagerInterface::class]);
    });

    describe('->attach()', function (): void {

        it('not attach on route on console', function (): void {

            allow(ForceHttps::class)->toReceive('isInConsole')->andReturn(true);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            $listener->attach($this->eventManager);

            expect($this->eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_ROUTE, [$listener, 'forceHttpsScheme']);
            expect($this->eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$listener, 'forceHttpsScheme'], 1000);

        });

        it('not attach when not enabled', function (): void {

            allow(ForceHttps::class)->toReceive('isInConsole')->andReturn(false);
            $listener = new ForceHttps([
                'enable'                => false,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            $listener->attach($this->eventManager);

            expect($this->eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_ROUTE, [$listener, 'forceHttpsScheme']);
            expect($this->eventManager)->not->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$listener, 'forceHttpsScheme'], 1000);

        });

        it('attach on route event on non-console and enable', function (): void {

            allow(ForceHttps::class)->toReceive('isInConsole')->andReturn(false);
            $listener = new ForceHttps([
                'enable'                => true,
                'force_all_routes'      => true,
                'force_specific_routes' => [],
            ]);

            allow($this->eventManager)->toReceive('attach')->with(MvcEvent::EVENT_ROUTE, [$listener, 'forceHttpsScheme']);
            allow($this->eventManager)->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$listener, 'forceHttpsScheme'], 1000);
            $listener->attach($this->eventManager);

            expect($this->eventManager)->toReceive('attach')->with(MvcEvent::EVENT_ROUTE, [$listener, 'forceHttpsScheme']);
            expect($this->eventManager)->toReceive('attach')->with(MvcEvent::EVENT_DISPATCH_ERROR, [$listener, 'forceHttpsScheme'], 1000);

        });

    });

    describe('->forceHttpsScheme()', function (): void {

        beforeEach(function (): void {
            $this->mvcEvent             = Double::instance(['extends' => MvcEvent::class, 'methods' => '__construct']);
            $this->response             = Double::instance(['extends' => Response::class]);
            $this->request              = Double::instance(['extends' => Request::class]);
            $this->uri                  = Double::instance(['extends' => Uri::class]);
            $this->routeMatch           = Double::instance(['extends' => RouteMatch::class, 'methods' => '__construct']);

            Quit::disable();
        });

        context('on current scheme is https', function (): void {

            it('not redirect if uri already has https scheme and without strict_transport_security', function (): void {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                ]);

                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('about');

                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('https');
                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'toString')->andReturn('https://www.example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                expect($this->mvcEvent)->toReceive('getResponse');

                $listener->forceHttpsScheme($this->mvcEvent);
                expect($this->response)->not->toReceive('getHeaders');

            });

            it('not redirect if router not match', function (): void {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                ]);

                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn(null);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('about');

                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('https');
                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'toString')->andReturn('https://www.example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                expect($this->mvcEvent)->toReceive('getResponse');

                $listener->forceHttpsScheme($this->mvcEvent);
                expect($this->response)->not->toReceive('getHeaders');

            });

        });

        context('on current scheme is http', function (): void {

            it('not redirect if force_all_routes is false and route name not in force_specific_routes config', function (): void {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => false,
                    'force_specific_routes' => [
                        'checkout'
                    ],
                ]);

                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=0');

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->not->toReceive('send');

            });

            it('not redirect on router not match', function (): void {

                $listener = new ForceHttps([
                    'enable'                => true,
                ]);

                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn(null);
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->not->toReceive('send');

            });

            it('redirect if force_all_routes is false and route name in force_specific_routes config', function (): void {

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
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('checkout');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('setStatusCode')->with(308)->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
                allow($this->response)->toReceive('send');

                $closure = function () use ($listener): void {
                    $listener->forceHttpsScheme($this->mvcEvent);
                };
                expect($closure)->toThrow(new QuitException('Exit statement occurred', 0));

                expect($this->mvcEvent)->toReceive('getResponse');

            });

            it('not redirect if force_all_routes is true and route name in exclude_specific_routes config', function (): void {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'exclude_specific_routes' => [
                        'checkout'
                    ],
                    'force_specific_routes' => [],
                ]);

                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('checkout');
                allow($this->uri)->toReceive('toString')->andReturn('http://example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('send');

                $listener->forceHttpsScheme($this->mvcEvent);
                expect($this->mvcEvent)->toReceive('getResponse');
            });


            it('redirect if force_all_routes is true and route name not in exclude_specific_routes config', function (): void {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'exclude_specific_routes' => [
                        'sale'
                    ],
                    'force_specific_routes' => [],
                ]);

                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('checkout');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('setStatusCode')->with(308)->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
                allow($this->response)->toReceive('send');

                $closure = function () use ($listener): void {
                    $listener->forceHttpsScheme($this->mvcEvent);
                };
                expect($closure)->toThrow(new QuitException('Exit statement occurred', 0));

                expect($this->mvcEvent)->toReceive('getResponse');
            });

            it('redirect if force_all_routes is true', function (): void {

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
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('about');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');
                allow($this->response)->toReceive('setStatusCode')->with(308)->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
                allow($this->response)->toReceive('send');

                $closure = function () use ($listener): void {
                    $listener->forceHttpsScheme($this->mvcEvent);
                };
                expect($closure)->toThrow(new QuitException('Exit statement occurred', 0));

                expect($this->mvcEvent)->toReceive('getResponse');

            });

            it('redirect if force_all_routes is true and strict_transport_security config exists', function (): void {

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
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('about');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('setStatusCode')->with(308)->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
                allow($this->response)->toReceive('send');

                $closure = function () use ($listener): void {
                    $listener->forceHttpsScheme($this->mvcEvent);
                };
                expect($closure)->toThrow(new QuitException('Exit statement occurred', 0));

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');

            });


            it('redirect no router not match, but allow_404 is true', function (): void {

                $listener = new ForceHttps([
                    'enable'    => true,
                    'allow_404' => true,
                ]);

                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn(null);
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/404');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('setStatusCode')->with(308)->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/404');
                allow($this->response)->toReceive('send');

                $closure = function () use ($listener): void {
                    $listener->forceHttpsScheme($this->mvcEvent);
                };
                expect($closure)->toThrow(new QuitException('Exit statement occurred', 0));

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/404');

            });

            it('redirect with www prefix with configurable "add_www_prefix" on force_all_routes', function (): void {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                    'strict_transport_security' => [
                        'enable' => true,
                        'value' => 'max-age=31536000',
                    ],
                    'add_www_prefix' => true,
                ]);

                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('about');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('setStatusCode')->with(308)->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://example.com/about');
                allow($this->response)->toReceive('send');

                $closure = function () use ($listener): void {
                    $listener->forceHttpsScheme($this->mvcEvent);
                };
                expect($closure)->toThrow(new QuitException('Exit statement occurred', 0));

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Location', 'https://www.example.com/about');

            });

            it('redirect without www prefix for already has www prefix with configurable "remove_www_prefix" on force_all_routes', function (): void {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                    'strict_transport_security' => [
                        'enable' => true,
                        'value' => 'max-age=31536000',
                    ],
                    'add_www_prefix' => false,
                    'remove_www_prefix' => true,
                ]);

                allow($this->uri)->toReceive('toString')->andReturn('http://www.example.com/about');
                allow($this->mvcEvent)->toReceive('getRequest')->andReturn($this->request);
                allow($this->request)->toReceive('getUri')->andReturn($this->uri);
                allow($this->uri)->toReceive('getScheme')->andReturn('http');
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('about');
                allow($this->uri)->toReceive('setScheme')->with('https')->andReturn($this->uri);
                allow($this->uri)->toReceive('toString')->andReturn('https://www.example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')
                                      ->with('Location', 'https://example.com/about')
                                      ->andReturn($this->response);

                allow($this->response)->toReceive('setStatusCode')->with(308)->andReturn($this->response);
                allow($this->response)->toReceive('send');

                $closure = function () use ($listener): void {
                    $listener->forceHttpsScheme($this->mvcEvent);
                };
                expect($closure)->toThrow(new QuitException('Exit statement occurred', 0));

                expect($this->mvcEvent)->toReceive('getResponse');

            });

            it('not redirect with set strict_transport_security exists and uri already has https scheme', function (): void {

                $listener = new ForceHttps([
                    'enable'                => true,
                    'force_all_routes'      => true,
                    'force_specific_routes' => [],
                    'strict_transport_security' => [
                        'enable' => true,
                        'value' => 'max-age=31536000',
                    ],
                ]);

                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('about');
                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'getScheme')->andReturn('https');
                allow($this->mvcEvent)->toReceive('getRequest', 'getUri', 'toString')->andReturn('https://www.example.com/about');
                allow($this->mvcEvent)->toReceive('getResponse')->andReturn($this->response);
                allow($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');

                $listener->forceHttpsScheme($this->mvcEvent);

                expect($this->mvcEvent)->toReceive('getResponse');
                expect($this->response)->toReceive('getHeaders', 'addHeaderLine')->with('Strict-Transport-Security: max-age=31536000');

            });

            it('set Strict-Transport-Security if force_specific_routes has its value, match and strict_transport_security config exists', function (): void {

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
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('login');
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

            it('set Strict-Transport-Security to expire if force_specific_routes has its value, match and strict_transport_security config exists', function (): void {

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
                allow($this->mvcEvent)->toReceive('getRouteMatch')->andReturn($this->routeMatch);
                allow($this->routeMatch)->toReceive('getMatchedRouteName')->andReturn('login');
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
