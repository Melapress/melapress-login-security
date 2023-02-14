<?php
/**
 * WPassword
 *
 * @copyright Copyright (C) 2013-2023, WP White Security - support@wpwhitesecurity.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: Melapress Login Security
 * Version:     1.0.0
 * Plugin URI:  https://www.wpwhitesecurity.com/wordpress-plugins/password-security/
 * Description: Configure password policies and help your users use strong passwords. Ensure top notch password security on your website by beefing up the security of your user accounts.
 * Author:      WP White Security
 * Author URI:  https://www.wpwhitesecurity.com/
 * Text Domain: ppm-wp
 * Domain Path: /languages/
 * License:     GPL v3
 * Requires at least: 5.0
 * WC tested up to: 5.6.0
 * Requires PHP: 7.0
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
 * @package wordpress
 * @subpackage wpassword
 */

// Namespace: ppm-wp, ppm_wp.

// Setup function name based on build.
$mpls = 'ppm_freemius';
$mpls = 'ppm_wp';

if ( ! function_exists( 'mls_free_on_plugin_activation' ) ) {
	/**
	 * Takes care of deactivation of the premium plugin when the free plugin is activated.
	 */
	function mls_free_on_plugin_activation() {
		$premium_version_slug = 'melapress-login-security-premium/melapress-login-security-premium.php';
		if ( is_plugin_active( $premium_version_slug ) ) {
			deactivate_plugins( $premium_version_slug, true );
		}
	}

	register_activation_hook( __FILE__, 'mls_free_on_plugin_activation' );
}
// phpcs:ignore

