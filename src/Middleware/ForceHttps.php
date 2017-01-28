<?php

namespace ForceHttpsModule\Middleware;

use Zend\Console\Console;
use Zend\Expressive\Router\RouterInterface;

class ForceHttps
{
    private $config;

    private $router;

    public function __construct(array $config, RouterInterface $router)
    {
        $this->config = $config;
        $this->router = $router;
    }

    /**
     * Is Scheme https ?
     *
     * @param string $uriScheme
     *
     * @return bool
     */
    private function isSchemeHttps($uriScheme)
    {
        return $uriScheme === 'https';
    }

    /**
     * Check Config if is going to be forced to https.
     *
     * @param  $match
     * @return bool
     */
    private function isGoingToBeForcedToHttps($match)
    {
        if (! $this->config['force_all_routes'] &&
            ! in_array(
                $match->getMatchedRouteName(),
                $this->config['force_specific_routes']
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Set The HTTP Strict Transport Security.
     *
     * @param string $uriScheme
     * @param $match
     * @param $response
     */
    private function setHttpStrictTransportSecurity($uriScheme, $match, $response)
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

    /**
     * Validate Scheme and Forced Https Config
     *
     * @param  string $uriScheme
     * @param  $match
     *
     * @return bool
     */
    private function validateSchemeAndToBeForcedHttpsConfig($uriScheme, $match)
    {
        if ($this->isSchemeHttps($uriScheme) || ! $this->isGoingToBeForcedToHttps($match)) {
            return false;
        }

        return true;
    }

    public function __invoke($request, $response, callable $next = null)
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
