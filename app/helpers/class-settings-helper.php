<?php
/**
 * Helper class to get options within this plugin.
 *
 * @package MelapressLoginSecurity
 * @since 2.1.0
 */

declare(strict_types=1);

namespace MLS\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class for getting various options for the plugin.
 *
 * @since 2.1.0
 */
class Settings_Helper {

	/**
	 * Cache site options
	 *
	 * @var array
	 *
	 * @since 2.1.0
	 */
	public static array $site_options_cache = array();

	/**
	 * Cache options.
	 *
	 * @var array
	 *
	 * @since 2.1.0
	 */
	public static array $options_cache = array();

	/**
	 * Get MLS option from db or cache.
	 *
	 * @param   string $setting_to_get                     Item to get.
	 * @param   mixed  $default_value                      Default value.
	 * @param   false  $get_site_option_instead_of_option  Get site_option, or option.
	 *
	 * @return  mixed                                      What we found.
	 *
	 * @since 2.1.0
	 */
	public static function get_mls_setting( $setting_to_get, $default_value = false, $get_site_option_instead_of_option = false ) {
		// If we have it stored, use it.
		if ( $get_site_option_instead_of_option && isset( self::$site_options_cache[ $setting_to_get ] ) && ! empty( self::$site_options_cache[ $setting_to_get ] ) ) {
			return self::$site_options_cache[ $setting_to_get ];
		} elseif ( ! $get_site_option_instead_of_option && isset( self::$options_cache[ $setting_to_get ] ) && ! empty( self::$options_cache[ $setting_to_get ] ) ) {
			return self::$options_cache[ $setting_to_get ];
		}

		// Nothing stored by this point, so grab, cache it and send it back.
		if ( $get_site_option_instead_of_option ) {
			self::$site_options_cache[ $setting_to_get ] = get_site_option( $setting_to_get, $default_value );
			return self::$site_options_cache[ $setting_to_get ];
		} else {
			self::$options_cache[ $setting_to_get ] = get_option( $setting_to_get, $default_value );
			return self::$options_cache[ $setting_to_get ];
		}
	}
}
