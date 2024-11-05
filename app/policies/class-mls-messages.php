<?php
/**
 * Password hint messages.
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

if ( ! class_exists( '\MLS\MLS_Messages' ) ) {

	/**
	 * Provides all string messages for forms.
	 *
	 * @since 2.0.0
	 */
	class MLS_Messages {

		/**
		 * Error strings used in PHP
		 *
		 * @var array Error strings used in PHP
		 *
		 * @since 2.0.0
		 */
		public $error_strings = array();

		/**
		 * Error strings used by the password strength meter
		 *
		 * @var array Error strings used by the password strength meter
		 *
		 * @since 2.0.0
		 */
		public $js_error_strings = array();

		/**
		 * Strings indicating password strength. Replaces WP default.
		 *
		 * @var array Strings indicating password strength. Replaces WP default.
		 *
		 * @since 2.0.0
		 */
		public $pws_l10n = array();

		/**
		 * Strings for the Password Reset UI. Replaces WP default.
		 *
		 * @var array Strings for the Password Reset UI. Replaces WP default.
		 *
		 * @since 2.0.0
		 */
		public $user_profile_l10n = array();

		/**
		 * Our special char code.
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		private $special_char_strings = '<code>&#33; &#64; &#35; &#36; &#37; &#94; &#38; &#42; &#40; &#41; &#95; &#63; &#163; &#34; &#45; &#43; &#61; &#126; &#59; &#58; &#8364; &#60; &#62;</code>';

		/**
		 * Instantiate localised strings
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function init() {

			$mls = melapress_login_security();

			$options = $mls->options->users_options;

			/*
			 * Remove any excluded special characters from the list displaed.
			 *
			 * @since 2.1.0
			 */
			$excluded_string = isset( $options->excluded_special_chars ) ? $options->excluded_special_chars : '';
			if ( ! empty( $excluded_string ) && \MLS\Helpers\OptionsHelper::string_to_bool( $options->rules['exclude_special_chars'] ) ) {
				// decode the characters.
				$excluded_string = html_entity_decode( $excluded_string );

				// reformat the string to array of decoded special chars.
				$decoded_special_chars = explode( ' ', html_entity_decode( preg_replace( '/\<(\/)?code\>/', '', $this->special_char_strings ) ) );

				// split the excluded special chars to an array.
				$excluded_entities = \MLS\MB_String_Helper::mb_split_string( $excluded_string );

				// get the difference and reform the string. Limit max items to 4 for tidyness.
				$this->special_char_strings = '<code>' . implode( ' ', array_slice( array_diff( $decoded_special_chars, $excluded_entities ), 0, 20 ) ) . '</code>';
			}

			$excluded_char_strings = '<code style="letter-spacing: 5px">' . esc_attr( $excluded_string ) . '</code>';

			if ( ! isset( $options->password_history ) ) {
				$mls_options = new \MLS\MLS_Options();
				$mls_options->init();
				$options = $mls_options->user_role_policy();
			}

			$this->error_strings = array(
				'strength'              => __( 'Should not use known words.', 'melapress-login-security' ),
				'username'              => __( 'Cannot contain the username.', 'melapress-login-security' ),
				/* translators: %d: Number of passwords the current password cannot be the same as */
				'history'               => sprintf( __( 'Password cannot be the same as the last %d passwords.', 'melapress-login-security' ), $options->password_history ),
				/* translators: %d: Configured minumum password length */
				'length'                => sprintf( __( 'Must be at least %d characters long.', 'melapress-login-security' ), $options->min_length ),
				'mix_case'              => \MLS\Helpers\OptionsHelper::string_to_bool( $options->ui_rules['mix_case'] ) ? __( 'Must contain both UPPERCASE & lowercase characters.', 'melapress-login-security' ) : '',
				'numeric'               => \MLS\Helpers\OptionsHelper::string_to_bool( $options->ui_rules['numeric'] ) ? __( 'Must contain numbers.', 'melapress-login-security' ) : '',
				/* translators: %d: Characters which cannot be used in a password */
				'special_chars'         => \MLS\Helpers\OptionsHelper::string_to_bool( $options->ui_rules['special_chars'] ) ? sprintf( __( 'Must contain special characters such as %s.', 'melapress-login-security' ), $this->special_char_strings ) : '',
				'exclude_special_chars' => sprintf(
					/* translators: 1 = list of special characters */
					__( 'Password cannot contain any of these special characters: %s', 'melapress-login-security' ),
					$excluded_char_strings
				),
			);

			$this->js_error_strings = array(
				'strength'              => array(
					0 => __( 'is very easy to guess. Please avoid using known words in the password.', 'melapress-login-security' ),
					1 => __( 'is very easy to guess. Please avoid using known words in the password.', 'melapress-login-security' ),
					2 => __( 'is relatively easy to guess. Please avoid using known words in the password.', 'melapress-login-security' ),
					3 => __( 'is not strong enough. Please avoid using known words in the password.', 'melapress-login-security' ),
				),
				'username'              => __( 'Cannot contain the username.', 'melapress-login-security' ),
				'history'               => sprintf(
					/* translators: %d = number of passwords */
					__( 'Password cannot be the same as the last %d passwords.', 'melapress-login-security' ),
					$options->password_history
				),
				'length'                => sprintf(
					/* translators: %d = min pw length */
					__( 'Must be at least %d characters long.', 'melapress-login-security' ),
					$options->min_length
				),
				'mix_case'              => __( 'Must contain both UPPERCASE & lowercase characters.', 'melapress-login-security' ),
				'numeric'               => __( 'Must contain numbers.', 'melapress-login-security' ),
				'special_chars'         => sprintf(
					/* translators: %s = special chars */
					__( 'Must contain special characters such as %s.', 'melapress-login-security' ),
					'<code>&#33; &#64; &#35; &#36; &#37; &#94; &#38; &#42; &#40; &#41; &#95; &#63; &#163; &#34; &#45; &#43; &#61; &#126; &#59; &#58; &#8364; &#60; &#62;</code>'
				),
				'exclude_special_chars' => sprintf(
					/* translators: 1 = list of special characters */
					__( 'Password cannot contain any of these special characters: %s', 'melapress-login-security' ),
					$excluded_char_strings
				),
			);

			$this->pws_l10n = array(
				'unknown'  => __( 'Password strength unknown', 'melapress-login-security' ),
				'short'    => __( 'Too short', 'melapress-login-security' ),
				'bad'      => __( 'Insecure:', 'melapress-login-security' ),
				'good'     => __( 'Insecure:', 'melapress-login-security' ),
				'strong'   => __( 'Strong &amp; Secure', 'melapress-login-security' ),
				'mismatch' => __( 'Mismatch', 'melapress-login-security' ),
				'invalid'  => __( 'Invalid', 'melapress-login-security' ),
			);

			$user_id = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$this->user_profile_l10n = array(
				'warn'           => __( 'Your new password has not been saved.', 'melapress-login-security' ),
				'warnWeak'       => __( 'Confirm use of weak password.', 'melapress-login-security' ),
				'show'           => __( 'Show', 'melapress-login-security' ),
				'hide'           => __( 'Hide', 'melapress-login-security' ),
				'cancel'         => __( 'Cancel' ),
				'ariaShow'       => esc_attr__( 'Show password', 'melapress-login-security' ),
				'ariaHide'       => esc_attr__( 'Hide password', 'melapress-login-security' ),
				'hintMsg'        => esc_html__( 'Hints for a strong password:', 'melapress-login-security' ),
				'hintMsgUserNew' => esc_html__( 'Password tip:', 'melapress-login-security' ),
				'hintBefore'     => esc_html__( 'Use a strong password that consists at least of 8 characters, lower and upper case letters, numbers and symbols. Refer to the', 'melapress-login-security' ),
				'hintLink'       => esc_html__( 'strong password guidelines', 'melapress-login-security' ),
				'hintAfter'      => esc_html__( 'for more information.', 'melapress-login-security' ),
				'polyfill'       => array(
					'calledOnNull'        => esc_html__( 'Array.prototype.find called on null or undefined', 'melapress-login-security' ),
					'callbackNotFunction' => esc_html__( 'callback must be a function', 'melapress-login-security' ),
				),
				'user_id'        => $user_id,
				'nonce'          => wp_create_nonce( 'reset-password-for-' . $user_id ),
			);
		}
	}
}
