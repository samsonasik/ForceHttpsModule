<?php

namespace ForceHttpsModuleSpec\Middleware;

use ForceHttpsModule\Middleware\ForceHttps;
use Kahlan\Plugin\Double;
use Zend\Console\Console;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Router\RouteResult;

describe('ForceHttps', function () {

    describe('->invoke()', function () {

        given('response', function () {
            return Double::instance(['extends'    => Response::class]);
        });

        given('request', function () {
            return Double::instance(['implements'    => ServerRequestInterface::class]);
        });

        given('uri', function () {
            return Double::instance(['extends'    => Uri::class]);
        });

        given('router', function () {
            return Double::instance(['implements'    => RouterInterface::class]);
        });

        it('call next() on console', function () {

            Console::overrideIsConsole(true);
            $listener = new ForceHttps([], $this->router);

            $listener->__invoke($this->request, $this->response, function () {});

            expect($this->response)->not->toReceive('withStatus');
        });

        it('call next() on not-enable', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps(['enable' => false], $this->router);

            $listener->__invoke($this->request, $this->response, function () {});

            expect($this->response)->not->toReceive('withStatus');
        });

        it('call next() on router not match', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRouteFailure();
            allow($this->router)->toReceive('match')->andReturn($match);

            $listener = new ForceHttps(['enable' => true], $this->router);

            $listener->__invoke($this->request, $this->response, function () {});

            expect($this->response)->not->toReceive('withStatus');

        });

        it('call next() on https and match but no strict_transport_security config', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRouteMatch('about', 'About', []);
            allow($this->router)->toReceive('match')->andReturn($match);

            allow($this->request)->toReceive('getUri', 'getScheme')->andReturn('https');

            $listener = new ForceHttps(['enable' => true, 'force_all_routes' => true], $this->router);

            $listener->__invoke($this->request, $this->response, function () {});

            expect($this->response)->not->toReceive('withStatus');

        });

        it('call next() on http and match, with force_all_routes is false and matched route name not in force_specific_routes config', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRouteMatch('about', 'About', []);
            allow($this->router)->toReceive('match')->andReturn($match);

            allow($this->request)->toReceive('getUri', 'getScheme')->andReturn('http');

            $listener = new ForceHttps(
                [
                    'enable' => true,
                    'force_all_routes' => false,
                    'force_specific_routes' => [],
                    'strict_transport_security' => [
                        'enable' => false,
                        'value'  => 'max-age=31536000',
                    ],
                ],
                $this->router
            );

            $listener->__invoke($this->request, $this->response, function () {});

            expect($this->response)->not->toReceive('withStatus');

        });

        it('call next() on https and match, with strict_transport_security config, but disabled', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRouteMatch('about', 'About', []);
            allow($this->router)->toReceive('match')->andReturn($match);

            allow($this->request)->toReceive('getUri', 'getScheme')->andReturn('https');

            $listener = new ForceHttps(
                [
                    'enable' => true,
                    'force_all_routes' => true,
                    'strict_transport_security' => [
                        'enable' => false,
                        'value'  => 'max-age=31536000',
                    ],
                ],
                $this->router
            );

            $listener->__invoke($this->request, $this->response, function () {});

            expect($this->response)->not->toReceive('withStatus');

        });

        it('call next() on https and match, with strict_transport_security config, and enabled', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRouteMatch('about', 'About', []);

            allow($this->router)->toReceive('match')->andReturn($match);
            allow($this->request)->toReceive('getUri', 'getScheme')->andReturn('https');

            $listener = new ForceHttps(
                [
                    'enable' => true,
                    'force_all_routes' => true,
                    'strict_transport_security' => [
                        'enable' => true,
                        'value'  => 'max-age=31536000',
                    ],
                ],
                $this->router
            );

            $listener->__invoke($this->request, $this->response, function () {});

            expect($this->response)->not->toReceive('withStatus');

        });

        it('return Response with 308 status on http and match', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRouteMatch('about', 'About', []);

            allow($this->router)->toReceive('match')->andReturn($match);
            allow($this->request)->toReceive('getUri', 'getScheme')->andReturn('http');

            allow($this->response)->toReceive('withStatus')->andReturn($this->response);

            $listener = new ForceHttps(
                [
                    'enable' => true,
                    'force_all_routes' => true,
                    'strict_transport_security' => [
                        'enable' => true,
                        'value'  => 'max-age=31536000',
                    ],
                ],
                $this->router
            );

            $listener->__invoke($this->request, $this->response, function () {});

            expect($this->response)->toReceive('withStatus');

        });

    });

});
