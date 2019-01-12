<?php

declare(strict_types=1);

namespace ForceHttpsModule\Listener;

use ForceHttpsModule\HttpsTrait;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;

class ForceHttpsOnSharedEventManager
{
    use HttpsTrait;

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function setHttpStrictTransportSecurity(string $uriScheme, Response $response) : Response
    {
        if ($this->isSkippedHttpStrictTransportSecurity($uriScheme, null, $response)) {
            return $response;
        }

        if ($this->config['strict_transport_security']['enable'] === true) {
            $response->getHeaders()
                     ->addHeaderLine('Strict-Transport-Security: ' . $this->config['strict_transport_security']['value']);
            return $response;
        }

        // set max-age = 0 to strictly expire it,
        $response->getHeaders()
                 ->addHeaderLine('Strict-Transport-Security: max-age=0');
        return $response;
    }

    public function __invoke(MvcEvent $e)
    {
        /** @var \Zend\Router\RouteMatch $routeMatch */
        $routeMatch = $e->getRouteMatch();

        $controller = $e->getTarget();
        $action     = \str_replace('-', '', $routeMatch->getParam('action')) . 'Action';

        if (\method_exists($controller, $action)) {
            return;
        }

        /** @var \Zend\Http\PhpEnvironment\Request $request */
        $request   = $e->getRequest();
        /** @var Response $response */
        $response  = $e->getResponse();

        $uri       = $request->getUri();
        /** @var string  $uriScheme*/
        $uriScheme = $uri->getScheme();

        $response   = $this->setHttpStrictTransportSecurity($uriScheme, $response);
        if (! $this->isGoingToBeForcedToHttps(null)) {
            return;
        }

        if ($this->isSchemeHttps($uriScheme)) {
            $uriString       = $uri->toString();
            $httpsRequestUri = $this->getFinalhttpsRequestUri($uriString);

            if ($uriString === $httpsRequestUri) {
                return;
            }
        }

        if (! isset($httpsRequestUri)) {
            $uriString       = $uri->setScheme('https')->toString();
            $httpsRequestUri = $this->getFinalhttpsRequestUri($uriString);
        }

        // 308 keeps headers, request method, and request body
        $response->setStatusCode(308);
        $response->getHeaders()
                 ->addHeaderLine('Location', $httpsRequestUri);
        $response->send();

        exit(0);
    }
}
