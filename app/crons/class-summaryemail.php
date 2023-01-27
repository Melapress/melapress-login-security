<?php
/**
 * Handles the cron task for the weekly summary email.
 *
 * @since 2.4.1
 *
 * @package WordPress
 */

namespace PPMWP\Crons;

use \PPMWP\Helpers\OptionsHelper;

/**
 * Inactive users cron.
 *
 * @since  2.4.1
 */
class SummaryEmail implements CronInterface {

	/**
	 * Holds an instance of the main plugin class.
	 *
	 * @var PPM_WP
	 */
	public $caller;

	/**
	 * Sets up the properties for this cron.
	 *
	 * @method construct
	 * @since  2.4.1
	 * @param  PPM_WP $caller Instance of the main InactiveUsers class.
	 */
	public function __construct( $caller ) {
		$this->caller = $caller;
		// adds a cron schedule that runs every 6 hours.
		add_filter(
			'cron_schedules',
			function( $schedules ) {
				$schedules['weekly'] = array(
					'interval' => 604800,
					'display'  => __( 'Once Weekly' ),
				);
				return $schedules;
			}
		);
		add_action( 'wp_ajax_ppmwp_send_summary_email', array( $this, 'send_summary_email' ) );
	}

	/**
	 * Entrypoint to register this cron task.
	 *
	 * @method register
	 * @since  2.4.1
	 */
	public function register() {

		if ( ! isset( $this->caller->options->ppm_setting->send_summary_email ) ) {
			return;
		}

		// Go no further if this isnt set.
		if ( ! isset( $this->caller->options->ppm_setting->send_summary_email ) ) {
			return;
		}

		$enable_weekly_email = OptionsHelper::string_to_bool( $this->caller->options->ppm_setting->send_summary_email );

		if ( $enable_weekly_email ) {
			// registers the scheduled task.
			$this->register_cron();
			// hooks in the action to be run by the cron.
			$this->action();
		} elseif ( wp_next_scheduled( 'ppmwp_send_summary_email' ) ) {
			wp_clear_scheduled_hook( 'ppmwp_send_summary_email' );
		}
	}

	/**
	 * Register this cron task.
	 *
	 * @method register_cron
	 * @since  2.4.1
	 */
	private function register_cron() {
		// bail early if this cron is already scheduled.
		if ( wp_next_scheduled( 'ppmwp_send_summary_email' ) ) {
			return;
		}
		wp_schedule_event(
			\current_time( 'timestamp' ),
			'weekly',
			'ppmwp_send_summary_email'
		);
	}

	/**
	 * Adds the action for the cron.
	 *
	 * @method action
	 * @since  2.4.1
	 */
	public function action() {
		add_action( 'ppmwp_send_summary_email', array( $this, 'send_summary_email' ) );
	}

