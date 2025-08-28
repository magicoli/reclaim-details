<?php
/**
 * Reclaim Details - PSR-4 Autoloader
 * 
 * @package Reclaim\Details
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PSR-4 Autoloader for Reclaim\Details namespace
 */
spl_autoload_register( function ( $class ) {
	// Project-specific namespace prefix
	$prefix = 'Reclaim\\Details\\';
	
	// Base directory for the namespace prefix
	$base_dir = __DIR__ . '/src/Reclaim/Details/';
	
	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		// No, move to the next registered autoloader
		return;
	}
	
	// Get the relative class name
	$relative_class = substr( $class, $len );
	
	// Replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
	
	// If the file exists, require it
	if ( file_exists( $file ) ) {
		require $file;
	}
} );
