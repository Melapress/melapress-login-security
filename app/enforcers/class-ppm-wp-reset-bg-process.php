<?php
/**
 * Handle BG processes.
 *
 * @package WordPress
 * @subpackage wpassword
 */

/**
 * Handles bacgrkund resets..
 */
class PPM_Reset_User_PW_Process extends WP_Background_Process {

	/**
	 * Action to run.
	 *
	 * @var string
	 */
	protected $action = 'ppm_reset_user_pw';

	/**
	 * Task logic.
	 *
	 * @param int $item - User ID.
	 * @return bool.
	 */
	protected function task( $item ) {

		if ( empty( $item ) || ! isset( $item ) ) {
			return false;
		}

		$ppm   = ppm_wp();
		$reset = new PPM_WP_Reset();
		$user  = get_user_by( 'id', $item );
		$reset->reset( $user->ID, $user->data->user_pass, 'admin', true );
		$ppm->ppm_user_session_destroy( $user->ID );

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
