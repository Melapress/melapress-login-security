<?php
/**
 * Melapress Login Security
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Validators;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides basic validation for the inputs
 *
 *  @since 2.0.0
 */
class Validator {

	/**
	 * Checks if give variable is integer
	 *
	 * @param int      $integer - Int to validate.
	 * @param int      $min_range - Minimum.
	 * @param int|bool $max_range - Maximum.
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public static function validate_integer( $integer, int $min_range = 0, $max_range = null ): bool {

		$options = array(
			'min_range' => $min_range,
		);

		if ( $max_range ) {
			$options['max_range'] = $max_range;
		}

		if ( filter_var(
			$integer,
			FILTER_VALIDATE_INT,
			array( 'options' => $options )
		) === false ) {
			return false;
		}

		return true;
	}

	/**
	 * Validates if the value is in given set or not
	 *
	 * @param mixed $value - Needle.
	 * @param array $possible_values - Haystack.
	 *
	 * @return boolean
	 *
	 * @since 2.0.0
	 */
	public static function validate_in_set( $value, array $possible_values ): bool {

		if ( ! in_array( $value, $possible_values, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validates password by checking if the password contains username
	 *
	 * @param string $password - Password to check.
	 * @param int    $user_id - User ID.
	 * @param string $user_name User Name.
	 *
	 * @return boolean
	 *
	 * @since 2.0.0
	 */
	public static function validate_password_not_contain_username( string $password, int $user_id = 0, string $user_name = '' ): bool {
		if ( '' === trim( $password ) ) {
			return false;
		}

		if ( $user_id ) {
			$user = get_userdata( (int) $user_id );

			if ( is_wp_error( $user ) ) {
				return false;
			}

			$user_name = $user->user_login;
		}

		if ( '' === trim( $user_name ) ) {
			$user_id = get_current_user_id();
			$user    = get_userdata( (int) $user_id );

			if ( is_wp_error( $user ) ) {
				return false;
			}

			if ( isset( $user->user_login ) ) {
				$user_name = $user->user_login;
			} else {
				return false;
			}
		}

		$password  = \mb_strtolower( $password );
		$user_name = \mb_strtolower( $user_name );

		if ( false !== \mb_strpos( $password, $user_name ) ) {
			return false;
		}

		return true;
	}
}
