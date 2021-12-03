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
	 * This class instance.
	 *
	 * @var WP_Bulk_Process single instance of this class.
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Admin notices to add.
	 *
	 * @var array Array of admin notices.
	 * @since 1.0.0
	 */
	private $notices = array();

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
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->define_constants();

		register_activation_hook( WP_BULK_PROCESS_PLUGIN_FILE, array( $this, 'activation_check' ) );

		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_init', array( $this, 'add_plugin_notices' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );

		// If the environment check fails, initialize the plugin.
		if ( $this->is_environment_compatible() ) {
			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		}
	}

	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot clone instances of %s.', esc_html( get_class( $this ) ) ), '1.0.0' );
	}

	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot unserialize instances of %s.', esc_html( get_class( $this ) ) ), '1.0.0' );
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
				// phpcs:enable WordPress.PHP.DevelopmentFunctions
			}
		}
	}

	/**
	 * Define WC Constants.
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		$plugin_data = get_plugin_data( WP_BULK_PROCESS_PLUGIN_FILE );
		$this->define( 'WP_BULK_PROCESS_ABSPATH', dirname( WP_BULK_PROCESS_PLUGIN_FILE ) . '/' );
		$this->define( 'WP_BULK_PROCESS_PLUGIN_BASENAME', plugin_basename( WP_BULK_PROCESS_PLUGIN_FILE ) );
		$this->define( 'WP_BULK_PROCESS_PLUGIN_NAME', $plugin_data['Name'] );
		$this->define( 'WP_BULK_PROCESS_VERSION', $plugin_data['Version'] );
		$this->define( 'WP_BULK_PROCESS_MIN_PHP_VERSION', $plugin_data['RequiresPHP'] );
		$this->define( 'WP_BULK_PROCESS_MIN_WP_VERSION', $plugin_data['RequiresWP'] );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 *
	 * @since 1.0.0
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
	 *
	 * @since 1.0.0
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
	 * Checks the server environment and other factors and deactivates plugins as necessary.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 *
	 * @since 1.0.0
	 */
	public function activation_check() {
		if ( ! $this->is_environment_compatible() ) {
			$this->deactivate_plugin();
			wp_die(
				sprintf(
					/* translators: %s Plugin Name */
					esc_html__(
						'%1$s could not be activated. %2$s',
						'wp-bulk-process'
					),
					esc_html( WP_BULK_PROCESS_PLUGIN_NAME ),
					esc_html( $this->get_environment_message() )
				)
			);
		}
	}

	/**
	 * Checks the environment on loading WordPress, just in case the environment changes after activation.
	 *
	 * @since 1.0.0
	 */
	public function check_environment() {
		if ( ! $this->is_environment_compatible() && is_plugin_active( WP_BULK_PROCESS_PLUGIN_BASENAME ) ) {
			$this->deactivate_plugin();
			$this->add_admin_notice(
				'bad_environment',
				'error',
				sprintf(
					/* translators: %s Plugin Name */
					__( '%s has been deactivated.', 'wp-bulk-process' ),
					WP_BULK_PROCESS_PLUGIN_NAME
				) . ' ' . $this->get_environment_message()
			);
		}
	}


	/**
	 * Adds notices for out-of-date WordPress and/or WooCommerce versions.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_notices() {
		if ( ! $this->is_wp_compatible() ) {
			$this->add_admin_notice(
				'update_wordpress',
				'error',
				sprintf(
					/* translators: 1: Plugin Name 2: Minimum WP Version 3: Update Url */
					__( '%1$s requires WordPress version %2$s or higher. Please %3$supdate WordPress &raquo;%4$s', 'wp-bulk-process' ),
					WP_BULK_PROCESS_PLUGIN_NAME,
					WP_BULK_PROCESS_MIN_WP_VERSION,
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
					'</a>'
				)
			);
		}
	}

	/**
	 * Determines if the required plugins are compatible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function plugins_compatible() {
		return $this->is_wp_compatible();
	}


	/**
	 * Determines if the WordPress compatible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_wp_compatible() {
		if ( ! WP_BULK_PROCESS_MIN_WP_VERSION ) {
			return true;
		}
		return version_compare( get_bloginfo( 'version' ), WP_BULK_PROCESS_MIN_WP_VERSION, '>=' );
	}

	/**
	 * Deactivates the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function deactivate_plugin() {
		deactivate_plugins( WP_BULK_PROCESS_PLUGIN_FILE );

		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			unset( $_GET['activate'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug    The slug for the notice.
	 * @param string $class   The css class for the notice.
	 * @param string $message The notice message.
	 */
	private function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message,
		);
	}

	/**
	 * Displays any admin notices added with WP_Bulk_Process::add_admin_notice()
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {
		foreach ( (array) $this->notices as $notice_key => $notice ) {
			?>
			<div class="<?php echo esc_attr( $notice['class'] ); ?>">
				<p>
				<?php
				echo wp_kses(
					$notice['message'],
					array(
						'strong' => array(),
						'a'      => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);
				?>
					</p>
			</div>
			<?php
		}
	}


	/**
	 * Determines if the server environment is compatible with this plugin.
	 *
	 * Override this method to add checks for more than just the PHP version.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_environment_compatible() {
		return version_compare( phpversion(), WP_BULK_PROCESS_MIN_PHP_VERSION, '>=' );
	}


	/**
	 * Gets the message for display when the environment is incompatible with this plugin.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_environment_message() {
		return sprintf(
			/* translators: 1: Minimum PHP Version 2: Current PHP Version */
			__( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'wp-bulk-process' ),
			WP_BULK_PROCESS_MIN_PHP_VERSION,
			phpversion()
		);
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @since 1.0.0
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
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/wp-bulk-process/wp-bulk-process-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/wp-bulk-process-LOCALE.mo
	 *
	 * @since 1.0.0
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
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	public function init_plugin() {
		if ( ! $this->plugins_compatible() ) {
			return;
		}

		// Include required files.
		$this->includes();

		// Before init action.
		do_action( 'before_wp_bulk_process_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Init action.
		do_action( 'wp_bulk_process_init' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WP_BULK_PROCESS_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( WP_BULK_PROCESS_PLUGIN_FILE ) );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}
}
