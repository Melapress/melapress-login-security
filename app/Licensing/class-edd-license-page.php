<?php
/**
 * EDD License Page.
 *
 * Registers and renders the license management admin page,
 * and adds the "Manage License" plugin action link.
 *
 * All plugin-specific values are sourced from Licensing_Factory constants
 * and accessor methods — no direct coupling to plugin globals.
 *
 * @since      3.3.0
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

if ( ! class_exists( '\MLS\Licensing\EDD_License_Page' ) ) {

	/**
	 * Handles the EDD license admin page and plugin action link.
	 *
	 * @since 2.4.0
	 */
	class EDD_License_Page {

		/**
		 * Page slug for the license page.
		 *
		 * References the canonical constant in Licensing_Factory.
		 *
		 * @var string
		 */
		const PAGE_SLUG = Licensing_Factory::UNIFIED_LICENSE_PAGE_SLUG;

		/**
		 * Initialize the license page hooks.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function init() {
			// Page registration is now handled by Licensing_Factory's unified license page.
			// Only register the plugin action link here.
			if ( \is_multisite() ) {
				\add_filter( 'network_admin_plugin_action_links_' . EDD_Provider::get_plugin_basename(), array( __CLASS__, 'add_action_link' ) );
			} else {
				\add_filter( 'plugin_action_links_' . EDD_Provider::get_plugin_basename(), array( __CLASS__, 'add_action_link' ) );
			}
		}

		/**
		 * Register the license admin page.
		 *
		 * When no valid license exists, registers a top-level menu item
		 * that shows the license activation form.
		 * This prevents access to all other plugin pages.
		 *
		 * When a valid license exists, registers as a visible submenu page
		 * accessible via the "Manage License" plugin action link.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function register_page() {
			$menu_slug = Licensing_Factory::MENU_SLUG;

			if ( ! EDD_Provider::has_active_valid_license() ) {
				// No valid license — register as top-level menu.
				$hook = \add_menu_page(
					\__( Licensing_Factory::MENU_TITLE, 'melapress-login-security' ),
					\__( Licensing_Factory::MENU_TITLE, 'melapress-login-security' ),
					'manage_options',
					$menu_slug,
					array( __CLASS__, 'render_page' ),
					' ',
					99
				);

				// Add menu icon styles if a callback is configured.
				if ( ! empty( Licensing_Factory::MENU_ICON_CLASS ) && method_exists( Licensing_Factory::MENU_ICON_CLASS, Licensing_Factory::MENU_ICON_METHOD ) ) {
					\add_action( 'admin_head', array( Licensing_Factory::MENU_ICON_CLASS, Licensing_Factory::MENU_ICON_METHOD ) );
				}

				// Remove any submenus registered by other modules at a late priority.
				$menu_hook = \is_multisite() ? 'network_admin_menu' : 'admin_menu';
				\add_action( $menu_hook, array( __CLASS__, 'remove_all_submenus' ), 999 );
			} else {
				// Valid license — register as visible submenu.
				$hook = \add_submenu_page(
					$menu_slug,
					\__( 'Manage License', 'melapress-login-security' ),
					\__( 'Manage License', 'melapress-login-security' ),
					'manage_options',
					self::PAGE_SLUG,
					array( __CLASS__, 'render_page' ),
				);
			}

			\add_action( "load-{$hook}", array( __CLASS__, 'enqueue_scripts' ) );
		}

		/**
		 * Remove all submenus under the plugin's top-level menu.
		 *
		 * Runs at a late priority (999) on admin_menu to clean up any
		 * submenus registered by plugin modules when no valid EDD license
		 * exists. This ensures only the top-level license activation page
		 * is accessible, matching the Freemius unlicensed behavior.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function remove_all_submenus() {
			global $submenu;

			$menu_slug = Licensing_Factory::MENU_SLUG;

			if ( isset( $submenu[ $menu_slug ] ) ) {
				$submenu[ $menu_slug ] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		/**
		 * Get the license page URL.
		 *
		 * Resolves the correct admin page URL based on stored license data.
		 * When license data exists (active, expired, or free mode), returns the license submenu page.
		 * When no license data exists (fresh install), returns the top-level menu page.
		 *
		 * @return string - The license page admin URL.
		 *
		 * @since 2.4.0
		 */
		public static function get_license_page_url(): string {
			$page_slug = Licensing_Factory::has_stored_license_data() ? self::PAGE_SLUG : Licensing_Factory::MENU_SLUG;

			return \network_admin_url( 'admin.php?page=' . $page_slug );
		}

		/**
		 * Add "Manage License" link to the plugin action links.
		 *
		 * @param array $links - Existing plugin action links.
		 *
		 * @return array - Modified action links.
		 *
		 * @since 2.4.0
		 */
		public static function add_action_link( array $links ): array {
			$links['edd_license'] = '<a href="' . \esc_url( Licensing_Factory::get_license_page_url() ) . '">' . \esc_html__( 'Manage License', 'melapress-login-security' ) . '</a>';

			return $links;
		}

		/**
		 * Enqueue scripts and styles for the license page.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function enqueue_scripts() {

			\wp_enqueue_script(
				EDD_Provider::SCRIPT_HANDLE,
				Licensing_Factory::PLUGIN_URL . 'app/Licensing/edd-licensing.js',
				array(),
				Licensing_Factory::PLUGIN_VERSION,
				true
			);

			\wp_localize_script(
				EDD_Provider::SCRIPT_HANDLE,
				'eddLicense',
				array(
					'ajaxUrl'      => \admin_url( 'admin-ajax.php' ),
					'nonce'        => \wp_create_nonce( EDD_Provider::NONCE_ACTION ),
					'redirectUrl'  => \network_admin_url( 'admin.php?page=' . Licensing_Factory::MENU_SLUG ),
					'isMultisite'  => \is_multisite(),
					'pollInterval' => 3000,
					'actions'      => array(
						'activate'   => EDD_Provider::AJAX_PREFIX . 'activate_license',
						'deactivate' => EDD_Provider::AJAX_PREFIX . 'deactivate_license',
						'sync'       => EDD_Provider::AJAX_PREFIX . 'sync_license',
						'progress'   => EDD_Provider::AJAX_PREFIX . 'get_activation_progress',
					),
					'i18n'         => array(
						'activatingText'       => \esc_html__( 'Activating...', 'melapress-login-security' ),
						'deactivatingText'     => \esc_html__( 'Deactivating...', 'melapress-login-security' ),
						'syncingText'          => \esc_html__( 'Syncing...', 'melapress-login-security' ),
						'activatingLabel'      => \esc_html__( 'Activating', 'melapress-login-security' ),
						'deactivatingLabel'    => \esc_html__( 'Deactivating', 'melapress-login-security' ),
						'syncingLabel'         => \esc_html__( 'Syncing', 'melapress-login-security' ),
						'enterLicenseKey'      => \esc_html__( 'Please enter a license key.', 'melapress-login-security' ),
						'activateBtn'          => \esc_html__( 'Activate', 'melapress-login-security' ),
						'deactivateBtn'        => \esc_html__( 'Deactivate', 'melapress-login-security' ),
						'syncBtn'              => \esc_html__( 'Sync License', 'melapress-login-security' ),
						'networkError'         => \esc_html__( 'A network error occurred. Please try again.', 'melapress-login-security' ),
						'activatedSuccess'     => \esc_html__( 'License activated successfully.', 'melapress-login-security' ),
						'activationFailed'     => \esc_html__( 'Activation failed.', 'melapress-login-security' ),
						'deactivatedSuccess'   => \esc_html__( 'License deactivated successfully.', 'melapress-login-security' ),
						'deactivationFailed'   => \esc_html__( 'Deactivation failed.', 'melapress-login-security' ),
						'syncedSuccess'        => \esc_html__( 'License synced successfully.', 'melapress-login-security' ),
						'syncFailed'           => \esc_html__( 'Sync failed.', 'melapress-login-security' ),
						'sitesComplete'        => \esc_html__( 'sites complete', 'melapress-login-security' ),
						'completedWithErrors'  => \esc_html__( 'completed with %d error(s).', 'melapress-login-security' ),
						'completedSuccessfully' => \esc_html__( 'completed successfully.', 'melapress-login-security' ),
					),
				)
			);
		}

		/**
		 * Render the license management page.
		 *
		 * Detects multisite and shows network-specific information
		 * (subsite activation count) when applicable.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function render_page() {
			if ( ! \current_user_can( 'manage_options' ) ) {
				return;
			}

			$license_key  = \get_option( EDD_Provider::LICENSE_KEY_OPTION, '' );
			$status       = \get_option( EDD_Provider::LICENSE_STATUS_OPTION, '' );
			$license_data = \get_option( EDD_Provider::LICENSE_DATA_OPTION, array() );

			if ( ! is_array( $license_data ) ) {
				$license_data = array();
			}

			$is_active     = 'valid' === $status;
			$is_multisite  = \is_multisite();
			$item_name     = isset( $license_data['item_name'] ) ? $license_data['item_name'] : '';
			$expires       = isset( $license_data['expires'] ) ? $license_data['expires'] : '';
			$site_count    = isset( $license_data['site_count'] ) ? (int) $license_data['site_count'] : 0;
			$license_limit = isset( $license_data['license_limit'] ) ? (int) $license_data['license_limit'] : 0;

			// Format expiration date.
			$expiry_display = '';
			if ( ! empty( $expires ) && 'lifetime' !== $expires ) {
				$expiry_display = \wp_date( \get_option( 'date_format' ), strtotime( $expires ) );
			} elseif ( 'lifetime' === $expires ) {
				$expiry_display = \__( 'Lifetime', 'melapress-login-security' );
			}

			// Format activation count.
			$activation_display = '';
			if ( $license_limit > 0 ) {
				$activation_display = sprintf( '%d / %d', $site_count, $license_limit );
			} elseif ( 0 === $license_limit && $is_active ) {
				$activation_display = \__( 'Unlimited', 'melapress-login-security' );
			}

			// Multisite-specific data.
			$network_activations  = array();
			$network_active_count = 0;
			$network_total_sites  = 0;

			if ( $is_multisite && $is_active ) {
				$network_activations  = EDD_Network_Licensing::get_network_activation_status();
				$network_active_count = count(
					array_filter(
						$network_activations,
						function ( $a ) {
							return isset( $a['status'] ) && 'active' === $a['status'];
						}
					)
				);
				$network_total_sites  = count( \get_sites( array( 'number' => 0 ) ) );
			}

			?>
			<div class="wrap">
				<h1><?php \esc_html_e( 'License Management', 'melapress-login-security' ); ?></h1>

				<div id="edd-license-message" class="notice" style="display:none;"><p></p></div>
				<div id="edd-license-progress" style="display:none;">
					<p><span id="edd-license-progress-text"></span></p>
					<progress id="edd-license-progress-bar" max="100" value="0" style="width:100%;"></progress>
				</div>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="edd-license-key"><?php \esc_html_e( 'License Key', 'melapress-login-security' ); ?></label>
							</th>
							<td>
								<input type="<?php echo $is_active ? 'password' : 'text'; ?>"
									id="edd-license-key"
									name="license_key"
									class="regular-text"
									value="<?php echo \esc_attr( $license_key ); ?>"
									<?php echo $is_active ? 'readonly' : ''; ?>
								/>

								<?php if ( ! $is_active ) : ?>
									<button type="button" id="edd-license-activate" class="button button-primary">
										<?php \esc_html_e( 'Activate', 'melapress-login-security' ); ?>
									</button>
								<?php else : ?>
									<button type="button" id="edd-license-deactivate" class="button">
										<?php \esc_html_e( 'Deactivate', 'melapress-login-security' ); ?>
									</button>
									<button type="button" id="edd-license-sync" class="button">
										<?php \esc_html_e( 'Sync License', 'melapress-login-security' ); ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php \esc_html_e( 'Status', 'melapress-login-security' ); ?></th>
							<td>
								<span id="edd-license-status" class="edd-license-status edd-license-status--<?php echo \esc_attr( $is_active ? 'active' : $status ); ?>">
									<?php
									if ( $is_active && $is_multisite ) {
										\esc_html_e( 'Active (Network)', 'melapress-login-security' );
									} elseif ( $is_active ) {
										\esc_html_e( 'Active', 'melapress-login-security' );
									} elseif ( 'expired' === $status ) {
										\esc_html_e( 'Expired', 'melapress-login-security' );
									} elseif ( ! empty( $status ) ) {
										\esc_html_e( 'Inactive', 'melapress-login-security' );
									} else {
										\esc_html_e( 'Not activated', 'melapress-login-security' );
									}
									?>
								</span>
							</td>
						</tr>

						<?php if ( $is_active && ! empty( $item_name ) ) : ?>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Plan', 'melapress-login-security' ); ?></th>
							<td><strong><?php echo \esc_html( $item_name ); ?></strong></td>
						</tr>
						<?php endif; ?>

						<?php if ( $is_active && ! empty( $expiry_display ) ) : ?>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Expires', 'melapress-login-security' ); ?></th>
							<td><?php echo \esc_html( $expiry_display ); ?></td>
						</tr>
						<?php endif; ?>

						<?php if ( $is_active && $is_multisite ) : ?>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Network Subsites', 'melapress-login-security' ); ?></th>
							<td>
								<?php
								printf(
									/* translators: 1: activated subsites count, 2: total subsites, 3: activations used, 4: license limit */
									\esc_html__( '%1$d of %2$d subsites activated (%3$s total activations used)', 'melapress-login-security' ),
									$network_active_count, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									$network_total_sites, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									$activation_display // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								);
								?>
							</td>
						</tr>
						<?php elseif ( $is_active && ! empty( $activation_display ) ) : ?>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Activations', 'melapress-login-security' ); ?></th>
							<td><?php echo \esc_html( $activation_display ); ?></td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
		}
	}
}
