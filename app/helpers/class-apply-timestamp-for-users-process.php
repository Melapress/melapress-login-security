<?php
/**
 * Apply install timestamp.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apply timestamp in BG.
 *
 * @since 2.0.0
 */
class Apply_Timestamp_For_Users_Process extends \WP_Background_Process {

	/**
	 * Current action.
	 *
	 * @var string
	 *
	 * @since 2.0.0
	 */
	protected $action = 'ppm_apply_active_timestamp';

	/**
	 * Task logic.
	 *
	 * @param array $item User.
	 *
	 * @return bool Did complete.
	 *
	 * @since 2.0.0
	 */
	protected function task( $item ) {

		if ( empty( $item ) || ! isset( $item ) ) {
			return false;
		}

		foreach ( $item as $user ) {
			$last_activity = get_user_meta( $user->ID, MLS_PREFIX . '_last_activity', true );
			if ( ! $last_activity || empty( $last_activity ) ) {
				add_user_meta( $user->ID, MLS_PREFIX . '_last_activity', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			}
		}

		return false;
	}
}
