<?php

class PPM_Reset_User_PW_Process extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'ppm_reset_user_pw';

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
	 * @inheritDoc
	 */
	protected function complete() {
		parent::complete();
	}
}
