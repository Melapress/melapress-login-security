<?php
/**
 * Helper class to get options within this plugin.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\MLS_Options;
use MLS\InactiveUsers;

/**
 * Helper class for getting various options for the plugin.
 *
 * @since 2.0.0
 */
class OptionsHelper {

	/**
	 * Checks if inactive users feature should be active.
	 *
	 * This feature has several pre-requesits. It needs to be enabled, the
	 * password expiration feature needs to be enabled and the length on that
	 * passwords expiry needs to be longer than 30 days.
	 *
	 * @method should_inactive_users_feature_be_active
	 *
	 * @param bool $check_failed_logins_also - Check failed logins module too.
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public static function should_inactive_users_feature_be_active( $check_failed_logins_also = false ) {
		$mls = \melapress_login_security();

		// return early if the inactive class already is set active.
		if ( isset( $mls->inactive ) && ! is_bool( $mls->inactive ) && null !== $mls->inactive->is_feature_enabled() ) {
			return $mls->inactive->is_feature_enabled();
		} else {
			// not already determined to be active so assume false till tested.
			$active = false;
		}
		// If accessed early this item can be an array but we always want an
		// object.
		$master_policy = self::get_master_policy_options();
		if ( empty( $master_policy ) || ! isset( $master_policy->inactive_users_enabled ) ) {
			// If empty, then check DB.
			$master_policy = (object) get_site_option( MLS_PREFIX . '_options' );
		}

		// check if we are enabled.
		if (
			( ( isset( $master_policy->inactive_users_enabled ) && self::string_to_bool( $master_policy->inactive_users_enabled ) ) ||
			( $check_failed_logins_also && isset( $master_policy->failed_login_policies_enabled ) && self::string_to_bool( $master_policy->failed_login_policies_enabled ) ) )
		) {
			// master policy sets this as active, no need to do farther checks.
			$active = true;
		}

		// if master policy doesn't make this active check individual roles.
		if ( ! $active ) {
			global $wp_roles;
			$roles = $wp_roles->get_names();
			// loop through roles till we are either active or finished.
			foreach ( $roles as $role => $role_name ) {
				// if we got active in the last run break early.
				if ( $active ) {
					break;
				}
				$role_options = self::get_role_options( $role );

				if ( ( isset( $role_options->inherit_policies ) && self::string_to_bool( $role_options->inherit_policies ) ) || ( isset( $role_options->enforce_password ) && self::string_to_bool( $role_options->enforce_password ) ) ) {
					// policy is inherited from master which  didn't activate
					// this role is excluded from policies so continue.
					continue;
				}
				if (
					( ( isset( $role_options->inactive_users_enabled ) && self::string_to_bool( $role_options->inactive_users_enabled ) ) ||
					( $check_failed_logins_also && isset( $role_options->failed_login_policies_enabled ) && self::string_to_bool( $role_options->failed_login_policies_enabled ) ) )
				) {
					$active = true;
				}
			}
		}

		// feature is enabled if this is true, false by default.
		if ( isset( $mls->inactive ) && ! is_bool( $mls->inactive ) ) {
			$mls->inactive->set_feature_enabled( $active );
		}
		return $active;
	}

	/**
	 * Gets the options for the master policy.
	 *
	 * @method get_master_policy_options
	 *
	 * @return object
	 *
	 * @since 2.0.0
	 */
	public static function get_master_policy_options() {
		$mls           = melapress_login_security();
		$master_policy = ( isset( $mls->options->inherit ) ) ? $mls->options->inherit : array();
		return (object) $master_policy;
	}

	/**
	 * Checks global settings in order to extract the plugin enabled status properly
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public static function get_plugin_is_enabled() {
		$global_settings = self::get_master_policy_options();

		if ( ! is_object( $global_settings ) ) {
			return false;
		}

		// Check if any policy group is enabled.
		if ( isset( $global_settings->enable_password_policies_group ) && self::string_to_bool( $global_settings->enable_password_policies_group ) ) {
			return true;
		}
		if ( isset( $global_settings->enable_session_policies_group ) && self::string_to_bool( $global_settings->enable_session_policies_group ) ) {
			return true;
		}
		if ( isset( $global_settings->enable_device_policies_group ) && self::string_to_bool( $global_settings->enable_device_policies_group ) ) {
			return true;
		}
		if ( isset( $global_settings->enable_login_policies_group ) && self::string_to_bool( $global_settings->enable_login_policies_group ) ) {
			return true;
		}

		// Backward compat: fall back to master_switch.
		return isset( $global_settings->master_switch ) ? self::string_to_bool( $global_settings->master_switch ) : false;
	}

	/**
	 * Checks if a specific policy group is enabled.
	 *
	 * @param string $group The group key: 'password', 'session', 'device', or 'login'.
	 *
	 * @return bool
	 *
	 * @since 2.6.0
	 */
	public static function is_policy_group_enabled( $group ) {
		$global_settings = self::get_master_policy_options();

		if ( ! is_object( $global_settings ) ) {
			return false;
		}

		$key = 'enable_' . $group . '_policies_group';

		return isset( $global_settings->$key ) ? self::string_to_bool( $global_settings->$key ) : false;
	}

	/**
	 * Gets the options for a specific role.
	 *
	 * @method get_role_options
	 * @param  string $role a user role to try get options policy for.
	 *
	 * @return object
	 *
	 * @since 2.0.0
	 */
	/**
	 * Clean a login-redirect target, which is stored as a path and not a URL.
	 *
	 * Both redirect settings on the login-page screen are paths relative to the
	 * site root: the field is rendered inside site_url() with a trailing slash,
	 * and the redirect is built as '/' . rtrim( $value, '/' ). Passing them
	 * through esc_url_raw() turned a bare "reception" into
	 * "http://reception" — esc_url() adds a scheme to anything that has none —
	 * and the resulting redirect target, "//http://reception", is one
	 * wp_safe_redirect() rejects, so the feature stopped working on the first
	 * save after upgrading.
	 *
	 * A leading slash is removed for the same reason: '/' . '/x' is '//x', which
	 * a browser reads as a host and wp_safe_redirect() refuses.
	 *
	 * Anything pointing off-site is dropped rather than stored. That was the
	 * point of validating these fields in the first place, and a redirect to
	 * another host could never work here anyway.
	 *
	 * @param string $value - Raw value from the form or from the database.
	 *
	 * @return string Path relative to the site root, without a leading slash.
	 *
	 * @since 2.4.0
	 */
	public static function sanitize_login_redirect_path( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		// Strip control characters and anything that cannot appear in a path.
		$value = preg_replace( '/[\x00-\x1F\x7F\s]+/', '', $value );

		if ( '' === $value ) {
			return '';
		}

		$has_scheme = (bool) preg_match( '#^[a-z][a-z0-9+.\-]*:#i', $value );

		// A scheme-relative "//host/path" is off-site as far as a browser cares.
		if ( $has_scheme || 0 === strpos( $value, '//' ) ) {
			if ( $has_scheme && ! preg_match( '#^https?://#i', $value ) ) {
				// javascript:, data: and friends are never a redirect target.
				return '';
			}

			$parts = \wp_parse_url( $value );
			$host  = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';
			$home  = strtolower( (string) \wp_parse_url( \home_url(), PHP_URL_HOST ) );
			$path  = isset( $parts['path'] ) ? $parts['path'] : '';

			if ( '' === $host || $host === $home ) {
				// Already this site; the path is the part that means anything.
				$value = $path;
			} elseif ( false === strpos( $host, '.' ) && 'localhost' !== $host ) {
				/*
				 * Not a hostname, so this is a path an earlier build mangled:
				 * esc_url_raw() turned "reception" into "http://reception",
				 * which parses with "reception" as the host and no path. Joining
				 * the two back together returns what was typed, and the value is
				 * only ever used as a path below, so recovering it cannot point
				 * anywhere off this site.
				 */
				$value = $host . $path;
			} else {
				// A real host that is not this one. Refusing it is the whole
				// point of validating these fields.
				return '';
			}

			if ( isset( $parts['query'] ) && '' !== $parts['query'] ) {
				$value .= '?' . $parts['query'];
			}
		}

		$value = ltrim( $value, '/' );

		// No traversal, and no backslashes for a browser to normalise.
		$value = str_replace( array( '..', '\\' ), '', $value );

		return $value;
	}

