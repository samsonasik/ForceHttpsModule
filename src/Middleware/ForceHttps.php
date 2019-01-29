<?php

declare(strict_types=1);

namespace ForceHttpsModule\Middleware;

use ForceHttpsModule\HttpsTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

    private function setHttpStrictTransportSecurity(
        string            $uriScheme,
        ResponseInterface $response,
        RouteResult       $match
    ) : ResponseInterface {

        if ($this->isSkippedHttpStrictTransportSecurity($uriScheme, $response, $match)) {
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $response = $handler->handle($request);
        if (! $this->config['enable']) {
            return $response;
        }

        $match    = $this->router->match($request);

        $uri       = $request->getUri();
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

        if (! isset($httpsRequestUri)) {
            $uriString       = $uri->withScheme('https')->__toString();
            $httpsRequestUri = $this->getFinalhttpsRequestUri($uriString);
        }

        // 308 keeps headers, request method, and request body
        $response = $response->withStatus(308);
        return $response->withHeader('Location', $httpsRequestUri);
    }
}
