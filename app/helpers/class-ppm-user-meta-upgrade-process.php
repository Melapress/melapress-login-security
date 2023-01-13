<?php

if ( ! class_exists( 'WP_Background_Process' ) && file_exists( PPM_WP_PATH . 'third-party/wp-background-process.php' ) ) {
  require_once PPM_WP_PATH . 'third-party/wp-background-process.php';
}
if ( ! class_exists( 'WP_Async_Request' ) && file_exists( PPM_WP_PATH . 'third-party/wp-async-request.php' ) ) {
  require_once PPM_WP_PATH . 'third-party/third-party/wp-async-request.php';
}

class PPM_User_Meta_Upgrade_Process extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'ppm_user_meta_upgrade';

  protected function task( $item ) {

    if ( empty( $item ) || ! isset( $item ) ) {
      return false;
    }

    $user = $item;

    $password_history = get_user_meta( $user->ID, '_ppm_wp_password_history', true );
    if ( ! empty( $password_history ) ) {
      add_user_meta( $user->ID, PPM_WP_META_KEY, $password_history );
			delete_user_meta( $user->ID, '_ppm_wp_password_history' );
    }

    $delayed_reset = get_user_meta( $user->ID, '_ppm_wp_delayed_reset', true );
    if ( ! empty( $delayed_reset ) ) {
      add_user_meta( $user->ID, PPM_WP_META_DELAYED_RESET_KEY, $delayed_reset );
			delete_user_meta( $user->ID, '_ppm_wp_delayed_reset' );
    }

    $password_expired = get_user_meta( $user->ID, '_ppm_wp_password_expired', true );
    if ( ! empty( $password_expired ) ) {
      add_user_meta( $user->ID, PPM_WP_META_PASSWORD_EXPIRED, $password_expired );
			delete_user_meta( $user->ID, '_ppm_wp_password_expired' );
    }

    $new_user_register = get_user_meta( $user->ID, '_ppm_wp_new_user_register', true );
    if ( ! empty( $new_user_register ) ) {
      add_user_meta( $user->ID, PPM_WP_META_NEW_USER, $new_user_register );
			delete_user_meta( $user->ID, '_ppm_wp_new_user_register' );
    }

    $reset_pw_on_login = get_user_meta( $user->ID, '_ppm_wp_reset_pw_on_login', true );
    if ( ! empty( $reset_pw_on_login ) ) {
      add_user_meta( $user->ID, PPM_WP_META_USER_RESET_PW_ON_LOGIN, $reset_pw_on_login );
			delete_user_meta( $user->ID, '_ppm_wp_reset_pw_on_login' );
    }

    $inactive_user_flag = get_user_meta( $user->ID, '_ppm_wp_dormant_user_flag', true );
    if ( ! empty( $inactive_user_flag ) ) {
      add_user_meta( $user->ID, PPMWP_DORMANT_FLAG_KEY, $inactive_user_flag );
			delete_user_meta( $user->ID, '_ppm_wp_inactive_user_flag' );
    }

    return false;
  }

  /**
	 * @inheritDoc
	 */
	protected function complete() {
		parent::complete();

		// Lets keep a note, so we know its all done.
		update_site_option( 'ppmwp_prefixes_updated', 'true' );
	}
}
