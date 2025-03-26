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

		return self::string_to_bool( $global_settings->master_switch );
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
	public static function get_role_options( $role = '' ) {
		$mls     = melapress_login_security();
		$options = ( isset( melapress_login_security()->options ) ) ? melapress_login_security()->options->get_role_options( $role ) : array();
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
		// multiply the value by the unit to get a time in seconds.
		switch ( $unit ) {
			case 'hours':
				$expiry_time = $value * HOUR_IN_SECONDS;
				break;
			case 'days':
				$expiry_time = $value * DAY_IN_SECONDS;
				break;
			case 'weeks':
				$expiry_time = $value * WEEK_IN_SECONDS;
				break;
			case 'months':
				$expiry_time = $value * MONTH_IN_SECONDS;
				break;
			default:
				// assume seconds.
				$expiry_time = $value;
		}
		return $expiry_time;
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
		return ( $blocked_time ) ? $blocked_time : get_user_meta( $user_id, MLS_PREFIX . '_last_activity', true );
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

		$preferred_roles = array_map(
			function ( $role ) {
				return str_replace( ' ', '_', strtolower( $role ) );
			},
			$preferred_roles
		);

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
		$roles     = self::prioritise_roles( $roles );
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
	 * @since 4.4.3
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
	 * Ensure input is ok for specific settings.
	 *
	 * @param   string $setting_key  Setting to clean.
	 * @param   mixed  $value        Current value.
	 *
	 * @return  mixed - Result.
	 */
	public static function sanitise_value_by_key( $setting_key, $value ) {
		$processed_value = false;
		if ( in_array( $setting_key, \MLS\MLS_Options::$policy_boolean_options, true ) || in_array( $setting_key, \MLS\MLS_Options::$settings_boolean_options, true ) ) {
			if ( 'yes' === $value || 'no' === $value ) {
				return $value;
			} else {
				return 'no';
			}
		} elseif ( in_array( $setting_key, \MLS\MLS_Options::$textarea_settings, true ) ) {
			return wp_kses_post( sanitize_textarea_field( $value ) );
		}

		return $value;
	}
}
