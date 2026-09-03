<?php
/**
 * EDD Network Licensing.
 *
 * Handles multisite-specific licensing logic: activating/deactivating
 * licenses for all subsites in a network, tracking per-site activation
 * status, and managing the subsite lifecycle (new site / deleted site).
 *
 * Each subsite in the network consumes one activation slot from the
 * EDD license. The network admin manages the license from the network
 * admin dashboard, and all subsites are activated/deactivated as a batch.
 *
 * All plugin-specific values are sourced from Licensing_Factory constants
 * — no direct coupling to plugin globals.
 *
 * @since      2.4.0
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

if ( ! class_exists( '\MLS\Licensing\EDD_Network_Licensing' ) ) {

	/**
	 * Handles multisite network licensing operations.
	 *
	 * @since 2.4.0
	 */
	class EDD_Network_Licensing {

		/**
		 * Network option for storing per-site activation data.
		 *
		 * References the canonical constant in Licensing_Factory.
		 *
		 * @var string
		 */
		const NETWORK_ACTIVATIONS_OPTION = Licensing_Factory::NETWORK_ACTIVATIONS_OPTION;

		/**
		 * Transient name for tracking batch activation progress.
		 *
		 * References the canonical constant in Licensing_Factory.
		 *
		 * @var string
		 */
		const PROGRESS_TRANSIENT = Licensing_Factory::NETWORK_PROGRESS_TRANSIENT;

		/**
		 * Number of sites to process per batch.
		 *
		 * @var int
		 */
		const BATCH_SIZE = 5;

		/**
		 * Progress transient TTL in seconds (5 minutes).
		 *
		 * @var int
		 */
		const PROGRESS_TTL = 300;

		/**
		 * Network option flag for failed subsite activation (insufficient slots).
		 *
		 * References the canonical constant in Licensing_Factory.
		 *
		 * @var string
		 */
		const ACTIVATION_FAILED_FLAG = Licensing_Factory::NETWORK_ACTIVATION_FAILED_FLAG;

		/**
		 * Initialize the network licensing hooks.
		 *
		 * Registers hooks for the subsite lifecycle (creation/deletion)
		 * to automatically activate/deactivate licenses.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function init() {
			\add_action( 'wp_insert_site', array( __CLASS__, 'on_site_created' ) );
			\add_action( 'wp_delete_site', array( __CLASS__, 'on_site_deleted' ) );
		}

		/**
		 * Handle new subsite creation.
		 *
		 * Automatically activates the license for the new subsite if
		 * a valid license exists and activation slots are available.
		 * If no slots are available, sets a persistent notice flag.
		 *
		 * @param \WP_Site $new_site - The new site object.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function on_site_created( $new_site ) {
			if ( ! EDD_Provider::has_active_valid_license() ) {
				return;
			}

			$main_site_id = \get_main_site_id();
			$license_key  = \get_blog_option( $main_site_id, EDD_Provider::LICENSE_KEY_OPTION );
			$license_data = \get_blog_option( $main_site_id, EDD_Provider::LICENSE_DATA_OPTION, array() );
			$item_id      = is_array( $license_data ) && isset( $license_data['item_id'] ) ? (int) $license_data['item_id'] : 0;

			if ( empty( $license_key ) || 0 === $item_id ) {
				return;
			}

			// Build URL from site object properties (get_home_url may not work before site is fully set up).
			$scheme   = \is_ssl() ? 'https' : 'http';
			$site_url = \esc_url( $scheme . '://' . $new_site->domain . $new_site->path );
			$site_url = \untrailingslashit( $site_url );

			// Check if slots are available.
			$slots_available = self::get_available_slots( $license_key, $item_id );

			if ( false === $slots_available || $slots_available < 1 ) {
				// No slots available — set persistent notice flag.
				\update_site_option( self::ACTIVATION_FAILED_FLAG, true );
				return;
			}

			// Activate the new subsite.
			$result      = EDD_Provider::try_activate_license( $license_key, $item_id, $site_url );
			$activations = self::get_network_activation_status();

			if ( true === $result ) {
				$activations[ $site_url ] = array(
					'status'       => 'active',
					'activated_at' => time(),
				);
			} else {
				$activations[ $site_url ] = array(
					'status' => 'failed',
					'error'  => is_array( $result ) && isset( $result['message'] ) ? $result['message'] : 'Unknown error',
				);
				\update_site_option( self::ACTIVATION_FAILED_FLAG, true );
			}

			\update_site_option( self::NETWORK_ACTIVATIONS_OPTION, $activations );

			// Refresh license data.
			EDD_Provider::check_license( $license_key );
		}

		/**
		 * Handle subsite deletion.
		 *
		 * Deactivates the license for the deleted subsite to free the
		 * activation slot, and removes it from the network activations data.
		 *
		 * @param \WP_Site $old_site - The deleted site object.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function on_site_deleted( $old_site ) {
			$main_site_id = \get_main_site_id();
			$license_key  = \get_blog_option( $main_site_id, EDD_Provider::LICENSE_KEY_OPTION );
			$license_data = \get_blog_option( $main_site_id, EDD_Provider::LICENSE_DATA_OPTION, array() );
			$item_id      = is_array( $license_data ) && isset( $license_data['item_id'] ) ? (int) $license_data['item_id'] : 0;

			if ( empty( $license_key ) || 0 === $item_id ) {
				return;
			}

			// Build URL from site object properties (site tables may already be deleted).
			$scheme      = \is_ssl() ? 'https' : 'http';
			$site_url    = \esc_url( $scheme . '://' . $old_site->domain . $old_site->path );
			$site_url    = \untrailingslashit( $site_url );
			$activations = self::get_network_activation_status();

			// Deactivate this site's license on the store.
			EDD_Provider::try_deactivate_for_url( $license_key, $item_id, $site_url );

			// Remove from stored activations.
			if ( isset( $activations[ $site_url ] ) ) {
				unset( $activations[ $site_url ] );
				\update_site_option( self::NETWORK_ACTIVATIONS_OPTION, $activations );
			}

			// A slot was freed — clear the failed activation flag if set.
			\delete_site_option( self::ACTIVATION_FAILED_FLAG );

			// Refresh license data — switch to main site context first
			// because the deleted site's tables no longer exist.
			if ( ! empty( $license_key ) ) {
				\switch_to_blog( \get_main_site_id() );
				EDD_Provider::check_license( $license_key );
				\restore_current_blog();
			}
		}

		/**
		 * Activate the license for all subsites in the network.
		 *
		 * Pre-checks available activation slots against the number of subsites.
		 * If sufficient slots are available, activates each subsite individually
		 * in batches, tracking progress for frontend polling.
		 *
		 * @param string $license_key - The license key to activate.
		 *
		 * @return bool|array True on success, array with error info on failure.
		 *
		 * @since 2.4.0
		 */
		public static function activate_network_license( string $license_key ) {
			$sites      = self::get_all_site_urls();
			$site_count = count( $sites );

			// Step 1: Determine the correct item_id via sequential try.
			$item_id = self::resolve_item_id( $license_key );

			if ( 0 === $item_id ) {
				return array(
					'success' => false,
					'message' => \__( 'This license key is not valid for this product.', 'melapress-login-security' ),
					'code'    => 'item_name_mismatch',
				);
			}

			// Step 2: Pre-check available slots.
			$slots_available = self::get_available_slots( $license_key, $item_id );

			if ( false === $slots_available ) {
				return array(
					'success' => false,
					'message' => \__( 'Unable to verify license status. Please try again.', 'melapress-login-security' ),
					'code'    => 'check_failed',
				);
			}

			if ( $slots_available < $site_count ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: 1: number of subsites, 2: available activations */
						\__( 'Your network has %1$d subsites but your license only has %2$d activations remaining. Please upgrade your license or reduce the number of subsites before activating.', 'melapress-login-security' ),
						$site_count,
						$slots_available
					),
					'code'    => 'insufficient_slots',
				);
			}

			// Step 3: Store the license key and item_id before batch activation.
			\update_option( EDD_Provider::LICENSE_KEY_OPTION, $license_key );

			// Step 4: Initialize progress tracking.
			self::set_progress( $site_count, 0, 'processing' );

			// Step 5: Batch activate all subsites.
			$activations = array();
			$errors      = array();
			$completed   = 0;

			$batches = array_chunk( $sites, self::BATCH_SIZE );

			foreach ( $batches as $batch ) {
				foreach ( $batch as $site_url ) {
					$result = EDD_Provider::try_activate_license( $license_key, $item_id, $site_url );

					if ( true === $result ) {
						$activations[ $site_url ] = array(
							'status'       => 'active',
							'activated_at' => time(),
						);
					} else {
						$error_msg                = is_array( $result ) && isset( $result['message'] ) ? $result['message'] : 'Unknown error';
						$activations[ $site_url ] = array(
							'status' => 'failed',
							'error'  => $error_msg,
						);
						$errors[]                 = $site_url . ': ' . $error_msg;
					}

					++$completed;
				}

				// Update progress after each batch.
				self::set_progress( $site_count, $completed, 'processing', $errors );
			}

			// Step 6: Store activation results.
			\update_site_option( self::NETWORK_ACTIVATIONS_OPTION, $activations );

			// Step 7: Determine overall success.
			$active_count = count(
				array_filter(
					$activations,
					function ( $a ) {
						return 'active' === $a['status'];
					}
				)
			);

			if ( $active_count > 0 ) {
				// At least some sites activated — mark license as valid.
				\update_option( EDD_Provider::LICENSE_STATUS_OPTION, 'valid' );
				\update_option( EDD_Provider::PREMIUM_OPTION, 'yes' );
				\set_transient( EDD_Provider::PREMIUM_OPTION, 'yes', DAY_IN_SECONDS );
				\delete_transient( EDD_Provider::LICENSE_CHECK_TRANSIENT );

				// Fetch fresh license data.
				EDD_Provider::check_license( $license_key );
			}

			// Step 8: Mark progress as complete.
			self::set_progress( $site_count, $completed, 'complete', $errors );

			if ( ! empty( $errors ) ) {
				return array(
					'success' => true,
					'message' => sprintf(
						/* translators: 1: activated count, 2: total count */
						\__( 'License activated on %1$d of %2$d sites. Some sites had errors.', 'melapress-login-security' ),
						$active_count,
						$site_count
					),
					'partial' => true,
				);
			}

			return true;
		}

		/**
		 * Deactivate the license for all subsites in the network.
		 *
		 * Batch deactivates each subsite individually and clears all local data.
		 *
		 * @return bool True on success, false on failure.
		 *
		 * @since 2.4.0
		 */
		public static function deactivate_network_license(): bool {
			$license_key = \get_option( EDD_Provider::LICENSE_KEY_OPTION );

			if ( empty( $license_key ) ) {
				return false;
			}

			$license_data = \get_option( EDD_Provider::LICENSE_DATA_OPTION, array() );
			$item_id      = is_array( $license_data ) && isset( $license_data['item_id'] ) ? (int) $license_data['item_id'] : 0;
			$activations  = self::get_network_activation_status();

			// If no stored activations, use current sites.
			if ( empty( $activations ) ) {
				$sites = self::get_all_site_urls();
			} else {
				$sites = array_keys( $activations );
			}

			$site_count = count( $sites );

			// Initialize progress.
			self::set_progress( $site_count, 0, 'processing' );

			$completed = 0;
			$errors    = array();
			$batches   = array_chunk( $sites, self::BATCH_SIZE );

			foreach ( $batches as $batch ) {
				foreach ( $batch as $site_url ) {
					if ( $item_id > 0 ) {
						$result = EDD_Provider::try_deactivate_for_url( $license_key, $item_id, $site_url );

						if ( ! $result ) {
							$errors[] = $site_url;
						}
					}

					++$completed;
				}

				self::set_progress( $site_count, $completed, 'processing', $errors );
			}

			// Clear all local data regardless of individual results.
			\delete_site_option( self::NETWORK_ACTIVATIONS_OPTION );
			EDD_Provider::clear_local_license_data();

			// Mark progress as complete.
			self::set_progress( $site_count, $completed, 'complete', $errors );

			return true;
		}

		/**
		 * Sync the license for the network.
		 *
		 * Reconciles the current subsites against stored activations:
		 * - New subsites are activated (if slots available).
		 * - Removed subsites are deactivated.
		 * - Updates the network activation option with current state.
		 *
		 * @return bool True on success, false on failure.
		 *
		 * @since 2.4.0
		 */
		public static function sync_network_license(): bool {
			$license_key = \get_option( EDD_Provider::LICENSE_KEY_OPTION );

			if ( empty( $license_key ) ) {
				return false;
			}

			$license_data = \get_option( EDD_Provider::LICENSE_DATA_OPTION, array() );
			$item_id      = is_array( $license_data ) && isset( $license_data['item_id'] ) ? (int) $license_data['item_id'] : 0;

			if ( 0 === $item_id ) {
				return false;
			}

			$current_sites      = self::get_all_site_urls();
			$stored_activations = self::get_network_activation_status();
			$stored_urls        = array_keys( $stored_activations );

			// Find new subsites (in current but not stored).
			$new_sites = array_diff( $current_sites, $stored_urls );

			// Find removed subsites (in stored but not current).
			$removed_sites = array_diff( $stored_urls, $current_sites );

			// Deactivate removed subsites.
			foreach ( $removed_sites as $site_url ) {
				EDD_Provider::try_deactivate_for_url( $license_key, $item_id, $site_url );
				unset( $stored_activations[ $site_url ] );
			}

			// Activate new subsites (if slots available).
			if ( ! empty( $new_sites ) ) {
				$slots_available = self::get_available_slots( $license_key, $item_id );

				if ( false !== $slots_available && $slots_available > 0 ) {
					$sites_to_activate = array_slice( $new_sites, 0, $slots_available );

					foreach ( $sites_to_activate as $site_url ) {
						$result = EDD_Provider::try_activate_license( $license_key, $item_id, $site_url );

						if ( true === $result ) {
							$stored_activations[ $site_url ] = array(
								'status'       => 'active',
								'activated_at' => time(),
							);
						} else {
							$stored_activations[ $site_url ] = array(
								'status' => 'failed',
								'error'  => is_array( $result ) && isset( $result['message'] ) ? $result['message'] : 'Unknown error',
							);
						}
					}
				}
			}

			// Update stored data.
			\update_site_option( self::NETWORK_ACTIVATIONS_OPTION, $stored_activations );

			// Clear the failed activation flag after sync.
			\delete_site_option( self::ACTIVATION_FAILED_FLAG );

			// Refresh license data from the store.
			\delete_transient( EDD_Provider::LICENSE_CHECK_TRANSIENT );
			\delete_transient( EDD_Provider::PREMIUM_OPTION );
			EDD_Provider::check_license( $license_key );

			return true;
		}

		/**
		 * Get the network activation status data.
		 *
		 * @return array - Array of per-site activation data.
		 *
		 * @since 2.4.0
		 */
		public static function get_network_activation_status(): array {
			$data = \get_site_option( self::NETWORK_ACTIVATIONS_OPTION, array() );

			if ( ! is_array( $data ) ) {
				return array();
			}

			return $data;
		}

		/**
		 * Get the batch activation progress.
		 *
		 * @return array - Progress data array.
		 *
		 * @since 2.4.0
		 */
		public static function get_activation_progress(): array {
			$progress = \get_site_transient( self::PROGRESS_TRANSIENT );

			if ( ! is_array( $progress ) ) {
				return array(
					'total'     => 0,
					'completed' => 0,
					'status'    => 'idle',
					'errors'    => array(),
				);
			}

			return $progress;
		}

		/**
		 * Get all site URLs in the network.
		 *
		 * @return array - Array of site URLs.
		 *
		 * @since 2.4.0
		 */
		private static function get_all_site_urls(): array {
			$sites = \get_sites( array( 'number' => 0 ) );
			$urls  = array();

			foreach ( $sites as $site ) {
				$urls[] = \untrailingslashit( \get_home_url( (int) $site->blog_id ) );
			}

			return $urls;
		}

		/**
		 * Resolve the correct EDD item_id for the license key.
		 *
		 * Tries Premium first, then Enterprise. Uses check_license (not activate)
		 * to determine which product the key belongs to without consuming a slot.
		 *
		 * @param string $license_key - The license key.
		 *
		 * @return int - The resolved item_id, or 0 if none matched.
		 *
		 * @since 2.4.0
		 */
		private static function resolve_item_id( string $license_key ): int {
			$item_ids        = array( EDD_Provider::ITEM_ID_PREMIUM, EDD_Provider::ITEM_ID_ENTERPRISE );
			$mismatch_errors = array( 'key_mismatch', 'item_name_mismatch', 'invalid_item_id', 'missing' );

			foreach ( $item_ids as $item_id ) {
				$api_params = array(
					'edd_action' => 'check_license',
					'license'    => $license_key,
					'item_id'    => $item_id,
					'url'        => \network_home_url(),
				);

				$response = \wp_remote_post(
					EDD_Provider::STORE_URL,
					array(
						'timeout'   => 15,
						'sslverify' => true,
						'body'      => $api_params,
					)
				);

				if ( \is_wp_error( $response ) ) {
					continue;
				}

				$data = json_decode( \wp_remote_retrieve_body( $response ), true );

				if ( ! is_array( $data ) ) {
					continue;
				}

				// Check if the license status indicates this item_id is correct.
				// EDD may return 'invalid_item_id' in the license field (not just the error field)
				// when the key doesn't belong to this product.
				$license_status = isset( $data['license'] ) ? $data['license'] : '';
				$valid_statuses = array( 'valid', 'expired', 'inactive', 'disabled', 'site_inactive' );

				if ( in_array( $license_status, $valid_statuses, true ) ) {
					// Store the license data from the check.
					\update_option( EDD_Provider::LICENSE_DATA_OPTION, $data );
					return $item_id;
				}
			}

			return 0;
		}

		/**
		 * Get the number of available activation slots for the license.
		 *
		 * @param string $license_key - The license key.
		 * @param int    $item_id     - The EDD product ID.
		 *
		 * @return int|false - Number of available slots, or false on failure.
		 *
		 * @since 2.4.0
		 */
		private static function get_available_slots( string $license_key, int $item_id ) {
			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => $license_key,
				'item_id'    => $item_id,
				'url'        => \network_home_url(),
			);

			$response = \wp_remote_post(
				EDD_Provider::STORE_URL,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			if ( \is_wp_error( $response ) ) {
				return false;
			}

			$data = json_decode( \wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $data ) ) {
				return false;
			}

			// Store fresh license data.
			\update_option( EDD_Provider::LICENSE_DATA_OPTION, $data );

			if ( isset( $data['activations_left'] ) ) {
				// 'unlimited' means no limit.
				if ( 'unlimited' === $data['activations_left'] ) {
					return PHP_INT_MAX;
				}

				return (int) $data['activations_left'];
			}

			// If license_limit is 0, it means unlimited.
			if ( isset( $data['license_limit'] ) && 0 === (int) $data['license_limit'] ) {
				return PHP_INT_MAX;
			}

			return false;
		}

		/**
		 * Set the batch activation/deactivation progress.
		 *
		 * @param int    $total     - Total number of sites.
		 * @param int    $completed - Number completed so far.
		 * @param string $status    - Status: 'processing', 'complete', 'failed'.
		 * @param array  $errors    - Array of error messages.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function set_progress( int $total, int $completed, string $status, array $errors = array() ) {
			\set_site_transient(
				self::PROGRESS_TRANSIENT,
				array(
					'total'     => $total,
					'completed' => $completed,
					'status'    => $status,
					'errors'    => $errors,
				),
				self::PROGRESS_TTL
			);
		}
	}
}
