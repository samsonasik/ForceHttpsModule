<?php

namespace ForceHttpsModule\Listener;

use Zend\Console\Console;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Client;
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

        $response        = $e->getResponse();
        $uriWithScheme   = $uri->setScheme('https');
        $httpsRequestUri = $uriWithScheme->toString();

        // if has request body, then
        //    a.keep headers, request method, and body
        //    b.call uri with https
        if (! empty($content = $request->getContent())) {

            $requestMethod = $request->getMethod();
            $client = new Client();
            $client->setUri($httpsRequestUri);
            $client->setMethod($requestMethod);
            $client->setRawBody($content);

            $headers = $request->getHeaders();
            $clientHeaders = [];
            foreach ($headers->toArray() as $key => $value) {
                if ($key === 'Origin') {
                    $value = $uriWithScheme->getScheme() . '://' . $uriWithScheme->getHost();
                }
                $clientHeaders[$key] = $value;
            }
            $client->setHeaders($clientHeaders);

            $result  = $client->send();
            $response->setContent($result->getBody());
            $response->setStatusCode($result->getStatusCode());

            $response->send();
            exit(0);
        }

        $response->setStatusCode(302);
        $response->getHeaders()
                 ->addHeaderLine('Location', $httpsRequestUri);
        $response->send();
    }
}
