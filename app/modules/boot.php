<?php
/**
 * Boot file for the extensions.
 *
 * @since 7.0.0
 * @package C4WP
 */

if ( file_exists( PPM_WP_PATH . 'app/modules/ExtensionLoader.php' ) ) {
	require_once PPM_WP_PATH . 'app/modules/ExtensionLoader.php';

	// include extensions for premium version.
	$loader = new \PPMWP\ExtensionLoader(
		function ( $slug, $allowed_plans ) {
			// first we check if no-one tampered with the config file, if they did, no extensions are allowed.
			$expected_checksums          = array(
				'2dfc89ce96cb73230917052081e112c3',
			);

			$real_checksum = \PPMWP\ExtensionLoader::stored_cache_checksum();

			if ( ! in_array( $real_checksum, $expected_checksums, true ) ) {
				return false;
			}

			// use Freemius to determine if extension is allowed or not; all extensions are allowed at the moment.
			if ( is_array( $allowed_plans ) && ! empty( $allowed_plans ) ) {
				$plan = ppm_freemius()->get_plan();
				if ( $plan instanceof FS_Plugin_Plan ) {
					if ( in_array( $plan->name, $allowed_plans, true ) ) {
						return true;
					}
				}
			}

			return false;
		},
		array(
			'zVGtaxjMGK2ntbo' => 'third-party',
			'oEY8ZzSEXdIgF6G' => 'email-settings',
			'BHm1Jyf2jxwvsvy' => 'inactive-users',
			'ATnbWZNhqbiJaro' => 'failed-logins',
		)
	);
	$loader->bootstrap();
}