if ( ! function_exists( $mpls ) ) {


	/*
	 * Define Constants
	 */

	if ( ! defined( 'PPM_WP_PATH' ) ) {
		/**
		 * The plugin's absolute path for inclusions
		 */
		define( 'PPM_WP_PATH', plugin_dir_path( __FILE__ ) );
	}

	if ( ! defined( 'PPM_WP_URL' ) ) {
		/**
		 * The plugin's url for loading assets
		 */
		define( 'PPM_WP_URL', plugin_dir_url( __FILE__ ) );
	}

	if ( ! defined( 'PPM_WP_BASENAME' ) ) {
		/**
		 * The plugin's base directory
		 */
		define( 'PPM_WP_BASENAME', plugin_basename( __FILE__ ) );
	}

	if ( ! defined( 'PPMWP_PREFIX' ) ) {
		define( 'PPMWP_PREFIX', 'ppmwp' );
	}

	if ( ! defined( 'PPM_WP_FILE' ) ) {
		/**
		 * The plugin's absolute path for inclusions
		 */
		define( 'PPM_WP_FILE', __FILE__ );
	}

	if ( ! defined( 'PPM_WP_META_KEY' ) ) {
		/**
		 * Meta key for password history
		 */
		define( 'PPM_WP_META_KEY', PPMWP_PREFIX . '_password_history' );
	}

	if ( ! defined( 'PPM_WP_META_DELAYED_RESET_KEY' ) ) {
		/**
		 * Meta key for delayed reset
		 */
		define( 'PPM_WP_META_DELAYED_RESET_KEY', PPMWP_PREFIX . '_delayed_reset' );
	}

	if ( ! defined( 'PPM_WP_META_PASSWORD_EXPIRED' ) ) {
		/**
		 * Meta key for expired password mark
		 */
		define( 'PPM_WP_META_PASSWORD_EXPIRED', PPMWP_PREFIX . '_password_expired' );
	}

	if ( ! defined( 'PPM_WP_META_EXPIRED_EMAIL_SENT' ) ) {
		/**
		 * Meta key to flag email was sent.
		 */
		define( 'PPM_WP_META_EXPIRED_EMAIL_SENT', PPMWP_PREFIX . '_expired_email_sent' );
	}

	if ( ! defined( 'PPM_WP_META_NEW_USER' ) ) {
		/**
		 * Meta key for new user mark.
		 */
		define( 'PPM_WP_META_NEW_USER', PPMWP_PREFIX . '_new_user_register' );
	}

	if ( ! defined( 'PPM_WP_META_USER_RESET_PW_ON_LOGIN' ) ) {
		/**
		 * Meta key flag to reset on next login.
		 */
		define( 'PPM_WP_META_USER_RESET_PW_ON_LOGIN', PPMWP_PREFIX . '_reset_pw_on_login' );
	}

	if ( ! defined( 'PPMWP_DORMANT_FLAG_KEY' ) ) {
		/**
		 * Meta key flag to mark user as inactive.
		 */
		define( 'PPMWP_DORMANT_FLAG_KEY', PPMWP_PREFIX . '_inactive_user_flag' );
	}

	if ( ! defined( 'PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY' ) ) {
		/**
		 * Meta key flag to mark user as blocked.
		 */
		define( 'PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY', PPMWP_PREFIX . '_is_blocked_user' );
	}

	if ( ! defined( 'PPMWP_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP' ) ) {
		/**
		 * Meta key flag to mark user as blocked.
		 */
		define( 'PPMWP_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP', PPMWP_PREFIX . '_blocked_since' );
	}

	if ( ! defined( 'PPMWP_VERSION' ) ) {
		/**
		 * Meta key flag to mark user as blocked.
		 */
		define( 'PPMWP_VERSION', '1.0.0' );
	}


	// Include classes usually loaded via composer.
	if ( ! file_exists( plugin_dir_path( __FILE__ ) . '/sdk/ppm-freemius.php' ) ) {
		require_once PPM_WP_PATH . 'app/Utilities/class-validatorfactory.php';
		require_once PPM_WP_PATH . 'app/Validators/class-validator.php';
		if ( ! class_exists( 'WP_Async_Request' ) && file_exists( PPM_WP_PATH . 'third-party/wp-async-request.php' ) ) {
			require_once PPM_WP_PATH . 'third-party/wp-async-request.php';
		}
		if ( ! class_exists( 'WP_Background_Process' ) && file_exists( PPM_WP_PATH . 'third-party/wp-background-process.php' ) ) {
			require_once PPM_WP_PATH . 'third-party/wp-background-process.php';
		}
	}

	/**
	 * Include class that provides the saved options for run-time
	 */
	require_once PPM_WP_PATH . 'app/policies/class-ppm-wp-options.php';

	/**
	 * Include class that provides all the string messages shown to the user
	 */
	require_once PPM_WP_PATH . 'app/policies/class-ppm-wp-msgs.php';

	/**
	 * Include class that provides rules to check passwords against
	 */
	require_once PPM_WP_PATH . 'app/policies/class-ppm-wp-regex.php';

	/**
	 * Include class that expires passwords
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-ppm-wp-expire.php';

	/**
	 * Include class that resets passwords
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-ppm-wp-reset.php';

	/**
	 * Include class that validates and generates passwords
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-ppm-wp-password-check.php';

	/**
	 * Include class that hooks into user forms
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-ppm-wp-forms.php';

	/**
	 * Include class that manipulates password history
	 */
	require_once PPM_WP_PATH . 'app/helpers/class-ppm-wp-password-gen.php';

	/**
	 * Include class that manipulates password history
	 */
	require_once PPM_WP_PATH . 'app/helpers/class-ppm-wp-history.php';

	/**
	 * Include class that show WP pointer
	 */
	require_once PPM_WP_PATH . 'app/helpers/class-pointer.php';

	/**
	 * Include Admin class
	 */
	require_once PPM_WP_PATH . 'admin/class-ppm-wp-admin.php';

	/**
	 * Include multisite admin class
	 */
	require_once PPM_WP_PATH . 'admin/class-ppm-wp-ms-admin.php';

	/**
	 * Include main class
	 */
	require_once PPM_WP_PATH . 'app/class-ppm-wp.php';

	/**
	 * Include helpers.
	 */
	require_once PPM_WP_PATH . 'app/helpers/class-ppm-user-meta-upgrade-process.php';
	require_once PPM_WP_PATH . 'app/helpers/class-ppm-apply-timestamp-for-users-process.php';
	require_once PPM_WP_PATH . 'app/helpers/class-ppm-mb-string-helper.php';

	/**
	 * Include new user class
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-new-user.php';

	/**
	 * Include new user class
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-user-profile.php';

	/**
	 * Include reset password bg processing class
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-ppm-wp-reset-bg-process.php';

	/**
	 * Include class that handles shortcodes.
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-shortcodes.php';

	/**
	 * Checks if a user is exempted from the policies
	 *
	 * @param integer $user_id - ID of user we are checking.
	 * @return boolean
	 */
	if ( ! function_exists( 'ppm_is_user_exempted' ) ) {
		function ppm_is_user_exempted( $user_id = false ) {
			$exempted = PPM_WP::is_user_exempted( $user_id );
			return $exempted;
		}
	}

	/**
	 * Get an instance of the main class
	 *
	 * @return object
	 */
	if ( ! function_exists( 'ppm_wp' ) ) {
		function ppm_wp() {

			/**
			 * Instantiate & start the plugin
			 */
			$ppm = PPM_WP::_instance();
			return $ppm;
		}
	}

	add_action( 'plugins_loaded', 'ppm_wp' );
	register_activation_hook( __FILE__, array( 'PPM_WP', 'activation_timestamp' ) );
	register_deactivation_hook( __FILE__, array( 'PPM_WP', 'ppm_deactivation' ) );
}
