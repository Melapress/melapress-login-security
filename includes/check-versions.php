<?php
/**
 * Handles deactivating versions based on recently activated plugin version.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* @free:start */
if ( ! function_exists( 'mls_free_on_plugin_activation' ) ) {
	/**
	 * Takes care of deactivation of the premium plugin when the free plugin is activated.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	function mls_free_on_plugin_activation() {
		update_site_option( MLS_PREFIX . '_redirect_to_settings', true );
		$premium_version_slug = 'melapress-login-security-premium/melapress-login-security-premium.php';
		if ( is_plugin_active( $premium_version_slug ) ) {
			deactivate_plugins( $premium_version_slug, true );
		}
	}	
}
/* @free:end */