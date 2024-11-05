<?php
/**
 * Loads premium packaged into plugin.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( '\MLS\RestrictLogins' ) ) {
	add_action( 'ppm_settings_additional_settings', array( '\MLS\RestrictLogins', 'settings_markup' ), 30, 2 );
	add_action( 'ppm_message_settings_markup_footer', array( '\MLS\RestrictLogins', 'add_template_settings' ), 90 );
	add_action( 'show_user_profile', array( '\MLS\RestrictLogins', 'add_user_profile_field' ), 10 );
	add_action( 'edit_user_profile', array( '\MLS\RestrictLogins', 'add_user_profile_field' ), 10 );
	add_action( 'personal_options_update', array( '\MLS\RestrictLogins', 'save_user_profile_field' ) );
	add_action( 'edit_user_profile_update', array( '\MLS\RestrictLogins', 'save_user_profile_field' ) );
	add_action( 'authenticate', array( '\MLS\RestrictLogins', 'pre_login_check' ), 30, 3 );
}
