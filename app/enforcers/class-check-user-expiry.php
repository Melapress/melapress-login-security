<?php
/**
 * Melapress Login Security Expire Class.
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

use MLS\Reset_Passwords;
use \MLS\Helpers\OptionsHelper;

if ( ! class_exists( '\MLS\Check_User_Expiry' ) ) {

	/**
	 * Declare Check_User_Expiry class.
	 *
	 * @since 2.0.0
	 */
	class Check_User_Expiry {
		/**
		 * Melapress Login Security Options.
		 *
		 * @var object $options Option.
		 *
		 * @since 2.0.0
		 */
		private $options;

		/**
		 * Desired priority.
		 *
		 * @var integer
		 *
		 * @since 2.0.0
		 */
		private $filter_priority = 10;

		/**
		 * Init hooks.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function hook() {
			$mls = melapress_login_security();

			$this->options = $mls->options;
			// Admin init.
			add_action( 'admin_init', array( $this, 'check_on_load' ) );
			add_action( 'wp_loaded', array( $this, 'check_on_load_front_end' ) );
			// Session expired AJAX.
			add_action( 'wp_ajax_ppm_ajax_session_expired', array( $this, 'ppm_ajax_session_expired' ) );

			$override_needed       = apply_filters( 'mls_override_has_expired_priority', false );
			$this->filter_priority = ( $override_needed && is_int( $override_needed ) ) ? $override_needed : $this->filter_priority;

			add_action( 'admin_notices', array( $this, 'password_about_to_expire_notice' ) );
			add_action( 'wp_ajax_dismiss_password_expiry_soon_notice', array( $this, 'dismiss_password_expiry_soon_notice' ) );
			add_shortcode( 'mls_user_password_expiry_notice', array( $this, 'password_about_to_expire_notice_shortcode' ) );
		}

		/**
		 * Create expire shortcode
		 *
		 * @return string - Notice markup.
		 *
		 * @since 2.0.0
		 */
		public function password_about_to_expire_notice_shortcode() {
			ob_start();
			$this->password_about_to_expire_notice();
			return ob_get_clean();
		}

		/**
		 * Check wp authenticate user
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function ppm_authenticate_user() {
			add_filter( 'wp_authenticate_user', array( $this, 'has_expired' ), $this->filter_priority, 2 );
		}

		/**
		 * Session expired dialog box ajax.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function ppm_ajax_session_expired() {
			$user_id = get_current_user_id();
			$this->expire( $user_id );
			exit;
		}

		/**
		 * Check user password expire OR not.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function check_on_load() {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				return;
			}

			// Get terminate setting.
			$terminate_session_password = OptionsHelper::string_to_bool( $this->options->mls_setting->terminate_session_password );

			// Check force terminate setting is enabled.
			if ( ! $terminate_session_password ) {
				// Check user's password expire or not.
				if ( $this->should_password_expire( $user_id ) ) {
					$this->expire( $user_id );
				}
			}
		}

		/**
		 * Check user password expire OR not.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function check_on_load_front_end() {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				return;
			}

			// Check user's password expire or not.
			if ( $this->should_password_expire( $user_id ) ) {
				$this->expire( $user_id );
			}
		}


		/**
		 * Check user password expired on wp_authenticate_user hook.
		 *
		 * @param object $user User Object.
		 * @param string $password Enter password.
		 *
		 * @return \WP_Error
		 *
		 * @since 2.0.0
		 */
		public function has_expired( $user, $password ) {
			// get the saved history by user.
			$user_password = array();

			if ( is_a( $user, '\WP_User' ) ) {
				// This user is exempt, so lets stop here.
				if ( \MLS_Core::is_user_exempted( $user->ID ) ) {
					return $user;
				}

				$role_options      = OptionsHelper::get_preferred_role_options( $user->roles );
				$expiry            = $role_options->password_expiry;
				$is_feature_active = isset( $role_options->activate_password_expiration_policies ) && OptionsHelper::string_to_bool( $role_options->activate_password_expiration_policies ) ? true : false;

				if ( ! $is_feature_active ) {
					return $user;
				}

				$password_history = get_user_meta( $user->ID, MLS_PW_HISTORY_META_KEY, true );
			} else {
				$password_history = false;
			}

			// Ensure we dont check a change as its happening within UM.
			if ( isset( $_POST['um_account_nonce_password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $user;
			}

			// If check user password history exists OR not.
			if ( $password_history ) {
				// Reset by user.
				foreach ( $password_history as $history ) {
					if ( in_array( 'user', $history, true ) ) {
						$user_password[] = $history;
					}
				}
				// Reset by admin.
				if ( empty( $user_password ) ) {
					foreach ( $password_history as $history ) {
						if ( in_array( 'admin', $history, true ) ) {
							$user_password[] = $history;
						}
					}
				}
			}

			// Get user last password.
			$user_password = end( $user_password );
			if ( empty( $user_password ) && is_a( $user, '\WP_User' ) ) {
				$user_password             = array();
				$user_password['password'] = $user->data->user_pass;
			}

			// the password is not okay.
			if ( $password && is_a( $user, '\WP_User' ) && ! wp_check_password( $password, $user_password['password'], $user->ID ) ) {
				return new \WP_Error(
					'incorrect_password',
					sprintf(
						/* translators: %s: user name */
						__( '<strong>ERROR</strong>: The password you entered for the username %s is incorrect.', 'melapress-login-security' ),
						'<strong>' . $user->user_login . '</strong>'
					) .
					' <a href="' . wp_lostpassword_url() . '">' .
					__( 'Lost your password?', 'melapress-login-security' ) .
					'</a>'
				);
			}


			/* @free:start */
			if ( is_a( $user, '\WP_User' ) ) {
				// check if it password expired flag is existing.
				if ( get_user_meta( $user->ID, MLS_PASSWORD_EXPIRED_META_KEY, true ) ) {
					return new \WP_Error(
						'password-expired',
						sprintf(
							/* translators: %s: user name */
							__( '<strong>ERROR</strong>: The password you entered for the username %s has expired.', 'melapress-login-security' ),
							'<strong>' . $user->user_login . '</strong>'
						) .
						' <a href="' . wp_lostpassword_url() . '">' .
						__( 'Get a new password.', 'melapress-login-security' ) .
						'</a>'
					);
				}
			}
			/* @free:end */

			$mls = melapress_login_security();
			if ( is_a( $user, '\WP_User' ) ) {
				if ( OptionsHelper::string_to_bool( $mls->options->notify_password_reset_on_login ) && get_user_meta( $user->ID, MLS_PREFIX . '_pw_expires_soon_notice_dismissed', true ) ) {
					delete_user_meta( $user->ID, MLS_PREFIX . '_pw_expires_soon_notice_dismissed' );
				}
			}

			// Always return user object.
			return $user;
		}

		/**
		 * Resets particular user password & sets password expire flag, which forces user to reset password.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		private function expire( $user_id ) {

			if ( \MLS_Core::is_user_exempted( $user_id ) ) {
				return;
			}

			if ( ! $this->should_password_expire( $user_id ) ) {
				return;
			}

			// this will reset the password in the system.
			// and the password that the user is trying to enter becomes invalid.
			$user_data        = get_userdata( $user_id );
			$current_password = $user_data->user_pass;

			/**
			 * Fire of action for others to observe.
			 */
			do_action( 'mls_user_password_has_expired', $user_id );

			// Reset user by User ID.
			Reset_Passwords::reset_by_id( $user_id, $current_password, 'system' );
			// save the last expiry time in an easy to access meta as this is
			// used/modified by the inactive users feature.
			$last_expiry = OptionsHelper::set_user_last_expiry_time( current_time( 'timestamp' ), $user_id ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		}

		/**
		 * Should Password Expire.
		 *
		 * @param type $user_id User ID.
		 *
		 * @return boolean
		 *
		 * @since 2.0.0
		 */
		public static function should_password_expire( $user_id ) {
			$user              = get_user_by( 'id', $user_id );
			$role_options      = OptionsHelper::get_preferred_role_options( $user->roles );
			$expiry            = $role_options->password_expiry;
			$is_feature_active = isset( $role_options->activate_password_expiration_policies ) && OptionsHelper::string_to_bool( $role_options->activate_password_expiration_policies ) ? true : false;

			// no need to expire if expiry is set to 0 (by default, or by choice).
			if ( $expiry['value'] < 1 || ! $is_feature_active ) {
				return false;
			}

			if ( \MLS_Core::is_user_exempted( $user_id ) ) {
				return false;
			}

			// get the password history.
			$password_history = get_user_meta( $user_id, MLS_PW_HISTORY_META_KEY, true );
			// no password history means that the password was never reset by the system or admin or user.
			if ( empty( $password_history ) ) {
				$last_reset = (int) get_site_option( MLS_PREFIX . '_activation' );
			} else {
				// check the last entry.
				$last_password_event = end( $password_history );
				$last_reset          = (int) $last_password_event['timestamp'];
			}

			// get the expiry into a string.
			$expiry_string          = implode( ' ', $expiry );
			$notify_password_expiry = $role_options->notify_password_expiry;

			if ( OptionsHelper::string_to_bool( $notify_password_expiry ) ) {
				$expiry_timestamp              = get_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon', true );
				$allowed_time_in_seconds       = OptionsHelper::get_users_password_history_expiry_time_in_seconds( $user_id );
				$time_since_last_reset_seconds = current_time( 'timestamp' ) - $last_reset; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
				$notify_period_in_seconds      = OptionsHelper::get_users_password_expiry_notice_time_in_seconds( $user_id );
				$expiry_days_in_secs           = strtotime( $expiry_string, 0 );
				$grace                         = $expiry_days_in_secs - $notify_period_in_seconds;

				if ( $time_since_last_reset_seconds >= $grace ) {
					update_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon', $last_reset + $expiry_days_in_secs );
				} else {
					delete_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon' );
				}
			}

			// if the password hasn't expired.
			if ( current_time( 'timestamp' ) < strtotime( $expiry_string, $last_reset ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
				return false;
			}

			return true;
		}

		/**
		 * Show notice.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function password_about_to_expire_notice() {
			$user_id                = get_current_user_id();
			$expiry_timestamp       = get_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon', true );
			$notice_dismissed       = get_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon_notice_dismissed', true );
			$user                   = get_user_by( 'id', $user_id );
			$role_options           = OptionsHelper::get_preferred_role_options( $user->roles );
			$notify_password_expiry = ( 'yes' === $role_options->notify_password_expiry ) ? true : false;

			if ( \MLS_Core::is_user_exempted( $user_id ) ) {
				if ( ! empty( $expiry_timestamp ) ) {
					// User was marked as expiring but feature has since been disabled.
					delete_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon' );
					delete_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon_notice_dismissed' );
				}
				return;
			}

			$is_feature_active = isset( $role_options->activate_password_expiration_policies ) && OptionsHelper::string_to_bool( $role_options->activate_password_expiration_policies ) ? true : false;

			if ( $is_feature_active && $notify_password_expiry && ! empty( $expiry_timestamp ) && empty( $notice_dismissed ) ) {
				$user_link = get_edit_profile_url( $user_id );
				?>
				<div id="mls_pw_expire_notice" class="notice notice-success is-dismissible">
					<p>
					<?php
					/* translators: %1s: day %2s: time */
					echo wp_sprintf( esc_html__( 'Your password is going to expire on %1$s at %2$s.', 'melapress-login-security' ), esc_attr( date_i18n( get_option( 'date_format' ), $expiry_timestamp ) ), esc_attr( wp_date( get_option( 'time_format' ), $expiry_timestamp ) ) );
					?>
					</p>
					<p>
						<a href="<?php echo esc_url( $user_link ); ?>" class="button button-primary"><?php esc_html_e( 'Reset password now', 'melapress-login-security' ); ?></a> <a href="#dismiss_pw_notice" class="button button-secondary" data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( 'mls_dismiss_pw_notice_nonce' ) ); ?>" data-user-id="<?php echo esc_attr( $user_id ); ?>"><?php esc_html_e( 'Dismiss notice', 'melapress-login-security' ); ?></a>
					</p>
				</div>
				<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function( $ ) {
					jQuery( 'a[href="#dismiss_pw_notice"], #mls_pw_expire_notice .notice-dismiss' ).on( 'click', function( event ) {
						var nonce  = jQuery( '#mls_pw_expire_notice [data-dismiss-nonce]' ).attr( 'data-dismiss-nonce' );
						var userID = jQuery( '#mls_pw_expire_notice [data-user-id]' ).attr( 'data-user-id' );
						
						jQuery.ajax({
							type: 'POST',
							url: '<?php echo esc_url( network_admin_url( 'admin-ajax.php' ) ); ?>',
							async: true,
							data: {
								action: 'dismiss_password_expiry_soon_notice',
								nonce : nonce,
								user_id: userID,
							},
							success: function ( result ) {		
								jQuery( '#mls_pw_expire_notice' ).slideUp( 300 );
							}
						});
					});
				});
				//]]>
				</script>
				<?php
			} elseif ( ! empty( $expiry_timestamp ) ) {
				// User was marked as expiring but feature has since been disabled.
				delete_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon' );
				delete_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon_notice_dismissed' );
			}
		}

		/**
		 * Handle dismissing notice.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function dismiss_password_expiry_soon_notice() {
			// Grab POSTed data.
			$nonce   = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;
			$user_id = isset( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : false;

			// Check nonce.
			if ( empty( $nonce ) || ! $nonce || ! wp_verify_nonce( $nonce, 'mls_dismiss_pw_notice_nonce' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			update_user_meta( $user_id, MLS_PREFIX . '_pw_expires_soon_notice_dismissed', true );

			wp_send_json_success( esc_html__( 'complete.', 'melapress-login-security' ) );
		}
	}

}
