<?php
/**
 * Apply install timestamp.
 *
 * @since 2.1.0
 *
 * @package WordPress
 */

if ( ! class_exists( 'WP_Async_Request' ) && file_exists( PPM_WP_PATH . 'third-party/wp-async-request.php' ) ) {
	require_once PPM_WP_PATH . 'third-party/wp-async-request.php';
}
if ( ! class_exists( 'WP_Background_Process' ) && file_exists( PPM_WP_PATH . 'third-party/wp-background-process.php' ) ) {
	require_once PPM_WP_PATH . 'third-party/wp-background-process.php';
}

/**
 * Apply timestamp in BG.
 */
class PPM_Apply_Timestamp_For_Users_Process extends WP_Background_Process {

	/**
	 * Current action.
	 *
	 * @var string
	 */
	protected $action = 'ppm_apply_active_timestamp';

	/**
	 * Task logic.
	 *
	 * @param array $item User.
	 * @return bool Did complete.
	 */
	protected function task( $item ) {

		if ( empty( $item ) || ! isset( $item ) ) {
			return false;
		}

		foreach ( $item as $user ) {
			$last_activity = get_user_meta( $user->ID, 'ppmwp_last_activity', true );
			if ( ! $last_activity || empty( $last_activity ) ) {
				add_user_meta( $user->ID, 'ppmwp_last_activity', current_time( 'timestamp' ) );
			}
		}

		return false;
	}

	/**
	 * Did complete.
	 *
	 * @inheritDoc
	 */
	protected function complete() {
		parent::complete();
	}
}
