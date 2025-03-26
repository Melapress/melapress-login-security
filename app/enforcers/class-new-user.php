<?php
/**
 * Melapress Login Security New User Register
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS;

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
		public function init() {
			// Redirect login page.
			add_action( 'validate_password_reset', array( $this, 'ppm_validate_password_reset' ), 10, 2 );
			add_action( 'user_profile_update_errors', array( $this, 'ppm_new_user_errors' ), 10 );
			add_filter( 'login_redirect', array( $this, 'override_login_redirects' ), 1000, 3 );
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
		public function override_login_redirects( $redirect_to, $requested_redirect_to, $user ) {
			if ( ! empty( $redirect_to ) && is_a( $user, '\WP_User' ) ) {
				if ( get_user_meta( $user->ID, 'mls_temp_user', true ) ) {
					return $redirect_to;
				}

				$reset            = new \MLS\MLS_Reset_Passwords();
				$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'new-user' );
				$mls              = \MLS_Core::get_instance();

				if ( $verify_reset_key ) {
					if ( isset( $verify_reset_key->errors['invalid_key'] ) ) {
						$reset_key                    = \MLS\User_Profile::generate_new_reset_key( $user->ID );
						$verify_reset_key             = check_password_reset_key( $reset_key, $user->user_login );
						$verify_reset_key->reset_key  = $reset_key;
						$verify_reset_key->user_login = $user->user_login;
					}

					$mls->handle_user_redirection( $verify_reset_key, false, true );
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
		public function ppm_validate_password_reset( $error, $user ) {
			// Get user reset key.
			$reset            = new \MLS\MLS_Reset_Passwords();
			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'new-user' );

			// Ignore nonce check as we are only using this as a flag.
			// If check reset key exists OR not.
			if ( ( $verify_reset_key && ! $verify_reset_key->errors ) && ( isset( $_GET['action'] ) && 'rp' === $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// Logout current user.
				wp_logout();
				// Login notice.
				add_filter( 'login_message', array( $this, 'ppm_retrieve_password_message' ) );
			}
		}

		/**
		 * Customize retrieve password message.
		 *
		 * @return string message
		 *
		 * @since 2.0.0
		 */
		public function ppm_retrieve_password_message() {
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
		public function ppm_new_user_errors( $errors ) {

			// Ignore nonce check as we are only using this as a flag.
			if ( isset( $_POST['from'] ) && 'profile' === $_POST['from'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			$mls     = melapress_login_security();
			$options = $mls->options->users_options;

			$user_settings = $mls->options->users_options;
			$role_setting  = $mls->options->setting_options;

			$options_master_switch    = \MLS\Helpers\OptionsHelper::string_to_bool( $options->master_switch );
			$settings_master_switch   = \MLS\Helpers\OptionsHelper::string_to_bool( $user_settings->master_switch );
			$inherit_policies_setting = \MLS\Helpers\OptionsHelper::string_to_bool( $user_settings->inherit_policies );
			$post_array               = filter_input_array( INPUT_POST );

			$is_needed  = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );
			$post_array = filter_input_array( INPUT_POST );

			if ( $is_needed && isset( $post_array['pass1'] ) && ! empty( $post_array['pass1'] ) ) {
				$pwd_check          = new \MLS\Password_Check();
				$does_violate_rules = $pwd_check->does_violate_rules( $post_array['pass1'] );

				if ( $does_violate_rules ) {
					$errors->add( 'ppm_password_error', __( 'Password does not meet policy requirements.' ) );
				}
			}

			return $errors;
		}
	}
}
