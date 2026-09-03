<?php
/**
 * Licensing Factory for Melapress Login Security plugin.
 *
 * Central entry point for licensing operations. This factory determines which
 * licensing provider to use (Freemius or EDD) and routes all licensing calls
 * through the appropriate provider implementation.
 *
 * @since      2.0.0
 * @package    MelapressLoginSecurity
 * @subpackage Licensing
 * @copyright  2026 Melapress
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link       https://melapress.com/wordpress-login-security/
 */

declare(strict_types=1);

namespace MLS\Licensing;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use MLS\Licensing\EDD_Provider;
use MLS\Licensing\Freemius_Provider;

if ( ! class_exists( '\MLS\Licensing\Licensing_Factory' ) ) {

	/**
	 * Factory class for licensing providers.
	 *
	 * Implements a singleton pattern and provides a unified interface
	 * to whichever licensing provider is active.
	 *
	 * @since 2.0.0
	 */
	class Licensing_Factory {

		/**
		 * The active licensing provider instance.
		 *
		 * @var Licensing_Provider|null
		 * @since 3.2.0
		 */
		private static $provider = null;

		/**
		 * The provider type being used.
		 *
		 * @var string|null
		 * @since 3.2.0
		 */
		private static $provider_type = null;

		/*
		|----------------------------------------------------------------------
		| Plugin-specific configuration.
		|
		| Change ONLY these constants when adapting for a different plugin.
		| All other Licensing classes reference Licensing_Factory for these.
		|----------------------------------------------------------------------
		*/

		/**
		 * Text domain used for translations.
		 *
		 * @var string
		 */
		public const TEXT_DOMAIN = 'melapress-login-security';

		/**
		 * Human-readable plugin name (used in admin notices).
		 *
		 * @var string
		 */
		public const PLUGIN_NAME = 'Melapress Login Security';

		/**
		 * Pricing page URL template.
		 *
		 * @var string
		 */
		public const PRICING_URL = 'https://melapress.com/wordpress-login-security/pricing/?&utm_source=plugin&utm_medium=mls&utm_campaign=edd_pricing';

		/**
		 * Admin menu icon render callback class (fully qualified).
		 * Set to empty string if not applicable.
		 *
		 * @var string
		 */
		public const MENU_ICON_CLASS = '\MLS\Admin\Admin';

		/**
		 * Admin menu icon render callback method.
		 *
		 * @var string
		 */
		public const MENU_ICON_METHOD = 'render_menu_icon_styles';

		/**
		 * Human-readable menu title for the top-level admin menu.
		 *
		 * @var string
		 */
		public const MENU_TITLE = 'Login Security';

		/**
		 * Short prefix used for unified AJAX actions, nonce, script handles, and HTML IDs.
		 * Must be unique per plugin and URL-safe (lowercase, no spaces).
		 *
		 * @var string
		 */
		public const SLUG_PREFIX = 'mls';

		/**
		 * Freemius plugin ID.
		 *
		 * @var string
		 */
		public const FREEMIUS_PLUGIN_ID = '4028';

		/**
		 * Freemius internal slug (underscored).
		 *
		 * @var string
		 */
		public const FREEMIUS_INTERNAL_SLUG = 'melapress_login_security';

		/**
		 * Freemius external slug (hyphenated).
		 *
		 * @var string
		 */
		public const FREEMIUS_SLUG = 'melapress-login-security';

		/**
		 * Freemius public key.
		 *
		 * @var string
		 */
		public const FREEMIUS_PUBLIC_KEY = 'pk_9abad03ceb8172d40170994a44140';

		/**
		 * Freemius premium status option name.
		 *
		 * @var string
		 */
		public const FREEMIUS_PREMIUM_OPTION = 'fs_mls_premium';

		/**
		 * Plugin icon path (relative to plugin root) for Freemius.
		 *
		 * @var string
		 */
		public const PLUGIN_ICON_PATH = 'assets/images/mls-square.jpg';

		/**
		 * External pricing page URL for Freemius redirect.
		 *
		 * @var string
		 */
		public const FREEMIUS_PRICING_URL = 'https://melapress.com/wordpress-login-security/pricing/?&utm_source=plugin&utm_medium=mls&utm_campaign=priciing_url';

		/**
		 * External pricing page redirect URL (used in Freemius redirect handler).
		 *
		 * @var string
		 */
		public const FREEMIUS_PRICING_REDIRECT_URL = 'https://melapress.com/wordpress-login-security/pricing/?&utm_source=plugin&utm_medium=mls&utm_campaign=redirect_to_external_price_page';

		/**
		 * Freemius connect message URL for the opt-in message.
		 *
		 * @var string
		 */
		public const FREEMIUS_OPTIN_URL = 'https://melapress.com/wordpress-login-security/?&utm_source=plugin&utm_medium=mls&utm_campaign=optin_message';

		/*
		|----------------------------------------------------------------------
		| Plugin identity constants (wrap the plugin's global defines).
		|----------------------------------------------------------------------
		*/

		/**
		 * Absolute path to the main plugin file.
		 *
		 * @var string
		 */
		public const PLUGIN_FILE = MLS_FILE;

		/**
		 * Absolute path to the plugin directory (with trailing slash).
		 *
		 * @var string
		 */
		public const PLUGIN_PATH = MLS_PATH;

		/**
		 * URL to the plugin directory (with trailing slash).
		 *
		 * @var string
		 */
		public const PLUGIN_URL = MLS_PLUGIN_URL;

		/**
		 * Current plugin version string.
		 *
		 * @var string
		 */
		public const PLUGIN_VERSION = MLS_VERSION;

		/**
		 * Plugin basename (e.g. 'melapress-login-security-premium/melapress-login-security.php').
		 *
		 * @var string
		 */
		public const PLUGIN_BASENAME = MLS_BASENAME;

		/**
		 * Plugin directory slug (folder name).
		 *
		 * @var string
		 */
		public const PLUGIN_SLUG = 'melapress-login-security-premium';

		/**
		 * Admin menu slug (top-level page).
		 *
		 * @var string
		 */
		public const MENU_SLUG = MLS_MENU_SLUG;

		/*
		|----------------------------------------------------------------------
		| Unified licensing configuration.
		|----------------------------------------------------------------------
		*/

		/**
		 * Option name for storing the preferred licensing provider.
		 *
		 * @var string
		 */
		public const PROVIDER_OPTION = 'mls_licensing_provider';

		/**
		 * Query-string parameter for switching providers.
		 *
		 * @var string
		 */
		public const SWITCH_PROVIDER_PARAM = 'mls_switch_provider';

		/**
		 * Nonce action for unified license AJAX requests.
		 *
		 * @var string
		 */
		public const UNIFIED_NONCE_ACTION = 'mls_unified_license';

		/**
		 * Script handle for the unified license JS.
		 *
		 * @var string
		 */
		public const UNIFIED_SCRIPT_HANDLE = 'mls-unified-licensing';

		/**
		 * Page slug for the unified license submenu page.
		 *
		 * @var string
		 */
		public const UNIFIED_LICENSE_PAGE_SLUG = 'mls-license';

		/**
		 * AJAX action: unified license activation.
		 *
		 * @var string
		 */
		public const UNIFIED_AJAX_ACTIVATE = 'mls_unified_activate_license';

		/**
		 * AJAX action: unified license deactivation.
		 *
		 * @var string
		 */
		public const UNIFIED_AJAX_DEACTIVATE = 'mls_unified_deactivate_license';

		/**
		 * AJAX action: unified license sync.
		 *
		 * @var string
		 */
		public const UNIFIED_AJAX_SYNC = 'mls_unified_sync_license';

		/**
		 * AJAX action: unified license change (EDD only).
		 *
		 * @var string
		 */
		public const UNIFIED_AJAX_CHANGE = 'mls_unified_change_license';

		/**
		 * Fallback pricing URL (used when no provider is available).
		 *
		 * @var string
		 */
		public const FALLBACK_PRICING_URL = 'https://melapress.com/wordpress-login-security/pricing/';

		/**
		 * Fallback account URL (used when no provider is available).
		 *
		 * @var string
		 */
		public const FALLBACK_ACCOUNT_URL = 'https://melapress.com/account/';

		/*
		|----------------------------------------------------------------------
		| Network licensing configuration.
		|----------------------------------------------------------------------
		*/

		/**
		 * Network option for storing per-site activation data.
		 *
		 * @var string
		 */
		public const NETWORK_ACTIVATIONS_OPTION = 'mls_edd_network_activations';

		/**
		 * Transient name for tracking batch activation progress.
		 *
		 * @var string
		 */
		public const NETWORK_PROGRESS_TRANSIENT = 'mls_edd_activation_progress';

		/**
		 * Network option flag for failed subsite activation (insufficient slots).
		 *
		 * @var string
		 */
		public const NETWORK_ACTIVATION_FAILED_FLAG = 'mls_edd_subsite_activation_failed';

		/*
		|----------------------------------------------------------------------
		| Freemius UI messages.
		|----------------------------------------------------------------------
		*/

		/**
		 * Disclaimer text shown in Freemius opt-in messages.
		 *
		 * @var string
		 */
		public const OPTIN_DISCLAIMER = 'NO LOGIN SECURITY DATA IS SENT BACK TO OUR SERVERS.';


		/**
		 * Initialize the licensing factory.
		 *
		 * This should be called early in the plugin bootstrap process.
		 *
		 * @return void
		 * @since 3.2.0
		 */
		public static function init() {
			$has_license_data = self::has_stored_license_data();

			// Only initialize a provider if there's evidence of a prior activation.
			// This prevents Freemius SDK from loading its connect page on fresh installs
			// where our unified license form should be the only entry point.
			if ( $has_license_data ) {
				$provider = self::get_provider();
				if ( $provider ) {
					$provider::init();
				}
			}

			// Hook to allow switching providers via admin.
			add_action( 'admin_init', array( __CLASS__, 'maybe_switch_provider' ) );

			// Determine if we have an active valid license.
			// Only check via the provider if stored data exists (avoids triggering SDK).
			$is_licensed  = $has_license_data && self::has_active_valid_license();
			$is_free_mode = $has_license_data && ! $is_licensed;

			// Register unified license page when no license data exists at all (fresh install).
			// In free mode (expired license), keep normal menus and add license as submenu.
			if ( ! $is_licensed && ! $is_free_mode ) {
				if ( \is_multisite() ) {
					add_action( 'network_admin_menu', array( __CLASS__, 'register_license_page' ), 900 );
				} else {
					add_action( 'admin_menu', array( __CLASS__, 'register_license_page' ), 900 );
				}
			} else {
				// When license is active, register license management submenu.
				// Skip if Freemius is the provider — it handles its own Account page.
				if ( 'freemius' !== self::get_provider_type() ) {
					if ( \is_multisite() ) {
						add_action( 'network_admin_menu', array( __CLASS__, 'register_license_submenu' ), 50 );
					} else {
						add_action( 'admin_menu', array( __CLASS__, 'register_license_submenu' ), 50 );
					}
				}

				// Ensure the license/account submenu is always the last item.
				if ( \is_multisite() ) {
					add_action( 'network_admin_menu', array( __CLASS__, 'reorder_license_submenu_last' ), 99999 );
				} else {
					add_action( 'admin_menu', array( __CLASS__, 'reorder_license_submenu_last' ), 99999 );
				}
			}

			// Unified AJAX handler for license activation.
			add_action( 'wp_ajax_' . self::UNIFIED_AJAX_ACTIVATE, array( __CLASS__, 'ajax_activate_license' ) );
			add_action( 'wp_ajax_' . self::UNIFIED_AJAX_DEACTIVATE, array( __CLASS__, 'ajax_deactivate_license' ) );
			add_action( 'wp_ajax_' . self::UNIFIED_AJAX_SYNC, array( __CLASS__, 'ajax_sync_license' ) );
			add_action( 'wp_ajax_' . self::UNIFIED_AJAX_CHANGE, array( __CLASS__, 'ajax_change_license' ) );
		}

		/**
		 * Check if there's stored license data from a prior activation.
		 *
		 * Used to determine if provider initialization (and SDK loading) should
		 * happen. On a fresh install with no prior activation, this returns false
		 * which prevents the Freemius SDK from loading its connect page.
		 *
		 * @return bool True if license data exists for any provider.
		 * @since 3.3.0
		 */
		public static function has_stored_license_data(): bool {
			// On multisite, license data may be on the main site.
			$is_multisite = \is_multisite();
			$main_site_id = $is_multisite ? \get_main_site_id() : 0;

			// Check EDD license data.
			$edd_key = \get_option( EDD_Provider::LICENSE_KEY_OPTION, '' );
			if ( empty( $edd_key ) && $is_multisite ) {
				$edd_key = \get_blog_option( $main_site_id, EDD_Provider::LICENSE_KEY_OPTION, '' );
			}
			if ( ! empty( $edd_key ) ) {
				if ( empty( \get_option( self::PROVIDER_OPTION, '' ) ) ) {
					\update_option( self::PROVIDER_OPTION, 'edd' );
				}
				if ( $is_multisite && empty( \get_blog_option( $main_site_id, self::PROVIDER_OPTION, '' ) ) ) {
					\update_blog_option( $main_site_id, self::PROVIDER_OPTION, 'edd' );
				}
				return true;
			}

			/**
			 * Check Freemius premium option (indicates prior Freemius activation).
			 *
			 * Uses null as the default to distinguish "option doesn't exist" (fresh
			 * install) from "option is 'no'" (Freemius was active but sync or
			 * deactivation set it to 'no'). On multisite, the first admin load
			 * after a plugin update can trigger sync_premium_license() before the
			 * SDK is ready, writing 'no' even though the license is still valid.
			 * Checking for existence rather than 'yes' prevents this from
			 * misidentifying the site as a fresh install.
			 */
			$fs_premium = \get_option( Freemius_Provider::FS_WP2FAP_OPTION, null );
			if ( null === $fs_premium && $is_multisite ) {
				$fs_premium = \get_blog_option( $main_site_id, Freemius_Provider::FS_WP2FAP_OPTION, null );
			}
			if ( null !== $fs_premium ) {
				if ( empty( \get_option( self::PROVIDER_OPTION, '' ) ) ) {
					\update_option( self::PROVIDER_OPTION, 'freemius' );
				}
				if ( $is_multisite && empty( \get_blog_option( $main_site_id, self::PROVIDER_OPTION, '' ) ) ) {
					\update_blog_option( $main_site_id, self::PROVIDER_OPTION, 'freemius' );
				}
				return true;
			}

			// Check explicit provider preference (set after first activation).
			$provider = \get_option( self::PROVIDER_OPTION, '' );
			if ( empty( $provider ) && $is_multisite ) {
				$provider = \get_blog_option( $main_site_id, self::PROVIDER_OPTION, '' );
				if ( ! empty( $provider ) ) {
					\update_option( self::PROVIDER_OPTION, $provider );
				}
			}
			if ( ! empty( $provider ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get the active licensing provider.
		 *
		 * Determines which provider to use based on availability and configuration.
		 * Priority order:
		 * 1. Explicitly configured provider (via option or filter)
		 * 2. Freemius (if available)
		 * 3. EDD (fallback)
		 *
		 * @param bool $force_refresh Force re-detection of provider.
		 * @return Licensing_Provider|null The active provider class name or null.
		 * @since 3.2.0
		 */
		public static function get_provider( bool $force_refresh = false ) {
			if ( null !== self::$provider && ! $force_refresh ) {
				return self::$provider;
			}

			// Check for explicitly configured provider.
			$preferred_provider = get_option( self::PROVIDER_OPTION, '' );

			// Allow filtering the preferred provider.
			// $preferred_provider = apply_filters( self::SLUG_PREFIX . '_licensing_provider', $preferred_provider );

			// Validate and use preferred provider if specified and available.
			if ( ! empty( $preferred_provider ) ) {
				if ( 'freemius' === $preferred_provider && Freemius_Provider::is_available() ) {
					self::$provider      = Freemius_Provider::class;
					self::$provider_type = 'freemius';
					return self::$provider;
				} elseif ( 'edd' === $preferred_provider && EDD_Provider::is_available() ) {
					self::$provider      = EDD_Provider::class;
					self::$provider_type = 'edd';
					return self::$provider;
				}
			}

			// // Auto-detect based on availability - Freemius takes priority.
			// if ( Freemius_Provider::is_available() ) {
			// self::$provider      = Freemius_Provider::class;
			// self::$provider_type = 'freemius';
			// return self::$provider;
			// }

			// // Fallback to EDD.
			// if ( EDD_Provider::is_available() ) {
			// self::$provider      = EDD_Provider::class;
			// self::$provider_type = 'edd';
			// return self::$provider;
			// }

			// No provider available.
			self::$provider      = null;
			self::$provider_type = null;
			return null;
		}

		/**
		 * Get the provider type name.
		 *
		 * @return string Provider type ('freemius', 'edd', or 'none').
		 * @since 3.2.0
		 */
		public static function get_provider_type(): string {
			if ( null === self::$provider_type ) {
				self::get_provider();
			}

			return self::$provider_type ?? 'none';
		}

		/**
		 * Check if a provider is active.
		 *
		 * @return bool True if a provider is available, false otherwise.
		 * @since 3.2.0
		 */
		public static function has_provider(): bool {
			return null !== self::get_provider();
		}

		/**
		 * Set the preferred licensing provider.
		 *
		 * @param string $provider Provider type ('freemius' or 'edd').
		 * @return bool True on success, false on failure.
		 * @since 3.2.0
		 */
		public static function set_provider( string $provider ): bool {
			if ( ! in_array( $provider, array( 'freemius', 'edd' ), true ) ) {
				return false;
			}

			// Verify the provider is available.
			if ( 'freemius' === $provider && ! Freemius_Provider::is_available() ) {
				return false;
			}

			if ( 'edd' === $provider && ! EDD_Provider::is_available() ) {
				return false;
			}

			update_option( self::PROVIDER_OPTION, $provider );
			self::get_provider( true ); // Force refresh.

			return true;
		}

		/**
		 * Maybe switch provider based on admin request.
		 *
		 * @return void
		 * @since 3.2.0
		 */
		public static function maybe_switch_provider() {
			if ( ! isset( $_GET[ self::SWITCH_PROVIDER_PARAM ] ) || ! isset( $_GET['_wpnonce'] ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::SWITCH_PROVIDER_PARAM ) ) {
				return;
			}

			$new_provider = sanitize_text_field( wp_unslash( $_GET[ self::SWITCH_PROVIDER_PARAM ] ) );

			if ( self::set_provider( $new_provider ) ) {
				add_action(
					'admin_notices',
					function () use ( $new_provider ) {
						echo '<div class="notice notice-success is-dismissible"><p>';
						printf(
							/* translators: %s: provider name */
							esc_html__( 'Licensing provider switched to %s successfully.', 'melapress-login-security' ),
							esc_html( ucfirst( $new_provider ) )
						);
						echo '</p></div>';
					}
				);
			}
		}

		/**
		 * Proxy method: Check if the license is active and valid.
		 *
		 * @return bool True if license is active and valid, false otherwise.
		 * @since 3.2.0
		 */
		public static function has_active_valid_license(): bool {
			$provider = self::get_provider();
			return $provider ? $provider::has_active_valid_license() : false;
		}

		/**
		 * Proxy method: Check if the premium version is active.
		 *
		 * @return bool True if premium is active, false otherwise.
		 * @since 3.2.0
		 */
		public static function is_premium(): bool {
			$provider = self::get_provider();
			return $provider ? $provider::is_premium() : false;
		}

		/**
		 * Proxy method: Check if the plugin is registered.
		 *
		 * @return bool True if registered, false otherwise.
		 * @since 3.2.0
		 */
		public static function is_registered(): bool {
			$provider = self::get_provider();
			return $provider ? $provider::is_registered() : false;
		}

		/**
		 * Proxy method: Get the license object/data.
		 *
		 * @return mixed License object or data structure, null if not available.
		 * @since 3.2.0
		 */
		public static function get_license() {
			$provider = self::get_provider();
			return $provider ? $provider::get_license() : null;
		}

		/**
		 * Proxy method: Get the license quota.
		 *
		 * @return int Number of allowed users/sites, -1 if unlimited or unavailable.
		 * @since 3.2.0
		 */
		public static function get_license_quota(): int {
			$provider = self::get_provider();
			return $provider ? $provider::get_license_quota() : -1;
		}

		/**
		 * Proxy method: Check if license quota has been exceeded.
		 *
		 * @return bool True if quota exceeded, false otherwise.
		 * @since 3.2.0
		 */
		public static function is_quota_exceeded(): bool {
			$provider = self::get_provider();
			return $provider ? $provider::is_quota_exceeded() : false;
		}

		/**
		 * Proxy method: Get the pricing page URL.
		 *
		 * @return string Pricing page URL.
		 * @since 3.2.0
		 */
		public static function get_pricing_url(): string {
			$provider = self::get_provider();
			return $provider ? $provider::get_pricing_url() : self::FALLBACK_PRICING_URL;
		}

		/**
		 * Proxy method: Get the account/dashboard URL.
		 *
		 * @return string Account/dashboard URL.
		 * @since 3.2.0
		 */
		public static function get_account_url(): string {
			$provider = self::get_provider();
			return $provider ? $provider::get_account_url() : self::FALLBACK_ACCOUNT_URL;
		}

		/**
		 * Proxy method: Sync/refresh the license status.
		 *
		 * @return bool True on success, false on failure.
		 * @since 3.2.0
		 */
		public static function sync_license(): bool {
			$provider = self::get_provider();
			return $provider ? $provider::sync_license() : false;
		}

		/**
		 * Proxy method: Activate a license key.
		 *
		 * @param string $license_key The license key to activate.
		 * @return bool|array True on success, array with error info on failure.
		 * @since 3.2.0
		 */
		public static function activate_license( string $license_key ) {
			$provider = self::get_provider();
			return $provider ? $provider::activate_license( $license_key ) : false;
		}

		/**
		 * Proxy method: Deactivate the current license.
		 *
		 * @return bool True on success, false on failure.
		 * @since 3.2.0
		 */
		public static function deactivate_license(): bool {
			$provider = self::get_provider();
			return $provider ? $provider::deactivate_license() : false;
		}

		/**
		 * Proxy method: Get the plugin basename.
		 *
		 * @return string Plugin basename.
		 * @since 3.2.0
		 */
		public static function get_plugin_basename(): string {
			$provider = self::get_provider();
			return $provider ? $provider::get_plugin_basename() : plugin_basename( self::PLUGIN_FILE );
		}

		/**
		 * Proxy method: Add an action hook.
		 *
		 * @param string   $tag      The action hook name.
		 * @param callable $callback The callback function.
		 * @param int      $priority Priority.
		 * @param int      $args     Number of arguments.
		 * @return void
		 * @since 3.2.0
		 */
		public static function add_action( string $tag, callable $callback, int $priority = 10, int $args = 1 ) {
			$provider = self::get_provider();
			if ( $provider ) {
				$provider::add_action( $tag, $callback, $priority, $args );
			}
		}

		/**
		 * Proxy method: Add a filter hook.
		 *
		 * @param string   $tag      The filter hook name.
		 * @param callable $callback The callback function.
		 * @param int      $priority Priority.
		 * @param int      $args     Number of arguments.
		 * @return void
		 * @since 3.2.0
		 */
		public static function add_filter( string $tag, callable $callback, int $priority = 10, int $args = 1 ) {
			$provider = self::get_provider();
			if ( $provider ) {
				$provider::add_filter( $tag, $callback, $priority, $args );
			}
		}

		/**
		 * Call a method on the currently selected provider if it exists.
		 *
		 * @param string $method Method name to call on the provider.
		 * @param mixed  ...$args Optional arguments to pass to the provider method.
		 * @return mixed|null Result of the provider method call, or null if not callable.
		 * @since 3.2.0
		 */
		public static function provider_call( string $method, ...$args ) {
			$provider = self::get_provider();
			if ( ! $provider ) {
				return null;
			}

			if ( method_exists( $provider, $method ) && is_callable( array( $provider, $method ) ) ) {
				return forward_static_call_array( array( $provider, $method ), $args );
			} elseif ( $provider::get_provider_instance() && method_exists( $provider::get_provider_instance(), $method ) ) {
				return call_user_func_array( array( $provider::get_provider_instance(), $method ), $args );
			}

			return null;
		}

		/**
		 * Get information about available providers.
		 *
		 * @return array Array of provider information.
		 * @since 3.2.0
		 */
		public static function get_available_providers(): array {
			$providers = array();

			if ( Freemius_Provider::is_available() ) {
				$providers['freemius'] = array(
					'name'      => 'Freemius',
					'available' => true,
					'active'    => 'freemius' === self::get_provider_type(),
				);
			}

			if ( EDD_Provider::is_available() ) {
				$providers['edd'] = array(
					'name'      => 'Easy Digital Downloads',
					'available' => true,
					'active'    => 'edd' === self::get_provider_type(),
				);
			}

			return $providers;
		}

		/*
		|----------------------------------------------------------------------
		| Unified License Page.
		|
		| Provides a single license activation interface that accepts both
		| Freemius (sk_ prefix) and EDD license keys. The key type is
		| detected server-side and routed to the appropriate provider.
		|----------------------------------------------------------------------
		*/

		/**
		 * Register the unified license page as a top-level menu item.
		 *
		 * When no valid license exists, this becomes the only accessible page.
		 *
		 * @return void
		 * @since 3.3.0
		 */
		public static function register_license_page() {
			global $_registered_pages, $_parent_pages, $admin_page_hooks;

			$menu_slug = self::MENU_SLUG;

			// Preserve the admin_page_hooks value before removing/re-adding the menu.
			// Freemius registers submenu pages (e.g. mls-policies-account) using a
			// hookname derived from admin_page_hooks[menu_slug]. If we change this
			// value by re-adding the menu with a different title, WordPress can no
			// longer resolve the account page's hookname, breaking access checks.
			$saved_page_hook = isset( $admin_page_hooks[ $menu_slug ] ) ? $admin_page_hooks[ $menu_slug ] : null;

			// Remove any previously registered page at this slug (from Freemius SDK or Admin class).
			\remove_menu_page( $menu_slug );

			// Also unregister any page callbacks that were hooked to this slug's hookname.
			$hookname = get_plugin_page_hookname( $menu_slug, '' );
			if ( ! empty( $hookname ) ) {
				\remove_all_actions( $hookname );
			}
			unset( $_registered_pages[ $hookname ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			unset( $_parent_pages[ $menu_slug ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			$hook = \add_menu_page(
				\__( self::MENU_TITLE, 'melapress-login-security' ),
				\__( self::MENU_TITLE, 'melapress-login-security' ),
				'manage_options',
				$menu_slug,
				array( __CLASS__, 'render_license_page' ),
				' ',
				99
			);

			// Restore the original admin_page_hooks value so Freemius submenu pages
			// (like the account page) retain their correct hookname for access checks.
			if ( null !== $saved_page_hook ) {
				$admin_page_hooks[ $menu_slug ] = $saved_page_hook; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}

			// Add menu icon styles if a callback is configured.
			if ( ! empty( self::MENU_ICON_CLASS ) && method_exists( self::MENU_ICON_CLASS, self::MENU_ICON_METHOD ) ) {
				\add_action( 'admin_head', array( self::MENU_ICON_CLASS, self::MENU_ICON_METHOD ) );
			}

			\add_action( "load-{$hook}", array( __CLASS__, 'enqueue_license_scripts' ) );

			// Remove any submenus registered by other modules.
			$menu_hook = \is_multisite() ? 'network_admin_menu' : 'admin_menu';
			\add_action( $menu_hook, array( __CLASS__, 'remove_all_submenus' ), 999 );
		}

		/**
		 * Register the license page as a submenu (when license is active).
		 *
		 * @return void
		 * @since 2.4.0
		 */
		public static function register_license_submenu() {
			$menu_slug = self::MENU_SLUG;

			$hook = \add_submenu_page(
				$menu_slug,
				\__( 'Manage License', 'melapress-login-security' ),
				\__( 'Manage License', 'melapress-login-security' ),
				'manage_options',
				self::UNIFIED_LICENSE_PAGE_SLUG,
				array( __CLASS__, 'render_license_page' ),
				100
			);

			\add_action( "load-{$hook}", array( __CLASS__, 'enqueue_license_scripts' ) );
		}

		/**
		 * Reorder the submenu so the license/account item is always last.
		 *
		 * Handles both our own "Manage License" page (EDD) and the
		 * Freemius SDK "Account" page by moving the matching entry
		 * to the end of the submenu array.
		 *
		 * @return void
		 * @since 2.4.0
		 */
		public static function reorder_license_submenu_last() {
			global $submenu;

			$menu_slug = self::MENU_SLUG;

			if ( empty( $submenu[ $menu_slug ] ) ) {
				return;
			}

			// Slugs that identify the license/account submenu entry.
			$license_slugs = array(
				self::UNIFIED_LICENSE_PAGE_SLUG,        // EDD "Manage License".
				$menu_slug . '-account',               // Freemius "Account".
			);

			$license_index = null;

			foreach ( $submenu[ $menu_slug ] as $index => $item ) {
				if ( isset( $item[2] ) && \in_array( $item[2], $license_slugs, true ) ) {
					$license_index = $index;
					break;
				}
			}

			if ( null === $license_index ) {
				return;
			}

			// Remove the entry and re-append it at the end.
			$license_item = $submenu[ $menu_slug ][ $license_index ];
			unset( $submenu[ $menu_slug ][ $license_index ] );
			$submenu[ $menu_slug ][] = $license_item;
		}

		/**
		 * Remove all submenus under the plugin's top-level menu.
		 *
		 * Ensures only the license activation page is accessible
		 * when no valid license exists. Also removes any Freemius-registered
		 * menu pages that would conflict with the unified license page.
		 *
		 * @return void
		 * @since 3.3.0
		 */
		public static function remove_all_submenus() {
			global $submenu;

			$menu_slug = self::MENU_SLUG;

			if ( isset( $submenu[ $menu_slug ] ) ) {
				// Preserve the Freemius account page if user has an active Freemius
				// connection. Removing it breaks WordPress's admin page access check
				// (get_admin_page_parent cannot resolve the parent), which prevents
				// the disconnect form from being processed.
				$account_slug = $menu_slug . '-account';
				$preserved    = array();

				if ( 'freemius' === self::get_provider_type() || \get_option( Freemius_Provider::FS_WP2FAP_OPTION, '' ) === 'yes' ) {
					foreach ( $submenu[ $menu_slug ] as $key => $item ) {
						if ( isset( $item[2] ) && $account_slug === $item[2] ) {
							$preserved[ $key ] = $item;
						}
					}
				}

				$submenu[ $menu_slug ] = $preserved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		/**
		 * Enqueue scripts for the unified license page.
		 *
		 * @return void
		 * @since 3.3.0
		 */
		public static function enqueue_license_scripts() {
			$plugin_url     = self::PLUGIN_URL;
			$plugin_version = self::PLUGIN_VERSION;
			$menu_slug      = self::MENU_SLUG;

			\wp_enqueue_style(
				'mls-licensing-form',
				$plugin_url . 'app/Licensing/licensing-form.css',
				array(),
				$plugin_version
			);

			\wp_enqueue_script(
				self::UNIFIED_SCRIPT_HANDLE,
				$plugin_url . 'app/Licensing/unified-licensing.js',
				array(),
				$plugin_version,
				true
			);

			\wp_localize_script(
				self::UNIFIED_SCRIPT_HANDLE,
				'unifiedLicense',
				array(
					'ajaxUrl'     => \admin_url( 'admin-ajax.php' ),
					'nonce'       => \wp_create_nonce( self::UNIFIED_NONCE_ACTION ),
					'redirectUrl' => \network_admin_url( 'admin.php?page=' . $menu_slug ),
					'isMultisite' => \is_multisite(),
					'prefix'      => self::SLUG_PREFIX,
					'actions'     => array(
						'activate'   => self::UNIFIED_AJAX_ACTIVATE,
						'deactivate' => self::UNIFIED_AJAX_DEACTIVATE,
						'sync'       => self::UNIFIED_AJAX_SYNC,
						'change'     => self::UNIFIED_AJAX_CHANGE,
					),
					'i18n'        => array(
						'activatingText'     => \esc_html__( 'Activating...', 'melapress-login-security' ),
						'deactivatingText'   => \esc_html__( 'Deactivating...', 'melapress-login-security' ),
						'syncingText'        => \esc_html__( 'Syncing...', 'melapress-login-security' ),
						'enterLicenseKey'    => \esc_html__( 'Please enter a license key.', 'melapress-login-security' ),
						'activateBtn'        => \esc_html__( 'Activate', 'melapress-login-security' ),
						'deactivateBtn'      => \esc_html__( 'Deactivate', 'melapress-login-security' ),
						'syncBtn'            => \esc_html__( 'Sync License', 'melapress-login-security' ),
						'activateNewBtn'     => \esc_html__( 'Activate New', 'melapress-login-security' ),
						'cancelBtn'          => \esc_html__( 'Cancel', 'melapress-login-security' ),
						'changingText'       => \esc_html__( 'Changing...', 'melapress-login-security' ),
						'changedSuccess'     => \esc_html__( 'License changed successfully.', 'melapress-login-security' ),
						'changeFailed'       => \esc_html__( 'License change failed.', 'melapress-login-security' ),
						'networkError'       => \esc_html__( 'A network error occurred. Please try again.', 'melapress-login-security' ),
						'activatedSuccess'   => \esc_html__( 'License activated successfully.', 'melapress-login-security' ),
						'activationFailed'   => \esc_html__( 'Activation failed.', 'melapress-login-security' ),
						'deactivatedSuccess' => \esc_html__( 'License deactivated successfully.', 'melapress-login-security' ),
						'deactivationFailed' => \esc_html__( 'Deactivation failed.', 'melapress-login-security' ),
						'syncedSuccess'      => \esc_html__( 'License synced successfully.', 'melapress-login-security' ),
						'syncFailed'         => \esc_html__( 'Sync failed.', 'melapress-login-security' ),
					),
				)
			);
		}

		/**
		 * Render the unified license page.
		 *
		 * Shows the same interface regardless of the licensing provider.
		 * Users can enter either Freemius (sk_) or EDD license keys.
		 *
		 * @return void
		 * @since 3.3.0
		 */
		public static function render_license_page() {
			if ( ! \current_user_can( 'manage_options' ) ) {
				return;
			}

			$is_active     = self::has_active_valid_license();
			$provider_type = self::get_provider_type();
			$license_key   = '';
			$status        = '';
			$item_name     = '';
			$expires       = '';

			// Get license details from the active provider.
			if ( 'edd' === $provider_type ) {
				$license_key  = \get_option( EDD_Provider::LICENSE_KEY_OPTION, '' );
				$status       = \get_option( EDD_Provider::LICENSE_STATUS_OPTION, '' );
				$license_data = \get_option( EDD_Provider::LICENSE_DATA_OPTION, array() );

				if ( is_array( $license_data ) ) {
					$item_name = isset( $license_data['item_name'] ) ? $license_data['item_name'] : '';
					$expires   = isset( $license_data['expires'] ) ? $license_data['expires'] : '';
				}
			} elseif ( 'freemius' === $provider_type ) {
				$fs = Freemius_Provider::get_provider_instance();
				if ( $fs && $fs->is_registered() ) {
					$license = $fs->_get_license();
					if ( is_object( $license ) ) {
						$license_key = $license->secret_key ?? '';
						$expires     = $license->expiration ?? '';
					}
				}
			}

			// Format expiration date.
			$expiry_display = '';
			$id_prefix      = self::SLUG_PREFIX;

			if ( ! empty( $expires ) && 'lifetime' !== $expires ) {
				$expiry_display = \wp_date( \get_option( 'date_format' ), strtotime( $expires ) );
			} elseif ( 'lifetime' === $expires ) {
				$expiry_display = \__( 'Lifetime', 'melapress-login-security' );
			}

			?>
			<div class="mls-license-wrap">
				<h2><?php \esc_html_e( 'Activate your license key', 'melapress-login-security' ); ?></h2>

				<div class="mls-license-card">
					<img src="<?php echo \esc_url( self::PLUGIN_URL . 'assets/images/password-policy-manager.png' ); ?>"
						alt="<?php echo \esc_attr( self::PLUGIN_NAME ); ?>"
						class="mls-license-logo" />

					<div id="<?php echo \esc_attr( $id_prefix ); ?>-license-message" class="mls-license-notice"></div>

					<div id="<?php echo \esc_attr( $id_prefix ); ?>-license-progress" class="mls-license-progress">
						<p><span id="<?php echo \esc_attr( $id_prefix ); ?>-license-progress-text"></span></p>
						<progress id="<?php echo \esc_attr( $id_prefix ); ?>-license-progress-bar" max="100" value="0"></progress>
					</div>

					<?php if ( ! $is_active ) : ?>
						<p class="mls-license-card-title">
							<?php
							printf(
								/* translators: %s: plugin name */
								\esc_html__( 'To get started with %s, please enter your license key below:', 'melapress-login-security' ),
								'<strong>' . \esc_html( self::PLUGIN_NAME ) . '</strong>'
							);
							?>
						</p>

						<div class="mls-license-input-row">
							<input type="text"
								id="<?php echo \esc_attr( $id_prefix ); ?>-license-key"
								name="license_key"
								placeholder="<?php \esc_attr_e( 'Paste your license key', 'melapress-login-security' ); ?>"
								value=""
								autocomplete="off" />
							<button type="button" id="<?php echo \esc_attr( $id_prefix ); ?>-license-activate" class="mls-license-btn">
								<?php \esc_html_e( 'Activate License', 'melapress-login-security' ); ?>
							</button>
						</div>

						<p class="mls-license-help">
							<?php
							printf(
								/* translators: %s: contact link */
								\esc_html__( "Can't find your license key? %s so we can assist you.", 'melapress-login-security' ),
								'<a href="mailto:support@melapress.com">' . \esc_html__( 'Contact us', 'melapress-login-security' ) . '</a>'
							);
							?>
						</p>
					<?php else : ?>
						<p class="mls-license-card-title">
							<?php
							printf(
								/* translators: %s: plugin name */
								\esc_html__( 'Your %s license is active.', 'melapress-login-security' ),
								'<strong>' . \esc_html( self::PLUGIN_NAME ) . '</strong>'
							);
							?>
						</p>

						<div class="mls-license-input-row">
							<input type="password"
								id="<?php echo \esc_attr( $id_prefix ); ?>-license-key"
								name="license_key"
								value="<?php echo \esc_attr( $license_key ); ?>"
								readonly />
							<button type="button" id="<?php echo \esc_attr( $id_prefix ); ?>-license-deactivate" class="mls-license-btn">
								<?php \esc_html_e( 'Deactivate', 'melapress-login-security' ); ?>
							</button>
						</div>

						<div class="mls-license-actions">
							<button type="button" id="<?php echo \esc_attr( $id_prefix ); ?>-license-sync" class="mls-license-btn-secondary">
								<?php \esc_html_e( 'Sync License', 'melapress-login-security' ); ?>
							</button>
							<?php if ( 'edd' === $provider_type ) : ?>
								<button type="button" id="<?php echo \esc_attr( $id_prefix ); ?>-license-change" class="mls-license-btn-secondary">
									<?php \esc_html_e( 'Change License', 'melapress-login-security' ); ?>
								</button>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<hr class="mls-license-divider" />

					<div class="mls-license-status-row">
						<span class="mls-license-status-label"><?php \esc_html_e( 'Status', 'melapress-login-security' ); ?></span>
						<span id="<?php echo \esc_attr( $id_prefix ); ?>-license-status" class="mls-license-badge mls-license-badge--<?php echo \esc_attr( $is_active ? 'active' : ( 'expired' === $status ? 'expired' : 'inactive' ) ); ?>">
							<?php
							if ( $is_active ) {
								\esc_html_e( 'Active', 'melapress-login-security' );
							} elseif ( 'expired' === $status ) {
								\esc_html_e( 'Expired', 'melapress-login-security' );
							} else {
								\esc_html_e( 'Not activated', 'melapress-login-security' );
							}
							?>
						</span>
					</div>

					<?php if ( $is_active ) : ?>
						<div class="mls-license-details">
							<dl class="mls-license-details-table">
								<?php if ( ! empty( $item_name ) ) : ?>
									<div class="mls-license-detail-row">
										<dt><?php \esc_html_e( 'Plan', 'melapress-login-security' ); ?></dt>
										<dd><?php echo \esc_html( $item_name ); ?></dd>
									</div>
								<?php endif; ?>
								<?php if ( ! empty( $expiry_display ) ) : ?>
									<div class="mls-license-detail-row">
										<dt><?php \esc_html_e( 'Expires', 'melapress-login-security' ); ?></dt>
										<dd><?php echo \esc_html( $expiry_display ); ?></dd>
									</div>
								<?php endif; ?>
							</dl>
						</div>
					<?php endif; ?>
				</div>

				<p class="mls-license-footer">
					<?php
					printf(
						/* translators: 1: plugin name, 2: bold server name */
						\esc_html__( 'For license management, and to deliver security & feature updates, %1$s connects to the %2$s.', 'melapress-login-security' ),
						\esc_html( self::PLUGIN_NAME ),
						'<strong>' . \esc_html__( 'Melapress licensing servers', 'melapress-login-security' ) . '</strong>'
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Detect the license key type based on prefix.
		 *
		 * @param string $license_key The license key to check.
		 * @return string 'freemius' if key starts with sk_, 'edd' otherwise.
		 * @since 3.3.0
		 */
		public static function detect_key_type( string $license_key ): string {
			if ( strpos( $license_key, 'sk_' ) === 0 ) {
				return 'freemius';
			}

			return 'edd';
		}

		/**
		 * Unified AJAX handler for license activation.
		 *
		 * Detects key type (Freemius sk_ prefix vs EDD) and routes
		 * to the appropriate provider for activation.
		 *
		 * @return void
		 * @since 3.3.0
		 */
		public static function ajax_activate_license() {
			\check_ajax_referer( self::UNIFIED_NONCE_ACTION, 'nonce' );

			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permission denied.', 'melapress-login-security' ) ) );
			}

			$license_key = isset( $_POST['license_key'] ) ? \sanitize_text_field( \wp_unslash( $_POST['license_key'] ) ) : '';

			if ( empty( $license_key ) ) {
				\wp_send_json_error( array( 'message' => \__( 'License key is required.', 'melapress-login-security' ) ) );
			}

			$key_type = self::detect_key_type( $license_key );

			// Verify the target provider is available.
			if ( 'freemius' === $key_type && ! Freemius_Provider::is_available() ) {
				\wp_send_json_error( array( 'message' => \__( 'Freemius licensing is not available in this build.', 'melapress-login-security' ) ) );
			}

			if ( 'edd' === $key_type && ! EDD_Provider::is_available() ) {
				\wp_send_json_error( array( 'message' => \__( 'EDD licensing is not available in this build.', 'melapress-login-security' ) ) );
			}

			// Set the provider preference based on the key type.
			self::set_provider( $key_type );

			// Route activation to the detected provider.
			if ( 'freemius' === $key_type ) {
				$result = Freemius_Provider::activate_license( $license_key );
			} else {
				$result = EDD_Provider::activate_license( $license_key );
			}

			if ( true === $result ) {
				\wp_send_json_success( array( 'message' => \__( 'License activated successfully.', 'melapress-login-security' ) ) );
			} else {
				$error_message = is_array( $result ) && isset( $result['message'] )
					? $result['message']
					: \__( 'License activation failed.', 'melapress-login-security' );
				\wp_send_json_error( array( 'message' => $error_message ) );
			}
		}

		/**
		 * Unified AJAX handler for license deactivation.
		 *
		 * @return void
		 * @since 3.3.0
		 */
		public static function ajax_deactivate_license() {
			\check_ajax_referer( self::UNIFIED_NONCE_ACTION, 'nonce' );

			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permission denied.', 'melapress-login-security' ) ) );
			}

			$result = self::deactivate_license();

			if ( $result ) {
				// Clear provider preference on deactivation.
				\delete_option( self::PROVIDER_OPTION );
				\wp_send_json_success( array( 'message' => \__( 'License deactivated successfully.', 'melapress-login-security' ) ) );
			} else {
				\wp_send_json_error( array( 'message' => \__( 'Failed to deactivate license.', 'melapress-login-security' ) ) );
			}
		}

		/**
		 * Unified AJAX handler for license sync.
		 *
		 * @return void
		 * @since 3.3.0
		 */
		public static function ajax_sync_license() {
			\check_ajax_referer( self::UNIFIED_NONCE_ACTION, 'nonce' );

			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permission denied.', 'melapress-login-security' ) ) );
			}

			$result = self::sync_license();

			if ( $result ) {
				\wp_send_json_success( array( 'message' => \__( 'License synced successfully.', 'melapress-login-security' ) ) );
			} else {
				\wp_send_json_error( array( 'message' => \__( 'Failed to sync license data.', 'melapress-login-security' ) ) );
			}
		}

		/**
		 * Unified AJAX handler for changing the license key (EDD only).
		 *
		 * Activates the new license first, then deactivates the old one
		 * on the store. If the new activation fails, the old license
		 * remains untouched.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function ajax_change_license() {
			\check_ajax_referer( self::UNIFIED_NONCE_ACTION, 'nonce' );

			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permission denied.', 'melapress-login-security' ) ) );
			}

			if ( 'edd' !== self::get_provider_type() ) {
				\wp_send_json_error( array( 'message' => \__( 'License change is not supported for this provider.', 'melapress-login-security' ) ) );
			}

			$new_key = isset( $_POST['license_key'] ) ? \sanitize_text_field( \wp_unslash( $_POST['license_key'] ) ) : '';

			if ( empty( $new_key ) ) {
				\wp_send_json_error( array( 'message' => \__( 'License key is required.', 'melapress-login-security' ) ) );
			}

			// Capture old license details before activation overwrites them.
			$old_key     = \get_option( EDD_Provider::LICENSE_KEY_OPTION, '' );
			$old_data    = \get_option( EDD_Provider::LICENSE_DATA_OPTION, array() );
			$old_item_id = is_array( $old_data ) && isset( $old_data['item_id'] ) ? (int) $old_data['item_id'] : 0;
			$old_status     = \get_option( EDD_Provider::LICENSE_STATUS_OPTION, '' );
			$old_premium    = \get_option( EDD_Provider::PREMIUM_OPTION, '' );
			$old_last_valid = \get_option( EDD_Provider::LAST_VALID_OPTION, 0 );
			$old_sites       = array();
			$old_activations = array();

			if ( \is_multisite() ) {
				$old_activations = EDD_Network_Licensing::get_network_activation_status();
				$old_sites       = array_keys( $old_activations );
			}

			// Prevent deactivating the same key that was just activated.
			if ( $new_key === $old_key ) {
				\wp_send_json_error( array( 'message' => \__( 'The new license key is the same as the current one.', 'melapress-login-security' ) ) );
			}

			// Try activating the new license first.
			$result = EDD_Provider::activate_license( $new_key );

			if ( true !== $result ) {
				// Restore old license data — activate_license() may have overwritten it.
				\update_option( EDD_Provider::LICENSE_KEY_OPTION, $old_key );
				\update_option( EDD_Provider::LICENSE_DATA_OPTION, $old_data );
				\update_option( EDD_Provider::LICENSE_STATUS_OPTION, $old_status );
				\update_option( EDD_Provider::PREMIUM_OPTION, $old_premium );
				\update_option( EDD_Provider::LAST_VALID_OPTION, $old_last_valid );

				// Restore network activation data and clear stale transients.
				if ( \is_multisite() ) {
					\update_site_option( EDD_Network_Licensing::NETWORK_ACTIVATIONS_OPTION, $old_activations );
					\delete_site_transient( EDD_Network_Licensing::PROGRESS_TRANSIENT );
				}

				\delete_transient( EDD_Provider::LICENSE_CHECK_TRANSIENT );
				\delete_transient( EDD_Provider::PREMIUM_OPTION );

				if ( '' !== $old_premium ) {
					\set_transient( EDD_Provider::PREMIUM_OPTION, $old_premium, EDD_Provider::LICENSE_CHECK_INTERVAL );
				}

				$error_message = is_array( $result ) && isset( $result['message'] )
					? $result['message']
					: \__( 'License activation failed.', 'melapress-login-security' );
				\wp_send_json_error( array( 'message' => $error_message ) );
			}

			// New license activated — deactivate the old one on the store.
			$deactivation_failed = false;

			if ( ! empty( $old_key ) && $old_item_id > 0 ) {
				if ( \is_multisite() && ! empty( $old_sites ) ) {
					foreach ( $old_sites as $site_url ) {
						if ( ! EDD_Provider::try_deactivate_for_url( $old_key, $old_item_id, $site_url ) ) {
							$deactivation_failed = true;
						}
					}
				} else {
					if ( ! EDD_Provider::try_deactivate_for_url( $old_key, $old_item_id, \home_url() ) ) {
						$deactivation_failed = true;
					}
				}
			}

			if ( $deactivation_failed ) {
				\wp_send_json_success( array(
					'message' => \__( 'License changed successfully. Note: the previous license could not be fully deactivated. Please contact support if activation slots are not freed.', 'melapress-login-security' ),
				) );
			}

			\wp_send_json_success( array( 'message' => \__( 'License changed successfully.', 'melapress-login-security' ) ) );
		}

		/**
		 * Get the unified license page URL.
		 *
		 * @return string License page admin URL.
		 * @since 3.3.0
		 */
		public static function get_license_page_url(): string {
			if ( self::has_active_valid_license() ) {
				return \network_admin_url( 'admin.php?page=' . self::UNIFIED_LICENSE_PAGE_SLUG );
			}

			$menu_slug = self::MENU_SLUG;
			return \network_admin_url( 'admin.php?page=' . $menu_slug );
		}
	}
}
