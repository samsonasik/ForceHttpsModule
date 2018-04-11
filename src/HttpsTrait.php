<?php

declare(strict_types=1);

namespace ForceHttpsModule;

use Psr\Http\Message\ResponseInterface;
use Zend\Expressive\Router\RouteResult;
use Zend\Http\PhpEnvironment\Response;
use Zend\Router\RouteMatch;

trait HttpsTrait
{
    private function isSchemeHttps(string $uriScheme) : bool
    {
        return $uriScheme === 'https';
    }

    /**
     * Check Config if is going to be forced to https.
     *
     * @param  RouteMatch|RouteResult $match
     */
    private function isGoingToBeForcedToHttps($match) : bool
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
     * @param RouteMatch|RouteResult     $match
     * @param Response|ResponseInterface $response
     *
     */
    private function isSkippedHttpStrictTransportSecurity(string $uriScheme, $match, $response) : bool
    {
        return ! $this->isSchemeHttps($uriScheme) ||
            ! $this->isGoingToBeForcedToHttps($match) ||
            ! isset(
                $this->config['strict_transport_security']['enable'],
                $this->config['strict_transport_security']['value']
            );
    }

    /**
     * Add www. prefix when use add_www_prefix = true
     */
    private function withWwwPrefixWhenRequired(string $httpsRequestUri) : string
    {
        $addWwwPrefix        = $this->config['add_www_prefix'] ?? false;
        $alreadyHasWwwPrefix = \substr($httpsRequestUri, 8, 4) === 'www.';

        if (! $addWwwPrefix || $alreadyHasWwwPrefix) {
            return $httpsRequestUri;
        }

        return \substr_replace($httpsRequestUri, 'www.', 8, 0);
    }

    /**
     * Remove www. prefix when use remove_www_prefix = true
     * It only works if previous's config 'add_www_prefix' => false
     */
    private function withoutWwwPrefixWhenNotRequired(string $httpsRequestUri) : string
    {
        $addWwwPrefix = $this->config['add_www_prefix'] ?? false;
        if ($addWwwPrefix) {
            return $httpsRequestUri;
        }

        $removeWwwPrefix     = $this->config['remove_www_prefix'] ?? false;
        $alreadyHasWwwPrefix = \substr($httpsRequestUri, 8, 4) === 'www.';

        if (! $removeWwwPrefix || ! $alreadyHasWwwPrefix) {
            return $httpsRequestUri;
        }

        return \substr_replace($httpsRequestUri, '', 8, 4);
    }

    /**
     * Get Final Request Uri with configured with or without www prefix
     *
     * @param string $httpsRequestUri
     * @return string
     */
    private function getFinalhttpsRequestUri(string $httpsRequestUri) : string
    {
        $httpsRequestUri = $this->withWwwPrefixWhenRequired($httpsRequestUri);
        $httpsRequestUri = $this->withoutWwwPrefixWhenNotRequired($httpsRequestUri);

        return $httpsRequestUri;
    }
}
