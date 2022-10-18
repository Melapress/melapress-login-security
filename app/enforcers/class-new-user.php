<?php
/**
 * PPM New User Register
 *
 * @package wordpress
 * @subpackage wpassword
 * @author WP White Security
 */

// If check class exists OR not.
if ( ! class_exists( 'PPM_New_User_Register' ) ) {
	/**
	 * Declare PPM_New_User_Register Class
	 */
	class PPM_New_User_Register {

		/**
		 * Init hooks.
		 */
		public function init() {
			// Redirect login page.
			add_action( 'validate_password_reset', array( $this, 'ppm_validate_password_reset' ), 10, 2 );
			add_action( 'wp_login', array( $this, 'ppm_first_time_login' ), 10, 2 );
			add_action( 'user_profile_update_errors', array( $this, 'ppm_new_user_errors' ), 10, 3 );
			add_filter( 'login_redirect', array( $this, 'override_login_redirects' ), 1000, 3 );
		}

		/**
		 * Redirect user after successful.
		 *
		 * @param object $user_login Current user login.
		 * @param object $user User object.
		 */
		public function ppm_first_time_login( $user_login, $user ) {

			// Get user reset key.
			$reset = new PPM_WP_Reset();
			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'new-user' );

			// If check reset key exists OR not.
			if ( $verify_reset_key && ! $verify_reset_key->errors ) {
				// Handle users directly registered using Restrict Content.
				if ( isset( $_REQUEST['action'] ) && 'rc_process_registration_form' === $_REQUEST['action'] ) {
					$redirect_to = add_query_arg(
						array(
							'action' => 'rp',
							'key'    => $verify_reset_key->reset_key,
							'login'  => rawurlencode( $verify_reset_key->user_login ),
						),
						network_site_url( 'wp-login.php' )
					);
					wp_send_json_success( array(
						'success'  => true,
						'redirect' => $redirect_to
					) );
				} else {
					$redirect_to = add_query_arg(
						array(
							'action' => 'rp',
							'key'    => $verify_reset_key->reset_key,
							'login'  => rawurlencode( $verify_reset_key->user_login )
						),
						network_site_url( 'wp-login.php' )
					);

					wp_safe_redirect( $redirect_to );
					die;
				}
			} elseif ( isset( $verify_reset_key->errors['expired_key'] ) && ! empty( $verify_reset_key->errors['expired_key'] ) ) {

				// If a user has reached this point, they have a valid key in the correct place,
				// but they have taken too long to reset, so we reset the key and send them back to login.

				// Create new reset key for this user.
				$key    = get_password_reset_key( $user );

				if ( ! is_wp_error( $key ) ) {
					// Update user with new key information.
					$update = update_user_meta( $user->ID, PPM_WP_META_NEW_USER, $key );
				}

				// Send user back to login.
				$redirect_to = add_query_arg(
					array(
						'action' => 'rp',
						'key'    => $verify_reset_key->reset_key,
						'login'  => rawurlencode( $verify_reset_key->user_login )
					),
					network_site_url( 'wp-login.php' )
				);

				wp_safe_redirect( $redirect_to );
				die;
			}
		}

		// Override login_redirect to ensure we are not taken to a custom page.
		function override_login_redirects( $redirect_to, $requested_redirect_to, $user ) {
			if ( ! empty( $redirect_to ) && is_a( $user, '\WP_User' ) ) {

				$reset = new PPM_WP_Reset();
				$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'new-user' );

				if ( $verify_reset_key && ! $verify_reset_key->errors ) {
					$redirect_to = add_query_arg(
						array(
							'action' => 'rp',
							'key'    => $verify_reset_key->reset_key,
							'login'  => rawurlencode( $verify_reset_key->user_login ),
							'redirect_to' => $redirect_to,
						),
						network_site_url( 'wp-login.php' )
					);
					wp_redirect( $redirect_to );
					exit;
				}
			}

			return $redirect_to;
		}

		/**
		 * Change reset password form message.
		 *
		 * @param object $error WP_Error object.
		 * @param object $user User object.
		 */
		public function ppm_validate_password_reset( $error, $user ) {
			// Get user reset key.
			$reset = new PPM_WP_Reset();
			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'new-user' );

			// If check reset key exists OR not.
			if ( ( $verify_reset_key && ! $verify_reset_key->errors ) && ( isset( $_GET['action'] ) && 'rp' === $_GET['action'] ) ) {
				// Logout current user.
				wp_logout();
				// Login notice.
				add_filter( 'login_message', array( $this, 'ppm_retrieve_password_message' ) );
			}
		}

		/**
		 * Customize retrieve password message.
		 *
		 * @param string $message Retrive password message.
		 * @return string message
		 */
		public function ppm_retrieve_password_message( $message ) {
			return wp_sprintf( '<p class="message reset-pass">%s</p>', __( 'To ensure you use a strong password, you are required to change your password before you login for the first time.', 'ppm-wp' ) );
		}

		public function ppm_new_user_errors( $errors, $update, $user ) {

			if ( isset( $_POST['from'] ) && 'profile' === $_POST['from'] ) {
				return;
			}

			$ppm           = ppm_wp();
			$options = $ppm->options->users_options;

			$user_settings = $ppm->options->users_options;
			$role_setting  = $ppm->options->setting_options;

			$options_master_switch    = PPMWP\Helpers\OptionsHelper::string_to_bool( $options->master_switch );
			$settings_master_switch   = PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->master_switch );
			$inherit_policies_setting = PPMWP\Helpers\OptionsHelper::string_to_bool( $user_settings->inherit_policies );

			$is_needed = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );

			if ( $is_needed ) {

				$pwd_check          = new PPM_WP_Password_Check();
				$does_violate_rules = $pwd_check->does_violate_rules( $_POST['pass1'] );

				if ( $does_violate_rules ) {
					$errors->add( 'ppm_password_error', __( 'Password does not meet policy requirments.' ) );
				}

			}

			return $errors;
		}

	}
}
