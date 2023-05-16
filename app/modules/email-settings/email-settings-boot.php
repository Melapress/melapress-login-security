<?php
/**
 * Loads premium packaged into plugin.
 *
 * @package PPMWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once PPM_WP_PATH . 'app/modules/email-settings/class-ppm-email-settings.php';

add_action( 'mls_extension_init', 'email_boot' );

function email_boot() {
    $ppm_email_settings = new PPM_Email_Settings();
    $ppm_email_settings->init();
}