	public static function get_role_options( $role = '' ) {
		// $mls     = melapress_login_security();
		// $options = ( isset( melapress_login_security()->options ) ) ? melapress_login_security()->options->get_role_options( $role ) : array();

		$options = \get_site_option( MLS_PREFIX . '_' . $role . '_options', MLS_Options::get_default_options() );

		if ( self::string_to_bool( $options['master_switch'] ) ) {
			/*
			 * master_switch on a role record means "inherit the site-wide
			 * policy" — it is what the "Inherit login security policies"
			 * checkbox writes — so the site-wide policy is what applies and the
			 * role's own stored values are deliberately not used.
			 *
			 * This was written as wp_parse_args() with a single argument, which
			 * merges nothing and returns its input unchanged. Same result, but
			 * it read as though defaults were being combined with something.
			 */
			$options = MLS_Options::get_default_options();
		}

		return (object) $options;
	}

	/**
	 * Gets the time, in seconds, that the users password was last reset.
	 *
	 * @method get_password_history_expiry_time_in_seconds
	 * @param  integer $value a value to generate a seconds based time from.
	 * @param  string  $unit  the unit to multiply the value by.
	 *
	 * @return int
	 *
	 * @since 2.0.0
	 */
	public static function get_password_history_expiry_time_in_seconds( $value = 0, $unit = '' ) {
		$expiry_time = 0;
		// if we don't have a unit and value to get time from then get it from master policy.
		if ( empty( $value ) && empty( $unit ) ) {
			$mls = \melapress_login_security();
			// If accessed early this item can be an array but we always want an
			// object.
			$setting_options = ( isset( $mls->options->setting_options ) ) ? $mls->options->setting_options : array();
			$setting_options = is_array( $setting_options ) ? (object) $setting_options : $setting_options;
			// if this array doesn't exist we need to bail early.
			// probably means plugin is not yet fully installed.
			if ( ! isset( $setting_options->password_expiry['value'] ) ) {
				return $expiry_time;
			}
			// can get values from an object.
			$value = (int) $setting_options->password_expiry['value'];
			$unit  = ( isset( $setting_options->password_expiry['unit'] ) ) ? $setting_options->password_expiry['unit'] : false;
		}
		return self::duration_in_seconds( $value, $unit );
	}

	/**
	 * A value-and-unit pair as a number of seconds.
	 *
	 * Both the expiry period and the notification lead time are stored as a
	 * number plus an independent unit, so the numbers alone are not comparable:
	 * 5 days is longer than 2 months only if you ignore the units, which is the
	 * mistake this exists to prevent.
	 *
	 * @param int|string $value - The number.
	 * @param string     $unit  - hours, days, weeks or months. Anything else is treated as seconds.
	 *
	 * @return int Seconds.
	 *
	 * @since 2.4.0
	 */
	/**
	 * Hold the expiry-notification lead time inside the expiry period.
	 *
	 * Both settings are a number plus an independent unit, so comparing the
	 * numbers alone is meaningless — an expiry of 2 months with a notification
	 * 5 days before it used to read as 5 >= 2 and get rewritten to "2 days".
	 * Any configuration whose notification number exceeded the expiry number
	 * was affected, which is most of them once expiry is set in months.
	 *
	 * Nothing is clamped when there is no expiry period. That case used to zero
	 * the notification on *any* save of the policies page, including a save made
	 * for an unrelated setting.
	 *
	 * @param int|string $notify_value - Notification lead number.
	 * @param string     $notify_unit  - Notification lead unit.
	 * @param int|string $expiry_value - Expiry period number.
	 * @param string     $expiry_unit  - Expiry period unit.
	 *
	 * @return array|null array( 'value' => int, 'unit' => string ) when it has to be pulled back, null when it is already inside the period.
	 *
	 * @since 2.4.0
	 */
	public static function clamped_expiry_notification( $notify_value, $notify_unit, $expiry_value, $expiry_unit ) {
		$expiry_seconds = self::duration_in_seconds( $expiry_value, $expiry_unit );

		// No expiry period to measure against.
		if ( $expiry_seconds <= 0 ) {
			return null;
		}

		if ( self::duration_in_seconds( $notify_value, $notify_unit ) < $expiry_seconds ) {
			return null;
		}

		/*
		 * Notify no earlier than the moment the password expires. The unit travels
		 * with the number: copying only the number is what turned "2 months" into
		 * "2 days".
		 */
		return array(
			'value' => (int) $expiry_value,
			'unit'  => (string) $expiry_unit,
		);
	}

	public static function duration_in_seconds( $value, $unit ): int {
		$value = (int) $value;

		switch ( $unit ) {
			case 'hours':
				return $value * HOUR_IN_SECONDS;
			case 'days':
				return $value * DAY_IN_SECONDS;
			case 'weeks':
				return $value * WEEK_IN_SECONDS;
			case 'months':
				return $value * MONTH_IN_SECONDS;
			default:
				// Assume seconds.
				return $value;
		}
	}

	/**
	 * Gets an expiry time for a given user ID - either from master policy or
	 * from a role specific policy.
	 *
	 * @method get_users_password_history_expiry_time_in_seconds
	 * @param  int $user_id a user id to try get a time for.
	 *
	 * @return int
	 *
	 * @since 2.0.0
	 */
	public static function get_users_password_history_expiry_time_in_seconds( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			return 0;
		}

		$history_expiry_time = 0;

