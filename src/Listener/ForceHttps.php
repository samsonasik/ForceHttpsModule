<?php

namespace ForceHttpsModule\Listener;

use ForceHttpsModule\HttpsTrait;
use Zend\Console\Console;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\MvcEvent;

class ForceHttps extends AbstractListenerAggregate
{
    use HttpsTrait;

    /**
     * @var array
     */
    private $config;

    /**
     * @param  array       $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param EventManagerInterface $events
     * @param int                   $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        if (Console::isConsole()) {
            return;
        }

        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'forceHttpsScheme']);
    }

    /**
     * Set The HTTP Strict Transport Security.
     *
     * @param string   $uriScheme
     * @param mixed    $match     didn't typed hinted in code in favor of zf-mvc ^2.5 RouteMatch compat
     * @param Response $response
     * @return Response
     */
    private function setHttpStrictTransportSecurity($uriScheme, $match, Response $response)
    {
        if ($this->isSkippedHttpStrictTransportSecurity($uriScheme, $match, $response)) {
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

    /**
     * Force Https Scheme handle.
     *
     * @param  MvcEvent         $e
     */
    public function forceHttpsScheme(MvcEvent $e)
    {
        if (! $this->config['enable']) {
           return;
        }

        /** @var $request \Zend\Http\PhpEnvironment\Request */
        $request   = $e->getRequest();
        /** @var $response \Zend\Http\PhpEnvironment\Response */
        $response  = $e->getResponse();

        $uri       = $request->getUri();
        /** @var $uriScheme string */
        $uriScheme = $uri->getScheme();

        $routeMatch = $e->getRouteMatch();
        $response   = $this->setHttpStrictTransportSecurity($uriScheme, $routeMatch, $response);
        if (! $this->validateSchemeAndToBeForcedHttpsConfig($uriScheme, $routeMatch)) {
            return;
        }

        $httpsRequestUri = $this->withWwwPrefixWhenRequired($uri->setScheme('https')->toString());

        // 307 keeps headers, request method, and request body
        // \Zend\Http\PhpEnvironment\Response doesn't support 308 yet
        $response->setStatusCode(307);
        $response->getHeaders()
                 ->addHeaderLine('Location', $httpsRequestUri);
        $response->send();
    }
}
