<?php

declare(strict_types=1);

namespace ForceHttpsModule\Middleware;

use ForceHttpsModule\HttpsTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Console\Console;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;

class ForceHttps implements MiddlewareInterface
{
    use HttpsTrait;

    /** @var array */
    private $config;

    /** @var RouterInterface */
    private $router;

    public function __construct(array $config, RouterInterface $router)
    {
        $this->config = $config;
        $this->router = $router;
    }

    private function setHttpStrictTransportSecurity($uriScheme, RouteResult $match, ResponseInterface $response) : ResponseInterface
    {
        if ($this->isSkippedHttpStrictTransportSecurity($uriScheme, $match, $response)) {
            return $response;
        }

        if ($this->config['strict_transport_security']['enable'] === true) {
            $response = $response->withHeader('Strict-Transport-Security', $this->config['strict_transport_security']['value']);
            return $response;
        }

        $response = $response->withHeader('Strict-Transport-Security', 'max-age=0');
        return $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        if (Console::isConsole() || ! $this->config['enable']) {
            return $handler->handle($request);
        }

        $match    = $this->router->match($request);
        if ($match->isFailure()) {
            return $handler->handle($request);
        }

        $uri       = $request->getUri();
        $uriScheme = $uri->getScheme();

        $response = $this->setHttpStrictTransportSecurity($uriScheme, $match, $handler->handle($request));
        if (! $this->isGoingToBeForcedToHttps($match)) {
            return $response;
        }

        if ($this->isSchemeHttps($uriScheme)) {
            $uriString       = $uri->__toString();
            $httpsRequestUri = $this->withWwwPrefixWhenRequired($uriString);
            $httpsRequestUri = $this->withoutWwwPrefixWhenNotRequired($httpsRequestUri);

            if ($uriString === $httpsRequestUri) {
                return $response;
            }
        }

        if (! isset($httpsRequestUri)) {
            $newUri          = $uri->withScheme('https');
            $httpsRequestUri = $this->withWwwPrefixWhenRequired($newUri->__toString());
            $httpsRequestUri = $this->withoutWwwPrefixWhenNotRequired($httpsRequestUri);
        }

        // 308 keeps headers, request method, and request body
        $response = $response->withStatus(308);
        $response = $response->withHeader('Location', $httpsRequestUri);

        return $response;
    }
}
