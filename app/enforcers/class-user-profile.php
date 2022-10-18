<?php
/**
 * PPM New User Register
 *
 * @package wordpress
 * @subpackage wpassword
 * @author WP White Security
 */

// If check class exists OR not.
if ( ! class_exists( 'PPM_User_Profile' ) ) {
	/**
	 * Declare PPM_User_Profile Class
	 */
	class PPM_User_Profile {

		/**
		 * Init hooks.
		 */
		public function init() {
			global $pagenow;
			if ( 'profile.php' !== $pagenow || 'user-edit.php' !== $pagenow ) {
				add_action( 'show_user_profile', array( $this, 'reset_user_password' ) );
				add_action( 'edit_user_profile', array( $this, 'reset_user_password' ) );
				add_action( 'personal_options_update', array( $this, 'save_profile_fields' ) );
				add_action( 'edit_user_profile_update', array( $this, 'save_profile_fields' ) );
			}
			add_action( 'wp_login', array( $this, 'ppm_reset_pw_on_login' ), 10, 2 );
		}

		public function reset_user_password( $user ) {
			// Get current user, we going to need this regardless.
			$current_user = wp_get_current_user();

			// Bail if we still dont have an object.
			if ( ! is_a( $user, '\WP_User' ) || ! is_a( $current_user, '\WP_User' ) ) {
				return;
			}

			$reset = get_user_meta( $user->ID, PPM_WP_META_USER_RESET_PW_ON_LOGIN, true );

			// If the profile was recently updated, one of those updates could be a new password,
			// so if the user is set to reset on next login, lets generate a fresh reset key
			// to avoid "invalid reset link" when logging in next time.
			if ( isset( $_REQUEST['updated'] ) && ! empty( $reset ) ) {
			    $this->generate_new_reset_key( $user->ID );
			}

			if ( current_user_can( 'manage_options' ) ) { ?>
				<table class="form-table" role="presentation">
					<tbody><tr id="password" class="user-pass1-wrap">
						<th><label for="reset_password"><?php _e( 'Reset password on next login', 'ppm-wp' ); ?></label></th>
						<td>
							<label for="reset_password_on_next_login">
								<input name="reset_password_on_next_login" type="checkbox" id="reset_password_on_next_login" <?php checked( ! empty( $reset ) ); ?>>
								<?php _e( 'Reset password on next login', 'ppm-wp' ); ?>
							</label>
							<br>
						</td>
						</tr>
					</tbody>
				</table>
				<?php
			}
		}

		public function save_profile_fields( $user_id ) {
			if ( ! current_user_can( 'manage_options' ) ) {
			    return;
			}

			if ( isset( $_POST['reset_password_on_next_login'] ) ) {
				$reset      = get_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN, true );
				if ( empty( $reset ) ) {
					$this->generate_new_reset_key( $user_id );
				}
			} else {
				// Remove any reset on login keys if admin has disabled it for this user.
				delete_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN );
			}
		}

		/**
         * Generates a new password reset key and also saves it to our own meta field.
         *
		 * @param int $user_id
         * @since 2.5.0
		 */
		private function generate_new_reset_key( $user_id ) {
			$userdata = get_user_by( 'id', $user_id );
			$key      = get_password_reset_key( $userdata );
			if ( ! is_wp_error( $key ) ) {
				update_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN, $key );
			}
		}

		public function ppm_reset_pw_on_login( $user_login, $user ) {
			// Get user reset key.
			$reset = new PPM_WP_Reset();
			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, 'reset-on-login' );

			// If check reset key exists OR not.
			if ( $verify_reset_key && ! $verify_reset_key->errors ) {
				$redirect_to = add_query_arg(
					array(
						'action' => 'rp',
						'key'    => $verify_reset_key->reset_key,
						'login'  => rawurlencode( $verify_reset_key->user_login ),
					),
					network_site_url( 'wp-login.php' )
				);
				wp_safe_redirect( $redirect_to );
				die;
			}
		}

		/**
		 * Sends reset email to user. Message depends on $by value
		 *
		 * @param int    $user_id        User ID
		 * @param string $by             Can be 'system' or 'admin'. Depending on its value different messages are sent.
		 * @param bool   $return_on_fail Flag to determine if we return or die on mail failure.
		 */
		public function send_reset_next_login_email( $user_id, $by, $return_on_fail = false ) {

			$user_data = get_userdata( $user_id );

			// Redefining user_login ensures we return the right case in the email.
			$user_login	 = $user_data->user_login;
			$user_email	 = $user_data->user_email;
			$key		 = get_user_meta( $user_id, PPM_WP_META_USER_RESET_PW_ON_LOGIN, true );
			$login_page  = OptionsHelper::get_password_reset_page();
			if( $by == 'admin' ) {
				$by_str = __('Your user password was reset by the website administrator. Below are the details:', 'ppm-wp');
				/* translators: %1$s: is Reset by text, %2$s is user login name, %3$s is Site URL, %4$s: Reset PW URL, %5s$ is admin email address */
				$message = sprintf( __('Hello,

%1$s

Website: %2$s

username: %3$s

You will be asked to reset your password when you next login. Otherwise, you can visit the following URL to reset your password:

%4$s

If you have any questions or require assistance contact your website administrator on %5$s.

Thank you.', 'ppm-wp'),
				$by_str,
				network_home_url( '/' ),
				$user_data->user_login,
				esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ),
				is_multisite() ? get_site_option( 'admin_email' ) : get_option('admin_email') );
			}

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
			$title = sprintf( __( '[%s] Password Reset', 'ppm-wp' ), $blogname );

			$ppm = ppm_wp();

			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'wordpress@' . str_ireplace( 'www.', '', parse_url( network_site_url(), PHP_URL_HOST ) );
			$from_email = sanitize_email( $from_email );
			$headers[] = 'From: ' . $from_email;

			if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message, $headers ) ) {
				$fail_message = __( 'The email could not be sent.', 'ppm-wp' ) . "<br />\n" . __( 'Possible reason: your host may have disabled the mail() function.', 'ppm-wp' );
				if ( $return_on_fail ) {
					return $fail_message;
				} else {
					wp_die( wp_kses_post( $fail_message ) );
				}
			}
		}
	}
}
