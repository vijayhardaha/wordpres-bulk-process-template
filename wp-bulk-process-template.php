<?php
/**
 * Plugin Name: WP Bulk Process
 * Plugin URI: https://pph.me/vijayhardaha
 * Description: A WordPress custom plugin templates to perform a bulk process with Ajax without worrying about memory exceed or timeout problems.
 * Version: 1.0.0
 * Author: Vijay Hardaha
 * Author URI: https://twitter.com/vijayhardaha
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: wp-bulk-process
 * Domain Path: /languages/
 *
 * @package WP_Bulk_Process
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
