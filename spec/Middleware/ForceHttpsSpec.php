<?php

namespace ForceHttpsModule\Spec\Middleware;

use ForceHttpsModule\Middleware\ForceHttps;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;

describe('ForceHttps', function (): void {

    describe('->invoke()', function (): void {

        given('response', fn(): object => Double::instance(['implements' => ResponseInterface::class]));

        given('request', fn(): object => Double::instance(['implements' => ServerRequestInterface::class]));

        given('uri', fn(): object => Double::instance(['implements' => UriInterface::class]));

        given('router', fn(): object => Double::instance(['implements'  => RouterInterface::class]));

        it('not redirect on not-enable', function (): void {

            $listener = new ForceHttps(['enable' => false], $this->router);

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');
        });

        it('not redirect on router not match', function (): void {

            $routeResult = RouteResult::fromRouteFailure(null);

            allow($this->router)->toReceive('match')->andReturn($routeResult);

            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('getScheme')->andReturn('http');

            $listener = new ForceHttps(['enable' => true], $this->router);

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');

        });

        it('not redirect on router not match and config allow_404 is false', function (): void {

            $routeResult = RouteResult::fromRouteFailure(null);

            allow($this->router)->toReceive('match')->andReturn($routeResult);

            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('http://example.com/404');
            allow($this->uri)->toReceive('getScheme')->andReturn('http');

            $listener = new ForceHttps(
                [
                    'enable'    => true,
                    'allow_404' => false,
                ],
                $this->router
            );

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');

        });

        it('not redirect on https and match but no strict_transport_security config', function (): void {

            $routeResult = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->router)->toReceive('match')->andReturn($routeResult);

            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('https://example.com/about');
            allow($this->uri)->toReceive('getScheme')->andReturn('https');

            $listener = new ForceHttps(['enable' => true, 'force_all_routes' => true], $this->router);

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);

            $listener->process($this->request, $handler);

            expect($this->response)->not->toReceive('withStatus');

        });

        it('not redirect on http and match, with force_all_routes is false and matched route name not in force_specific_routes config', function (): void {

            $routeResult = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));
            allow($this->router)->toReceive('match')->andReturn($routeResult);

            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('http://example.com/about');
            allow($this->uri)->toReceive('getScheme')->andReturn('http');

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

        it('not redirect on https and match, with strict_transport_security config, but disabled', function (): void {

            $routeResult = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->router)->toReceive('match')->andReturn($routeResult);

            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('https://example.com/about');
            allow($this->uri)->toReceive('getScheme')->andReturn('https');

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

        it('not redirect on https and match, with strict_transport_security config, and enabled', function (): void {

            $routeResult = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->router)->toReceive('match')->andReturn($routeResult);
            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('https://example.com/about');
            allow($this->uri)->toReceive('getScheme')->andReturn('https');

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

        it('return Response with 308 status on http and match', function (): void {

            $routeResult = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->router)->toReceive('match')->andReturn($routeResult);

            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('getScheme')->andReturn('http');
            allow($this->uri)->toReceive('withScheme')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('https://example.com/about');

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

        it('return Response with 308 status on http and not match, but allow_404 is true', function (): void {

            $routeResult = RouteResult::fromRouteFailure(null);

            allow($this->router)->toReceive('match')->andReturn($routeResult);

            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('getScheme')->andReturn('http');
            allow($this->uri)->toReceive('withScheme')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('https://example.com/404');

            $handler = Double::instance(['implements' => RequestHandlerInterface::class]);
            allow($handler)->toReceive('handle')->with($this->request)->andReturn($this->response);
            allow($this->response)->toReceive('withStatus')->with(308)->andReturn($this->response);
            allow($this->response)->toReceive('withHeader')->with('Location', 'https://example.com/404')->andReturn($this->response);

            $listener = new ForceHttps(
                [
                    'enable'    => true,
                    'allow_404' => true,
                ],
                $this->router
            );
            $listener->process($this->request, $handler);

            expect($this->response)->toReceive('withStatus')->with(308);
            expect($this->response)->toReceive('withHeader')->with('Location', 'https://example.com/404');

        });

        it('return Response with 308 status with include www prefix on http and match with configurable "add_www_prefix"', function (): void {

            $routeResult = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->router)->toReceive('match')->andReturn($routeResult);
            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('https://example.com/about');
            allow($this->uri)->toReceive('getScheme')->andReturn('http');
            allow($this->uri)->toReceive('withScheme')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('https://www.example.com/about');

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

        it('return Response with 308 status with remove www prefix on http and match with configurable "remove_www_prefix"', function (): void {

            $routeResult = RouteResult::fromRoute(new Route('/about', Double::instance(['implements' => MiddlewareInterface::class])));

            allow($this->router)->toReceive('match')->andReturn($routeResult);
            allow($this->uri)->toReceive('__toString')->andReturn('https://www.example.com/about');
            allow($this->request)->toReceive('getUri')->andReturn($this->uri);
            allow($this->uri)->toReceive('getScheme')->andReturn('http');
            allow($this->uri)->toReceive('withScheme')->andReturn($this->uri);
            allow($this->uri)->toReceive('__toString')->andReturn('https://example.com/about');

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
