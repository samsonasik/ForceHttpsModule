<?php

namespace ForceHttpsModule\Listener;

use Zend\Console\Console;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;

class ForceHttps extends AbstractListenerAggregate
{
    /**
     * @var array
     */
    private $config;

    /**
     * @method __construct
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
        if (Console::isConsole() || ! $this->config['enable']) {
            return;
        }

        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, [$this, 'setHttpStrictTransportSecurity']);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'forceHttpsScheme']);
    }

    /**
     * Set The HTTP Strict Transport Security.
     *
     * @param  MvcEvent $e
     */
    public function setHttpStrictTransportSecurity(MvcEvent $e)
    {
        /** @var $request \Zend\Http\PhpEnvironment\Request */
        $request   = $e->getRequest();
        $uriScheme = $request->getUri()->getScheme();

        /** @var $response \Zend\Http\PhpEnvironment\Response */
        $response = $e->getResponse();

        if (
            ($this->isSchemeHttps($uriScheme) || $this->isGoingToBeForcedToHttps($e)) &&
            isset(
                $this->config['strict_transport_security']['enable'],
                $this->config['strict_transport_security']['value']
            ) &&
            $this->config['strict_transport_security']['enable'] === true
        ) {
            $response->getHeaders()
                     ->addHeaderLine('Strict-Transport-Security: ' . $this->config['strict_transport_security']['value']);
            return;
        }

        // set max-age = 0 to strictly expire it,
        $response->getHeaders()
                 ->addHeaderLine('Strict-Transport-Security: max-age=0');
    }

    /**
     * Force Https Scheme handle.
     *
     * @param  MvcEvent         $e
     */
    public function forceHttpsScheme(MvcEvent $e)
    {
        /** @var $request \Zend\Http\PhpEnvironment\Request */
        $request   = $e->getRequest();
        $uri       = $request->getUri();
        $uriScheme = $uri->getScheme();

        if ($this->isSchemeHttps($uriScheme) || ! $this->isGoingToBeForcedToHttps($e)) {
            return;
        }

        /** @var $response \Zend\Http\PhpEnvironment\Response */
        $response        = $e->getResponse();
        $httpsRequestUri = $uri->setScheme('https')->toString();

        // 307 keeps headers, request method, and request body
        $response->setStatusCode(307);
        $response->getHeaders()
                 ->addHeaderLine('Location', $httpsRequestUri);
        $response->send();

        exit(0);
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
     * @param  MvcEvent $e
     * @return bool
     */
    private function isGoingToBeForcedToHttps(MvcEvent $e)
    {
        if (! $this->config['force_all_routes'] &&
            ! in_array(
                $e->getRouteMatch()->getMatchedRouteName(),
                $this->config['force_specific_routes']
            )
        ) {
            return false;
        }

        return true;
    }
}
