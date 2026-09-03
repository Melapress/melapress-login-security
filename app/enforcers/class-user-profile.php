<?php
/**
 * Melapress Login Security New User Register
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

use MLS\Helpers\OptionsHelper;

// If check class exists OR not.
if ( ! class_exists( '\MLS\User_Profile' ) ) {
	/**
	 * Declare User_Profile Class
	 *
	 * @since 2.0.0
	 */
	class User_Profile {

		/**
		 * Init hooks.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			global $pagenow;
			if ( 'profile.php' !== $pagenow || 'user-edit.php' !== $pagenow ) {
				\add_action( 'show_user_profile', array( __CLASS__, 'add_area_heading' ), 5 );
				\add_action( 'edit_user_profile', array( __CLASS__, 'add_area_heading' ), 5 );
				\add_action( 'show_user_profile', array( __CLASS__, 'reset_user_password' ), 20 );
				\add_action( 'edit_user_profile', array( __CLASS__, 'reset_user_password' ), 20 );
				\add_action( 'edit_user_profile', array( __CLASS__, 'lock_unlock_user_button' ), 25 );
				\add_action( 'personal_options_update', array( __CLASS__, 'save_profile_fields' ) );
				\add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile_fields' ) );
			}
			\add_action( 'admin_init', array( __CLASS__, 'handle_profile_lock_unlock' ) );

			/*
			 * The gate, and the redirect that used to be mistaken for one.
			 *
			 * `wp_login` fires *after* wp_signon() has already called
			 * wp_set_auth_cookie(), so anything enforced there is advisory: the
			 * session exists before the check runs, and a client that ignores the
			 * Location header simply keeps it. Refusing on `authenticate` means no
			 * cookie is ever issued.
			 *
			 * The wp_login handler stays for the flows that legitimately redirect
			 * an already-authenticated user — new-user and reset-on-login setup —
			 * but it is no longer what stops a flagged account.
			 */
			\add_filter( 'authenticate', array( __CLASS__, 'refuse_until_password_reset' ), 60, 3 );
			\add_action( 'wp_login', array( __CLASS__, 'ppm_reset_pw_on_login' ), 1, 2 );

			// Consume the requirement once the password has actually been changed.
			\add_action( 'after_password_reset', array( __CLASS__, 'clear_password_reset_requirement' ), 10, 1 );
		}

		/**
		 * Refuse authentication while a password reset is outstanding.
		 *
		 * With "reset password on unblock" enabled, unlocking an account writes a
		 * reset key to MLS_USER_RESET_PW_ON_LOGIN_META_KEY and the user is meant
		 * to set a new password before regaining access. That was enforced by a
		 * 302 issued from `wp_login` — by which point wp_signon() has already set
		 * the authentication cookie. Anything that did not follow the redirect
		 * kept a fully valid session on the old password, indefinitely, and the
		 * flag was never consumed so it survived every subsequent login.
		 *
		 * Refusing here means no cookie is issued at all, which covers wp-login,
		 * XML-RPC and anything else routed through wp_authenticate() in one place.
		 * Application Passwords do not pass through this filter; Api_Login_Guard
		 * consults this same callback for that channel.
		 *
		 * @param \WP_User|\WP_Error|null $user     - Result so far.
		 * @param string                  $username - Submitted login name.
		 * @param string                  $password - Submitted password.
		 *
		 * @return \WP_User|\WP_Error|null
		 *
		 * @since 2.4.0
		 */
		public static function refuse_until_password_reset( $user, $username = '', $password = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			if ( ! $user instanceof \WP_User ) {
				// Already refused by something earlier, or no account resolved.
				return $user;
			}

			if ( \MLS_Core::is_user_exempted( $user->ID ) ) {
				return $user;
			}

			$pending = \get_user_meta( $user->ID, MLS_USER_RESET_PW_ON_LOGIN_META_KEY, true );

			if ( empty( $pending ) ) {
				return $user;
			}

			// A temporary-login account is governed by its own lifecycle.
			if ( \get_user_meta( $user->ID, 'mls_temp_user', true ) ) {
				return $user;
			}

			$reset_url = self::password_reset_url( $user, $pending );

			/*
			 * Refuse by returning, never by redirecting.
			 *
			 * An earlier revision sent a 302 to the reset form and called exit()
			 * from here. `authenticate` is a filter, and wp_authenticate() is
			 * called from plenty of places that are not the login page, so that
			 * terminated any request which happened to carry a login-shaped POST.
			 * The error below reaches wp-login.php with the same link in it, and
			 * costs nothing to a caller that is not a browser.
			 */
			$message = \esc_html__( 'You must set a new password before signing in.', 'melapress-login-security' );

			if ( $reset_url ) {
				$message .= ' <a href="' . \esc_url( $reset_url ) . '">' . \esc_html__( 'Set a new password', 'melapress-login-security' ) . '</a>';
			}

			return new \WP_Error( 'mls_password_reset_required', $message );
		}

		/**
		 * A usable reset URL for an account carrying a pending requirement.
		 *
		 * The stored key can have expired — core keys last a day by default. If it
		 * no longer verifies a fresh one is issued and stored, otherwise refusing
		 * authentication would leave the account with no way back in at all.
		 *
		 * @param \WP_User $user       - The account.
		 * @param string   $stored_key - Key currently on the account.
		 *
		 * @return string Empty string when no key could be issued.
		 *
		 * @since 2.4.0
		 */
		private static function password_reset_url( $user, $stored_key ): string {
			$key = (string) $stored_key;

			if ( \is_wp_error( \check_password_reset_key( $key, $user->user_login ) ) ) {
				$key = (string) self::generate_new_reset_key( $user->ID );
			}

			if ( '' === $key ) {
				return '';
			}

			return \add_query_arg(
				array(
					'action' => 'rp',
					'key'    => $key,
					'login'  => rawurlencode( $user->user_login ),
				),
				\network_site_url( 'wp-login.php' )
			);
		}

		/**
		 * Drop the reset requirement once the password has been changed.
		 *
		 * Nothing cleared this before, so an account stayed flagged for good.
		 *
		 * @param \WP_User $user - The account whose password was reset.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function clear_password_reset_requirement( $user ) {
			if ( ! $user instanceof \WP_User ) {
				return;
			}

			\delete_user_meta( $user->ID, MLS_USER_RESET_PW_ON_LOGIN_META_KEY );
		}

		/**
		 * Add heading to profiles.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function add_area_heading() {
			if ( \current_user_can( 'manage_options' ) ) { ?>
			<br>
			<h2><?php \esc_html_e( 'Melapress Login Security user profile settings', 'melapress-login-security' ); ?></h2>
				<?php
			}
		}

		/**
		 * Handle reset of individual password.
		 *
		 * @param WP_User $user - user to reset.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function reset_user_password( $user ) {
			// Get current user, we going to need this regardless.
			$current_user = \wp_get_current_user();

			// Bail if we still dont have an object.
			if ( ! is_a( $user, '\WP_User' ) || ! is_a( $current_user, '\WP_User' ) ) {
				return;
			}

			$reset = \get_user_meta( $user->ID, MLS_USER_RESET_PW_ON_LOGIN_META_KEY, true );

			// If the profile was recently updated, one of those updates could be a new password,
			// so if the user is set to reset on next login, lets generate a fresh reset key
			// to avoid "invalid reset link" when logging in next time.
			if ( isset( $_REQUEST['updated'] ) && ! empty( $reset ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				self::generate_new_reset_key( $user->ID );
			}

			if ( current_user_can( 'manage_options' ) ) {
				?>
				<table class="form-table" role="presentation">
					<tbody><tr id="password" class="user-pass1-wrap">
						<th><label for="reset_password"><?php esc_html_e( 'Change password on next login', 'melapress-login-security' ); ?></label></th>
						<td>
							<label for="reset_password_on_next_login">
								<input name="reset_password_on_next_login" type="checkbox" id="reset_password_on_next_login" <?php \checked( ! empty( $reset ) ); ?>>
								<?php \wp_nonce_field( 'pmls_reset_on_next_login', 'mls_user_profile_nonce' ); ?>
							</label>
							<br>
						</td>
						</tr>
					</tbody>
				</table>
				<?php
			}
		}

		/**
		 * Render lock/unlock button on the user profile page.
		 *
		 * @param \WP_User $user The user being edited.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function lock_unlock_user_button( $user ) {
			if ( ! \current_user_can( 'edit_user', $user->ID ) ) {
				return;
			}

			$is_locked = (bool) OptionsHelper::is_user_locked_by_any_mechanism( $user->ID, 'any' );
			$action    = $is_locked ? 'unlock' : 'lock';
			$label     = $is_locked ? \esc_html__( 'Unlock user', 'melapress-login-security' ) : \esc_html__( 'Lock user', 'melapress-login-security' );
			$btn_class = $is_locked ? 'button' : 'button';

			$url = \wp_nonce_url(
				\add_query_arg(
					array(
						'mls_profile_action' => 'mls_' . $action . '_user',
					),
					\admin_url( 'user-edit.php?user_id=' . $user->ID )
				),
				'mls_' . $action . '_user_' . $user->ID
			);

			?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th><?php \esc_html_e( 'Account lock status', 'melapress-login-security' ); ?></th>
						<td>
							<?php if ( $is_locked ) : ?>
								<span style="color:#d63638;font-weight:600;"><?php \esc_html_e( 'Locked', 'melapress-login-security' ); ?></span>
								<?php
								$reason_label = \MLS\Admin\User_Helper::get_user_locked_reason_label( $user->ID );
								if ( $reason_label ) {
									echo ' &mdash; <span class="description">' . \esc_html( $reason_label ) . '</span>';
								}
								?>
							<?php else : ?>
								<span style="color:#00a32a;font-weight:600;"><?php \esc_html_e( 'Active', 'melapress-login-security' ); ?></span>
							<?php endif; ?>
							<br><br>
							<a href="<?php echo \esc_url( $url ); ?>" class="<?php echo \esc_attr( $btn_class ); ?>">
								<?php echo \esc_html( $label ); ?>
							</a>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Handle lock/unlock action from the user profile page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function handle_profile_lock_unlock() {
			if ( ! isset( $_GET['mls_profile_action'], $_GET['user_id'], $_GET['_wpnonce'] ) ) {
				return;
			}

			$action  = \sanitize_text_field( \wp_unslash( $_GET['mls_profile_action'] ) );
			$user_id = (int) $_GET['user_id'];

			if ( ! \current_user_can( 'edit_user', $user_id ) ) {
				return;
			}

			$nonce = \sanitize_text_field( \wp_unslash( $_GET['_wpnonce'] ) );

			if ( 'mls_lock_user' === $action && \wp_verify_nonce( $nonce, 'mls_lock_user_' . $user_id ) ) {
				$inactive_users = OptionsHelper::get_inactive_users();

				\MLS\Admin\User_Helper::lock_user( $user_id );
				\MLS\Admin\User_Helper::set_user_locked_reason(
					$user_id,
					array(
						'reason'  => 'manual',
						'user_id' => \get_current_user_id(),
					)
				);

				if ( ! in_array( $user_id, $inactive_users, true ) ) {
					$inactive_users[] = $user_id;
					OptionsHelper::set_inactive_users_array( $inactive_users );
				}

				\wp_safe_redirect( \add_query_arg( array( 'user_id' => $user_id, 'mls_user_locked' => '1' ), \admin_url( 'user-edit.php' ) ) );
				exit;
			} elseif ( 'mls_unlock_user' === $action && \wp_verify_nonce( $nonce, 'mls_unlock_user_' . $user_id ) ) {
				OptionsHelper::fully_unlock_user( $user_id );

				\wp_safe_redirect( \add_query_arg( array( 'user_id' => $user_id, 'mls_user_unlocked' => '1' ), \admin_url( 'user-edit.php' ) ) );
				exit;
			}
		}

		/**
		 * Handles saving of user profile fields.
		 *
		 * @param  int $user_id - user ID.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function save_profile_fields( $user_id ) {
			if ( ! isset( $_POST['reset_password_on_next_login'] ) && ! isset( $_POST['mls_user_profile_nonce'] ) ) {
				return;
			}

			if ( ! \current_user_can( 'manage_options' ) || ! \current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['mls_user_profile_nonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['mls_user_profile_nonce'] ) ), 'pmls_reset_on_next_login' ) ) {
				return;
			}

			if ( isset( $_POST['reset_password_on_next_login'] ) ) {
				$reset = \get_user_meta( $user_id, MLS_USER_RESET_PW_ON_LOGIN_META_KEY, true );
				if ( empty( $reset ) ) {
					/**
					 * Fire of action for others to observe.
					 */
					\do_action( 'mls_user_required_to_reset_password_on_next_login', $user_id );
					self::generate_new_reset_key( $user_id );
				}
			} else {
				/**
				 * Fire of action for others to observe.
				 */
					\do_action( 'mls_user_no_longer_required_to_reset_password_on_next_login', $user_id );
				// Remove any reset on login keys if admin has disabled it for this user.
				\delete_user_meta( $user_id, MLS_USER_RESET_PW_ON_LOGIN_META_KEY );
			}
		}

		/**
		 * Generates a new password reset key and also saves it to our own meta field.
		 *
		 * @param int $user_id - Current ID.
		 *
		 * @return object
		 *
		 * @since 2.0.0
		 */
		public static function generate_new_reset_key( $user_id ) {
			$userdata = \get_user_by( 'id', $user_id );
			$key      = \get_password_reset_key( $userdata );
			if ( ! \is_wp_error( $key ) ) {
				\update_user_meta( $user_id, MLS_USER_RESET_PW_ON_LOGIN_META_KEY, $key );

				return $key;
			}
		}

		/**
		 * Send user for further processing in central function.
		 *
		 * @param  string  $user_login - User logging in.
		 * @param  WP_User $user - User object.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function ppm_reset_pw_on_login( $user_login, $user ) {
			self::ppm_handle_login_based_resets( $user_login, $user, 'reset-on-login' );
		}

		/**
		 * Redirect user to reset page if needed.
		 *
		 * @param  string  $user_login - User logging in.
		 * @param  WP_User $user - User object.
		 * @param  string  $reset_type - Where did they come from.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function ppm_handle_login_based_resets( $user_login, $user, $reset_type = 'reset-on-login' ) {
			// Get user reset key.
			$reset = new \MLS\Reset_Passwords();

			$verify_reset_key = $reset->ppm_get_user_reset_key( $user, $reset_type );

			if ( ! $verify_reset_key || \get_user_meta( $user->ID, 'mls_temp_user', true ) ) {
				return;
			}

			if ( ( $verify_reset_key && ! \is_wp_error( $verify_reset_key ) && 'new-user' === $reset_type ) || ( isset( $verify_reset_key->errors['invalid_key'] ) && ! empty( $verify_reset_key->errors['invalid_key'] ) && 'reset-on-login' === $reset_type ) || ( $verify_reset_key && ! $verify_reset_key->errors && 'reset-on-login' === $reset_type ) ) {
				$reset_key                    = self::generate_new_reset_key( $user->ID );
				$verify_reset_key             = \check_password_reset_key( $reset_key, $user_login );
				$verify_reset_key->reset_key  = $reset_key;
				$verify_reset_key->user_login = $user_login;

				\MLS_Core::handle_user_redirection( $verify_reset_key );

			} elseif ( $verify_reset_key && ! $verify_reset_key->errors && 'reset-on-login' !== $reset_type ) {
				// Handle users directly registered using Restrict Content.
				if ( isset( $_REQUEST['action'] ) && 'rc_process_registration_form' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					\MLS_Core::handle_user_redirection( $verify_reset_key, true );
				} else {
					\MLS_Core::handle_user_redirection( $verify_reset_key );
				}
			} elseif ( isset( $verify_reset_key->errors['expired_key'] ) && ! empty( $verify_reset_key->errors['expired_key'] ) && 'new-user' === $reset_type ) {
				// If a user has reached this point, they have a valid key in the correct place,
				// but they have taken too long to reset, so we reset the key and send them back to login.

				// Create new reset key for this user.
				$key = \get_password_reset_key( $user );

				if ( ! \is_wp_error( $key ) ) {
					// Update user with new key information.
					$update = \update_user_meta( $user->ID, MLS_NEW_USER_META_KEY, $key );
				}
				\MLS_Core::handle_user_redirection( $verify_reset_key );
			}
		}

		/**
		 * Sends reset email to user. Message depends on $by value
		 *
		 * @param int    $user_id        User ID.
		 * @param string $by             Can be 'system' or 'admin'. Depending on its value different messages are sent.
		 * @param bool   $return_on_fail Flag to determine if we return or die on mail failure.
		 *
		 * @return void|string - Result.
		 *
		 * @since 2.0.0
		 */
		public function send_reset_next_login_email( $user_id, $by, $return_on_fail = false ) {

			$user_data = \get_userdata( $user_id );

			// Redefining user_login ensures we return the right case in the email.
			$user_login    = $user_data->user_login;
			$user_email    = $user_data->user_email;
			$key           = \get_user_meta( $user_id, MLS_USER_RESET_PW_ON_LOGIN_META_KEY, true );
			$login_page    = OptionsHelper::get_password_reset_page();
			$email_content = false;

			if ( 'admin' === $by ) {
				$content       = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_delayed_reset_email_body' );
				$email_content = \MLS\EmailAndMessageStrings::replace_email_strings( $content, $user_id, array( 'reset_url' => \esc_url_raw( \network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) ) );
			}

			$title = \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_delayed_reset_email_subject' ), $user_id );

			$mls = \melapress_login_security();

			$from_email = $mls->options->mls_setting->from_email ? $mls->options->mls_setting->from_email : 'mls@' . str_ireplace( 'www.', '', \wp_parse_url( \network_site_url(), PHP_URL_HOST ) );
			$from_email = \sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;

			if ( $email_content && ! \MLS\Emailer::send_email( $user_email, \wp_specialchars_decode( $title ), $email_content, $headers ) ) {
				$fail_message = \__( 'The email could not be sent.', 'melapress-login-security' ) . "<br />\n" . \__( 'Possible reason: your host may have disabled the mail() function.', 'melapress-login-security' );
				if ( $return_on_fail ) {
					return $fail_message;
				} else {
					\wp_die( \wp_kses_post( $fail_message ) );
				}
			}
		}
	}
}
