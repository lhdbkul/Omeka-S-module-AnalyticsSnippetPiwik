<?php declare(strict_types=1);

namespace AnalyticsSnippetPiwikTest\Tracker;

use MatomoTracker;

/**
 * Matomo tracker that records the request instead of sending it.
 */
class SpyMatomoTracker extends MatomoTracker
{
    /**
     * @var array
     */
    public $requests = [];

    protected function sendRequest(string $url, string $method = 'GET', $data = null, bool $force = false): string
    {
        $this->requests[] = $url;
        return '';
    }
}
