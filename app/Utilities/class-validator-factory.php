<?php
/**
 * Melapress Login Security
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Utilities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Validators\Validator;

/**
 * Calls the proper validator method based on provided rules
 *
 * @since 2.0.0
 */
class Validator_Factory {

	/**
	 * Calls Validator method based on given rules and returns the result
	 *
	 * Expects the following format for rules @see MLS_Options::$default_options_validation_rules
	 * 'typeRule' => [
	 *               ['number', 'inset' ]
	 *               [ 'min', 'max', 'set' ]
	 *           ]
	 *
	 * @param mixed $value - Value to validate.
	 * @param array $rules - Applicable rule.
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public static function validate( $value, array $rules ) {

		if ( isset( $rules['typeRule'] ) ) {
			if ( 'number' === $rules['typeRule'] ) {
				$min = (int) ( $rules['min'] ?? 0 );
				$max = ( $rules['max'] ?? null );

				return Validator::validate_integer( $value, $min, $max );
			}
			if ( 'inset' === $rules['typeRule'] ) {
				$range = $rules['set'] ?? array();

				return Validator::validate_in_set( $value, $range );
			}
		}

		return true;
	}
}
