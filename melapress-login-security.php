<?php
/**
 * Melapress Login Security
 *
 * @copyright Copyright (C) 2013-2026, Melapress - support@melapress.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: Melapress Login Security
 * Version:     2.3.0
 * Plugin URI:  https://melapress.com/wordpress-login-security/
 * Description: Configure password policies and help your users use strong passwords. Ensure top notch password security on your website by beefing up the security of your user accounts.
 * Author:      Melapress
 * Author URI:  https://melapress.com/
 * Text Domain: melapress-login-security
 * Domain Path: /languages/
 * License:     GPL v3
 * Requires at least: 5.5
 * WC tested up to: 9.3.3
 * Requires PHP: 7.3
 * Network: true
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package MelapressLoginSecurity
 */

use MLS\Licensing\Licensing_Factory;

// Setup function name based on build.
// $melapress_login_security = 'melapress_login_security_freemius';
// @free:start
// $melapress_login_security = 'melapress_login_security';
// @free:end

require_once \plugin_dir_path( __FILE__ ) . '/includes/check-versions.php';
require_once \plugin_dir_path( __FILE__ ) . '/includes/user-functions.php';
require_once \plugin_dir_path( __FILE__ ) . 'class-plugin-deactivation.php';

new \Deactivation_Feedback_Server\Plugin_Deactivation();

// @free:start
\register_activation_hook( __FILE__, 'mls_free_on_plugin_activation' );
// @free:end

/**
 * Define Constants
 */

if ( ! defined( 'MLS_FILE' ) ) {
	/**
	 * The plugin's absolute path for inclusions
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_FILE', __FILE__ );
}

if ( ! defined( 'MLS_PATH' ) ) {
	/**
	 * The plugin's absolute path for inclusions
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_PATH', \plugin_dir_path( MLS_FILE ) );
}

if ( ! defined( 'MLS_PLUGIN_URL' ) ) {
	/**
	 * The plugin's url for loading assets
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_PLUGIN_URL', \plugin_dir_url( MLS_FILE ) );
}

if ( ! defined( 'MLS_BASENAME' ) ) {
	/**
	 * The plugin's base directory
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_BASENAME', \plugin_basename( MLS_FILE ) );
}

if ( ! defined( 'MLS_PREFIX' ) ) {
	/**
	 * The plugin's prefix
	 *
	 * @since 2.0.0
	 */
	if ( ! empty( \get_site_option( 'ppmwp_options', false ) ) ) {
		define( 'MLS_PREFIX', 'ppmwp' );
	} else {
		define( 'MLS_PREFIX', 'mls' );
	}
}

if ( ! defined( 'MLS_PW_HISTORY_META_KEY' ) ) {
	/**
	 * Meta key for password history
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_PW_HISTORY_META_KEY', MLS_PREFIX . '_password_history' );
}

if ( ! defined( 'MLS_DELAYED_RESET_META_KEY' ) ) {
	/**
	 * Meta key for delayed reset
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_DELAYED_RESET_META_KEY', MLS_PREFIX . '_delayed_reset' );
}

if ( ! defined( 'MLS_PASSWORD_EXPIRED_META_KEY' ) ) {
	/**
	 * Meta key for expired password mark
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_PASSWORD_EXPIRED_META_KEY', MLS_PREFIX . '_password_expired' );
}

if ( ! defined( 'MLS_EXPIRED_EMAIL_SENT_META_KEY' ) ) {
	/**
	 * Meta key to flag email was sent.
	 */
	define( 'MLS_EXPIRED_EMAIL_SENT_META_KEY', MLS_PREFIX . '_expired_email_sent' );
}

