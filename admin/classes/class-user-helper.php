<?php
/**
 * Helper for user related admin UI actions (quick actions + bulk actions).
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

/**
 * Class User_Helper
 *
 * Static helper to add quick row actions and bulk actions to the Users admin.
 *
 * @since 2.0.0
 */
if ( ! class_exists( '\MLS\Admin\User_Helper' ) ) {
	/**
	 * User Helper Class.
	 *
	 * @since 2.0.0
	 */
	class User_Helper {

		const USER_LOCKED_REASON = 'mls_locked_reason';
		const USER_LOCKED_META   = 'mls_locked';

		/**
		 * Valid reasons for locking a user.
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		private static $user_locked_reasons = array( 'manual' );

		/**
		 * Initialise hooks for this helper.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			// $inactive_feature_enabled = \MLS\Helpers\OptionsHelper::should_inactive_users_feature_be_active( true );
			// if ( isset( $master_policy->failed_login_policies_enabled ) && \MLS\Helpers\OptionsHelper::string_to_bool( $master_policy->failed_login_policies_enabled ) ) {
			// $inactive_feature_enabled = true;
			// }

			// if ( $inactive_feature_enabled ) {
				\add_filter( 'user_row_actions', array( __CLASS__, 'add_quick_user_action' ), 10, 2 );
				\add_filter( 'bulk_actions-users', array( __CLASS__, 'register_bulk_actions' ) );
				\add_filter( 'handle_bulk_actions-users', array( __CLASS__, 'handle_bulk_actions' ), 10, 3 );
				\add_action( 'admin_notices', array( __CLASS__, 'display_admin_notices' ) );

				\add_action( 'mls_user_locked_due_to_inactivity_unlocked', array( __CLASS__, 'unlock_user' ) );

				\add_filter( 'authenticate', array( __CLASS__, 'check_user_is_locked' ), 999999, 3 );
			// }
		}

		/**
		 * Check if correct user name is used.
		 *
		 * @param \WP_User $user - User to check.
		 * @param string   $username - Username.
		 * @param string   $password - Password.
		 *
		 * @return \WP_User|\WP_Error
		 *
		 * @since 2.0.0
		 */
		public static function check_user_is_locked( $user, $username, $password ) {
			if ( ! isset( $user->roles ) ) {
				return $user;
			}

			if ( \MLS_Core::is_user_exempted( $user->ID ) ) {
				return $user;
			}

			if ( self::is_user_locked( $user->ID ) ) {
				$error_content = \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_locked_failure_message' ), $user->ID );
				$error_message = new \WP_Error( 'ppm_login_error', $error_content );

				/**
				 * Fire of action for others to observe.
				 */
				do_action( 'mls_user_login_blocked_due_to_locked_status', $user->ID );

				return $error_message;
			}

			return $user;
		}

		/**
		 * Add quick Lock/Unlock action to the user row actions.
		 *
		 * @param array    $actions Current actions.
		 * @param \WP_User $user    User object.
		 *
		 * @return array Modified actions.
		 *
		 * @since 2.0.0
		 */
		public static function add_quick_user_action( $actions, $user ) {
			if ( ! isset( $user->ID ) ) {
				return $actions;
			}

			if ( ! \current_user_can( 'edit_user', $user->ID ) ) {
				return $actions;
			}

			$is_inactive = self::is_user_locked( $user->ID );
			$action      = $is_inactive ? 'unlock' : 'lock';
			$label       = $is_inactive ? \esc_html__( 'Unlock', 'melapress-login-security' ) : \esc_html__( 'Lock', 'melapress-login-security' );

			$url = \wp_nonce_url(
				\add_query_arg(
					array(
						'action'  => 'mls_' . $action . '_user',
						'user_id' => $user->ID,
					),
					\admin_url( 'users.php' )
				),
				'mls_' . $action . '_user_' . $user->ID
			);

			$actions[ 'mls_' . $action ] = '<a href="' . \esc_url( $url ) . '">' . \esc_html( $label ) . '</a>';

			return $actions;
		}

		/**
		 * Register bulk actions for users list.
		 *
		 * @param array $bulk_actions Existing bulk actions.
		 *
		 * @return array Modified bulk actions.
		 *
		 * @since 2.0.0
		 */
		public static function register_bulk_actions( $bulk_actions ) {
			$bulk_actions['mls_lock_users']   = \esc_html__( 'Lock Users', 'melapress-login-security' );
			$bulk_actions['mls_unlock_users'] = \esc_html__( 'Unlock Users', 'melapress-login-security' );
			return $bulk_actions;
		}

		/**
		 * Handle bulk actions submitted from the users list screen.
		 *
		 * @param string $redirect_to URL to redirect to after processing.
		 * @param string $doaction    The action being taken.
		 * @param array  $user_ids    Array of user IDs selected.
		 *
		 * @return string Redirect URL.
		 *
		 * @since 2.0.0
		 */
		public static function handle_bulk_actions( $redirect_to, $doaction, $user_ids ) {
			// Only handle our custom actions.
			if ( ! in_array( $doaction, array( 'mls_lock_users', 'mls_unlock_users' ), true ) ) {
				return $redirect_to;
			}

			if ( empty( $user_ids ) || ! is_array( $user_ids ) ) {
				return $redirect_to;
			}

			$processed = 0;

			if ( 'mls_lock_users' === $doaction ) {
				$inactive_users = OptionsHelper::get_inactive_users();

				foreach ( $user_ids as $user_id ) {
					$user_id = (int) $user_id;

					if ( ! \current_user_can( 'edit_user', $user_id ) ) {
						continue;
					}

					// Set user as inactive (adds usermeta flag).
					self::lock_user( $user_id );
					self::set_user_locked_reason(
						$user_id,
						array(
							'reason'  => 'manual',
							'user_id' => get_current_user_id(),
						)
					);

					// Add to global inactive users array for admin UI display.
					if ( ! in_array( $user_id, $inactive_users, true ) ) {
						$inactive_users[] = $user_id;
					}

					++$processed;
				}

				// Update the site option with all locked users.
				OptionsHelper::set_inactive_users_array( $inactive_users );

				$redirect_to = add_query_arg( 'mls_locked', $processed, $redirect_to );
			}

			if ( 'mls_unlock_users' === $doaction ) {
				$failed_logins = class_exists( '\MLS\Failed_Logins' ) ? new \MLS\Failed_Logins() : null;

				$inactive_users = OptionsHelper::get_inactive_users();

				foreach ( $user_ids as $user_id ) {
					$user_id = (int) $user_id;

					if ( ! \current_user_can( 'edit_user', $user_id ) ) {
						continue;
					}

					// Clear inactive data and remove from site option array.
					self::unlock_user( $user_id );
					self::remove_user_locked_reason( $user_id );

					// Clear failed login data if available.
					if ( $failed_logins && method_exists( $failed_logins, 'clear_failed_login_data' ) ) {
						$failed_logins->clear_failed_login_data( $user_id, false );
					}

					// Add to global inactive users array for admin UI display.
					if ( in_array( $user_id, $inactive_users, true ) ) {
						$inactive_users = array_diff( $inactive_users, array( $user_id ) );
					}

					++$processed;
				}

				// Update the site option with all locked users.
				OptionsHelper::set_inactive_users_array( $inactive_users );

				$redirect_to = add_query_arg( 'mls_unlocked', $processed, $redirect_to );
			}

			return $redirect_to;
		}

		/**
		 * Lock a user.
		 *
		 * @param int $user_id The user ID.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function lock_user( $user_id ) {
			\update_user_meta( $user_id, self::USER_LOCKED_META, true );
		}

		/**
		 * Unlock a user.
		 *
		 * @param int $user_id The user ID.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function unlock_user( $user_id ) {
			\delete_user_meta( $user_id, self::USER_LOCKED_META );
		}

		/**
		 * Check if a user is locked.
		 *
		 * @param int $user_id The user ID.
		 *
		 * @return bool True if locked, false otherwise.
		 *
		 * @since 2.0.0
		 */
		public static function is_user_locked( $user_id ) {
			return (bool) \get_user_meta( $user_id, self::USER_LOCKED_META, true );
		}

		/**
		 * Get the valid reasons for locking a user.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function get_user_locked_reasons() {
			self::$user_locked_reasons = array(
				'manual' => __( 'locked', 'melapress-login-security' ),
			);

			return self::$user_locked_reasons;
		}

		/**
		 * Get the label for the reason a user was locked.
		 *
		 * @param int $user The user ID.
		 *
		 * @return string The label for the reason.
		 *
		 * @since 2.0.0
		 */
		public static function get_user_locked_reason_label( $user ) {
			$reasons = self::get_user_locked_reasons();

			$reason_data = (array) self::get_user_locked_reason( (int) $user );
			$reason_data = reset( $reason_data );
			if ( ! is_array( $reason_data ) || ! isset( $reason_data['reason'] ) ) {
				return __( 'inactivity', 'melapress-login-security' );
			}

			if ( isset( $reason_data['reason'] ) && isset( $reasons[ $reason_data['reason'] ] ) ) {

				if ( isset( $reason_data['user_id'] ) ) {
					$locked_by_user = get_user_by( 'id', (int) $reason_data['user_id'] );
					if ( $locked_by_user ) {
						return sprintf(
							/* translators: 1: Reason, 2: User who locked */
							__( '%1$s (by %2$s)', 'melapress-login-security' ),
							$reasons[ $reason_data['reason'] ],
							$locked_by_user->display_name
						);
					}
				}

				return $reasons[ $reason_data['reason'] ];
			}

			return __( 'inactivity', 'melapress-login-security' );
		}

		/**
		 * Set the reason for locking a user.
		 *
		 * @param int   $user_id The user ID.
		 * @param array $reason  The reason for locking the user.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function set_user_locked_reason( $user_id, $reason ) {
			\update_user_meta( $user_id, self::USER_LOCKED_REASON, $reason );
		}

		/**
		 * Get the reason for locking a user.
		 *
		 * @param int $user_id The user ID.
		 *
		 * @return mixed The reason for locking the user.
		 *
		 * @since 2.0.0
		 */
		public static function get_user_locked_reason( $user_id ) {
			return \get_user_meta( $user_id, self::USER_LOCKED_REASON );
		}

		/**
		 * Remove the reason for locking a user.
		 *
		 * @param int $user_id The user ID.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function remove_user_locked_reason( $user_id ) {
			\delete_user_meta( $user_id, self::USER_LOCKED_REASON );
		}

		/**
		 * Display admin notices for lock/unlock actions.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function display_admin_notices() {
			// Check if we're on the users page.
			$screen = \get_current_screen();
			if ( ! $screen || 'users' !== $screen->id ) {
				return;
			}

			// Handle quick action redirects.
			if ( isset( $_GET['action'], $_GET['user_id'], $_GET['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$action  = \sanitize_text_field( \wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$user_id = (int) $_GET['user_id'];
				$nonce   = \sanitize_text_field( \wp_unslash( $_GET['_wpnonce'] ) );

				if ( 'mls_lock_user' === $action && \wp_verify_nonce( $nonce, 'mls_lock_user_' . $user_id ) ) {
					if ( \current_user_can( 'edit_user', $user_id ) ) {
						$inactive_users = OptionsHelper::get_inactive_users();

						self::lock_user( $user_id );
						self::set_user_locked_reason(
							$user_id,
							array(
								'reason'  => 'manual',
								'user_id' => get_current_user_id(),
							)
						);

						if ( ! in_array( $user_id, $inactive_users, true ) ) {
							$inactive_users[] = $user_id;
							OptionsHelper::set_inactive_users_array( $inactive_users );
						}

						echo '<div class="notice notice-success is-dismissible"><p>';
						esc_html_e( 'User has been locked.', 'melapress-login-security' );
						echo '</p></div>';
					}
				} elseif ( 'mls_unlock_user' === $action && \wp_verify_nonce( $nonce, 'mls_unlock_user_' . $user_id ) ) {
					if ( \current_user_can( 'edit_user', $user_id ) ) {
						self::unlock_user( $user_id );

						$failed_logins = class_exists( '\MLS\Failed_Logins' ) ? new \MLS\Failed_Logins() : null;
						if ( $failed_logins && method_exists( $failed_logins, 'clear_failed_login_data' ) ) {
							$failed_logins->clear_failed_login_data( $user_id, false );
						}

						$inactive_users = OptionsHelper::get_inactive_users();
						if ( in_array( $user_id, $inactive_users, true ) ) {
							$inactive_users = array_diff( $inactive_users, array( $user_id ) );
							OptionsHelper::set_inactive_users_array( $inactive_users );
						}

						echo '<div class="notice notice-success is-dismissible"><p>';
						\esc_html_e( 'User has been unlocked.', 'melapress-login-security' );
						echo '</p></div>';
					}
				}
			}

			// Display bulk action results.
			if ( isset( $_GET['mls_locked'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$count = (int) $_GET['mls_locked'];
				echo '<div class="notice notice-success is-dismissible"><p>';
				/* translators: %d: Number of users locked */
				echo \esc_html( sprintf( \_n( '%d user has been locked.', '%d users have been locked.', $count, 'melapress-login-security' ), $count ) );
				echo '</p></div>';
			}

			if ( isset( $_GET['mls_unlocked'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$count = (int) $_GET['mls_unlocked'];
				echo '<div class="notice notice-success is-dismissible"><p>';
				/* translators: %d: Number of users unlocked */
				echo \esc_html( sprintf( \_n( '%d user has been unlocked.', '%d users have been unlocked.', $count, 'melapress-login-security' ), $count ) );
				echo '</p></div>';
			}
		}
	}
}
