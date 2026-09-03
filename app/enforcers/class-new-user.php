<?php
/**
 * Melapress Login Security New User Register
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS;

use MLS\Helpers\OptionsHelper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If check class exists OR not.
if ( ! class_exists( '\MLS\New_User_Register' ) ) {
	/**
	 * Declare New_User_Register Class
	 *
	 * @since 2.0.0
	 */
	class New_User_Register {

		/**
		 * Init hooks.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			// Redirect login page.
			add_action( 'validate_password_reset', array( __CLASS__, 'ppm_validate_password_reset' ), 10, 2 );
			add_action( 'user_profile_update_errors', array( __CLASS__, 'ppm_new_user_errors' ), 10 );
			add_filter( 'login_redirect', array( __CLASS__, 'override_login_redirects' ), 1000, 3 );
		}

		/**
		 * Override login_redirect to ensure we are not taken to a custom page.
		 *
		 * @param  string  $redirect_to - Current redirect.
		 * @param  string  $requested_redirect_to - Requested redirected.
		 * @param  WP_User $user - User to redirect.
		 *
		 * @return string - New redirect.
		 *
		 * @since 2.0.0
		 */
		public static function override_login_redirects( $redirect_to, $requested_redirect_to, $user ) {
			if ( ! empty( $redirect_to ) && is_a( $user, '\WP_User' ) ) {
				if ( get_user_meta( $user->ID, 'mls_temp_user', true ) ) {
					return $redirect_to;
				}

				$reset            = new \MLS\Reset_Passwords();
				$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'new-user' );

				if ( $verify_reset_key ) {
					if ( isset( $verify_reset_key->errors['invalid_key'] ) || empty( $user->user_activation_key ) ) {
						remove_filter( 'allow_password_reset', array( Reset_Passwords::class, 'ppm_is_user_allowed_to_reset' ), 10 );
						$reset_key                    = \MLS\User_Profile::generate_new_reset_key( $user->ID );
						$verify_reset_key             = check_password_reset_key( $reset_key, $user->user_login );
						$verify_reset_key->reset_key  = $reset_key;
						$verify_reset_key->user_login = $user->user_login;
					}

					\MLS_Core::handle_user_redirection( $verify_reset_key, false, true );
				}
			}

			return $redirect_to;
		}

		/**
		 * Change reset password form message.
		 *
		 * @param object $error WP_Error object.
		 * @param object $user User object.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function ppm_validate_password_reset( $error, $user ) {
			// Get user reset key.
			$reset            = new \MLS\Reset_Passwords();
			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'new-user' );

			// Ignore nonce check as we are only using this as a flag.
			// If check reset key exists OR not.
			if ( ( $verify_reset_key && ! $verify_reset_key->errors ) && ( isset( $_GET['action'] ) && 'rp' === $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// Logout current user.
				wp_logout();
				// Login notice.
				add_filter( 'login_message', array( __CLASS__, 'ppm_retrieve_password_message' ) );
			}
		}

		/**
		 * Customize retrieve password message.
		 *
		 * @return string message
		 *
		 * @since 2.0.0
		 */
		public static function ppm_retrieve_password_message() {
			return wp_sprintf( '<p class="message reset-pass">%s</p>', __( 'To ensure you use a strong password, you are required to change your password before you login for the first time.', 'melapress-login-security' ) );
		}

		/**
		 * Adds our error messages to the reset message.
		 *
		 * @param  WP_Error $errors - Current login errors.
		 *
		 * @return WP_Error $errors - Appended errors.
		 *
		 * @since 2.0.0
		 */
		/**
		 * Whether a role is exempt from password policies.
		 *
		 * "Do not enforce password & login policies for this role" is stored as
		 * enforce_password, a name that reads as its own opposite: a truthy value
		 * means the policies do not apply.
		 *
		 * Everywhere else the exemption is reached through
		 * MLS_Core::is_user_exempted(), which resolves it from an account's roles.
		 * That is no help while a user is being created: there is no account to ask
		 * about until the password has already been judged, so nothing consulted
		 * the exemption and an exempt role could not be given a password the
		 * site-wide policy would refuse. The role comes from the submitted form
		 * instead, and get_role_options() resolves inheritance the same way
		 * is_user_exempted() does, so a role inheriting an exempt site-wide policy
		 * is exempt too.
		 *
		 * @param string $role Role slug from the request.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		public static function role_is_exempt( $role ) {
			$role = \sanitize_key( (string) $role );

			if ( '' === $role ) {
				return false;
			}

			$options = OptionsHelper::get_role_options( $role );

			return \property_exists( $options, 'enforce_password' )
				&& OptionsHelper::string_to_bool( $options->enforce_password );
		}

		public static function ppm_new_user_errors( $errors ) {

			// Ignore nonce check as we are only using this as a flag.
			if ( isset( $_POST['from'] ) && 'profile' === $_POST['from'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			$mls     = melapress_login_security();
			$options = $mls->options->users_options;

			$user_settings = $mls->options->users_options;
			$role_setting  = $mls->options->setting_options;

			$options_master_switch    = OptionsHelper::string_to_bool( $options->master_switch );
			$settings_master_switch   = OptionsHelper::string_to_bool( $user_settings->master_switch );
			$inherit_policies_setting = OptionsHelper::string_to_bool( $user_settings->inherit_policies );
			$post_array               = filter_input_array( INPUT_POST );

			$is_needed  = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );
			$post_array = filter_input_array( INPUT_POST );

			if ( $is_needed && isset( $post_array['pass1'] ) && ! empty( $post_array['pass1'] ) ) {
				/*
				 * Judge the password against the policy of the role being created,
				 * not of the administrator doing the creating.
				 *
				 * MLS_Regex compiles its rules for the current user, which on
				 * user-new.php is whoever is logged in. There is no account to point
				 * it at yet, so the role from the request is what it has to go on.
				 * Without this, an Editor policy of twenty characters was enforced
				 * to the administrator's twelve and the new user was created.
				 *
				 * Only on creation: an edit already runs through
				 * Password_Check::validate_for_user(), which points the rules at the
				 * account being edited.
				 */
				if ( isset( $post_array['action'] ) && 'createuser' === $post_array['action'] && ! empty( $post_array['role'] ) ) {
					$target_role = \sanitize_key( $post_array['role'] );

					if ( self::role_is_exempt( $target_role ) ) {
						return $errors;
					}

					\MLS\MLS_Regex::init_for_role( $target_role );
				}

				$pwd_check          = new \MLS\Password_Check();
				$does_violate_rules = $pwd_check->does_violate_rules( $post_array['pass1'] );

				if ( $does_violate_rules ) {
					$errors->add( 'ppm_password_error', __( 'Password does not meet policy requirements.', 'melapress-login-security' ) );
				}
			}

			return $errors;
		}
	}
}
