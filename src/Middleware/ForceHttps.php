<?php

namespace ForceHttpsModule\Middleware;

use ForceHttpsModule\HttpsTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Console\Console;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Router\RouteResult;

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
        if (
            $this->isSchemeHttps($uriScheme) &&
            $this->isGoingToBeForcedToHttps($match) &&
            isset(
                $this->config['strict_transport_security']['enable'],
                $this->config['strict_transport_security']['value']
            )
        ) {
            if ($this->config['strict_transport_security']['enable'] === true) {
                $response = $response->withHeader('Strict-Transport-Security', $this->config['strict_transport_security']['value']);
                return $response;
            }

            $response = $response->withHeader('Strict-Transport-Security', 'max-age=0');
            return $response;
        }

        return $response;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $match = $this->router->match($request);
        if (Console::isConsole() || ! $this->config['enable'] || $match->isFailure()) {
            return $next($request, $response);
        }

        $uri       = $request->getUri();
        $uriScheme = $uri->getScheme();

        $response = $this->setHttpStrictTransportSecurity($uriScheme, $match, $response);
        if (! $this->validateSchemeAndToBeForcedHttpsConfig($uriScheme, $match)) {
            return $next($request, $response);
        }

        $newUri          = $uri->withScheme('https');
        $httpsRequestUri = $newUri->__toString();

        // 308 keeps headers, request method, and request body
        // \Zend\Diactoros\Response already support 308
        $response = $response->withStatus(308);
        $response = $response->withHeader('Location', $httpsRequestUri);

        return $response;
    }
}