		$user = get_userdata( $user_id );
		if ( is_a( $user, '\WP_User' ) ) {
			$user_roles = self::prioritise_roles( $user->roles );
			foreach ( $user_roles as $user_role ) {
				$role_options = self::get_role_options( $user_role );
				if ( ! isset( $role_options->password_expiry['value'] ) || ! isset( $role_options->password_expiry['unit'] ) ) {
					// skip this as the policy doesn't have a history expiry time.
					continue;
				}
				$history_expiry_time = self::get_password_history_expiry_time_in_seconds( $role_options->password_expiry['value'], $role_options->password_expiry['unit'] );
				// break from loop early if we have an expiry from one of the roles.
				if ( $history_expiry_time ) {
					break;
				}
			}
		}
		return $history_expiry_time;
	}

	/**
	 * Get expiry time for a specific user based on ID.
	 *
	 * @param integer $user_id - Lookup ID.
	 *
	 * @return int
	 *
	 * @since 2.0.0
	 */
	public static function get_users_password_expiry_notice_time_in_seconds( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			return 0;
		}

		$history_expiry_time = 0;

		$user = get_userdata( $user_id );
		if ( is_a( $user, '\WP_User' ) ) {
			$user_roles = self::prioritise_roles( $user->roles );
			foreach ( $user_roles as $user_role ) {
				$role_options = self::get_role_options( $user_role );
				if ( ! isset( $role_options->notify_password_expiry_days ) || ! isset( $role_options->notify_password_expiry_unit ) ) {
					// skip this as the policy doesn't have a history expiry time.
					continue;
				}
				$history_expiry_time = strtotime( $role_options->notify_password_expiry_days . ' ' . $role_options->notify_password_expiry_unit, 0 );
				// break from loop early if we have an expiry from one of the roles.
				if ( $history_expiry_time ) {
					break;
				}
			}
		}
		return $history_expiry_time;
	}

	/**
	 * Get the inactive users array.
	 *
	 * @method get_inactive_users
	 *
	 * @return array $users - Found users.
	 *
	 * @since 2.0.0
	 */
	public static function get_inactive_users() {
		$users = get_site_option( MLS_PREFIX . '_inactive_users', array() );
		// if for some reason we have invalid values use empty array.
		if ( ! is_array( $users ) ) {
			$users = array();
		}
		return $users;
	}

	/**
	 * Gets the users last history timestamp from user meta.
	 *
	 * @method get_users_last_history_time
	 * @param  int $user_id a user id.
	 *
	 * @return int
	 *
	 * @since 2.0.0
	 */
	public static function get_users_last_history_time( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			return 0;
		}

		$password_history = get_user_meta( $user_id, MLS_PREFIX . '_last_activity', true );
		return (int) $password_history;
	}

	/**
	 * Runs a check to see if a user that is inactive can still reset due to
	 * them being reset by an admin withing the time frame.
	 *
	 * NOTE: assumes they ARE allowed to reset by default.
	 *
	 * @method is_inactive_user_allowed_to_reset
	 * @param  int $user_id user ID to use.
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public static function is_inactive_user_allowed_to_reset( $user_id = 0 ) {
		$reset_time    = self::get_users_last_history_time( $user_id );
		$reset_allowed = true;
		// If we have a last reset or history time then check it.
		if ( $reset_time ) {
			// If the last reset time + dormancy period is more than current
			// time user is allowed to reset.
			if ( (int) $reset_time + apply_filters( 'ppmwp_adjust_dormancy_period', \MLS\InactiveUsers::DORMANCY_PERIOD ) < current_time( 'timestamp' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
				$reset_allowed = false;
			}
		}

		return $reset_allowed;
	}

	/**
	 * Gets the time a users password last 'expired'.
	 *
	 * NOTE: this value may also be the time user was last reset by admin.
	 *
	 * @method get_user_last_expiry_time
	 * @param  int $user_id user ID to use.
	 *
	 * @return int
	 *
	 * @since 2.0.0
	 */
	public static function get_user_last_expiry_time( $user_id = 0 ) {
		$time = get_user_meta( $user_id, MLS_PREFIX . '_last_activity', true );
		// if we have a time return it otherwise return 0.
		return ( isset( $time ) ) ? $time : 0;
	}

	/**
	 * Sets the users last expiry time - or deletes the key when time === 0;
	 *
	 * @method set_user_last_expiry_time
	 * @param  int/null $time a timestamp to save - when 0 we delete the meta.
	 * @param  integer  $user_id a user ID.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public static function set_user_last_expiry_time( $time, $user_id = 0 ) {
		// if there is no user ID or time to work with bail early.
		if ( empty( $user_id ) ) {
			return;
		}
		// if the user is inactive exempt then delete their expiry and bail.
		if ( \MLS_Core::is_user_exempted( $user_id ) ) {
			delete_user_meta( $user_id, MLS_PREFIX . '_' . \MLS\Password_History::LAST_EXPIRY_TIME_KEY );
			return;
		}
		// if time is zero then delete the key otherwise update with new value.
		if ( 0 === $time ) {
			delete_user_meta( $user_id, MLS_PREFIX . '_' . \MLS\Password_History::LAST_EXPIRY_TIME_KEY );
		} else {
			update_user_meta( $user_id, MLS_PREFIX . '_' . \MLS\Password_History::LAST_EXPIRY_TIME_KEY, $time );
		}
	}

	/**
	 * Sets a user as inactive.
	 *
	 * @method set_user_inactive
	 * @param  int $user_id user ID to use.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public static function set_user_inactive( $user_id = 0 ) {

		/**
		 * Fire of action for others to observe.
		 */
		do_action( 'mls_user_set_as_inactive', $user_id );

		// sets this user metakey to true as a inactive flag on the user.
		update_user_meta( $user_id, MLS_PREFIX . '_' . InactiveUsers::DORMANT_USER_FLAG_KEY, true );
		update_user_meta( $user_id, MLS_PREFIX . '_' . InactiveUsers::DORMANT_SET_TIME, current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	}

	/**
	 * Gets a timestamp of the time when a user was set inactive.
	 *
	 * @method get_inactive_user_time
	 * @param  integer $user_id A user id to work with.
	 *
	 * @return null|int
	 *
	 * @since 2.0.0
	 */
	public static function get_inactive_user_time( $user_id = 0 ) {
		$blocked_time = get_user_meta( $user_id, MLS_PREFIX . '_blocked_since', true );
		if ( $blocked_time ) {
			return $blocked_time;
		}

		if ( class_exists( '\MLS\Admin\User_Helper' ) ) {
			$manual_time = get_user_meta( $user_id, \MLS\Admin\User_Helper::USER_LOCKED_SINCE_META, true );
			if ( $manual_time ) {
				return $manual_time;
			}
		}

		return get_user_meta( $user_id, MLS_PREFIX . '_last_activity', true );
	}

	/**
	 * Clears all relevant data about a inactive user.
	 *
	 * Removes them from the inactive array, deletes their inactive set time and
	 * their inactive flag.
	 *
	 * @method clear_inactive_data_about_user
	 * @param  int  $user_id A user id to work with.
	 * @param  bool $leave_unlock_flag - Leave usermeta or not.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public static function clear_inactive_data_about_user( $user_id = 0, $leave_unlock_flag = false ) {
		if ( ! $user_id || ! is_int( $user_id ) ) {
			return;
		}
		$inactive_users          = self::get_inactive_users();
		$inactive_array_modified = false;

		// remove from the inactive users list.
		// phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict -- don't care if type is string or int.
		if ( isset( $inactive_users ) && in_array( $user_id, $inactive_users ) ) {
			$keys = array_keys( $inactive_users, $user_id );

			// phpcs:enable
			// remove this user from the inactive array.
			if ( ! empty( $keys ) ) {
				$inactive_array_modified = true;
				foreach ( $keys as $key ) {
					unset( $inactive_users[ $key ] );
				}
			}
		}

		if ( $inactive_array_modified ) {
			self::set_inactive_users_array( $inactive_users );
		}

		if ( class_exists( 'MLS\InactiveUsers' ) ) {
			// delete the inactive flag and inactive set time.
			delete_user_meta( $user_id, MLS_PREFIX . '_' . InactiveUsers::DORMANT_USER_FLAG_KEY );
			delete_user_meta( $user_id, MLS_PREFIX . '_' . InactiveUsers::DORMANT_SET_TIME );
		}

		// mark as recently unlocked.
		if ( $leave_unlock_flag ) {
			update_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked', true );
			update_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_time', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			update_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_reason', 'inactive' );
		}
	}

	/**
	 * Adds the initial user that enabled inactive users feature to the list of
	 * users exempt from the checking. This prevents a complete site lockout in
	 * a situation where all user accounts would be inactive locked.
	 *
	 * @method add_initial_user_to_exempt_list
	 * @param  \WP_User $user a user object to maybe be added to inactive exempt list.
	 *
	 * @return bool - Was added.
	 *
	 * @since 2.0.0
	 */
	public static function add_initial_user_to_exempt_list( $user ) {
		$added        = false;
		$mls          = melapress_login_security();
		$exempt_users = isset( $mls->options->mls_setting->exempted['users'] ) ? $mls->options->mls_setting->exempted['users'] : array();
		// if we have an empty list then add this user.
		if ( empty( $exempt_users ) ) {
			$exempt_users[] = (string) $user->ID;
			// update the inactive exempt list adding user that enabled feature.
			$mls->options->mls_setting->exempted['users'] = $exempt_users;
			if ( $mls->options->mls_save_setting( (array) $mls->options->mls_setting ) ) {
				$added = true;
			}
		}
		return $added;
	}

	/**
	 * Get dormancy period for a specific role.
	 *
	 * @param  int $user_id - User ID.
	 *
	 * @return string Time.
	 *
	 * @since 2.0.0
	 */
	public static function get_role_specific_dormancy_period( $user_id ) {
		$user_data = get_userdata( $user_id );
		$roles     = self::prioritise_roles( $user_data->roles );
		foreach ( $roles as $user_role ) {
			$role_options = self::get_role_options( $user_role );

			if ( ! isset( $role_options->inactive_users_expiry['value'] ) || ! isset( $role_options->inactive_users_expiry['unit'] ) ) {
				continue;
			}
			$inactive_expiry_time = $role_options->inactive_users_expiry['value'] . ' ' . $role_options->inactive_users_expiry['unit'];
			// break from loop early if we have an expiry from one of the roles.
			if ( $inactive_expiry_time ) {
				break;
			}
		}

		if ( ! isset( $inactive_expiry_time ) ) {
			$options              = get_site_option( MLS_PREFIX . '_options' );
			$inactive_expiry_time = $options['inactive_users_expiry']['value'] . ' ' . $options['inactive_users_expiry']['unit'];
		}

		$inactive_expiry_time = strtotime( $inactive_expiry_time, 0 );
		return $inactive_expiry_time;
	}

	/**
	 * Converts a string to a bool.
	 *
	 * @param bool $incoming_string String to convert.
	 *
	 * @return string Result.
	 *
	 * @since 2.0.0
	 */
	public static function string_to_bool( $incoming_string ) {
		return is_bool( $incoming_string ) ? $incoming_string : ( 'yes' === $incoming_string || 1 === $incoming_string || 'true' === $incoming_string || '1' === $incoming_string || 'on' === $incoming_string || 'enable' === $incoming_string );
	}

	/**
	 * Converts a bool to a 'yes' or 'no'.
	 *
	 * @param bool $incoming_bool String to convert.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public static function bool_to_string( $incoming_bool ) {
		if ( ! is_bool( $incoming_bool ) ) {
			$incoming_bool = self::string_to_bool( $incoming_bool );
		}
		return true === $incoming_bool ? 'yes' : 'no';
	}

	/**
	 * Turn stored role-order entries into real role slugs.
	 *
	 * The priority list used to be saved as display names, and the slug was
	 * reconstructed from one by lowercasing it and turning spaces into
	 * underscores. That is right only when a role's slug happens to be its
	 * lowercased name, which is true of the core roles and of very little else:
	 * a role registered as add_role( 'mls_seo_mgr', 'SEO Manager' ) normalised
	 * to `seo_manager`, matched nothing, and was silently dropped from the
	 * ordering — so the other role a user held won, and its policies applied
	 * instead. Renamed or localised role names failed the same way.
	 *
	 * Entries are now resolved rather than guessed at: a slug is taken as it
	 * stands, a display name is looked up against the registered roles, and the
	 * old transformation survives only as a last resort for anything that
	 * matches neither. Installations still holding display names therefore keep
	 * working without a migration.
	 *
	 * @param array|string $entries - Stored order, as slugs or display names.
	 *
	 * @return array Role slugs, in the order given.
	 *
	 * @since 2.4.0
	 */
	public static function resolve_role_slugs( $entries ) {
		if ( is_string( $entries ) ) {
			$entries = explode( ',', $entries );
		}

		if ( ! is_array( $entries ) ) {
			return array();
		}

		$role_names = array();

		if ( function_exists( 'wp_roles' ) ) {
			$roles_object = \wp_roles();

			if ( is_object( $roles_object ) && method_exists( $roles_object, 'get_names' ) ) {
				$role_names = (array) $roles_object->get_names();
			}
		}

		// Display name => slug, matched without regard to case.
		$by_name = array();

		foreach ( $role_names as $slug => $name ) {
			$by_name[ strtolower( (string) $name ) ] = $slug;
		}

		$resolved = array();

		foreach ( $entries as $entry ) {
			$entry = trim( (string) $entry );

			if ( '' === $entry ) {
				continue;
			}

			if ( isset( $role_names[ $entry ] ) ) {
				$resolved[] = $entry;
				continue;
			}

			$lookup = strtolower( $entry );

			if ( isset( $by_name[ $lookup ] ) ) {
				$resolved[] = $by_name[ $lookup ];
				continue;
			}

			// Neither a slug nor a name this site knows. Fall back to the old
			// transformation so an entry for a role that is not registered right
			// now — deactivated plugin, say — behaves as it always did.
			$resolved[] = str_replace( ' ', '_', $lookup );
		}

		return array_values( array_unique( $resolved ) );
	}

	/**
	 * Takes the array of roles a user has and sorts them into our own priority.
	 *
	 * @param array $roles - Rule array.
	 *
	 * @return array - Sorted array.
	 *
	 * @since 2.0.0
	 */
	public static function prioritise_roles( $roles = array() ) {
		$mls = melapress_login_security();

		if ( ! isset( $mls->options->mls_setting->multiple_role_order ) ) {
			return $roles;
		}

		$preferred_roles = $mls->options->mls_setting->multiple_role_order;

		if ( empty( $preferred_roles ) ) {
			return $roles;
		}

		$preferred_roles = self::resolve_role_slugs( $preferred_roles );

		$processing_needed = self::string_to_bool( $mls->options->mls_setting->users_have_multiple_roles );
		// Only do this if we want to.
		if ( $processing_needed && count( $roles ) > 1 ) {
			// Sort roles given into the order we want, then trim the unwanted roles leftover.
			$roles = array_intersect( array_replace( $roles, $preferred_roles ), $roles );
		}

		return $roles;
	}

	/**
	 * Sort roles and return options for prefered role.
	 *
	 * @param array $roles - Roles array.
	 *
	 * @return object - Options for role.
	 *
	 * @since 2.0.0
	 */
	public static function get_preferred_role_options( $roles ) {
		$roles     = (array) self::prioritise_roles( $roles );
		$user_role = reset( $roles );

		return self::get_role_options( $user_role );
	}

	/**
	 * SReturn filterable redirect URL.
	 *
	 * @return string - Reset page.
	 *
	 * @since 2.0.0
	 */
	public static function get_password_reset_page() {
		$standard_page = 'wp-login.php';
		return apply_filters( 'mls_reset_reset_pw_login_page', $standard_page );
	}

	/**
	 * Sets the inactive users array.
	 *
	 * Array should be a single dimentional array containing user IDs.
	 *
	 * @method set_inactive_users_array
	 * @param  array $inactive_array an array of `inactive` and `reset` ids.
	 *
	 * @return bool - Was update.
	 *
	 * @since 2.0.0
	 */
	public static function set_inactive_users_array( $inactive_array ) {
		$updated = false;
		if ( is_array( $inactive_array ) ) {
			$updated = update_site_option( MLS_PREFIX . '_inactive_users', $inactive_array );
		}
		return $updated;
	}

	/**
	 * Checks if a user is considered inactive.
	 *
	 * @method is_user_inactive
	 * @param  int $user_id user ID to use.
	 *
	 * @return boolean
	 *
	 * @since 2.0.0
	 */
	public static function is_user_inactive( $user_id = 0 ) {
		if ( class_exists( 'MLS\InactiveUsers' ) ) {
			return get_user_meta( $user_id, MLS_PREFIX . '_' . InactiveUsers::DORMANT_USER_FLAG_KEY, true );
		} else {
			return false;
		}
	}

	/**
	 * Checks whether a user is currently locked by any mechanism.
	 *
	 * Returns the lock source string if locked, or false if not locked.
	 * Possible return values: 'manual', 'failed_logins', 'inactivity', or false.
	 *
	 * @param int $user_id The user ID to check.
	 *
	 * @return string|false The lock source, or false if not locked.
	 *
	 * @since 2.0.0
	 */
	/**
	 * Whether the current user may perform a write whose blast radius is the
	 * whole network.
	 *
	 * `manage_options` is a role capability every subsite administrator holds.
	 * Several handlers reachable from the network admin do network-scoped work
	 * — resetting every password on the network, running a migration across
	 * network options and usermeta, deleting a network option — and gating
	 * those on `manage_options` means the capability check is doing none of the
	 * work. What has been holding is the nonce, and a nonce proves where a
	 * request came from, not what its sender is entitled to do.
	 *
	 * On single site the two are equivalent, so this changes nothing there.
	 *
	 * @return bool
	 *
	 * @since 2.4.0
	 */
	public static function current_user_can_manage_scope() {
		if ( is_multisite() ) {
			return current_user_can( 'manage_network_options' );
		}

		return current_user_can( 'manage_options' );
	}

	public static function is_user_locked_by_any_mechanism( $user_id = 0, $scope = 'request' ) {
		if ( ! $user_id ) {
			return false;
		}

		// Check manual lock first (highest priority / stickiest).
		if ( class_exists( '\MLS\Admin\User_Helper' ) && \MLS\Admin\User_Helper::is_user_locked( $user_id ) ) {
			return 'manual';
		}

		// Check failed-login lock.
		//
		// Two different questions, and conflating them is a bug either way.
		//
		//  'request' — is this account locked out for the source making *this*
		//              request? The only safe basis for an authentication
		//              decision, and the default for that reason. Answering
		//              "any" here would let an attacker who has tripped their
		//              own throttle suppress attempt counting for every other
		//              source, including the account holder's.
		//
		//  'any'     — is this account locked out from anywhere? What an
		//              administrator needs in order to see the lockout and
		//              clear it. Never use it to decide a login.
		//
		// This used to read an account-wide meta flag that nothing writes any
		// more, so it always answered "not locked" and the Locked Users screen
		// went blank.
		//
		// The scope split governs the *per-source* throttle only. A lock left
		// behind by 2.3.x is an account-level state and is honoured in both
		// scopes — see has_legacy_account_lock().
		if ( class_exists( '\MLS\Failed_Logins' ) ) {
			/*
			 * The account-wide flag written by 2.3.x is an account-level state and
			 * is honoured in both scopes.
			 *
			 * The scope distinction above exists because the *per-source* throttle
			 * must not let one source lock an account for everyone. It does not
			 * apply to this flag: nothing writes it any more, so an attacker
			 * cannot create it, and it is what the Locked Users screen shows and
			 * clears. Folding it into the request-scoped branch meant a site that
			 * upgraded mid-lockout displayed the account as locked while allowing
			 * it to log in.
			 */
			if ( \MLS\Failed_Logins::has_legacy_account_lock( $user_id ) ) {
				return 'failed_logins';
			}

			/*
			 * Request scope asks is_source_locked_out(), not is_source_throttled().
			 *
			 * The attempt counter is a transient, and on multisite transients are
			 * per-blog, so the counter alone let a locked-out user log in on any
			 * other site in the network. is_source_locked_out() also consults the
			 * lock record, which is user meta and therefore network-wide, matched
			 * on the same (account, source) key — so the lock follows the user
			 * across sites without becoming an account-wide lock that an attacker
			 * could trip for somebody else.
			 */
			$locked_out = ( 'any' === $scope )
				? \MLS\Failed_Logins::has_active_lock_event( $user_id )
				: \MLS\Failed_Logins::is_source_locked_out( $user_id );

			if ( $locked_out ) {
				return 'failed_logins';
			}
		}

		// Check inactivity lock.
		if ( self::is_user_inactive( $user_id ) ) {
			return 'inactivity';
		}

		return false;
	}

	/**
	 * Fully unlocks a user by clearing ALL lock types at once.
	 *
	 * This ensures a single unlock action restores access regardless of how
	 * many policies would otherwise apply (R1 requirement).
	 *
	 * @param int    $user_id            The user ID to unlock.
	 * @param string $unlock_reason_label Short label for the unlock reason (e.g. 'blocked', 'inactive', 'manual').
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public static function fully_unlock_user( $user_id = 0, $unlock_reason_label = 'unlocked' ) {
		if ( ! $user_id ) {
			return;
		}

		// 1. Clear failed-login lock data.
		if ( class_exists( '\MLS\Failed_Logins' ) ) {
			\MLS\Failed_Logins::clear_failed_login_data( $user_id, false );
		}

		// 2. Clear inactivity lock data.
		self::clear_inactive_data_about_user( $user_id, false );

		// 3. Clear manual lock data.
		if ( class_exists( '\MLS\Admin\User_Helper' ) ) {
			\MLS\Admin\User_Helper::unlock_user( $user_id );
			\MLS\Admin\User_Helper::remove_user_locked_reason( $user_id );
		}

		// 4. Reset last activity time so inactivity timer starts fresh.
		update_user_meta( $user_id, MLS_PREFIX . '_last_activity', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		// 5. Reset last expiry time.
		self::set_user_last_expiry_time( current_time( 'timestamp' ), $user_id ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		// 6. Mark as recently unlocked.
		update_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked', true );
		update_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_time', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		update_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_reason', $unlock_reason_label );
	}

	/**
	 * Recursive argument parsing
	 *
	 * This acts like a multi-dimensional version of wp_parse_args() (minus
	 * the querystring parsing - you must pass arrays).
	 *
	 * Values from $a override those from $b; keys in $b that don't exist
	 * in $a are passed through.
	 *
	 * This is different from array_merge_recursive(), both because of the
	 * order of preference ($a overrides $b) and because of the fact that
	 * array_merge_recursive() combines arrays deep in the tree, rather
	 * than overwriting the b array with the a array.
	 *
	 * The implementation of this function is specific to the needs of
	 * BP_Group_Extension, where we know that arrays will always be
	 * associative, and that an argument under a given key in one array
	 * will be matched by a value of identical depth in the other one. The
	 * function is NOT designed for general use, and will probably result
	 * in unexpected results when used with data in the wild. See, eg,
	 * http://core.trac.wordpress.org/ticket/19888
	 *
	 * @param array $a - Array 1.
	 * @param array $b - Array 2.
	 * @param array $remove_orphans - remove empties.
	 *
	 * @return array
	 *
	 * @since 2.0.0
	 */
	public static function recursive_parse_args( &$a, $b, $remove_orphans = false ) {
		$a          = (array) $a;
		$b          = (array) $b;
		$r          = $b;
		$do_removal = false;

		if ( $remove_orphans ) {
			// Items which used to exist in $b but dont in the new settings.
			$orphaned_keys = array_diff_key( $b, $a );
			if ( ! empty( $orphaned_keys ) ) {
				foreach ( $orphaned_keys as $key => $val ) {
					unset( $r[ $key ] );
				}
			}
		}

		foreach ( $a as $k => &$v ) {
			if ( 'users' === $k ) {
				$do_removal = true;
			}

			if ( is_array( $v ) && isset( $r[ $k ] ) ) {
				$r[ $k ] = self::recursive_parse_args( $v, $r[ $k ], $do_removal );
			} else {
				$r[ $k ] = $v;
			}
		}

		return $r;
	}

	/**
	 * House all allowed markup for use in our plugin.
	 *
	 * @return array - Our args.
	 *
	 * @since 2.0.0
	 */
	public static function get_allowed_kses_args() {
		$wp_kses_args = array(
			'input'    => array(
				'type'                    => array(),
				'id'                      => array(),
				'name'                    => array(),
				'value'                   => array(),
				'size'                    => array(),
				'class'                   => array(),
				'min'                     => array(),
				'max'                     => array(),
				'required'                => array(),
				'checked'                 => array(),
				'onkeydown'               => array(),
				'data-toggle-target'      => array(),
				'style'                   => array(),
				'data-toggle-other-areas' => array(),
				'data-export-wpws-users'  => array(),
				'data-import-wpws-users'  => array(),
				'data-nonce'              => array(),
			),
			'select'   => array(
				'class' => array(),
				'id'    => array(),
				'name'  => array(),
			),
			'option'   => array(
				'id'       => array(),
				'name'     => array(),
				'value'    => array(),
				'selected' => array(),
			),
			'tr'       => array(
				'valign' => array(),
				'class'  => array(),
				'id'     => array(),
			),
			'th'       => array(
				'scope' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'thead'    => array(
				'scope' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'tbody'    => array(
				'scope' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'tfoot'    => array(
				'scope' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'td'       => array(
				'class' => array(),
				'id'    => array(),
			),
			'fieldset' => array(
				'class' => array(),
				'id'    => array(),
			),
			'legend'   => array(
				'class' => array(),
				'id'    => array(),
			),
			'label'    => array(
				'for'   => array(),
				'class' => array(),
				'id'    => array(),
			),
			'p'        => array(
				'class' => array(),
				'id'    => array(),
			),
			'span'     => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'li'       => array(
				'class'         => array(),
				'id'            => array(),
				'data-role-key' => array(),
			),
			'ul'       => array(
				'class' => array(),
				'id'    => array(),
			),
			'a'        => array(
				'class'             => array(),
				'id'                => array(),
				'style'             => array(),
				'data-tab-target'   => array(),
				'data-wizard-goto'  => array(),
				'data-check-inputs' => array(),
				'data-nonce'        => array(),
				'href'              => array(),
				'target'            => array(),
			),
			'h3'       => array(
				'class' => array(),
			),
			'b'        => array(),
			'i'        => array(),
			'div'      => array(
				'style' => array(),
				'class' => array(),
				'id'    => array(),
			),
			'table'    => array(
				'class' => array(),
				'id'    => array(),
			),
			'strong'   => array(
				'class' => array(),
				'id'    => array(),
			),
			'img'      => array(
				'class' => array(),
				'src'   => array(),
				'id'    => array(),
			),
			'textarea' => array(
				'class' => array(),
				'name'  => array(),
				'rows'  => array(),
				'cols'  => array(),
				'id'    => array(),
			),
			'script'   => array(
				'type' => array(),
			),
			'style'    => array(
				'class' => array(),
			),
			'details'  => array(
				'class' => array(),
			),
			'summary'  => array(
				'class' => array(),
			),
			'pre'      => array(
				'class' => array(),
			),
			'br'       => array(
				'class' => array(),
			),
		);
		return $wp_kses_args;
	}

	/**
	 * Simple checker for admin facing notices.
	 *
	 * @return int - Count.
	 *
	 * @since 2.0.0
	 */
	public static function get_current_notices_count() {
		$count = 0;

		if ( get_site_option( MLS_PREFIX . '_update_notice_needed', false ) ) {
			++$count;
		}

		if ( get_site_option( 'mls_migration_required' ) || get_site_option( 'ppm_migration_required' ) ) {
			++$count;
		}

		return $count;
	}

	/**
	 * Returns the default role for the given user
	 *
	 * @param null|int|\WP_User $user - The WP user.
	 *
	 * @return array
	 *
	 * @since 2.0.0
	 */
	public static function get_user_roles( $user = null ) {

		if ( is_multisite() ) {
			$blog_id = \get_current_blog_id();

			if ( ! is_user_member_of_blog( $user->ID, $blog_id ) ) {

				$user_blog_id = \get_active_blog_for_user( $user->ID );

				if ( null !== $user_blog_id ) {

					$user = new \WP_User(
					// $user_id
						$user->ID,
						// $name | login, ignored if $user_id is set
						'',
						// $blog_id
						$user_blog_id->blog_id
					);
				}
			}
		}

		return $user->roles;
	}

	/**
	 * Strip all content from a given variable and return only numbers.
	 *
	 * @param   mixed $target - Item to clean.
	 *
	 * @return  mixed - Cleaned input.
	 *
	 * @since 2.1.0
	 */
	public static function strip_all_but_numeric( $target ) {
		if ( is_bool( $target ) ) {
			return false;
		}
		return preg_replace( '/[^0-9]/', '', (string) $target );
	}

	/**
	 * Ensure a given input is yes or no only.
	 *
	 * @param string $value - input string.
	 *
	 * @return string  - Cleaned input.
	 *
	 * @since 2.1.0
	 */
	public static function sanitize_yes_no_input( $value ) {
		if ( 'yes' === $value || true === $value || 1 === $value || '1' === $value ) {
			return 'yes';
		}

		return 'no';
	}

	/**
	 * Ensure input is ok for specific settings.
	 *
	 * @param   string $setting_key  Setting to clean.
	 * @param   mixed  $value        Current value.
	 *
	 * @return  mixed - Result.
	 *
	 * @since 2.1.0
	 */
	public static function sanitise_value_by_key( $setting_key, $value ) {
		$processed_value = '';

		if ( in_array( $setting_key, MLS_Options::$policy_boolean_options, true ) || in_array( $setting_key, MLS_Options::$settings_boolean_options, true ) ) {
			$processed_value = self::sanitize_yes_no_input( $value );

		} elseif ( in_array( $setting_key, MLS_Options::$textarea_settings, true ) ) {
			$processed_value = wp_kses_post( $value );

		} elseif ( is_array( $value ) ) {
			$processed_value = array();

			switch ( $setting_key ) {
				case 'password_expiry':
				case 'inactive_users_expiry':
				case 'remember_session_expiry':
				case 'password_reset_key_expiry':
				case 'default_session_expiry':
					$valid_units = array(
						'months',
						'days',
						'hours',
						'seconds',
					);

					if ( isset( $value['unit'] ) && in_array( $value['unit'], $valid_units, true ) ) {
						$processed_value['value'] = self::strip_all_but_numeric( $value['value'] );
						$processed_value['unit']  = $value['unit'];
					}

					break;

				case 'ui_rules':
					$processed_value['history']               = self::sanitize_yes_no_input( $value['history'] );
					$processed_value['username']              = self::sanitize_yes_no_input( $value['username'] );
					$processed_value['length']                = self::sanitize_yes_no_input( $value['length'] );
					$processed_value['numeric']               = self::sanitize_yes_no_input( $value['numeric'] );
					$processed_value['mix_case']              = self::sanitize_yes_no_input( $value['mix_case'] );
					$processed_value['special_chars']         = self::sanitize_yes_no_input( $value['special_chars'] );
					$processed_value['exclude_special_chars'] = self::sanitize_yes_no_input( $value['exclude_special_chars'] );
					break;

				case 'rules':
					$processed_value['length']                = self::sanitize_yes_no_input( $value['length'] );
					$processed_value['numeric']               = self::sanitize_yes_no_input( $value['numeric'] );
					$processed_value['upper_case']            = self::sanitize_yes_no_input( $value['upper_case'] );
					$processed_value['lower_case']            = self::sanitize_yes_no_input( $value['lower_case'] );
					$processed_value['special_chars']         = self::sanitize_yes_no_input( $value['special_chars'] );
					$processed_value['exclude_special_chars'] = self::sanitize_yes_no_input( $value['exclude_special_chars'] );
					break;

				case 'timed_logins_schedule':
					$days       = array(
						'monday',
						'tuesday',
						'wednesday',
						'thursday',
						'friday',
						'saturday',
						'sunday',
					);
					$valid_keys = array(
						'enable',
						'from_hr',
						'from_min',
						'from_am_or_pm',
						'to_hr',
						'to_min',
						'to_am_or_pm',
					);

					foreach ( $days as $day ) {
						if ( isset( $value[ $day ] ) && is_array( $value[ $day ] ) ) {
							$processed_value[ $day ] = array(
								'enable'        => self::sanitize_yes_no_input( $value[ $day ]['enable'] ?? 'no' ),
								'from_hr'       => self::strip_all_but_numeric( $value[ $day ]['from_hr'] ?? '9' ),
								'from_min'      => self::strip_all_but_numeric( $value[ $day ]['from_min'] ?? '00' ),
								'from_am_or_pm' => ( isset( $value[ $day ]['from_am_or_pm'] ) && in_array( $value[ $day ]['from_am_or_pm'], array( 'am', 'pm' ), true ) ) ? $value[ $day ]['from_am_or_pm'] : 'am',
								'to_hr'         => self::strip_all_but_numeric( $value[ $day ]['to_hr'] ?? '5' ),
								'to_min'        => self::strip_all_but_numeric( $value[ $day ]['to_min'] ?? '00' ),
								'to_am_or_pm'   => ( isset( $value[ $day ]['to_am_or_pm'] ) && in_array( $value[ $day ]['to_am_or_pm'], array( 'am', 'pm' ), true ) ) ? $value[ $day ]['to_am_or_pm'] : 'pm',
							);
						}
					}

					break;

				case 'enabled_questions':
					foreach ( $value as $question_key => $question ) {
						$processed_value[ sanitize_key( $question_key ) ] = sanitize_textarea_field( $question );
					}
					break;

				case 'exempted':
					if ( isset( $value['users'] ) ) {
						foreach ( $value['users'] as $index => $user_id ) {
							$processed_value['users'][ $index ] = self::strip_all_but_numeric( $user_id );
						}
					}
					break;

				case 'multiple_role_order':
					global $wp_roles;

					if ( ! isset( $wp_roles ) ) {
						$wp_roles = new \WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					}

					$role_names = array_values( $wp_roles->get_names() );
					$role_slugs = array_keys( $wp_roles->get_names() );

					foreach ( $value as $input_role_name ) {
						if ( '' === $input_role_name ) {
							continue;
						}
						if ( in_array( $input_role_name, $role_names, true ) || in_array( $input_role_name, $role_slugs, true ) ) {
							$processed_value[] = $input_role_name;
						}
					}

					break;

				default:
					$processed_value = false;
			}
		} elseif ( ! is_array( $value ) ) {
			// Handle numeric settings.
			$numeric_settings = array(
				'min_length',
				'password_history',
				'failed_login_attempts',
				'failed_login_reset_attempts',
				'failed_login_reset_hours',
				'restrict_login_ip_count',
				'notify_password_expiry_days',
				'min_answered_needed_count',
				'password_expiry_email_limit_count',
			);

			// Handle plain text/string settings.
			$text_settings = array(
				'send_summary_email_day',
				'use_custom_from_email',
				'from_email',
				'from_display_name',
				'excluded_special_chars',
				'custom_login_url',
				'custom_login_redirect',
				'restrict_login_allowed_ips',
				'restrict_login_redirect_url',
				'restrict_login_bypass_slug',
				'password_expiry_email_limit',
				'restrict_login_credentials',
				'failed_login_unlock_setting',
				'notify_password_expiry_unit',
				'login_geo_method',
				'login_geo_action',
				'login_geo_countries',
				'login_geo_redirect_url',
				'iplocate_api_key',
				'currently_editing_role',
				'recognized_device_duration',
			);

			if ( in_array( $setting_key, $numeric_settings, true ) ) {
				$processed_value = self::strip_all_but_numeric( $value );
			} elseif ( in_array( $setting_key, $text_settings, true ) ) {
				$processed_value = \sanitize_text_field( $value );
			}
		}

		return $processed_value;
	}

	/**
	 * The option and user-meta key prefixes this plugin owns.
	 *
	 * `ppmwp_` is the pre-2.0 name and is still present on sites upgraded from
	 * WP Password Policy Manager. MLS_PREFIX is one or the other, so the list is
	 * deduplicated.
	 *
	 * The trailing underscore matters. Matching on `mls` alone also matched
	 * every key in the table-prefix namespace of a site whose prefix begins with
	 * those three letters — on the development site, prefix `mlst_`, that was
	 * `mlst_user_roles` in the options table and `mlst_capabilities` plus
	 * `mlst_user_level` for every user. Underscore-delimited, none of them match.
	 *
	 * @return string[]
	 *
	 * @since 2.4.0
	 */
	public static function owned_key_prefixes() {
		return array_values(
			array_unique(
				array(
					MLS_PREFIX . '_',
					'mls_',
					'ppmwp_',
				)
			)
		);
	}

	/**
	 * Whether a key belongs to WordPress rather than to this plugin.
	 *
	 * Core namespaces a handful of option and user-meta keys with the site's
	 * table prefix. A site whose table prefix is exactly `mls_` — plausible
	 * enough, MLS is also Multiple Listing Service — puts those keys inside this
	 * plugin's own prefix, where the delimiter cannot separate them. They are
	 * named explicitly instead.
	 *
	 * Deleting them would remove the site's role definitions and every user's
	 * capabilities; exporting them would carry them into a settings file.
	 *
	 * @param string $key Option name or meta key.
	 *
	 * @return bool
	 *
	 * @since 2.4.0
	 */
	public static function is_reserved_key( $key ) {
		global $wpdb;

		$reserved = array(
			'user_roles',
			'capabilities',
			'user_level',
			'user-settings',
			'user-settings-time',
			'dashboard_quick_press_last_post_id',
			'autosave_draft_ids',
		);

		$prefixes = array_unique(
			array_filter(
				array(
					(string) $wpdb->prefix,
					(string) $wpdb->base_prefix,
				)
			)
		);

		foreach ( $prefixes as $prefix ) {
			if ( 0 !== strpos( (string) $key, $prefix ) ) {
				continue;
			}

			// Per-blog user meta is {base_prefix}{blog_id}_capabilities.
			$suffix = preg_replace( '/^\\d+_/', '', substr( (string) $key, strlen( $prefix ) ) );

			if ( in_array( $suffix, $reserved, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Options holding a credential, which must neither leave nor enter a site.
	 *
	 * A settings file is downloaded, emailed, attached to support tickets and
	 * committed to repositories. These belong to one installation's licence and
	 * are useless anywhere else even if they were safe to move.
	 *
	 * @return string[]
	 *
	 * @since 2.4.0
	 */
	public static function credential_option_names() {
		return array(
			'mls_edd_license_key',
			'mls_edd_license_data',
		);
	}

	/**
	 * Options that describe *this installation* rather than its configuration.
	 *
	 * Everything here is either meaningless on another site or actively harmful
	 * when written there, so none of it is exported and none of it is accepted on
	 * import. The reasons differ and are worth keeping written down:
	 *
	 * `_inactive_users` is a list of **user IDs**. Import it elsewhere and
	 * whoever happens to hold those IDs on the target is marked inactive —
	 * possibly an administrator.
	 *
	 * `_activation` is the fallback "last password change" baseline for any user
	 * with no password history, read by Check_User_Expiry::should_password_expire(),
	 * the inactive-users background process and the reports table. Importing
	 * another site's timestamp changes whether users' passwords count as expired.
	 *
	 * `_plugin_version` is the migration gate — Migration::$version_option_name.
	 * Writing a newer value than the target has actually reached would skip its
	 * migrations permanently. `_active_version`, `_group_toggles_migrated` and the
	 * `_migration_*` family are the same hazard in smaller form.
	 *
	 * The licensing status keys are an installation's entitlement state, not
	 * settings.
	 *
	 * @return string[]
	 *
	 * @since 2.4.0
	 */
	public static function non_portable_option_names() {
		$names = array(
			'mls_edd_license_status',
			'mls_edd_premium',
			'mls_licensing_provider',

			/*
			 * When this installation last saw a valid licence.
			 *
			 * It is licence bookkeeping, not configuration: it opens the grace
			 * window that keeps a site working through a failed check. Carried
			 * into another site it would describe a licence that site never had,
			 * and carried back in later it would describe a moment that has
			 * nothing to do with the receiving install.
			 *
			 * Nothing is lost by dropping it. Both providers re-stamp it on the
			 * next successful activation or check, and the grace test seeds it
			 * from the current time when it finds nothing — so a site without it
			 * gets a full window rather than an expired one.
			 */
			'mls_edd_license_last_valid',
			'mls_fs_license_last_valid',
		);

		// Both spellings: `ppmwp_` is the pre-2.0 prefix and the importer still
		// accepts several of these names under it.
		foreach ( array( 'mls', 'ppmwp' ) as $prefix ) {
			$names[] = $prefix . '_activation';
			$names[] = $prefix . '_active_version';
			$names[] = $prefix . '_plugin_version';
			$names[] = $prefix . '_inactive_users';
			$names[] = $prefix . '_group_toggles_migrated';

			// One-shot and dismissal flags: post-activation redirect, update and
			// promotional notices, and whether this site's administrator has
			// dismissed them. None of it is configuration.
			$names[] = $prefix . '_redirect_to_settings';
			$names[] = $prefix . '_update_notice_needed';
			$names[] = $prefix . '_feature_highlight_needed';
			$names[] = $prefix . '_show_update_notice';
			$names[] = $prefix . '_extra_event_banner';
			$names[] = $prefix . '_extra_event_banner_dismissed';
			$names[] = $prefix . '_extra_event_banner_end_date';
			$names[] = $prefix . '_extra_event_banner_super_dismissed';

			$names[] = $prefix . '_200_migration_complete';
			$names[] = $prefix . '_migration_started';
			$names[] = $prefix . '_migration_status';
			$names[] = $prefix . '_migration_required';
		}

		$names[] = 'ppm_migration_required';

		return array_values( array_unique( $names ) );
	}

	/**
	 * Whether an option may cross between sites in a settings file.
	 *
	 * One predicate for both directions, so the export cannot omit something the
	 * import would still accept, or the reverse.
	 *
	 * @param string $option_name Option or network-meta name.
	 *
	 * @return bool
	 *
	 * @since 2.4.0
	 */
	public static function is_portable_option( $option_name ) {
		$option_name = (string) $option_name;

		if ( in_array( $option_name, self::credential_option_names(), true ) ) {
			return false;
		}

		if ( in_array( $option_name, self::non_portable_option_names(), true ) ) {
			return false;
		}

		// Belongs to WordPress, not to this plugin.
		return ! self::is_reserved_key( $option_name );
	}

	/**
	 * Setting keys that hold a secret and must never leave the site.
	 *
	 * These live inside the settings array, so filtering an export by option
	 * name cannot reach them. The IP-geolocation API key is billable and was
	 * being written verbatim into the settings file an administrator downloads
	 * and, routinely, shares or commits.
	 *
	 * @return string[]
	 *
	 * @since 2.4.0
	 */
	public static function secret_setting_keys() {
		return array(
			'iplocate_api_key',
		);
	}

	/**
	 * Strip secret settings out of an exported value.
	 *
	 * @param mixed $value Option value, of any shape.
	 *
	 * @return mixed
	 *
	 * @since 2.4.0
	 */
	public static function redact_secret_settings( $value ) {
		$secrets = self::secret_setting_keys();

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $inner ) {
				if ( in_array( (string) $key, $secrets, true ) ) {
					unset( $value[ $key ] );
					continue;
				}

				$value[ $key ] = self::redact_secret_settings( $inner );
			}

			return $value;
		}

		if ( is_object( $value ) ) {
			foreach ( get_object_vars( $value ) as $key => $inner ) {
				if ( in_array( (string) $key, $secrets, true ) ) {
					unset( $value->$key );
					continue;
				}

				$value->$key = self::redact_secret_settings( $inner );
			}
		}

		return $value;
	}

	/**
	 * Add one to a counter held in a transient, without losing concurrent hits.
	 *
	 * Every rate limit in the plugin used to read a transient, add one in PHP
	 * and write it back. Two requests that arrive together both read the same
	 * number and both write the same number plus one, so the counter advances by
	 * one instead of two. An attacker sending requests in parallel therefore gets
	 * roughly as many extra attempts as they are willing to open connections —
	 * which is precisely the thing a rate limit is supposed to stop, and it costs
	 * them nothing to do.
	 *
	 * The increment happens in the store instead. With an external object cache
	 * that is `wp_cache_incr()`, which is atomic; without one the transient is a
	 * row in the options table and MySQL adds one to it in a single statement.
	 * Neither can lose a hit.
	 *
	 * @param string $transient Transient name, without the `_transient_` prefix.
	 * @param int    $window    Lifetime in seconds, applied when seeding.
	 *
	 * @return int The counter's value after this call.
	 *
	 * @since 2.4.0
	 */
	public static function increment_counter( $transient, $window, $network_wide = false ) {
		$window = max( 60, (int) $window );

		/*
		 * $network_wide stores the counter as a site transient.
		 *
		 * On multisite a plain transient lives in the current blog's options
		 * table, so a per-blog counter means the configured allowance is spent
		 * again on every site — three attempts on a three-site network is nine.
		 * A site transient lives in sitemeta, so the allowance is what the
		 * administrator actually set. On single site the two are the same store
		 * under a different prefix, so behaviour there is unchanged.
		 */
		$getter      = $network_wide ? 'get_site_transient' : 'get_transient';
		$setter      = $network_wide ? 'set_site_transient' : 'set_transient';
		$cache_group = $network_wide ? 'site-transient' : 'transient';

		if ( \wp_using_ext_object_cache() ) {
			// The getter reads this same group when an object cache is in use.
			$value = \wp_cache_incr( $transient, 1, $cache_group );

			if ( false !== $value ) {
				return (int) $value;
			}

			// Nothing to increment, or a pre-2.4.0 list rather than a count.
			$existing = $getter( $transient );
			$seed     = is_array( $existing ) ? count( $existing ) + 1 : 1;

			$setter( $transient, $seed, $window );

			return $seed;
		}

		global $wpdb;

		// Also discards the row if it has expired, so the counter restarts.
		$current = $getter( $transient );

		/*
		 * Not a number means either nothing is stored yet, or the value was
		 * written by a version that kept a list rather than a count — the
		 * failed-login counter held an array of timestamps until 2.4.0, and
		 * those transients are still live when a site updates. `option_value + 1`
		 * against a serialized array is an error under strict SQL mode, so the
		 * counter is restarted from what is there instead.
		 */
		if ( false === $current || ! is_numeric( $current ) ) {
			$seed = is_array( $current ) ? count( $current ) + 1 : 1;

			$setter( $transient, $seed, $window );

			return $seed;
		}

		$option = ( $network_wide ? '_site_transient_' : '_transient_' ) . $transient;

		/*
		 * A network-wide counter on multisite lives in sitemeta, not options —
		 * update_site_option() writes there. The single-site case is the options
		 * table either way, only the prefix differs.
		 */
		if ( $network_wide && \is_multisite() ) {
			$network_id = \get_current_network_id();

			$updated = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE `{$wpdb->sitemeta}` SET `meta_value` = `meta_value` + 1 WHERE `meta_key` = %s AND `site_id` = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$option,
					$network_id
				)
			);

			if ( ! $updated ) {
				$setter( $transient, (int) $current + 1, $window );

				return (int) $current + 1;
			}

			// get_network_option() caches under "{network id}:{option}".
			\wp_cache_delete( $network_id . ':' . $option, 'site-options' );

			return (int) $getter( $transient );
		}

		$updated = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE `{$wpdb->options}` SET `option_value` = `option_value` + 1 WHERE `option_name` = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$option
			)
		);

		if ( ! $updated ) {
			$setter( $transient, (int) $current + 1, $window );

			return (int) $current + 1;
		}

		// Transients that carry an expiry are never autoloaded, so only this
		// one entry needs invalidating.
		\wp_cache_delete( $option, 'options' );

		return (int) $getter( $transient );
	}
}
