<?php
/**
 * Handles the cron task for the weekly summary email.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Crons;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

/**
 * Inactive users cron.
 *
 * @since 2.0.0
 */
class SummaryEmail implements CronInterface {

	/**
	 * Holds an instance of the main plugin class.
	 *
	 * @var MLS_Core
	 *
	 * @since 2.0.0
	 */
	public $caller;

	/**
	 * Sets up the properties for this cron.
	 *
	 * @method construct
	 *
	 * @param MLS_Core $caller Instance of the main InactiveUsers class.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function __construct( $caller ) {
		$this->caller = $caller;
		// adds a cron schedule that runs every 6 hours.
		add_filter(
			'cron_schedules',
			function ( $schedules ) {
				$schedules['weekly'] = array(
					'interval' => 604800,
					'display'  => __( 'Once Weekly' ),
				);
				return $schedules;
			}
		);
		add_action( 'wp_ajax_mls_send_summary_email', array( $this, 'send_summary_email' ) );
	}

	/**
	 * Entrypoint to register this cron task.
	 *
	 * @method register
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function register() {

		if ( ! isset( $this->caller->options->mls_setting->send_summary_email ) ) {
			return;
		}

		// Go no further if this isnt set.
		if ( ! isset( $this->caller->options->mls_setting->send_summary_email ) ) {
			return;
		}

		$enable_weekly_email = OptionsHelper::string_to_bool( $this->caller->options->mls_setting->send_summary_email );

		if ( $enable_weekly_email ) {
			// registers the scheduled task.
			$this->register_cron();
			// hooks in the action to be run by the cron.
			$this->action();
		} elseif ( wp_next_scheduled( 'mls_send_summary_email' ) ) {
			wp_clear_scheduled_hook( 'mls_send_summary_email' );
		}
	}

	/**
	 * Register this cron task.
	 *
	 * @method register_cron
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	private function register_cron() {
		// bail early if this cron is already scheduled.
		if ( wp_next_scheduled( 'mls_send_summary_email' ) ) {
			return;
		}
		wp_schedule_event(
			\current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			'weekly',
			'mls_send_summary_email'
		);
	}

	/**
	 * Adds the action for the cron.
	 *
	 * @method action
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function action() {
		add_action( 'mls_send_summary_email', array( $this, 'send_summary_email' ) );
	}

	/**
	 * The email sumary cron.
	 *
	 * @method send_summary_email
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function send_summary_email() {

		// Access plugin instance.
		$mls = melapress_login_security();

		// Setup the basics.
		$from_email = $mls->options->mls_setting->from_email ? $mls->options->mls_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
		$from_email = sanitize_email( $from_email );
		$headers[]  = 'From: ' . $from_email;
		$headers[]  = 'Content-Type: text/html; charset=UTF-8';

		$current_timestamp = current_time( 'timestamp' );  // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested, WordPress.DateTime.RestrictedFunctions.date_date
		if ( ! $current_timestamp ) {
			$current_timestamp = current_datetime();
		}

		$weeknumber = date( 'W' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested, WordPress.DateTime.RestrictedFunctions.date_date
		$year       = date( 'Y' );

		if ( is_multisite() ) {
			$blogname = get_network()->site_name;
		} else {
			/*
			 * The blogname option is escaped with esc_html on the way into the database
			 * in sanitize_option we want to reverse this for the plain text arena of emails.
			 */
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		}

		/* @free:start */

		/* translators: Password reset email subject. 1: Site name, 2: Week number */
		$title = sprintf( __( '%1$1s Week %2$2s - {site_name} password resets summary', 'melapress-login-security' ), $year, $weeknumber );

		/* @free:end */


		$title = \MLS\EmailAndMessageStrings::replace_email_strings( $title, get_current_user_id() );

		$email = get_option( 'admin_email' );

		// Setup empty array for free edition.
		$inactive_users = array();
		$blocked_users  = array();


		$failed_logins      = new \MLS\Failed_Logins();
		$blocked_users      = $failed_logins->get_all_currently_login_locked_users();
		$blocked_users      = $this->remove_unwanted_users( $blocked_users, 'blocked' );
		$recently_unblocked = $this->get_users_with_recent_unlocks( 'blocked' );

		$resets = $this->get_users_with_recent_password_resets();

		$introduction = '<p>' . wp_sprintf( __( 'Below is the list of all the password resets that took place on the website {site_name} during week %1s of the year %2s.', 'melapress-login-security' ), '<strong>' . $weeknumber . '</strong>', '<strong>' . $year . '</strong>' ) . '</p>';

		$message = '<div style="font-family: helvetica;"><div style="width: 400px; margin-bottom: 0px;"><img src="' . esc_url( MLS_PLUGIN_URL . 'assets/images/mls-email-header.png' ) . '"></div><br>';

		$message .= '<p>' . __( 'Hello website administrator,', 'melapress-login-security' ) . '</p>';
		$message .= '<p>' . $introduction . '</p>';
		
		// If we have nothing to report, do nothing.
		if ( empty( $inactive_users ) && empty( $blocked_users ) && empty( $resets ) ) {
			return;
		}


		// Show recent failed login lockouts.
		if ( ! empty( $blocked_users ) ) {
			$message .= '<p><strong>' . __( 'Users who exceeded failed login attempts:', 'melapress-login-security' ) . '</strong><br><table style="text-align: left; position: relative; left: -3px;">';
			$message .= '<tr><th style="min-width: 150px;">' . __( 'Login name', 'melapress-login-security' ) . '</th><th style="min-width: 150px;">' . __( 'Role', 'melapress-login-security' ) . '</th><th>' . __( 'Blocked since', 'melapress-login-security' ) . '</th></tr>';

			foreach ( $blocked_users as $user_id => $details ) {
				$message .= '<tr><td>' . $details['user_login'] . '</td><td>' . $details['user_role'] . '</td><td>' . $details['timestamp'] . '</td></tr>';
			}

			$message .= '</table></p>';
		}
		if ( ! empty( $recently_unblocked ) ) {
			$message .= '<p><strong>' . __( 'Users blocked due to failed logins and unblocked:', 'melapress-login-security' ) . '</strong><br><table style="text-align: left; position: relative; left: -3px;">';
			$message .= '<tr><th style="min-width: 150px;">' . __( 'Login name', 'melapress-login-security' ) . '</th><th style="min-width: 150px;">' . __( 'Role', 'melapress-login-security' ) . '</th><th>' . __( 'Inactive since', 'melapress-login-security' ) . '</th></tr>';

			foreach ( $recently_unblocked as $user_id => $details ) {
				$message .= '<tr><td style="min-width: 150px;">' . $details['user_login'] . '</td><td>' . $details['user_role'] . '</td><td>' . $details['timestamp'] . '</td></tr>';
			}

			$message .= '</table></p>';
		}

		// Show recent resets.
		if ( ! empty( $resets ) ) {
			$message .= '<p><strong>' . __( 'Recent users with password resets:', 'melapress-login-security' ) . '</strong><br><table style="text-align: left; position: relative; left: -3px;">';
			$message .= '<tr><th style="min-width: 150px;">' . __( 'Login name', 'melapress-login-security' ) . '</th><th style="min-width: 150px;">' . __( 'Role', 'melapress-login-security' ) . '</th><th>' . __( 'Last reset', 'melapress-login-security' ) . '</th></tr>';

			foreach ( $resets as $user_id => $details ) {
				$message .= '<tr><td>' . $details['user_login'] . '</td><td>' . $details['user_role'] . '</td><td>' . $details['timestamp'] . '</td></tr>';
			}

			$message .= '</table></p><br>';
		}

		$message .= '<p></div>';

		$message = \MLS\EmailAndMessageStrings::replace_email_strings( $message, get_current_user_id() );

		\MLS\Emailer::send_email( $email, wp_specialchars_decode( $title ), $message, $headers );
	}

	/**
	 * Query users for password history and use result to determine which ones fall within out timeframe.
	 *
	 * @return array $users
	 *
	 * @since 2.0.0
	 */
	public function get_users_with_recent_password_resets() {
		global $wpdb;

		$users          = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"
			SELECT ID FROM $wpdb->users
			INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id
			WHERE $wpdb->usermeta.meta_key LIKE %s
			",
				array(
					MLS_PREFIX . '_password_history%',
				)
			)
		);
		$users          = array_map(
			function ( $user ) {
				if ( ! \MLS_Core::is_user_exempted( $user->ID ) ) {
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
	 * Query users for password history and use result to determine which ones fall within out timeframe.
	 *
	 * @param array $context - Specifies subjects.
	 *
	 * @return array $users - Users array.
	 *
	 * @since 2.0.0
	 */
	public function get_users_with_recent_unlocks( $context = 'inactive' ) {
		global $wpdb;

		$users          = $wpdb->get_results( // phpcs:ignore 
			$wpdb->prepare(
				"
			SELECT ID FROM $wpdb->users
			INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id
			WHERE $wpdb->usermeta.meta_key = %s
			",
				array(
					MLS_PREFIX . '_recently_unlocked',
				)
			)
		);
		$users          = array_map(
			function ( $user ) {
				if ( ! \MLS_Core::is_user_exempted( $user->ID ) ) {
					return (int) $user->ID;
				}
			},
			$users
		);
		$possible_users = ( ! empty( $users ) ) ? $users : array();

		$i = 0;
		foreach ( $possible_users as $user_id ) {
			if ( get_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_reason', true ) !== $context ) {
				unset( $possible_users[ $i ] );
			}
			++$i;
		}

		$users = $this->remove_unwanted_users( $possible_users, $context, true );

		return $users;
	}

	/**
	 * Removes users IDs from list and leaves only IDs which occur in our desired time frame.
	 *
	 * @param  array  $possible_user_ids - Users to check.
	 * @param  string $type - Type to lookup.
	 * @param  bool   $check_unlocks - Checking for unblocked users.
	 *
	 * @return array
	 *
	 * @since 2.0.0
	 */
	public function remove_unwanted_users( $possible_user_ids, $type, $check_unlocks = false ) {

		$users = array();

		foreach ( $possible_user_ids as $user_id ) {
			if ( 'password_resets' === $type ) {
				$history               = get_user_meta( $user_id, MLS_PW_HISTORY_META_KEY, true );
				if ( ! get_user_meta( $user_id, MLS_PREFIX . '_user_has_manually_reset', true ) ) {
					continue;
				}
				$last_change_timestamp = $history[0]['timestamp'];
			}

			if ( 'inactive' === $type ) {
				$last_change_timestamp = get_user_meta( $user_id, MLS_PREFIX . '_inactive_set_time', true );
				if ( $check_unlocks ) {
					$last_change_timestamp = get_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_time', true );
				}
			}

			if ( 'blocked' === $type ) {
				$last_change_timestamp = get_user_meta( $user_id, MLS_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP_META_KEY, true );
				if ( $check_unlocks ) {
					$last_change_timestamp = get_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_time', true );
				}
			}

			if ( $last_change_timestamp > strtotime( '-1 week' ) ) {
				$userdata  = get_user_by( 'id', $user_id );
				$user_info = array(
					'user_login' => $userdata->user_login,
					'user_role'  => isset( $userdata->roles[0] ) ? $userdata->roles[0] : 'None',
					'timestamp'  => date_i18n( get_option( 'date_format' ), $last_change_timestamp ) . ' ' . date_i18n( get_option( 'time_format' ), $last_change_timestamp ),
				);
				array_push( $users, $user_info );
				if ( $check_unlocks ) {
					// No longer needed, so remove.
					delete_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked' );
					delete_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_time' );
					delete_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_reason' );
				}
			}
		}

		return $users;
	}
}
