<?php
/**
 * Handles the cron task for the inactive users feature.
 *
 * @since 2.1.0
 *
 * @package wordpress
 */

namespace PPMWP\Crons;

use \PPMWP\Helpers\OptionsHelper;

/**
 * Inactive users cron.
 *
 * @since 2.1.0
 */
class InactiveUsersCheck implements CronInterface {

	/**
	 * Holds an instance of the main plugin class.
	 *
	 * @var InactiveUsers
	 */
	public $caller;

	/**
	 * Sets up the properties for this cron.
	 *
	 * @method __construct
	 * @since  2.1.0
	 * @param  {InactiveUsers} $caller Instance of the main InactiveUsers class.
	 */
	public function __construct( $caller ) {
		$this->caller = $caller;
		// adds a cron schedule that runs every 6 hours.
		add_filter(
			'cron_schedules',
			function( $schedules ) {
				$schedules['fourdaily'] = array(
					'interval' => HOUR_IN_SECONDS * 6,
					'display'  => esc_html__( 'Every 6 Hours' ),
				);
				return $schedules;
			}
		);
		add_action( 'wp_ajax_ppmwp_inactive_users_check', array( $this, 'inactive_users_check' ) );
	}

	/**
	 * Entrypoint to register this cron task.
	 *
	 * @method register
	 * @since  2.1.0
	 */
	public function register() {
		// registers the scheduled task.
		$this->register_cron();
		// hooks in the action to be run by the cron.
		$this->action();
	}

	/**
	 * Register this cron task.
	 *
	 * @method register_cron
	 * @since  2.1.0
	 */
	private function register_cron() {
		// bail early if this cron is already scheduled.
		if ( wp_next_scheduled( 'ppmwp_inactive_users_check' ) ) {
			return;
		}
		wp_schedule_event(
			\current_time( 'timestamp' ),
			'fourdaily',
			'ppmwp_inactive_users_check'
		);
	}

	/**
	 * Adds the action for the cron.
	 *
	 * @method action
	 * @since  2.1.0
	 */
	public function action() {
		add_action( 'ppmwp_inactive_users_check', array( $this, 'inactive_users_check' ) );
	}

	/**
	 * The inactive users cron.
	 *
	 * This checks if users should be set to inactive and if so sets the meta
	 * that flags the account as inactive.
	 *
	 * @method inactive_users_cron
	 * @since  2.1.0
	 */
	public function inactive_users_check() {

		// For testing purposes we may want to fire this via ajax. If so verify
		// a nonce.
		if ( wp_doing_ajax() ) {
			check_ajax_referer( 'ppmwp_inactive_cron_trigger' );
		}
		// bail early if the inactive users feature is not enabled.
		$enabled = OptionsHelper::should_inactive_users_feature_be_active();
		if ( ! $enabled ) {
			return;
		}
		// get ids of any already inactive users.
		$inactive_users = OptionsHelper::get_inactive_users();

		// get other users' ids.
		$users_query = new \WP_User_Query(
			array(
				'exclude' => $inactive_users,
				'fields'  => 'ids',
			)
		);

		$user_ids = $users_query->results;

		// hold any users made inactive in this array for storing at the end.
		$made_inactive = array();
		foreach ( $user_ids as $user_id ) {
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
			}
			// if user doesn't have an expiry time then use the password history time.
			if ( ! $time ) {
				continue;
			}

			// this user is inactive if their time + inactive period is less than current time.
			if ( ( $time + apply_filters( 'ppmwp_adjust_dormancy_period', $role_specific_dormancy ) ) < current_time( 'timestamp' ) ) {
				$ppm   = ppm_wp();
				// user is inactive.
				OptionsHelper::set_user_inactive( $user_id );
				$ppm->ppm_user_session_destroy( $user_id );
				$made_inactive[] = $user_id;
			}
		}
		if ( ! empty( $made_inactive ) ) {
			$inactive_users = array_merge( $inactive_users, $made_inactive );
			OptionsHelper::set_inactive_users_array( $inactive_users );
		}

		// if this was fired by an ajax call return a success message.
		if ( wp_doing_ajax() ) {
			wp_send_json_success(
				array(
					'changed' => ! empty( $made_inactive ) ? true : false,
				)
			);
		}
	}
}
