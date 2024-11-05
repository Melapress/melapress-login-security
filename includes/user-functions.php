<?php
/**
 * User facing functions
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks if a user is exempted from the policies
 *
 * @param integer $user_id - ID of user we are checking.
 *
 * @return boolean
 *
 * @since 2.0.0
 */
if ( ! function_exists( 'ppm_is_user_exempted' ) ) {
	/**
	 * Checks if a user is exempted from the policies
	 *
	 * @param integer $user_id - ID of user we are checking.
	 *
	 * @return boolean
	 *
	 * @since 2.0.0
	 */
	function ppm_is_user_exempted( $user_id = false ) {
		$exempted = MLS_Core::is_user_exempted( $user_id );
		return $exempted;
	}
}
