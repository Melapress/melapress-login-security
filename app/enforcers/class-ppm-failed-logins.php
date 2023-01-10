<?php
/**
 * PPM New User Register
 *
 * @package wordpress
 * @subpackage wpassword
 * @author WP White Security
 */

use \PPMWP\Helpers\OptionsHelper;

/**
 * Check if this class already exists.
 */
if ( ! class_exists( 'PPM_Failed_Logins' ) ) {
	/**
	 * Declare PPM_Failed_Logins Class
	 */
	class PPM_Failed_Logins {

		/**
		 * Init hooks.
		 */
		public function init() {

			// Only load further if needed.
			if ( ! OptionsHelper::getPluginIsEnabled() ) {
				return;
			}

			add_action( 'authenticate', array( $this, 'pre_login_check' ), 20, 3 );
			add_action( 'wp_login_failed', array( $this, 'failed_login_check' ), 10, 2 );			
			add_action( 'wp_login', array( $this, 'clear_failed_login_data' ), 10, 2 );
		}

		/**
		 * Check login to determine if the user is currently blocked
		 *
		 * @param  mixed  $user         WP_User if the user is authenticated. WP_Error or null otherwise.
		 * @param  string $username     Username or email address.
		 * @param  string $password     ser password.
		 *
		 * @return null|WP_User|WP_Error
		 */
		public function pre_login_check( $user, $username, $password ) {

			// If WP has already created an error at this point, pass it back and bail.
			if ( is_wp_error( $user ) ) {
				return $user;
			}

			// Get the user ID, either from the user object if we have it, or by SQL query if we dont.
			$user_id = ( isset( $user->ID ) ) ? $user->ID : $this->get_user_id_from_login_name( $username );

			// If we still have nothing, stop here.
			if ( ! $user_id ) {
				return $user;
			}

			// Return if this user is exempt.
			if ( ppm_is_user_exempted( $user_id ) ) {
				return $user;
			}

			$userdata = get_user_by( 'id', $user_id );

			$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

			if ( OptionsHelper::string_to_bool( $role_options->failed_login_policies_enabled ) ) {

				if ( 'timed' === $role_options->failed_login_unlock_setting ) {

					$login_attempts_transient = $this->get_users_stored_transient_data( $user_id, true );
					$current_time             = current_time( 'timestamp' );

					// See if enough time has passed since last failed attempt.
					$time_difference = ( ! empty( $login_attempts_transient ) ) ? $current_time - $login_attempts_transient < $role_options->failed_login_reset_hours * 60 * 60 : false;

					// Enough time has passed and the user is allowed to reset.
					if ( ! $time_difference ) {
						$this->clear_failed_login_data( $userdata->user_login, $userdata );
					}
				}

				// Check if the user current user has been blocked from further login attemtps.
				$is_user_blocked = get_user_meta( $user_id, PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY, true );

				if ( 'yes' === $is_user_blocked ) {
					$user = new WP_Error( 'ppmwp_login_attempts_blocked', __( 'Your account has surpassed the allowed number of login attempts and can no longer log in.', 'ppm-wp' ) );
				}
			}

			// We must return the user, regardless.
			return $user;
		}

		/**
		 * Logs failed attempt in a transient and determine if this failed attempt surpasses the threshold number of allowed attempts.
		 *
		 * @param  Array  $username Currently logging in user name.
		 * @param  Object $error    Current errors object.
		 *
		 * @return $error           Error object with our errors appended to it.
		 */
		public function failed_login_check( $username, $error = false ) {

			// If user is using an email, act accordingly.
			if ( filter_var( $username, FILTER_VALIDATE_EMAIL ) ) {
				$userdata = get_user_by( 'email', $username );
			} else {
				$userdata = get_user_by( 'login', $username );
			}

			// If we still have nothing, stop here.
			if ( ! $userdata || ! $error ) {
				return;
			}

			// Return if this user is exempt.
			if ( ppm_is_user_exempted( $userdata->ID ) ) {
				return;
			}

			$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

			if ( OptionsHelper::string_to_bool( $role_options->failed_login_policies_enabled ) ) {
				// Setup needed variables for later.
				$max_login_attempts            = $role_options->failed_login_attempts;
				$login_attempts_transient_name = PPMWP_PREFIX . '_user_' . $userdata->ID . '_failed_login_attempts';

				// Get the user ID by SQL query.
				$user_id  = $this->get_user_id_from_login_name( $username );
				// Grab users currently stored attempts.
				$login_attempts_transient = $this->get_users_stored_transient_data( $userdata->ID, false );
				// Check if we have any failed login attempts stored for this user in a transient.
				$current_failed_login_attempts = ( ! empty( $login_attempts_transient ) ) ?	$login_attempts_transient : [];
				// Add this failed attempts to what we have so far.
				array_push( $current_failed_login_attempts, current_time( 'timestamp' ) );
				// Save it, but only upto the number of max allowed attempts - we dont want this thing to bloat.
				set_transient( $login_attempts_transient_name, array_slice( $current_failed_login_attempts, -$max_login_attempts ), 86400 );

				// Now check if, including this most recent attempt, the user has surpassed the max number of allowed attempts.
				if ( count( $current_failed_login_attempts ) >= $max_login_attempts ) {
					// This user has exceed what we allow, so there outta here.
					update_user_meta( $userdata->ID, PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY, 'yes' );
					update_user_meta( $userdata->ID, PPMWP_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP, current_time( 'timestamp' ) );

					if ( ! isset( $error->errors['ppmwp_login_attempts_blocked'] ) ) {
						$error_string = __( 'Your account has surpassed the allowed number of login attempts and can no longer log in.', 'ppm-wp' );
						$error->add( 'ppmwp_login_attempts_blocked', '<br>' . $error_string );
						if ( function_exists( 'wc_add_notice' ) ) {
							wc_add_notice( $error_string, 'notice' );
						}
					}
				}
				// This user has a number of attempts remaining, so lets let them know before they lock themselves out.
				else {
					$attempts_left = $max_login_attempts - count( $current_failed_login_attempts );
					$error_string = sprintf( esc_html( _n(
						'You have %d attempt remaining.',
						'You have %d attempts remaining.',
						$attempts_left,
						'ppm-wp'
					) ), $attempts_left );
					$error->add( 'ppmwp_login_attempts_blocked', '<br>' . $error_string );
					if ( function_exists( 'wc_add_notice' ) ) {
						wc_add_notice( $error_string, 'notice' );
					}
				}	

				return $error;
			}
		}

		/**
		 * Remove the "user blocked" usermeta and any currently held transients upon a succesful login.
		 *
		 * @param  string $username Currently logged in user.
		 * @param  object $user     Currently logged in user object.
		 */
		public function clear_failed_login_data( $username, $user ) {

			// Get the user ID, either from the user object if we have it, or by SQL query if we dont.
			if ( is_numeric( $username ) ) {
				$user_id = $username;
			} else {
				$user_id = ( isset( $user->ID ) ) ? $user->ID : $this->get_user_id_from_login_name( $username );
			}

			if ( $user_id ) {
				$login_attempts_transient_name = PPMWP_PREFIX . '_user_'  . $user_id . '_failed_login_attempts';
				$delete_transient              = delete_transient( $login_attempts_transient_name );
				$unblock_user                  = delete_user_meta( $user_id, PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY );
				$is_blocked_user               = delete_user_meta( $user_id, PPMWP_PREFIX . 'is_blocked_user' );
			}
		}

		/**
		 * Small helper function to return all, or the most recently stored failed login attempts.
		 *
		 * @param  int     $user_id                  User id to lookup.
		 * @param  boolean $return_latest_entry_only Flag to determine if we only want the most recent attempt.
		 *
		 * @return array                             Stored failure attempts.
		 */
		public function get_users_stored_transient_data( $user_id, $return_latest_entry_only = false ) {
			$login_attempts_transient_name = PPMWP_PREFIX . '_user_' . $user_id . '_failed_login_attempts';
			$transient_data                = get_transient( $login_attempts_transient_name );
			$current_time                  = current_time( 'timestamp' );
			$current_time_minus_24_hours   = $current_time - 86400;

			// Remove any attempts older than 24 hours.
			if ( ! empty( $transient_data ) ) {
				foreach ( $transient_data as $key => $login_attempt_timestamp ) {
					if ( $login_attempt_timestamp < $current_time_minus_24_hours ) {
						unset( $transient_data[$key] );
					}
				}
			}

			if ( $return_latest_entry_only && ! empty( $transient_data ) ) {
				$transient_data = end( $transient_data );
			}

			return $transient_data;
		}

		/**
		 * Queries the usermeta table to retrieve a users ID. Leaner than using get_user_by as we dont need the whole user object.
		 *
		 * @param  string $username  Users login name.
		 *
		 * @return string            Users ID.
		 */
		public function get_user_id_from_login_name( $username ) {
			global $wpdb;
			$select    = $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_login = %s", [ $username ] );
			$user_data = $wpdb->get_results( $select );

			if ( isset( $user_data[0] ) ) {
				$user_id = $user_data[0];
				$user_id = $user_id->ID;
				return $user_id;
			}
 		}

		/**
		 * Retreive all IDs for users who are currently blocked.
		 *
		 * @return array Array of user IDs.
		 */
		public function get_all_currently_login_locked_users() {
			global $wpdb;
			$select = '
				SELECT ID FROM ' . $wpdb->users . '
				INNER JOIN ' . $wpdb->usermeta . ' ON ' . $wpdb->users . '.ID = ' . $wpdb->usermeta . '.user_id
				WHERE ' . $wpdb->usermeta . '.meta_key LIKE \'ppmwp_is_blocked_%\'
			';
			$users = $wpdb->get_results( $select );
			$users = array_map( function ( $user ) {
				if ( ! ppm_is_user_exempted( $user->ID ) ) {
					return (int) $user->ID;
				}
			}, $users );
			$users = ( ! empty( $users ) ) ? $users : [];

			return $users;
		}

		/**
		 * Send user a notification email once the account has been unblocked, also reset password if required.
		 *
		 * @param  int  $user_id        		User ID to notify.
		 */
		public function send_logins_unblocked_notification_email_to_user( $user_id, $reset_password ) {

			// Access plugin instance.
			$ppm       = ppm_wp();

			// Grab user data object.
			$user_data = get_userdata( $user_id );

			// Redefining user_login ensures we return the right case in the email.
			$user_login	 = $user_data->user_login;
			$user_email	 = $user_data->user_email;

			// Only reset the password if the role has this option enabled.
			if ( $reset_password ) {
				$key     = get_password_reset_key( $user_data );
				if ( ! is_wp_error( $key ) ) {
					$update  = update_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN, $key );
				}
			}

			// Prepare email details.
			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'wordpress@' . str_ireplace( 'www.', '', parse_url( network_site_url(), PHP_URL_HOST ) );
			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;

			$title      = \PPM_Email_Settings::replace_email_strings( isset( $ppm->options->ppm_setting->user_unblocked_email_title ) ? $ppm->options->ppm_setting->user_unblocked_email_title : \PPM_Email_Settings::get_default_string( 'user_unblocked_email_title' ), $user_id );

			if ( $reset_password ) {
				$login_page = OptionsHelper::get_password_reset_page();
				$msg   = isset( $ppm->options->ppm_setting->user_unblocked_email_reset_message ) ? $ppm->options->ppm_setting->user_unblocked_email_reset_message : \PPM_Email_Settings::get_default_string( 'user_unblocked_email_reset_message' );
				$args['reset_or_continue'] = $msg . ' ' . esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) . "\n";
			} else {
				$msg   = isset( $ppm->options->ppm_setting->user_unblocked_email_continue_message ) ? $ppm->options->ppm_setting->user_unblocked_email_continue_message : \PPM_Email_Settings::get_default_string( 'user_unblocked_email_continue_message' );
				$args['reset_or_continue'] = $msg . "\n";
			}

			$content   = isset( $ppm->options->ppm_setting->user_unblocked_email_body ) ? $ppm->options->ppm_setting->user_unblocked_email_body : \PPM_Email_Settings::default_message_contents( 'user_unblocked' );
			$email_content = \PPM_Email_Settings::replace_email_strings( $content, $user_id, $args );

			// Fire off the mail.
			wp_mail( $user_email, wp_specialchars_decode( $title ), $email_content, $headers );
		}
	}
}
