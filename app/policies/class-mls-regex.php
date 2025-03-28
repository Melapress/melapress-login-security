<?php
/**
 * Handles regex within plugin.
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

if ( ! class_exists( '\MLS\MLS_Regex' ) ) {

	/**
	 * Provides regexes to check password against
	 *
	 * @since 2.0.0
	 */
	class MLS_Regex implements \JsonSerializable {

		/**
		 * Patterns.
		 *
		 * @var array Regexes to check password policies
		 *
		 * NOTE: these are javascript regex patterns and not PCRE.
		 *
		 * @since 2.0.0
		 */
		private $rules = array(
			'length'                => '.{$length,}', // the $length placeholder.
			'numeric'               => '[0-9]',
			'upper_case'            => '[A-Z]',
			'lower_case'            => '[a-z]',
			'special_chars'         => '[.,!@#$%^&*()_?£"\-+=~;:€<>]',
			'exclude_special_chars' => '^((?![{excluded_chars}]).)*$',
		);

		/**
		 * Length.
		 *
		 * @var int
		 *
		 * @since 2.0.0
		 */
		public $length;

		/**
		 * Length.
		 *
		 * @var bool
		 *
		 * @since 2.0.0
		 */
		public $numeric;

		/**
		 * Length.
		 *
		 * @var bool
		 *
		 * @since 2.0.0
		 */
		public $upper_case;

		/**
		 * Length.
		 *
		 * @var bool
		 *
		 * @since 2.0.0
		 */
		public $lower_case;

		/**
		 * Length.
		 *
		 * @var bool
		 *
		 * @since 2.0.0
		 */
		public $special_chars;

		/**
		 * Length.
		 *
		 * @var bool
		 *
		 * @since 2.0.0
		 */
		public $exclude_special_chars;

		/**
		 * Plugin Options
		 *
		 * @var object Plugin Options
		 *
		 * @since 2.0.0
		 */
		private $user_options;

		/**
		 * Initialise rules
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function init() {

			global $pagenow;

			// get options.
			$mls                = melapress_login_security();
			$this->user_options = $mls->options->users_options;

			$allowed_pages = array( 'user-new.php', 'user-edit.php', 'profile.php' );
			if ( ! $this->user_options && ! in_array( $pagenow, $allowed_pages, true ) && ! isset( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			$this->rules['special_chars'] = melapress_login_security()->get_special_chars();

			// set minimum length.
			$this->set_min_length();
			// replace the excluded chars placeholder with the values.
			$this->set_excluded_chars();

			// set each property so it can be used conveniently.
			foreach ( $this->user_options->rules as $key => $rule ) {
				if ( \MLS\Helpers\OptionsHelper::string_to_bool( $rule ) ) {
					// for eg, $this->length.
					if ( isset( $this->rules[ $key ] ) ) {
						$this->{$key} = $this->rules[ $key ];
					}
				}

				// If the rule is not enabled in the policy settings,
				// remove it from rules.
				if ( ! \MLS\Helpers\OptionsHelper::string_to_bool( $rule ) ) {
					unset( $this->rules[ $key ] );
				}
			}
		}

		/**
		 * Set minimum length in regex from options
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		private function set_min_length() {
			// replace $length placeholder with actual length.
			$this->rules['length'] = preg_replace( '/\$length/', (string) $this->user_options->min_length, $this->rules['length'] );
		}

		/**
		 * Set the list of excluded chars in the regex.
		 *
		 * @method set_excluded_chars
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		private function set_excluded_chars() {
			// replace $excluded_chars placeholder with actual excluded chars.
			if ( isset( $this->user_options->ui_rules['exclude_special_chars'] )
				&& \MLS\Helpers\OptionsHelper::string_to_bool( $this->user_options->ui_rules['exclude_special_chars'] )
				&& ! empty( $this->user_options->excluded_special_chars )
			) {
				$allowed_special_chars = ltrim( rtrim( $this->rules['special_chars'], ']' ), '[' );
				$excluded_chars_arr    = str_split( html_entity_decode( str_replace( '&pound', '£', $this->user_options->excluded_special_chars ), ENT_QUOTES, 'UTF-8' ), 1 );
				foreach ( $excluded_chars_arr as $excluded_char ) {
					$allowed_special_chars = str_replace( $excluded_char, '', $allowed_special_chars );
				}

				if ( '' !== trim( $allowed_special_chars ) ) {
					$this->rules['special_chars'] = "[{$allowed_special_chars}]";
					// Escape dash.
					$this->rules['special_chars'] = str_replace( '-', '\-', $this->rules['special_chars'] );
					$this->rules['special_chars'] = str_replace( '\-+', '-\+', $this->rules['special_chars'] );
				} else {
					unset( $this->rules['special_chars'] );
				}

				$excluded_chars                       = ( preg_quote( $this->user_options->excluded_special_chars ) ); // phpcs:ignore WordPress.PHP.PregQuoteDelimiter.Missing
				$this->rules['exclude_special_chars'] = preg_replace( '/{excluded_chars}/', $excluded_chars, $this->rules['exclude_special_chars'] );
			} else {
				unset( $this->rules['exclude_special_chars'] );
			}
		}

		/**
		 * Return rules.
		 *
		 * @inheritDoc
		 */
		#[\ReturnTypeWillChange]
		public function jsonSerialize() {
			return $this->rules;
		}
	}

}
