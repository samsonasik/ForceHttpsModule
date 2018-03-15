<?php

namespace ForceHttpsModuleSpec\Middleware;

use ForceHttpsModule\Middleware\ForceHttps;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Console\Console;
use Zend\Diactoros\Uri;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;

describe('ForceHttps', function () {

    describe('->invoke()', function () {

        given('response', function () {
            return Double::instance(['implements' => ResponseInterface::class]);
        });

        given('request', function () {
            return Double::instance(['implements' => ServerRequestInterface::class]);
        });

        given('uri', function () {
            return Double::instance(['implements' => UriInterface::class]);
        });

        given('router', function () {
            return Double::instance(['implements'  => RouterInterface::class]);
        });

        it('not redirect on console', function () {

            Console::overrideIsConsole(true);
            $listener = new ForceHttps([], $this->router);

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');
        });

        it('not redirect on not-enable', function () {

            Console::overrideIsConsole(false);
            $listener = new ForceHttps(['enable' => false], $this->router);

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');
        });

        it('not redirect on router not match', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRouteFailure(null);
            allow($this->router)->toReceive('match')->andReturn($match);

            $listener = new ForceHttps(['enable' => true], $this->router);

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');

        });

        it('not redirect on https and match but no strict_transport_security config', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->router)->toReceive('match')->andReturn($match);

            allow($this->request)->toReceive('getUri', 'getScheme')->andReturn('https');

            $listener = new ForceHttps(['enable' => true, 'force_all_routes' => true], $this->router);

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');

        });

        it('not redirect on http and match, with force_all_routes is false and matched route name not in force_specific_routes config', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));
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

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');

        });

        it('not redirect on https and match, with strict_transport_security config, but disabled', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

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

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);
            allow($this->response)->toReceive('withHeader')->with('Strict-Transport-Security', 'max-age=0')->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');

        });

        it('not redirect on https and match, with strict_transport_security config, and enabled', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

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

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);
            allow($this->response)->toReceive('withHeader')->with('Strict-Transport-Security', 'max-age=31536000')->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');

        });

        it('return Response with 308 status on http and match', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->router)->toReceive('match')->andReturn($match);
            allow($this->request)->toReceive('getUri', 'getScheme')->andReturn('http');
            allow($this->request)->toReceive('getUri', 'withScheme', '__toString')->andReturn('https://example.com/about');

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);
            allow($this->response)->toReceive('withStatus')->with(308)->andReturn($this->response);
            allow($this->response)->toReceive('withHeader')->with('Location', 'https://example.com/about')->andReturn($this->response);

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
            $listener->process($this->request, $handler);

            expect($this->response)->toReceive('withStatus')->with(308);
            expect($this->response)->toReceive('withHeader')->with('Location', 'https://example.com/about');

        });

        it('return Response with 308 status with include www prefix on http and match with configurable "add_www_prefix"', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->router)->toReceive('match')->andReturn($match);
            allow($this->request)->toReceive('getUri', 'getScheme')->andReturn('http');
            allow($this->request)->toReceive('getUri', 'withScheme', '__toString')->andReturn('https://example.com/about');

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);
            allow($this->response)->toReceive('withStatus')->with(308)->andReturn($this->response);
            allow($this->response)->toReceive('withHeader')->with('Location', 'https://www.example.com/about')->andReturn($this->response);

            $listener = new ForceHttps(
                [
                    'enable' => true,
                    'force_all_routes' => true,
                    'strict_transport_security' => [
                        'enable' => true,
                        'value'  => 'max-age=31536000',
                    ],
                    'add_www_prefix' => true,
                ],
                $this->router
            );
            $listener->process($this->request, $handler);

            expect($this->response)->toReceive('withStatus')->with(308);
            expect($this->response)->toReceive('withHeader')->with('Location', 'https://www.example.com/about');

        });

        it('return Response with 308 status with remove www prefix on http and match with configurable "remove_www_prefix"', function () {

            Console::overrideIsConsole(false);
            $match = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->request)->toReceive('getUri', '__toString')->andReturn('http://www.example.com/about');
            allow($this->router)->toReceive('match')->andReturn($match);
            allow($this->request)->toReceive('getUri', 'getScheme')->andReturn('http');
            allow($this->request)->toReceive('getUri', 'withScheme', '__toString')->andReturn('https://www.example.com/about');

            allow($this->response)->toReceive('withStatus')->andReturn($this->response);
            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);
            allow($this->response)->toReceive('withStatus')->with(308)->andReturn($this->response);
            allow($this->response)->toReceive('withHeader')->with('Location', 'https://example.com/about')->andReturn($this->response);

            $listener = new ForceHttps(
                [
                    'enable' => true,
                    'force_all_routes' => true,
                    'strict_transport_security' => [
                        'enable' => true,
                        'value'  => 'max-age=31536000',
                    ],
                    'add_www_prefix' => false,
                    'remove_www_prefix' => true,
                ],
                $this->router
            );

            $listener->process($this->request, $handler);

            expect($this->response)->toReceive('withStatus')->with(308);
            expect($this->response)->toReceive('withHeader')->with('Location', 'https://example.com/about');

        });

    });

});
