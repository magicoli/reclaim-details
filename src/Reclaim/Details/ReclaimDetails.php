<?php
/**
 * Reclaim Details Library
 * 
 * Provides "View details" functionality for WordPress plugins using local readme.txt and assets
 * Reusable, plugin-agnostic library - reclaim control of your plugin information display
 * 
 * @package Reclaim\Details
 * @version 1.0.0
 */

namespace Reclaim\Details;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReclaimDetails {
	
	private $plugin_file;
	private $plugin_slug;
	private $plugin_dir;
	private $plugin_data;
	private $readme_data;
	
	/**
	 * Constructor - Auto-detects calling plugin
	 * 
	 * @param string|null $plugin_file Optional: Full path to main plugin file. If null, auto-detects.
	 */
	public function __construct( $plugin_file = null ) {
		$this->plugin_file = $plugin_file ?: $this->detect_calling_plugin();
		$this->plugin_dir = dirname( $this->plugin_file );
		$this->plugin_slug = basename( $this->plugin_dir );
		
		// Initialize
		$this->load_plugin_data();
		$this->load_readme_data();
		$this->init_hooks();
	}
	
	/**
	 * Auto-detect the calling plugin file
	 */
	private function detect_calling_plugin() {
		// Get the calling file from backtrace
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		
		// Look for the first file that's in a plugin directory
		foreach ( $backtrace as $trace ) {
			if ( isset( $trace['file'] ) ) {
				$file = $trace['file'];
				
				// Check if this file is in a plugin directory
				if ( strpos( $file, WP_PLUGIN_DIR ) === 0 ) {
					// Find the main plugin file
					$plugin_dir = $this->find_plugin_root( $file );
					return $this->find_main_plugin_file( $plugin_dir );
				}
			}
		}
		
		// Fallback: try to detect from current file location
		$current_file = __FILE__;
		$plugin_dir = $this->find_plugin_root( $current_file );
		return $this->find_main_plugin_file( $plugin_dir );
	}
	
	/**
	 * Find the plugin root directory
	 */
	private function find_plugin_root( $file ) {
		$dir = dirname( $file );
		
		// Keep going up until we find a directory directly under WP_PLUGIN_DIR
		while ( dirname( $dir ) !== WP_PLUGIN_DIR && $dir !== dirname( $dir ) ) {
			$dir = dirname( $dir );
		}
		
		return $dir;
	}
	
	/**
	 * Find the main plugin file in a directory
	 */
	private function find_main_plugin_file( $plugin_dir ) {
		$plugin_slug = basename( $plugin_dir );
		
		// Most common pattern: plugin-name/plugin-name.php
		$main_file = $plugin_dir . '/' . $plugin_slug . '.php';
		if ( file_exists( $main_file ) && $this->is_main_plugin_file( $main_file ) ) {
			return $main_file;
		}
		
		// Scan for PHP files with plugin headers
		$php_files = glob( $plugin_dir . '/*.php' );
		foreach ( $php_files as $file ) {
			if ( $this->is_main_plugin_file( $file ) ) {
				return $file;
			}
		}
		
		// Fallback
		return $plugin_dir . '/' . $plugin_slug . '.php';
	}
	
	/**
	 * Check if a file is a main plugin file (has plugin headers)
	 */
	private function is_main_plugin_file( $file ) {
		if ( ! file_exists( $file ) ) {
			return false;
		}
		
		$content = file_get_contents( $file, false, null, 0, 8192 ); // Read first 8KB
		return strpos( $content, 'Plugin Name:' ) !== false;
	}
	
