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

use MLS\Utilities\Validator_Factory;
use MLS\Helpers\OptionsHelper;

if ( ! class_exists( '\MLS\Admin\Admin' ) ) {

	/**
	 * Declare Admin class
	 *
	 * @since 2.0.0
	 */
	class Admin {

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

			add_filter( 'plugin_action_links_' . MLS_BASENAME, array( __CLASS__, 'plugin_action_links' ), 100, 1 );
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

			// Ajax.
			add_action( 'wp_ajax_get_users_roles', array( __CLASS__, 'search_users_roles' ) );
			add_action( 'wp_ajax_mls_send_test_email', array( __CLASS__, 'send_test_email' ) );
			add_action( 'wp_ajax_mls_process_reset', array( '\MLS\Reset_Passwords', 'process_global_password_reset' ) );

			// Bulk actions.
			add_filter( 'bulk_actions-users', array( '\MLS\Reset_Passwords', 'add_bulk_action_link' ), 10, 1 );
			add_filter( 'handle_bulk_actions-users', array( '\MLS\Reset_Passwords', 'handle_bulk_action_link' ), 10, 3 );
			add_action( 'admin_notices', array( '\MLS\Reset_Passwords', 'bulk_action_admin_notice' ) );

			// Add dialog box.
			add_action( 'admin_footer', array( __CLASS__, 'admin_footer_session_expired_dialog' ) );
			add_action( 'admin_footer', array( __CLASS__, 'popup_notices' ) );

			$options_master_switch    = OptionsHelper::string_to_bool( self::$options->master_switch );
			$settings_master_switch   = OptionsHelper::string_to_bool( self::$settings->master_switch );
			$inherit_policies_setting = OptionsHelper::string_to_bool( self::$settings->inherit_policies );

			$is_needed = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );

			if ( $is_needed ) {
				if ( OptionsHelper::string_to_bool( self::$settings->enforce_password ) ) {
					return;
				}
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'global_admin_enqueue_scripts' ) );
			}

			add_action( 'admin_notices', array( __CLASS__, 'plugin_was_updated_banner' ), 10, 3 );
			add_action( 'wp_ajax_dismiss_mls_update_notice', array( __CLASS__, 'dismiss_update_notice' ) );
			add_action( 'wp_ajax_mls_begin_migration', array( __CLASS__, 'begin_migration' ) );
			add_action( 'wp_ajax_mls_get_migration_status', array( __CLASS__, 'get_migration_status' ) );


			/* @free:start */
			if ( ! class_exists( '\MLS\EmailAndMessageTemplates' ) ) {
				add_filter( 'mls_settings_page_nav_tabs', array( __CLASS__, 'messages_settings_tab_link' ), 10, 1 );
				add_filter( 'mls_settings_page_content_tabs', array( __CLASS__, 'messages_settings_tab' ), 10, 1 );
			}
			/* @free:end */
		}

		/**
		 * Show notice to recently updated plugin.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function plugin_was_updated_banner() {
			$show_update_notice     = get_site_option( MLS_PREFIX . '_update_notice_needed', false );
			$screen                 = get_current_screen();
			$mls_migration_required = is_multisite() ? get_site_option( 'mls_migration_required' ) : get_option( 'mls_migration_required' );
			$migration_complete     = is_multisite() ? get_site_option( 'mls_200_migration_complete' ) : get_option( 'mls_200_migration_complete' );

			$pages_for_banner = array(
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

			if ( in_array( $screen->base, $pages_for_banner, true ) && $show_update_notice ) {
				?>
				<!-- Copy START -->
				<div class="mls-plugin-update">
					<div class="mls-plugin-update-content">
						<h2 class="mls-plugin-update-title"><?php esc_html_e( 'Melapress Login Security has been updated to version', 'melapress-login-security' ); ?> <?php echo esc_attr( MLS_VERSION ); ?>.</h2>
						<p class="mls-plugin-update-text">
							<?php esc_html_e( 'You are now running the latest version of Melapress Login Security. To see what\'s been included in this update, refer to the plugin\'s release notes and change log where we list all new features, updates, and bug fixes.', 'melapress-login-security' ); ?>							
						</p>
						<a href="https://melapress.com/wordpress-login-security/releases/?utm_source=plugin&utm_medium=banner&utm_campaign=mls" target="_blank" class="mls-cta-link"><?php esc_html_e( 'Read the release notes', 'melapress-login-security' ); ?></a>
					</div>
					<button aria-label="Close button" class="mls-plugin-update-close" data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( 'mls_dismiss_update_notice_nonce' ) ); ?>"></button>
				</div>
				<!-- Copy END -->
				
				<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function( $ ) {
					jQuery( 'body' ).on( 'click', '.mls-plugin-update-close', function ( e ) {
						var nonce  = jQuery( '.mls-plugin-update [data-dismiss-nonce]' ).attr( 'data-dismiss-nonce' );
						
						jQuery.ajax({
							type: 'POST',
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							async: true,
							data: {
								action: 'dismiss_mls_update_notice',
								nonce : nonce,
							},
							success: function ( result ) {		
								jQuery( '.mls-plugin-update' ).slideUp( 300 );
							}
						});
					});
				});
				//]]>
				</script>
				<?php
			}

			if ( ( in_array( $screen->base, $pages_for_banner, true ) && $mls_migration_required && ! $migration_complete ) || ( in_array( $screen->base, $pages_for_banner, true ) && ! empty( get_site_option( 'ppmwp_options', false ) ) ) ) {
				?>
				<div class="mls-plugin-data-migration">
					<div class="mls-plugin-update-content">
						<h2 class="mls-plugin-update-title"><?php esc_html_e( 'Important data migration required', 'melapress-login-security' ); ?></h2>
						<p class="mls-plugin-update-text">
							<?php esc_html_e( 'This update made some required changes to where our plugin data is stored and requires updating to avoid issues in future. Click below to begin the process, which will happen automatically.', 'melapress-login-security' ); ?>							
						</p>
						<a href="https://melapress.com/wordpress-login-security/releases/?utm_source=plugin&utm_medium=banner&utm_campaign=mls" target="_blank" class="mls-cta-link mls-begin-migration" data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( 'mls_begin_migration_nonce' ) ); ?>"><?php esc_html_e( 'Begin migration process', 'melapress-login-security' ); ?></a>
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
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
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
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						async: true,
						data: {
							action: 'mls_get_migration_status',
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

			if ( ( in_array( $screen->base, $pages_for_banner, true ) && $mls_migration_required && ! $migration_complete ) || ( in_array( $screen->base, $pages_for_banner, true ) && $show_update_notice ) ) {
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
						background-image: url(<?php echo esc_url( MLS_PLUGIN_URL ) . 'admin/assets/images/close-icon-rev.svg'; ?>); /* Path to your close icon */
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
						background-image: url(<?php echo esc_url( MLS_PLUGIN_URL ) . 'admin/assets/images/mls-updated-bg.png'; ?>); /* Background image only displayed on desktop */
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
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

			// Check nonce.
			if ( ! current_user_can( 'manage_options' ) || empty( $nonce ) || ! $nonce || ! wp_verify_nonce( $nonce, 'mls_begin_migration_nonce' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			\MLS\UpdateRoutines::update_from_pre_200();

			wp_send_json_success( esc_html__( 'Started', 'melapress-login-security' ) );
		}

		/**
		 * Get current migration status.
		 *
		 * @return object - Result.
		 *
		 * @since 2.0.0
		 */
		public static function get_migration_status() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			$status = get_site_option( 'mls_migration_status', false );
			if ( ! empty( $status ) ) {
				wp_send_json_success( $status );
			}
			return wp_send_json_success( esc_html__( 'Process starting', 'melapress-login-security' ) );
		}

		/**
		 * Handle notice dismissal.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function dismiss_update_notice() {
			// Grab POSTed data.
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;

			// Check nonce.
			if ( ! current_user_can( 'manage_options' ) || empty( $nonce ) || ! $nonce || ! wp_verify_nonce( $nonce, 'mls_dismiss_update_notice_nonce' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			delete_site_option( MLS_PREFIX . '_update_notice_needed' );

			wp_send_json_success( esc_html__( 'Complete.', 'melapress-login-security' ) );
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
						data-windowtitle="<?php echo ( isset( $notice['title'] ) ) ? esc_attr( $notice['title'] ) : ''; ?>"
						data-redirect="<?php echo ( isset( $notice['redirect'] ) ) ? esc_attr( $notice['redirect'] ) : ''; ?>"
						>
						<div class="notice_modal_wrapper">
							<p><?php echo wp_kses_post( $notice['message'] ); ?></p>
							<?php
							if ( isset( $notice['buttons'] ) && ! empty( $notice['buttons'] ) ) {
								?>
								<div class="notice_modal_footer">
									<?php
									foreach ( $notice['buttons'] as $key => $button ) {
										?>
										<button type="button"
											class="<?php echo ( isset( $button['class'] ) ) ? esc_attr( $button['class'] ) : ''; ?>"
											onClick="<?php echo ( isset( $button['onClick'] ) ) ? esc_attr( $button['onClick'] ) : ''; ?>"
											>
												<?php echo esc_html( $button['text'] ); ?>
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
						<p><?php esc_html_e( 'To ensure you dont lock yourself out of your own dashboard, be sure to exclude your own admin account from password policies when enabling this feature.', 'melapress-login-security' ); ?></p>
						<div class="notice_modal_footer">
							<button type="button" class="button-primary" onclick="mls_close_thickbox()"><?php esc_html_e( 'Acknowledge', 'melapress-login-security' ); ?></button>
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

			if ( function_exists( 'melapress_login_security_freemius' ) ) {
				if ( melapress_login_security_freemius()->can_use_premium_code() && isset( $old_links['upgrade'] ) ) {
					unset( $old_links['upgrade'] );
				} elseif ( melapress_login_security_freemius()->is_free_plan() ) {
					unset( $old_links['upgrade'] );
					$upgrade_link = '<a style="color: #dd7363; font-weight: bold;" class="mls-premium-link" target="_blank" href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugins&utm_medium=referral&utm_campaign=mls">' . __( 'Get the Premium!', 'melapress-login-security' ) . '</a>';
					array_push( $new_links, $upgrade_link );
				}
			} else {
				$upgrade_link = '<a style="color: #dd7363; font-weight: bold;" class="mls-premium-link" target="_blank" href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugins&utm_medium=referral&utm_campaign=mls">' . __( 'Get the Premium!', 'melapress-login-security' ) . '</a>';
				array_push( $new_links, $upgrade_link );
			}

			$config_link = '<a href="' . add_query_arg( 'page', MLS_MENU_SLUG, network_admin_url( 'admin.php' ) ) . '">' . __( 'Configure policies', 'melapress-login-security' ) . '</a>';
			array_push( $new_links, $config_link );

			$docs_link = '<a target="_blank" href="' . add_query_arg(
				array(
					'utm_source'   => 'plugins',
					'utm_medium'   => 'link',
					'utm_campaign' => 'mls',
				),
				'https://melapress.com/support/kb/'
			) . '">' . __( 'Docs', 'melapress-login-security' ) . '</a>';
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
			$notification_count = OptionsHelper::get_current_notices_count();

			// Add admin menu page.
			$hook_name = add_menu_page(
				__( 'Login Security Policies', 'melapress-login-security' ),
				$notification_count ? sprintf( 'Login Security <span style="position: absolute; margin-left: 3px;" class="awaiting-mod">%d</span>', $notification_count ) : __( 'Login Security', 'melapress-login-security' ),
				'manage_options',
				MLS_MENU_SLUG,
				array( __CLASS__, 'screen' ),
				melapress_login_security()->icon,
				99
			);

			add_action( "load-$hook_name", array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_action( "admin_head-$hook_name", array( __CLASS__, 'process' ) );

			add_submenu_page( MLS_MENU_SLUG, __( 'Login Security Policies', 'melapress-login-security' ), __( 'Login Security Policies', 'melapress-login-security' ), 'manage_options', MLS_MENU_SLUG, array( __CLASS__, 'screen' ) );

			// Add admin submenu page.
			$hook_submenu = add_submenu_page(
				MLS_MENU_SLUG,
				__( 'Help & Contact Us', 'melapress-login-security' ),
				__( 'Help & Contact Us', 'melapress-login-security' ),
				'manage_options',
				'mls-help',
				array(
					__CLASS__,
					'ppm_display_help_page',
				)
			);

			add_action( "load-$hook_submenu", array( __CLASS__, 'help_page_enqueue_scripts' ) );

			// Add admin submenu page for settings.
			$settings_hook_submenu = add_submenu_page(
				MLS_MENU_SLUG,
				__( 'Settings', 'melapress-login-security' ),
				__( 'Settings', 'melapress-login-security' ),
				'manage_options',
				'mls-settings',
				array(
					__CLASS__,
					'ppm_display_settings_page',
				)
			);

			add_action( "load-$settings_hook_submenu", array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_action( "admin_head-$settings_hook_submenu", array( __CLASS__, 'process' ) );

			// Add admin submenu page for form placement.
			$forms_hook_submenu = add_submenu_page(
				MLS_MENU_SLUG,
				__( 'Forms & Placement', 'melapress-login-security' ),
				__( 'Forms & Placement', 'melapress-login-security' ),
				'manage_options',
				'mls-forms',
				array(
					__CLASS__,
					'ppm_display_forms_page',
				),
				1
			);

			add_action( "load-$forms_hook_submenu", array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_action( "admin_head-$forms_hook_submenu", array( __CLASS__, 'process' ) );

			// Add admin submenu page for form placement.
			$hide_login_submenu = add_submenu_page(
				MLS_MENU_SLUG,
				__( 'Login page hardening', 'melapress-login-security' ),
				__( 'Login page hardening', 'melapress-login-security' ),
				'manage_options',
				'mls-hide-login',
				array(
					__CLASS__,
					'ppm_display_hide_login_page',
				),
				2
			);

			add_action( "load-$hide_login_submenu", array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_action( "admin_head-$hide_login_submenu", array( __CLASS__, 'process' ) );


			/* @free:start */
			$hook_upgrade_submenu = add_submenu_page( MLS_MENU_SLUG, esc_html__( 'Premium Features ➤', 'melapress-login-security' ), esc_html__( 'Premium Features ➤', 'melapress-login-security' ), 'manage_options', 'mls-upgrade', array( __CLASS__, 'ppm_display_upgrade_page' ), 3 );
			add_action( "load-$hook_upgrade_submenu", array( __CLASS__, 'help_page_enqueue_scripts' ) );
			/* @free:end */

			if ( ! is_multisite() ) {
				// Add admin submenu page for temp logins.
				$temp_logins_submenu = add_submenu_page(
					MLS_MENU_SLUG,
					__( 'Temporary Logins', 'melapress-login-security' ),
					__( 'Temporary Logins', 'melapress-login-security' ),
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

		/* @free:start */
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
		/* @free:end */

		/**
		 * Melapress Login Security onload process
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function process() {
			// nonce checked later before processing happens.
			$is_user_action = isset( $_POST[ MLS_PREFIX . '_nonce' ] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( $is_user_action ) {
				self::save();
			}
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
			return isset( $_POST[ MLS_PREFIX . '_nonce' ] ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ MLS_PREFIX . '_nonce' ] ) ), MLS_PREFIX . '_nonce_form' ) : false;
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

			$current_context = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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
			if ( isset( $_POST['mls_options']['inherit_policies'] ) && sanitize_text_field( wp_unslash( $_POST['mls_options']['inherit_policies'] ) ) === 1 ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				// Get user role.
				$setting_option = ( isset( $_POST['mls_options']['ppm-user-role'] ) && ! empty( $_POST['mls_options']['ppm-user-role'] ) ) ? '_' . sanitize_text_field( wp_unslash( $_POST['mls_options']['ppm-user-role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				// Delete site option.
				delete_site_option( MLS_PREFIX . $setting_option . '_options' );
				// unset settings.
				unset( $_POST['mls_options'] );
				// Reassign setting open.
				self::$setting_tab = (object) $mls->options->inherit;
				// Success notice.
				self::notice( 'admin_save_success_notice' );
			}

			$post_array = filter_input_array( INPUT_POST );
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
								if ( ! Validator_Factory::validate( sanitize_text_field( wp_unslash( $_POST['mls_options'][ $key ][ $field_name ] ) ), $rule ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
									self::notice( 'admin_save_error_notice' );
									$ok_to_save = false;
								}
							}
						}
					} elseif ( isset( $_POST['mls_options'][ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
							$rule = $valid_rules;
						if ( ! Validator_Factory::validate( sanitize_text_field( wp_unslash( $_POST['mls_options'][ $key ] ) ), $rule ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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

				$mls_setting = OptionsHelper::recursive_parse_args( $settings, $mls->options->mls_setting );

				if ( self::$options->mls_save_setting( $mls_setting ) ) {
					self::notice( 'admin_save_success_notice' );
				}
				return;
			}

			// Policies area.
			if ( 'mls-policies' === $current_context ) {

				if ( ! isset( $_POST['mls_options']['disable_self_reset_message'] ) || empty( $_POST['mls_options']['disable_self_reset_message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['disable_self_reset_message'] = __( 'You are not allowed to reset your password. Please contact the website administrator.', 'melapress-login-security' );
				}

				if ( ! isset( $_POST['mls_options']['locked_user_disable_self_reset_message'] ) || empty( $_POST['mls_options']['locked_user_disable_self_reset_message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['locked_user_disable_self_reset_message'] = __( 'You are not allowed to reset your password. Please contact the website administrator.', 'melapress-login-security' );
				}

				if ( ! isset( $_POST['mls_options']['user_unlocked_email_title'] ) || empty( $_POST['mls_options']['user_unlocked_email_title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['disable_self_reset_message'] = __( 'You are not allowed to reset your password. Please contact the website administrator.', 'melapress-login-security' );
				}

				if ( ! isset( $_POST['mls_options']['inactive_users_enabled'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['inactive_users_enabled'] = 0;
				} else {
					$_POST['mls_options']['inactive_users_enabled'] = 1;
					// add the current user to the inactive exempt list if that list
					// is empty.
					$added = OptionsHelper::add_initial_user_to_exempt_list( wp_get_current_user() );
					if ( $added ) {
						$args = array(
							'page' => 'mls-settings',
						);
						$url  = add_query_arg( $args, network_admin_url( 'admin.php' ) );
						// add details to output for the modal popup.
						self::$extra_notice_details[] = array(
							'title'    => __( 'User Added to Exempt List', 'melapress-login-security' ),
							'message'  => __( 'Your user has been exempted from the all policies since there must be at least one excluded user to avoid all users being locked out. You can change this from the plugin\'s settings.', 'melapress-login-security' ),
							'redirect' => add_query_arg(
								array(
									'page' => 'mls-settings',
									'tab'  => 'setting',
								),
								network_admin_url( 'admin.php' )
							),
							'buttons'  => array(
								array(
									'text'    => __( 'View settings', 'melapress-login-security' ),
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
						$_POST['mls_options']['inactive_users_expiry']['value'] = sanitize_text_field( wp_unslash( $_POST['mls_options']['inactive_users_expiry']['value'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
						$_POST['mls_options']['default_session_expiry']['value'] = sanitize_text_field( wp_unslash( $_POST['mls_options']['default_session_expiry']['value'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}
					if ( empty( $_POST['mls_options']['remember_session_expiry']['value'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						self::notice( 'admin_save_error_required_field_notice' );
						$ok_to_save = false;
					} else {
						$_POST['mls_options']['remember_session_expiry']['value'] = sanitize_text_field( wp_unslash( $_POST['mls_options']['remember_session_expiry']['value'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					}
				}

				// Check inputs for emptyness.
				$ok_to_save = true;
				if ( ( isset( $_POST['mls_options']['min_length'] ) && empty( $_POST['mls_options']['min_length'] ) ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing
					( isset( $_POST['mls_options']['password_expiry'] ) && empty( $_POST['mls_options']['password_expiry']['value'] ) && intval( $_POST['mls_options']['password_expiry']['value'] ) !== 0 ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing
					( isset( $_POST['mls_options']['password_history'] ) && empty( $_POST['mls_options']['password_history'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
					) {
					self::notice( 'admin_save_error_required_field_notice' );
					$ok_to_save = false;
				}

				if ( isset( $_POST['mls_options']['ui_rules']['exclude_special_chars'] ) && intval( $_POST['mls_options']['ui_rules']['exclude_special_chars'] ) !== 0 && empty( $_POST['mls_options']['excluded_special_chars'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					self::notice( 'admin_save_error_required_field_notice' );
					$ok_to_save = false;
				}

				$min_req_security_questions = isset( $_POST['mls_options']['min_answered_needed_count'] ) ? intval( $_POST['mls_options']['min_answered_needed_count'] ) : 3; // phpcs:ignore WordPress.Security.NonceVerification.Missing

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
								if ( ! Validator_Factory::validate( sanitize_text_field( wp_unslash( $_POST['mls_options'][ $key ][ $field_name ] ) ), $rule ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
									self::notice( 'admin_save_error_notice' );
									$ok_to_save = false;
								}
							}
						}
					} elseif ( isset( $_POST['mls_options'][ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

							$rule = $valid_rules;
						if ( ! Validator_Factory::validate( sanitize_text_field( wp_unslash( $_POST['mls_options'][ $key ] ) ), $rule ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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

				if ( isset( $_POST['mls_options']['notify_password_expiry_days'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( intval( $_POST['mls_options']['notify_password_expiry_days'] ) >= intval( $_POST['mls_options']['password_expiry']['value'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$settings_updated['notify_password_expiry_days'] = intval( $_POST['mls_options']['password_expiry']['value'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
						if ( 0 === $settings_updated['notify_password_expiry_days'] ) {
							$settings_updated['notify_password_expiry'] = false;
						}
					}
					if ( 'hours' === $_POST['mls_options']['password_expiry']['unit'] && 'days' === $_POST['mls_options']['notify_password_expiry_unit'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Missing
						$settings_updated['notify_password_expiry_unit'] = 'hours';
					}
				}

				if ( ! isset( $_POST['mls_options']['activate_password_expiration_policies'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$_POST['mls_options']['activate_password_expiration_policies'] = 0;
				} else {
					$_POST['mls_options']['activate_password_expiration_policies'] = 1;
					// add the current user to the inactive exempt list if that list
					// is empty.
					$added = OptionsHelper::add_initial_user_to_exempt_list( wp_get_current_user() );
					if ( $added ) {
						$args = array(
							'page' => 'mls-settings',
						);
						$url  = add_query_arg( $args, network_admin_url( 'admin.php' ) );
						// add details to output for the modal popup.
						self::$extra_notice_details[] = array(
							'title'    => __( 'User Added to Exempt List', 'melapress-login-security' ),
							'message'  => __( 'Your user has been exempted from the all policies since there must be at least one excluded user to avoid all users being locked out. You can change this from the plugin\'s settings.', 'melapress-login-security' ),
							'redirect' => add_query_arg(
								array(
									'page' => 'mls-settings',
									'tab'  => 'setting',
								),
								network_admin_url( 'admin.php' )
							),
							'buttons'  => array(
								array(
									'text'    => __( 'View settings', 'melapress-login-security' ),
									'class'   => 'button-primary',
									'onClick' => 'mls_close_thickbox("' . $url . '")',
								),
							),
						);
					}
				}

				// Process reset blocked message.
				$settings_updated['disable_self_reset_message']             = ( ! empty( $settings['disable_self_reset_message'] ) ) ? sanitize_textarea_field( $settings['disable_self_reset_message'] ) : false;
				$settings_updated['locked_user_disable_self_reset_message'] = ( ! empty( $settings['locked_user_disable_self_reset_message'] ) ) ? sanitize_textarea_field( $settings['locked_user_disable_self_reset_message'] ) : false;
				$settings_updated['deactivated_account_message']            = ( isset( $settings['deactivated_account_message'] ) && ! empty( $settings['deactivated_account_message'] ) ) ? wp_kses_post( $settings['deactivated_account_message'] ) : trim( \MLS\MLS_Options::get_default_account_deactivated_message() );
				$settings_updated['timed_login_message']                    = ( ! empty( $settings['timed_login_message'] ) ) ? sanitize_textarea_field( $settings['timed_login_message'] ) : false;

				$processedmls_options = apply_filters( 'mls_pre_option_save_validation', array_merge( $settings, $settings_updated ) );

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
				$user = get_user_by( 'login', trim( $username ) );
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
			add_action( 'admin_notices', array( __CLASS__, $callback_function ) );
		}

		/**
		 * Enqueue script for help page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function help_page_enqueue_scripts() {
			wp_enqueue_style( 'mls-help', MLS_PLUGIN_URL . 'admin/assets/css/help.css', array(), MLS_VERSION );
		}

		/**
		 * Add scripts for admin pages.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function admin_enqueue_scripts() {
			$mls = melapress_login_security();
			add_thickbox();
			wp_enqueue_script( 'jquery-ui-dialog' );

			// enqueue plugin JS.
			wp_enqueue_style( 'ppm-wp-settings-css', MLS_PLUGIN_URL . 'admin/assets/css/settings.css', array(), MLS_VERSION );
			wp_enqueue_script( 'ppm-wp-settings', MLS_PLUGIN_URL . 'admin/assets/js/settings.js', array( 'jquery-ui-autocomplete', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), MLS_VERSION, true );
			$session_setting = isset( $mls->options->mls_setting->terminate_session_password ) ? $mls->options->mls_setting->terminate_session_password : $mls->options->default_setting->terminate_session_password;
			wp_localize_script(
				'ppm-wp-settings',
				'ppm_ajax',
				array(
					'ajax_url'                   => admin_url( 'admin-ajax.php' ),
					'test_email_nonce'           => wp_create_nonce( 'send_test_email' ),
					'settings_nonce'             => wp_create_nonce( 'mls-policies' ),
					'terminate_session_password' => OptionsHelper::string_to_bool( $session_setting ),
					'special_chars_regex'        => melapress_login_security()->get_special_chars( true ),
					'reset_done_title'           => __( 'Reset process complete', 'melapress-login-security' ),
					'csv_error'                  => __( 'CSV contains invalid data, provide user IDs only.', 'melapress-login-security' ),
					'csv_file_error'             => __( 'Please provide the correct file type only.', 'melapress-login-security' ),
					'csv_error_length'           => __( 'Please ensure more than 1 ID is provided.', 'melapress-login-security' ),
					'reset_done_text'            => __( 'You may now close this window.', 'melapress-login-security' ),
				)
			);
			do_action( 'mls_enqueue_admin_scripts' );
			wp_localize_script(
				'ppm-wp-settings',
				'ppmwpSettingsStrings',
				array(
					'resetPasswordsDelayedMessage'   => __( 'This will reset the passwords of all users on this site. Users have to change their password once they logout and log back in. Are you sure?', 'melapress-login-security' ),
					'resetPasswordsInstantlyMessage' => __( 'This will reset the passwords of all users on this site and terminate their sessions instantly. Are you sure?', 'melapress-login-security' ),
					'resetOwnPasswordMessage'        => __( 'Should the plugin reset your password as well?', 'melapress-login-security' ),
				)
			);
		}

		/**
		 * Global admin enqueue scripts.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function global_admin_enqueue_scripts() {
			// enqueue these scripts and styles before admin_head
			// jquery and jquery-ui should be dependencies, didn't check though.
			if ( ! wp_script_is( 'jquery-ui-dialog', 'queue' ) ) {
				wp_enqueue_script( 'jquery-ui-dialog' );
			}

			if ( ! wp_style_is( 'wp-jquery-ui-dialog', 'queue' ) ) {
				wp_enqueue_style( 'wp-jquery-ui-dialog' );
			}

			// Global JS.
			wp_enqueue_script( 'ppm-wp-global', MLS_PLUGIN_URL . 'admin/assets/js/global.js', array( 'jquery' ), MLS_VERSION, true );
			wp_localize_script(
				'ppm-wp-global',
				'ppmwpGlobalStrings',
				array(
					'emailResetInstructions' => __( 'Please check your email for instructions on how to reset your password.', 'melapress-login-security' ),
					'shortPasswordMessage'   => __( 'By setting the minimum number of characters in passwords to less than 6 you\'re encouraging weak passwords and polices cannot be enforced. Would you like to proceed?', 'melapress-login-security' ),
					'submitOK'               => __( 'OK', 'melapress-login-security' ),
					'submitNo'               => __( 'No', 'melapress-login-security' ),
				)
			);
			// Check password expired.
			$should_password_expire = \MLS\Check_User_Expiry::should_password_expire( get_current_user_id() );
			$session_setting        = isset( self::$options->mls_setting->terminate_session_password ) ? self::$options->mls_setting->terminate_session_password : self::$options->default_setting->terminate_session_password;
			// localize options.
			wp_localize_script(
				'ppm-wp-global',
				'options',
				array(
					'global_ajax_url'            => admin_url( 'admin-ajax.php' ),
					'wp_admin'                   => wp_logout_url( network_admin_url() ),
					'terminate_session_password' => OptionsHelper::string_to_bool( $session_setting ),
					'should_password_expire'     => OptionsHelper::string_to_bool( $should_password_expire ),
				)
			);
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
				<p><?php esc_html_e( 'Your password has expired hence your session is being terminated. Click the button below to receive an email with the reset password link.', 'melapress-login-security' ); ?></p>
				<p><?php esc_html_e( 'For more information please contact the WordPress admin on ', 'melapress-login-security' ); ?><?php echo esc_url( get_option( 'admin_email' ) ); ?></p>
				<a href="javascript:;" class="button-primary reset"><?php esc_html_e( 'Reset password', 'melapress-login-security' ); ?></a>
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

			check_admin_referer( 'mls-policies' );

			$get_array  = filter_input_array( INPUT_GET );
			$search_str = $get_array['search_str'];

			if ( isset( $get_array['action'] ) && 'get_users_roles' !== $get_array['action'] ) {
				die();
			}

			$exclude_users = empty( $get_array['exclude_users'] ) ? false : self::decode_js_var( $get_array['exclude_users'] );

			$users = self::search_users( $search_str, $exclude_users );

			echo wp_json_encode( $users );

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

			// Search by user meta.
			$meta_args = array(
				'exclude'    => $exclude_users,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => 'first_name',
						'value'   => ".*$search_str",
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'last_name',
						'value'   => ".*$search_str",
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
			// Merge users.
			$users = $user_query->results + $user_query_by_meta->results;
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
		public static function format_users( $users ) {
			$formatted_users = array();
			foreach ( $users as $user ) {
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
					<p><?php esc_html_e( 'Policies updated successfully.', 'melapress-login-security' ); ?></p>
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
					<p><?php esc_html_e( 'Policies update failed. Please try again.', 'melapress-login-security' ); ?></p>
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
					<p><?php esc_html_e( 'This setting is mandatory. Please specify a value.', 'melapress-login-security' ); ?></p>
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
					<p><?php esc_html_e( 'Please ensure you have the minimum number of questions configured.', 'melapress-login-security' ); ?></p>
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
			if ( ! check_admin_referer( 'send_test_email' ) ) {
				exit;
			}

			// Checking if request is made by a logged in user or not.
			$current_user = wp_get_current_user();
			if ( ! is_user_logged_in() || ! ( $current_user instanceof \WP_User ) ) {
				wp_send_json_error( array( 'message' => __( 'No user logged in.', 'melapress-login-security' ) ) );
			}
			if ( ! isset( $current_user->user_email ) || empty( $current_user->user_email ) ) {
				wp_send_json_error( array( 'message' => __( 'Current user has no email address defined', 'melapress-login-security' ) ) );
			}

			// Populating data for email.
			$to      = $current_user->user_email;
			$subject = esc_html__( '{site_name} - Melapress Login Security plugin test email', 'melapress-login-security' );
			$subject = \MLS\EmailAndMessageStrings::replace_email_strings( $subject, get_current_user_id() );

			$message = sprintf(
				__(
					'<p>Hooray!</p>

<p>If you are reading this email it means that your website’s email setup is working. You can now enable the password and other login security policies on {site_name} using Melapress Login Security.</p>
<p>If you need help getting started, refer to our <a href="https://melapress.com/support/kb/melapress-login-security-getting-started/?utm_source=plugins&utm_medium=link&utm_campaign=mls">getting started guide</a>.</p>
<p>Stay secure!</p>',
					'melapress-login-security'
				)
			);

			$message = \MLS\EmailAndMessageStrings::replace_email_strings( $message, get_current_user_id() );

			$from_email = self::$options->mls_setting->from_email ? self::$options->mls_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );

			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;
			$headers[]  = 'Content-Type: text/html; charset=UTF-8';

			// Errors might be thrown in wp_mail, so handling them beforehand.
			add_action( 'wp_mail_failed', array( __CLASS__, 'log_ajax_mail_error' ) );

			$status = \MLS\Emailer::send_email( $to, $subject, $message, $headers );

			if ( true === $status ) {
				/* translators: %s: Users email address. */
				wp_send_json_success( array( 'message' => sprintf( __( 'An email was sent successfully to your account email address: %s. Please check your email address to confirm receipt.', 'melapress-login-security' ), $to ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'An error occurred while trying to send email, please check if the server is configured to send emails before saving settings', 'melapress-login-security' ) ) );
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
		public static function log_ajax_mail_error( $error ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				if ( is_wp_error( $error ) ) {
					wp_send_json_error( array( 'message' => $error->get_error_message() ) );
				} else {
					wp_send_json_error( array( 'message' => __( 'Mail was not sent due to some unknown error', 'melapress-login-security' ) ) );
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
			return get_site_option( MLS_PREFIX . '_reset_timestamp', 0 );
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
		public static function messages_settings_tab_link( $markup ) {
			return $markup . '<a href="#message-settings" class="nav-tab" data-tab-target=".ppm-message-settings">' . esc_attr__( 'User notification templates', 'melapress-login-security' ) . '</a>';
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
		public static function messages_settings_tab( $markup ) {
			ob_start();
			?>
			<div class="settings-tab ppm-message-settings">
				<?php self::render_message_template_settings(); ?>
			</div>
			<?php
			return $markup . ob_get_clean();
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
								<p class="description"><?php esc_html_e( 'The following tags are available for use in all email template fields.', 'melapress-login-security' ); ?></p><br><br>
								<b><?php esc_html_e( 'Available tags:', 'melapress-login-security' ); ?></b><br>
								{home_url} <i><?php esc_html_e( '- Site URL', 'melapress-login-security' ); ?></i><br>
								{site_name} <i><?php esc_html_e( '- Site Name', 'melapress-login-security' ); ?></i><br>
								{user_login_name} <i><?php esc_html_e( '- User Login Name', 'melapress-login-security' ); ?></i><br>
								{user_first_name} <i><?php esc_html_e( '- User First Name', 'melapress-login-security' ); ?></i><br>
								{user_last_name} <i><?php esc_html_e( '- User Last Name', 'melapress-login-security' ); ?></i><br>
								{user_display_name} <i><?php esc_html_e( '- User Display Name', 'melapress-login-security' ); ?></i><br>
								{admin_email} <i><?php esc_html_e( '- From email address / site admin email', 'melapress-login-security' ); ?></i><br>
								{remaining_time} <i><?php esc_html_e( '- Time until next login is allowed.', 'melapress-login-security' ); ?></i><br>	
							</div>
						</div>

						<tr valign="top">
							<br>
							<h1><?php esc_html_e( 'User messages template', 'melapress-login-security' ); ?></h1>
							<p class="description"><?php esc_html_e( 'On this page you can edit all of the user messages and prompts used by the plugin', 'melapress-login-security' ); ?></p>
							<br>
						</tr>

					</tbody>
				</table>	

				<table class="form-table has-sticky-bar">
					<tbody>

						<tr valign="top">
							<h3><?php esc_html_e( 'User requests password reset when the feature is disabled', 'melapress-login-security' ); ?></h3>
							<p class="description"><?php esc_html_e( 'This warning is shown when a user requests a password reset but an active Login Security Policy prohibits it.', 'melapress-login-security' ); ?></p>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Message', 'melapress-login-security' ); ?>
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
									wp_editor( $content, $editor_id, $settings );
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
							<h3><?php esc_html_e( 'User exceeds maximum number of failed logins.', 'melapress-login-security' ); ?></h3>
							<p class="description"><?php esc_html_e( 'This warning is shown when a user exceed the max allowed number of failed login attempts.', 'melapress-login-security' ); ?></p>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Message', 'melapress-login-security' ); ?>
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
									wp_editor( $content, $editor_id, $settings );
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
