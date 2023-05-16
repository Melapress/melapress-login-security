<?php
/**
 * Loads premium packaged into plugin.
 *
 * @package PPMWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once PPM_WP_PATH . 'app/modules/failed-logins/class-ppm-failed-logins.php';

add_action( 'mls_extension_init', 'failed_logins_boot' );

function failed_logins_boot() {
    $failed_logins = new PPM_Failed_Logins();
    $failed_logins->init();
}

