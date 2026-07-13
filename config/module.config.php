<?php declare(strict_types=1);

namespace AnalyticsSnippetPiwik;

return [
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
        ],
    ],
    'analyticssnippet' => [
        'trackers' => [
            'matomo' => Tracker\Matomo::class,
        ],
    ],
    'analyticssnippetpiwik' => [
        'config' => [
            'analyticssnippetpiwik_tracker_url' => '',
            'analyticssnippetpiwik_site_id' => '',
            'analyticssnippetpiwik_token_auth' => '',
        ],
    ],
];
