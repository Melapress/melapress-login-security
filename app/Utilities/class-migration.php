<?php
/**
 * Responsible for plugin version migrations.
 *
 * @package    MelapressLoginSecurity
 * @subpackage Utilities
 * @copyright  2024 Melapress
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 *
 * @since      2.4.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if class exists.
 */
if ( ! class_exists( '\MLS\Migration' ) ) {

	/**
	 * Put all migration methods here.
	 *
	 * @package MLS
	 * @since 2.4.0
	 */
	class Migration extends Abstract_Migration {

		/**
		 * Where policies for deleted roles are kept after being removed.
		 *
		 * Nothing reads this; it exists so that a policy taken away by the
		 * cleanup can be restored by hand if the role turns out to have been
		 * registered by a plugin that was only switched off.
		 *
		 * @var string
		 *
		 * @since 2.4.0
		 */
		public const ORPHANED_ROLE_POLICY_ARCHIVE = 'mls_orphaned_role_policies';

		/**
		 * The name of the option from which we should extract version.
		 * Note: version is expected in version format - 1.0.0; 1; 1.0; 1.0.0.0
		 * Note: only numbers will be processed.
		 *
		 * @var string
		 *
		 * @since 2.4.0
		 */
		protected static $version_option_name = 'mls_plugin_version';

		/**
		 * The constant name where the plugin version is stored.
		 * Note: version is expected in version format - 1.0.0; 1; 1.0; 1.0.0.0
		 * Note: only numbers will be processed.
		 *
		 * @var string
		 *
		 * @since 2.4.0
		 */
		protected static $const_name_of_plugin_version = 'MLS_VERSION';

		/**
		 * Migration for version up to 2.4.0
		 *
		 * Initial migration method. Stores the version for the first time
		 * when the migration framework is introduced.
		 *
		 *
		 * Hashes security-question answers that were stored in clear text.
		 *
		 * The answers gate account unlock and password reset, so they are
		 * credentials; kept as plain text they are exposed to every database
		 * backup, every user-export tool and any SQL injection elsewhere on the
		 * site. Storage moved to `wp_hash_password()` in 2.4.0 and this converts
		 * what is already on disk.
		 *
		 * Users are processed in batches so a site with a large number of them
		 * does not hold a request open. Anything this misses is converted
		 * lazily by `Security_Prompt::verify_answer()` the next time the user
		 * answers correctly, so an interrupted run cannot leave an account
		 * unable to authenticate.
		 *
		 *
		 * Brings existing known-device histories under the per-user cap
		 * introduced in 2.4.0. See cap_known_device_history().
		 *
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		protected static function migrate_up_to_240() {
			\delete_transient( 'mls_config_file_hash' );

			self::enable_group_toggles_from_existing_policies();

			if ( class_exists( '\MLS\Security_Prompt' ) ) {
				\MLS\Security_Prompt::schedule_answer_hashing();
			}

			self::encrypt_temporary_login_tokens();

			self::cap_known_device_history();

			self::retire_per_site_failed_login_counters();

			self::purge_orphaned_role_policies();

			self::preserve_password_reset_security_question();
		}

		/**
		 * Keep the password-reset security question switched on where it was
		 * already in force.
		 *
		 * The "Require security question to initiate a password reset" checkbox
		 * governed nothing: the gate consulted only whether security questions
		 * were enabled at all, so every site with the feature on has been asking
		 * for an answer before sending a reset link, whether or not the box was
		 * ticked. Now that the box is read, leaving it as stored would quietly
		 * drop that gate on upgrade — a security control disappearing without
		 * anybody choosing to remove it.
		 *
		 * So the box is ticked wherever the behaviour was already in effect. The
		 * setting then means what it says, and an administrator who does not want
		 * the gate can untick it deliberately.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function preserve_password_reset_security_question() {
			$options = array( MLS_PREFIX . '_options' );

			foreach ( self::stored_role_policy_slugs() as $slug ) {
				$options[] = MLS_PREFIX . '_' . $slug . '_options';
			}

			foreach ( $options as $option ) {
				$policy = \get_site_option( $option, false );

				if ( ! is_array( $policy ) ) {
					continue;
				}

				$questions_on = isset( $policy['enable_security_questions'] )
					&& \MLS\Helpers\OptionsHelper::string_to_bool( $policy['enable_security_questions'] );

				if ( ! $questions_on ) {
					continue;
				}

				$already_set = isset( $policy['require_sq_password_reset'] )
					&& \MLS\Helpers\OptionsHelper::string_to_bool( $policy['require_sq_password_reset'] );

				if ( $already_set ) {
					continue;
				}

				$policy['require_sq_password_reset'] = 'yes';

				\update_site_option( $option, $policy );
			}
		}

		/**
		 * Remove policy rows belonging to roles that no longer exist.
		 *
		 * A role's policy is stored as `mls_<slug>_options`, and nothing removes
		 * it when the role itself is deleted. The row then outlives the role
		 * indefinitely. It is inert while it sits there — resolution asks about
		 * the roles an account actually holds, and WordPress drops a role it no
		 * longer knows about — but if the same slug is ever registered again, by
		 * the same plugin or a different one, the new role silently adopts a
		 * policy somebody configured for something else.
		 *
		 * Two things make this more delicate than it looks:
		 *
		 * A role registered by a plugin that happens to be deactivated right now
		 * is indistinguishable from a deleted one. Deleting its policy would
		 * lose a working configuration the moment somebody toggled a plugin off,
		 * so every row removed here is copied into an archive option first and
		 * can be put back by hand.
		 *
		 * Roles are per-site: `wp_roles()` describes the current blog only. On a
		 * network, a role can exist on one site and not another, while these
		 * policies are network-wide. Deciding from a single site would delete a
		 * policy another site still needs, so the known-role set is the union
		 * across every site in the network.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function purge_orphaned_role_policies() {
			$known = self::known_role_slugs();

			// Nothing resolvable — a broken roles table, say. Do nothing rather
			// than treat every policy on the site as an orphan.
			if ( empty( $known ) ) {
				return;
			}

			$stored = self::stored_role_policy_slugs();

			if ( empty( $stored ) ) {
				return;
			}

			$orphans = array_diff( $stored, $known );

			if ( empty( $orphans ) ) {
				return;
			}

			$archive = \get_site_option( self::ORPHANED_ROLE_POLICY_ARCHIVE, array() );

			if ( ! is_array( $archive ) ) {
				$archive = array();
			}

			foreach ( $orphans as $slug ) {
				$option = MLS_PREFIX . '_' . $slug . '_options';
				$policy = \get_site_option( $option, false );

				if ( false !== $policy ) {
					$archive[ $slug ] = array(
						'policy'     => $policy,
						'removed_at' => time(),
					);
				}

				\delete_site_option( $option );
			}

			\update_site_option( self::ORPHANED_ROLE_POLICY_ARCHIVE, $archive );
		}

		/**
		 * Every role slug registered anywhere on this installation.
		 *
		 * @return array
		 *
		 * @since 2.4.0
		 */
		private static function known_role_slugs() {
			$known = self::role_slugs_on_this_site();

			if ( ! \is_multisite() ) {
				return $known;
			}

			$paged = 1;

			do {
				$site_ids = \get_sites(
					array(
						'fields'                 => 'ids',
						'number'                 => 200,
						'paged'                  => $paged,
						'update_site_meta_cache' => false,
					)
				);

				foreach ( $site_ids as $site_id ) {
					\switch_to_blog( (int) $site_id );
					$known = array_merge( $known, self::role_slugs_on_this_site() );
					\restore_current_blog();
				}

				++$paged;
			} while ( count( $site_ids ) === 200 );

			return array_values( array_unique( $known ) );
		}

		/**
		 * @return array Role slugs registered on the current site.
		 *
		 * @since 2.4.0
		 */
		private static function role_slugs_on_this_site() {
			if ( ! function_exists( 'wp_roles' ) ) {
				return array();
			}

			$roles = \wp_roles();

			if ( ! is_object( $roles ) ) {
				return array();
			}

			// switch_to_blog() does not always rebuild this by itself.
			// reinit() has been deprecated since WordPress 4.7.
			if ( method_exists( $roles, 'for_site' ) ) {
				$roles->for_site( \get_current_blog_id() );
			} elseif ( method_exists( $roles, 'reinit' ) ) {
				$roles->reinit();
			}

			if ( ! method_exists( $roles, 'get_names' ) ) {
				return array();
			}

			return array_keys( (array) $roles->get_names() );
		}

		/**
		 * The role slugs that currently have a policy row.
		 *
		 * @return array
		 *
		 * @since 2.4.0
		 */
		private static function stored_role_policy_slugs() {
			global $wpdb;

			$prefix  = MLS_PREFIX . '_';
			$suffix  = '_options';
			$pattern = $wpdb->esc_like( $prefix ) . '%' . $wpdb->esc_like( $suffix );

			if ( \is_multisite() ) {
				$names = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT meta_key FROM {$wpdb->sitemeta} WHERE site_id = %d AND meta_key LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						\get_current_network_id(),
						$pattern
					)
				);
			} else {
				$names = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$pattern
					)
				);
			}

			$slugs = array();

			foreach ( (array) $names as $name ) {
				$slug = substr( $name, strlen( $prefix ), -strlen( $suffix ) );

				// `mls_options` is the site-wide policy, not a role's, and does
				// not match the pattern above — but a stray `mls__options` would,
				// and it belongs to no role either.
				if ( '' === $slug || false === $slug ) {
					continue;
				}

				$slugs[] = $slug;
			}

			return array_values( array_unique( $slugs ) );
		}

		/**
		 * Drop failed-login counters left behind in each site's options table.
		 *
		 * The failed-login counters moved from plain transients to site
		 * transients. On multisite a plain transient lives in the current blog's
		 * options table, so the configured allowance was spent again on every
		 * site in the network — three attempts on a three-site network meant nine
		 * guesses before anything locked. Site transients live in sitemeta, so the
		 * allowance is now what the administrator actually set.
		 *
		 * The old rows are orphaned by that change. They are counters, not locks:
		 * a lockout that is actually in force is recorded in the
		 * `mls_failed_login_locks` user meta, which is network-wide and untouched
		 * here, and enforcement reads that record as well as the counter. So
		 * nobody currently locked out is released by this — only partial tallies
		 * are discarded, which at worst gives someone mid-way through their
		 * allowance a fresh start.
		 *
		 * Deliberately not summed into the new counter: adding per-site tallies
		 * together could lock an account that no single site had locked, which is
		 * a worse failure than forgetting a partial count.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function retire_per_site_failed_login_counters() {
			if ( ! \is_multisite() ) {
				self::delete_per_site_counter_rows();

				return;
			}

			$paged = 1;

			do {
				$site_ids = \get_sites(
					array(
						'fields'                 => 'ids',
						'number'                 => 200,
						'paged'                  => $paged,
						'update_site_meta_cache' => false,
					)
				);

				foreach ( $site_ids as $site_id ) {
					\switch_to_blog( (int) $site_id );
					self::delete_per_site_counter_rows();
					\restore_current_blog();
				}

				++$paged;
			} while ( count( $site_ids ) === 200 );
		}

		/**
		 * Remove the counter rows from the current site's options table.
		 *
		 * Matches only the counter prefixes, so the legacy per-user attempt list
		 * (`mls_user_{id}_failed_login_attempts`) and everything else the plugin
		 * stores are left alone.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function delete_per_site_counter_rows() {
			global $wpdb;

			foreach ( array( 'mls_login_', 'mls_login_src_', 'mls_acct_att_', 'mls_acct_cool_' ) as $prefix ) {
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE %s OR `option_name` LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$wpdb->esc_like( '_transient_' . $prefix ) . '%',
						$wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%'
					)
				);
			}

			\wp_cache_flush();
		}

		/**
		 * Enable policy group toggles based on existing active policies.
		 *
		 * In 2.4.0 group toggles were introduced. Without this, all groups
		 * appear disabled after upgrade even though individual policies are active.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function enable_group_toggles_from_existing_policies() {
			$options = self::get_option( MLS_PREFIX . '_options', array() );
			if ( is_array( $options ) && ! empty( $options ) ) {
				$options = self::maybe_set_group_toggles( $options );
				self::update_option( MLS_PREFIX . '_options', $options );
			}

			global $wp_roles;
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new \WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}

			foreach ( array_keys( $wp_roles->roles ) as $role ) {
				$role_options = self::get_option( MLS_PREFIX . '_' . $role . '_options', false );
				if ( is_array( $role_options ) && ! empty( $role_options ) ) {
					$role_options = self::maybe_set_group_toggles( $role_options );
					self::update_option( MLS_PREFIX . '_' . $role . '_options', $role_options );
				}
			}
		}

		/**
		 * Set group toggle keys to 'yes' when child policies are already active.
		 *
		 * @param array $options Policy options array.
		 *
		 * @return array
		 *
		 * @since 2.4.0
		 */
		private static function maybe_set_group_toggles( array $options ): array {
			$password_policies = array( 'activate_password_policies', 'activate_password_expiration_policies', 'activate_password_recycle_policies' );
			foreach ( $password_policies as $key ) {
				if ( isset( $options[ $key ] ) && 'yes' === $options[ $key ] ) {
					$options['enable_password_policies_group'] = 'yes';
					break;
				}
			}

			if ( isset( $options['enable_sessions_policies'] ) && 'yes' === $options['enable_sessions_policies'] ) {
				$options['enable_session_policies_group'] = 'yes';
			}

			if ( isset( $options['enable_device_policies'] ) && 'yes' === $options['enable_device_policies'] ) {
				$options['enable_device_policies_group'] = 'yes';
			}

			if ( isset( $options['failed_login_policies_enabled'] ) && 'yes' === $options['failed_login_policies_enabled'] ) {
				$options['enable_login_policies_group'] = 'yes';
			}
			if ( isset( $options['timed_logins'] ) && 'yes' === $options['timed_logins'] ) {
				$options['enable_login_policies_group'] = 'yes';
			}
			if ( isset( $options['restrict_login_ip'] ) && 'yes' === $options['restrict_login_ip'] ) {
				$options['enable_login_policies_group'] = 'yes';
			}

			return $options;
		}

		/**
		 * Encrypt temporary-login tokens that are stored in clear text.
		 *
		 * These tokens log a user straight in, so on disk they are equivalent
		 * to a password. Unlike the security answers this runs inline:
		 * symmetric encryption costs microseconds, and a site only ever has as
		 * many tokens as it has temporary logins.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function encrypt_temporary_login_tokens() {
			global $wpdb;

			if ( ! class_exists( '\MLS\TemporaryLogins\Temporary_Logins' ) ) {
				return;
			}

			$token_key = \MLS\TemporaryLogins\Temporary_Logins::TOKEN_META;
			$prefix    = \MLS\TemporaryLogins\Temporary_Logins::TOKEN_CIPHER_PREFIX;

			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT user_id, meta_value
					FROM {$wpdb->usermeta}
					WHERE meta_key = %s
						AND meta_value != ''
						AND meta_value NOT LIKE %s",
					$token_key,
					$wpdb->esc_like( $prefix ) . '%'
				)
			);

			foreach ( (array) $rows as $row ) {
				\MLS\TemporaryLogins\Temporary_Logins::store_token( (int) $row->user_id, $row->meta_value );
			}
		}

		/**
		 * Bring existing known-device histories under the per-user cap.
		 *
		 * Before 2.4.0 nothing bounded this list: every login without a matching
		 * device cookie appended a record and only the single matching entry was
		 * ever removed, and then only once it had outlived its recognition window.
		 * Long-lived accounts on cookie-clearing browsers accumulated hundreds of
		 * records in one user-meta row, which is read and unserialised on every
		 * login for that user.
		 *
		 * What this deliberately does *not* do:
		 *
		 * It does not prune records that have merely expired, even though the
		 * runtime now does. An upgrade should be the least surprising thing that
		 * can happen to a site, so this only removes what the new cap requires and
		 * nothing else; expired entries are cleared on the user's next login.
		 *
		 * It does not reorder anything. The manage-devices screen addresses a
		 * record by its index in this array, and the oldest records are removed
		 * while the survivors keep their relative order.
		 *
		 * Rows are walked in batches keyed on umeta_id rather than with an offset,
		 * so the cursor cannot skip or repeat a row when one is written mid-run,
		 * and a row is only written when its contents actually change.
		 *
		 * Free builds do not ship the device-detection module, hence the guard.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function cap_known_device_history() {
			global $wpdb;

			if ( ! class_exists( '\MLS\Device_Detection' ) ) {
				return;
			}

			$meta_key = \MLS\Device_Detection::KNOWN_DEVICES_META_KEY;
			$cap      = \MLS\Device_Detection::max_known_devices();

			if ( $cap < 1 ) {
				return;
			}

			$batch_size = 200;
			$last_id    = 0;

			do {
				$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT umeta_id, user_id, meta_value
						FROM {$wpdb->usermeta}
						WHERE meta_key = %s
							AND umeta_id > %d
						ORDER BY umeta_id ASC
						LIMIT %d",
						$meta_key,
						$last_id,
						$batch_size
					)
				);

				$rows = is_array( $rows ) ? $rows : array();

				foreach ( $rows as $row ) {
					$last_id = (int) $row->umeta_id;

					$devices = \maybe_unserialize( $row->meta_value );

					if ( ! is_array( $devices ) || count( $devices ) <= $cap ) {
						continue;
					}

					$trimmed = \MLS\Device_Detection::trim_known_devices( $devices, '', false );

					if ( count( $trimmed ) === count( $devices ) ) {
						continue;
					}

					// Through the meta API so the value is serialised correctly
					// and the user's meta cache is invalidated with it.
					\update_user_meta( (int) $row->user_id, $meta_key, $trimmed );
				}
			} while ( count( $rows ) === $batch_size );
		}

		/**
		 * Returns the plugin settings by a given option name.
		 *
		 * @param string $setting_name - The option name to retrieve.
		 *
		 * @return mixed
		 *
		 * @since 2.4.0
		 */
		protected static function get_settings( $setting_name ) {
			return self::get_option( sanitize_key( $setting_name ) );
		}

		/**
		 * Updates the plugin settings.
		 *
		 * @param string $setting_name - The option name to update.
		 * @param mixed  $settings     - The settings values.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		protected static function set_settings( $setting_name, $settings ) {
			self::update_option( sanitize_key( $setting_name ), $settings );
		}
	}
}
