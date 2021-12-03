<?php
/**
 * Plugin Name: WordPress Bulk Process
 * Plugin URI: https://github.com/vijayhardaha/wordpres-bulk-process-template
 * Description: A WordPress plugin template to perform a bulk process with Ajax without worrying about memory exceed or timeout problems.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://twitter.com/vijayhardaha
 * Text Domain: wp-bulk-process
 * Domain Path: /languages/
 * Requires at least: 5.4
 * Requires PHP: 5.6
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WP_Bulk_Process
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! defined( 'WP_BULK_PROCESS_PLUGIN_FILE' ) ) {
	define( 'WP_BULK_PROCESS_PLUGIN_FILE', __FILE__ );
}

// Include the main WP Bulk Process class.
if ( ! class_exists( 'WP_Bulk_Process', false ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-wp-bulk-process.php';
}

/**
 * Returns the main instance of WP_Bulk_Process.
 *
 * @since  1.0.0
 * @return WP_Bulk_Process
 */
function wp_bulk_process() {
	return WP_Bulk_Process::instance();
}

// Global for backwards compatibility.
$GLOBALS['wp_bulk_process'] = wp_bulk_process();
