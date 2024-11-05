<?php
/**
 * String helper.
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

if ( ! class_exists( '\MLS\MB_String_Helper' ) ) {

	/**
	 * Helper object for some string manipulations.
	 *
	 * @since 2.0.0
	 */
	class MB_String_Helper {

		/**
		 * String shuffler.
		 *
		 * @param string $incoming_string Thing to shuffle.
		 *
		 * @return string - Shuffled result.
		 *
		 * @since 2.0.0
		 */
		public static function mb_str_shuffle( $incoming_string ) {
			$chars = self::mb_get_chars( $incoming_string );
			shuffle( $chars );

			return implode( '', $chars );
		}

		/**
		 * Get chars.
		 *
		 * @param string $incoming_string - String to read.
		 *
		 * @return array - result.
		 *
		 * @since 2.0.0
		 */
		private static function mb_get_chars( $incoming_string ) {
			$chars = array();

			for ( $i = 0, $length = mb_strlen( $incoming_string ); $i < $length; ++$i ) {
				$chars[] = mb_substr( $incoming_string, $i, 1, 'UTF-8' );
			}

			return $chars;
		}

		/**
		 * Helper function which basically replaces mb_str_split but is compatible with PHP older than7.4.
		 *
		 * @param string  $incoming_string - String to split.
		 * @param integer $split_length - Split length.
		 * @param mixed   $encoding - Encoding type.
		 *
		 * @return string - Split string.
		 *
		 * @since 2.0.0
		 */
		public static function mb_split_string( $incoming_string, $split_length = 1, $encoding = null ) {
			if ( null !== $incoming_string && ! \is_scalar( $incoming_string ) && ! ( \is_object( $incoming_string ) && \method_exists( $incoming_string, '__toString' ) ) ) {
				trigger_error( 'mb_str_split(): expects parameter 1 to be string, ' . \esc_html( \gettype( $incoming_string ) ) . ' given', E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				return null;
			}
			if ( null !== $split_length && ! \is_bool( $split_length ) && ! \is_numeric( $split_length ) ) {
				trigger_error( 'mb_str_split(): expects parameter 2 to be int, ' . \esc_html( \gettype( $split_length ) ) . ' given', E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				return null;
			}
			$split_length = (int) $split_length;
			if ( 1 > $split_length ) {
				trigger_error( 'mb_str_split(): The length of each segment must be greater than zero', E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				return false;
			}
			if ( null === $encoding ) {
				$encoding = mb_internal_encoding();
			} else {
				$encoding = (string) $encoding;
			}

			if ( ! in_array( $encoding, mb_list_encodings(), true ) ) {
				static $aliases;
				if ( null === $aliases ) {
					$aliases = array();
					foreach ( mb_list_encodings() as $encoding ) {
						$encoding_aliases = mb_encoding_aliases( $encoding );
						if ( $encoding_aliases ) {
							foreach ( $encoding_aliases as $alias ) {
								$aliases[] = $alias;
							}
						}
					}
				}
				if ( ! in_array( $encoding, $aliases, true ) ) {
					trigger_error( 'mb_str_split(): Unknown encoding "' . esc_html( $encoding ) . '"', E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					return null;
				}
			}

			$result = array();
			$length = mb_strlen( $incoming_string, $encoding );
			for ( $i = 0; $i < $length; $i += $split_length ) {
				$result[] = mb_substr( $incoming_string, $i, $split_length, $encoding );
			}
			return $result;
		}
	}
}
