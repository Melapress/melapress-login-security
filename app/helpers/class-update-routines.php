<?php
/**
 * Handle Plugin updates.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS;

use MLS\Reset_Passwords;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\MLS\UpdateRoutines' ) ) {

	/**
	 * Routines to run when the plugin is updated
	 *
	 * @since 2.0.0
	 */
	class UpdateRoutines {

		/**
		 * Handle routines on plugin upgrades.
		 *
		 * @param string $old_version - Previous version.
		 * @param string $new_version - New version.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function plugin_upgraded( $old_version, $new_version ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			if ( ! empty( $old_version ) && version_compare( $old_version, '2.0.0', '<' ) ) {

				// SETTINGS.
				// Specifically search for oboslete prefix.
				$settings = is_multisite() ? get_site_option( 'ppmwp_setting' ) : get_option( 'ppmwp_setting' );

				// Set from to use custom.
				if ( isset( $settings['from_email'] ) && ! empty( $settings['from_email'] ) ) {
					$settings['use_custom_from_email'] = 'custom_email';
				}

				// Add inactive exempt users to global list.
				if ( isset( $settings['inactive_exempt']['users'] ) && ! empty( $settings['inactive_exempt']['users'] ) ) {
					$settings['exempt']['users'] = $settings['inactive_exempt']['users'] + $settings['exempt']['users'];
				}

				// Specifically save to oboslete prefix, migration will resolve this.
				$update_settings = is_multisite() ? update_site_option( 'ppmwp_setting', $settings ) : update_option( 'ppmwp_setting', $settings );

				// POLICIES.
				self::determine_new_setting_values();

				update_site_option( 'mls_migration_required', true );
			}
		}

		/**
		 * Set modules to active in cases where the module had no enable/disable switch.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function determine_new_setting_values() {
			$policy   = is_multisite() ? get_site_option( 'ppmwp_options' ) : get_option( 'ppmwp_options' );
			$settings = is_multisite() ? get_site_option( 'ppmwp_setting' ) : get_option( 'ppmwp_setting' );

			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			// Password policies module.
			if ( ( isset( $policy['ui_rules']['mix_case'] ) && $policy['ui_rules']['mix_case'] ) || ( isset( $policy['ui_rules']['numeric'] ) && $policy['ui_rules']['numeric'] ) || ( isset( $policy['ui_rules']['special_chars'] ) && $policy['ui_rules']['special_chars'] ) ) {
				$policy['activate_password_policies'] = 'yes';
			}

			// Password expire module.
			if ( isset( $policy['password_expiry']['value'] ) && '0' !== $policy['password_expiry']['value'] ) {
				$policy['activate_password_expiration_policies'] = 'yes';
			}

			// Password recyling, set to try as previously had no 'off' switch.
			$policy['activate_password_recycle_policies'] = 'yes';

			// Messages.
			if ( isset( $policy['restrict_login_message'] ) && ! empty( $policy['restrict_login_message'] ) && $policy['restrict_login_message'] ) {
				$settings['restrict_login_ip_login_blocked_message'] = $policy['restrict_login_message'];
			}

			if ( isset( $policy['timed_login_message'] ) && ! empty( $policy['timed_login_message'] ) ) {
				$settings['timed_logins_login_blocked_message'] = $policy['timed_login_message'];
			}

			if ( isset( $policy['deactivated_account_message'] ) && ! empty( $policy['deactivated_account_message'] ) ) {
				$settings['inactive_user_account_locked_reset_disabled_message'] = $policy['deactivated_account_message'];
			}

			if ( isset( $policy['disable_self_reset_message'] ) && ! empty( $policy['disable_self_reset_message'] ) ) {
				$settings['password_reset_request_disabled_message'] = $policy['disable_self_reset_message'];
			}

			// Emails.
			if ( isset( $settings['user_password_expired_title'] ) && ! empty( $settings['user_password_expired_title'] ) ) {
				$settings['user_password_expired_email_subject'] = $settings['user_password_expired_title'];
			}

			if ( isset( $settings['user_reset_next_login_title'] ) && ! empty( $settings['user_reset_next_login_title'] ) ) {
				$settings['user_delayed_reset_email_subject'] = $settings['user_reset_next_login_title'];
			}

			if ( isset( $settings['user_unlocked_email_title'] ) && ! empty( $settings['user_unlocked_email_title'] ) ) {
				$settings['user_unlocked_email_subject'] = $settings['user_unlocked_email_title'];
			}

			if ( isset( $settings['user_unlocked_email_body'] ) && ! empty( $settings['user_unlocked_email_body'] ) ) {
				$settings['user_unlocked_email_body'] = $settings['user_unlocked_email_body'];
			}

			// Save updated rules.
			$update = is_multisite() ? update_site_option( 'ppmwp_options', $policy ) : update_option( 'ppmwp_options', $policy );
			$update = is_multisite() ? update_site_option( 'ppmwp_setting', $settings ) : update_option( 'ppmwp_setting', $settings );
		}

		/**
		 * Update plugin data from installs pre version 2.0.0.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function update_from_pre_200() {
			$has_run = is_multisite() ? get_site_option( 'mls_200_migration_complete' ) : get_option( 'mls_200_migration_complete' );

			$update_status = is_multisite() ? update_site_option( 'mls_migration_started', true ) : update_option( 'mls_migration_started', true );

			if ( $has_run ) {
				return;
			}

			$update_status = is_multisite() ? update_site_option( 'mls_migration_status', 'Options migration started' ) : update_option( 'mls_migration_status', 'Options migration started' );

			// Gather any possible entries for this plugin from the options table.
			global $wpdb;
			if ( is_multisite() ) {
				$prepared_query = $wpdb->prepare(
					"SELECT `meta_key` FROM `{$wpdb->sitemeta}` WHERE `meta_key` LIKE %s ORDER BY `meta_key` ASC",
					'ppmwp%'
				);
			} else {
				$prepared_query = $wpdb->prepare(
					"SELECT `option_name` FROM `{$wpdb->options}` WHERE `option_name` LIKE %s ORDER BY `option_name` ASC",
					'ppmwp%'
				);
			}
			$results = $wpdb->get_results( $prepared_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Move data to new prefix and clear out old entries.
			foreach ( $results as $key => $old_setting_prefix ) {
				$old_setting_prefix = is_multisite() ? $old_setting_prefix->meta_key : $old_setting_prefix->option_name;
				$value_to_migrate   = is_multisite() ? get_site_option( $old_setting_prefix ) : get_option( $old_setting_prefix );
				$update             = is_multisite() ? update_site_option( str_replace( 'ppmwp', 'mls', $old_setting_prefix ), $value_to_migrate ) : update_option( str_replace( 'ppmwp', 'mls', $old_setting_prefix ), $value_to_migrate );
				if ( $update ) {
					$delete = is_multisite() ? delete_site_option( $old_setting_prefix ) : delete_option( $old_setting_prefix );
				}
			}

			// Update settings.
			$settings = is_multisite() ? get_site_option( 'mls_setting' ) : get_option( 'mls_setting' );

			// Set from to use custom.
			if ( isset( $settings['from_email'] ) && ! empty( $settings['from_email'] ) ) {
				$settings['use_custom_from_email'] = 'custom_email';
			}

			// Add inactive exempt users to global list.
			if ( isset( $settings['inactive_exempt']['users'] ) && ! empty( $settings['inactive_exempt']['users'] ) ) {
				$settings['exempt']['users'] = $settings['inactive_exempt']['users'] + $settings['exempt']['users'];
			}

			$update_settings = is_multisite() ? update_site_option( 'mls_setting', $settings ) : update_option( 'mls_setting', $settings );

			update_site_option( 'mls_migration_status', 'Options migration complete, starting user data.' );

			self::migrate_usermeta();
		}

		/**
		 * Handles launching the BG process which updated the usermeta to the new prefix.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function migrate_usermeta() {
			// exclude exempted roles and users.
			$user_args = array(
				'fields' => array( 'ID' ),
			);

			// If check multisite installed OR not.
			if ( is_multisite() ) {
				$user_args['blog_id'] = 0;
			}

			// Send users for bg processing later.
			$total_users        = Reset_Passwords::count_users();
			$batch_size         = 50;
			$slices             = ceil( $total_users / $batch_size );
			$users              = array();
			$background_process = new \MLS\Migrate_UserMeta_BG_Process();

			update_site_option( 'mls_migration_status', 'User data being sent for background processing.' );

			for ( $count = 0; $count < $slices; $count++ ) {
				$user_args['number'] = $batch_size;
				$user_args['offset'] = $count * $batch_size;
				$users               = get_users( $user_args );

				if ( ! empty( $users ) ) {
					foreach ( $users as $user ) {
						$item = array(
							'ID' => $user->ID,
						);
						$background_process->push_to_queue( $item );
					}
				}
			}

			// Fire off bg processes.
			$background_process->save()->dispatch();
		}
	}
}
