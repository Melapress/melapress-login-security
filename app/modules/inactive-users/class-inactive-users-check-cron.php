<?php
/**
 * Handles the cron task for the inactive users feature.
 *
 * @since 2.1.0
 *
 * @package WordPress
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

		// exclude exempted roles and users.
		$user_args = array(
			'exclude' => $inactive_users,
			'fields'  => array( 'ID' ),
		);

		// If check multisite installed OR not.
		if ( is_multisite() ) {
			$user_args['blog_id'] = 0;
		}

		// Send users for bg processing later.
		$total_users        = count_users();
		$batch_size         = 50;
		$slices             = ceil( $total_users['total_users'] / $batch_size );
		$users              = array();
		$background_process = new \PPM_CheckInactiveUsers_Process();

		for ( $count = 0; $count < $slices; $count++ ) {
			$user_args['number'] = $batch_size;
			$user_args['offset'] = $count * $batch_size;
			$users               = get_users( $user_args );

			if ( ! empty( $users ) ) {
				$background_process->push_to_queue( $users );
			}
		}

		// Fire off bg processes.
		$background_process->save()->dispatch();

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
