<?php

declare(strict_types=1);

namespace ForceHttpsModule\Listener;

use ForceHttpsModule\HttpsTrait;
use Zend\Console\Console;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;

class ForceHttps extends AbstractListenerAggregate
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

    public function attach(EventManagerInterface $events, $priority = 1) : void
    {
        if (Console::isConsole() || ! $this->config['enable']) {
            return;
        }

        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'forceHttpsScheme']);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'forceHttpsScheme'], 1000);
    }

    private function setHttpStrictTransportSecurity(
        string     $uriScheme,
        Response   $response,
        RouteMatch $match = null
    ) : Response {
        if ($this->isSkippedHttpStrictTransportSecurity($uriScheme, $match)) {
            return $response;
        }

        if ($this->config['strict_transport_security']['enable'] === true) {
            $response->getHeaders()
                     ->addHeaderLine(sprintf(
                         'Strict-Transport-Security: %s',
                         $this->config['strict_transport_security']['value']
                     ));
            return $response;
        }

        // set max-age = 0 to strictly expire it,
        $response->getHeaders()
                 ->addHeaderLine('Strict-Transport-Security: max-age=0');
        return $response;
    }

    /**
     * Force Https Scheme handle.
     */
    public function forceHttpsScheme(MvcEvent $e) : void
    {
        /** @var \Zend\Http\PhpEnvironment\Request $request */
        $request   = $e->getRequest();
        /** @var Response $response */
        $response  = $e->getResponse();

        $uri       = $request->getUri();
        /** @var string  $uriScheme*/
        $uriScheme = $uri->getScheme();

        /** @var RouteMatch|null $routeMatch */
        $routeMatch = $e->getRouteMatch();
        $response   = $this->setHttpStrictTransportSecurity($uriScheme, $response, $routeMatch);
        if (! $this->isGoingToBeForcedToHttps($routeMatch)) {
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
