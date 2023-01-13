<?php

class PPM_Apply_Timestamp_For_Users_Process extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'ppm_apply_active_timestamp';

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
	 * @inheritDoc
	 */
	protected function complete() {
		parent::complete();
	}
}
