<?php
/**
 * Handle PW generation.
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

use MLS\Helpers\OptionsHelper;

if ( ! class_exists( '\MLS\Password_Gen' ) ) {

	/**
	 * Generates passwords in accordance with policies
	 */
	class Password_Gen {

		/**
		 * Initialise
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function hook() {
			// Only load further if needed.
			if ( ! OptionsHelper::get_plugin_is_enabled() ) {
				return;
			}

			// filter reset password form on wp-login.php.
			add_action( 'validate_password_reset', array( $this, 'generate_password_filter' ) );

			// filter new user creation form.
			add_action( 'user_new_form_tag', array( $this, 'generate_password_filter' ) );

			// filter profile/user edit form.
			add_action( 'personal_options', array( $this, 'generate_password_filter' ) );

			// filter password retreived by ajax.
			add_action( 'admin_init', array( $this, 'ajax_generate' ) );
			add_action( 'login_init', array( $this, 'ajax_generate' ) );
		}

		/**
		 * Generates strong passwords for ajax calls from WP.
		 */
		public function ajax_generate() {

			// add strong function.
			add_action( 'wp_ajax_generate-password', array( $this, '_generate' ), 0 );
			add_action( 'wp_ajax_nopriv_generate-password', array( $this, '_generate' ), 0 );
		}

		/**
		 * Filters the output of wp_generate_password.
		 *
		 * @see wp_generate_password()
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function generate_password_filter() {
			add_filter( 'random_password', array( $this, '_generate' ) );
		}

		/**
		 * Generates a strong password conforming to policies
		 *
		 * @param  bool $should_return - return needed.
		 *
		 * @return string Strong Password
		 *
		 * @since 2.0.0
		 */
		public function _generate( $should_return = false ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
			// Need to remove the filter as early as possible.
			if ( doing_filter( 'random_password' ) ) {
				// remove the filter from wp_generate_password for any other functions.
				remove_filter( 'random_password', array( $this, '_generate' ) );
			}

			// get core instance.
			$mls = melapress_login_security();

			// default passwords will be generated as per policies.
			// even if user is exempted from the policies.
			// an array of character groups as per available policies.
			$chargroups = array(
				'lower_case'    => 'abcdefghijklmnopqrstuvwxyz',
				'upper_case'    => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
				'numeric'       => '0123456789',
				'special_chars' => $mls->get_allowed_special_chars(),
			);

			// remove any rule that's not active from character groups.

			$rules = $mls->options->__get( 'rules' );

			foreach ( $chargroups as $chargroup => $chars ) {
				if ( false === $rules[ $chargroup ] ) {
					unset( $chargroups[ $chargroup ] );
				}
			}

			// count the available character groups.
			$chargroup_count = count( $chargroups );

			// set a length way more than the minimum required for the generated password.
			$length = $mls->options->users_options->min_length + 8;

			if ( 0 === $chargroup_count ) {
				$strong_password = wp_generate_password( $length );
			} else {
				// divide the length into equal whole number parts.
				$max_chars_per_group = floor( $length / $chargroup_count );

				$password = '';

				foreach ( $chargroups as $chargroup => $chars ) {

					// for each character group, get a part of the password.
					$password .= self::_random_string( $max_chars_per_group, $chars );
				}

				// merge all characters acroos groups in one.
				$allchars = implode( '', $chargroups );

				// if any more characters can be accomodated.
				$full_password = $password . self::_random_string( $length - mb_strlen( $password ), $allchars );

				// shuffle the password.
				$strong_password = \MLS\MB_String_Helper::mb_str_shuffle( $full_password );
			}
			// If doing ajax, then print the result and exit.
			if ( ! $should_return && isset( $_REQUEST['action'] ) && 'ppm_ajax_session_expired' !== $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( wp_doing_ajax() ) {
					wp_send_json_success( $strong_password );
				}
			}

			return $strong_password;
		}

		/**
		 * Generates a random string from given characters.
		 *
		 * @param integer $length Length of generated string.
		 * @param string  $chars A string containing characters used to generate string.
		 *
		 * @return string A random string.
		 *
		 * @since 2.0.0
		 */
		public static function _random_string( $length, $chars ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

			$part_password = '';
			$chars_count   = mb_strlen( $chars );
			for ( $i = 0; $i < $length; $i++ ) {
				$part_password .= mb_substr( $chars, wp_rand( 0, $chars_count - 1 ), 1 );
			}

			return $part_password;
		}
	}

}
