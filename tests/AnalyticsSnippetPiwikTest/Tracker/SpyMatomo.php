<?php declare(strict_types=1);

namespace AnalyticsSnippetPiwikTest\Tracker;

use AnalyticsSnippetPiwik\Tracker\Matomo;
use MatomoTracker;

/**
 * Matomo tracker of the module that builds a spy instead of a real tracker.
 */
class SpyMatomo extends Matomo
{
    /**
     * @var SpyMatomoTracker|null
     */
    public $tracker;

    /**
     * @var array
     */
    public $createArgs = [];

    protected function createTracker(int $siteId, string $trackerUrl): MatomoTracker
    {
        $this->createArgs = ['site_id' => $siteId, 'tracker_url' => $trackerUrl];
        $this->tracker = new SpyMatomoTracker($siteId, $trackerUrl);
        return $this->tracker;
    }
}
