<?php
/**
 * WPassword
 *
 * @package WordPress
 * @subpackage wpassword
 */

namespace PPMWP\Validators;

/**
 * Provides basic validation for the inputs
 *
 * @since 2.5
 */
class Validator {

	/**
	 * Checks if give variable is integer
	 *
	 * @since 2.5
	 *
	 * @param $integer
	 * @param int      $minRange
	 * @param int|bool $maxRange
	 *
	 * @return bool
	 */
	public static function validateInteger( $integer, int $minRange = 0, $maxRange = null ): bool {

		$options = array(
			'min_range' => $minRange,
		);

		if ( $maxRange ) {
			$options['max_range'] = $maxRange;
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
	 * @since 2.5
	 *
	 * @param mixed $value
	 * @param array $possibleValues
	 *
	 * @return boolean
	 */
	public static function validateInSet( $value, array $possibleValues ): bool {

		if ( ! in_array( $value, $possibleValues ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validates password by checking if the password contains username
	 *
	 * @since 2.5
	 *
	 * @param string $password
	 * @param int    $userId
	 * @param string $userName
	 *
	 * @return boolean
	 */
	public static function validatePasswordNotContainUsername( string $password, int $userId = 0, string $userName = '' ): bool {
		if ( '' === trim( $password ) ) {
			return false;
		}

		if ( $userId ) {
			$user = get_userdata( (int) $userId );

			if ( is_wp_error( $user ) ) {
				return false;
			}

			$userName = $user->user_login;
		}

		if ( '' === trim( $userName ) ) {
			$userId = get_current_user_id();
			$user   = get_userdata( (int) $userId );

			if ( is_wp_error( $user ) ) {
				return false;
			}

			$userName = $user->user_login;
		}

		$password = \mb_strtolower( $password );
		$userName = \mb_strtolower( $userName );

		if ( false !== \mb_strpos( $password, $userName ) ) {
			return false;
		}

		return true;
	}
}
