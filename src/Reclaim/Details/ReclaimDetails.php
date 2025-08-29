<?php
/**
 * Reclaim Details Library
 * 
 * Provides "View details" functionality for WordPress plugins using local readme.txt and assets
 * Reusable, plugin-agnostic library - reclaim control of your plugin information display
 * 
 * @package Reclaim\Details
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
	 * Parse WordPress readme.txt format using wp-package-parser
	 */
	private function parse_readme( $content ) {
		// Decode UTF-8 encoding before parsing to prevent double-encoding issues
		$parsed = \WPPackageParser\Parser::parseReadme( utf8_decode( $content ), true ); // true = apply markdown formatting
		
		if ( ! $parsed || ! is_array( $parsed ) ) {
			return array(); // Return empty array if parsing fails
		}
		
		// Convert to our expected format
		$data = array(
			'name' => $parsed['name'] ?? '',
			'contributors' => $parsed['contributors'] ?? array(),
			'tags' => $parsed['tags'] ?? array(),
			'requires_at_least' => $parsed['requires'] ?? '',
			'tested_up_to' => $parsed['tested'] ?? '',
			'stable_tag' => $parsed['stable'] ?? '',
			'short_description' => $parsed['short_description'] ?? '',
			'sections' => $parsed['sections'] ?? array(),
		);
		
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
		
		// Banner and icons from assets
		$plugin_info->banners = $this->get_banners();
		$plugin_info->icons = $this->get_icons();
		
		// Process all sections from readme.txt
		$plugin_info->sections = $this->process_sections();
		
		// Screenshots (if assets exist)
		$plugin_info->screenshots = $this->get_screenshots();
        // echo "<pre>" . print_r($plugin_info->screenshots, true) . "</pre>";
        // die();
        
		return $plugin_info;
	}
	
	/**
	 * Process all readme sections into appropriate tabs
	 */
	private function process_sections() {
		if ( empty( $this->readme_data['sections'] ) ) {
			return array();
		}
		
		$all_sections = $this->readme_data['sections'];
		
        // Use standard section titles (for reference, wp-admin/includes/plugin-install.php)
        $plugins_section_titles = array(
            'description'  => _x( 'Description', 'Plugin installer section title' ),
            'installation' => _x( 'Installation', 'Plugin installer section title' ),
            'faq'          => _x( 'FAQ', 'Plugin installer section title' ),
            'screenshots'  => _x( 'Screenshots', 'Plugin installer section title' ),
            'changelog'    => _x( 'Changelog', 'Plugin installer section title' ),
            'reviews'      => _x( 'Reviews', 'Plugin installer section title' ),
            'other_notes'  => _x( 'Other Notes', 'Plugin installer section title' ),
        );
        
		// Define which sections get their own tabs (matching WordPress core)
		$dedicated_tabs = array_keys( $plugins_section_titles );

        // Set $tab_sections with same keys but empty values
        $tab_sections = array_fill_keys( $dedicated_tabs, null );

        // Extract dedicated tab sections (case-insensitive)
		foreach ( $all_sections as $section_key => $section_content ) {
			$section_key_lower = strtolower( str_replace( ' ', '_', $section_key ) );
			if ( in_array( $section_key_lower, $dedicated_tabs ) && $section_key_lower !== 'description' ) {
				$tab_sections[ $section_key_lower ] = $section_content;
				unset( $all_sections[ $section_key ] ); // Remove from main array
			}
		}
		
		// Build description from remaining sections
		$description_parts = array();
		foreach ( $all_sections as $section_key => $section_content ) {
			if ( empty( trim( $section_content ) ) ) {
				continue;
			}
			
			if ( strtolower( $section_key ) === 'description' ) {
				// Description goes first
				array_unshift( $description_parts, $section_content );
			} else {
				// Other sections get added with headers
				$description_parts[] = "<h4>" . esc_html( $section_key ) . "</h4>\n" . $section_content;
			}
		}
		
		if ( ! empty( $description_parts ) ) {
			$tab_sections['description'] = implode( "\n\n", $description_parts );
		}
		
		// Process screenshots section to replace numbered items with actual images
		if ( ! empty( $tab_sections['screenshots'] ) ) {
			$tab_sections['screenshots'] = $this->process_screenshots_section( $tab_sections['screenshots'] );
		}
		
        $tab_sections = array_filter( $tab_sections ); // Remove empty sections
		return $tab_sections;
	}
	
	/**
	 * Process screenshots section to replace numbered items with actual images
	 */
	private function process_screenshots_section( $content ) {
		// Get available screenshots
		$screenshots = $this->get_screenshots();
		
		if ( empty( $screenshots ) ) {
			return $content; // No screenshots available, return content as-is
		}
		
		// Replace ordered list items with actual images
		// The list items are in order, so we can map them to screenshot numbers
		$screenshot_index = 1;
		
		$content = preg_replace_callback(
			'/<li>([^<]+)<\/li>/',
			function( $matches ) use ( $screenshots, &$screenshot_index ) {
				$caption = trim( $matches[1] );
				
				// If we have a screenshot for this index, replace with image
				if ( isset( $screenshots[ $screenshot_index ] ) ) {
					$src = esc_url( $screenshots[ $screenshot_index ]['src'] );
					$alt = esc_attr( $caption );
					$result = "<li><img src=\"{$src}\" alt=\"{$alt}\" style=\"max-width: 100%; height: auto;\" /><br><em>{$caption}</em></li>";
					$screenshot_index++;
					return $result;
				}
				
				// No screenshot for this index, keep original
				$screenshot_index++;
				return $matches[0];
			},
			$content
		);
		
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
				$num = $matches[1];
				$screenshots[ $num ] = array(
					'src' => plugins_url( 'assets/' . basename( $file ), $this->plugin_file ),
					'caption' => $this->get_screenshot_caption( $num )
				);
			}
		}
		
		ksort( $screenshots );
		return $screenshots;
	}
	
	/**
	 * Get screenshot caption from readme.txt
	 */
	private function get_screenshot_caption( $num ) {
		if ( ! isset( $this->readme_data['sections']['Screenshots'] ) ) {
			return 'Screenshot ' . $num;
		}
		
		$screenshots_content = $this->readme_data['sections']['Screenshots'];
		
		// Handle HTML format (after markdown processing)
		if ( strpos( $screenshots_content, '<li>' ) !== false ) {
			// Extract list items and match by position
			preg_match_all( '/<li>(.*?)<\/li>/s', $screenshots_content, $matches );
			if ( isset( $matches[1][ $num - 1 ] ) ) {
				return strip_tags( trim( $matches[1][ $num - 1 ] ) );
			}
		} else {
			// Plain text format (fallback)
			$lines = explode( "\n", $screenshots_content );
			foreach ( $lines as $line ) {
				if ( preg_match( '/^' . $num . '\.\s*(.+)$/', trim( $line ), $matches ) ) {
					return trim( $matches[1] );
				}
			}
		}
		
		return 'Screenshot ' . $num;
	}
	
	/**
	 * Get plugin banners from assets directory
	 */
	private function get_banners() {
		$banners = array();
		$assets_dir = $this->plugin_dir . '/assets';
		
		if ( ! is_dir( $assets_dir ) ) {
			return $banners;
		}
		
		// High resolution banner (1544x500)
		$banner_2x = $assets_dir . '/banner-1544x500.png';
		if ( file_exists( $banner_2x ) ) {
			$banners['high'] = plugins_url( 'assets/banner-1544x500.png', $this->plugin_file );
		} elseif ( file_exists( str_replace( '.png', '.jpg', $banner_2x ) ) ) {
			$banners['high'] = plugins_url( 'assets/banner-1544x500.jpg', $this->plugin_file );
		}
		
		// Standard resolution banner (772x250)
		$banner_1x = $assets_dir . '/banner-772x250.png';
		if ( file_exists( $banner_1x ) ) {
			$banners['low'] = plugins_url( 'assets/banner-772x250.png', $this->plugin_file );
		} elseif ( file_exists( str_replace( '.png', '.jpg', $banner_1x ) ) ) {
			$banners['low'] = plugins_url( 'assets/banner-772x250.jpg', $this->plugin_file );
		}
		
		return $banners;
	}
	
	/**
	 * Get plugin icons from assets directory
	 */
	private function get_icons() {
		$icons = array();
		$assets_dir = $this->plugin_dir . '/assets';
		
		if ( ! is_dir( $assets_dir ) ) {
			return $icons;
		}
		
		// High resolution icon (256x256)
		$icon_2x = $assets_dir . '/icon-256x256.png';
		if ( file_exists( $icon_2x ) ) {
			$icons['2x'] = plugins_url( 'assets/icon-256x256.png', $this->plugin_file );
		} elseif ( file_exists( str_replace( '.png', '.jpg', $icon_2x ) ) ) {
			$icons['2x'] = plugins_url( 'assets/icon-256x256.jpg', $this->plugin_file );
		}
		
		// Standard resolution icon (128x128)
		$icon_1x = $assets_dir . '/icon-128x128.png';
		if ( file_exists( $icon_1x ) ) {
			$icons['1x'] = plugins_url( 'assets/icon-128x128.png', $this->plugin_file );
		} elseif ( file_exists( str_replace( '.png', '.jpg', $icon_1x ) ) ) {
			$icons['1x'] = plugins_url( 'assets/icon-128x128.jpg', $this->plugin_file );
		}
		
		// SVG icon (preferred)
		$icon_svg = $assets_dir . '/icon.svg';
		if ( file_exists( $icon_svg ) ) {
			$icons['svg'] = plugins_url( 'assets/icon.svg', $this->plugin_file );
		}
		
		return $icons;
	}
}
