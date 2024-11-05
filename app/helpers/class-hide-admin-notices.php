<?php
/**
 * Helper class to hide other admin notices.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class to hide other admin notices.
 *
 * @since 2.0.0
 */
class HideAdminNotices {

	/**
	 * Check whether we are on an admin and plugin page.
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public static function is_admin_page() {
		$cur_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$check    = 'ppm';

		return \is_admin() && ( false !== strpos( $cur_page, $check ) );
	}

	/**
	 * Remove all non MLS plugin notices from our plugin pages.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public static function hide_unrelated_notices() {

		// Bail if we're not on our screen or page.
		if ( ! self::is_admin_page() ) {
			return;
		}

		self::remove_unrelated_actions( 'user_admin_notices' );
		self::remove_unrelated_actions( 'admin_notices' );
		self::remove_unrelated_actions( 'all_admin_notices' );
		self::remove_unrelated_actions( 'network_admin_notices' );
	}

	/**
	 * Remove all non-WP Mail SMTP notices from the our plugin pages based on the provided action hook.
	 *
	 * @param string $action The name of the action.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	private static function remove_unrelated_actions( $action ) {

		global $wp_filter;

		if ( empty( $wp_filter[ $action ]->callbacks ) || ! is_array( $wp_filter[ $action ]->callbacks ) ) {
			return;
		}

		foreach ( $wp_filter[ $action ]->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $name => $arr ) {
				if (
				( // Cover object method callback case.
					is_array( $arr['function'] ) &&
					isset( $arr['function'][0] ) &&
					is_object( $arr['function'][0] ) &&
					strpos( strtolower( get_class( $arr['function'][0] ) ), 'ppm' ) !== false
				) ||
				( // Cover class static method callback case.
					! empty( $name ) &&
					strpos( strtolower( $name ), 'ppm' ) !== false
				)
				) {
					continue;
				}

				unset( $wp_filter[ $action ]->callbacks[ $priority ][ $name ] );
			}
		}
	}
}
