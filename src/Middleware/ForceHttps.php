<?php

declare(strict_types=1);

namespace ForceHttpsModule\Middleware;

use ForceHttpsModule\HttpsTrait;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForceHttps implements MiddlewareInterface
{
    use HttpsTrait;

    /**
     * @param mixed[] $config
     */
    public function __construct(private array $config, private RouterInterface $router)
    {
    }

    private function setHttpStrictTransportSecurity(
        string $uriScheme,
        ResponseInterface $response,
        RouteResult $routeResult
    ): ResponseInterface {
        if ($this->isSkippedHttpStrictTransportSecurity($uriScheme, $routeResult)) {
            return $response;
        }

        if ($this->config['strict_transport_security']['enable'] === true) {
            return $response->withHeader(
                'Strict-Transport-Security',
                $this->config['strict_transport_security']['value']
            );
        }

        return $response->withHeader('Strict-Transport-Security', 'max-age=0');
    }

    public function process(
        ServerRequestInterface $serverRequest,
        RequestHandlerInterface $requestHandler
    ): ResponseInterface {
        $response = $requestHandler->handle($serverRequest);
        if (! $this->config['enable']) {
            return $response;
        }

        $match = $this->router->match($serverRequest);

        $uri       = $serverRequest->getUri();
        $uriScheme = $uri->getScheme();

        $response = $this->setHttpStrictTransportSecurity($uriScheme, $response, $match);
        if (! $this->isGoingToBeForcedToHttps($match)) {
            return $response;
        }

        if ($this->isSchemeHttps($uriScheme)) {
            $uriString       = $uri->__toString();
            $httpsRequestUri = $this->getFinalhttpsRequestUri($uriString);

            if ($uriString === $httpsRequestUri) {
                return $response;
            }
        }

        $httpsRequestUri ??= $this->getFinalhttpsRequestUri((string) $uri->withScheme('https'));

        // 308 keeps headers, request method, and request body
        $response = $response->withStatus(308);
        return $response->withHeader('Location', $httpsRequestUri);
    }
}
