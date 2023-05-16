<?php
/**
 * Loads premium packaged into plugin.
 *
 * @package PPMWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once PPM_WP_PATH . 'app/modules/inactive-users/class-inactive-users.php';
require_once PPM_WP_PATH . 'app/modules/inactive-users/class-inactive-users-check-bg-process.php';

add_action( 'init', 'setup_inactive_users_feature' );

add_filter( 'mls_inactive_users_init', 'setup_main_variable' );

function setup_inactive_users_feature() {
    $ppm = ppm_wp();
    $ppm->inactive = new \PPMWP\InactiveUsers( $ppm );
    $ppm->inactive->init();

    new PPM_CheckInactiveUsers_Process();
}

function setup_main_variable() {    
    $ppm = ppm_wp();
    $inactive = new \PPMWP\InactiveUsers( $ppm );
    if ( is_multisite() ) {
        add_action( 'network_admin_menu', array( $inactive, 'admin_menu' ) );
    } else {
        add_action( 'admin_menu', array( $inactive, 'admin_menu' ) );
    }
    return $inactive;
}
