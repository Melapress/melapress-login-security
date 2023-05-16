<?php
/**
 * Handle BG processes.
 *
 * @package WordPress
 * @subpackage wpassword
 */

use \PPMWP\Helpers\OptionsHelper;


/**
 * Handles bacgrkund resets..
 */
class PPM_CheckInactiveUsers_Process extends WP_Background_Process {

	/**
	 * Action to run.
	 *
	 * @var string
	 */
	protected $action = 'ppm_check_inactive_users_task';

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

		// hold any users made inactive in this array for storing at the end.
		$made_inactive = array();

        $user_ids = $item;

		foreach ( $user_ids as $user ) {
            $user_id = $user->ID;
			if ( OptionsHelper::is_user_inactive_exempted( $user_id ) ) {
				continue;
			}

			// initial time is zero.
			$time = 0;

			// get the users last activity time.
			$last_active = get_user_meta( $user_id, 'ppmwp_last_activity', true );

			// get the specific dormancy perioud for this users role.
			$role_specific_dormancy = OptionsHelper::get_role_specific_dormancy_period( $user_id );

			if ( $last_active ) {
				// make sure we have it as an int and not a string.
				$time = (int) $last_active;
			} else {
				$time = (int) get_site_option( PPMWP_PREFIX . '_activation' );
				update_user_meta( $user_id, 'ppmwp_last_activity', $time );
			}
			// if user doesn't have an expiry time then use the password history time.
			if ( ! $time ) {
				continue;
			}

			// this user is inactive if their time + inactive period is less than current time.
			if ( ( $time + apply_filters( 'ppmwp_adjust_dormancy_period', $role_specific_dormancy ) ) < current_time( 'timestamp' ) ) {
				$ppm = ppm_wp();
				// user is inactive.
				OptionsHelper::set_user_inactive( $user_id );
				$ppm->ppm_user_session_destroy( $user_id );
				$made_inactive[] = $user_id;
			}
		}
		if ( ! empty( $made_inactive ) ) {
            // get ids of any already inactive users.
            $inactive_users = OptionsHelper::get_inactive_users();
			$inactive_users = array_merge( $inactive_users, $made_inactive );
			OptionsHelper::set_inactive_users_array( $inactive_users );
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
