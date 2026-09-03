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

if ( ! class_exists( '\MLS\Helpers\HideAdminNotices' ) ) {

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

			return \is_admin() && ( false !== strpos( $cur_page, 'mls' ) );
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
		 * Remove all non-MLS notices from the plugin pages based on the provided action hook.
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
				if ( ! is_array( $hooks ) ) {
					continue;
				}

				foreach ( $hooks as $name => $arr ) {
					if ( self::is_own_callback( $name, $arr ) ) {
						continue;
					}

					unset( $wp_filter[ $action ]->callbacks[ $priority ][ $name ] );
				}
			}
		}

		/**
		 * Whether a registered notice callback belongs to this plugin.
		 *
		 * The identity is derived from the callback itself rather than trusting
		 * the array key. Two reasons:
		 *
		 * - The key is not necessarily a string. WordPress keys its callbacks by
		 *   a computed id, but anything that appends straight into
		 *   `$wp_filter[ $hook ]->callbacks[ $priority ][]` — a pattern several
		 *   notice-managing plugins use — gets an integer key instead. This file
		 *   declares strict_types, so handing that integer to strpos() was a
		 *   fatal TypeError rather than a coercion, and it took down every
		 *   plugin admin screen for anyone with such a plugin installed.
		 *
		 * - A static callback registered as `array( 'MLS\Foo', 'bar' )` has a
		 *   string class name in the callback, not an object, and the old check
		 *   only recognised objects. With a non-string key as well, one of this
		 *   plugin's own notices could have been removed.
		 *
		 * @param string|int $name - Key the callback is registered under.
		 * @param mixed      $arr  - The registration record.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		private static function is_own_callback( $name, $arr ): bool {
			if ( is_string( $name ) && false !== strpos( $name, 'MLS' ) ) {
				return true;
			}

			if ( ! is_array( $arr ) || ! isset( $arr['function'] ) ) {
				return false;
			}

			$callback = $arr['function'];

			if ( is_string( $callback ) ) {
				return false !== strpos( $callback, 'MLS' );
			}

			if ( is_array( $callback ) && isset( $callback[0] ) ) {
				$class = is_object( $callback[0] )
					? get_class( $callback[0] )
					: ( is_string( $callback[0] ) ? $callback[0] : '' );

				return '' !== $class && false !== strpos( $class, 'MLS' );
			}

			// A closure or invokable carries no name to match on.
			return false;
		}
	}
}
