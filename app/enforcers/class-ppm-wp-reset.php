<?php

/**
 * @package wordpress
 * @subpackage wpassword
 *
 */
if ( ! class_exists( 'PPM_WP_Reset' ) ) {

	/**
	 * Resets passwords
	 */
	class PPM_WP_Reset {

		/**
		 * Hooks delayed password reset to login if option is checked
		 */
		public function hook() {

			$ppm = ppm_wp();
			//Hook the function only if reset delay is checked in the options
			if ( $ppm->options->ppm_setting->terminate_session_password ) {
				add_action( 'wp_authenticate', array( $this, 'check_on_login' ), 0, 2 );
			}
			// Customize password reset key expiry time.
			add_filter( 'password_reset_expiration', array( $this, 'customize_reset_key_expiry_time' ) );

			add_filter( 'allow_password_reset', array( $this, 'ppm_is_user_allowed_to_reset' ), 10, 2 ); 
		}

		/**
		 * Resets a user's password.
		 *
		 * @global object $wpdb
		 * @param integer $user_id The user ID
		 * @param string $by Reset by system, admin or user
		 * @param bool $reset_all
		 */
		public function reset( $user_id, $current_password, $by = 'system', $reset_all = false ) {
			$ppm = ppm_wp();

			// we can't reset without a user ID
			if ( $user_id === false ) {
				return;
			}

			// create a password event
			$password_event = array(
				'password' => $current_password,
				'timestamp' => current_time( 'timestamp' ),
				'by' => $by,
			);

			// push current password to password history of the user
			PPM_WP_History::_push( $user_id, $password_event );

			if( in_array($by, ['admin', 'system']) ){
				// update user's expired status 1
				if ( ! PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->ppm_setting->terminate_session_password ) ) {
					$this->delayed_reset( $user_id );
				} else {
					update_user_meta( $user_id, PPM_WP_META_PASSWORD_EXPIRED, 1 );
					$this->send_reset_email( $user_id, $by );
					// Destroy user session.
					$ppm->ppm_user_session_destroy( $user_id );
				}
			}
		}

		/**
		 * Sends reset email to user. Message depends on $by value
		 *
		 * @param int    $user_id        User ID
		 * @param string $by             Can be 'system' or 'admin'. Depending on its value different messages are sent.
		 * @param bool   $return_on_fail Flag to determine if we return or die on mail failure.
		 */
		public function send_reset_email( $user_id, $by, $return_on_fail = false, $is_delayed = false ) {

			// Check if message has already been sent.
			$email_sent = get_user_meta( $user_id, PPM_WP_META_EXPIRED_EMAIL_SENT, true );
			if ( $email_sent ) {
				return;
			}
			
			$user_data = get_userdata( $user_id );

			// Redefining user_login ensures we return the right case in the email.
			$user_login	 = $user_data->user_login;
			$user_email	 = $user_data->user_email;
			$key		 = get_password_reset_key( $user_data );
			$login_page  = PPMWP\Helpers\OptionsHelper::get_password_reset_page();
			if ( ! is_wp_error( $key ) ) {
				if( $by == 'admin' ) {
					$by_str = __('Your user password was reset by the website administrator. Below are the details:', 'ppm-wp');
					if ( $is_delayed ) {
						/* translators: %1$s: is Reset by text, %2$s is Site URL, %3$s is User login name, %4$s is admin email address */
						$message = sprintf(	__(
							'Hello, %1$s Website: %2$s username: %3$s Please be aware your password has been reset by the sites administrator and you will be required to provide a new one upon next login. If you have any questions or require assistance contact your website administrator on %4$s. Thank you.', 'ppm-wp'),
						$by_str,
						network_home_url( '/' ),
						$user_data->user_login,
						is_multisite() ? get_site_option( 'admin_email' ) : get_option('admin_email') );
					} else {
						/* translators: %1$s: is Reset by text, %2$s is Site URL, %3$s is User login name, %4$s: Reset PW URL, %5$s is admin email address */
						$message = sprintf(	__(
							'Hello, %1$s Website: %2$s username: %3$s Please visit the following URL to reset your password: %4$s If you have any questions or require assistance contact your website administrator on %5$s. Thank you.', 'ppm-wp'),
						$by_str,
						network_home_url( '/' ),
						$user_data->user_login,
						esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ),
						is_multisite() ? get_site_option( 'admin_email' ) : get_option('admin_email') );
					}

				}
				else {
					$by_str	= __( 'Your password', 'ppm-wp' );
					/* translators: %1$s: is Reset by text, %2$s is user login name, %3$s is Site URL, %4$s: Reset PW URL, %5s$ is admin email address */
					$message = sprintf( __('Hello, %1$s for the user %2$s on the website %3$s has expired. Please visit the following URL to reset your password: %4$s If you have any questions or require assistance contact your website administrator on %5$s. Thank you.', 'ppm-wp'),
					$by_str,
					$user_data->user_login,
					network_home_url( '/' ),
					esc_url_raw( network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ),
					is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' ) );
				}
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

			if( $by == 'admin' ) {
				/* translators: Password reset email subject. 1: Site name */
				$title = sprintf( __( '[%s] Password Reset', 'ppm-wp' ), $blogname );
			} else {
				/* translators: Password reset email subject. 1: Site name */
				$title = sprintf( __( '[%s] Password Expired', 'ppm-wp' ), $blogname );
			}

			// Update usermeta so we know we have sent a message.
			update_user_meta( $user_id, PPM_WP_META_EXPIRED_EMAIL_SENT, true );

			$ppm = ppm_wp();

			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'wordpress@' . str_ireplace( 'www.', '', parse_url( network_site_url(), PHP_URL_HOST ) );
			$from_email = sanitize_email( $from_email );
			$headers[] = 'From: ' . $from_email;

			if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message, $headers ) ) {
				$fail_message = __( 'The email could not be sent.', 'ppm-wp' ) . "<br />\n" . __( 'Possible reason: your host may have disabled the mail() function.', 'ppm-wp' );
				// Remove flag so we can try again.
				delete_user_meta( $user_id, PPM_WP_META_EXPIRED_EMAIL_SENT );
				if ( $return_on_fail ) {
					return $fail_message;
				} else {
					wp_die( wp_kses_post( $fail_message ) );
				}
			}
		}

		public function send_admin_email() {
			$user_data = get_userdata( get_current_user_id() );

			$message = __( 'All passwords have been reset for:', 'ppm-wp' ) . "\r\n\r\n";
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
			$title = sprintf( __( '[%s] Global Password Reset Complete', 'ppm-wp' ), $blogname );

			$ppm = ppm_wp();

			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'wordpress@'.str_ireplace('www.', '', parse_url( network_site_url(), PHP_URL_HOST ) );
			$from_email = sanitize_email( $from_email );
			$headers[] = 'From: ' . $from_email;

			if ( $message && ! wp_mail( $user_data->user_email, wp_specialchars_decode( $title ), $message, $headers ) )
				wp_die( __( 'The email could not be sent.', 'ppm-wp' ) . "<br />\n" . __( 'Possible reason: your host may have disabled the mail() function.', 'ppm-wp' ) );
			return true;
		}

		/**
		 * Reset all the users passwords
		 */
		public function reset_all() {
			$ppm = ppm_wp();

			$exempted_users = array();

			if ( isset( $_POST['current_user'] ) ) {
				array_push( $exempted_users, get_current_user_id() );
			}

			// exclude exempted roles and users
			$user_args = array(
				'exclude'		 => $exempted_users,
				'fields' 		 => array( 'ID' ),
			);

			// If check multisite installed OR not.
			if ( is_multisite() ) {
				$user_args['blog_id'] = 0;
			}

			// Send users for bg processing later.
			$total_users = count_users();
			$batch_size  = 50;
			$slices      = ceil( $total_users['total_users'] / $batch_size );
			$users       = array();
			$background_process = new PPM_Reset_User_PW_Process();

			for ( $count = 0; $count < $slices; $count++ ) {
				$user_args['number'] = $batch_size;
				$user_args['offset'] = $count * $batch_size;
				$users = get_users( $user_args );

				if ( ! empty( $users ) ) {
					foreach ( $users as $user ) {
						$background_process->push_to_queue( $user->ID );
					}
				}
			}

			// Fire off bg processes.
			$background_process->save()->dispatch();


			return $this->send_admin_email();
		}

		public function delayed_reset( $user_id ) {

			$ppm = ppm_wp();

			if ( ppm_is_user_exempted( $user_id ) ) {
				return;
			}

			// Destroy user session.
			// THIS METHOD CAN ONLY BE REACHED IF THE OPTION TO TERMINATE
			// SESSIONS IS _UNCHECKED_. SESSIONS SHOULDN'T BE DESTROYED HERE.
			//$ppm->ppm_user_session_destroy( $user_id );

			// Update user meta
			update_user_meta( $user_id, PPM_WP_META_DELAYED_RESET_KEY, true );
			update_user_meta( $user_id, PPM_WP_META_PASSWORD_EXPIRED, 1 );

			$this->send_reset_email( $user_id, 'admin', false, true );
		}

		/**
		 * Runs on every login request
		 *
		 * @param type $user_login
		 * @param type $user_password
		 */
		public function check_on_login( $user_login, $user_password ) {
			if ( empty( $user_login ) || empty( $user_password ) ) {
				return;
			}

			$user = get_user_by( 'login', $user_login );
			if ( $user ) {
				$this->maybe_reset( $user->ID );
			}
		}

		/**
		 * Tries to reset the password if needed
		 *
		 * @param type $user_id
		 */
		private function maybe_reset( $user_id ) {

			if ( !$this->should_password_reset( $user_id ) ) {
				return;
			}

			$user_data			 = get_userdata( $user_id );
			$current_password	 = $user_data->user_pass;

			$this->reset( $user_id, $current_password, 'admin' );
			delete_user_meta( $user_id, PPM_WP_META_DELAYED_RESET_KEY );
		}

		/**
		 * Returns whether the password should be reset or not
		 *
		 * @param type $user_id
		 * @return boolean
		 */
		private function should_password_reset( $user_id ) {
			return get_user_meta( $user_id, PPM_WP_META_DELAYED_RESET_KEY, true ) == 1;
		}

		/**
		 * Modify the defailt reset expiry time of 24 hours to a setting of the admins choosing.
		 *
		 * @param int $expiration Default expiry time.
		 */
		public function customize_reset_key_expiry_time( $expiration ) {
			$ppm             = ppm_wp();
			$number          = $ppm->options->ppm_setting->password_reset_key_expiry['value'];
			$unit            = ( 'days' === $ppm->options->ppm_setting->password_reset_key_expiry['unit'] ) ? DAY_IN_SECONDS : HOUR_IN_SECONDS;
			$new_expiry_time = $number * $unit;
			return $new_expiry_time;
		}

		/**
		 * Get user reset by user ID.
		 *
		 * @param  object $user User object.
		 *
		 * @return object
		 */
		public function ppm_get_user_reset_key( $user, $meta_key ) {
			$verify_reset_key = false;
			$user_id          = $user->ID;
			$user_login       = $user->user_login;

			$usermeta_key = ( 'new-user' === $meta_key ) ? PPM_WP_META_NEW_USER : PPM_WP_META_USER_RESET_PW_ON_LOGIN;

			// User get reset by user ID.
			$reset_key = get_user_meta( $user_id, $usermeta_key, true );

			// If check reset key exists OR not.
			if ( $reset_key ) {
				$verify_reset_key             = check_password_reset_key( $reset_key, $user_login );
				$verify_reset_key->reset_key  = $reset_key;
				$verify_reset_key->user_login = $user_login;
			}
			return $verify_reset_key;
		}

		public function ppm_is_user_allowed_to_reset( $allow, $user_id ) {
			$ppm = ppm_wp();
			$default_options = PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->inherit['master_switch'] ) ? $ppm->options->inherit : [];

			// Get user by ID.
			$get_userdata = get_user_by( 'ID', $user_id );
			$roles         = $get_userdata->roles;

			$roles = PPMWP\Helpers\OptionsHelper::prioritise_roles( $roles );
			$roles = reset( $roles );

			// If we reach this point with no default options, stop here.
			if ( empty( $default_options ) ) {
				return true;
			}

			// Allow if request is from an admin.
			if ( isset( $_REQUEST['action'] ) && 'resetpassword' == $_REQUEST['action'] || isset( $_REQUEST['action'] ) && 'ppmwp_unlock_inactive_user' == $_REQUEST['action'] ) {
				$user = wp_get_current_user();
				$allowed_roles = [ 'administrator' ];
				if ( array_intersect( $allowed_roles, $user->roles ) ) {
					return true;
				}
			}

			// Get option by role name.
			$options = get_site_option( PPMWP_PREFIX . '_' . $roles . '_options', $default_options );
			if ( isset( $options['disable_self_reset'] ) && PPMWP\Helpers\OptionsHelper::string_to_bool( $options['disable_self_reset'] ) ) {
				return new WP_Error( 'reset_disabled', esc_attr( $options['disable_self_reset_message'] ) );
			}
			return true;
		}
	}
}
