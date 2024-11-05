<?php
/**
 * Melapress Login SecurityEmail Settings
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

if ( ! class_exists( '\MLS\EmailAndMessageStrings' ) ) {

	/**
	 * Manipulate Users' Password History
	 *
	 * @since 2.0.0
	 */
	class EmailAndMessageStrings {

		/**
		 * Get array of available defaults.
		 *
		 * @return array - Our strings.
		 *
		 * @since 2.0.0
		 */
		public static function get_default_strings() {
			$default_strings = array(
				'user_reset_email_subject'                 => __( '{site_name} - your password has been reset', 'melapress-login-security' ),
				'user_delayed_reset_email_subject'         => __( '{site_name} - you need to change your password', 'melapress-login-security' ),
				'user_password_expired_email_subject'      => __( '{site_name} - your password has expired', 'melapress-login-security' ),
				'user_unlocked_email_subject'              => __( '{site_name} - your user has been unlocked', 'melapress-login-security' ),
				'user_unlocked_reset_needed_email_subject' => __( '{site_name} - your user has been unlocked', 'melapress-login-security' ),
				'user_imported_email_subject'              => __( 'Welcome to {site_name}', 'melapress-login-security' ),
				'user_imported_forced_reset_email_subject' => __( 'Welcome to {site_name}', 'melapress-login-security' ),
				'user_unlocked_email_title'                => __( '[{blogname}] Account Unlocked', 'melapress-login-security' ),
				'user_unlocked_email_reset_message'        => __( 'Please visit the following URL to reset your password:', 'melapress-login-security' ),
				'user_unlocked_email_continue_message'     => __( 'You may continue to login as normal', 'melapress-login-security' ),
				'user_unblocked_email_title'               => __( '[{blogname}] Account logins unblocked', 'melapress-login-security' ),
				'user_unblocked_email_reset_message'       => __( 'Please visit the following URL to reset your password:', 'melapress-login-security' ),
				'user_unblocked_email_continue_message'    => __( 'You may continue to login as normal', 'melapress-login-security' ),
				'user_reset_next_login_title'              => __( '[{blogname}] Password Reset', 'melapress-login-security' ),
				'user_delayed_reset_title'                 => __( '[{blogname}] Password Expired', 'melapress-login-security' ),
				'user_password_expired_title'              => __( '[{blogname}] Password Expired', 'melapress-login-security' ),
				'user_imported_email_title'                => __( 'Welcome', 'melapress-login-security' ),
				'user_imported_forced_reset_email_title'   => __( 'Welcome', 'melapress-login-security' ),
			);
			return $default_strings;
		}

		/**
		 * Get default string for desired setting.
		 *
		 * @param string $wanted - Desired string.
		 *
		 * @return string|bool - Located string, or false.
		 *
		 * @since 2.0.0
		 */
		public static function get_default_string( $wanted ) {
			$default_strings        = self::get_default_strings();
			$default_email_contents = self::default_message_contents( $wanted );

			if ( isset( $default_strings[ $wanted ] ) ) {
				return $default_strings[ $wanted ];
			} elseif ( ! empty( $default_email_contents ) ) {
				return $default_email_contents;
			}
			return false;
		}

		/**
		 * Neat holder for default email body texts.
		 *
		 * @param string] $template - Desired template.
		 *
		 * @return string - Message text.
		 *
		 * @since 2.0.0
		 */
		public static function default_message_contents( $template ) {

			$message = '';

			if ( 'user_unlocked' === $template || 'user_unlocked_email_body' === $template ) {
				$message  = __( 'Hello,', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'The user with the email {user_email} on the website {site_name} has been unlocked. Below are the details:', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Website: ', 'melapress-login-security' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'melapress-login-security' ) . '{user_login_name}' . "\n";
				$message .= __( 'Username: ', 'melapress-login-security' ) . '{user_login_name}' . "\n";
				$message .= __( 'You can proceed to log in as usual. ', 'melapress-login-security' ) . '{admin_email}' . "\n\n";
				$message .= __( 'If you have any questions or require assistance please contact the website administrator on {admin_email}.', 'melapress-login-security' ) . "\n";

			} elseif ( 'user_unblocked' === $template ) {
				$message  = __( 'Hello', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Your user account has been unblocked from further login attempts by the website administrator. Below are the details:', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Website: ', 'melapress-login-security' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'melapress-login-security' ) . '{user_login_name}' . "\n";
				$message .= "\n" . '{reset_or_continue}' . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on ', 'melapress-login-security' ) . '{admin_email}' . "\n\n";
				$message .= __( 'Thank you. ', 'melapress-login-security' ) . "\n";

			} elseif ( 'reset_next_login' === $template || 'user_delayed_reset_email_body' === $template ) {
				$message  = __( 'Hello', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'The administrator of the website {site_name} requires you to change your password on the next login. This means that the next time you try to log in to {site_name} you will be asked to change your password. Below are the details:', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Website name: {site_name}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Website URL: {site_url}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Username: {user_login_name}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance please contact the website administrator on {admin_email}.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'global_delayed_reset' === $template ) {
				$message  = __( 'Hello,', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'The administrator of the website {site_name} requires you to change your password on the next login. This means that the next time you try to log in to {site_name} you will be asked to change your password. Below are the details:', 'melapress-login-security' ) . "\n\n";
				$message  = __( 'Website name: {site_name}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Website URL: {site_url}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Username: {user_login_name}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance please contact the website administrator on {admin_email}.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'password_expired' === $template || 'user_password_expired_email_body' === $template ) {
				$message  = __( 'Hello,', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Your password for the user {user_login_name} on the website {site_name} has expired. The website’s URL is {site_url}..', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Please visit the following URL to reset your password: {reset_url}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance please contact the website administrator on {admin_email}.', 'melapress-login-security' ) . "\n";

			} elseif ( 'user_reset_email_body' === $template ) {
				$message  = __( 'Hello,', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Your user password on the website {site_name} has been reset by the website administrator. Therefore the next time you will try to log in to the website you will need to specify a new password. Below are the details:', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Website name: {site_name}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Website URL: {site_url}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Username: {user_login_name}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance please contact the website administrator on {admin_email}.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'user_imported' === $template || 'user_imported_email_body' === $template ) {
				$message  = __( 'Hello', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'The user {user_login_name} with the email address {user_email} has been created on the website {home_url}.', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'You may log in with the following credentials:', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Username: {user_login_name}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Password: {user_password}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'It is highly recommended to change your password once you log in.', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance please contact the website administrator on {admin_email}.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'user_imported_forced_reset' === $template || 'user_imported_forced_reset_email_body' === $template ) {
				$message  = __( 'Hello,', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'The user {user_login_name} with the email address {user_email} has been created on the website {home_url}.', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Please visit the following URL to reset your password so you can log in to the website: {reset_url}.', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance please contact the website administrator on {admin_email}.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'user_unlocked_reset_needed_email_body' === $template ) {
				$message  = __( 'Hello,', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'The user with the email {user_email} on the website {site_name} has been unlocked. Below are the details:', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Website: {home_url}', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'Username: {user_login_name}.', 'melapress-login-security' ) . "\n\n";
				$message .= __( 'To log back into the website, you are required to reset your password. You can do so from the following URL: {reset_url}', 'melapress-login-security' ) . "\n";
				$message .= __( 'If you have any questions or require assistance please contact the website administrator on {admin_email}.', 'melapress-login-security' ) . "\n";
			} elseif ( 'inactive_user_account_locked_message' === $template ) {
				$message = __( 'Your WordPress user has been locked. Please contact the website administrator ( {admin_email} ) to unlock your user account.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'inactive_user_account_locked_reset_disabled_message' === $template ) {
				$message = __( 'Password resets via emails have been disabled. Please contact the website administrator.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'password_reset_request_disabled_message' === $template ) {
				$message = __( 'Password resets via emails have been disabled. Please contact the website administrator.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'password_expired_message' === $template ) {
				$message = __( 'The password you entered for the username {user_login_name} has expired.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'restrict_logins_prompt_failure_message' === $template ) {
				$message = __( 'Please check the credentials and try again.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'timed_logins_login_blocked_message' === $template ) {
				$message = __( 'Login is not allowed at this time.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'restrict_login_ip_login_blocked_message' === $template ) {
				$message = __( 'Logins from this IP address are not allowed. Please contact the website administrator ( {admin_email} ) for further information.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'failed_logins_login_blocked_message' === $template ) {
				$message = __( 'Your user account has surpassed the maximum allowed number of failed login attempts and it has been locked.', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'failed_logins_login_blocked_message' === $template ) {
				$message = __( 'Incorrect answer provided', 'melapress-login-security' ) . "\n\n";
			} elseif ( 'security_prompt_response_failure_message' === $template ) {
				$message = __( 'Incorrect answer provided', 'melapress-login-security' ) . "\n\n";
			}

			$message = apply_filters( 'mls_default_email_content_strings', $message, $template );

			return $message;
		}

		/**
		 * Replace our tags with the relevant data when sending the email.
		 *
		 * @param string $input - Original text.
		 * @param string $user_id - Applicable user ID.
		 * @param array  $args - Extra args.
		 *
		 * @return string $final_output - Final message text.
		 *
		 * @since 2.0.0
		 */
		public static function replace_email_strings( $input = '', $user_id = 0, $args = array() ) {

			$mls        = melapress_login_security();
			$user       = get_userdata( $user_id );
			$from_email = $mls->options->mls_setting->from_email ? $mls->options->mls_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );

			if ( ! is_a( $user, '\WP_User' ) ) {
				// These are the strings we are going to search for, as well as there respective replacements.
				$replacements = array(
					'{site_url}'            => esc_url( get_bloginfo( 'url' ) ),
					'{home_url}'            => esc_url( get_bloginfo( 'url' ) ),
					'{site_name}'           => ( is_multisite() ) ? get_network()->site_name : wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
					'{admin_email}'         => $from_email,
					'{blogname}'            => ( is_multisite() ) ? get_network()->site_name : wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
					'{reset_or_continue}'   => ( ! empty( $args ) && isset( $args['reset_or_continue'] ) ) ? sanitize_text_field( $args['reset_or_continue'] ) : '',
					'{reset_url}'           => ( ! empty( $args ) && isset( $args['reset_url'] ) ) ? sanitize_text_field( $args['reset_url'] ) : '',
					'{password}'            => ( ! empty( $args ) && isset( $args['password'] ) ) ? sanitize_text_field( $args['password'] ) : '',
					'{device_ip}'           => ( ! empty( $args ) && isset( $args['device_ip'] ) ) ? sanitize_text_field( $args['device_ip'] ) : '',
					'{clear_sessions_link}' => ( ! empty( $args ) && isset( $args['clear_sessions_link'] ) ) ? sanitize_text_field( $args['clear_sessions_link'] ) : '',
				);

				$final_output = str_replace( array_keys( $replacements ), array_values( $replacements ), $input );
				return $final_output;
			}

			// These are the strings we are going to search for, as well as there respective replacements.
			$replacements = array(
				'{home_url}'            => esc_url( get_bloginfo( 'url' ) ),
				'{site_url}'            => esc_url( get_bloginfo( 'url' ) ),
				'{site_name}'           => ( is_multisite() ) ? get_network()->site_name : wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				'{user_login_name}'     => sanitize_text_field( $user->user_login ),
				'{user_first_name}'     => sanitize_text_field( $user->firstname ),
				'{user_last_name}'      => sanitize_text_field( $user->lastname ),
				'{user_display_name}'   => sanitize_text_field( $user->display_name ),
				'{user_email}'          => sanitize_text_field( $user->user_email ),
				'{admin_email}'         => $from_email,
				'{blogname}'            => ( is_multisite() ) ? get_network()->site_name : wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				'{reset_or_continue}'   => ( ! empty( $args ) && isset( $args['reset_or_continue'] ) ) ? sanitize_text_field( $args['reset_or_continue'] ) : '',
				'{reset_url}'           => ( ! empty( $args ) && isset( $args['reset_url'] ) ) ? sanitize_text_field( $args['reset_url'] ) : '',
				'{password}'            => ( ! empty( $args ) && isset( $args['password'] ) ) ? sanitize_text_field( $args['password'] ) : '',
				'{user_password}'       => ( ! empty( $args ) && isset( $args['password'] ) ) ? sanitize_text_field( $args['password'] ) : '',
				'{device_ip}'           => ( ! empty( $args ) && isset( $args['device_ip'] ) ) ? sanitize_text_field( $args['device_ip'] ) : '',
				'{clear_sessions_link}' => ( ! empty( $args ) && isset( $args['clear_sessions_link'] ) ) ? sanitize_text_field( $args['clear_sessions_link'] ) : '',
			);

			$final_output = str_replace( array_keys( $replacements ), array_values( $replacements ), $input );
			return $final_output;
		}

		/**
		 * Get email template value
		 *
		 * @param string $setting_name - Setting name.
		 *
		 * @return string - Value.
		 *
		 * @since 2.0.0
		 */
		public static function get_email_template_setting( $setting_name ) {
			$mls           = melapress_login_security();
			$settings      = (array) $mls->options->mls_setting;
			$setting_value = isset( $settings[ $setting_name ] ) && ! empty( $settings[ $setting_name ] ) ? $settings[ $setting_name ] : self::get_default_string( $setting_name );
			return $setting_value;
		}
	}
}
