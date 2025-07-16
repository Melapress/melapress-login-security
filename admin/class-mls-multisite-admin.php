<?php
/**
 * Melapress Login Security Multisite Support class.
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

if ( ! class_exists( '\MLS\Admin\Network_Admin' ) ) {

	/**
	 * Network_Admin extend to Admin class.
	 *
	 * @since 2.0.0
	 */
	class Network_Admin extends \MLS\Admin\Admin {

		/**
		 * Class construct.
		 *
		 * @param array|object $options PPM options.
		 * @param array|object $settings PPM setting options.
		 * @param array|object $setting_options Get current role option.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function __construct( $options, $settings, $setting_options ) {
			self::$options     = $options;
			self::$settings    = $settings;
			self::$setting_tab = $setting_options;

			add_filter( 'network_admin_plugin_action_links_' . MLS_BASENAME, array( __CLASS__, 'plugin_action_links' ), 10, 1 );
			add_action( 'network_admin_menu', array( __CLASS__, 'admin_menu' ) );

			// Ajax.
			add_action( 'wp_ajax_get_users_roles', array( __CLASS__, 'search_users_roles' ) );
			add_action( 'wp_ajax_mls_send_test_email', array( __CLASS__, 'send_test_email' ) );
			add_action( 'wp_ajax_mls_process_reset', array( '\MLS\Reset_Passwords', 'process_global_password_reset' ) );

			// Add dialog box.
			add_action( 'admin_footer', array( __CLASS__, 'admin_footer_session_expired_dialog' ) );
			add_action( 'admin_footer', array( __CLASS__, 'popup_notices' ) );

			$options_master_switch    = OptionsHelper::string_to_bool( self::$options->master_switch );
			$settings_master_switch   = OptionsHelper::string_to_bool( self::$settings->master_switch );
			$inherit_policies_setting = OptionsHelper::string_to_bool( self::$settings->inherit_policies );

			$is_needed = ( $options_master_switch || ( $settings_master_switch || ! $inherit_policies_setting ) );

			if ( $is_needed ) {
				// Enqueue admin scripts.
				if ( OptionsHelper::string_to_bool( self::$settings->enforce_password ) ) {
					return;
				}
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'global_admin_enqueue_scripts' ) );
			}

			if ( class_exists( '\MLS\Failed_Logins' ) ) {
				add_action( 'network_admin_menu', array( '\MLS\Failed_Logins', 'add_locked_users_admin_menu' ), 20, 3 );
			}

			add_action( 'network_admin_notices', array( __CLASS__, 'plugin_was_updated_banner' ), 10, 3 );
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
		 * Register admin menu.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function admin_menu() {
			// Add admin menu page.
			$hook_name = add_menu_page( __( 'Login Security Policies', 'melapress-login-security' ), __( 'Login Security', 'melapress-login-security' ), 'manage_network_options', MLS_MENU_SLUG, array( __CLASS__, 'screen' ), 'data:image/svg+xml;base64,' . melapress_login_security()->icon, 99 );

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
				),
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
		}

		/**
		 * Network admin notice.
		 *
		 * @param string $callback_function Callback function.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function notice( $callback_function ) {
			add_action( 'network_admin_notices', array( __CLASS__, $callback_function ) );
		}

		/**
		 * Search User
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
				'blog_id'        => 0,
				'exclude'        => $exclude_users,
				'search'         => '*' . $search_str . '*',
				'search_columns' => array(
					'user_login',
					'user_email',
					'user_nicename',
					'user_url',
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
				'blog_id'    => 0,
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
	}

}
