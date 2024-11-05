<?php
/**
 * MLS Constants
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
* Define Constants
*/
if ( ! defined( 'MLS_PATH' ) ) {
	/**
	 * The plugin's absolute path for inclusions
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_PATH', plugin_dir_path( MLS_FILE ) );
}

if ( ! defined( 'MLS_PLUGIN_URL' ) ) {
	/**
	 * The plugin's url for loading assets
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_PLUGIN_URL', plugin_dir_url( MLS_FILE ) );
}

if ( ! defined( 'MLS_BASENAME' ) ) {
	/**
	 * The plugin's base directory
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_BASENAME', plugin_basename( MLS_FILE ) );
}

if ( ! defined( 'MLS_PREFIX' ) ) {
	/**
	 * The plugin's prefix
	 *
	 * @since 2.0.0
	 */
	if ( ! empty( get_site_option( 'ppmwp_options', false ) ) ) {
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
	define( 'MLS_VERSION', '2.0.0' );
}

if ( ! defined( 'MLS_MENU_SLUG' ) ) {
	/**
	 * Meta key flag to mark user as blocked.
	 *
	 * @since 2.0.0
	 */
	define( 'MLS_MENU_SLUG', 'mls-policies' );
}
