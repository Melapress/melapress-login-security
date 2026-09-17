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
		 * The other Melapress plugins, by wordpress.org slug.
		 *
		 * Their notices are kept on these screens: a customer running two of our
		 * plugins should still be told that one of them needs a licence renewing
		 * or has finished an upgrade, and silencing our own family made the
		 * plugin look like the thing that had gone quiet.
		 *
		 * Premium builds install under the same slug with "-premium" appended,
		 * which is handled where this list is read. The list is a fast path
		 * rather than the whole answer — a directory renamed on the way in still
		 * matches through the plugin header, see is_sibling_plugin_dir().
		 *
		 * @var string[]
		 *
		 * @since 2.4.2
		 */
		private const SIBLING_PLUGIN_SLUGS = array(
			'admin-notices-manager',
			'melapress-login-security',
			'melapress-role-editor',
			'website-file-changes-monitor',
			'wp-2fa',
			'wp-security-audit-log',
		);

		/**
		 * Plugin directories that belong to us, resolved once per request.
		 *
		 * @var array<string, bool>|null
		 *
		 * @since 2.4.2
		 */
		private static $sibling_dirs = null;

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
		 * Lift the other Melapress plugins' notices above the page title.
		 *
		 * Two separate mechanisms decide where a notice ends up, and only the
		 * second one matters here:
		 *
		 * 1. Core fires `admin_notices` from admin-header.php, before the page
		 *    callback runs. Everything printed there is already above the title
		 *    in the document.
		 * 2. Then wp-admin/js/common.js moves every `div.notice`, `div.updated`
		 *    and `div.error` to sit just after `.wp-header-end` — or, when a
		 *    screen does not print that marker as these ones do not, just after
		 *    the first `h1`/`h2` inside `.wrap`. That is what drops them below
		 *    the title.
		 *
		 * That relocation has a documented opt-out: it skips anything carrying
		 * `.inline`. So a sibling plugin's notice is run early, marked, and left
		 * exactly where it was printed — above the title.
		 *
		 * This plugin's own notices are deliberately untouched and keep landing
		 * under the title where they always have.
		 *
		 * @return void
		 *
		 * @since 2.4.2
		 */
		public static function raise_sibling_notices() {
			if ( ! self::is_admin_page() ) {
				return;
			}

			$markup = '';

			foreach ( self::notice_hooks_for_this_screen() as $action ) {
				$markup .= self::render_sibling_notices( $action );
			}

			if ( '' === trim( $markup ) ) {
				return;
			}

			self::print_sibling_notice_styles();

			echo '<div class="mls-sibling-notices">' . $markup . '</div>'; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Markup produced by the sibling plugin's own notice callback.
		}

		/**
		 * Give raised notices the spacing they would have had further down.
		 *
		 * Core styles a notice differently depending on where it sits. The base
		 * rule is `margin: 5px 15px 2px`, but `.wrap .notice` overrides it with
		 * `margin: 5px 0 15px`. Notices normally end up inside `.wrap`, because
		 * common.js moves them next to the page title, so that override is what
		 * an administrator is used to seeing.
		 *
		 * These are deliberately left above the title, which puts them outside
		 * `.wrap` and back on the base rule — indented by 15px and almost
		 * touching whatever follows. The wrapper takes `.wrap`'s own margins so
		 * the notices line up with the page, and the notices inside it are given
		 * the in-wrap spacing.
		 *
		 * (The `inline` class carries no styling of its own in current core; it
		 * only tells common.js to leave the element alone.)
		 *
		 * @return void
		 *
		 * @since 2.4.2
		 */
		private static function print_sibling_notice_styles() {
			?>
			<style>
				.mls-sibling-notices {
					/* Matches core's own .wrap, so the notices line up with the page. */
					margin: 10px 20px 0 2px;
				}

				.mls-sibling-notices .notice,
				.mls-sibling-notices div.updated,
				.mls-sibling-notices div.error {
					/* What core gives a notice once it is inside .wrap. */
					margin: 5px 0 15px;
				}

				.mls-sibling-notices .notice:last-child,
				.mls-sibling-notices div.updated:last-child,
				.mls-sibling-notices div.error:last-child {
					margin-bottom: 0;
				}
			</style>
			<?php
		}

		/**
		 * The notice hooks core will actually fire on this screen.
		 *
		 * admin-header.php fires exactly one of network_admin_notices,
		 * user_admin_notices and admin_notices — whichever matches the screen —
		 * and then all_admin_notices.
		 *
		 * Draining all four instead printed notices core would never have shown
		 * here. Registering the same notice on more than one of them is an
		 * ordinary defensive habit, so a plugin doing that had its notice
		 * rendered twice: once for the hook belonging to this screen and again
		 * for one that belongs to another.
		 *
		 * Removal is a different matter — hide_unrelated_notices() still clears
		 * every hook, because taking a callback off a hook that never fires
		 * costs nothing and misses nothing.
		 *
		 * @return string[]
		 *
		 * @since 2.4.2
		 */
		private static function notice_hooks_for_this_screen(): array {
			if ( \is_network_admin() ) {
				$screen_hook = 'network_admin_notices';
			} elseif ( \is_user_admin() ) {
				$screen_hook = 'user_admin_notices';
			} else {
				$screen_hook = 'admin_notices';
			}

			return array( $screen_hook, 'all_admin_notices' );
		}

		/**
		 * Run and detach the sibling-plugin callbacks on one hook.
		 *
		 * They are removed once captured so that core does not print them a
		 * second time when it fires the hook itself.
		 *
		 * @param string $action - Notice hook to drain.
		 *
		 * @return string Captured markup, with the relocation opt-out applied.
		 *
		 * @since 2.4.2
		 */
		private static function render_sibling_notices( $action ) {
			global $wp_filter;

			if ( empty( $wp_filter[ $action ]->callbacks ) || ! is_array( $wp_filter[ $action ]->callbacks ) ) {
				return '';
			}

			$markup = '';

			foreach ( $wp_filter[ $action ]->callbacks as $priority => $hooks ) {
				if ( ! is_array( $hooks ) ) {
					continue;
				}

				foreach ( $hooks as $name => $arr ) {
					// Ours stay where they are; only the siblings move.
					if ( self::is_own_callback( $name, $arr ) || ! self::is_sibling_callback( $arr ) ) {
						continue;
					}

					unset( $wp_filter[ $action ]->callbacks[ $priority ][ $name ] );

					if ( ! isset( $arr['function'] ) || ! is_callable( $arr['function'] ) ) {
						continue;
					}

					ob_start();

					try {
						call_user_func( $arr['function'] );
					} catch ( \Throwable $e ) {
						/*
						 * Another plugin's notice is not worth taking the screen
						 * down for. Drop whatever it managed to print and carry
						 * on with the rest.
						 */
						ob_end_clean();
						continue;
					}

					$markup .= self::opt_out_of_relocation( (string) ob_get_clean() );
				}
			}

			return $markup;
		}

		/**
		 * Add core's `inline` class so common.js leaves the notice in place.
		 *
		 * Only the opening tag of a notice-ish element is touched, and only when
		 * the class is not already there.
		 *
		 * @param string $markup - Captured notice markup.
		 *
		 * @return string
		 *
		 * @since 2.4.2
		 */
		private static function opt_out_of_relocation( $markup ) {
			if ( '' === trim( $markup ) ) {
				return '';
			}

			return (string) preg_replace_callback(
				'#<div\b([^>]*\bclass=(["\'])([^"\']*\b(?:notice|updated|error)\b[^"\']*)\2[^>]*)>#i',
				function ( $matches ) {
					if ( preg_match( '/\binline\b/', $matches[3] ) ) {
						return $matches[0];
					}

					$attributes = str_replace(
						'class=' . $matches[2] . $matches[3] . $matches[2],
						'class=' . $matches[2] . $matches[3] . ' inline' . $matches[2],
						$matches[1]
					);

					return '<div' . $attributes . '>';
				},
				$markup
			);
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
					if ( self::is_own_callback( $name, $arr ) || self::is_sibling_callback( $arr ) ) {
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

		/**
		 * Whether a notice callback comes from another Melapress plugin.
		 *
		 * Decided by where the callback is defined rather than what it is
		 * called. Our plugins prefix their classes half a dozen different ways —
		 * WSAL, WP2FA, ANM and so on — and a closure or a plain function has no
		 * prefix at all, so matching on names would keep some of our notices and
		 * drop others. The file a callback lives in says plainly which plugin
		 * registered it.
		 *
		 * @param mixed $arr - The registration record.
		 *
		 * @return bool
		 *
		 * @since 2.4.2
		 */
		private static function is_sibling_callback( $arr ): bool {
			if ( ! is_array( $arr ) || ! isset( $arr['function'] ) ) {
				return false;
			}

			$file = self::callback_source_file( $arr['function'] );

			if ( '' === $file ) {
				return false;
			}

			return self::is_sibling_plugin_dir( self::plugin_dir_from_file( $file ) );
		}

		/**
		 * The file a callback is defined in.
		 *
		 * @param mixed $callback - Anything WordPress accepts as a callback.
		 *
		 * @return string Absolute path, or '' when it cannot be resolved.
		 *
		 * @since 2.4.2
		 */
		private static function callback_source_file( $callback ): string {
			try {
				if ( $callback instanceof \Closure ) {
					$reflection = new \ReflectionFunction( $callback );
				} elseif ( is_string( $callback ) && false !== strpos( $callback, '::' ) ) {
					$reflection = new \ReflectionMethod( $callback );
				} elseif ( is_string( $callback ) ) {
					if ( ! function_exists( $callback ) ) {
						return '';
					}
					$reflection = new \ReflectionFunction( $callback );
				} elseif ( is_array( $callback ) && isset( $callback[0], $callback[1] ) ) {
					$reflection = new \ReflectionMethod( $callback[0], (string) $callback[1] );
				} elseif ( is_object( $callback ) && method_exists( $callback, '__invoke' ) ) {
					$reflection = new \ReflectionMethod( $callback, '__invoke' );
				} else {
					return '';
				}
			} catch ( \ReflectionException $e ) {
				// A callback we cannot look at is one we cannot vouch for.
				return '';
			}

			$file = $reflection->getFileName();

			return is_string( $file ) ? \wp_normalize_path( $file ) : '';
		}

		/**
		 * The plugin directory a file sits in, if any.
		 *
		 * @param string $file - Absolute path, already normalised.
		 *
		 * @return string Directory name directly under the plugins folder, or ''.
		 *
		 * @since 2.4.2
		 */
		private static function plugin_dir_from_file( string $file ): string {
			if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
				return '';
			}

			$plugins_root = \trailingslashit( \wp_normalize_path( WP_PLUGIN_DIR ) );

			if ( 0 !== strpos( $file, $plugins_root ) ) {
				return '';
			}

			$relative = substr( $file, strlen( $plugins_root ) );
			$segments = explode( '/', $relative );

			// A single-file plugin has no directory of its own to judge.
			return count( $segments ) > 1 ? $segments[0] : '';
		}

		/**
		 * Whether a plugin directory is one of ours.
		 *
		 * @param string $dir - Directory name under the plugins folder.
		 *
		 * @return bool
		 *
		 * @since 2.4.2
		 */
		private static function is_sibling_plugin_dir( string $dir ): bool {
			if ( '' === $dir ) {
				return false;
			}

			if ( null === self::$sibling_dirs ) {
				self::$sibling_dirs = self::resolve_sibling_dirs();
			}

			return ! empty( self::$sibling_dirs[ $dir ] );
		}

		/**
		 * Work out which installed plugin directories belong to us.
		 *
		 * The known slugs answer for a normal install, with "-premium" allowed on
		 * the end because that is how the paid builds are packaged. The plugin
		 * headers answer for everything else — a directory renamed by hand, a
		 * build we ship later, a bundle installed under its own name — by looking
		 * for us as the author.
		 *
		 * @return array<string, bool>
		 *
		 * @since 2.4.2
		 */
		private static function resolve_sibling_dirs(): array {
			$dirs = array();

			foreach ( self::SIBLING_PLUGIN_SLUGS as $slug ) {
				$dirs[ $slug ]              = true;
				$dirs[ $slug . '-premium' ] = true;
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				$plugin_admin = ABSPATH . 'wp-admin/includes/plugin.php';

				if ( ! is_readable( $plugin_admin ) ) {
					return $dirs;
				}

				require_once $plugin_admin;
			}

			foreach ( \get_plugins() as $plugin_file => $plugin_data ) {
				$dir = dirname( \wp_normalize_path( $plugin_file ) );

				if ( '.' === $dir || isset( $dirs[ $dir ] ) ) {
					continue;
				}

				$author = strtolower( ( $plugin_data['Author'] ?? '' ) . ' ' . ( $plugin_data['AuthorURI'] ?? '' ) );

				if ( false !== strpos( $author, 'melapress' ) || false !== strpos( $author, 'wp white security' ) ) {
					$dirs[ $dir ] = true;
				}
			}

			return $dirs;
		}
	}
}
