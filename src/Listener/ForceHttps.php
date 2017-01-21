<?php

namespace ForceHttpsModule\Listener;

use Zend\Console\Console;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Client;
use Zend\Http\Header\Origin;
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

        // if has request body, then
        //    a.keep headers, request method, and body
        //    b.call uri with https
        if (! empty($content = $request->getContent())) {

            // keep methods and body
            $client = new Client();
            $client->setMethod($request->getMethod());
            $client->setRawBody($content);

            // keep headers with clean up "Origin" and re-set headers
            /* @var $requestHeaders \Zend\Http\Headers */
            $requestHeaders = $request->getHeaders();
            $headers        = $requestHeaders->toArray();
            unset($headers['Origin']);
            $requestHeaders->clearHeaders();
            $requestHeaders->addHeaders($headers);
            $requestHeaders->addHeader(new Origin($uriScheme . '://' . $uri->getHost()));
            $client->setHeaders($requestHeaders);

            // call uri with https
            $client->setUri($httpsRequestUri);
            $result  = $client->send();

            // send response
            $response->setContent($result->getBody());
            $response->setStatusCode($result->getStatusCode());
            $response->getHeaders()
                     ->addHeaderLine('Content-Type', $headers['Content-Type']);

            $response->send();
            exit(0);
        }

        $response->setStatusCode(302);
        $response->getHeaders()
                 ->addHeaderLine('Location', $httpsRequestUri);
        $response->send();
    }
}