	/**
	 * Load plugin data from main file headers
	 */
	private function load_plugin_data() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$this->plugin_data = get_plugin_data( $this->plugin_file );
	}
	
	/**
	 * Parse readme.txt file
	 */
	private function load_readme_data() {
		$readme_file = $this->plugin_dir . '/readme.txt';
		
		if ( ! file_exists( $readme_file ) ) {
			$this->readme_data = array();
			return;
		}
		
		$readme_content = file_get_contents( $readme_file );
		$this->readme_data = $this->parse_readme( $readme_content );
	}
	
	/**
	 * Parse WordPress readme.txt format
	 */
	private function parse_readme( $content ) {
		$data = array();
		
		// Parse headers
		preg_match( '/=== (.+) ===/', $content, $matches );
		$data['name'] = isset( $matches[1] ) ? trim( $matches[1] ) : '';
		
		// Parse metadata
		$lines = explode( "\n", $content );
		foreach ( $lines as $line ) {
			if ( preg_match( '/^([^:]+):\s*(.+)$/', trim( $line ), $matches ) ) {
				$key = strtolower( str_replace( ' ', '_', trim( $matches[1] ) ) );
				$data[ $key ] = trim( $matches[2] );
			}
		}
		
		// Parse sections
		$sections = array();
		$current_section = '';
		$in_section = false;
		
		foreach ( $lines as $line ) {
			$line = trim( $line );
			
			// Section headers
			if ( preg_match( '/^== (.+) ==$/', $line, $matches ) ) {
				$current_section = strtolower( str_replace( ' ', '_', $matches[1] ) );
				$sections[ $current_section ] = '';
				$in_section = true;
				continue;
			}
			
			// Content
			if ( $in_section && ! empty( $line ) && ! preg_match( '/^=/', $line ) ) {
				$sections[ $current_section ] .= $line . "\n";
			}
		}
		
		$data['sections'] = $sections;
		
		return $data;
	}
	
	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Add "View details" to plugin row meta (in description column)
		add_filter( 'plugin_row_meta', array( $this, 'add_view_details_link' ), 10, 2 );
		
		// Handle plugin information requests
		add_filter( 'plugins_api', array( $this, 'handle_plugin_info' ), 10, 3 );
	}
	
	/**
	 * Add "View details" link to plugin row meta (description column)
	 */
	public function add_view_details_link( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( $this->plugin_file ) !== $plugin_file ) {
			return $plugin_meta;
		}
		
		// Create the "View details" link using WordPress's native system
		$details_url = add_query_arg( array(
			'tab'       => 'plugin-information',
			'plugin'    => $this->plugin_slug,
			'TB_iframe' => 'true',
			'width'     => '772',
			'height'    => '550'
		), admin_url( 'plugin-install.php' ) );
		
		$plugin_meta[] = '<a href="' . esc_url( $details_url ) . '" class="thickbox open-plugin-details-modal">' . __( 'View details' ) . '</a>';
		
		return $plugin_meta;
	}
	
	/**
	 * Handle WordPress plugins_api requests
	 */
	public function handle_plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}
		
		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}
		
		return $this->build_plugin_info();
	}
	
	/**
	 * Build plugin information object from readme.txt and plugin headers
	 */
	private function build_plugin_info() {
		$plugin_info = new \stdClass();
		
		// Basic information from plugin headers
		$plugin_info->name = $this->plugin_data['Name'];
		$plugin_info->slug = $this->plugin_slug;
		$plugin_info->version = $this->plugin_data['Version'];
		$plugin_info->author = '<a href="' . esc_url( $this->plugin_data['AuthorURI'] ) . '">' . $this->plugin_data['Author'] . '</a>';
		$plugin_info->homepage = $this->plugin_data['PluginURI'];
		$plugin_info->short_description = $this->plugin_data['Description'];
		
		// WordPress compatibility from readme.txt
		$plugin_info->requires = isset( $this->readme_data['requires_at_least'] ) ? $this->readme_data['requires_at_least'] : '5.0';
		$plugin_info->tested = isset( $this->readme_data['tested_up_to'] ) ? $this->readme_data['tested_up_to'] : '6.8';
		$plugin_info->requires_php = isset( $this->readme_data['requires_php'] ) ? $this->readme_data['requires_php'] : '7.4';
		$plugin_info->stable_tag = isset( $this->readme_data['stable_tag'] ) ? $this->readme_data['stable_tag'] : $this->plugin_data['Version'];
		
		// Metadata
		$plugin_info->last_updated = date( 'Y-m-d' );
		$plugin_info->added = '2025-08-28';
		$plugin_info->download_link = $this->plugin_data['PluginURI'] . '/releases/latest';
		
		// Sections from readme.txt
		$plugin_info->sections = array();
		
		if ( isset( $this->readme_data['sections']['description'] ) ) {
			$plugin_info->sections['description'] = $this->format_section_content( $this->readme_data['sections']['description'] );
		}
		
		if ( isset( $this->readme_data['sections']['installation'] ) ) {
			$plugin_info->sections['installation'] = $this->format_section_content( $this->readme_data['sections']['installation'] );
		}
		
		if ( isset( $this->readme_data['sections']['changelog'] ) ) {
			$plugin_info->sections['changelog'] = $this->format_section_content( $this->readme_data['sections']['changelog'] );
		}
		
		if ( isset( $this->readme_data['sections']['faq'] ) ) {
			$plugin_info->sections['faq'] = $this->format_section_content( $this->readme_data['sections']['faq'] );
		}
		
		// Screenshots (if assets exist)
		$plugin_info->screenshots = $this->get_screenshots();
		
		return $plugin_info;
	}
	
	/**
	 * Format section content (convert basic markdown-like syntax to HTML)
	 */
	private function format_section_content( $content ) {
		$content = trim( $content );
		
		// Convert = headings =
		$content = preg_replace( '/^= (.+) =$/', '<h3>$1</h3>', $content );
		
		// Convert * lists
		$content = preg_replace( '/^\* (.+)$/m', '<li>$1</li>', $content );
		
		// Wrap consecutive <li> in <ul>
		$content = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $content );
		
		// Convert line breaks to paragraphs
		$content = wpautop( $content );
		
		return $content;
	}
	
	/**
	 * Get screenshots from assets directory
	 */
	private function get_screenshots() {
		$screenshots = array();
		$assets_dir = $this->plugin_dir . '/assets';
		
		if ( ! is_dir( $assets_dir ) ) {
			return $screenshots;
		}
		
		// Look for screenshot files (screenshot-1.png, screenshot-2.jpg, etc.)
		$files = glob( $assets_dir . '/screenshot-*.{png,jpg,jpeg,gif}', GLOB_BRACE );
		
		foreach ( $files as $file ) {
			if ( preg_match( '/screenshot-(\d+)\./', basename( $file ), $matches ) ) {
				$screenshots[ $matches[1] ] = array(
					'src' => plugins_url( 'assets/' . basename( $file ), $this->plugin_file ),
					'caption' => 'Screenshot ' . $matches[1]
				);
			}
		}
		
		ksort( $screenshots );
		return $screenshots;
	}
}
