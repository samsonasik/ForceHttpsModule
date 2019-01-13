<?php

namespace ForceHttpsModule\Middleware;

use ForceHttpsModule\HttpsTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Console\Console;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;

class ForceHttps
{
    use HttpsTrait;

    /** @var array */
    private $config;

    /** @var RouterInterface */
    private $router;

    /**
     * @param  array           $config
     * @param  RouterInterface $router
     */
    public function __construct(array $config, RouterInterface $router)
    {
        $this->config = $config;
        $this->router = $router;
    }

    /**
     * Set The HTTP Strict Transport Security.
     *
     * @param string            $uriScheme
     * @param RouteResult       $match
     * @param ResponseInterface $response
     */
    private function setHttpStrictTransportSecurity($uriScheme, RouteResult $match, ResponseInterface $response)
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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $match = $this->router->match($request);
        if (Console::isConsole() || ! $this->config['enable']) {
            return $next($request, $response);
        }

        $uri       = $request->getUri();
        $uriScheme = $uri->getScheme();

        $response = $this->setHttpStrictTransportSecurity($uriScheme, $match, $response);
        if (! $this->isGoingToBeForcedToHttps($match)) {
            return $next($request, $response);
        }

        if ($this->isSchemeHttps($uriScheme)) {
            $uriString = $uri->__toString();
            $httpsRequestUri = $this->withWwwPrefixWhenRequired($uriString);
            $httpsRequestUri = $this->withoutWwwPrefixWhenNotRequired($httpsRequestUri);

            if ($uriString === $httpsRequestUri) {
                return $next($request, $response);
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
