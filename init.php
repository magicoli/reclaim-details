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

// Load composer dependencies (wp-package-parser, etc.)
require_once __DIR__ . '/vendor/autoload.php';

// PSR-4 Autoloader for Reclaim\Details namespace
spl_autoload_register( function ( $class ) {
	$prefix = 'Reclaim\\Details\\';
	$base_dir = __DIR__ . '/src/Reclaim/Details/';
	
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}
	
	$relative_class = substr( $class, $len );
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
	
	if ( file_exists( $file ) ) {
		require $file;
	}
} );

// Auto-instantiate the library (it will detect the calling plugin automatically)
new \Reclaim\Details\ReclaimDetails();
