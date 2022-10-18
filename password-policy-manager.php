<?php

/**
 * WPassword
 *
 * @copyright Copyright (C) 2013-%%YEAR%%, WP White Security - support@wpwhitesecurity.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: WPassword
 * Version:     2.6.1
 * Plugin URI:  https://www.wpwhitesecurity.com/wordpress-plugins/password-security/
 * Description: WPassword allows you to configure password policies for your WordPress website users, ensuring top notch password security.
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
 */


// Namespace: ppm-wp, ppm_wp


if ( ! function_exists( 'ppm_freemius' ) ) {
	/**
	 * Freemius SDK.
	 *
	 * @since 2.7.0
	 */
	if ( file_exists( plugin_dir_path( __FILE__ ) . '/sdk/ppm-freemius.php' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/sdk/ppm-freemius.php';
	}

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


	/**
	 * Meta key for password history
	 */
	define ('PPM_WP_META_KEY', PPMWP_PREFIX . '_password_history');

	/**
	 * Meta key for delayed reset
	 */
	define ('PPM_WP_META_DELAYED_RESET_KEY', PPMWP_PREFIX . '_delayed_reset');

	/**
	 * Meta key for expired password mark
	 */
	define ('PPM_WP_META_PASSWORD_EXPIRED', PPMWP_PREFIX . '_password_expired');

	define ('PPM_WP_META_EXPIRED_EMAIL_SENT', PPMWP_PREFIX . '_expired_email_sent');

	/**
	 * Meta key for new user mark
	 */
	define ('PPM_WP_META_NEW_USER', PPMWP_PREFIX . '_new_user_register');

	define ('PPM_WP_META_USER_RESET_PW_ON_LOGIN', PPMWP_PREFIX . '_reset_pw_on_login');


	define( 'PPMWP_DORMANT_FLAG_KEY', PPMWP_PREFIX . '_inactive_user_flag' );

	define( 'PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY', PPMWP_PREFIX . '_is_blocked_user' );

	define( 'PPMWP_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP', PPMWP_PREFIX . '_blocked_since' );
	
	/*
	 * Include classes that define and provide policies
	 */

	$autoloader_file_path = PPM_WP_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
	if ( file_exists( $autoloader_file_path ) ) {
		require_once $autoloader_file_path;
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

	/*
	 * Include classes that enforce and implement policies
	 */

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
	 * Include class that manipulates password history
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-ppm-failed-logins.php';

	/**
	 * Include class that handles shortcodes.
	 */
	require_once PPM_WP_PATH . 'app/enforcers/class-shortcodes.php';

	/**
	 * Checks if a user is exempted from the policies
	 *
	 * @param integer $user_id
	 * @return boolean
	 */
	function ppm_is_user_exempted( $user_id = false ) {

		$exempted = PPM_WP::is_user_exempted($user_id);

		return $exempted;
	}

	/**
	 * Get an instance of the main class
	 *
	 * @return object
	 */
	function ppm_wp() {

		/**
		 * Instantiate & start the plugin
		 */
		$ppm = PPM_WP::_instance();

		return $ppm;
	}

	add_action( 'plugins_loaded', 'ppm_wp' );

	/**
	 * @todo uninstall :(
	 * @todo extra line to test multisite
	 */

	register_activation_hook(__FILE__, array( 'PPM_WP', 'activation_timestamp' ) );
	register_deactivation_hook(__FILE__, array( 'PPM_WP', 'ppm_deactivation' ) );
	// Add freemius comptaible uninstall function.
	ppm_freemius()->add_action( 'after_uninstall', array( 'PPM_WP', 'cleanup' ) );
}
