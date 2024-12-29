<?php

declare(strict_types=1);

namespace ForceHttpsModule;

use Laminas\Router\RouteMatch;
use Mezzio\Router\RouteResult;

use function in_array;
use function strpos;
use function substr_replace;

trait HttpsTrait
{
    private bool $needsWwwPrefix = false;

    private bool $alreadyHasWwwPrefix = false;

    private function isSchemeHttps(string $uriScheme): bool
    {
        return $uriScheme === 'https';
    }

    /**
     * Check Config if is going to be forced to https.
     */
    private function isGoingToBeForcedToHttps(RouteMatch|RouteResult|null $match = null): bool
    {
        if ($match === null || ($match instanceof RouteResult && $match->isFailure())) {
            return $this->config['allow_404'] ?? false;
        }

        $matchedRouteName = $match->getMatchedRouteName();

        if ($this->config['force_all_routes']) {
            return ! (! empty($this->config['exclude_specific_routes'])
            && in_array($matchedRouteName, $this->config['exclude_specific_routes']));
        }

        return in_array($matchedRouteName, $this->config['force_specific_routes']);
    }

    /**
     * Check if Setup Strict-Transport-Security need to be skipped.
     */
    private function isSkippedHttpStrictTransportSecurity(
        string $uriScheme,
        RouteMatch|RouteResult|null $match = null
    ): bool {
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
    private function withWwwPrefixWhenRequired(string $httpsRequestUri): string
    {
        $this->needsWwwPrefix      = $this->config['add_www_prefix'] ?? false;
        $this->alreadyHasWwwPrefix = strpos($httpsRequestUri, 'www.', 8) === 8;

        if (! $this->needsWwwPrefix || $this->alreadyHasWwwPrefix) {
            return $httpsRequestUri;
        }

        return substr_replace($httpsRequestUri, 'www.', 8, 0);
    }

    /**
     * Remove www. prefix when use remove_www_prefix = true
     * It only works if previous's config 'add_www_prefix' => false
     */
    private function withoutWwwPrefixWhenNotRequired(string $httpsRequestUri): string
    {
        if ($this->needsWwwPrefix) {
            return $httpsRequestUri;
        }

        $removeWwwPrefix = $this->config['remove_www_prefix'] ?? false;
        if (! $removeWwwPrefix || ! $this->alreadyHasWwwPrefix) {
            return $httpsRequestUri;
        }

        return substr_replace($httpsRequestUri, '', 8, 4);
    }

    /**
     * Get Final Request Uri with configured with or without www prefix
     */
    private function getFinalhttpsRequestUri(string $httpsRequestUri): string
    {
        $httpsRequestUri = $this->withWwwPrefixWhenRequired($httpsRequestUri);

        return $this->withoutWwwPrefixWhenNotRequired($httpsRequestUri);
    }
}
