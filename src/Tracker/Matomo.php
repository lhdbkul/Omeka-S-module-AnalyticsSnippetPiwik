<?php declare(strict_types=1);

namespace AnalyticsSnippetPiwik\Tracker;

use AnalyticsSnippet\Tracker\AbstractTracker;
use Laminas\EventManager\Event;
use MatomoTracker;

class Matomo extends AbstractTracker
{
    public function track($url, $type, Event $event): void
    {
        if ($type !== 'html') {
            $this->trackNotInlineScript($url, $type, $event);
        }
    }

    /**
     * @link https://matomo.org/docs/tracking-api
     */
    protected function trackNotInlineScript($url, $type, Event $event): void
    {
        $settings = $this->services->get('Omeka\Settings');
        $siteId = $settings->get('analyticssnippetpiwik_site_id');
        $trackerUrl = $settings->get('analyticssnippetpiwik_tracker_url');
        if (empty($siteId) || empty($trackerUrl)) {
            return;
        }

        // The settings are stored as strings and the server data may be null,
        // but MatomoTracker is typed strictly, so all values must be cast.
        $ip = (string) $this->getClientIp();
        $userId = (string) $this->getUserId();
        $referrer = (string) $this->getUrlReferrer();
        $userAgent = (string) $this->getUserAgent();

        $matomoTracker = $this->createTracker((int) $siteId, (string) $trackerUrl);

        $matomoTracker
            ->setUrl((string) $url)
            ->setUrlReferrer($referrer)
            ->setIp($ip)
            ->setUserAgent($userAgent)
            ->setCustomTrackingParameter('user_id', $userId);

        // Specify an API token with at least Admin permission, so the Visitor
        // IP address can be recorded
        // Learn more about token_auth: https://matomo.org/faq/general/faq_114/
        $tokenAuth = $this->services->get('Omeka\Cipher')
            ->decrypt((string) $settings->get('analyticssnippetpiwik_token_auth'));
        if ($tokenAuth) {
            $matomoTracker->setTokenAuth($tokenAuth);
        }

        // You can manually set the visitor details (resolution, time, plugins,
        // etc.)
        // See all other ->set* functions available in the MatomoTracker.php file
        // $matomoTracker->setResolution(1600, 1400);

        // Sends Tracker request via http
        @$matomoTracker->doTrackPageView($type);

        // Tracks an event
        // $matomoTracker->doTrackEvent($category, $action, $name, $value);

        // Tracks an internal Site Search query, and optionally tracks the
        // Search Category, and Search results Count.
        // $matomoTracker->doTrackSiteSearch($keyword, $category, $countResults);

        // Tracks a download or outlink
        // $matomoTracker->doTrackAction($actionUrl, $actionType);

        // You can also track Goal conversions
        // $matomoTracker->doTrackGoal($idGoal = 1, $revenue = 42);
    }

    protected function trackError($url, $type, Event $event): void
    {
        $this->trackNotInlineScript($url, 'error', $event);
    }

    /**
     * Build the Matomo tracker, so it can be replaced during tests.
     */
    protected function createTracker(int $siteId, string $trackerUrl): MatomoTracker
    {
        return new MatomoTracker($siteId, $trackerUrl);
    }
}
