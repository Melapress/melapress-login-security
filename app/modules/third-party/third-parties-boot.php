<?php
/**
 * Loads premium packaged into plugin.
 *
 * @package PPMWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once PPM_WP_PATH . 'app/modules/third-party/class-thirdparties.php';

add_action( 'mls_extension_init', 'third_parties_boot' );

function third_parties_boot() {
    $ppm_tp = new PPM_ThirdParties();
    $ppm_tp->init();
}

