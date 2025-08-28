<?php
/**
 * Reclaim Details - Easy Integration
 * 
 * Include this file to automatically initialize Reclaim Details for your plugin.
 * 
 * Usage:
 *   require_once 'path/to/reclaim-details/init.php';
 * 
 * That's it! The library will auto-detect your plugin and set up "View details" functionality.
 * 
 * @package Reclaim\Details
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the autoloader
require_once __DIR__ . '/autoload.php';

// Auto-instantiate the library (it will detect the calling plugin automatically)
new \Reclaim\Details\ReclaimDetails();