if ( ! defined( 'MLS_NEW_USER_META_KEY' ) ) {
	/**
	 * Meta key for new user mark.
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_NEW_USER_META_KEY', MLS_PREFIX . '_new_user_register' );
}

if ( ! defined( 'MLS_USER_RESET_PW_ON_LOGIN_META_KEY' ) ) {
	/**
	 * Meta key flag to reset on next login.
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_USER_RESET_PW_ON_LOGIN_META_KEY', MLS_PREFIX . '_reset_pw_on_login' );
}

if ( ! defined( 'MLS_USER_INACTIVE_META_KEY' ) ) {
	/**
	 * Meta key flag to mark user as inactive.
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_USER_INACTIVE_META_KEY', MLS_PREFIX . '_inactive_user_flag' );
}

if ( ! defined( 'MLS_USER_BLOCK_FURTHER_LOGINS_META_KEY' ) ) {
	/**
	 * Meta key flag to mark user as blocked.
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_USER_BLOCK_FURTHER_LOGINS_META_KEY', MLS_PREFIX . '_is_blocked_user' );
}

if ( ! defined( 'MLS_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP_META_KEY' ) ) {
	/**
	 * Meta key flag to mark user as blocked.
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP_META_KEY', MLS_PREFIX . '_blocked_since' );
}

if ( ! defined( 'MLS_VERSION' ) ) {
	/**
	 * Meta key flag to mark user as blocked.
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_VERSION', '2.3.0' );
}

if ( ! defined( 'MLS_MENU_SLUG' ) ) {
	/**
	 * Meta key flag to mark user as blocked.
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_MENU_SLUG', 'mls-policies' );
}


// if ( ! function_exists( $melapress_login_security ) ) {


	/*
	 * Include classes that define and provide policies
	 */
	$autoloader_file_path = MLS_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if ( file_exists( $autoloader_file_path ) ) {
	require_once $autoloader_file_path;
}

	/**
	 * Get an instance of the main class
	 *
	 * @return object
	 *
	 * @since 2.0.0
	 */
if ( ! function_exists( 'melapress_login_security' ) ) {
	/**
	 * Get an instance of the main class
	 *
	 * @return object
	 *
	 * @since 2.0.0
	 */
	function melapress_login_security() {

		/**
		 * Instantiate & start the plugin
		 *
		 * @since 2.0.0
		 */
		$mls = MLS_Core::get_instance();
		return $mls;
	}
}

	\add_action( 'plugins_loaded', 'melapress_login_security' );
	\register_activation_hook( __FILE__, array( 'MLS_Core', 'activation_timestamp' ) );
	\register_deactivation_hook( __FILE__, array( 'MLS_Core', 'ppm_deactivation' ) );


	// @free:start

	// Redirect to settings on activate.
	\add_action( 'admin_init', 'mls_plugin_activate_redirect' );

	/**
	 * Redirect to settings on plugin activation.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
if ( ! function_exists( 'mls_plugin_activate_redirect' ) ) {
	function mls_plugin_activate_redirect() {
		if ( \get_site_option( MLS_PREFIX . '_redirect_to_settings', false ) ) {
			// Check user has permission to access settings.
			if ( ! \current_user_can( 'manage_options' ) ) {
				return;
			}

			\delete_site_option( MLS_PREFIX . '_redirect_to_settings' );
			$url = \add_query_arg( 'page', 'mls-policies', \network_admin_url( 'admin.php' ) );
			\wp_safe_redirect( $url );
			exit;
		}
	}
}
	// @free:end

	\add_action( 'admin_init', 'mls_on_plugin_update', 10 );

	/**
	 * Redirect to settings on plugin update.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
if ( ! function_exists( 'mls_on_plugin_update' ) ) {
	/**
	 * Show notice to user on plugin version update.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	function mls_on_plugin_update() {

		$stored_version    = \get_site_option( MLS_PREFIX . '_active_version', false );
		$existing_settings = \get_site_option( MLS_PREFIX . '_options', false );

		if ( $existing_settings && ! empty( $existing_settings ) ) {
			if ( ! empty( $stored_version ) && version_compare( $stored_version, MLS_VERSION, '<' ) ) {
				\update_site_option( MLS_PREFIX . '_active_version', MLS_VERSION );
				\update_site_option( MLS_PREFIX . '_show_update_notice', true );

				\MLS\UpdateRoutines::plugin_upgraded( $stored_version, MLS_VERSION );
			} elseif ( empty( $stored_version ) ) {
				\update_site_option( MLS_PREFIX . '_active_version', MLS_VERSION );
				\update_site_option( MLS_PREFIX . '_show_update_notice', true );
			}

			if ( \get_site_option( MLS_PREFIX . '_show_update_notice', false ) ) {
				\delete_site_option( MLS_PREFIX . '_show_update_notice' );
				\update_site_option( MLS_PREFIX . '_update_notice_needed', true );
				$args = array(
					'page' => 'mls-policies',
				);
				$url  = \add_query_arg( $args, \network_admin_url( 'admin.php' ) );
				\wp_safe_redirect( $url );
				exit;
			}
		}

		if ( ! $stored_version ) {
			\update_site_option( MLS_PREFIX . '_active_version', MLS_VERSION );
		}

		if ( \get_site_option( MLS_PREFIX . '_show_update_notice', false ) ) {
			\delete_site_option( MLS_PREFIX . '_show_update_notice' );
			\update_site_option( MLS_PREFIX . '_update_notice_needed', true );
			$args = array(
				'page' => 'mls-policies',
			);
			$url  = \add_query_arg( $args, \network_admin_url( 'admin.php' ) );
			\wp_safe_redirect( $url );
			exit;
		}
	}
}
// }

/**
 * Declare compatibility with WC HPOS.
 *
 * @return void
 *
 * @since 2.0.0
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Fallback function for where there is PHP lower than v8.
	 *
	 * @param string $haystack - The string to be searched.
	 * @param string $needle - The string to search for.
	 *
	 * @return boolean
	 *
	 * @since 2.1.2
	 */
	function str_contains( string $haystack, string $needle ): bool {
		return '' === $needle || false !== strpos( $haystack, $needle );
	}
}
