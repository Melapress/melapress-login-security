<?php
/**
 * Handle BG processes.
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
 * Handles background resets.
 *
 * @since 2.0.0
 */
class Reset_User_PW_Process extends \WP_Background_Process {

	/**
	 * Action to run.
	 *
	 * @var string
	 *
	 * @since 2.0.0
	 */
	protected $action = 'ppm_reset_user_pw';

	/**
	 * Task logic.
	 *
	 * @param int $item - User ID.
	 *
	 * @return bool.
	 *
	 * @since 2.0.0
	 */
	protected function task( $item ) {
		if ( empty( $item ) || ! isset( $item ) ) {
			return false;
		}

		$mls        = melapress_login_security();
		$user       = get_user_by( 'ID', $item['ID'] );
		$is_delayed = false;
		if ( isset( $item['reset_when'] ) && 'reset-login' === $item['reset_when'] ) {
			\MLS\User_Profile::generate_new_reset_key( $user->ID );
			$is_delayed = true;
		}

		if ( $item['kill_sessions'] ) {
			$mls->ppm_user_session_destroy( $user->ID );
		}

		if ( $item['send_reset'] ) {
			delete_user_meta( $user->ID, MLS_EXPIRED_EMAIL_SENT_META_KEY );
		}

		\MLS\Reset_Passwords::reset_by_id( $user->ID, $user->data->user_pass, 'admin', $is_delayed, $item['kill_sessions'], $item['send_reset'], true );

		return false;
	}
}
