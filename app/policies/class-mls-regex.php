<?php
/**
 * Handles regex within plugin.
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

if ( ! class_exists( '\MLS\MLS_Regex' ) ) {

	/**
	 * Provides regexes to check password against
	 *
	 * @since 2.0.0
	 */
	class MLS_Regex {

		/**
		 * Default patterns (templates before init replaces placeholders).
		 *
		 * @var array
		 */
		private static array $default_rules = array(
			'length'                => '.{$length,}',
			'numeric'               => '[0-9]',
			'upper_case'            => '[A-Z]',
			'lower_case'            => '[a-z]',
			'special_chars'         => '[.,!@#$%^&*()_?£"\-+=~;:€<>]',
			'exclude_special_chars' => '^((?![{excluded_chars}]).)*$',
		);

		/**
		 * Active rules after init() processes options.
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		private static array $rules = array();

		/**
		 * Plugin options for the current user/role.
		 *
		 * @var array|null
		 *
		 * @since 2.0.0
		 */
		private static $user_options = null;

		/**
		 * Whether init() has already run.
		 *
		 * @var bool
		 */
		private static bool $initialised = false;

		/**
		 * Initialise rules.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function init(): void {
			$userid = get_current_user_id();
			if ( isset( $_GET['user_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$candidate = absint( $_GET['user_id'] );
				if ( $candidate && is_admin() && current_user_can( 'edit_user', $candidate ) ) {
					$userid = $candidate;
				}
			}

			if ( 0 === $userid ) {
				list( $rp_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
				$rp_cookie       = 'wp-resetpass-' . COOKIEHASH;
				if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
					list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

					$user = check_password_reset_key( $rp_key, $rp_login );

					if ( isset( $_POST['pass1'] ) && ! hash_equals( $rp_key, $_POST['rp_key'] ) ) {
						$user = false;
					}

					if ( is_a( $user, '\WP_User' ) ) {
						$userid = $user->ID;
					}
				}
			}

			self::init_for_user( (int) $userid );
		}

		/**
		 * Initialise rules for a trusted user ID supplied by a WordPress hook.
		 *
		 * @param int $userid User whose role policy must be enforced; zero uses defaults.
		 * @return void
		 */
		public static function init_for_user( int $userid ): void {
			global $pagenow;

			// Start from the default templates every time.
			self::$rules        = self::$default_rules;
			self::$user_options = null;
			self::$initialised = false;

			$roles = '';

			if ( 0 !== $userid ) {

				$user = \get_user_by( 'ID', $userid );

				if ( ! is_a( $user, '\WP_User' ) ) {
					return;
				}

				$roles = $user->roles;

				$roles = (array) \MLS\Helpers\OptionsHelper::prioritise_roles( $roles );
				$roles = reset( $roles );
			}

			self::init_for_role( (string) $roles );
		}

		/**
		 * Initialise rules for a role rather than for an account.
		 *
		 * Needed wherever the password being judged does not belong to whoever is
		 * making the request. On user-new.php there is no account yet, so
		 * init_for_user() compiled the rules of the administrator doing the
		 * creating: a role with a stricter policy of its own was enforced only to
		 * whatever the administrator's own policy allowed, with nothing on screen
		 * to say so. The role is in the request, and this is how it gets used.
		 *
		 * @param string $role Role slug the policy is wanted for; empty means the
		 *                     site-wide policy.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function init_for_role( string $role ): void {
			global $pagenow;

			// Start from the default templates every time.
			self::$rules        = self::$default_rules;
			self::$user_options = null;
			self::$initialised  = false;

			$roles = \sanitize_key( $role );

			$options = \get_site_option( '' !== $roles ? MLS_PREFIX . '_' . $roles . '_options' : MLS_PREFIX . '_options', MLS_Options::get_default_options() );

			// A role option stored empty or corrupt would otherwise be indexed
			// as an array on the next line and fatal the request.
			if ( ! is_array( $options ) ) {
				$options = MLS_Options::get_default_options();
			}

			if ( \MLS\Helpers\OptionsHelper::string_to_bool( $options['master_switch'] ) ) {
				/*
				 * master_switch on a role record means "inherit the site-wide
				 * policy" — it is what the "Inherit login security policies"
				 * checkbox writes — so the site-wide policy is what applies and the
				 * role's own stored values are deliberately not used.
				 *
				 * This was written as wp_parse_args() with a single argument, which
				 * merges nothing and returns its input unchanged. Same result, but
				 * it read as though defaults were being combined with something.
				 */
				$options = MLS_Options::get_default_options();
			}

			$allowed_pages = array( 'user-new.php', 'user-edit.php', 'profile.php' );
			if ( ! $options && ! in_array( $pagenow, $allowed_pages, true ) && ! isset( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			self::$user_options = $options;

			self::$rules['special_chars'] = \MLS_Core::get_special_chars();

			// set minimum length.
			self::set_min_length();
			// replace the excluded chars placeholder with the values.
			self::set_excluded_chars();

			// Remove rules that are not enabled in the policy settings.
			foreach ( $options['rules'] as $key => $rule ) {
				if ( ! OptionsHelper::string_to_bool( $rule ) ) {
					unset( self::$rules[ $key ] );
				}
			}

			self::$initialised = true;
		}

		/**
		 * Get the active rules array.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function get_rules(): array {
			return self::$rules;
		}

		/**
		 * Check whether init() has been called.
		 *
		 * @return bool
		 */
		public static function is_initialised(): bool {
			return self::$initialised;
		}

		/**
		 * Reset state (useful for testing).
		 *
		 * @return void
		 */
		public static function reset(): void {
			self::$rules        = array();
			self::$user_options = null;
			self::$initialised  = false;
		}

		/**
		 * Set minimum length in regex from options.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		private static function set_min_length(): void {
			self::$rules['length'] = preg_replace( '/\$length/', (string) self::$user_options['min_length'], self::$rules['length'] );
		}

		/**
		 * Set the list of excluded chars in the regex.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		private static function set_excluded_chars(): void {
			if ( isset( self::$user_options['ui_rules']['exclude_special_chars'] )
				&& OptionsHelper::string_to_bool( self::$user_options['ui_rules']['exclude_special_chars'] )
				&& ! empty( self::$user_options['excluded_special_chars'] )
			) {
				$allowed_special_chars = ltrim( rtrim( self::$rules['special_chars'], ']' ), '[' );
				$excluded_chars_arr    = str_split( html_entity_decode( str_replace( '&pound', '£', self::$user_options['excluded_special_chars'] ), ENT_QUOTES, 'UTF-8' ), 1 );
				foreach ( $excluded_chars_arr as $excluded_char ) {
					$allowed_special_chars = str_replace( $excluded_char, '', $allowed_special_chars );
				}

				if ( '' !== trim( $allowed_special_chars ) ) {
					self::$rules['special_chars'] = "[{$allowed_special_chars}]";
					self::$rules['special_chars'] = str_replace( '-', '\-', self::$rules['special_chars'] );
					self::$rules['special_chars'] = str_replace( '\-+', '-\+', self::$rules['special_chars'] );
				} else {
					unset( self::$rules['special_chars'] );
				}

				$excluded_chars                       = ( preg_quote( self::$user_options['excluded_special_chars'] ) ); // phpcs:ignore WordPress.PHP.PregQuoteDelimiter.Missing
				self::$rules['exclude_special_chars'] = preg_replace( '/{excluded_chars}/', $excluded_chars, self::$rules['exclude_special_chars'] );
			} else {
				unset( self::$rules['exclude_special_chars'] );
			}
		}
	}

}
