<?php
/**
 * WP Bulk Process setup
 *
 * @package WP_Bulk_Process
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main WP_Bulk_Process Class.
 *
 * @class WP_Bulk_Process
 */
final class WP_Bulk_Process {
	/**
	 * The single instance of the class.
	 *
	 * @var WP_Bulk_Process
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Main WP_Bulk_Process Instance.
	 *
	 * Ensures only one instance of WP_Bulk_Process is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @return WP_Bulk_Process - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * WP_Bulk_Process Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * When WP has loaded all plugins, trigger the `wp_bulk_process_loaded` hook.
	 *
	 * This ensures `wp_bulk_process_loaded` is called only after all other plugins
	 * are loaded.
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded() {
		do_action( 'wp_bulk_process_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		register_activation_hook( WP_BULK_PROCESS_PLUGIN_FILE, array( $this, 'install' ) );

		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'admin_notices', array( $this, 'build_dependencies_notice' ) );
		add_action( 'activated_plugin', array( $this, 'activated_plugin' ) );
		add_action( 'deactivated_plugin', array( $this, 'deactivated_plugin' ) );

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -1 );
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Output a admin notice when build dependencies not met.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function build_dependencies_notice() {
		$old_php = version_compare( phpversion(), WP_BULK_PROCESS_MIN_PHP_VERSION, '<' );
		$old_wp  = version_compare( get_bloginfo( 'version' ), WP_BULK_PROCESS_MIN_WP_VERSION, '<' );

		// Both PHP and WordPress up to date version => no notice.
		if ( ! $old_php && ! $old_wp ) {
			return;
		}

		if ( $old_php && $old_wp ) {
			$msg = sprintf(
				/* translators: 1: Minimum PHP version 2: Recommended PHP version 3: Minimum WordPress version */
				__( 'Update required: WP Bulk Process require PHP version %1$s or newer (%2$s or higher recommended) and WordPress version %3$s or newer to work properly. Please update to required version to have best experience.', 'wp-bulk-process' ),
				WP_BULK_PROCESS_MIN_PHP_VERSION,
				WP_BULK_PROCESS_BEST_PHP_VERSION,
				WP_BULK_PROCESS_MIN_WP_VERSION
			);
		} elseif ( $old_php ) {
			$msg = sprintf(
				/* translators: 1: Minimum PHP version 2: Recommended PHP version */
				__( 'Update required: WP Bulk Process require PHP version %1$s or newer (%2$s or higher recommended) to work properly. Please update to required version to have best experience.', 'wp-bulk-process' ),
				WP_BULK_PROCESS_MIN_PHP_VERSION,
				WP_BULK_PROCESS_BEST_PHP_VERSION
			);
		} elseif ( $old_wp ) {
			$msg = sprintf(
				/* translators: %s: Minimum WordPress version */
				__( 'Update required: WP Bulk Process require WordPress version %s or newer to work properly. Please update to required version to have best experience.', 'wp-bulk-process' ),
				WP_BULK_PROCESS_MIN_WP_VERSION
			);
		}

		echo '<div class="error"><p>' . $msg . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Install wp_bulk_process
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function install() {
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 1.0.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				/* translators: 1: error message 2: file name and path 3: line number */
				$error_message = sprintf( __( '%1$s in %2$s on line %3$s', 'wp-bulk-process' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL;
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( $error_message );
				// phpcs:enable
			}
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$this->define( 'WP_BULK_PROCESS_ABSPATH', dirname( WP_BULK_PROCESS_PLUGIN_FILE ) . '/' );
		$this->define( 'WP_BULK_PROCESS_PLUGIN_BASENAME', plugin_basename( WP_BULK_PROCESS_PLUGIN_FILE ) );
		$this->define( 'WP_BULK_PROCESS_VERSION', '1.0.0' );
		$this->define( 'WP_BULK_PROCESS_MIN_PHP_VERSION', '5.3' );
		$this->define( 'WP_BULK_PROCESS_BEST_PHP_VERSION', '5.6' );
		$this->define( 'WP_BULK_PROCESS_MIN_WP_VERSION', '4.0' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Core classes.
		 */
		if ( $this->is_request( 'admin' ) ) {
			include_once WP_BULK_PROCESS_ABSPATH . 'includes/class-wp-bulk-process-admin.php';
		}
	}

	/**
	 * Init WP_Bulk_Process when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_wp_bulk_process_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Init action.
		do_action( 'wp_bulk_process_init' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/wp-bulk-process/wp-bulk-process-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/wp-bulk-process-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'wp-bulk-process' );

		unload_textdomain( 'wp-bulk-process' );
		load_textdomain( 'wp-bulk-process', WP_LANG_DIR . '/wp-bulk-process/wp-bulk-process-' . $locale . '.mo' );
		load_plugin_textdomain( 'wp-bulk-process', false, plugin_basename( dirname( WP_BULK_PROCESS_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WP_BULK_PROCESS_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( WP_BULK_PROCESS_PLUGIN_FILE ) );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Ran when any plugin is activated.
	 *
	 * @since 1.0.0
	 * @param string $filename The filename of the activated plugin.
	 */
	public function activated_plugin( $filename ) {
		// Add you plugin activation code here.
	}

	/**
	 * Ran when any plugin is deactivated.
	 *
	 * @since 1.0.0
	 * @param string $filename The filename of the deactivated plugin.
	 */
	public function deactivated_plugin( $filename ) {
		// Add you plugin deactivation code here.
	}
}
