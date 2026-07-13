<?php declare(strict_types=1);

namespace AnalyticsSnippetPiwikTest\Tracker;

use Common\Stdlib\Cipher;
use Laminas\Http\Response;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\ViewEvent;
use PHPUnit\Framework\TestCase;

class MatomoTest extends TestCase
{
    const TRACKER_URL = 'http://example.org/matomo/matomo.php';

    const URL = 'http://example.org/s/test/page';

    /**
     * @var array
     */
    protected $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    protected function buildServices(array $settings = [], string $secretKey = ''): ServiceManager
    {
        $viewHelpers = new ServiceManager();
        $viewHelpers->setService('Identity', fn () => null);

        $services = new ServiceManager();
        $services->setService('Omeka\Settings', new FakeSettings($settings));
        $services->setService('Omeka\Cipher', new Cipher($secretKey === '' ? [] : [$secretKey]));
        $services->setService('ViewHelperManager', $viewHelpers);

        return $services;
    }

    protected function track(array $settings, string $type = 'json', string $secretKey = ''): SpyMatomo
    {
        $response = new Response();
        $response->setContent('{}');
        $viewEvent = new ViewEvent();
        $viewEvent->setResponse($response);

        $tracker = new SpyMatomo();
        $tracker->setServiceLocator($this->buildServices($settings, $secretKey));
        $tracker->track(self::URL, $type, $viewEvent);

        return $tracker;
    }

    protected function defaultSettings(array $settings = []): array
    {
        return $settings + [
            'analyticssnippetpiwik_site_id' => '3',
            'analyticssnippetpiwik_tracker_url' => self::TRACKER_URL,
        ];
    }

    public function testHtmlTypeIsIgnored(): void
    {
        $tracker = $this->track($this->defaultSettings(), 'html');

        $this->assertNull($tracker->tracker);
    }

    public function testMissingSiteIdDoesNotTrack(): void
    {
        $tracker = $this->track([
            'analyticssnippetpiwik_tracker_url' => self::TRACKER_URL,
        ]);

        $this->assertNull($tracker->tracker);
    }

    public function testMissingTrackerUrlDoesNotTrack(): void
    {
        $tracker = $this->track([
            'analyticssnippetpiwik_site_id' => '3',
        ]);

        $this->assertNull($tracker->tracker);
    }

    public function testStringSiteIdIsCastToInt(): void
    {
        $tracker = $this->track($this->defaultSettings());

        $this->assertSame(3, $tracker->createArgs['site_id']);
        $this->assertSame(self::TRACKER_URL, $tracker->createArgs['tracker_url']);
    }

    public function testRequestIsSentOnce(): void
    {
        $tracker = $this->track($this->defaultSettings());

        $this->assertCount(1, $tracker->tracker->requests);
        $this->assertStringStartsWith(self::TRACKER_URL, $tracker->tracker->requests[0]);
    }

    public function testRequestContainsUrlAndSiteId(): void
    {
        $tracker = $this->track($this->defaultSettings());
        $request = $tracker->tracker->requests[0];

        $this->assertStringContainsString('idsite=3', $request);
        $this->assertStringContainsString('url=' . urlencode(self::URL), $request);
    }

    public function testRequestContainsAnonymousUserId(): void
    {
        $tracker = $this->track($this->defaultSettings());

        $this->assertStringContainsString('user_id=0', $tracker->tracker->requests[0]);
    }

    public function testMissingServerDataDoesNotFail(): void
    {
        unset($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);

        $tracker = $this->track($this->defaultSettings());

        $this->assertCount(1, $tracker->tracker->requests);
    }

    public function testServerDataIsForwarded(): void
    {
        $_SERVER['HTTP_REFERER'] = 'http://example.org/referrer';
        $_SERVER['HTTP_USER_AGENT'] = 'Test agent';
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';

        $tracker = $this->track($this->defaultSettings());

        $this->assertStringContainsString(
            'urlref=' . urlencode('http://example.org/referrer'),
            $tracker->tracker->requests[0]
        );
        // The user agent is appended by MatomoTracker::sendRequest(), so it is
        // not part of the tracked url.
        $this->assertSame('Test agent', $tracker->tracker->userAgent);
        $this->assertSame('203.0.113.5', $tracker->tracker->ip);
    }

    public function testClientIpRequiresTokenAuthToBeSent(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';

        $withoutToken = $this->track($this->defaultSettings());
        $this->assertStringNotContainsString('cip=', $withoutToken->tracker->requests[0]);

        $withToken = $this->track($this->defaultSettings([
            'analyticssnippetpiwik_token_auth' => str_repeat('a', 32),
        ]));
        $this->assertStringContainsString('cip=203.0.113.5', $withToken->tracker->requests[0]);
    }

    public function testTokenAuthIsSetWhenConfigured(): void
    {
        $tracker = $this->track($this->defaultSettings([
            'analyticssnippetpiwik_token_auth' => str_repeat('a', 32),
        ]));

        $this->assertSame(str_repeat('a', 32), $tracker->tracker->token_auth);
    }

    public function testTokenAuthIsAbsentWhenNotConfigured(): void
    {
        $tracker = $this->track($this->defaultSettings());

        $this->assertFalse($tracker->tracker->token_auth);
    }

    public function testEncryptedTokenAuthIsDecrypted(): void
    {
        $secretKey = base64_encode(str_repeat('k', SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        $token = str_repeat('b', 32);
        $encrypted = (new Cipher([$secretKey]))->encrypt($token);

        $this->assertStringStartsWith('sodium:', $encrypted);

        $tracker = $this->track($this->defaultSettings([
            'analyticssnippetpiwik_token_auth' => $encrypted,
        ]), 'json', $secretKey);

        $this->assertSame($token, $tracker->tracker->token_auth);
    }

    /**
     * @dataProvider providerNotHtmlTypes
     */
    public function testNotHtmlTypeIsTrackedAsPageTitle(string $type): void
    {
        $tracker = $this->track($this->defaultSettings(), $type);

        $this->assertStringContainsString('action_name=' . $type, $tracker->tracker->requests[0]);
    }

    public function providerNotHtmlTypes(): array
    {
        return [
            'json' => ['json'],
            'xml' => ['xml'],
            'undefined' => ['undefined'],
        ];
    }
}
