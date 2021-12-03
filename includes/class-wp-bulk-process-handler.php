<?php
/**
 * WP Bulk Process Admin
 *
 * @class WP_Bulk_Process_Handler
 * @package WP_Bulk_Process
 * @subpackage WP_Bulk_Process/Admin
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Bulk_Process_Handler' ) ) {
	return new WP_Bulk_Process_Handler();
}

/**
 * WP_Bulk_Process_Handler class.
 */
class WP_Bulk_Process_Handler {
	/**
	 * Stores the total number of records to be processed.
	 *
	 * (default value: 0)
	 *
	 * @var int
	 */
	private $limit = 0;

	/**
	 * Start time of current import.
	 *
	 * (default value: 0)
	 *
	 * @var int
	 */
	private $start_time = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wp_bulk_process_ajax_handler', array( $this, 'ajax_handler' ) );
	}

	/**
	 * Process ajax handler.
	 */
	public function ajax_handler() {
		check_ajax_referer( 'wp-bulk-process-security', 'security' );

		$params = array(
			'start_pos' => isset( $_POST['position'] ) ? absint( $_POST['position'] ) : 0,
			'action'    => isset( $_POST['action_name'] ) ? sanitize_text_field( wp_unslash( $_POST['action_name'] ) ) : 0,
			'lines'     => apply_filters( 'wp_bulk_process_batch_size', 50 ),
		);

		$results          = $this->run_process( $params );
		$percent_complete = $results['percentage'];

		if ( 100 === $percent_complete ) {
			wp_send_json_success(
				array(
					'position'   => 'done',
					'percentage' => 100,
					'url'        => add_query_arg( array( '_wpnonce' => wp_create_nonce( 'wp-bulk-process-complete' ) ), admin_url( 'tools.php?page=wp-bulk-process&step=done' ) ),
					'success'    => count( $results['success'] ),
					'failed'     => count( $results['failed'] ),
					'skipped'    => count( $results['skipped'] ),
					'html'       => $results['html'],
				)
			);
		} else {
			wp_send_json_success(
				array(
					'position'   => $results['position'],
					'percentage' => $percent_complete,
					'success'    => count( $results['success'] ),
					'failed'     => count( $results['failed'] ),
					'skipped'    => count( $results['skipped'] ),
					'html'       => $results['html'],
				)
			);
		}
	}

	/**
	 * Run the process
	 *
	 * @param array $params Process data.
	 * @return array
	 */
	private function run_process( $params ) {
		$this->start_time = time();
		$index            = 0;

		$data = array(
			'success'    => array(),
			'failed'     => array(),
			'skipped'    => array(),
			'percentage' => 0,
			'position'   => 0,
		);

		$action = $params['action'];

		// Fetch the parsed data based on your action using conditional statements.
		$parsed_data = $this->get_parsed_data( $params['start_pos'], $params['lines'] );

		if ( empty( $this->limit ) || empty( $parsed_data ) ) {
			$data['position']   = $params['start_pos'];
			$data['percentage'] = 100;
			return $data;
		}

		foreach ( $parsed_data as $item ) {
			$new_index = $params['start_pos'] + $index + 1;

			/**
			 * Do your process based on your action using conditional statements.
			 * Or You can create separate functions for each action and call
			 * functions based on your selected action on first step.
			 */

			$index ++;
			if ( $this->time_exceeded( $start_time ) || $this->memory_exceeded() ) {
				break;
			}
		}

		$data['position']   = $index + $params['start_pos'];
		$percentage         = ( $data['position'] * 100 ) / $this->limit;
		$data['percentage'] = absint( $percentage ) >= 100 ? 100 : number_format( $percentage, 2 );
		return $data;
	}

	/**
	 * Return parsed data
	 *
	 * @param int $offset Offset.
	 * @param int $limit Limit.
	 * @return mixed
	 */
	private function get_parsed_data( $offset = 0, $limit = 300 ) {
		global $wpdb;
		// Write your Query and return data.
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @return bool
	 */
	private function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return apply_filters( 'wp_bulk_process_handler_memory_exceeded', $return );
	}

	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	private function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}

		return intval( $memory_limit ) * 1024 * 1024;
	}

	/**
	 * Time exceeded.
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	private function time_exceeded() {
		$finish = $this->start_time + apply_filters( 'wp_bulk_process_handler_default_time_limit', 20 ); // 20 seconds
		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		return apply_filters( 'wp_bulk_process_handler_time_exceeded', $return );
	}
}

return new WP_Bulk_Process_Handler();
