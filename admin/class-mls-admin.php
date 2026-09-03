<?php
/**
 * Melapress Login Security Admin Class.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;
use MLS\Licensing\Licensing_Factory;
use MLS\Utilities\Validator_Factory;

if ( ! class_exists( '\MLS\Admin\Admin' ) ) {

	/**
	 * Declare Admin class
	 *
	 * @since 2.0.0
	 */
	class Admin {

		public const PLUGIN_PAGES = array(
			'toplevel_page_mls-policies',
			'toplevel_page_mls-policies-network',
			'toplevel_page_mls-forms',
			'toplevel_page_mls-forms-network',
			'toplevel_page_mls-reports',
			'toplevel_page_mls-reports-network',
			'toplevel_page_mls-locked-users',
			'toplevel_page_mls-locked-users-network',
			'toplevel_page_mls-hide-login',
			'toplevel_page_mls-hide-login-network',
			'toplevel_page_mls-settings',
			'toplevel_page_mls-settings-network',
			'toplevel_page_mls-policies-account',
			'toplevel_page_mls-policies-account-network',
			'toplevel_page_mls-help',
			'toplevel_page_mls-help-network',
		);

		/**
		 * Melapress Login Security Options.
		 *
		 * @var array|object
		 *
		 * @since 2.0.0
		 */
		public static $options;

		/**
		 * Password Policy Manager Settings.
		 *
		 * @var array|object settings
		 *
		 * @since 2.0.0
		 */
		public static $settings;

		/**
		 * Melapress Login Security Setting Tab.
		 *
		 * @var array $setting_tab
		 *
		 * @since 2.0.0
		 */
		public static $setting_tab = array();

		/**
		 * Melapress Login Security additional notice content.
		 *
		 * @var array $extra_notice_details
		 *
		 * @since 2.0.0
		 */
		private static $extra_notice_details = array();

		/**
		 * Class construct.
		 *
		 * @param array|object $options PPM options.
		 * @param array|object $settings PPM setting options.
		 * @param array|object $setting_options Get current role option.
		 *
		 * @return mixed
		 *
		 * @since 2.0.0
		 */
		public function __construct( $options, $settings, $setting_options ) {
			self::$options     = $options;
			self::$settings    = $settings;
			self::$setting_tab = $setting_options;

			\add_filter( 'plugin_action_links_' . MLS_BASENAME, array( __CLASS__, 'plugin_action_links' ), 100, 1 );
			\add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

			// Ajax.
			\add_action( 'wp_ajax_get_users_roles', array( __CLASS__, 'search_users_roles' ) );
			\add_action( 'wp_ajax_mls_send_test_email', array( __CLASS__, 'send_test_email' ) );
			\add_action( 'wp_ajax_mls_process_reset', array( '\MLS\Reset_Passwords', 'process_global_password_reset' ) );

			// Bulk actions.
			\add_filter( 'bulk_actions-users', array( '\MLS\Reset_Passwords', 'add_bulk_action_link' ), 10, 1 );
			\add_filter( 'handle_bulk_actions-users', array( '\MLS\Reset_Passwords', 'handle_bulk_action_link' ), 10, 3 );
			\add_action( 'admin_notices', array( '\MLS\Reset_Passwords', 'bulk_action_admin_notice' ) );

			// Add dialog box.
			\add_action( 'admin_footer', array( __CLASS__, 'admin_footer_session_expired_dialog' ) );
			\add_action( 'admin_footer', array( __CLASS__, 'popup_notices' ) );

			$options_master_switch    = OptionsHelper::string_to_bool( self::$options->master_switch );
			$settings_master_switch   = OptionsHelper::string_to_bool( self::$settings->master_switch );
			$inherit_policies_setting = OptionsHelper::string_to_bool( self::$settings->inherit_policies );

			$is_needed = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );

			if ( $is_needed ) {
				if ( OptionsHelper::string_to_bool( self::$settings->enforce_password ) ) {
					return;
				}
				\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'global_admin_enqueue_scripts' ) );
			}

			\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_custom_wp_admin_style' ) );

			\add_action( 'admin_notices', array( __CLASS__, 'plugin_was_updated_banner' ), 20, 3 );

			// @free:start
			\add_action( 'admin_notices', array( __CLASS__, 'extra_event_banner' ), 10, 3 );
			\add_action( 'wp_ajax_mls_dismiss_extra_event_banner', array( __CLASS__, 'dismiss_extra_event_banner' ) );
			// @free:end

			\add_action( 'wp_ajax_dismiss_mls_update_notice', array( __CLASS__, 'dismiss_update_notice' ) );
			\add_action( 'wp_ajax_dismiss_mls_feature_highlight', array( __CLASS__, 'dismiss_feature_highlight' ) );
			\add_action( 'wp_ajax_mls_begin_migration', array( __CLASS__, 'begin_migration' ) );
			\add_action( 'wp_ajax_mls_get_migration_status', array( __CLASS__, 'get_migration_status' ) );


			// @free:start
			if ( ! class_exists( '\MLS\EmailAndMessageTemplates' ) ) {
				\add_filter( 'mls_settings_page_nav_tabs', array( __CLASS__, 'messages_settings_tab_link' ), 10, 1 );
				\add_filter( 'mls_settings_page_content_tabs', array( __CLASS__, 'messages_settings_tab' ), 10, 1 );
			}
			// @free:end
		}

		/**
		 * Show extra event banner.
		 *
		 * @return void
		 *
		 * @since 2.3.0
		 */
		public static function extra_event_banner() {
			$screen                       = \get_current_screen();
			$show_extra_event_banner      = \get_site_option( MLS_PREFIX . '_extra_event_banner', false );
			$extra_event_banner_dismissed = \get_site_option( MLS_PREFIX . '_extra_event_banner_dismissed', false );

			if ( $show_extra_event_banner ) {
				$event_ending_date = \get_site_option( MLS_PREFIX . '_extra_event_banner_end_date', false );
				if ( $event_ending_date && ( \time() > (int) ( $event_ending_date ) ) ) {
					$show_extra_event_banner = false;
					\delete_site_option( MLS_PREFIX . '_extra_event_banner' );
					\delete_site_option( MLS_PREFIX . '_extra_event_banner_end_date' );
					\delete_site_option( MLS_PREFIX . '_extra_event_banner_dismissed' );
				}
			}
			if ( in_array( $screen->base, self::PLUGIN_PAGES, true ) && $show_extra_event_banner && ! $extra_event_banner_dismissed ) {

				\remove_action( 'admin_notices', array( __CLASS__, 'plugin_was_updated_banner' ), 20, 3 );
				?>
				<!-- Copy START -->
				<div class="black-friday mls-extra-event-banner" style="margin-top: 20px; margin-right: 20px;">
					<!-- SVG Icon on the Left -->
					<img class="black-friday-svg" src="<?php echo esc_url( MLS_PLUGIN_URL . 'assets/images/upgrade-plugin-icon.svg' ); ?>" alt="Premium Plugin" width="113" height="101">
					
					<!-- Text Content -->
					<div class="black-friday-content">
					<h2 class="black-friday-title"><?php \esc_html_e( 'Upgrade to Premium', 'melapress-login-security' ); ?><br>
						<span class="bf-title-line-2"><span class="bf-underline"><?php \esc_html_e( 'Black Friday', 'melapress-login-security' ); ?></span> <?php \esc_html_e( ' Sale Now Live!', 'melapress-login-security' ); ?></span>
					</h2>
					<a href="https://melapress.com/black-friday-cyber-monday/?utm_source=plugin&utm_medium=mls&utm_campaign=BFCM2025" target="_blank" class="bf-cta-link"><?php \esc_html_e( 'Get Offer Now', 'melapress-login-security' ); ?></a>
					</div>
					
					<!-- Close Button -->
					<button aria-label="Close button" class="mls-extra-event-banner-close black-friday-close" data-dismiss-nonce="<?php echo \esc_attr( \wp_create_nonce( 'mls_dismiss_extra_event_banner_nonce' ) ); ?>"></button>
				</div>
				<!-- Copy END -->
				
				<script type="text/javascript">
					if ('scrollRestoration' in history) {
						history.scrollRestoration = 'manual';
					}
				jQuery(document).ready(function( $ ) {
					jQuery( 'body' ).on( 'click', '.mls-extra-event-banner-close', function ( e ) {
						var nonce  = jQuery( '.mls-extra-event-banner [data-dismiss-nonce]' ).attr( 'data-dismiss-nonce' );
						
						jQuery.ajax({
							type: 'POST',
							url: '<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>',
							async: true,
							data: {
								action: 'mls_dismiss_extra_event_banner',
								nonce : nonce,
							},
							success: function ( result ) {		
								jQuery( '.mls-extra-event-banner' ).slideUp( 300 );
							}
						});
					});
				});
				</script>
				<style>
					@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Quicksand:wght@600;700&display=swap');

					:root {
					--color-coral: #FF8977;
					--color-deep: #020E26;
					--color-pale-blue: #D9E4FD;
					--color-light-blue: #8AAAF1;
					--color-mls-maroon: #7A262A;
					--color-mls-red: #DD2B10;
					--ease-out-expo: cubic-bezier(0.32, 1, 0.3, 1);
					--ease-out-back: cubic-bezier(0.64, 0.69, 0.1, 1);
					}

					/* ==================== Black Friday Banner ==================== */
					.black-friday {
					background-color: var(--color-deep);
					font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
					-webkit-font-smoothing: subpixel-antialiased;
					color: #fff;
					display: flex;
					align-items: center;
					padding: 1.66rem;
					position: relative;
					overflow: hidden;
					transition: all 0.2s ease-in-out;
					border: none;
					border-left: 4px solid var(--color-coral);
					gap: 1.5rem; /* Space between SVG and text */
					}

					.black-friday-svg {
					flex-shrink: 0;
					width: 134px;
					height: 101px;
					z-index: 1;
					margin-right: 32px;
					}

					.black-friday-content {
					max-width: 45%;
					z-index: 1;
					}

					.black-friday-title {
					font-family: Inter, sans-serif;
					font-size: 2.2em;
					letter-spacing: .5px;
					text-transform: uppercase;
					color: var(--color-coral);
					line-height: .7em;
					font-weight: 900;
					margin-bottom: 4px;
					}

					.bf-title-line-2 {
					color: #fff;
					font-size: .725em;
					}

					.bf-underline {
					text-decoration: underline;
					}

					.black-friday-text {
					margin: .25rem 0 0;
					font-size: 13px;
					line-height: 1.3125rem;
					}

					.bf-link {
					color: #fff;
					font-weight: 400;
					text-decoration: underline;
					font-size: 0.875rem;
					padding: 0.675rem 1.3rem .7rem 0;
					transition: all 0.2s ease-in-out;
					display: inline-block;
					margin: .5rem 0 0;
					}

					.bf-link:hover {
					color: #D9E4FD;
					}

					.bf-cta-link {
					border-radius: 0.25rem;
					background: #D9E4FD;
					color: #454BF7;
					font-weight: bold;
					text-decoration: none;
					font-size: 0.875rem;
					padding: 0.675rem 1.3rem .7rem 1.3rem;
					transition: all 0.2s ease-in-out;
					display: inline-block;
					margin: .5rem 0 0;
					}

					.bf-cta-link:hover {
					background: #454BF7;
					color: #D9E4FD;
					}

					.black-friday-close {
					background-image: url('<?php echo esc_url( MLS_PLUGIN_URL . 'assets/images/close-icon-reverse.svg' ); ?>');
					background-size: cover;
					width: 12px;
					height: 12px;
					border: none;
					cursor: pointer;
					position: absolute;
					top: 20px;
					right: 20px;
					background-color: transparent;
					z-index: 1;
					}

					.black-friday {
					background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 182 127"><path d="M181.413 -165.636L134.391 234.514L0 234.514L2.19345e-05 -165.636L181.413 -165.636Z" fill="%23B6C3F2"/></svg>');
					background-repeat: no-repeat;
					background-position: -20px 0;
					}

					/* Z-index for layered elements */
					.plugin-update-content,
					.mls-svg,
					.plugin-update-close,
					.black-friday-content,
					.black-friday-svg,
					.black-friday-close {
					z-index: 1;
					}

					/* ==================== Responsive Design ==================== */
					@media (max-width: 1200px) {
					.plugin-update,
					.black-friday {
						background-image: none;
						flex-direction: column;
						text-align: center;
						gap: 1rem;
					}

					.mls-svg,
					.black-friday-svg {
						width: 90px;
						height: 80px;
						margin: 0;
					}

					.plugin-update-content,
					.black-friday-content {
						max-width: 100%;
					}
					}
				</style>

				<?php
			}
		}

		/**
		 * Handle event banner dismissal.
		 *
		 * @return void
		 *
		 * @since 2.300
		 */
		public static function dismiss_extra_event_banner() {
			// Grab POSTed data.
			$nonce = isset( $_POST['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) ) : false;

			// Check nonce.
			// Network-scoped: writes network options.
			if ( ! OptionsHelper::current_user_can_manage_scope() || empty( $nonce ) || ! $nonce || ! \wp_verify_nonce( $nonce, 'mls_dismiss_extra_event_banner_nonce' ) ) {
				\wp_send_json_error( \esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			\delete_site_option( MLS_PREFIX . '_extra_event_banner' );
			\update_site_option( MLS_PREFIX . '_extra_event_banner_dismissed', 'yes' );

			$today_date = gmdate( 'Y-m-d' );
			$today_date = gmdate( 'Y-m-d', strtotime( $today_date ) );

			if ( gmdate( 'Y-m-d', strtotime( '11/28/2025' ) ) === $today_date ) {
				\update_site_option( MLS_PREFIX . '_extra_event_banner_super_dismissed', 'yes' );
			}

			\wp_send_json_success( \esc_html__( 'Complete.', 'melapress-login-security' ) );
		}

		/**
		 * Show notice to recently updated plugin.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function plugin_was_updated_banner() {
			$show_update_notice     = \get_site_option( MLS_PREFIX . '_update_notice_needed', false );
			$screen                 = \get_current_screen();
			$mls_migration_required = \is_multisite() ? \get_site_option( 'mls_migration_required' ) : \get_option( 'mls_migration_required' );
			$migration_complete     = \is_multisite() ? \get_site_option( 'mls_200_migration_complete' ) : \get_option( 'mls_200_migration_complete' );

			if ( in_array( $screen->base, self::PLUGIN_PAGES, true ) ) {
				?>
				<style>
					.mls-update-notice {
						position: relative;
						background-color: #fff;
						border: 1px solid #c3c4c7;
						border-left: 4px solid #dd2b10;
						margin: 64px 20px 16px 0;
						padding: 0 36px 0 12px;
						font-size: 0.8125rem;
					}

					.mls-update-notice-close {
						position: absolute;
						top: 50%;
						right: 8px;
						transform: translateY(-50%);
						background: none;
						border: none;
						font-size: 1.25rem;
						line-height: 1;
						cursor: pointer;
						color: #787c82;
						padding: 4px;
					}

					.mls-update-notice-close:hover {
						color: #d63638;
					}

					.mls-update-notice p {
						margin: 0.5em 0;
					}

					.mls-update-notice strong,
					.mls-update-notice a {
						color: #dd2b10;
					}

					.mls-feature-highlight-notice {
						position: relative;
						display: flex;
						gap: 16px;
						background: #fafbf3;
						border-radius: 5px;
						box-shadow: 0 3px 6px rgba(0, 0, 0, 0.06);
						margin: 64px 20px 16px 0;
						padding: 16px 48px 16px 16px;
						color: #3c434a;
					}

					.mls-update-notice ~ .mls-feature-highlight-notice {
						margin-top: 16px;
					}

					.mls-feature-highlight-notice::before {
						content: '';
						background: url('<?php echo \esc_url( MLS_PLUGIN_URL . 'assets/images/password-policy-manager.png' ); ?>') no-repeat center / contain;
						flex-shrink: 0;
						height: 44px;
						width: 44px;
					}

					.mls-feature-highlight-notice h2 {
						color: #3c434a;
						font-size: 1.25rem;
						font-weight: 600;
						line-height: 1.3;
						margin: 0 0 8px;
						padding: 0;
					}

					.mls-feature-highlight-notice p {
						font-size: 0.875rem;
						line-height: 1.6;
						margin: 0 0 16px;
					}

					/*
					 * Melapress red, and the darker red from the same logo on
					 * hover. The shape is left as it was, matching the button
					 * WordPress plugins put in a notice like this.
					 */
					.mls-feature-highlight-notice-upgrade {
						background: #dd2b10;
						border-radius: 5px;
						color: #fff;
						display: inline-block;
						font-size: 0.875rem;
						line-height: 1;
						padding: 8px 12px;
						text-decoration: none;
					}

					.mls-feature-highlight-notice-upgrade:hover,
					.mls-feature-highlight-notice-upgrade:focus {
						background: #7a262a;
						color: #fff;
					}

					.mls-feature-highlight-notice-close {
						position: absolute;
						top: 8px;
						right: 8px;
						background: none;
						border: none;
						font-size: 1.25rem;
						line-height: 1;
						cursor: pointer;
						color: #787c82;
						padding: 4px;
					}

					.mls-feature-highlight-notice-close:hover,
					.mls-feature-highlight-notice-close:focus {
						color: #dd2b10;
					}

					@media (max-width: 782px) {
						.mls-update-notice,
						.mls-feature-highlight-notice {
							margin-right: 0;
							margin-top: 16px;
						}

						.mls-feature-highlight-notice {
							gap: 12px;
							padding: 14px;
							flex-direction: column;
						}

						.mls-feature-highlight-notice::before {
							height: 36px;
							width: 36px;
						}

						.mls-feature-highlight-notice h2 {
							font-size: 1.05rem;
						}

						.mls-feature-highlight-notice p {
							line-height: 1.5;
						}
					}

					@media (min-width: 790px) {
						.mls-update-notice,
						.mls-feature-highlight-notice {
							margin-right: 20px;
						}
					}
				</style>
				<?php
			}

			if ( in_array( $screen->base, self::PLUGIN_PAGES, true ) && $show_update_notice ) {
				?>
				<div class="mls-update-notice mls-notice">
					<p>
						<?php
						echo \wp_kses_post(
							sprintf(
								/* translators: 1: Plugin name. 2: Version number. */
								__( '%1$s has been updated to version %2$s', 'melapress-login-security' ),
								'<strong>Melapress Login Security</strong>',
								'<strong>' . MLS_VERSION . '</strong>'
							)
						);
						?>
						&ndash; <a href="https://melapress.com/wordpress-login-security/releases/?utm_source=plugin&utm_medium=mls&utm_campaign=update-notice-changelog" target="_blank" rel="noopener"><?php \esc_html_e( 'view changelog', 'melapress-login-security' ); ?></a>
					</p>
					<button type="button" class="mls-update-notice-close" data-dismiss-nonce="<?php echo \esc_attr( \wp_create_nonce( 'mls_dismiss_update_notice_nonce' ) ); ?>" aria-label="<?php \esc_attr_e( 'Dismiss', 'melapress-login-security' ); ?>">&times;</button>
				</div>
				<script type="text/javascript">
				jQuery(document).ready(function($){
					$('.mls-update-notice-close').on('click', function(){
						var $notice = $(this).closest('.mls-update-notice');
						$.post('<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>', {
							action: 'dismiss_mls_update_notice',
							nonce: $(this).data('dismiss-nonce')
						});
						$notice.slideUp(300);
					});
				});
				</script>
				<?php
			}

			// @free:start
			/*
			 * Shown after an upgrade, alongside the changelog notice above, and
			 * never on a fresh install — mls_on_plugin_update() only raises this
			 * flag when it finds a stored version older than this one. It carries
			 * its own flag rather than sharing the changelog one so that
			 * dismissing either notice leaves the other alone.
			 */
			if ( in_array( $screen->base, self::PLUGIN_PAGES, true ) && \get_site_option( MLS_PREFIX . '_feature_highlight_needed', false ) ) {
				?>
				<div class="mls-feature-highlight-notice mls-notice">
					<div>
						<h2><?php \esc_html_e( 'Take login security beyond the basics', 'melapress-login-security' ); ?></h2>
						<p><?php \esc_html_e( 'Add geo-blocking, inactive account policies, unrecognized device alerts, and detailed reports on user and password activity.', 'melapress-login-security' ); ?></p>
						<a class="mls-feature-highlight-notice-upgrade" href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugin&utm_medium=mls&utm_campaign=update-feature-highlight-banner" target="_blank" rel="noopener"><?php \esc_html_e( 'Unlock Premium Features', 'melapress-login-security' ); ?></a>
					</div>
					<button type="button" class="mls-feature-highlight-notice-close" data-dismiss-nonce="<?php echo \esc_attr( \wp_create_nonce( 'mls_dismiss_feature_highlight_nonce' ) ); ?>" aria-label="<?php \esc_attr_e( 'Dismiss', 'melapress-login-security' ); ?>">&times;</button>
				</div>
				<script type="text/javascript">
				jQuery(document).ready(function($){
					$('.mls-feature-highlight-notice-close').on('click', function(){
						var $notice = $(this).closest('.mls-feature-highlight-notice');
						$.post('<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>', {
							action: 'dismiss_mls_feature_highlight',
							nonce: $(this).data('dismiss-nonce')
						});
						$notice.slideUp(300);
					});
				});
				</script>
				<?php
			}
			// @free:end

			if ( ( in_array( $screen->base, self::PLUGIN_PAGES, true ) && $mls_migration_required && ! $migration_complete ) || ( in_array( $screen->base, self::PLUGIN_PAGES, true ) && ! empty( \get_site_option( 'ppmwp_options', false ) ) ) ) {
				?>
				<div class="mls-plugin-data-migration">
					<div class="mls-plugin-update-content">
						<h2 class="mls-plugin-update-title"><?php \esc_html_e( 'Important data migration required', 'melapress-login-security' ); ?></h2>
						<p class="mls-plugin-update-text">
							<?php \esc_html_e( 'This update made some required changes to where our plugin data is stored and requires updating to avoid issues in future. Click below to begin the process, which will happen automatically.', 'melapress-login-security' ); ?>							
						</p>
						<a href="https://melapress.com/wordpress-login-security/releases/?utm_source=plugin&utm_medium=banner&utm_campaign=mls" target="_blank" class="mls-cta-link mls-begin-migration" data-dismiss-nonce="<?php echo \esc_attr( \wp_create_nonce( 'mls_begin_migration_nonce' ) ); ?>"><?php \esc_html_e( 'Begin migration process', 'melapress-login-security' ); ?></a>
					</div>
					<div id="spinning-wrapper"><span class="dashicons dashicons-admin-generic"></span></div>
				</div>
				<script type="text/javascript">
				//<![CDATA[

				jQuery(document).ready(function( $ ) {
					jQuery( 'body' ).on( 'click', '.mls-begin-migration', function ( e ) {
						e.preventDefault();

						jQuery( '.mls-plugin-data-migration a' ).slideUp();
						jQuery( '.mls-plugin-data-migration .mls-plugin-update-content .mls-plugin-update-title' ).text( 'Thank you' );
						jQuery( '.mls-plugin-data-migration .mls-plugin-update-content .mls-plugin-update-text' ).html('The process should not take long, you can check the progress below. Please remain on this page whilst migration takes place.');
						jQuery( '<br><div class="status"></div>' ).insertAfter( '.mls-plugin-data-migration .mls-plugin-update-content .mls-plugin-update-text' );
						jQuery( '#spinning-wrapper' ).addClass( 'active' );

						var nonce  = jQuery( '.mls-plugin-data-migration [data-dismiss-nonce]' ).attr( 'data-dismiss-nonce' );
						jQuery.ajax({
							type: 'POST',
							url: '<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>',
							async: true,
							data: {
								action: 'mls_begin_migration',
								nonce : nonce,
							},
							success: function ( result ) {		
								setTimeout(function(){
									let intervalId = window.setInterval(function(){
										getMigrationStatus();
										if ( jQuery( '.mls-plugin-data-migration .mls-plugin-update-content .status' ).text( result.data ) == 'Completed' ) {
											clearInterval( intervalId );
										}
									}, 1000);
								}, 1000 );
							}
						});
					});
				});

				function getMigrationStatus() {
					jQuery.ajax({
						type: 'POST',
						url: '<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>',
						async: true,
						data: {
							action: 'mls_get_migration_status',
							nonce: '<?php echo \esc_js( \wp_create_nonce( 'mls_migration_status_nonce' ) ); ?>',
						},
						success: function ( result ) {		
							if ( result.data == 'Completed' ) {
								setTimeout(function() {
									jQuery( '.mls-plugin-data-migration .mls-plugin-update-content .mls-plugin-update-title' ).text( 'Migration complete' );
									jQuery( '.mls-plugin-data-migration .mls-plugin-update-content .status' ).remove();
									jQuery( '.mls-plugin-data-migration .mls-plugin-update-content .mls-plugin-update-text' ).html('All done, you may now continue as normal.');
									jQuery( '.mls-plugin-data-migration a' ).text( 'Close & continue' ).attr( 'href', '#close-migration' ).removeClass( 'mls-begin-migration' );								
									jQuery( '.mls-plugin-data-migration a' ).slideDown();
									jQuery( '#spinning-wrapper' ).removeClass( 'active' );
								}, 500 );
							} else {
								jQuery( '.mls-plugin-data-migration .mls-plugin-update-content .mls-plugin-update-title' ).text( 'Migration underway' );
								jQuery( '.mls-plugin-data-migration .mls-plugin-update-content .status' ).text( result.data );
							}
						}
					});
				}

				jQuery( 'body' ).on( 'click', 'a[href="#close-migration"]', function ( e ) {
					e.preventDefault();
					jQuery( '.mls-plugin-data-migration' ).slideUp();
				});
				//]]>
				</script>
				<?php
			}

			if ( ( in_array( $screen->base, self::PLUGIN_PAGES, true ) && $mls_migration_required && ! $migration_complete ) || ( in_array( $screen->base, self::PLUGIN_PAGES, true ) && $show_update_notice ) ) {
				?>
				<style type="text/css">
					/* Melapress brand font 'Quicksand' — There maybe be a preferable way to add this but this seemed the most discrete. */
					@font-face {
						font-family: 'Quicksand';
								src: url('<?php echo \esc_url( MLS_PLUGIN_URL ); ?>admin/assets/fonts/Quicksand-VariableFont_wght.woff2') format('woff2');
						font-weight: 100 900; /* This indicates that the variable font supports weights from 100 to 900 */
						font-style: normal;
					}
					
					.mls-plugin-update, .mls-plugin-data-migration {
						background-color: #482B15;
						border-radius: 7px;
						color: #fff;
						display: flex;
						justify-content: space-between;
						align-items: center;
						padding: 1.66rem;
						position: relative;
						overflow: hidden;
						transition: all 0.2s ease-in-out;
						margin-top: 20px;
						margin-right: 20px;
					}
				

					.mls-plugin-update-content {
						max-width: 45%;
					}
					
					.mls-plugin-update-title {
						margin: 0;
						font-size: 20px;
						font-weight: bold;
						font-family: Quicksand, sans-serif;
						line-height: 1.44rem;
						color: #fff;
					}
					
					.mls-plugin-update-text {
						margin: .25rem 0 0;
						font-size: 0.875rem;
						line-height: 1.3125rem;
					}
					
					.mls-plugin-update-text a:link {
						color: #FF8977;
					}
					
					.mls-cta-link {
						border-radius: 0.25rem;
						background: #FF8977;
						color: #0000EE;
						font-weight: bold;
						text-decoration: none;
						font-size: 0.875rem;
						padding: 0.675rem 1.3rem .7rem 1.3rem;
						transition: all 0.2s ease-in-out;
						display: inline-block;
						margin: .5rem auto;
					}
					
					.mls-cta-link:hover {
						background: #0000EE;
						color: #FF8977;
					}
					
					.mls-plugin-update-close {
						background-image: url(<?php echo \esc_url( MLS_PLUGIN_URL ) . 'admin/assets/images/close-icon-rev.svg'; ?>); /* Path to your close icon */
						background-size: cover;
						width: 18px;
						height: 18px;
						border: none;
						cursor: pointer;
						position: absolute;
						top: 20px;
						right: 20px;
						background-color: transparent;
					}
					
					.mls-plugin-update::before {
						content: '';
						background-image: url(<?php echo \esc_url( MLS_PLUGIN_URL ) . 'admin/assets/images/mls-updated-bg.png'; ?>); /* Background image only displayed on desktop */
						background-size: 100%;
						background-repeat: no-repeat;
						background-position: 100% 51%;
						position: absolute;
						top: 0;
						right: 0;
						bottom: 0;
						left: 0;
						z-index: 0;
					}
					
					.mls-plugin-update-content, .mls-plugin-update-close {
						z-index: 1;
					}
					
					@media (max-width: 1200px) {
						.mls-plugin-update::before {
							display: none;
						}
					
						.mls-plugin-update-content {
							max-width: 100%;
						}
					}

					.mls-plugin-data-migration {
						background-color: #D9E4FD;						
					}

					.mls-plugin-data-migration * {
						color: #1A3060;
					}

					.mls-plugin-data-migration .mls-plugin-update-content {
						min-height: 80px;
					}
						
					#spinning-wrapper {
						position: absolute;
						right: -20px;
						height: 300px;
						width: 300px;
					}

					#spinning-wrapper .dashicons {
						height: 300px;
						height: 300px;
						font-size: 300px;
					}

					#spinning-wrapper  * {
						color: #8AAAF1 !important;
					}

					#spinning-wrapper.active {
						-webkit-animation: spin 4s infinite linear;
					}

					@-webkit-keyframes spin {
						0%  {-webkit-transform: rotate(0deg);}
						100% {-webkit-transform: rotate(360deg);}   
					}
				</style>
				<?php
			}
		}

		/**
		 * Handle notice dismissal.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function begin_migration() {
			// Grab POSTed data.
			$nonce = isset( $_POST['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) ) : false;

			// Check nonce.
			// Network-scoped: runs UpdateRoutines across network options and usermeta.
			if ( ! OptionsHelper::current_user_can_manage_scope() || empty( $nonce ) || ! $nonce || ! \wp_verify_nonce( $nonce, 'mls_begin_migration_nonce' ) ) {
				\wp_send_json_error( \esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			\MLS\UpdateRoutines::update_from_pre_200();

			\wp_send_json_success( \esc_html__( 'Started', 'melapress-login-security' ) );
		}

		/**
		 * Get current migration status.
		 *
		 * @return object - Result.
		 *
		 * @since 2.0.0
		 */
		public static function get_migration_status() {
			// Reads a network option; scoped to match the writes beside it.
			if ( ! OptionsHelper::current_user_can_manage_scope() ) {
				\wp_send_json_error( \esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			/*
			 * Read-only, but still nonce-checked: it makes the contract the same
			 * as every other endpoint here rather than an exception a reviewer
			 * has to reason about, and it stops the status being polled
			 * cross-origin from a page the administrator happens to have open.
			 */
			$nonce = isset( $_POST['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) ) : '';

			if ( empty( $nonce ) || ! \wp_verify_nonce( $nonce, 'mls_migration_status_nonce' ) ) {
				\wp_send_json_error( \esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			$status = \get_site_option( 'mls_migration_status', false );
			if ( ! empty( $status ) ) {
				\wp_send_json_success( $status );
			}
			return \wp_send_json_success( \esc_html__( 'Process starting', 'melapress-login-security' ) );
		}

		/**
		 * Handle notice dismissal.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		/**
		 * Dismiss the feature banner shown after an upgrade.
		 *
		 * Separate from the changelog notice on purpose: they are two notices and
		 * closing one should not close the other.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function dismiss_feature_highlight() {
			$nonce = isset( $_POST['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) ) : false;

			// Network-scoped: deletes a network option.
			if ( ! OptionsHelper::current_user_can_manage_scope() || empty( $nonce ) || ! \wp_verify_nonce( $nonce, 'mls_dismiss_feature_highlight_nonce' ) ) {
				\wp_send_json_error( \esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			\delete_site_option( MLS_PREFIX . '_feature_highlight_needed' );

			\wp_send_json_success( \esc_html__( 'Complete.', 'melapress-login-security' ) );
		}

		public static function dismiss_update_notice() {
			// Grab POSTed data.
			$nonce = isset( $_POST['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) ) : false;

			// Check nonce.
			// Network-scoped: deletes a network option.
			if ( ! OptionsHelper::current_user_can_manage_scope() || empty( $nonce ) || ! $nonce || ! \wp_verify_nonce( $nonce, 'mls_dismiss_update_notice_nonce' ) ) {
				\wp_send_json_error( \esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			\delete_site_option( MLS_PREFIX . '_update_notice_needed' );

			\wp_send_json_success( \esc_html__( 'Complete.', 'melapress-login-security' ) );
		}

		/**
		 * Handles injecting content for a thickbox popup for special notice
		 * messages that people need to see when working with the settings of
		 * the plugin.
		 *
		 * Renders a hidden modal to be triggered when the page loads.
		 *
		 * NOTE: this code can be used to trigger it:
		 * if( jQuery('#notice_modal' ).length > 0 ) {
		 *     tb_show( jQuery('#notice_modal' ).data( 'windowTitle' ) , '#TB_inline?height=155&width=400&inlineId=notice_modal');
		 * }
		 *
		 * @method popup_notices
		 *
		 * @return mixed
		 *
		 * @since 2.0.0
		 */
		public static function popup_notices() {
			if ( is_array( self::$extra_notice_details ) && ! empty( self::$extra_notice_details ) ) {
				foreach ( self::$extra_notice_details as $notice ) {
					if ( ! isset( $notice['message'] ) ) {
						// no message to send, skip iteration.
						continue;
					}
					?>
					<div id="notice_modal" class="hidden"
						data-windowtitle="<?php echo ( isset( $notice['title'] ) ) ? \esc_attr( $notice['title'] ) : ''; ?>"
						data-redirect="<?php echo ( isset( $notice['redirect'] ) ) ? \esc_attr( $notice['redirect'] ) : ''; ?>"
						>
						<div class="notice_modal_wrapper">
							<p><?php echo \wp_kses_post( $notice['message'] ); ?></p>
							<?php
							if ( isset( $notice['buttons'] ) && ! empty( $notice['buttons'] ) ) {
								?>
								<div class="notice_modal_footer">
									<?php
									foreach ( $notice['buttons'] as $key => $button ) {
										?>
										<button type="button"
											class="<?php echo ( isset( $button['class'] ) ) ? \esc_attr( $button['class'] ) : ''; ?>"
											onClick="<?php echo ( isset( $button['onClick'] ) ) ? \esc_attr( $button['onClick'] ) : ''; ?>"
											>
												<?php echo \esc_html( $button['text'] ); ?>
											</button>
										<?php
									}
									?>
								</div>
								<?php
							}
							?>

						</div>
					</div>
					<?php
				}
			}

			?>
				<div id="mls_admin_lockout_notice_modal" class="hidden">
					<div class="notice_modal_wrapper">
						<p><?php \esc_html_e( 'To ensure you dont lock yourself out of your own dashboard, be sure to exclude your own admin account from password policies when enabling this feature.', 'melapress-login-security' ); ?></p>
						<div class="notice_modal_footer">
							<button type="button" class="button-primary" onclick="mls_close_thickbox()"><?php \esc_html_e( 'Acknowledge', 'melapress-login-security' ); ?></button>
						</div>
					</div>
				</div>
			<?php
		}

		/**
		 * Adds further links to the plugins action items.
		 *
		 * @param array $old_links - Original action links.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function plugin_action_links( $old_links ) {
			$new_links = array();

			if ( Licensing_Factory::provider_call( 'can_use_premium_code' ) ) {
				unset( $old_links['upgrade'] );
			} elseif ( ! Licensing_Factory::is_premium() ) {
				unset( $old_links['upgrade'] );
				$upgrade_link = '<a style="color: #dd7363; font-weight: bold;" class="mls-premium-link" target="_blank" href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugins&utm_medium=referral&utm_campaign=mls">' . \__( 'Get the Premium!', 'melapress-login-security' ) . '</a>';
				array_push( $new_links, $upgrade_link );
			} else {
				$upgrade_link = '<a style="color: #dd7363; font-weight: bold;" class="mls-premium-link" target="_blank" href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugins&utm_medium=referral&utm_campaign=mls">' . \__( 'Get the Premium!', 'melapress-login-security' ) . '</a>';
				array_push( $new_links, $upgrade_link );
			}

			$config_link = '<a href="' . \add_query_arg( 'page', MLS_MENU_SLUG, \network_admin_url( 'admin.php' ) ) . '">' . \__( 'Configure policies', 'melapress-login-security' ) . '</a>';
			array_push( $new_links, $config_link );

			$docs_link = '<a target="_blank" href="' . \add_query_arg(
				array(
					'utm_source'   => 'plugins',
					'utm_medium'   => 'link',
					'utm_campaign' => 'mls',
				),
				'https://melapress.com/support/kb/'
			) . '">' . \__( 'Docs', 'melapress-login-security' ) . '</a>';
			array_push( $new_links, $docs_link );

			return array_merge( $new_links, $old_links );
		}

		/**
		 * Register admin menu
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function admin_menu() {

			/*
			 * The menu title carries no notification bubble.
			 *
			 * It used to append an `awaiting-mod` counter when an update notice or
			 * a pending migration was outstanding — OptionsHelper::get_current_notices_count().
			 * That count is no longer surfaced here by request; the notices it
			 * referred to are still shown on the plugin's own screens.
			 *
			 * A side effect of removing it: the bubble branch built its title from
			 * a hardcoded 'Login Security' rather than a translated string, so the
			 * menu label was untranslatable whenever a notice was pending. Both
			 * cases now use the translated string.
			 */

			// Add admin menu page.
			$hook_name = \add_menu_page(
				\__( 'Login Security Policies', 'melapress-login-security' ),
				\__( 'Login Security', 'melapress-login-security' ),
				'manage_options',
				MLS_MENU_SLUG,
				array( __CLASS__, 'screen' ),
				' ', // 'data:image/svg+xml;base64,' . \base64_encode( \file_get_contents( MLS_PATH . 'assets/images/plugin-icon.svg' ) ),
				99
			);

			// Inject icon color styles without altering menu text color.
			\add_action( 'admin_head', array( __CLASS__, 'render_menu_icon_styles' ) );

			\add_action( "load-$hook_name", array( __CLASS__, 'admin_enqueue_scripts' ) );
			\add_action( "admin_head-$hook_name", array( __CLASS__, 'process' ) );

			\add_submenu_page( MLS_MENU_SLUG, \__( 'Login Security Policies', 'melapress-login-security' ), \__( 'Login Security Policies', 'melapress-login-security' ), 'manage_options', MLS_MENU_SLUG, array( __CLASS__, 'screen' ) );

			// Add admin submenu page.
			$hook_submenu = \add_submenu_page(
				MLS_MENU_SLUG,
				\__( 'Help & Contact Us', 'melapress-login-security' ),
				\__( 'Help & Contact Us', 'melapress-login-security' ),
				'manage_options',
				'mls-help',
				array(
					__CLASS__,
					'ppm_display_help_page',
				),
				90
			);

			\add_action( "load-$hook_submenu", array( __CLASS__, 'help_page_enqueue_scripts' ) );

			// Add admin submenu page for settings.
			$settings_hook_submenu = \add_submenu_page(
				MLS_MENU_SLUG,
				\__( 'Settings', 'melapress-login-security' ),
				\__( 'Settings', 'melapress-login-security' ),
				'manage_options',
				'mls-settings',
				array(
					__CLASS__,
					'ppm_display_settings_page',
				)
			);

			\add_action( "load-$settings_hook_submenu", array( __CLASS__, 'admin_enqueue_scripts' ) );
			\add_action( "admin_head-$settings_hook_submenu", array( __CLASS__, 'process' ) );

			// Add admin submenu page for form placement.
			$forms_hook_submenu = \add_submenu_page(
				MLS_MENU_SLUG,
				\__( 'Forms & Placement', 'melapress-login-security' ),
				\__( 'Forms & Placement', 'melapress-login-security' ),
				'manage_options',
				'mls-forms',
				array(
					__CLASS__,
					'ppm_display_forms_page',
				),
				1
			);

			\add_action( "load-$forms_hook_submenu", array( __CLASS__, 'admin_enqueue_scripts' ) );
			\add_action( "admin_head-$forms_hook_submenu", array( __CLASS__, 'process' ) );

			// Add admin submenu page for form placement.
			$hide_login_submenu = \add_submenu_page(
				MLS_MENU_SLUG,
				\__( 'Login page hardening', 'melapress-login-security' ),
				\__( 'Login page hardening', 'melapress-login-security' ),
				'manage_options',
				'mls-hide-login',
				array(
					__CLASS__,
					'ppm_display_hide_login_page',
				),
				2
			);

			\add_action( "load-$hide_login_submenu", array( __CLASS__, 'admin_enqueue_scripts' ) );
			\add_action( "admin_head-$hide_login_submenu", array( __CLASS__, 'process' ) );


			// @free:start
			$hook_upgrade_submenu = \add_submenu_page( MLS_MENU_SLUG, \esc_html__( 'Premium Features ➤', 'melapress-login-security' ), \esc_html__( 'Premium Features ➤', 'melapress-login-security' ), 'manage_options', 'mls-upgrade', array( __CLASS__, 'ppm_display_upgrade_page' ), 3 );
			\add_action( "load-$hook_upgrade_submenu", array( __CLASS__, 'help_page_enqueue_scripts' ) );
			// @free:end

			if ( ! \is_multisite() ) {
				// Add admin submenu page for temp logins.
				$temp_logins_submenu = \add_submenu_page(
					MLS_MENU_SLUG,
					\__( 'Temporary Logins', 'melapress-login-security' ),
					\__( 'Temporary Logins', 'melapress-login-security' ),
					'manage_options',
					'mls-temp-logins',
					'',
					5
				);
			}
		}

		/**
		 * Display help page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function ppm_display_help_page() {
			require_once 'templates/help/index.php';
		}

		/**
		 * Display settings page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function ppm_display_settings_page() {
			require_once 'templates/views/settings.php';
		}

		/**
		 * Display forms and placement settings page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function ppm_display_forms_page() {
			require_once 'templates/views/settings-forms.php';
		}

		/**
		 * Display forms and placement settings page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function ppm_display_hide_login_page() {
			require_once 'templates/views/settings-hide-login.php';
		}

		// @free:start
		/**
		 * Display help page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function ppm_display_upgrade_page() {
			require_once MLS_PATH . 'admin/templates/help/upgrade.php';
		}
		// @free:end

		/**
		 * Melapress Login Security onload process
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function process() {
			if ( ! isset( $_POST[ MLS_PREFIX . '_nonce' ] ) ) {
				return;
			}

			/*
			 * Capability first, then the nonce.
			 *
			 * This handler only verified the nonce. It is reached from admin
			 * screens that are themselves capability-gated, so no unauthorised
			 * path was open — but that made the guarantee a property of where the
			 * hook happens to be registered rather than of the handler, and a
			 * nonce proves intent, not permission. Asserting the capability here
			 * means a future refactor, a reused hook or a direct call cannot
			 * quietly turn a privileged writer loose.
			 *
			 * The scoped predicate rather than a bare `manage_options`: on a
			 * network these writes are network options, which a single site's
			 * administrator must not be able to change.
			 */
			if ( ! OptionsHelper::current_user_can_manage_scope() ) {
				return;
			}

			if ( ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ MLS_PREFIX . '_nonce' ] ) ), MLS_PREFIX . '_nonce_form' ) ) {
				return;
			}

			self::save();
		}

		/**
		 * Render PPM dashboard screen
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function screen() {
			include_once MLS_PATH . 'admin/templates/admin-form.php';
		}

		/**
		 * Melapress Login Securityverify wp nonce
		 *
		 * @return bool return
		 *
		 * @since 2.0.0
		 */
		public static function validate() {
			return isset( $_POST[ MLS_PREFIX . '_nonce' ] ) ? \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ MLS_PREFIX . '_nonce' ] ) ), MLS_PREFIX . '_nonce_form' ) : false;
		}

		/**
		 * Save settings values.
		 *
		 * @param string $settings_type - Thing to save.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function save( $settings_type = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			$known_contexts = array(
				'mls-settings',
				'mls-policies',
				'mls-forms',
				'mls-reports',
				'mls-locked-users',
				'mls-hide-login',
			);

			/*
			 * Capability first, and asserted here rather than only in process().
			 * This is a public static writer: the registered call path checks the
			 * capability, but that only holds while every caller remembers to. The
			 * check belongs with the write, not with one of its callers.
			 */
			if ( ! OptionsHelper::current_user_can_manage_scope() ) {
				return;
			}

			$current_context = isset( $_REQUEST['page'] ) ? \sanitize_key( \wp_unslash( $_REQUEST['page'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! $current_context || ! in_array( $current_context, $known_contexts, true ) ) {
				return;
			}

			// Validate the nonce.
			if ( ! self::validate() ) {
				self::notice( 'admin_save_error_notice' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			$mls = melapress_login_security();

			// If check policies inherit or not.
			//
			// The value arrives as a string — 'yes'/'no' from the stored option,
			// or '1'/'0' once the settings JS has touched the hidden field. It
			// was compared against the integer 1, which a string can never be
			// identical to, so this branch had never run: choosing "inherit"
			// silently left the role's own policies in place and overriding the
			// site-wide ones.
			// Only ever applies to a role tab. The site-wide policy has nothing
			// to inherit from, and its own hidden field also defaults to "yes"
			// — acting on that would delete the site-wide policy itself.
			$inherit_role = isset( $_POST['mls_options']['ppm-user-role'] ) ? \sanitize_text_field( \wp_unslash( $_POST['mls_options']['ppm-user-role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$inherit_requested = '' !== $inherit_role
				&& isset( $_POST['mls_options']['inherit_policies'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
				&& OptionsHelper::string_to_bool( \sanitize_text_field( \wp_unslash( $_POST['mls_options']['inherit_policies'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			/*
			 * Coming out of "do not enforce password & login policies for this
			 * role" puts the role back on the site-wide policy.
			 *
			 * While the exemption is on, its change handler disables every field
			 * in the form, and a browser does not submit disabled fields — so the
			 * save that turns the exemption on stores the role's policy as
			 * whatever the defaults are, which is correct enough while nothing is
			 * being enforced. Turning it off again used to leave exactly that
			 * behind: a role that was no longer exempt, enforced nothing of its
			 * own, and did not inherit either, so unticking a box that reads
			 * "start enforcing this role again" left the role enforcing nothing.
			 *
			 * Inheriting is the safe reading of the untick, and it is the state
			 * the role tab starts in.
			 */
			if ( ! $inherit_requested && '' !== $inherit_role ) {
				$stored_policy = \get_site_option( MLS_PREFIX . '_' . $inherit_role . '_options' );

				$was_exempt = is_array( $stored_policy )
					&& isset( $stored_policy['enforce_password'] )
					&& OptionsHelper::string_to_bool( $stored_policy['enforce_password'] );

				// An unticked checkbox is simply absent from the request.
				$still_exempt = isset( $_POST['mls_options']['enforce_password'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
					&& OptionsHelper::string_to_bool( \sanitize_text_field( \wp_unslash( $_POST['mls_options']['enforce_password'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( $was_exempt && ! $still_exempt ) {
					\delete_site_option( MLS_PREFIX . '_' . $inherit_role . '_options' );

					self::$setting_tab = (object) $mls->options->inherit;
					self::notice( 'admin_save_success_notice' );

					// Same reason as the branch below: everything after this
					// re-saves the submitted policy and would recreate the row.
					return;
				}
			}

			if ( $inherit_requested ) {
				// Delete the role option so it falls back to the site-wide policy.
				\delete_site_option( MLS_PREFIX . '_' . $inherit_role . '_options' );
				// Reassign setting open.
				self::$setting_tab = (object) $mls->options->inherit;
				// Success notice.
				self::notice( 'admin_save_success_notice' );

				// Stop here. Everything below re-saves the submitted policy,
				// which would immediately recreate the role option that was
				// just deleted. The previous `unset( $_POST['mls_options'] )`
				// could not prevent that: the code below reads the request with
				// filter_input_array(), which returns the original request data
				// and is unaffected by changes to $_POST.
				return;
			}

			$post_array = \filter_input_array( INPUT_POST );
			$settings   = isset( $post_array['mls_options'] ) ? $post_array['mls_options'] : array();

			// Forms admin area.
			if ( 'mls-forms' === $current_context ) {
				$settings['enable_wp_reset_form']          = isset( $settings['enable_wp_reset_form'] );
				$settings['enable_wp_profile_form']        = isset( $settings['enable_wp_profile_form'] );
				$settings['enable_wc_pw_reset']            = isset( $settings['enable_wc_pw_reset'] );
				$settings['enable_wc_checkout_reg']        = isset( $settings['enable_wc_checkout_reg'] );
				$settings['enable_bp_register']            = isset( $settings['enable_bp_register'] );
				$settings['enable_bp_pw_update']           = isset( $settings['enable_bp_pw_update'] );
				$settings['enable_ld_register']            = isset( $settings['enable_ld_register'] );
				$settings['enable_um_register']            = isset( $settings['enable_um_register'] );
				$settings['enable_um_pw_update']           = isset( $settings['enable_um_pw_update'] );
				$settings['enable_bbpress_pw_update']      = isset( $settings['enable_bbpress_pw_update'] );
				$settings['enable_mepr_register']          = isset( $settings['enable_mepr_register'] );
				$settings['enable_mepr_pw_update']         = isset( $settings['enable_mepr_pw_update'] );
				$settings['enable_edd_register']           = isset( $settings['enable_edd_register'] );
				$settings['enable_edd_pw_update']          = isset( $settings['enable_edd_pw_update'] );
				$settings['enable_pmp_register']           = isset( $settings['enable_pmp_register'] );
				$settings['enable_pmp_pw_update']          = isset( $settings['enable_pmp_pw_update'] );
				$settings['enable_pmp_pw_reset']           = isset( $settings['enable_pmp_pw_reset'] );
				$settings['enable_profilepress_register']  = isset( $settings['enable_profilepress_register'] );
				$settings['enable_profilepress_pw_reset']  = isset( $settings['enable_profilepress_pw_reset'] );
				$settings['enable_profilepress_pw_update'] = isset( $settings['enable_profilepress_pw_update'] );

				$mls_setting = OptionsHelper::recursive_parse_args( $settings, $mls->options->mls_setting );

				if ( self::$options->mls_save_setting( $mls_setting ) ) {
					self::notice( 'admin_save_success_notice' );
				}

				return;
			}

			// Settings area.
			if ( 'mls-settings' === $current_context ) {
				$settings['exempted']['users']                          = self::decode_js_var( $settings['exempted']['users'] );
				$settings['terminate_session_password']                 = isset( $settings['terminate_session_password'] );
				$settings['send_summary_email']                         = isset( $settings['send_summary_email'] );
				$settings['stop_pw_generate']                           = isset( $settings['stop_pw_generate'] );
				$settings['users_have_multiple_roles']                  = isset( $settings['users_have_multiple_roles'] );
				$settings['multiple_role_order']                        = explode( ',', $settings['multiple_role_order'] );
				$settings['disable_user_password_reset_email']          = isset( $settings['disable_user_password_reset_email'] );
				$settings['disable_user_delayed_password_reset_email']  = isset( $settings['disable_user_delayed_password_reset_email'] );
				$settings['disable_user_pw_expired_email']              = isset( $settings['disable_user_pw_expired_email'] );
				$settings['disable_device_policies_prompt_email']       = isset( $settings['disable_device_policies_prompt_email'] );
				$settings['disable_device_policies_prompt_admin_email'] = isset( $settings['disable_device_policies_prompt_admin_email'] );
				$settings['disable_user_imported_email']                = isset( $settings['disable_user_imported_email'] );
				$settings['disable_user_imported_forced_reset_email']   = isset( $settings['disable_user_imported_forced_reset_email'] );
				$settings['disable_user_unlocked_email']                = isset( $settings['disable_user_unlocked_email'] );
				$settings['disable_user_unlocked_reset_needed_email']   = isset( $settings['disable_user_unlocked_reset_needed_email'] );
				$settings['disable_multiple_sessions_email']            = isset( $settings['disable_multiple_sessions_email'] );
				$settings['send_plain_text_emails']                     = isset( $settings['send_plain_text_emails'] );

				if ( ! isset( $settings['clear_history'] ) ) {
					$settings['clear_history'] = 0;
				}

				$ok_to_save = true;

				/**
				 * Validates the input based on the rules defined in the @see MLS_Options::$settings_options_validation_rules
				 */
				foreach ( \MLS\MLS_Options::$settings_options_validation_rules as $key => $valid_rules ) {

					if ( is_array( $valid_rules ) && ! isset( $valid_rules['typeRule'] ) ) {
						foreach ( $valid_rules as $field_name => $rule ) {
							if ( isset( $_POST['mls_options'][ $key ][ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
								if ( ! Validator_Factory::validate( \sanitize_text_field( \wp_unslash( $_POST['mls_options'][ $key ][ $field_name ] ) ), $rule ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
									self::notice( 'admin_save_error_notice' );
									$ok_to_save = false;
								}
							}
						}
					} elseif ( isset( $_POST['mls_options'][ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
							$rule = $valid_rules;
						if ( ! Validator_Factory::validate( \sanitize_text_field( \wp_unslash( $_POST['mls_options'][ $key ] ) ), $rule ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
							self::notice( 'admin_save_error_notice' );
							$ok_to_save = false;
						}
					}
				}

				if ( $ok_to_save ) {
					$mls_setting = OptionsHelper::recursive_parse_args( $settings, $mls->options->mls_setting );

					if ( self::$options->mls_save_setting( $mls_setting ) ) {
						self::notice( 'admin_save_success_notice' );
					}
				}
				return;
			}

			// Login hardening.
			if ( 'mls-hide-login' === $current_context ) {
				$settings['custom_login_url']                 = isset( $settings['custom_login_url'] ) ? preg_replace( '/[^-\w,]/', '', $settings['custom_login_url'] ) : $mls->options->mls_setting->custom_login_url;
				$settings['custom_login_redirect']            = isset( $settings['custom_login_redirect'] ) ? preg_replace( '/[^-\w,]/', '', $settings['custom_login_redirect'] ) : $mls->options->mls_setting->custom_login_redirect;
				$settings['enable_gdpr_banner']               = isset( $settings['enable_gdpr_banner'] );
				$settings['enable_login_allowed_ips']         = isset( $settings['enable_login_allowed_ips'] );
				$settings['enable_failure_message_overrides'] = isset( $settings['enable_failure_message_overrides'] );

				// Sanitize GDPR banner message.
				if ( isset( $settings['gdpr_banner_message'] ) ) {
					$settings['gdpr_banner_message'] = wp_kses_post( $settings['gdpr_banner_message'] );
				}

				/*
				 * Both of these are paths relative to the site root, not URLs —
				 * the field is rendered inside site_url() and the redirect is
				 * built as '/' . rtrim( $value, '/' ). esc_url_raw() was adding
				 * a scheme to anything that had none, so a bare "reception" was
				 * stored as "http://reception" and the redirect it produced was
				 * one wp_safe_redirect() refuses.
				 */
				foreach ( array( 'restrict_login_redirect_url', 'login_geo_redirect_url' ) as $path_field ) {
					if ( isset( $settings[ $path_field ] ) ) {
						$settings[ $path_field ] = OptionsHelper::sanitize_login_redirect_path( $settings[ $path_field ] );
					}
				}

				// Validate IP addresses.
				if ( isset( $settings['restrict_login_allowed_ips'] ) && '' !== $settings['restrict_login_allowed_ips'] ) {
					$ips       = array_map( 'trim', explode( ',', $settings['restrict_login_allowed_ips'] ) );
					$valid_ips = array_filter( $ips, function ( $ip ) {
						return filter_var( $ip, FILTER_VALIDATE_IP );
					} );
					$settings['restrict_login_allowed_ips'] = implode( ',', $valid_ips );
				}

				// Validate country codes — must be exactly two uppercase letters.
				if ( isset( $settings['login_geo_countries'] ) && '' !== $settings['login_geo_countries'] ) {
					$codes       = array_map( 'trim', explode( ',', $settings['login_geo_countries'] ) );
					$valid_codes = array_filter( $codes, function ( $code ) {
						return preg_match( '/^[A-Z]{2}$/', $code );
					} );
					$settings['login_geo_countries'] = implode( ',', $valid_codes );
				}

				$mls_setting = OptionsHelper::recursive_parse_args( $settings, $mls->options->mls_setting );

				if ( self::$options->mls_save_setting( $mls_setting ) ) {
					self::notice( 'admin_save_success_notice' );
				}
				return;
			}

			// Policies area.
			if ( 'mls-policies' === $current_context ) {

				/*
				 * Set once, here, and only ever cleared below. It used to be
				 * reset to true partway through, which threw away the failures
				 * already recorded for the inactive-user and session expiry
				 * fields: the administrator saw the "required field" error and
				 * the policy was saved anyway, with an empty expiry.
				 */
				$ok_to_save = true;

				if ( ! isset( $_POST['mls_options']['disable_self_reset_message'] ) || empty( $_POST['mls_options']['disable_self_reset_message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['disable_self_reset_message'] = \__( 'You are not allowed to reset your password. Please contact the website administrator.', 'melapress-login-security' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}

				if ( ! isset( $_POST['mls_options']['locked_user_disable_self_reset_message'] ) || empty( $_POST['mls_options']['locked_user_disable_self_reset_message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['locked_user_disable_self_reset_message'] = \__( 'You are not allowed to reset your password. Please contact the website administrator.', 'melapress-login-security' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}

				if ( ! isset( $_POST['mls_options']['user_unlocked_email_title'] ) || empty( $_POST['mls_options']['user_unlocked_email_title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['disable_self_reset_message'] = \__( 'You are not allowed to reset your password. Please contact the website administrator.', 'melapress-login-security' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}

				if ( ! isset( $_POST['mls_options']['inactive_users_enabled'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['inactive_users_enabled'] = 0;
				} else {
					$_POST['mls_options']['inactive_users_enabled'] = 1;
					// add the current user to the inactive exempt list if that list
					// is empty.
					$added = OptionsHelper::add_initial_user_to_exempt_list( \wp_get_current_user() );
					if ( $added ) {
						$args = array(
							'page' => 'mls-settings',
						);
						$url  = \add_query_arg( $args, \network_admin_url( 'admin.php' ) );
						// add details to output for the modal popup.
						self::$extra_notice_details[] = array(
							'title'    => \__( 'User Added to Exempt List', 'melapress-login-security' ),
							'message'  => \__( 'Your user has been exempted from the all policies since there must be at least one excluded user to avoid all users being locked out. You can change this from the plugin\'s settings.', 'melapress-login-security' ),
							'redirect' => \add_query_arg(
								array(
									'page' => 'mls-settings',
									'tab'  => 'setting',
								),
								network_admin_url( 'admin.php' )
							),
							'buttons'  => array(
								array(
									'text'    => \__( 'View settings', 'melapress-login-security' ),
									'class'   => 'button-primary',
									'onClick' => 'mls_close_thickbox("' . $url . '")',
								),
							),
						);
					}

					if ( empty( $_POST['mls_options']['inactive_users_expiry']['value'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						self::notice( 'admin_save_error_required_field_notice' );
						$ok_to_save = false;
					} else {
							$_POST['mls_options']['inactive_users_expiry']['value'] = \sanitize_text_field( \wp_unslash( $_POST['mls_options']['inactive_users_expiry']['value'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}
				}

				if ( ! isset( $_POST['mls_options']['enable_sessions_policies'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['enable_sessions_policies'] = 0;
				} else {
					$_POST['mls_options']['enable_sessions_policies'] = 1;
					if ( empty( $_POST['mls_options']['default_session_expiry']['value'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						self::notice( 'admin_save_error_required_field_notice' );
						$ok_to_save = false;
					} else {
							$_POST['mls_options']['default_session_expiry']['value'] = \sanitize_text_field( \wp_unslash( $_POST['mls_options']['default_session_expiry']['value'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}
					if ( empty( $_POST['mls_options']['remember_session_expiry']['value'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						self::notice( 'admin_save_error_required_field_notice' );
						$ok_to_save = false;
					} else {
							$_POST['mls_options']['remember_session_expiry']['value'] = \sanitize_text_field( \wp_unslash( $_POST['mls_options']['remember_session_expiry']['value'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}
				}

				// Sanitize recognized device duration to allowed values only.
				if ( isset( $_POST['mls_options']['recognized_device_duration'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$allowed_durations = array( '1_month', '3_months', '6_months', '1_year' );
					$submitted         = \sanitize_text_field( \wp_unslash( $_POST['mls_options']['recognized_device_duration'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( ! in_array( $submitted, $allowed_durations, true ) ) {
						$_POST['mls_options']['recognized_device_duration'] = '1_year'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}
				}

				if ( isset( $_POST['mls_options']['restrict_login_credentials'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$allowed = array( 'default', 'email-only', 'username-only' );
					$submitted = \sanitize_text_field( \wp_unslash( $_POST['mls_options']['restrict_login_credentials'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( ! in_array( $submitted, $allowed, true ) ) {
						$_POST['mls_options']['restrict_login_credentials'] = 'default'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}
				}

				if ( isset( $_POST['mls_options']['failed_login_unlock_setting'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$allowed = array( 'unlock-by-admin', 'timed' );
					$submitted = \sanitize_text_field( \wp_unslash( $_POST['mls_options']['failed_login_unlock_setting'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( ! in_array( $submitted, $allowed, true ) ) {
						$_POST['mls_options']['failed_login_unlock_setting'] = 'unlock-by-admin'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}
				}

				if ( ( isset( $_POST['mls_options']['min_length'] ) && empty( $_POST['mls_options']['min_length'] ) ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing
					( isset( $_POST['mls_options']['password_expiry'] ) && empty( $_POST['mls_options']['password_expiry']['value'] ) && intval( $_POST['mls_options']['password_expiry']['value'] ) !== 0 ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing
					( isset( $_POST['mls_options']['password_history'] ) && empty( $_POST['mls_options']['password_history'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
					) {
					self::notice( 'admin_save_error_required_field_notice' );
					$ok_to_save = false;
				}

				if ( isset( $_POST['mls_options']['ui_rules']['exclude_special_chars'] ) && intval( $_POST['mls_options']['ui_rules']['exclude_special_chars'] ) !== 0 && empty( $_POST['mls_options']['excluded_special_chars'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					// Auto-disable the feature if no chars are specified, preventing an unsaveable state.
					$_POST['mls_options']['ui_rules']['exclude_special_chars'] = '0'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$settings['ui_rules']['exclude_special_chars']             = '0';
				}

				$min_req_security_questions = isset( $_POST['mls_options']['min_answered_needed_count'] ) ? intval( $_POST['mls_options']['min_answered_needed_count'] ) : 3; // phpcs:ignore WordPress.Security.NonceVerification.Missing

				// Clamp min_answered_needed_count to 0-10; disable feature if 0.
				$min_req_security_questions = max( 0, min( 10, $min_req_security_questions ) );
				if ( 0 === $min_req_security_questions ) {
					$settings['enable_security_questions']          = 'no';
					$_POST['mls_options']['enable_security_questions'] = 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}
				$settings['min_answered_needed_count']          = $min_req_security_questions;
				$_POST['mls_options']['min_answered_needed_count'] = $min_req_security_questions; // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( isset( $_POST['mls_options']['enable_sessions_policies'] ) && ! empty( $_POST['mls_options']['enable_sessions_policies'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( isset( $_POST['mls_options']['enabled_questions'] ) && ! empty( $_POST['mls_options']['enabled_questions'] ) && count( $_POST['mls_options']['enabled_questions'] ) < $min_req_security_questions ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						self::notice( 'admin_save_error_not_enough_questions_provided_notice' );
						$ok_to_save = false;
					}
				}

				/**
				 * Validates the input based on the rules defined in the @see MLS_Options::$default_options_validation_rules
				 */
				foreach ( \MLS\MLS_Options::$default_options_validation_rules as $key => $valid_rules ) {

					if ( is_array( $valid_rules ) && ! isset( $valid_rules['typeRule'] ) ) {
						foreach ( $valid_rules as $field_name => $rule ) {
							if ( isset( $_POST['mls_options'][ $key ][ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
								if ( ! Validator_Factory::validate( \sanitize_text_field( \wp_unslash( $_POST['mls_options'][ $key ][ $field_name ] ) ), $rule ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
									self::notice( 'admin_save_error_notice' );
									$ok_to_save = false;
								}
							}
						}
					} elseif ( isset( $_POST['mls_options'][ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

							$rule = $valid_rules;
						if ( ! Validator_Factory::validate( \sanitize_text_field( \wp_unslash( $_POST['mls_options'][ $key ] ) ), $rule ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
							self::notice( 'admin_save_error_notice' );
							$ok_to_save = false;
						}
					}
				}

				// Ensure slashes (which can be added when a " is excluded) are removed prior to saving.
				if ( isset( $settings['excluded_special_chars'] ) ) {
					$settings['excluded_special_chars'] = stripslashes( $settings['excluded_special_chars'] );
				}

				// Turn bools into yes/no.
				$settings_updated = array();
				// Process main options.
				foreach ( \MLS\MLS_Options::$policy_boolean_options as $main_bool ) {
					$bool_to_check                  = ( isset( $settings[ $main_bool ] ) ) ? $settings[ $main_bool ] : false;
					$settings_updated[ $main_bool ] = OptionsHelper::bool_to_string( $bool_to_check );
				}
				// Process UI options.
				foreach ( \MLS\MLS_Options::$password_ui_boolean_options as $ui_bool ) {
					$settings['ui_rules'][ $ui_bool ]         = isset( $settings['ui_rules'][ $ui_bool ] ) && ! in_array( $settings['ui_rules'][ $ui_bool ], array( 0, '0', false, '' ), true );
					$bool_to_check                            = ( isset( $settings['ui_rules'][ $ui_bool ] ) ) ? $settings['ui_rules'][ $ui_bool ] : false;
					$settings_updated['ui_rules'][ $ui_bool ] = OptionsHelper::bool_to_string( $bool_to_check );
				}
				// Process PW options.
				foreach ( \MLS\MLS_Options::$password_rules_boolean_options as $pw_rules_bool ) {
					$bool_to_check                               = ( isset( $settings['rules'][ $pw_rules_bool ] ) ) ? $settings['rules'][ $pw_rules_bool ] : false;
					$settings_updated['rules'][ $pw_rules_bool ] = OptionsHelper::bool_to_string( $bool_to_check );
				}

				/*
				 * Keep the notification lead time inside the expiry period.
				 *
				 * Both fields are a number plus an independent unit, and this used
				 * to compare the numbers alone: an expiry of 2 months with a
				 * notification 5 days before it read as 5 >= 2 and silently
				 * rewrote the notification to 2 — still in days. Any configuration
				 * whose notification number exceeded the expiry number was
				 * affected, which is most of them once expiry is expressed in
				 * months.
				 *
				 * It also ran when expiry was switched off. With an expiry value of
				 * 0 every save of this page — including one made for an unrelated
				 * setting — forced the notification to 0 and switched the
				 * notification off, destroying a perfectly good stored value.
				 *
				 * Both halves are fixed by converting to seconds before comparing
				 * and by not clamping at all when there is no expiry period to
				 * measure against.
				 */
				if ( isset( $_POST['mls_options']['notify_password_expiry_days'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$expiry_value = isset( $_POST['mls_options']['password_expiry']['value'] ) ? (int) $_POST['mls_options']['password_expiry']['value'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$expiry_unit  = isset( $_POST['mls_options']['password_expiry']['unit'] ) ? \sanitize_text_field( \wp_unslash( $_POST['mls_options']['password_expiry']['unit'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$notify_value = (int) $_POST['mls_options']['notify_password_expiry_days']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$notify_unit  = isset( $_POST['mls_options']['notify_password_expiry_unit'] ) ? \sanitize_text_field( \wp_unslash( $_POST['mls_options']['notify_password_expiry_unit'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

					$clamped = OptionsHelper::clamped_expiry_notification( $notify_value, $notify_unit, $expiry_value, $expiry_unit );

					if ( null !== $clamped ) {
						$settings_updated['notify_password_expiry_days'] = $clamped['value'];
						$settings_updated['notify_password_expiry_unit'] = $clamped['unit'];
					}
				}

				if ( ! isset( $_POST['mls_options']['activate_password_expiration_policies'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['activate_password_expiration_policies'] = 0;
				} else {
					$_POST['mls_options']['activate_password_expiration_policies'] = 1;
					// add the current user to the inactive exempt list if that list
					// is empty.
					$added = OptionsHelper::add_initial_user_to_exempt_list( \wp_get_current_user() );
					if ( $added ) {
						$args = array(
							'page' => 'mls-settings',
						);
						$url  = \add_query_arg( $args, \network_admin_url( 'admin.php' ) );
						// add details to output for the modal popup.
						self::$extra_notice_details[] = array(
							'title'    => \__( 'User Added to Exempt List', 'melapress-login-security' ),
							'message'  => \__( 'Your user has been exempted from the all policies since there must be at least one excluded user to avoid all users being locked out. You can change this from the plugin\'s settings.', 'melapress-login-security' ),
							'redirect' => \add_query_arg(
								array(
									'page' => 'mls-settings',
									'tab'  => 'setting',
								),
								network_admin_url( 'admin.php' )
							),
							'buttons'  => array(
								array(
									'text'    => \__( 'View settings', 'melapress-login-security' ),
									'class'   => 'button-primary',
									'onClick' => 'mls_close_thickbox("' . $url . '")',
								),
							),
						);
					}
				}

				// Process reset blocked message.
				$settings_updated['disable_self_reset_message']             = ( ! empty( $settings['disable_self_reset_message'] ) ) ? \sanitize_textarea_field( $settings['disable_self_reset_message'] ) : false;
				$settings_updated['locked_user_disable_self_reset_message'] = ( ! empty( $settings['locked_user_disable_self_reset_message'] ) ) ? \sanitize_textarea_field( $settings['locked_user_disable_self_reset_message'] ) : false;
				$settings_updated['deactivated_account_message']            = ( isset( $settings['deactivated_account_message'] ) && ! empty( $settings['deactivated_account_message'] ) ) ? \wp_kses_post( $settings['deactivated_account_message'] ) : trim( \MLS\MLS_Options::get_default_account_deactivated_message() );
				$settings_updated['timed_login_message']                    = ( ! empty( $settings['timed_login_message'] ) ) ? \sanitize_textarea_field( $settings['timed_login_message'] ) : false;


				$processedmls_options = \apply_filters( 'mls_pre_option_save_validation', array_merge( $settings, $settings_updated ) );

				if ( $ok_to_save ) {
					if ( self::$options->mls_save_policy( $processedmls_options ) ) {
						self::$setting_tab = (object) self::$options->setting_options;
						self::notice( 'admin_save_success_notice' );
					}
				}
			}
		}

		/**
		 * Validate a list of site users for the inactive exempted list.
		 *
		 * Accepts a CSV string of usernames, checks they exist and returns
		 * only those that are real users.
		 *
		 * @method validate_inactive_exempted
		 * @param  string $users_string CSV string of usernames.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public static function validate_inactive_exempted( $users_string ) {
			$users_array  = array();
			$users_string = (string) $users_string;
			$users        = explode( ',', $users_string );
			foreach ( $users as $username ) {
				$user = \get_user_by( 'login', trim( $username ) );
				if ( is_a( $user, '\WP_User' ) ) {
					$users_array[ $user->ID ] = $user->data->user_login;
				}
			}
			return $users_array;
		}

		/**
		 * Admin notice.
		 *
		 * @param  string $callback_function Callback function.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function notice( $callback_function ) {
			\add_action( 'admin_notices', array( __CLASS__, $callback_function ) );
		}

		/**
		 * Enqueue script for help page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function help_page_enqueue_scripts() {
			\wp_enqueue_style( 'mls-help', MLS_PLUGIN_URL . 'admin/assets/css/help.css', array(), MLS_VERSION );
		}

		/**
		 * Render the admin menu icon styles.
		 *
		 * Outputs the CSS for the MLS menu icon in the WordPress admin sidebar.
		 * This is a shared method called by both the main admin menu registration
		 * and the EDD license page when no valid license exists.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function render_menu_icon_styles() {
			$svg_default = MLS_PLUGIN_URL . 'assets/images/plugin-icon-default.svg';
			?>
			<style>
				#toplevel_page_mls-policies a div::before {
					content: "";
					display: inline-block;
					width: 20px;
					height: 20px;
					mask: url(<?php echo \esc_url( $svg_default ); ?>) no-repeat center;
					-webkit-mask: url(<?php echo \esc_url( $svg_default ); ?>) no-repeat center;
					background-color: #f0f6fc;
					opacity: 0.6;
				}
				#toplevel_page_mls-policies a:hover div::before,
				#toplevel_page_mls-policies a.wp-menu-open div::before {
					content: "";
					display: inline-block;
					width: 20px;
					height: 20px;
					mask: url(<?php echo \esc_url( $svg_default ); ?>) no-repeat center;
					-webkit-mask: url(<?php echo \esc_url( $svg_default ); ?>) no-repeat center;
					background-color: #a7aaad;
					opacity: 1;
				}
				#toplevel_page_mls-policies a div.wp-menu-name::before {
					mask-size: contain !important;
					-webkit-mask-size: contain !important;
					content: '' !important;
					-webkit-mask: none !important;
					mask-size: 0 !important;
					background-color: transparent !important;
					margin-left: -20px !important;
					margin-top: -10px !important;
				}
			</style>
			<?php
		}

		/**
		 * Enqueue admin scripts & styles on plugin pages.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function admin_enqueue_scripts() {
			$mls = melapress_login_security();
			\add_thickbox();
			\wp_enqueue_script( 'jquery-ui-dialog' );

			// enqueue plugin JS.
			\wp_enqueue_style( 'ppm-wp-settings-css', MLS_PLUGIN_URL . 'admin/assets/css/settings.css', array(), MLS_VERSION );
			\wp_enqueue_script( 'ppm-wp-settings', MLS_PLUGIN_URL . 'admin/assets/js/settings.js', array( 'jquery-ui-autocomplete', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), MLS_VERSION, true );
			$session_setting = isset( $mls->options->mls_setting->terminate_session_password ) ? $mls->options->mls_setting->terminate_session_password : $mls->options->default_setting->terminate_session_password;
			\wp_localize_script(
				'ppm-wp-settings',
				'ppm_ajax',
				array(
					'ajax_url'                   => \admin_url( 'admin-ajax.php' ),
					'test_email_nonce'           => \wp_create_nonce( 'send_test_email' ),
					'settings_nonce'             => \wp_create_nonce( 'mls-policies' ),
					'terminate_session_password' => OptionsHelper::string_to_bool( $session_setting ),
					'special_chars_regex'        => \MLS_Core::get_special_chars( true ),
					'reset_done_title'           => __( 'Reset process complete', 'melapress-login-security' ),
					'csv_error'                  => __( 'CSV contains invalid data, provide user IDs only.', 'melapress-login-security' ),
					'csv_file_error'             => __( 'Please provide the correct file type only.', 'melapress-login-security' ),
					'csv_error_length'           => __( 'Please ensure more than 1 ID is provided.', 'melapress-login-security' ),
					'reset_done_text'            => __( 'You may now close this window.', 'melapress-login-security' ),
				)
			);
			\do_action( 'mls_enqueue_admin_scripts' );
			\wp_localize_script(
				'ppm-wp-settings',
				'ppmwpSettingsStrings',
				array(
					'resetPasswordsDelayedMessage'   => __( 'This will reset the passwords of all users on this site. Users have to change their password once they logout and log back in. Are you sure?', 'melapress-login-security' ),
					'resetPasswordsInstantlyMessage' => __( 'This will reset the passwords of all users on this site and terminate their sessions instantly. Are you sure?', 'melapress-login-security' ),
					'resetOwnPasswordMessage'        => __( 'Should the plugin reset your password as well?', 'melapress-login-security' ),
				)
			);

			// Ensure global.js is loaded on settings pages for the short-password warning dialog.
			self::global_admin_enqueue_scripts();
		}

		/**
		 * Global admin enqueue scripts.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function global_admin_enqueue_scripts() {
			if ( ! \wp_style_is( 'wp-jquery-ui-dialog', 'queue' ) ) {
				\wp_enqueue_style( 'wp-jquery-ui-dialog' );
			}

			// Global JS — depends on jquery-ui-dialog for the session-expired / short-password dialogs.
			\wp_enqueue_script( 'ppm-wp-global', MLS_PLUGIN_URL . 'admin/assets/js/global.js', array( 'jquery', 'jquery-ui-dialog' ), MLS_VERSION, true );
			\wp_localize_script(
				'ppm-wp-global',
				'ppmwpGlobalStrings',
				array(
					'emailResetInstructions' => \__( 'Please check your email for instructions on how to reset your password.', 'melapress-login-security' ),
					'shortPasswordMessage'   => \__( 'By setting the minimum number of characters in passwords to less than 6 you\'re encouraging weak passwords and polices cannot be enforced. Would you like to proceed?', 'melapress-login-security' ),
					'submitOK'               => \__( 'OK', 'melapress-login-security' ),
					'submitNo'               => \__( 'No', 'melapress-login-security' ),
				)
			);
			// Check password expired.
			$should_password_expire = \MLS\Check_User_Expiry::should_password_expire( \get_current_user_id() );
			$session_setting        = isset( self::$options->mls_setting->terminate_session_password ) ? self::$options->mls_setting->terminate_session_password : self::$options->default_setting->terminate_session_password;
			// localize options.
			\wp_localize_script(
				'ppm-wp-global',
				'options',
				array(
					'global_ajax_url'            => \admin_url( 'admin-ajax.php' ),
					'wp_admin'                   => \wp_logout_url( \network_admin_url() ),
					'terminate_session_password' => OptionsHelper::string_to_bool( $session_setting ),
					'should_password_expire'     => OptionsHelper::string_to_bool( $should_password_expire ),
					'session_expired_nonce'      => \wp_create_nonce( 'mls_session_expired' ),
				)
			);
		}

		/**
		 * Load custom WP admin style.
		 *
		 * @param string $hook - Current page hook.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function load_custom_wp_admin_style( $hook ) {
			\wp_enqueue_style( 'ppm-wp-settings-css', MLS_PLUGIN_URL . 'admin/assets/css/admin.css', array(), MLS_VERSION );
		}


		/**
		 * Session expired dialog box.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function admin_footer_session_expired_dialog() {
			?>
			<div id="ppm-wp-dialog" class="hidden" style="max-width:800px">
				<p><?php \esc_html_e( 'Your password has expired hence your session is being terminated. Click the button below to receive an email with the reset password link.', 'melapress-login-security' ); ?></p>
				<p><?php \esc_html_e( 'For more information please contact the WordPress admin on ', 'melapress-login-security' ); ?><?php echo \esc_url( \get_option( 'admin_email' ) ); ?></p>
				<a href="javascript:;" class="button-primary reset"><?php \esc_html_e( 'Reset password', 'melapress-login-security' ); ?></a>
			</div>
			<div id="reset-all-dialog" class="hidden" style="max-width:800px">
			</div>
			<style>
				a[href="admin.php?page=mls-upgrade"] {
					color: #ff8977 !important;
				}
			</style>
			<?php
		}

		/**
		 * Get list of all roles.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function search_users_roles() {

			\check_admin_referer( 'mls-policies' );

			// The nonce alone is not authorisation: it only proves the request
			// came from a page this user was served. This endpoint returns every
			// matching user's login and email.
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( \esc_html__( 'Permission denied.', 'melapress-login-security' ), 403 );
			}

			$get_array  = \filter_input_array( INPUT_GET );
			$search_str = isset( $get_array['search_str'] ) ? $get_array['search_str'] : '';

			if ( isset( $get_array['action'] ) && 'get_users_roles' !== $get_array['action'] ) {
				die();
			}

			$exclude_users = empty( $get_array['exclude_users'] ) ? false : self::decode_js_var( $get_array['exclude_users'] );

			$users = self::search_users( $search_str, $exclude_users );

			echo \wp_json_encode( $users );

			die();
		}

		/**
		 * Turns json into usable string.
		 *
		 * @param string $to_decode - Item to decode.
		 *
		 * @return mixed
		 *
		 * @since 2.0.0
		 */
		public static function decode_js_var( $to_decode ) {
			$to_decode = json_decode( html_entity_decode( stripslashes( $to_decode ), ENT_QUOTES, 'UTF-8' ), true );

			if ( ! is_array( $to_decode ) && ! empty( $to_decode ) ) {
				$to_decode = self::decode_js_var( $to_decode );
			}

			return $to_decode;
		}

		/**
		 * Search Users
		 *
		 * @param string $search_str Search string.
		 * @param array  $exclude_users Exclude user array.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function search_users( $search_str, $exclude_users ) {
			// Search by user fields.
			$args = array(
				'exclude'        => $exclude_users,
				'search'         => '*' . $search_str . '*',
				'search_columns' => array(
					'user_login',
					'user_email',
					'user_nicename',
					'display_name',
				),
				'fields'         => array(
					'ID',
					'user_login',
				),
			);

			// Search by user meta — escape LIKE wildcards to prevent DoS via crafted input.
			global $wpdb;
			$escaped_search = $wpdb->esc_like( $search_str );
			$meta_args      = array(
				'exclude'    => $exclude_users,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => 'first_name',
						'value'   => $escaped_search,
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'last_name',
						'value'   => $escaped_search,
						'compare' => 'LIKE',
					),
				),
				'fields'     => array(
					'ID',
					'user_login',
				),
			);
			// Get users by search keyword.
			$user_query = new \WP_User_Query( $args );
			// Get user by search user meta value.
			$user_query_by_meta = new \WP_User_Query( $meta_args );
			// Merge users using get_results() accessor only to avoid direct property access and deduplicate by user ID.
			$users_field_query      = $user_query->get_results();
			$users_field_query_meta = $user_query_by_meta->get_results();
			$combined               = array_merge( $users_field_query, $users_field_query_meta );
			$users                  = array();
			$seen_ids               = array();
			foreach ( $combined as $u ) {
				if ( \property_exists( $u, 'ID' ) && ! isset( $seen_ids[ $u->ID ] ) ) {
					$seen_ids[ $u->ID ] = true;
					$users[]            = $u;
				}
			}
			// Return found users.
			return self::format_users( $users );
		}

		/**
		 * User format.
		 *
		 * @param array $users User Object.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function format_users( $users ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$formatted_users = array();
			if ( ! is_array( $users ) ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
				return $formatted_users; // Safety: ensure iterable.
			}
			foreach ( $users as $user ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
				$formatted_users[] = array(
					'id'    => $user->ID,
					'value' => $user->user_login,
				);
			}

			return $formatted_users;
		}

		/**
		 * Display custom admin notice.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function admin_save_success_notice() {
			?>

				<div class="notice notice-success is-dismissible">
					<p><?php \esc_html_e( 'Policies updated successfully.', 'melapress-login-security' ); ?></p>
				</div>

			<?php
		}

		/**
		 * Display custom admin notice.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function admin_save_error_notice() {
			?>

				<div class="notice notice-error is-dismissible">
					<p><?php \esc_html_e( 'Policies update failed. Please try again.', 'melapress-login-security' ); ?></p>
				</div>

			<?php
		}



		/**
		 * Display custom admin notice.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function admin_save_error_required_field_notice() {
			?>

				<div class="notice notice-error is-dismissible">
					<p><?php \esc_html_e( 'This setting is mandatory. Please specify a value.', 'melapress-login-security' ); ?></p>
				</div>

			<?php
		}

		/**
		 * Display custom admin notice.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function admin_save_error_not_enough_questions_provided_notice() {
			?>

				<div class="notice notice-error is-dismissible">
					<p><?php \esc_html_e( 'Please ensure you have the minimum number of questions configured.', 'melapress-login-security' ); ?></p>
				</div>

			<?php
		}

		/**
		 * Sends a test email to the logged in user.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function send_test_email() {
			// Check if its a valid request.
			if ( ! \check_admin_referer( 'send_test_email' ) ) {
				exit;
			}

			// Sends mail on demand — keep it to the people who administer the
			// site rather than anyone holding a valid nonce.
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \esc_html__( 'Permission denied.', 'melapress-login-security' ) ), 403 );
			}

			// Checking if request is made by a logged in user or not.
			$current_user = \wp_get_current_user();
			if ( ! \is_user_logged_in() || ! ( $current_user instanceof \WP_User ) ) {
				\wp_send_json_error( array( 'message' => \__( 'No user logged in.', 'melapress-login-security' ) ) );
			}
			if ( ! isset( $current_user->user_email ) || empty( $current_user->user_email ) ) {
				\wp_send_json_error( array( 'message' => \__( 'Current user has no email address defined', 'melapress-login-security' ) ) );
			}

			// Populating data for email.
			$to      = $current_user->user_email;
			$subject = \esc_html__( '{site_name} - Melapress Login Security plugin test email', 'melapress-login-security' );
			$subject = \MLS\EmailAndMessageStrings::replace_email_strings( $subject, \get_current_user_id() );

			$message = sprintf(
				__(
					'<p>Hooray!</p>
						<p>If you are reading this email it means that your website’s email setup is working. You can now enable the password and other login security policies on {site_name} using Melapress Login Security.</p>
						<p>If you need help getting started, refer to our <a href="https://melapress.com/support/kb/melapress-login-security-getting-started/?utm_source=plugins&utm_medium=link&utm_campaign=mls">getting started guide</a>.</p>
						<p>Stay secure!</p>',
					'melapress-login-security'
				)
			);

			$message = \MLS\EmailAndMessageStrings::replace_email_strings( $message, \get_current_user_id() );

			$from_email = self::$options->mls_setting->from_email ? self::$options->mls_setting->from_email : 'mls@' . \str_ireplace( 'www.', '', \wp_parse_url( \network_site_url(), PHP_URL_HOST ) );

			$from_email = \sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;
			$headers[]  = 'Content-Type: text/html; charset=UTF-8';

			// Errors might be thrown in wp_mail, so handling them beforehand.
			\add_action( 'wp_mail_failed', array( __CLASS__, 'log_ajax_mail_error' ) );

			$status = \MLS\Emailer::send_email( $to, $subject, $message, $headers );

			if ( true === $status ) {
				/* translators: %s: Users email address. */
				\wp_send_json_success( array( 'message' => \sprintf( \__( 'An email was sent successfully to your account email address: %s. Please check your email address to confirm receipt.', 'melapress-login-security' ), $to ) ) );
			} else {
				\wp_send_json_error( array( 'message' => \__( 'An error occurred while trying to send email, please check if the server is configured to send emails before saving settings', 'melapress-login-security' ) ) );
			}
			exit;
		}

		/**
		 * Logging of test mail function errors.
		 *
		 * @param object $error WP_Error Object.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function log_ajax_mail_error( $error ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				if ( \is_wp_error( $error ) ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
					\wp_send_json_error( array( 'message' => $error->get_error_message() ) ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
				} else {
					\wp_send_json_error( array( 'message' => \__( 'Mail was not sent due to some unknown error', 'melapress-login-security' ) ) );
				}
				exit;
			}
		}

		/**
		 * Get global timestamp.
		 *
		 * @return string|int Timestamp.
		 *
		 * @since 2.0.0
		 */
		public static function get_global_reset_timestamp() {
			return \get_site_option( MLS_PREFIX . '_reset_timestamp', 0 );
		}

		/**
		 * Add link to tabbed area within settings.
		 *
		 * @param  string $markup - Currently added content.
		 *
		 * @return string $markup - Appended content.
		 *
		 * @since 2.0.0
		 */
		public static function messages_settings_tab_link( $markup ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			return $markup . '<a href="#message-settings" class="nav-tab" data-tab-target=".ppm-message-settings">' . \esc_attr__( 'User notices templates', 'melapress-login-security' ) . '</a>'; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
		}

		/**
		 * Add settings tab content to settings area
		 *
		 * @param  string $markup - Currently added content.
		 *
		 * @return string $markup - Appended content.
		 *
		 * @since 2.0.0
		 */
		public static function messages_settings_tab( $markup ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			ob_start();
			?>
			<div class="settings-tab ppm-message-settings">
				<?php self::render_message_template_settings(); ?>
			</div>
			<?php
			return $markup . ob_get_clean(); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
		}

		/**
		 * Display settings markup for message templates.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function render_message_template_settings() {
			$mls = melapress_login_security();
			?>
				
				<table class="form-table has-sticky-bar">
					<tbody>

						<div style="position: fixed; left: 980px; width: 260px; border-left: 1px solid #c3c4c7; padding: 20px;">
							<div style="position: sticky;">
								<p class="description"><?php \esc_html_e( 'The following tags are available for use in all email template fields.', 'melapress-login-security' ); ?></p><br><br>
								<b><?php \esc_html_e( 'Available tags:', 'melapress-login-security' ); ?></b><br>
								{home_url} <i><?php \esc_html_e( '- Site URL', 'melapress-login-security' ); ?></i><br>
								{site_name} <i><?php \esc_html_e( '- Site Name', 'melapress-login-security' ); ?></i><br>
								{user_login_name} <i><?php \esc_html_e( '- User Login Name', 'melapress-login-security' ); ?></i><br>
								{user_first_name} <i><?php \esc_html_e( '- User First Name', 'melapress-login-security' ); ?></i><br>
								{user_last_name} <i><?php \esc_html_e( '- User Last Name', 'melapress-login-security' ); ?></i><br>
								{user_display_name} <i><?php \esc_html_e( '- User Display Name', 'melapress-login-security' ); ?></i><br>
								{admin_email} <i><?php \esc_html_e( '- From email address / site admin email', 'melapress-login-security' ); ?></i><br>
								{remaining_time} <i><?php \esc_html_e( '- Time until next login is allowed.', 'melapress-login-security' ); ?></i><br>	
							</div>
						</div>

						<tr valign="top">
							<br>
							<h1><?php \esc_html_e( 'User notices templates', 'melapress-login-security' ); ?></h1>
							<p class="description"><?php \esc_html_e( 'Customise the security-related notices and prompts shown to users during login and account actions.', 'melapress-login-security' ); ?></p>
							<br>
						</tr>

					</tbody>
				</table>	

				<table class="form-table has-sticky-bar">
					<tbody>

						<tr valign="top">
							<h3><?php \esc_html_e( 'Expired password', 'melapress-login-security' ); ?></h3>
							<p class="description"><?php \esc_html_e( 'Shown when a user attempts to log in using a password that has expired.', 'melapress-login-security' ); ?></p>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php \esc_html_e( 'Message', 'melapress-login-security' ); ?>
							</th>
							<td style="padding-right: 15px;">
								<fieldset>
									<?php
									$content   = \MLS\EmailAndMessageStrings::get_email_template_setting( 'password_reset_request_disabled_message' );
									$editor_id = 'mls_options_password_reset_request_disabled_message';
									$settings  = array(
										'media_buttons' => false,
										'editor_height' => 200,
										'textarea_name' => 'mls_options[password_reset_request_disabled_message]',
									);
									\wp_editor( $content, $editor_id, $settings );
									?>
								</fieldset>
							</td>
						</tr>	
					</tbody>
				</table>
				
				<?php
				?>
				<table class="form-table has-sticky-bar">
					<tbody>
						<tr valign="top">
							<h3><?php \esc_html_e( 'Password reset disabled', 'melapress-login-security' ); ?></h3>
							<p class="description"><?php \esc_html_e( 'Shown when a user requests a password reset but password resets are disabled by an active Login Security Policy.', 'melapress-login-security' ); ?></p>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php \esc_html_e( 'Message', 'melapress-login-security' ); ?>
							</th>
							<td style="padding-right: 15px;">
								<fieldset>
									<?php
									$content   = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_exceeded_failed_logins_count_message' );
									$editor_id = 'mls_options_user_exceeded_failed_logins_count_message';
									$settings  = array(
										'media_buttons' => false,
										'editor_height' => 200,
										'textarea_name' => 'mls_options[user_exceeded_failed_logins_count_message]',
									);
									\wp_editor( $content, $editor_id, $settings );
									?>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>

				<?php
		}
	}
}
