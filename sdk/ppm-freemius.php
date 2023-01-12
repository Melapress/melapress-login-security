<?php
/**
 * PPM Freemius SDK
 * Freemius SDK initialization file for PPM.
 *
 * @package WordPress
 * @subpackage Freemius
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Freemius SDK
 *
 * Create a helper function for easy SDK access.
 *
 * @return array
 * @since  2.7.0
 */
function ppm_freemius() {
	global $ppm_freemius;

	if ( ! isset( $ppm_freemius ) ) {
		// Define constant for freemius multisite support.
		define( 'WP_FS__PRODUCT_4028_MULTISITE', true );

		// Include Freemius SDK.
		$freemius_path = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . implode(
			DIRECTORY_SEPARATOR,
			array(
				'..',
				'vendor',
				'freemius',
				'wordpress-sdk',
				'start.php',
			)
		);

		if ( ! file_exists( $freemius_path ) ) {
			return $ppm_freemius;
		}

		require_once $freemius_path;
		// Check anonymous mode.
		$freemius_state = get_site_option( 'ppm_freemius_state', 'anonymous' );
		$is_anonymous   = 'anonymous' === $freemius_state || 'skipped' === $freemius_state;
		$is_premium     = true;
		$is_anonymous   = ( $is_premium ? false : $is_anonymous );
		// Trial arguments.
		$trial_args   = array(
			'days'               => 7,
			'is_require_payment' => false,
		);
		$ppm_freemius = fs_dynamic_init(
			array(
				'id'              => 4028,
				'slug'            => 'melapress-login-security',
				'premium_slug'    => 'melapress-login-security-premium',
				'type'            => 'plugin',
				'public_key'      => 'pk_9abad03ceb8172d40170994a44140',
				'premium_suffix'  => '(Premium)',
				'is_premium'      => true,
				'is_premium_only' => false,
				'has_addons'      => false,
				'has_paid_plans'  => true,
				'trial'           => $trial_args,
				'has_affiliation' => false,
				'menu'            => array(
					'slug'        => 'ppm_wp_settings',
					'support'     => false,
					'affiliation' => false,
					'network'     => true,
				),
				'anonymous_mode'  => $is_anonymous,
				'is_live'         => true,
			)
		);
	}

	return $ppm_freemius;
}

// Init Freemius.
ppm_freemius();

// Signal that SDK was initiated.
do_action( 'ppm_freemius_loaded' );
