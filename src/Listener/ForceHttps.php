<?php

declare(strict_types=1);

namespace ForceHttpsModule\Listener;

use ForceHttpsModule\HttpsTrait;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;

use function defined;
use function sprintf;

use const PHP_SAPI;

class ForceHttps extends AbstractListenerAggregate
{
    use HttpsTrait;

    private array $config;

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param int $priority
     */
    public function attach(EventManagerInterface $eventManager, $priority = 1): void
    {
        if ($this->isInConsole() || ! $this->config['enable']) {
            return;
        }

        $this->listeners[] = $eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'forceHttpsScheme']);
        $this->listeners[] = $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'forceHttpsScheme'], 1000);
    }

    /**
     * Check if currently running in console
     */
    private function isInConsole(): bool
    {
        return PHP_SAPI === 'cli' || defined('STDIN');
    }

    /**
     * Force Https Scheme handle.
     */
    public function forceHttpsScheme(MvcEvent $mvcEvent): void
    {
        /** @var Request $request */
        $request = $mvcEvent->getRequest();
        /** @var Response $response */
        $response = $mvcEvent->getResponse();

        $http = $request->getUri();
        /** @var string  $uriScheme*/
        $uriScheme = $http->getScheme();

        /** @var RouteMatch|null $routeMatch */
        $routeMatch = $mvcEvent->getRouteMatch();
        $response   = $this->setHttpStrictTransportSecurity($uriScheme, $response, $routeMatch);
        if (! $this->isGoingToBeForcedToHttps($routeMatch)) {
            return;
        }

        if ($this->isSchemeHttps($uriScheme)) {
            $uriString       = $http->toString();
            $httpsRequestUri = $this->getFinalhttpsRequestUri($uriString);

            if ($uriString === $httpsRequestUri) {
                return;
            }
        }

        $httpsRequestUri ??= $this->getFinalhttpsRequestUri((string) $http->setScheme('https'));

        // 308 keeps headers, request method, and request body
        $response->setStatusCode(308);
        $response->getHeaders()
                 ->addHeaderLine('Location', $httpsRequestUri);
        $response->send();

        exit(0);
    }

    private function setHttpStrictTransportSecurity(
        string $uriScheme,
        Response $response,
        ?RouteMatch $routeMatch
    ): Response {
        if ($this->isSkippedHttpStrictTransportSecurity($uriScheme, $routeMatch)) {
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
}
