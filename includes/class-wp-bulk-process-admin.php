<?php
/**
 * WP Bulk Process Admin
 *
 * @class WP_Bulk_Process_Admin
 * @package WP_Bulk_Process
 * @subpackage WP_Bulk_Process/Admin
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Bulk_Process_Admin' ) ) {
	return new WP_Bulk_Process_Admin();
}

/**
 * WP_Bulk_Process_Admin class.
 */
class WP_Bulk_Process_Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add menus.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		add_submenu_page( 'tools.php', __( 'WP Bulk Process', 'wp-bulk-process' ), __( 'WP Bulk Process', 'wp-bulk-process' ), 'manage_options', 'wp-bulk-process', array( $this, 'admin_menu_page' ) );
	}

	/**
	 * Valid screen ids for plugin scripts & styles
	 *
	 * @param   string $screen_id   screen id.
	 * @return  array
	 */
	public function is_valid_screen( $screen_id ) {
		$valid_screen_ids = apply_filters(
			'wp_bulk_process_valid_admin_screen_ids',
			array(
				'wp-bulk-process',
			)
		);

		if ( empty( $valid_screen_ids ) ) {
			return false;
		}

		foreach ( $valid_screen_ids as $admin_screen_id ) {
			$matcher = '/' . $admin_screen_id . '/';
			if ( preg_match( $matcher, $screen_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Register admin styles.
		wp_register_style( 'wp-bulk-process-admin-styles', wp_bulk_process()->plugin_url() . '/assets/css/admin' . $suffix . '.css', array(), WP_BULK_PROCESS_VERSION );

		// Admin styles for wp-bulk-process pages only.
		if ( $this->is_valid_screen( $screen_id ) ) {
			wp_enqueue_style( 'wp-bulk-process-admin-styles' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts.
		wp_register_script( 'wp-bulk-process-admin', wp_bulk_process()->plugin_url() . '/assets/js/admin' . $suffix . '.js', array( 'jquery' ), WP_BULK_PROCESS_VERSION, true );
	}

	/**
	 * Display admin page
	 */
	public function admin_menu_page() {
		$step = $this->get_current_step();
		?>
		<div class="wrap wp-bulk-process" id="wp-bulk-process">
			<div class="wp-bulk-process-wrapper">
				<div class="wp-bulk-process-page-title">
					<h2>
						<span class="dashicons dashicons-shortcode"></span>
						<span class="link-shadow"><?php esc_html_e( 'WP Bulk Process', 'wp-bulk-process' ); ?></span>
					</h2>
				</div>

				<div class="wp-bulk-process-steps">
					<?php $this->display_step_bar( $step ); ?>
				</div>

				<div class="wp-bulk-process-page-content">
					<?php
					switch ( $step ) {
						case 2:
							// Displays update step where you see the progress of bulk process.
							$this->display_update_step();
							break;

						case 3:
							// Displays results step where you see the result after bulk process is done.
							$this->display_results_step();
							break;

						case 1:
						default:
							// Displays default step where you start the bulk process.
							$this->display_default_step();
							break;
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns current step number
	 *
	 * @return int
	 */
	private function get_current_step() {
		$step = 1;
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'wp-bulk-process-submit-action' ) && isset( $_POST['wp_bulk_process_submit_call'] ) ) {
			$step = 2;
		} elseif ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wp-bulk-process-complete' ) && isset( $_GET['step'] ) && sanitize_key( $_GET['step'] ) === 'done' ) {
			$step = 3;
		}
		return $step;
	}

	/**
	 * Outputs step bar
	 *
	 * @param int $step Current step number.
	 */
	private function display_step_bar( $step = 1 ) {
		$step  = min( $step, 3 );
		$step  = max( $step, 1 );
		$width = $step - 1 > 0 ? ( 100 * ( $step - 1 ) ) / 2 : 0;
		?>
		<div>
			<div class="bar">
				<div class="bar-fill" style="width: <?php echo esc_attr( $width ); ?>%;"></div>
			</div>
			<div class="point <?php echo 1 === $step ? 'point-active' : ''; ?> <?php echo 1 < $step ? 'point-complete' : ''; ?>">
				<div class="bullet"></div>
				<label class="label"><?php esc_html_e( 'Select Action', 'wp-bulk-process' ); ?></label>
			</div>
			<div class="point <?php echo 2 === $step ? 'point-active' : ''; ?> <?php echo 2 < $step ? 'point-complete' : ''; ?>">
				<div class="bullet"></div>
				<label class="label"><?php esc_html_e( 'In Progess', 'wp-bulk-process' ); ?></label>
			</div>
			<div class="point <?php echo 3 === $step ? 'point-active' : ''; ?> <?php echo 3 <= $step ? 'point-complete' : ''; ?>">
				<div class="bullet"></div>
				<label class="label"><?php esc_html_e( 'Done!', 'wp-bulk-process' ); ?></label>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs default step
	 */
	private function display_default_step() {
		?>
		<form method="post" action="">
			<?php wp_nonce_field( 'wp-bulk-process-submit-action' ); ?>

			<div class="wp-bulk-process-setting-row wp-bulk-process-clear section-heading">
				<div class="wp-bulk-process-setting-field">
					<h2><?php esc_html_e( 'Information', 'wp-bulk-process' ); ?></h2>
					<p class="desc"><?php esc_html_e( 'Choose one of the action and start your process', 'wp-bulk-process' ); ?></p>
				</div>
			</div>

			<div id="bulk-action" class="wp-bulk-process-setting-row wp-bulk-process-clear">
				<div class="wp-bulk-process-setting-label">
					<label for="wp-bulk-process-setting-bulk_action"><?php esc_html_e( 'Bulk Process Action', 'wp-bulk-process' ); ?></label>
				</div>
				<div class="wp-bulk-process-setting-field">
					<select name="bulk_action" id="bulk-action" required>
						<option value="action1"><?php esc_html_e( 'E.g. Regenerate all users password', 'wp-bulk-process' ); ?></option>
						<option value="action2"><?php esc_html_e( 'E.g. Update any post meta in all posts', 'wp-bulk-process' ); ?></option>
					</select>
					<p class="desc"><?php esc_html_e( 'Choose the action which you wanna perform', 'wp-bulk-process' ); ?></p>
				</div>
			</div>

			<p class="wp-bulk-process-submit">
				<button type="submit" class="wp-bulk-process-btn" name="wp_bulk_process_submit_call"><?php esc_html_e( 'Start Process', 'wp-bulk-process' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Outputs update step
	 */
	private function display_update_step() {
		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wp-bulk-process-security' ),
			'action'  => isset( $_POST['bulk_action'] ) ? sanitize_key( $_POST['bulk_action'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification
		);
		wp_localize_script( 'wp-bulk-process-admin', 'wp_bulk_process_params', $params );
		wp_enqueue_script( 'wp-bulk-process-admin' );
		?>

		<div class="wp-bulk-process-setting-row wp-bulk-process-clear section-heading">
			<div class="wp-bulk-process-setting-field">
				<h2><?php esc_html_e( 'In Progress', 'wp-bulk-process' ); ?><span class="spinner progress-spinner"></span></h2>
				<p class="desc"><?php esc_html_e( 'Your process is in progress, Please do not close this window until process is finished.', 'wp-bulk-process' ); ?></p>

				<div class="progress-bar">
					<span class="fill"></span>
					<span class="percentage">0%</span>
				</div>
			</div>
		</div>

		<div class="wp-bulk-process-setting-row wp-bulk-process-clear">
			<div class="wp-bulk-process-results-content">
			<?php
			// This section will be empty and this it will be have ajax response.
			$ids   = array( 1001, 1002, 1003, 1004, 1005, 1006 );
			$count = 1;
			foreach ( $ids as $id ) {
				printf(
					'<p>%s. <a href="%s" target="_blank">#%s</a> page content updated successfully.</p>',
					esc_html( $count ),
					esc_url( get_edit_post_link( $id ) ),
					esc_html( $id )
				);
				$count++;
			}
			?>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs results step
	 */
	private function display_results_step() {
		$url = admin_url( 'tools.php?page=wp-bulk-process' );
		?>
		<div class="wp-bulk-process-setting-row wp-bulk-process-clear section-heading">
			<div class="wp-bulk-process-setting-field">
				<h2><?php esc_html_e( 'Complete', 'wp-bulk-process' ); ?></h2>
				<p class="desc"><?php printf( /* translators: %s start step url */ esc_html__( 'Your process has finished. Here is the summary of your last process. %s to go on start page.', 'wp-bulk-process' ), '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Click here', 'wp-bulk-process' ) . '</a>' ); ?></p>
			</div>
		</div>

		<div class="wp-bulk-process-setting-row wp-bulk-process-clear">
			<table class="wp-bulk-process-summary-table">
				<tbody>
					<?php // phpcs:disable WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput ?>
					<tr>
						<td><?php esc_html_e( 'Success', 'wp-bulk-process' ); ?></td>
						<td><?php echo isset( $_GET['success'] ) && ! empty( $_GET['success'] ) ? esc_html( $_GET['success'] ) : 0; ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Failed', 'wp-bulk-process' ); ?></td>
						<td><?php echo isset( $_GET['failed'] ) && ! empty( $_GET['failed'] ) ? esc_html( $_GET['failed'] ) : 0; ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Skipped', 'wp-bulk-process' ); ?></td>
						<td><?php echo isset( $_GET['skipped'] ) && ! empty( $_GET['skipped'] ) ? esc_html( $_GET['skipped'] ) : 0; ?></td>
					</tr>
					<?php // phpcs:enable WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

return new WP_Bulk_Process_Admin();
