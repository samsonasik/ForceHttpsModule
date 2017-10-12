<?php

namespace ForceHttpsModule;

use Psr\Http\Message\ResponseInterface;
use Zend\Router\RouteMatch;
use Zend\Expressive\Router\RouteResult;
use Zend\Http\PhpEnvironment\Response;

trait HttpsTrait
{
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
     * @param  RouteMatch|RouteResult $match
     *
     * @return bool
     */
    private function isGoingToBeForcedToHttps($match)
    {
        if (! $this->config['force_all_routes'] &&
            ! \in_array(
                $match->getMatchedRouteName(),
                $this->config['force_specific_routes']
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Check if Setup Strict-Transport-Security need to be skipped.
     *
     * @param string                     $uriScheme
     * @param RouteMatch|RouteResult     $match
     * @param Response|ResponseInterface $response
     *
     * @return bool
     */
    private function isSkippedHttpStrictTransportSecurity($uriScheme, $match, $response)
    {
        return ! $this->isSchemeHttps($uriScheme) ||
            ! $this->isGoingToBeForcedToHttps($match) ||
            ! isset(
                $this->config['strict_transport_security']['enable'],
                $this->config['strict_transport_security']['value']
            );
    }

    /**
     * Add www. prefix when required in the config
     *
     * @param  string $httpsRequestUri
     * @return string
     */
    private function withWwwPrefixWhenRequired($httpsRequestUri)
    {
        if (
            ! isset($this->config['add_www_prefix']) ||
            ! $this->config['add_www_prefix'] ||
            (
                $this->config['add_www_prefix'] === true &&
                \substr($httpsRequestUri, 8, 4) === 'www.'
            )
        ) {
            return $httpsRequestUri;
        }

        return \substr_replace($httpsRequestUri, 'www.', 8, 0);
    }

    private function withoutWwwPrefixWhenNotRequired($httpsRequestUri)
    {
        if (isset($this->config['add_www_prefix']) && $this->config['add_www_prefix'] === true) {
            return $httpsRequestUri;
        }

        if (
            ! isset($this->config['remove_www_prefix']) ||
            ! $this->config['remove_www_prefix'] ||
            (
                $this->config['remove_www_prefix'] === true &&
                \substr($httpsRequestUri, 8, 4) !== 'www.'
            )
        ) {
            return $httpsRequestUri;
        }

        return \substr_replace($httpsRequestUri, '', 8, 4);
    }
}
