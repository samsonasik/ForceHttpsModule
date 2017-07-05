<?php

namespace ForceHttpsModule;

use Zend\Router\RouteMatch;
use Zend\Expressive\Router\RouteResult;

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
     * Validate Scheme and Forced Https Config
     *
     * @param  string                 $uriScheme
     * @param  RouteMatch|RouteResult $match
     *
     * @return bool
     */
    private function validateSchemeAndToBeForcedHttpsConfig($uriScheme, $match)
    {
        if ($this->isSchemeHttps($uriScheme) || ! $this->isGoingToBeForcedToHttps($match)) {
            return false;
        }

        return true;
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
}