	/**
	 * The email sumary cron.
	 *
	 * @method send_summary_email
	 * @since  2.4.1
	 */
	public function send_summary_email() {

		// Access plugin instance.
		$ppm = ppm_wp();

		// Setup the basics.
		$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'wordpress@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
		$from_email = sanitize_email( $from_email );
		$headers[]  = 'From: ' . $from_email;
		$headers[]  = 'Content-Type: text/html; charset=UTF-8';

		$weeknumber = date( 'W', strtotime( current_time( 'timestamp' ) ) ); // phpcs:ignore

		$title = __( 'WPassword update - Week ', 'ppm-wp' ) . $weeknumber . ' - ' . get_site_url();
		$email = $from_email;

		// Setup empty array for free edition.
		$inactive_users = array();
		$blocked_users  = array();


		$resets = $this->get_users_with_recent_password_resets();

		$message = __( 'Here is your weekly summary.', 'ppm-wp' );

		// If we have nothing to report, do nothing.
		if ( empty( $inactive_users ) && empty( $blocked_users ) && empty( $resets ) ) {
			return;
		}

		/*
		*/
		// Show recently inactive users.
		if ( ! empty( $inactive_users ) ) {
			$message .= '<p><strong>' . __( 'Users marked as inactive:', 'ppm-wp' ) . '</strong><br>';

			foreach ( $inactive_users as $user_id => $details ) {
				$message .= $details['user_login'] . ' | ' . $details['user_role'] . ' | ' . __( 'Inactive since', 'ppm-wp' ) . ': ' . $details['timestamp'] . '<br>';
			}

			$message .= '</p>';
		}

		// Show recent failed login lockouts.
		if ( ! empty( $blocked_users ) ) {
			$message .= '<p><strong>' . __( 'Uers who exceeded failed login attempts:', 'ppm-wp' ) . '</strong><br>';

			foreach ( $blocked_users as $user_id => $details ) {
				$message .= $details['user_login'] . ' | ' . $details['user_role'] . ' | ' . __( 'Blocked since', 'ppm-wp' ) . ': ' . $details['timestamp'] . '<br>';
			}

			$message .= '</p>';
		}

		// Show recent resets.
		if ( ! empty( $resets ) ) {
			$message .= '<p><strong>' . __( 'Recent users with password resets:', 'ppm-wp' ) . '</strong><br>';

			foreach ( $resets as $user_id => $details ) {
				$message .= $details['user_login'] . ' | ' . $details['user_role'] . ' | ' . __( 'Last reset', 'ppm-wp' ) . ': ' . $details['timestamp'] . '<br>';
			}

			$message .= '</p>';
		}

		$message_end = sprintf(
			 /* translators: %1s: link to our plugin. */
			__( 'This email is generated by the %1$s WPassword plugin %2$s', 'ppm-wp' ),
			'<a href="https://www.wpwhitesecurity.com/wordpress-plugins/password-policy-manager-wordpress/" rel="nofollow">',
			'</a>'
		);

		$message .= '<p>' . $message_end . '</p>';

		wp_mail( $email, wp_specialchars_decode( $title ), $message, $headers );
	}

	/**
	 * Query users for password history and use result to determine which ones fall within out timeframe.
	 *
	 * @return array $users
	 * @since  2.4.1
	 */
	public function get_users_with_recent_password_resets() {
		global $wpdb;

		$users          = $wpdb->query(
			$wpdb->prepare(
				"
			SELECT ID FROM $wpdb->users
			INNER JOIN ' . $wpdb->usermeta . ' ON ' . $wpdb->users . '.ID = ' . $wpdb->usermeta . '.user_id
			WHERE ' . $wpdb->usermeta . '.meta_key LIKE %s
			",
				array(
					'ppmwp_password_history%',
				)
			)
		);
		$users          = array_map(
			function ( $user ) {
				if ( ! ppm_is_user_exempted( $user->ID ) ) {
					  return (int) $user->ID;
				}
			},
			$users
		);
		$possible_users = ( ! empty( $users ) ) ? $users : array();

		$users = $this->remove_unwanted_users( $possible_users, 'password_resets' );

		return $users;
	}

	/**
	 * Removes users IDs from list and leaves only IDs which occur in our desired timeframe.
	 *
	 * @param  array  $possible_user_ids - Users to check.
	 * @param  string $type - Type to lookup.
	 * @return array
	 * @since  2.4.1
	 */
	public function remove_unwanted_users( $possible_user_ids, $type ) {

		$users = array();

		foreach ( $possible_user_ids as $user_id ) {
			if ( 'password_resets' === $type ) {
				$history               = get_user_meta( $user_id, PPM_WP_META_KEY, true );
				$last_change_timestamp = $history[0]['timestamp'];
			}

			if ( 'inactive' === $type ) {
				$last_change_timestamp = get_user_meta( $user_id, PPMWP_PREFIX . '_inactive_set_time', true );
			}

			if ( 'blocked' === $type ) {
				$last_change_timestamp = get_user_meta( $user_id, PPMWP_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP, true );
			}

			if ( $last_change_timestamp > strtotime( '-1 week' ) ) {
				$userdata  = get_user_by( 'id', $user_id );
				$user_info = array(
					'user_login' => $userdata->user_login,
					'user_role'  => $userdata->roles[0],
					'timestamp'  => date_i18n( get_option( 'date_format' ), $last_change_timestamp ) . ' ' . date_i18n( get_option( 'time_format' ), $last_change_timestamp ),
				);
				array_push( $users, $user_info );
			}
		}

		return $users;
	}
}