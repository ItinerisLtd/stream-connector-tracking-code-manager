<?php
/**
 * Plugin Name:       Stream - Tracking Code Manager
 * Plugin URI:        https://github.com/ItinerisLtd/stream-connector-tracking-code-manager
 * Description:       Tracking code activity connector for Stream (Tracking Code Manager)
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      8.4
 * Author:            Itineris Limited
 * Author URI:        https://www.itineris.co.uk/
 * Text Domain:       stream-connector-tracking-code-manager
 */

declare(strict_types=1);

namespace Itineris\StreamConnectorTrackingCodeManager;

use function class_exists;
use function defined;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

add_filter('wp_stream_connectors', function (array $classes): array {
    if (class_exists('TCMP_Manager')) {
        $classes[] = new TrackingCodeManager();
    }

    return $classes;
});
