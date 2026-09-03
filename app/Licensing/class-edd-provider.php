<?php
/**
 * Easy Digital Downloads (EDD) Licensing Provider.
 *
 * Implements licensing through Easy Digital Downloads Software Licensing extension.
 * This provider handles license activation, validation, and updates through the EDD API.
 *
 * Plugin-specific configuration lives in Licensing_Factory. This class only
 * contains EDD-specific implementation constants (store URL, product IDs,
 * option keys, etc.).
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

if ( ! class_exists( '\MLS\Licensing\EDD_Provider' ) ) {

	/**
	 * EDD licensing provider implementation.
	 *
	 * @since 2.0.0
	 */
	class EDD_Provider implements Licensing_Provider {

		/*
		|----------------------------------------------------------------------
		| EDD-specific configuration.
		|
		| Plugin-wide constants (TEXT_DOMAIN, PLUGIN_NAME, SLUG_PREFIX, etc.)
		| live in Licensing_Factory. Only EDD implementation details below.
		|----------------------------------------------------------------------
		*/

		/**
		 * EDD store URL.
		 *
		 * @var string
		 */
		public const STORE_URL = 'https://store.melapress.com/';

		/**
		 * EDD product ID for the Premium tier.
		 *
		 * @var int
		 */
		public const ITEM_ID_PREMIUM = 22;

		/**
		 * EDD product ID for the Enterprise tier.
		 *
		 * @var int
		 */
		public const ITEM_ID_ENTERPRISE = 116;

		/**
		 * Prefix for AJAX action names.
		 *
		 * @var string
		 */
		public const AJAX_PREFIX = 'mls_edd_';

		/**
		 * Nonce action name for license AJAX requests.
		 *
		 * @var string
		 */
		public const NONCE_ACTION = 'mls_edd_license';

		/**
		 * Script handle for the license JS.
		 *
		 * @var string
		 */
		public const SCRIPT_HANDLE = 'edd-licensing';

		/*
		|----------------------------------------------------------------------
		| Option / transient keys (change prefix when adapting for new plugin).
		|----------------------------------------------------------------------
		*/

		/**
		 * Option name for storing license key.
		 *
		 * @var string
		 */
		public const LICENSE_KEY_OPTION = 'mls_edd_license_key';

		/**
		 * Option name for storing license status.
		 *
		 * @var string
		 */
		public const LICENSE_STATUS_OPTION = 'mls_edd_license_status';

		/**
		 * Option name for storing license data.
		 *
		 * @var string
		 */
		public const LICENSE_DATA_OPTION = 'mls_edd_license_data';

		/**
		 * Transient name for caching license checks.
		 *
		 * @var string
		 */
		public const LICENSE_CHECK_TRANSIENT = 'mls_edd_license_check';

		/**
		 * Option name for premium status fast-path check.
		 *
		 * @var string
		 */
		public const PREMIUM_OPTION = 'mls_edd_premium';

		/**
		 * License check interval in seconds (48 hours).
		 * Controls how often the license status is re-validated with the store.
		 *
		 * @var int
		 */
		public const LICENSE_CHECK_INTERVAL = 2 * DAY_IN_SECONDS;

		/**
		 * Option holding the timestamp of the last conclusive "valid" answer
		 * from the store. Used to bound how long a previously valid license
		 * keeps working while the store cannot be reached or answers
		 * inconclusively.
		 *
		 * @var string
		 */
		public const LAST_VALID_OPTION = 'mls_edd_license_last_valid';

		/**
		 * How long a previously valid license keeps working when the store
		 * cannot be reached, or answers with something that may be about this
		 * request rather than about the license itself.
		 *
		 * @var int
		 */
		public const CHECK_GRACE_PERIOD = 7 * DAY_IN_SECONDS;

		/**
		 * How long to wait before retrying after an inconclusive check.
		 *
		 * Without this, a store that cannot be reached means a blocking remote
		 * POST on every single admin page load.
		 *
		 * @var int
		 */
		public const CHECK_RETRY_INTERVAL = HOUR_IN_SECONDS;

		/**
		 * Timeout for the background license check.
		 *
		 * Shorter than the activation timeout: nobody is waiting on this one,
		 * and an inconclusive result is handled gracefully.
		 *
		 * @var int
		 */
		public const CHECK_TIMEOUT = 5;

		/**
		 * Statuses that are a definite statement about the license itself.
		 *
		 * These are applied immediately. Every other non-valid status may be
		 * about this particular request (wrong URL sent by a proxy, product id
		 * missing from a truncated payload, store-side error) rather than about
		 * the license, so it goes through the grace window instead.
		 *
		 * @var string[]
		 */
		public const AUTHORITATIVE_NEGATIVE_STATUSES = array( 'expired', 'disabled', 'revoked' );

		public const CACHE_KEY_PREFIX = 'edd_sl_failed_http_';

		/**
		 * Cache for license data.
		 *
		 * @var array|null
		 * @since 2.4.0
		 */
		private static $license_data = null;

		/**
		 * Initialize the EDD licensing provider.
		 *
		 * @return void
		 * @since 2.4.0
		 */
		public static function init() {
			if ( ! self::is_available() ) {
				return;
			}

			// Hook into admin_init to check license status.
			\add_action( 'admin_init', array( __CLASS__, 'maybe_check_license' ) );

			// Hook for plugin updates.
			\add_action( 'admin_init', array( __CLASS__, 'setup_updater' ), 0 );

			// Override plugin homepage URL in "View details" modal.
			\add_filter( 'plugins_api', array( __CLASS__, 'override_plugin_homepage' ), 20, 3 );

			// Admin notices — use network_admin_notices on multisite.
			\add_action( 'admin_notices', array( __CLASS__, 'license_notices' ) );
			if ( \is_multisite() ) {
				\add_action( 'network_admin_notices', array( __CLASS__, 'license_notices' ) );
			}

			// AJAX handler for license activation/deactivation/sync.
			\add_action( 'wp_ajax_' . self::AJAX_PREFIX . 'activate_license', array( __CLASS__, 'ajax_activate_license' ) );
			\add_action( 'wp_ajax_' . self::AJAX_PREFIX . 'deactivate_license', array( __CLASS__, 'ajax_deactivate_license' ) );
			\add_action( 'wp_ajax_' . self::AJAX_PREFIX . 'sync_license', array( __CLASS__, 'ajax_sync_license' ) );
			\add_action( 'wp_ajax_' . self::AJAX_PREFIX . 'get_activation_progress', array( __CLASS__, 'ajax_get_activation_progress' ) );

			// Initialize the license admin page.
			EDD_License_Page::init();

			// Initialize multisite lifecycle hooks.
			if ( \is_multisite() ) {
				EDD_Network_Licensing::init();
			}
		}

		/*
		|----------------------------------------------------------------------
		| Plugin runtime accessors (deprecated — prefer class constants).
		|
		| Kept for backward compatibility with code outside this directory.
		| Within the Licensing directory, use the class constants directly.
		|----------------------------------------------------------------------
		*/

		/**
		 * Get the main plugin file path.
		 *
		 * @deprecated Use Licensing_Factory::PLUGIN_FILE constant instead.
		 *
		 * @return string
		 * @since 2.4.0
		 */
		public static function get_plugin_file(): string {
			return Licensing_Factory::PLUGIN_FILE;
		}

		/**
		 * Get the plugin slug (directory name).
		 *
		 * @deprecated Use Licensing_Factory::PLUGIN_SLUG constant instead.
		 *
		 * @return string
		 * @since 2.4.0
		 */
		public static function get_plugin_slug(): string {
			return Licensing_Factory::PLUGIN_SLUG;
		}

		/**
		 * Get the plugin directory path.
		 *
		 * @deprecated Use Licensing_Factory::PLUGIN_PATH constant instead.
		 *
		 * @return string
		 * @since 2.4.0
		 */
		public static function get_plugin_path(): string {
			return Licensing_Factory::PLUGIN_PATH;
		}

		/**
		 * Get the plugin URL.
		 *
		 * @deprecated Use Licensing_Factory::PLUGIN_URL constant instead.
		 *
		 * @return string
		 * @since 2.4.0
		 */
		public static function get_plugin_url(): string {
			return Licensing_Factory::PLUGIN_URL;
		}

		/**
		 * Get the plugin version.
		 *
		 * @deprecated Use Licensing_Factory::PLUGIN_VERSION constant instead.
		 *
		 * @return string
		 * @since 2.4.0
		 */
		public static function get_plugin_version(): string {
			return Licensing_Factory::PLUGIN_VERSION;
		}

		/**
		 * Get the admin menu slug.
		 *
		 * @deprecated Use Licensing_Factory::MENU_SLUG constant instead.
		 *
		 * @return string
		 * @since 2.4.0
		 */
		public static function get_menu_slug(): string {
			return Licensing_Factory::MENU_SLUG;
		}

		/**
		 * Check if the license is active and valid.
		 *
		 * @return bool True if license is active and valid, false otherwise.
		 * @since 2.4.0
		 */
		public static function has_active_valid_license(): bool {
			$status = \get_option( self::LICENSE_STATUS_OPTION );
			return 'valid' === $status;
		}

		/**
		 * Check if the premium version is active.
		 *
		 * Resource-cautious function that reads a cached option only.
		 * No remote calls are made here. The option is updated by
		 * maybe_check_license() when the transient expires.
		 *
		 * @return bool True if premium is active, false otherwise.
		 * @since 2.4.0
		 */
		public static function is_premium(): bool {
			return 'yes' === \get_option( self::PREMIUM_OPTION );
		}

		/**
		 * Get the provider instance.
		 *
		 * @return null Always returns null for static provider.
		 * @since 2.4.0
		 */
		public static function get_provider_instance() {
			return null;
		}

		/**
		 * Check if the plugin is registered (has a license key).
		 *
		 * @return bool True if registered, false otherwise.
		 * @since 2.4.0
		 */
		public static function is_registered(): bool {
			$license_key = \get_option( self::LICENSE_KEY_OPTION );
			return ! empty( $license_key );
		}

		/**
		 * Get the license data.
		 *
		 * @return mixed License data array or null.
		 * @since 2.4.0
		 */
		public static function get_license() {
			if ( null !== self::$license_data ) {
				return self::$license_data;
			}

			self::$license_data = \get_option( self::LICENSE_DATA_OPTION );

			if ( ! is_array( self::$license_data ) ) {
				self::$license_data = array();
			}

			return self::$license_data;
		}

		/**
		 * Get the license quota.
		 *
		 * @return int Number of allowed activations/sites.
		 * @since 2.4.0
		 */
		public static function get_license_quota(): int {
			$license_data = self::get_license();

			if ( isset( $license_data['license_limit'] ) ) {
				return (int) $license_data['license_limit'];
			}

			return -1;
		}

		/**
		 * Check if license quota has been exceeded.
		 *
		 * @return bool True if quota exceeded, false otherwise.
		 * @since 2.4.0
		 */
		public static function is_quota_exceeded(): bool {
			$license_data = self::get_license();

			if ( ! isset( $license_data['activations_left'] ) ) {
				return false;
			}

			return (int) $license_data['activations_left'] <= 0;
		}

		/**
		 * Get the pricing page URL.
		 *
		 * @return string Pricing page URL.
		 * @since 2.4.0
		 */
		public static function get_pricing_url(): string {
			return Licensing_Factory::PRICING_URL;
		}

		/**
		 * Get the account/dashboard URL.
		 *
		 * @return string Account URL.
		 * @since 2.4.0
		 */
		public static function get_account_url(): string {
			return self::STORE_URL . 'my-account/';
		}

		/**
		 * Sync/refresh the license status.
		 *
		 * Routes to single-site or network sync based on multisite detection.
		 *
		 * @return bool True on success, false on failure.
		 *
		 * @since 2.4.0
		 */
		public static function sync_license(): bool {
			if ( \is_multisite() ) {
				return EDD_Network_Licensing::sync_network_license();
			}

			return self::sync_single_site_license();
		}

		/**
		 * Sync/refresh the license status for a single-site installation.
		 *
		 * @return bool True on success, false on failure.
		 *
		 * @since 2.4.0
		 */
		public static function sync_single_site_license(): bool {
			$license_key = \get_option( self::LICENSE_KEY_OPTION );

			if ( empty( $license_key ) ) {
				return false;
			}

			// Reset caches to force a fresh check.
			\delete_transient( self::LICENSE_CHECK_TRANSIENT );
			\delete_transient( self::PREMIUM_OPTION );
			self::$license_data = null;

			return self::check_license( $license_key );
		}

		/**
		 * Activate a license key.
		 *
		 * Routes to single-site or network activation based on multisite detection.
		 *
		 * @param string $license_key - The license key to activate.
		 *
		 * @return bool|array True on success, array with error info on failure.
		 *
		 * @since 2.4.0
		 */
		public static function activate_license( string $license_key ) {
			if ( \is_multisite() ) {
				return EDD_Network_Licensing::activate_network_license( $license_key );
			}

			return self::activate_single_site_license( $license_key );
		}

		/**
		 * Activate a license key for a single-site installation.
		 *
		 * Tries activation against Premium product first. If the key doesn't
		 * match (key_mismatch, item_name_mismatch, invalid_item_id), tries
		 * Enterprise. This is transparent to the user.
		 *
		 * @param string $license_key - The license key to activate.
		 *
		 * @return bool|array True on success, array with error info on failure.
		 *
		 * @since 2.4.0
		 */
		public static function activate_single_site_license( string $license_key ) {
			$item_ids        = array( self::ITEM_ID_PREMIUM, self::ITEM_ID_ENTERPRISE );
			$mismatch_errors = array( 'key_mismatch', 'item_name_mismatch', 'invalid_item_id', 'missing' );

			foreach ( $item_ids as $item_id ) {
				$result = self::try_activate_license( $license_key, $item_id );

				// If activation succeeded, fetch fresh license data and return.
				if ( true === $result ) {
					self::check_license( $license_key );
					return true;
				}

				// If the error is a product mismatch, try the next item_id.
				if ( is_array( $result ) && isset( $result['code'] ) && in_array( $result['code'], $mismatch_errors, true ) ) {
					continue;
				}

				// Any other error — return it to the user.
				return $result;
			}

			// All item_ids exhausted — return generic error.
			return array(
				'success' => false,
				'message' => \__( 'This license key is not valid for this product.', 'melapress-login-security' ),
				'code'    => 'item_name_mismatch',
			);
		}

		/**
		 * Try to activate a license key against a specific EDD product.
		 *
		 * @param string $license_key - The license key to activate.
		 * @param int    $item_id     - The EDD product ID to try.
		 * @param string $url         - The site URL to activate against. Defaults to home_url().
		 *
		 * @return bool|array True on success, array with error info on failure.
		 *
		 * @since 2.4.0
		 */
		public static function try_activate_license( string $license_key, int $item_id, string $url = '' ) {
			if ( empty( $url ) ) {
				$url = \home_url();
			}

			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license_key,
				'item_id'    => $item_id,
				'url'        => $url,
			);

			$response = \wp_remote_post(
				self::STORE_URL,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			if ( \is_wp_error( $response ) || 200 !== \wp_remote_retrieve_response_code( $response ) ) {
				return array(
					'success' => false,
					'message' => \is_wp_error( $response ) ? $response->get_error_message() : \__( 'An error occurred, please try again.', 'melapress-login-security' ),
				);
			}

			$license_data = json_decode( \wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $license_data ) ) {
				return array(
					'success' => false,
					'message' => \__( 'Invalid response from license server.', 'melapress-login-security' ),
				);
			}

			// Store license key and full response data.
			\update_option( self::LICENSE_KEY_OPTION, $license_key );
			\update_option( self::LICENSE_DATA_OPTION, $license_data );

			if ( isset( $license_data['license'] ) && 'valid' === $license_data['license'] ) {
				\update_option( self::LICENSE_STATUS_OPTION, 'valid' );
				\update_option( self::PREMIUM_OPTION, 'yes' );
				\update_option( self::LAST_VALID_OPTION, time() );
				\set_transient( self::PREMIUM_OPTION, 'yes', self::LICENSE_CHECK_INTERVAL );
				\delete_transient( self::LICENSE_CHECK_TRANSIENT );
				return true;
			}

			// Activation did not return valid — store the status and mark as not premium.
			$status = isset( $license_data['license'] ) ? $license_data['license'] : 'invalid';
			\update_option( self::LICENSE_STATUS_OPTION, $status );
			\update_option( self::PREMIUM_OPTION, 'no' );
			\set_transient( self::PREMIUM_OPTION, 'no', self::LICENSE_CHECK_INTERVAL );

			$error_code = isset( $license_data['error'] ) ? $license_data['error'] : 'activation_failed';

			return array(
				'success' => false,
				'message' => self::get_activation_error_message( $error_code ),
				'code'    => $error_code,
			);
		}

		/**
		 * Get a human-readable error message for an EDD activation error code.
		 *
		 * @param string $error_code - The EDD error code.
		 *
		 * @return string - Human-readable error message.
		 *
		 * @since 2.4.0
		 */
		private static function get_activation_error_message( string $error_code ): string {
			switch ( $error_code ) {
				case 'expired':
					return \__( 'Your license key has expired. Please renew your license.', 'melapress-login-security' );

				case 'disabled':
					return \__( 'Your license key has been disabled. Please contact support.', 'melapress-login-security' );

				case 'missing':
					return \__( 'The license key you entered is invalid.', 'melapress-login-security' );

				case 'invalid':
				case 'site_inactive':
					return \__( 'Your license key is not active for this site.', 'melapress-login-security' );

				case 'no_activations_left':
					return \__( 'Your license key has reached its activation limit. Please upgrade your license or deactivate it on another site.', 'melapress-login-security' );

				case 'item_name_mismatch':
				case 'invalid_item_id':
					return \__( 'This license key is not valid for this product.', 'melapress-login-security' );

				case 'key_mismatch':
					return \__( 'The license key does not match the expected product.', 'melapress-login-security' );

				default:
					return \__( 'License activation failed. Please check your license key and try again.', 'melapress-login-security' );
			}
		}

		/**
		 * Deactivate the current license.
		 *
		 * Routes to single-site or network deactivation based on multisite detection.
		 *
		 * @return bool True on success, false on failure.
		 *
		 * @since 2.4.0
		 */
		public static function deactivate_license(): bool {
			if ( \is_multisite() ) {
				return EDD_Network_Licensing::deactivate_network_license();
			}

			return self::deactivate_single_site_license();
		}

		/**
		 * Deactivate the current license for a single-site installation.
		 *
		 * @return bool True on success, false on failure.
		 *
		 * @since 2.4.0
		 */
		public static function deactivate_single_site_license(): bool {
			$license_key = \get_option( self::LICENSE_KEY_OPTION );

			if ( empty( $license_key ) ) {
				return false;
			}

			$license_data = \get_option( self::LICENSE_DATA_OPTION, array() );
			$item_id      = is_array( $license_data ) && isset( $license_data['item_id'] ) ? (int) $license_data['item_id'] : 0;

			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license_key,
				'url'        => \home_url(),
			);

			if ( $item_id > 0 ) {
				$api_params['item_id'] = $item_id;
			}

			$response = \wp_remote_post(
				self::STORE_URL,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			/**
			 * Even if the remote call fails, clean up local data.
			 * The user explicitly chose to deactivate — don't leave
			 * stale premium state on their site.
			 */
			if ( \is_wp_error( $response ) || 200 !== \wp_remote_retrieve_response_code( $response ) ) {
				self::clear_local_license_data();
				return false;
			}

			$license_data = json_decode( \wp_remote_retrieve_body( $response ), true );

			if ( isset( $license_data['license'] ) && 'deactivated' === $license_data['license'] ) {
				self::clear_local_license_data();
				return true;
			}

			// If the server says it's already inactive/failed, still clean up locally.
			self::clear_local_license_data();

			return false;
		}

		/**
		 * Clear all local license data (options and transients).
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function clear_local_license_data() {
			\delete_option( self::LICENSE_STATUS_OPTION );
			\delete_option( self::LICENSE_KEY_OPTION );
			\delete_option( self::LICENSE_DATA_OPTION );
			\delete_option( self::PREMIUM_OPTION );
			\delete_option( self::LAST_VALID_OPTION );
			\delete_transient( self::LICENSE_CHECK_TRANSIENT );
			\delete_transient( self::PREMIUM_OPTION );

			// Reset in-memory cache.
			self::$license_data = null;
		}

		/**
		 * Check if the user can use premium code.
		 *
		 * Equivalent to Freemius's can_use_premium_code() method.
		 * Returns true when the license is active and valid.
		 *
		 * @return bool True if premium code can be used, false otherwise.
		 *
		 * @since 2.4.0
		 */
		public static function can_use_premium_code(): bool {
			return self::has_active_valid_license();
		}

		/**
		 * Check if the user has a paying license.
		 *
		 * Equivalent to Freemius's is_paying() method.
		 * Returns true when the license is active and valid.
		 *
		 * @return bool True if the user is paying, false otherwise.
		 *
		 * @since 2.4.0
		 */
		public static function is_paying(): bool {
			return self::has_active_valid_license();
		}

		/**
		 * Check if the user is on a trial.
		 *
		 * Equivalent to Freemius's is_trial() method.
		 * Currently always returns false as EDD trials are not yet implemented.
		 *
		 * @return bool True if on trial, false otherwise.
		 *
		 * @since 2.4.0
		 */
		public static function is_trial(): bool {
			return false;
		}

		/**
		 * Get the current license plan.
		 *
		 * Returns an EDD_Plan instance with the normalized plan name
		 * derived from the EDD item_name. Returns null if no valid license.
		 *
		 * @return EDD_Plan|null - Plan instance or null.
		 *
		 * @since 2.4.0
		 */
		public static function get_plan() {
			if ( ! self::has_active_valid_license() ) {
				return null;
			}

			return EDD_Plan::get_plan_name();
		}

		/**
		 * Get the current plan name.
		 *
		 * @return string|null - Plan name or null.
		 *
		 * @since 2.4.0
		 */
		public static function get_plan_name() {
			return self::get_plan();
		}

		/**
		 * Get the provider name.
		 *
		 * @return string Provider name.
		 * @since 2.4.0
		 */
		public static function get_provider_name(): string {
			return 'edd';
		}

		/**
		 * Check if EDD provider is available.
		 *
		 * This checks if EDD licensing is configured (not if Freemius is available).
		 *
		 * @return bool True if EDD provider is available, false otherwise.
		 * @since 2.4.0
		 */
		public static function is_available(): bool {
			return \apply_filters( self::AJAX_PREFIX . 'provider_available', true );
		}

		/**
		 * Get the plugin basename.
		 *
		 * @return string Plugin basename.
		 * @since 2.4.0
		 */
		public static function get_plugin_basename(): string {
			return \plugin_basename( Licensing_Factory::PLUGIN_FILE );
		}

		/**
		 * Add an action hook (WordPress standard).
		 *
		 * @param string          $tag      The action hook name.
		 * @param callable|string $callback The callback function.
		 * @param int             $priority Priority.
		 * @param int             $args     Number of arguments.
		 * @return void
		 * @since 2.4.0
		 */
		public static function add_action( string $tag, $callback, int $priority = 10, int $args = 1 ) {
			\add_action( $tag, $callback, $priority, $args );
		}

		/**
		 * Add a filter hook (WordPress standard).
		 *
		 * @param string   $tag      The filter hook name.
		 * @param callable $callback The callback function.
		 * @param int      $priority Priority.
		 * @param int      $args     Number of arguments.
		 * @return void
		 * @since 2.4.0
		 */
		public static function add_filter( string $tag, callable $callback, int $priority = 10, int $args = 1 ) {
			\add_filter( $tag, $callback, $priority, $args );
		}

		/**
		 * Check license status with EDD API.
		 *
		 * @param string $license_key - The license key to check.
		 *
		 * @return bool True if valid, false otherwise.
		 *
		 * @since 2.4.0
		 */
		public static function check_license( string $license_key ): bool {
			$stored_data = \get_option( self::LICENSE_DATA_OPTION, array() );
			$item_id     = is_array( $stored_data ) && isset( $stored_data['item_id'] ) ? (int) $stored_data['item_id'] : 0;

			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => $license_key,
				'url'        => \home_url(),
			);

			if ( $item_id > 0 ) {
				$api_params['item_id'] = $item_id;
			}

			$response = \wp_remote_post(
				self::STORE_URL,
				array(
					'timeout'   => self::CHECK_TIMEOUT,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			// The store said nothing, so decide nothing.
			if ( \is_wp_error( $response ) ) {
				return self::handle_inconclusive_check();
			}

			// Anything other than a 200 is a web server, proxy or firewall
			// talking, not the licensing API. Never act on it.
			if ( 200 !== (int) \wp_remote_retrieve_response_code( $response ) ) {
				return self::handle_inconclusive_check();
			}

			$license_data = json_decode( \wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $license_data ) || ! isset( $license_data['license'] ) ) {
				return self::handle_inconclusive_check();
			}

			$status = (string) $license_data['license'];

			if ( 'valid' === $status ) {
				\update_option( self::LICENSE_DATA_OPTION, $license_data );
				\update_option( self::LAST_VALID_OPTION, time() );
				self::store_license_status( 'valid' );

				return true;
			}

			// A definite statement about the license itself — apply it now.
			if ( in_array( $status, self::AUTHORITATIVE_NEGATIVE_STATUSES, true ) ) {
				\update_option( self::LICENSE_DATA_OPTION, $license_data );
				self::store_license_status( $status );

				return false;
			}

			return self::handle_inconclusive_check( $status, $license_data );
		}

		/**
		 * Handle a license check that did not produce a trustworthy answer.
		 *
		 * A license that was valid keeps working for CHECK_GRACE_PERIOD so that
		 * a store outage, a proxy error page or a transient API failure cannot
		 * disconnect a paying customer. Either way a short retry transient is
		 * set, so an unreachable store does not mean a blocking remote request
		 * on every admin page load.
		 *
		 * @param string     $status       - Status reported by the store, if any.
		 * @param array|null $license_data - Payload from the store, if any.
		 *
		 * @return bool True while the license is still being honoured.
		 *
		 * @since 2.4.0
		 */
		private static function handle_inconclusive_check( string $status = '', $license_data = null ): bool {
			$was_valid = 'valid' === \get_option( self::LICENSE_STATUS_OPTION );

			if ( $was_valid && ! self::grace_period_expired() ) {
				// Keep the last known good answer and try again sooner. The
				// stored license data is left untouched: overwriting it with an
				// error payload would destroy the item_id the next check needs
				// and the item_name the plan tier is derived from.
				\set_transient( self::PREMIUM_OPTION, \get_option( self::PREMIUM_OPTION, 'yes' ), self::CHECK_RETRY_INTERVAL );

				return true;
			}

			if ( '' === $status ) {
				// Nothing usable came back. Leave the stored status alone rather
				// than inventing one, but stop hammering the store.
				\set_transient( self::PREMIUM_OPTION, \get_option( self::PREMIUM_OPTION, 'no' ), self::CHECK_RETRY_INTERVAL );

				return false;
			}

			if ( is_array( $license_data ) ) {
				\update_option( self::LICENSE_DATA_OPTION, $license_data );
			}

			self::store_license_status( $status );

			return false;
		}

		/**
		 * Whether the grace period for a previously valid license has run out.
		 *
		 * On a site that predates this bookkeeping the window starts at the
		 * first inconclusive check rather than counting as already expired.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		private static function grace_period_expired(): bool {
			$last_valid = (int) \get_option( self::LAST_VALID_OPTION, 0 );

			if ( $last_valid <= 0 ) {
				$last_valid = time();
				\update_option( self::LAST_VALID_OPTION, $last_valid );
			}

			return ( time() - $last_valid ) > self::CHECK_GRACE_PERIOD;
		}

		/**
		 * Persist a license status together with the premium fast-path flag.
		 *
		 * @param string $status - Status as reported by the store.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function store_license_status( string $status ) {
			$new_value = 'valid' === $status ? 'yes' : 'no';

			\update_option( self::LICENSE_STATUS_OPTION, $status );
			\set_transient( self::LICENSE_CHECK_TRANSIENT, $status, self::LICENSE_CHECK_INTERVAL );

			if ( $new_value !== \get_option( self::PREMIUM_OPTION ) ) {
				\update_option( self::PREMIUM_OPTION, $new_value );
			}

			\set_transient( self::PREMIUM_OPTION, $new_value, self::LICENSE_CHECK_INTERVAL );
		}

		/**
		 * Maybe check license status (runs on admin_init).
		 *
		 * @return void
		 * @since 2.4.0
		 */
		public static function maybe_check_license() {
			if ( \wp_doing_ajax() ) {
				return;
			}

			$license_key = \get_option( self::LICENSE_KEY_OPTION );

			if ( empty( $license_key ) ) {
				return;
			}

			// Use the premium transient as the 24h guard.
			$cached_premium = \get_transient( self::PREMIUM_OPTION );

			if ( false !== $cached_premium ) {
				return;
			}

			self::check_license( $license_key );
		}

		/**
		 * Setup the EDD updater.
		 *
		 * @return void
		 * @since 2.4.0
		 */
		public static function setup_updater() {
			// On multisite, license data is stored on the main site.
			if ( \is_multisite() ) {
				$main_site_id = \get_main_site_id();
				$license_key  = \get_blog_option( $main_site_id, self::LICENSE_KEY_OPTION );
				$license_data = \get_blog_option( $main_site_id, self::LICENSE_DATA_OPTION );
			} else {
				$license_key  = \get_option( self::LICENSE_KEY_OPTION );
				$license_data = \get_option( self::LICENSE_DATA_OPTION );
			}

			if ( empty( $license_key ) ) {
				return;
			}

			$item_id = isset( $license_data['item_id'] ) ? (int) $license_data['item_id'] : 0;

			if ( empty( $item_id ) ) {
				return;
			}

			EDD_Plugin_Updater::setup(
				self::STORE_URL,
				array(
					'license' => $license_key,
					'item_id' => $item_id,
					'author'      => 'Melapress',
					'beta'        => false,
					'text_domain' => Licensing_Factory::TEXT_DOMAIN,
				)
			);
		}

		/**
		 * Override the plugin homepage URL in the "View details" modal.
		 *
		 * EDD Software Licensing returns the store download permalink as the
		 * homepage. This filter replaces it with the marketing site URL.
		 *
		 * @param false|object|array $result - The result object or array.
		 * @param string             $action - The type of information being requested.
		 * @param object             $args - Plugin API arguments.
		 *
		 * @return false|object|array
		 *
		 * @since 2.4.0
		 */
		public static function override_plugin_homepage( $result, $action, $args ) {
			if ( 'plugin_information' !== $action ) {
				return $result;
			}

			if ( is_object( $result ) && isset( $result->homepage ) ) {
				$result->homepage = '';
			}

			return $result;
		}

		/**
		 * Display license admin notices.
		 *
		 * Shows dismissible notices for expired, invalid, activation limit,
		 * and missing license states.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function license_notices() {
			if ( ! \current_user_can( 'manage_options' ) ) {
				return;
			}

			$license_key  = \get_option( self::LICENSE_KEY_OPTION );
			$status       = \get_option( self::LICENSE_STATUS_OPTION );
			$license_data = \get_option( self::LICENSE_DATA_OPTION, array() );
			$error_code   = is_array( $license_data ) && isset( $license_data['error'] ) ? $license_data['error'] : '';
			$license_url  = EDD_License_Page::get_license_page_url();

			// No license key stored — prompt to activate.
			if ( empty( $license_key ) ) {
				echo '<div class="notice notice-info is-dismissible"><p>';
				printf(
					/* translators: 1: plugin name, 2: license page link */
					\esc_html__( '%1$s — please %2$s to receive updates and support.', 'melapress-login-security' ),
					\esc_html( Licensing_Factory::PLUGIN_NAME ),
					'<a href="' . \esc_url( $license_url ) . '">' . \esc_html__( 'activate your license', 'melapress-login-security' ) . '</a>'
				);
				echo '</p></div>';
				return;
			}

			// Expired license.
			if ( 'expired' === $status ) {
				echo '<div class="notice notice-error is-dismissible"><p>';
				printf(
					/* translators: 1: plugin name, 2: renew link */
					\esc_html__( 'Your %1$s license has expired. The plugin has been switched to free mode with limited functionality. Please %2$s to restore all premium features.', 'melapress-login-security' ),
					\esc_html( Licensing_Factory::PLUGIN_NAME ),
					'<a href="' . \esc_url( self::get_account_url() ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'renew your license', 'melapress-login-security' ) . '</a>'
				);
				echo '</p></div>';
				return;
			}

			// Activation limit reached.
			if ( 'no_activations_left' === $error_code ) {
				echo '<div class="notice notice-warning is-dismissible"><p>';
				printf(
					/* translators: 1: plugin name, 2: pricing page link */
					\esc_html__( 'Your %1$s license has reached its activation limit. Please %2$s or deactivate it on another site.', 'melapress-login-security' ),
					\esc_html( Licensing_Factory::PLUGIN_NAME ),
					'<a href="' . \esc_url( self::get_pricing_url() ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'upgrade your license', 'melapress-login-security' ) . '</a>'
				);
				echo '</p></div>';
				return;
			}

			// License is not activated for this site — previously this produced no
			// notice at all, leaving the user with a license prompt and no reason.
			if ( 'site_inactive' === $status || 'inactive' === $status ) {
				echo '<div class="notice notice-warning is-dismissible"><p>';
				printf(
					/* translators: 1: plugin name, 2: license page link */
					\esc_html__( 'Your %1$s license is not active for this site. Please %2$s to reactivate it.', 'melapress-login-security' ),
					\esc_html( Licensing_Factory::PLUGIN_NAME ),
					'<a href="' . \esc_url( $license_url ) . '">' . \esc_html__( 'visit the license page', 'melapress-login-security' ) . '</a>'
				);
				echo '</p></div>';
				return;
			}

			// Invalid license.
			if ( 'invalid' === $status || 'disabled' === $status ) {
				echo '<div class="notice notice-warning is-dismissible"><p>';
				printf(
					/* translators: 1: plugin name, 2: license page link */
					\esc_html__( 'Your %1$s license is invalid. Please %2$s or contact support.', 'melapress-login-security' ),
					\esc_html( Licensing_Factory::PLUGIN_NAME ),
					'<a href="' . \esc_url( $license_url ) . '">' . \esc_html__( 'check your license key', 'melapress-login-security' ) . '</a>'
				);
				echo '</p></div>';
			}

			// Multisite: new subsite could not be activated (no slots).
			if ( \is_multisite() && \get_site_option( EDD_Network_Licensing::ACTIVATION_FAILED_FLAG ) ) {
				echo '<div class="notice notice-warning"><p>';
				printf(
					/* translators: %s: pricing page link */
					\esc_html__( 'A new subsite could not be activated because your license has no remaining activations. Please %s or free a slot by deactivating another site.', 'melapress-login-security' ),
					'<a href="' . \esc_url( self::get_pricing_url() ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'upgrade your license', 'melapress-login-security' ) . '</a>'
				);
				echo '</p></div>';
			}
		}

		/**
		 * AJAX handler for license activation.
		 *
		 * @return void
		 * @since 2.4.0
		 */
		public static function ajax_activate_license() {
			\check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permission denied.', 'melapress-login-security' ) ) );
			}

			$license_key = isset( $_POST['license_key'] ) ? \sanitize_text_field( \wp_unslash( $_POST['license_key'] ) ) : '';

			if ( empty( $license_key ) ) {
				\wp_send_json_error( array( 'message' => \__( 'License key is required.', 'melapress-login-security' ) ) );
			}

			$result = self::activate_license( $license_key );

			if ( true === $result ) {
				\wp_send_json_success( array( 'message' => \__( 'License activated successfully.', 'melapress-login-security' ) ) );
			} else {
				\wp_send_json_error( $result );
			}
		}

		/**
		 * AJAX handler for license deactivation.
		 *
		 * @return void
		 * @since 2.4.0
		 */
		public static function ajax_deactivate_license() {
			\check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permission denied.', 'melapress-login-security' ) ) );
			}

			$result = self::deactivate_license();

			if ( $result ) {
				\wp_send_json_success( array( 'message' => \__( 'License deactivated successfully.', 'melapress-login-security' ) ) );
			} else {
				\wp_send_json_error( array( 'message' => \__( 'Failed to deactivate license.', 'melapress-login-security' ) ) );
			}
		}

		/**
		 * AJAX handler for license sync.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function ajax_sync_license() {
			\check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permission denied.', 'melapress-login-security' ) ) );
			}

			$result = self::sync_license();

			if ( $result ) {
				\wp_send_json_success( array( 'message' => \__( 'License data synced successfully.', 'melapress-login-security' ) ) );
			} else {
				\wp_send_json_error( array( 'message' => \__( 'Failed to sync license data.', 'melapress-login-security' ) ) );
			}
		}

		/**
		 * AJAX handler for getting activation progress.
		 *
		 * Used by the frontend to poll batch activation/deactivation status.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function ajax_get_activation_progress() {
			\check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Permission denied.', 'melapress-login-security' ) ) );
			}

			$progress = EDD_Network_Licensing::get_activation_progress();
			\wp_send_json_success( $progress );
		}

		/**
		 * Try to deactivate a license for a specific URL.
		 *
		 * Used by network licensing to deactivate individual subsites.
		 *
		 * @param string $license_key - The license key.
		 * @param int    $item_id     - The EDD product ID.
		 * @param string $url         - The site URL to deactivate.
		 *
		 * @return bool True on success, false on failure.
		 *
		 * @since 2.4.0
		 */
		public static function try_deactivate_for_url( string $license_key, int $item_id, string $url ): bool {
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license_key,
				'item_id'    => $item_id,
				'url'        => $url,
			);

			$response = \wp_remote_post(
				self::STORE_URL,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			if ( \is_wp_error( $response ) || 200 !== \wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			$license_data = json_decode( \wp_remote_retrieve_body( $response ), true );

			return isset( $license_data['license'] ) && 'deactivated' === $license_data['license'];
		}
	}
}
