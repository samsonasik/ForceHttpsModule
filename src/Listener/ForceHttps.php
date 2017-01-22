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

        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'forceHttpsScheme']);
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
        if ($uriScheme === 'https') {
            return;
        }

        if (! $this->config['force_all_routes'] &&
            ! in_array(
                $e->getRouteMatch()->getMatchedRouteName(),
                $this->config['force_specific_routes']
            )
        ) {
            return;
        }

        /** @var $response \Zend\Http\PhpEnvironment\Response */
        $response        = $e->getResponse();
        $httpsRequestUri = $uri->setScheme('https')->toString();

        $response->setStatusCode(307);
        $response->getHeaders()
                 ->addHeaderLine('Location', $httpsRequestUri);
        $response->send();

        exit(0);
    }
}
