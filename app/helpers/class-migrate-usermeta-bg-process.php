<?php
/**
 * Handle BG processes.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles background resets.
 *
 * @since 2.0.0
 */
class Migrate_UserMeta_BG_Process extends \WP_Background_Process {

	/**
	 * Action to run.
	 *
	 * @var string
	 *
	 * @since 2.0.0
	 */
	protected $action = 'mls_migrate_usermeta';

	/**
	 * Task logic.
	 *
	 * @param int $item - User ID.
	 *
	 * @return bool.
	 *
	 * @since 2.0.0
	 */
	protected function task( $item ) {
		if ( empty( $item ) || ! isset( $item ) ) {
			return false;
		}

		$user = get_user_by( 'ID', $item['ID'] );

		global $wpdb;
		$users_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d", array( 'ppmwp_%', $user->ID ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $users_data as $key => $meta_name ) {
			$meta_name = $meta_name['meta_key'];
			$value     = get_user_meta( $user->ID, $meta_name, true );
			update_user_meta( $user->ID, str_replace( 'ppmwp', 'mls', $meta_name ), $value );
			delete_user_meta( $user->ID, $meta_name );
		}

		return false;
	}

	/**
	 * Called when background process has completed.
	 */
	protected function completed() {
		update_site_option( 'mls_migration_status', 'Completed' );
		update_site_option( 'mls_200_migration_complete', true );
		delete_site_option( 'mls_migration_required' );
		do_action( $this->identifier . '_completed' );
	}
}
