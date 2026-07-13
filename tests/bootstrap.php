<?php declare(strict_types=1);

/**
 * Bootstrap for unit tests only (no database required).
 */
$omekaPath = dirname(__DIR__, 3);

require_once $omekaPath . '/vendor/autoload.php';
// The Matomo php tracker is a dependency of this module only.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// A module may be installed locally or via composer, the local one first.
$modulePath = function (string $module) use ($omekaPath): string {
    return file_exists($omekaPath . '/modules/' . $module . '/src')
        ? $omekaPath . '/modules/' . $module . '/src'
        : $omekaPath . '/composer-addons/modules/' . $module . '/src';
};

$loader = new \Composer\Autoload\ClassLoader();
$loader->addPsr4('Common\\', $modulePath('Common'));
$loader->addPsr4('AnalyticsSnippet\\', $modulePath('AnalyticsSnippet'));
$loader->addPsr4('AnalyticsSnippetPiwik\\', dirname(__DIR__) . '/src');
$loader->addPsr4('AnalyticsSnippetPiwikTest\\', __DIR__ . '/AnalyticsSnippetPiwikTest');
$loader->register();

error_reporting(E_ALL);
ini_set('display_errors', '1');
