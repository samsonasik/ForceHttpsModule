<?php

namespace ForceHttpsModule\Listener;

use Zend\Console\Console;
use Zend\Console\Console;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Uri\Uri;

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
        if (Console::isConsole()) {
            return;
        }

        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'forceHttps']);
    }

    public function forceHttps(MvcEvent $e)
    {
        if (! $this->config['enable']) {
            return;
        }

        $uri       = $e->getRequest()->getUri();
        $uriScheme = $uri->getScheme();
        if ($uriScheme === 'https') {
            return;
        }

        $httpsRequestUri = $uri->setScheme('https')->getRequestUri();
        if ($this->config['force_all_routes']) {
            return $this->redirectWithHttps($e);
        }

        $routeName = $e->getRouteMatch()->getMatchedRouteName();
        if (! in_array($routeName, $this->config['force_specific_routes'])) {
            return;
        }

        return $this->redirectWithHttps($e);
    }

    private function redirectWithHttps(MvcEvent $e)
    {
        $response = $e->getResponse();
        $response->setStatusCode(302);
        $response->getHeaders()
                 ->addHeaderLine('Location', $httpsRequestUri);

        return $e->stopPropagation();
    }
}
