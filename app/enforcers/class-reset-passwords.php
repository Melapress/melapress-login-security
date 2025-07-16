<?php
/**
 * Handle PW resets.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS;

use MLS\TemporaryLogins\Temporary_Logins;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\MLS\Reset_Passwords' ) ) {

	/**
	 * Resets passwords
	 *
	 * @since 2.0.0
	 */
	class Reset_Passwords {

		/**
		 * Hooks delayed password reset to login if option is checked
		 *
		 * @since 2.0.0
		 */
		public function hook() {
			$mls = melapress_login_security();
			// Hook the function only if reset delay is checked in the options.
			if ( $mls->options->mls_setting->terminate_session_password ) {
				add_action( 'wp_authenticate', array( $this, 'check_on_login' ), 0, 2 );
			}

			// Customize password reset key expiry time.
			add_filter( 'password_reset_expiration', array( $this, 'customize_reset_key_expiry_time' ) );

			add_filter( 'allow_password_reset', array( __CLASS__, 'ppm_is_user_allowed_to_reset' ), 10, 2 );
			add_filter( 'send_retrieve_password_email', array( __CLASS__, 'send_reset_mail' ), 10, 3 );
			add_filter( 'user_row_actions', array( __CLASS__, 'allowed_actions' ), 10, 2 );
			add_action( 'lostpassword_errors', array( __CLASS__, 'lostpassword_form' ), 10, 2 );
			add_filter( 'mepr-validate-forgot-password', array( $this, 'mepr_forgot_password' ), 10, 1 );
		}

		/**
		 * Add action link to users bulk actions.
		 *
		 * @param array $bulk_actions - Current actions.
		 *
		 * @return array $bulk_actions - modified actions.
		 *
		 * @since 2.0.0
		 */
		public static function add_bulk_action_link( $bulk_actions ) {
			$bulk_actions['mls-reset-password'] = __( 'Reset password', 'melapress-login-security' );
			return $bulk_actions;
		}

		/**
		 * Handle action.
		 *
		 * @param string $redirect_url - Current URL.
		 * @param string $action - Current action.
		 * @param array  $user_ids - IDs to check.
		 *
		 * @return string - Resulting URL.
		 *
		 * @since 2.0.0
		 */
		public static function handle_bulk_action_link( $redirect_url, $action, $user_ids ) {
			if ( 'mls-reset-password' === $action ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return $redirect_url;
				}
				foreach ( $user_ids as $user_id ) {
					self::reset_user( $user_id, false, true );
				}
				$redirect_url = add_query_arg( 'mls-reset-password', count( $user_ids ), $redirect_url );
			}
			return $redirect_url;
		}

		/**
		 * Show notice on bulk actions.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function bulk_action_admin_notice() {
			if ( isset( $_REQUEST['mls-reset-password'] ) && ! empty( $_REQUEST['mls-reset-password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$num_changed = (int) $_REQUEST['mls-reset-password']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				/* translators: %s: User count. */
				printf( '<div id="message" class="updated notice is-dismissable"><p>' . esc_html__( 'Reset %d users.', 'melapress-login-security' ) . '</p></div>', esc_attr( $num_changed ) );
			}
		}

		/**
		 * Process global resets.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function process_global_password_reset() {
			// Grab POSTed data.
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

			// Check nonce.
			if ( ! current_user_can( 'manage_options' ) || empty( $nonce ) || ! $nonce || ! wp_verify_nonce( $nonce, 'mls_mass_reset' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			$current_user = wp_get_current_user();

			$reset_type    = isset( $_POST['reset_type'] ) ? sanitize_text_field( wp_unslash( $_POST['reset_type'] ) ) : false;
			$role          = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : false;
			$file_text     = isset( $_POST['file_text'] ) ? wp_unslash( $_POST['file_text'] ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$users         = isset( $_POST['users'] ) ? wp_unslash( $_POST['users'] ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$include_self  = isset( $_POST['include_self'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['include_self'] ) ) ? true : false;
			$send_reset    = isset( $_POST['send_reset'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['send_reset'] ) ) ? true : false;
			$kill_sessions = isset( $_POST['kill_sessions'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['kill_sessions'] ) ) ? true : false;
			$reset_when    = isset( $_POST['reset_when'] ) ? sanitize_text_field( wp_unslash( $_POST['reset_when'] ) ) : false;

			/**
			 * Fire of action for others to observe.
			 */
			do_action( 'mls_global_password_change_triggered', $reset_type, $role, $users, $include_self, $send_reset, $kill_sessions, $reset_when );

			if ( 'reset-all' === $reset_type ) {
				if ( ! $include_self ) {
					self::reset_all( false, $kill_sessions, $send_reset, true, $reset_when );
				} else {
					self::reset_all( true, $kill_sessions, $send_reset, true, $reset_when );
				}
			} elseif ( 'reset-role' === $reset_type ) {
				$exempted_users = array();
				if ( isset( $mls->options->mls_setting->exempted['users'] ) && ! empty( $mls->options->mls_setting->exempted['users'] ) && \is_array( $mls->options->mls_setting->exempted['users'] ) ) {
					$exempted_users = $mls->options->mls_setting->exempted['users'];
				}

				if ( ! $include_self ) {
					array_push( $exempted_users, get_current_user_id() );
				}

				// exclude exempted roles and users.
				// $user_args = array(
				// 	'blog_id' => 0,
				// 	'role'    => $role,
				// 	'exclude' => $exempted_users,
				// 	'fields'  => array( 'ID' ),
				// );

				// $users = get_users( $user_args );

				$user_args = array(
					'role__in'    => $role,
					'excluded_users' => $exempted_users,
				);

				$users = MLS_Options::get_all_users_data( 'query', $user_args );

				foreach ( $users as $user ) {
					self::reset_user( $user->ID, $kill_sessions, $send_reset, $reset_when );
				}
			} elseif ( 'reset-users' === $reset_type ) {
				foreach ( $users as $user_id ) {
					self::reset_user( $user_id, $kill_sessions, $send_reset, $reset_when );
				}
			} elseif ( 'reset-csv' === $reset_type ) {
				$users       = explode( ',', $file_text );
				$reset_count = 0;
				foreach ( $users as $user_id ) {
					$user = get_userdata( $user_id );
					if ( false !== $user ) {
						$reset_count = ++$reset_count;
						self::reset_user( $user_id, $kill_sessions, $send_reset, $reset_when );
					}
				}
				if ( 0 === $reset_count ) {
					wp_send_json_error( esc_html__( 'No valid user data found in file, please try again', 'melapress-login-security' ) );
				}
			} else {
				wp_send_json_error( esc_html__( 'No valid reset type given', 'melapress-login-security' ) );
			}

			wp_send_json_success( esc_html__( 'Reset complete.', 'melapress-login-security' ) );
		}

		/**
		 * Handling reset of individual user.
		 *
		 * @param int     $user_id - ID to reset.
		 * @param boolean $kill_sessions - Destroy session on reset.
		 * @param boolean $send_reset - Send reset email.
		 * @param string  $reset_when - Is delayed flag.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function reset_user( $user_id, $kill_sessions = false, $send_reset = false, $reset_when = '' ) {
			$user = get_user_by( 'ID', $user_id );

			if ( get_user_meta( $user_id, 'mls_temp_user', true ) ) {
				return;
			}

			$is_delayed = false;
			if ( ! empty( $reset_when ) && 'reset-login' === $reset_when ) {
				\MLS\User_Profile::generate_new_reset_key( $user->ID );
				$is_delayed = true;
			}

			if ( $kill_sessions ) {
				$mls = melapress_login_security();
				$mls->ppm_user_session_destroy( $user->ID );
			}

			if ( $send_reset ) {
				delete_user_meta( $user_id, MLS_EXPIRED_EMAIL_SENT_META_KEY );
			}

			self::reset_by_id( $user->ID, $user->data->user_pass, 'admin', $is_delayed, $kill_sessions, $send_reset, true );
		}

		/**
		 * Monitor for memberpress password reset requests.
		 *
		 * @param  array $post - Posted data.
		 *
		 * @return array $post - Posted data.
		 *
		 * @since 2.0.0
		 */
		public function mepr_forgot_password( $post ) {
			if ( isset( $_POST['mepr_process_forgot_password_form'] ) && isset( $_POST['mepr_user_or_email'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( filter_var( wp_unslash( $_POST['mepr_user_or_email'] ), FILTER_VALIDATE_EMAIL ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$user = get_user_by( 'email', sanitize_text_field( wp_unslash( $_POST['mepr_user_or_email'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				} else {
					$user = get_user_by( 'login', sanitize_text_field( wp_unslash( $_POST['mepr_user_or_email'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}
			}

			if ( ! isset( $user->ID ) ) {
				return $post;
			}

			$allow = self::ppm_is_user_allowed_to_reset( true, $user->ID );

			if ( class_exists( '\MeprUtils' ) ) {
				if ( is_wp_error( $allow ) ) {
					if ( ! isset( $mepr_options ) ) {
						$mepr_options = \MeprOptions::fetch();
					}
					$login_url           = \MeprUtils::get_permalink( $mepr_options->login_page_id );
					$login_delim         = \MeprAppCtrl::get_param_delimiter_char( $login_url );
					$forgot_password_url = "{$login_url}{$login_delim}action=forgot_password&error=failed";

					// Handle password reset form.
					if ( isset( $_REQUEST['action'] ) && 'forgot_password' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$mls             = melapress_login_security();
						$default_options = \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->inherit['master_switch'] ) ? $mls->options->inherit : array();

						// Get user by ID.
						$roles        = $user->roles;

						$roles = (array) \MLS\Helpers\OptionsHelper::prioritise_roles( $roles );
						$roles = reset( $roles );

						$options = get_site_option( MLS_PREFIX . '_' . $roles . '_options', $default_options );
						if ( isset( $options['disable_self_reset'] ) && \MLS\Helpers\OptionsHelper::string_to_bool( $options['disable_self_reset'] ) ) {
							$post['mepr_user_or_email'] = esc_attr( $options['disable_self_reset_message'] );
						}
					} else {
						\MeprUtils::wp_redirect( $forgot_password_url );
					}
				}
			}

			return $post;
		}

		/**
		 * Resets a user's password.
		 *
		 * @global object  $wpdb
		 * @param  integer $user_id The user ID.
		 * @param  string  $current_password - Current password.
		 * @param  string  $by Reset by system, admin or user.
		 * @param  bool    $is_delayed - Is delayed reset..
		 * @param  bool    $kill_sessions - Did reset.
		 * @param  bool    $send_reset - Did reset.
		 * @param  bool    $is_global_reset - Did reset.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function reset_by_id( $user_id, $current_password, $by = 'system', $is_delayed = false, $kill_sessions = false, $send_reset = false, $is_global_reset = false ) {
			$mls = melapress_login_security();

			// we can't reset without a user ID.
			if ( false === $user_id ) {
				return;
			}

			// create a password event.
			$password_event = array(
				'password'  => $current_password,
				'timestamp' => current_time( 'timestamp' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
				'by'        => $by,
			);

			// push current password to password history of the user.
			\MLS\Password_History::push( $user_id, $password_event );

			/**
			 * Fire of action for others to observe.
			 */
			do_action( 'mls_user_password_reset_by_id', $user_id, $by, $is_delayed, $kill_sessions, $is_global_reset );

			if ( in_array( $by, array( 'admin', 'system' ), true ) ) {
				if ( $is_global_reset ) {
					if ( $kill_sessions ) {
						update_user_meta( $user_id, MLS_PASSWORD_EXPIRED_META_KEY, 1 );
						$mls->ppm_user_session_destroy( $user_id, false );
					}
					if ( $is_delayed ) {
						self::delayed_reset( $user_id );
					}
					if ( $send_reset ) {
						self::send_reset_email( $user_id, $by, false, $is_delayed );
					}
				} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
					// update user's expired status 1.
					if ( \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->terminate_session_password ) ) {
						$mls->ppm_user_session_destroy( $user_id );
					} else {
						self::delayed_reset( $user_id );
					}
					update_user_meta( $user_id, MLS_PASSWORD_EXPIRED_META_KEY, 1 );
					self::send_reset_email( $user_id, $by );
				}
			}
		}

		/**
		 * Sends reset email to user. Message depends on $by value
		 *
		 * @param int    $user_id        User ID.
		 * @param string $by             Can be 'system' or 'admin'. Depending on its value different messages are sent.
		 * @param bool   $return_on_fail Flag to determine if we return or die on mail failure.
		 * @param bool   $is_delayed     Is delayed or instant.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function send_reset_email( $user_id, $by, $return_on_fail = false, $is_delayed = false ) {
			// Check if message has already been sent.
			$email_sent = get_user_meta( $user_id, MLS_EXPIRED_EMAIL_SENT_META_KEY, true );

			if ( $email_sent ) {
				return;
			}

			$user_data = get_user_by( 'id', $user_id );

			if ( ! is_a( $user_data, '\WP_User' ) ) {
				return;
			}

			$role_options = \MLS\Helpers\OptionsHelper::get_preferred_role_options( $user_data->roles );

			// Redefining user_login ensures we return the right case in the email.
			$user_login = $user_data->user_login;
			$user_email = $user_data->user_email;
			$key        = get_password_reset_key( $user_data );
			$login_page = \MLS\Helpers\OptionsHelper::get_password_reset_page();
			$message    = false;

			if ( empty( $user_email ) ) {
				return;
			}

			if ( ! is_wp_error( $key ) ) {
				if ( 'admin' === $by ) {
					if ( $is_delayed ) {
						if ( isset( $role_options->disable_user_password_reset_email ) && \MLS\Helpers\OptionsHelper::string_to_bool( $role_options->disable_user_password_reset_email ) ) {
							return;
						}
						/* translators: Password reset email subject. 1: Site name */
						$title = \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_delayed_reset_email_subject' ), $user_id );

						$content = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_delayed_reset_email_body' );
						$message = \MLS\EmailAndMessageStrings::replace_email_strings( $content, $user_id, array( 'reset_url' => esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) ) );
					} else {
						if ( isset( $role_options->disable_user_delayed_password_reset_email ) && \MLS\Helpers\OptionsHelper::string_to_bool( $role_options->disable_user_delayed_password_reset_email ) ) {
							return;
						}
						/* translators: Password reset email subject. 1: Site name */
						$title = \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_reset_email_subject' ), $user_id );

						$content = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_reset_email_body' );
						$message = \MLS\EmailAndMessageStrings::replace_email_strings( $content, $user_id, array( 'reset_url' => esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) ) );
					}
				} else {
					if ( isset( $role_options->disable_user_pw_expired_email ) && \MLS\Helpers\OptionsHelper::string_to_bool( $role_options->disable_user_pw_expired_email ) ) {
						return;
					}
					$title   = \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_password_expired_email_subject' ), $user_id );
					$content = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_password_expired_email_body' );
					$message = \MLS\EmailAndMessageStrings::replace_email_strings( $content, $user_id, array( 'reset_url' => esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) ) );
				}
			}

			// Only send the email if allowed in settings.
			if ( $is_delayed && isset( $role_options->disable_user_password_reset_email ) && \MLS\Helpers\OptionsHelper::string_to_bool( $role_options->disable_user_password_reset_email ) ) {
				return;
			} elseif ( ! $is_delayed && isset( $role_options->disable_user_delayed_password_reset_email ) && \MLS\Helpers\OptionsHelper::string_to_bool( $role_options->disable_user_delayed_password_reset_email ) ) {
				return;
			}

			if ( $message && ! \MLS\Emailer::send_email( $user_email, wp_specialchars_decode( $title ), $message ) ) {
				$fail_message = __( 'The email could not be sent.', 'melapress-login-security' ) . "<br />\n" . __( 'Possible reason: your host may have disabled the mail() function.', 'melapress-login-security' );
				// Remove flag so we can try again.
				delete_user_meta( $user_id, MLS_EXPIRED_EMAIL_SENT_META_KEY );
				if ( $return_on_fail ) {
					return $fail_message;
				} else {
					wp_die( wp_kses_post( $fail_message ) );
				}
			}

			// Update usermeta so we know we have sent a message.
			update_user_meta( $user_id, MLS_EXPIRED_EMAIL_SENT_META_KEY, true );
		}

		/**
		 * Send notification email to admins upon global reset.
		 *
		 * @return bool - Result.
		 *
		 * @since 2.0.0
		 */
		public static function send_admin_email() {
			$user_data = get_userdata( get_current_user_id() );

			$message  = __( 'All passwords have been reset for:', 'melapress-login-security' ) . "\r\n\r\n";
			$message .= network_home_url( '/' ) . "\r\n\r\n";

			if ( is_multisite() ) {
				$blogname = get_network()->site_name;
			} else {
				/*
				 * The blogname option is escaped with esc_html on the way into the database
				 * in sanitize_option we want to reverse this for the plain text arena of emails.
				 */
				$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			}
			/* translators: Password reset email subject. 1: Site name */
			$title = sprintf( __( '[%s] Global Password Reset Complete', 'melapress-login-security' ), $blogname );

			$mls = melapress_login_security();

			$from_email = $mls->options->mls_setting->from_email ? $mls->options->mls_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;

			if ( $message && ! \MLS\Emailer::send_email( $user_data->user_email, wp_specialchars_decode( $title ), $message, $headers ) ) {
				wp_die( esc_html__( 'The email could not be sent.', 'melapress-login-security' ) . "<br />\n" . esc_html__( 'Possible reason: your host may have disabled the mail() function.', 'melapress-login-security' ) );
			}
			return true;
		}

		/**
		 * Reset all users.
		 *
		 * @param boolean $skip_self - Skip admin.
		 * @param boolean $kill_sessions - Destroy sessions.
		 * @param boolean $send_reset - Send email.
		 * @param boolean $is_global_reset - Is global.
		 * @param string  $reset_when - Is reset for now or later.
		 *
		 * @return bool - Result of email.
		 *
		 * @since 2.0.0
		 */
		public static function reset_all( $skip_self = true, $kill_sessions = false, $send_reset = false, $is_global_reset = false, $reset_when = false ) {
			$mls = melapress_login_security();

			$exempted_users = array();
			if ( isset( $mls->options->mls_setting->exempted['users'] ) && ! empty( $mls->options->mls_setting->exempted['users'] ) && \is_array( $mls->options->mls_setting->exempted['users'] ) ) {
				$exempted_users = $mls->options->mls_setting->exempted['users'];
			}

			// Nonce was checked prior to this call via process_reset.
			if ( isset( $_POST['current_user'] ) || ! $skip_self ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				array_push( $exempted_users, get_current_user_id() );
			}

			// exclude exempted roles and users.
			$user_args = array(
				'blog_id' => 0,
				'exclude' => $exempted_users,
				'fields'  => array( 'ID' ),
			);

			update_site_option( MLS_PREFIX . '_reset_timestamp', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

			// Send users for bg processing later.
			$total_users        = self::count_users();
			$batch_size         = 50;
			$slices             = ceil( $total_users / $batch_size );
			$users              = array();
			$background_process = new \MLS\Reset_User_PW_Process();

			for ( $count = 0; $count < $slices; $count++ ) {
				$user_args['number'] = $batch_size;
				$user_args['offset'] = $count * $batch_size;
				$users               = get_users( $user_args );

				if ( ! empty( $users ) ) {
					foreach ( $users as $user ) {
						if ( ! Temporary_Logins::is_valid_temp_user( $user ) ) {
							$item = array(
								'ID'              => $user->ID,
								'kill_sessions'   => $kill_sessions,
								'send_reset'      => $send_reset,
								'is_global_reset' => $is_global_reset,
								'reset_when'      => $reset_when,
							);
							$background_process->push_to_queue( $item );
						}
					}
				}
			}

			// Fire off bg processes.
			$background_process->save()->dispatch();

			return self::send_admin_email();
		}

		/**
		 * Flag user for reset later.
		 *
		 * @param  int  $user_id - User ID to flag.
		 * @param  bool $send_reset - Send email.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function delayed_reset( $user_id, $send_reset = false ) {
			if ( \MLS_Core::is_user_exempted( $user_id ) ) {
				return;
			}

			// Update user meta.
			update_user_meta( $user_id, MLS_DELAYED_RESET_META_KEY, true );

			if ( $send_reset ) {
				self::send_reset_email( $user_id, 'admin', false, true );
			}
		}

		/**
		 * Runs on every login request
		 *
		 * @param type $user_login - User login name.
		 * @param type $user_password - User PW.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function check_on_login( $user_login, $user_password ) {
			if ( empty( $user_login ) || empty( $user_password ) ) {
				return;
			}

			$user = get_user_by( 'login', $user_login );
			if ( $user ) {
				self::maybe_reset( $user->ID );
			}
		}

		/**
		 * Tries to reset the password if needed
		 *
		 * @param type $user_id - User ID.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		private static function maybe_reset( $user_id ) {
			if ( ! self::should_password_reset( $user_id ) ) {
				return;
			}

			$user_data        = get_userdata( $user_id );
			$current_password = $user_data->user_pass;

			self::reset_user( $user_id, $current_password, 'admin' );
			delete_user_meta( $user_id, MLS_DELAYED_RESET_META_KEY );
		}

		/**
		 * Returns whether the password should be reset or not
		 *
		 * @param type $user_id - User ID.
		 *
		 * @return boolean
		 *
		 * @since 2.0.0
		 */
		private static function should_password_reset( $user_id ) {
			return get_user_meta( $user_id, MLS_DELAYED_RESET_META_KEY, true ) === 1;
		}

		/**
		 * Modify the default reset expiry time of 24 hours to a setting of the admins choosing.
		 *
		 * @param int $expiration Default expiry time.
		 *
		 * @return string - New time.
		 *
		 * @since 2.0.0
		 */
		public function customize_reset_key_expiry_time( $expiration ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			$mls             = melapress_login_security();
			$number          = $mls->options->mls_setting->password_reset_key_expiry['value'];
			$unit            = ( 'days' === $mls->options->mls_setting->password_reset_key_expiry['unit'] ) ? DAY_IN_SECONDS : HOUR_IN_SECONDS;
			$new_expiry_time = $number * $unit;
			return $new_expiry_time;
		}

		/**
		 * Get user reset by user ID.
		 *
		 * @param  object $user User object.
		 * @param  string $meta_key Reset type.
		 *
		 * @return object
		 *
		 * @since 2.0.0
		 */
		public function ppm_get_user_reset_key( $user, $meta_key ) {
			$verify_reset_key = false;

			if ( is_a( $user, '\WP_User' ) ) {
				$user_id    = $user->ID;
				$user_login = $user->user_login;

				$usermeta_key = ( 'new-user' === $meta_key ) ? MLS_NEW_USER_META_KEY : MLS_USER_RESET_PW_ON_LOGIN_META_KEY;

				// User get reset by user ID.
				$reset_key = get_user_meta( $user_id, $usermeta_key, true );

				// If check reset key exists OR not.
				if ( $reset_key ) {
					$verify_reset_key             = check_password_reset_key( $reset_key, $user_login );
					$verify_reset_key->reset_key  = $reset_key;
					$verify_reset_key->user_login = $user_login;
				}
			}

			return $verify_reset_key;
		}

		/**
		 * Allows the retrieve password to be send or not.
		 *
		 * @param bool    $send       Whether to send the email.
		 * @param string  $user_login The username for the user.
		 * @param WP_User $user_data  WP_User object.
		 *
		 * @return bool
		 *
		 * @since 2.2.0
		 */
		public static function send_reset_mail( $send, $user_login, $user_data ) {

			if ( \is_a( $user_data, '\WP_User' ) ) {
				$allowed = self::ppm_is_user_allowed_to_reset( $send, $user_data->ID, true );
				if ( \is_wp_error( $allowed ) ) {
					return false;
				}
				return $allowed;
			}

			return $send;
		}

		/**
		 * Check for exclusion.
		 *
		 * @param \WP_Error      $errors    A WP_Error object containing any errors generated
		 *                                 by using invalid credentials.
		 * @param \WP_User|false $user_data WP_User object if found, false if the user does not exist.
		 *
		 * @return \WP_Error
		 *
		 * @since 2.2.0
		 */
		public static function lostpassword_form( $errors, $user_data ) {
			if ( \is_a( $user_data, '\WP_User' ) ) {
				$allowed = self::ppm_is_user_allowed_to_reset( true, $user_data->ID, true );

				if ( \is_wp_error( $allowed ) ) {
					$errors->add( $allowed->get_error_code(), $allowed->get_error_message(), $allowed->get_error_data() );
				}
			}

			return $errors;
		}

		/**
		 * Checks and removes reset link from user actions row if the given user is no allowed to.
		 *
		 * @param array    $actions - Array with the current actions.
		 * @param \WP_User $user_object - The user object.
		 *
		 * @return array
		 *
		 * @since 2.2.0
		 */
		public static function allowed_actions( $actions, $user_object ) {
			if ( \is_a( $user_object, '\WP_User' ) && isset( $actions['resetpassword'] ) && ! empty( $actions['resetpassword'] ) ) {
				$allowed = self::ppm_is_user_allowed_to_reset( true, $user_object->ID, true );

				if ( \is_wp_error( $allowed ) || false === (bool) $allowed ) {
					unset( $actions['resetpassword'] );
				}
			}

			return $actions;
		}

		/**
		 * Check if users is allowed to reset.
		 *
		 * @param  bool $allow - Is currently allowed.
		 * @param  int  $user_id - User ID.
		 * @param  bool $ignore_request - Should the request part must be ignored or not.
		 *
		 * @return bool|\WP_error
		 *
		 * @since 2.0.0
		 */
		public static function ppm_is_user_allowed_to_reset( $allow, $user_id, bool $ignore_request = false ) {

			if ( \MLS_Core::is_user_exempted( $user_id ) ) {
				return true;
			}

			$mls             = melapress_login_security();
			$default_options = \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->inherit['master_switch'] ) ? $mls->options->inherit : array();

			// Get user by ID.
			$get_userdata = get_user_by( 'ID', $user_id );
			$roles        = $get_userdata->roles;

			$roles = (array) \MLS\Helpers\OptionsHelper::prioritise_roles( $roles );

			// If we reach this point with no default options, stop here.
			if ( ! $roles || ! is_array( $roles ) ) {
				if ( is_multisite() ) {
					$roles = array( 'subscriber' );
				} else {
					return true;
				}
			}

			$roles = reset( $roles );

			if ( ! $ignore_request ) {
				$allowed_actions = array(
					'resetpassword',
					'mls_unlock_inactive_user',
					'wp_ppm_reset_user_pw',
				);

				// Allow if request is from an admin.
				if ( ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $allowed_actions, true ) ) || ( isset( $_REQUEST['from'] ) && isset( $_REQUEST['action'] ) && 'update' === $_REQUEST['action'] && 'profile' === $_REQUEST['from'] ) || ( isset( $_REQUEST['action'] ) && 'unlock' === $_REQUEST['action'] && isset( $_REQUEST['page'] ) && 'mls-locked-users' === $_REQUEST['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$user          = wp_get_current_user();
					$allowed_roles = array( 'administrator' );
					if ( array_intersect( $allowed_roles, $user->roles ) ) {
						return true;
					}
				}
			}

			if ( get_user_meta( $user_id, MLS_USER_RESET_PW_ON_LOGIN_META_KEY, true ) ) {
				return true;
			}

			// Check if user is currently considered to be 'locked'.
			$is_user_blocked = get_user_meta( $user_id, MLS_USER_BLOCK_FURTHER_LOGINS_META_KEY, true );

			$options = get_site_option( MLS_PREFIX . '_' . $roles . '_options', $default_options );


			// Get option by role name.
			if ( isset( $options['disable_self_reset'] ) && \MLS\Helpers\OptionsHelper::string_to_bool( $options['disable_self_reset'] ) ) {
				/**
				 * Fire of action for others to observe.
				 */
				do_action( 'mls_user_reset_request_blocked', $get_userdata->ID );
				return new \WP_Error( 'reset_disabled', \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'password_reset_request_disabled_message' ), $get_userdata->ID ) );
			}

			return true;
		}

		/**
		 * Counts the users on multisite network
		 *
		 * @return int
		 *
		 * @since 2.2.0
		 */
		public static function count_users(): int {
			global $wpdb;

			$query             =
					'SELECT count(ID) as users FROM ' . $wpdb->users;
					$db_result = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			return (int) $db_result[0]['users'];
		}
	}
}
