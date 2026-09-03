<?php
/**
 * MLS Temporary logins class.
 *
 * @package MelapressLoginSecurity
 * @since 2.1.0
 */

declare(strict_types=1);

namespace MLS\TemporaryLogins;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Emailer;

/**
 * Check if this class already exists.
 *
 * @since 2.1.0
 */
if ( ! class_exists( '\MLS\TemporaryLogins\Temporary_Logins' ) ) {

	/**
	 * Declare SessionsManager Class
	 *
	 * @since 2.1.0
	 */
	class Temporary_Logins {

		public const TEMP_USER_META_KEY = 'mls_temp_user';

		/** Prefix for the short-lived option used to serialize bearer-token use. */
		private const LOGIN_LOCK_PREFIX = 'mls_temp_login_lock_';

		/** Failed token lookups allowed from one source address per window. */
		private const MAX_TOKEN_ATTEMPTS = 20;

		/** How long failed token lookups are counted for, in seconds. */
		private const TOKEN_ATTEMPT_WINDOW = 15 * MINUTE_IN_SECONDS;

		/**
		 * Init hooks.
		 *
		 * @since 2.1.0
		 */
		public static function init() {
			\add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
			\add_action( 'wp_ajax_mls_create_login_link', array( __CLASS__, 'create_login_link' ) );
			\add_action( 'wp_ajax_mls_send_login_link', array( __CLASS__, 'send_login_link' ) );
			\add_action( 'admin_init', array( __CLASS__, 'monitor_admin_actions' ) );
			\add_action( 'admin_menu', array( __CLASS__, 'replace_admin_link' ), 11 );
			// Integration: attempt to hook into WP 2FA checks to allow skipping 2FA for temporary users.
			self::integrate_wp2fa();
		}

		/**
		 * Integration helper for WP 2FA plugins.
		 *
		 * Adds filters that short-circuit 2FA requirement when a temporary user has the
		 * `mls_temp_user_skip_2fa` meta set. This is best-effort: it registers handlers
		 * for commonly-provided filter hooks used by 2FA plugins. If the installed 2FA
		 * plugin exposes a different filter name we may need to adapt.
		 *
		 * @return void
		 */
		public static function integrate_wp2fa() {
			// Callback used for filters that ask whether to skip/show the 2FA form.
			$skip_form_cb = function( $skip, $user = null ) {
				$user_id = 0;
				if ( is_int( $user ) || ( is_string( $user ) && ctype_digit( $user ) ) ) {
					$user_id = (int) $user;
				} elseif ( is_object( $user ) && isset( $user->ID ) ) {
					$user_id = (int) $user->ID;
				} elseif ( ! empty( $GLOBALS['user_ID'] ) ) {
					$user_id = (int) $GLOBALS['user_ID'];
				}

				if ( $user_id > 0 && (int) get_user_meta( $user_id, 'mls_temp_user_skip_2fa', true ) ) {
					return true;
				}

				return $skip;
			};

			// Callback used for filters that ask whether 2FA is required/enabled for the user.
			$require_cb = function( $required, $user = null ) {
				$user_id = 0;
				if ( is_int( $user ) || ( is_string( $user ) && ctype_digit( $user ) ) ) {
					$user_id = (int) $user;
				} elseif ( is_object( $user ) && isset( $user->ID ) ) {
					$user_id = (int) $user->ID;
				} elseif ( ! empty( $GLOBALS['user_ID'] ) ) {
					$user_id = (int) $GLOBALS['user_ID'];
				}

				if ( $user_id > 0 && (int) get_user_meta( $user_id, 'mls_temp_user_skip_2fa', true ) ) {
					return false;
				}

				return $required;
			};

			// Register plugin-specific filter to skip the login 2FA form.
			\add_filter( 'wp_2fa_skip_2fa_login_form', $skip_form_cb, 10, 2 );

			// Register common filters used by 2FA providers to determine requirement.
			\add_filter( 'wp_2fa_is_user_required', $require_cb, 10, 2 );
			\add_filter( 'wp_2fa_user_required', $require_cb, 10, 2 );
			\add_filter( 'two_factor_is_user_enabled', $require_cb, 10, 2 );
			\add_filter( 'two_factor_should_run_for_user', $require_cb, 10, 2 );
			\add_filter( 'two_factor_user_api_login_enable', $require_cb, 10, 2 );
		}

		/**
		 * Replace url for alternative link to temp logins admin.
		 *
		 * @return void
		 */
		public static function replace_admin_link() {
			global $submenu;
			if ( isset( $submenu['mls-policies'] ) ) {
				foreach ( $submenu['mls-policies'] as $index => $submenu_item ) {
					if ( 'Temporary Logins' === $submenu_item[0] ) {
						$submenu['mls-policies'][ $index ][2] = 'users.php?page=mls-temporary-logins'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					}
				}
			}
		}

		/**
		 * Monitor for form submissions.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function monitor_admin_actions() {

			if ( ! isset( $_REQUEST['nonce'] ) || empty( $_REQUEST['nonce'] ) ) {
				return;
			}
			if ( ! \wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), MLS_PREFIX . '-disable-login-link' ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) || ! isset( $_REQUEST['action'] ) || empty( $_REQUEST['action'] ) || empty( $_REQUEST['user_id'] ) ) {
				return;
			}

			$user_id  = absint( $_REQUEST['user_id'] );
			$target   = get_user_by( 'ID', $user_id );
			if ( ! $target || ! current_user_can( 'edit_user', $user_id ) || ( is_multisite() && is_super_admin( $user_id ) ) || ! self::is_valid_temp_user( $target ) ) {
				return;
			}
			$base_url = \menu_page_url( 'mls-temporary-logins', false );

			if ( 'delete_link' === $_REQUEST['action'] ) {

				if ( self::is_valid_temp_user( $target ) ) {

					self::delete_user( $user_id );
					add_action( 'admin_notices', array( __CLASS__, 'user_deleted_notice' ) );

					wp_safe_redirect( $base_url, 302 );
				}
				exit();

			} elseif ( 'disable_link' === $_REQUEST['action'] ) {
				if ( self::is_valid_temp_user( $target ) ) {
					update_user_meta( $user_id, 'mls_temp_user_expired', self::get_current_gmt_timestamp() );
					add_action( 'admin_notices', array( __CLASS__, 'user_disabled_notice' ) );

					wp_safe_redirect( $base_url, 302 );
				}
				exit();

			} elseif ( 'enable_link' === $_REQUEST['action'] ) {
				if ( self::is_valid_temp_user( $target ) ) {
					delete_user_meta( $user_id, 'mls_temp_user_expired' );
					add_action( 'admin_notices', array( __CLASS__, 'user_reenabled_notice' ) );

					wp_safe_redirect( $base_url, 302 );
				}
				exit();
			}
		}

		/**
		 * Single user was deleted notice.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function user_deleted_notice() {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Temporary login deleted', 'melapress-login-security' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Bulk users was deleted notice.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function users_deleted_notice() {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Temporary logins deleted', 'melapress-login-security' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Single user was disabled/enabled notice.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function user_disabled_notice() {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Temporary login disabled', 'melapress-login-security' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Bulk users was disabled/enabled notice.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function user_reenabled_notice() {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Temporary login re-enabled.', 'melapress-login-security' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Register our admin menu and page.
		 *
		 * @return void
		 *
		 * @since 2.1.0.
		 */
		public static function register_admin_page() {
			$hook_name = add_submenu_page(
				'users.php',
				__( 'Temporary Logins', 'melapress-login-security' ),
				__( 'Temporary Logins', 'melapress-login-security' ),
				'manage_options',
				'mls-temporary-logins',
				array( __CLASS__, 'admin_area' ),
				10
			);
			\add_action( "load-$hook_name", array( '\MLS\Admin\Admin', 'admin_enqueue_scripts' ) );
		}

		/**
		 * Our admin page.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function admin_area() {
			if ( ! \current_user_can( 'manage_options' ) ) {
				return;
			}

			$editable_roles    = array_reverse( get_editable_roles() );
			$save_button_label = esc_html__( 'Create login link', 'melapress-login-security' );
			$display_form      = false;
			$cancel_href       = '#';
			$cancel_id         = 'id="cancel-mls-create-login"';
			$user_email_class  = '';

			if ( ! function_exists( '\wp_can_install_language_pack' ) ) {
				require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			}

			$languages                = \get_available_languages();
			$can_install_translations = \current_user_can( 'install_languages' ) && \wp_can_install_language_pack();

			$form_values = array(
				'user_email'                    => '',
				'user_first_name'               => '',
				'user_last_name'                => '',
				'user-role'                     => '',
				'skip_2fa'                      => 0,
				'redirect_to'                   => '',
				'login_expire'                  => 'expire_from_now',
				'max_logins'                    => 5,
				'locale'                        => get_locale(),
				'expire_from_now_denominator'   => 'day',
				'expire_from_login_denominator' => 'day',
				'expire_number'                 => 7,
				'expire_from_login_number'      => 7,
				'custom_date'                   => '',
				'custom_time'                   => '',
				'user_id'                       => 0,
			);

			// Are we currently editing an existing tempoary user?
			if ( isset( $_GET['user_id'] ) && isset( $_GET['action'] ) && 'edit_link' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$user_id = absint( $_GET['user_id'] );
				$user    = get_user_by( 'ID', $user_id ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( ! $user || ! current_user_can( 'edit_user', $user_id ) || ( is_multisite() && is_super_admin( $user_id ) ) || ! self::is_valid_temp_user( $user ) ) {
					wp_die(
						esc_html__( 'You cannot edit this temporary login.', 'melapress-login-security' ),
						esc_html__( 'Permission Denied', 'melapress-login-security' ),
						array( 'response' => 403 )
					);
				}

				$form_values = array(
					'user_id'                       => $user->ID,
					'user_email'                    => $user->user_email,
					'user_first_name'               => $user->first_name,
					'user_last_name'                => $user->last_name,
					'user-role'                     => implode( ',', $user->roles ),
					'skip_2fa'                      => (int) get_user_meta( $user->ID, 'mls_temp_user_skip_2fa', true ),
					'redirect_to'                   => get_user_meta( $user->ID, 'mls_temp_user_redirect_to', true ),
					'max_logins'                    => get_user_meta( $user->ID, 'mls_temp_user_max_login_limit', true ),
					'login_expire'                  => get_user_meta( $user->ID, 'mls_temp_user_expires_on', true ),
					'login_count'                   => get_user_meta( $user->ID, 'mls_login_count', true ),
					'locale'                        => get_user_meta( $user->ID, 'locale', true ),
					'expire_from_now_denominator'   => 'day',
					'expire_from_login_denominator' => 'day',
					'expire_number'                 => 7,
					'expire_from_login_number'      => 7,
					'custom_date'                   => ( 'custom_expire' === get_user_meta( $user->ID, 'mls_temp_user_expires_on', true ) ) ? gmdate( 'dd/mm/yy', get_user_meta( $user->ID, 'mls_temp_user_expires_on', true ) ) : '',
					'custom_time'                   => ( 'custom_expire' === get_user_meta( $user->ID, 'mls_temp_user_expires_on', true ) ) ? gmdate( 'HH:mm', get_user_meta( $user->ID, 'mls_temp_user_expires_on', true ) ) : '',
				);

				$user_email_class = 'disabled';
				$display_form     = 'style="display:block"';
				$cancel_href      = menu_page_url( 'mls-temporary-logins', false );
				$cancel_id        = false;

				// This user has expired, so set update label and form defaults.
				if ( get_user_meta( $user->ID, 'mls_temp_user_expired', true ) ) {
					$save_button_label           = esc_html__( 'Update and reactivate link', 'melapress-login-security' );
					$form_values['login_expire'] = 'expire_from_now';
					// This user has an active link.
				} else {
					$save_button_label = esc_html__( 'Edit login link', 'melapress-login-security' );
					if ( 'expire_from_first_use' === $form_values['login_expire'] ) {
						$expiry                                       = explode( ' ', get_user_meta( $user->ID, 'mls_temp_user_expires_on_date', true ) );
						$form_values['expire_from_login_number']      = $expiry[0];
						$form_values['expire_from_login_denominator'] = $expiry[1];
					} elseif ( 'custom_expiry' !== $form_values['login_expire'] ) {
						// User is set to expire from date of creation.
						$expiry = explode( ' ', get_user_meta( $user->ID, 'mls_temp_user_expires_on_date', true ) );
						if ( isset( $expiry[1] ) ) {
							$form_values['login_expire']                = 'expire_from_now';
							$form_values['expire_number']               = $expiry[0];
							$form_values['expire_from_now_denominator'] = $expiry[1];
							// User us set to expire at a custom time and date.
						} else {
							$time                        = gmdate( 'h:i', (int) $form_values['login_expire'] );
							$form_values['login_expire'] = 'custom_expiry';
							$form_values['custom_date']  = $expiry[0];
							$form_values['custom_time']  = $time;
						}
					}
				}
			}

			?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php esc_html_e( 'Melapress Login Security - Temporary Login', 'melapress-login-security' ); ?></h1>
					<a href="<?php echo esc_url( add_query_arg( 'page', 'mls-temporary-logins&action=create-login', admin_url( 'users.php' ) ) ); ?>" class="page-title-action mls-create-login-link"><?php esc_html_e( 'Create temporary login', 'melapress-login-security' ); ?></a>

					<p><?php esc_html_e( 'Create time-limited login links for external users without sharing passwords or creating permanent WordPress accounts. Temporary logins automatically expire and can be revoked at any time. Learn more about', 'melapress-login-security' ); ?> <a href="https://melapress.com/create-manage-wordpress-temporary-users-plugin/?utm_source=plugin&utm_medium=mls&temp_login_help_text" target="_blank"><?php esc_html_e( 'temporary logins', 'melapress-login-security' ); ?></a>.</p>

					<form id="new-temp-login-form" method="post" <?php echo wp_kses_post( $display_form ); ?>>
						<table class="form-table form-content">
							<input name="user_id" type="number" id="user_id" value="<?php echo esc_attr( $form_values['user_id'] ); ?>" aria-required="true" maxlength="60" class="form-input hidden">

							<tbody>
								<tr class="form-field">
									<th scope="row">
										<label for="user_email"><?php esc_html_e( 'Email address', 'melapress-login-security' ); ?></label>
									</th>
									<td class="pt-2">
										<input name="user_email" type="text" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" id="user_email" value="<?php echo esc_attr( $form_values['user_email'] ); ?>" aria-required="true" maxlength="60" class="form-input mw-300 <?php echo esc_attr( $user_email_class ); ?>" <?php echo esc_attr( $user_email_class ); ?>>
									</td>
								</tr>

								<tr class="form-field">
									<th scope="row">
										<label for="user_first_name"><?php esc_html_e( 'First name', 'melapress-login-security' ); ?></label>
									</th>
									<td>
										<input name="user_first_name" type="text" id="user_first_name" value="<?php echo esc_attr( $form_values['user_first_name'] ); ?>" aria-required="true" maxlength="60" class="form-input mw-300">
									</td>
								</tr>

								<tr class="form-field">
									<th scope="row">
										<label for="user_last_name"><?php esc_html_e( 'Last name', 'melapress-login-security' ); ?></label>
									</th>
									<td>
										<input name="user_last_name" type="text" id="user_last_name" value="<?php echo esc_attr( $form_values['user_last_name'] ); ?>" aria-required="true" maxlength="60" class="form-input mw-300">
									</td>
								</tr>

								<tr class="form-field">
									<th scope="row">
										<label for="user-role"><?php esc_html_e( 'Role', 'melapress-login-security' ); ?></label>
									</th>
									<td>
										<select name="role" id="user-role">											
											<?php
											foreach ( $editable_roles as $role_slug => $role_info ) {
												?>
													<option value="<?php echo esc_attr( $role_slug ); ?>" <?php selected( $form_values['user-role'], $role_slug ); ?>><?php echo esc_attr( $role_info['name'] ); ?></option>
													<?php
											}
											?>
										</select>
									</td>
								</tr>

								<tr class="form-field">
									<th scope="row">
										<label for="skip_2fa"><?php esc_html_e( 'Skip 2FA', 'melapress-login-security' ); ?></label>
									</th>
									<td>
										<label for="skip_2fa">
											<style>
												#skip_2fa {
													width: 0px !important;
												}
											</style>
											<input type="checkbox" id="skip_2fa" name="skip_2fa" value="1" <?php checked( $form_values['skip_2fa'], 1 ); ?>>
											<?php esc_html_e( 'If 2FA is enforced for users with this role via the', 'melapress-login-security' ); ?> <a href="https://melapress.com/wordpress-2fa/?utm_source=plugin&utm_medium=mls&temp_login_help_text_2fa" target="_blank"><?php esc_html_e( 'WP 2FA plugin', 'melapress-login-security' ); ?></a><?php esc_html_e( ', skip 2FA for this temporary user.', 'melapress-login-security' ); ?>
										</label>
									</td>
								</tr>
							
								<tr class="form-field">
									<th scope="row">
										<label for="redirect_to"><?php esc_html_e( 'Redirect after login', 'melapress-login-security' ); ?></label>
									</th>
									<td>
										<select name="redirect_to" id="redirect_to">
											<option value="wp_dashboard" <?php selected( $form_values['redirect_to'], 'wp_dashboard' ); ?>><?php esc_html_e( 'WordPress dashboard', 'melapress-login-security' ); ?></option>
											<option value="system_default" <?php selected( $form_values['redirect_to'], 'system_default' ); ?>><?php esc_html_e( 'System Default', 'melapress-login-security' ); ?></option>
											<option value="home_page" <?php selected( $form_values['redirect_to'], 'home_page' ); ?>><?php esc_html_e( 'Website Home Page', 'melapress-login-security' ); ?></option>		
										</select>
									</td>
								</tr>

								<tr class="form-field">
									<th scope="row">
										<label for="adduser-role"><?php esc_html_e( 'Login expiry', 'melapress-login-security' ); ?></label>
									</th>
									<td>
										<input type="radio" id="expire_from_now" name="login_expire" value="expire_from_now" <?php checked( $form_values['login_expire'], 'expire_from_now' ); ?>>
										<label for="expire_from_now"><?php esc_html_e( 'Expire', 'melapress-login-security' ); ?> 
											<input type="number" id="expire_number" name="expire_number" value="<?php echo esc_attr( $form_values['expire_number'] ); ?>" size="4" class="inline-input ltr" min="1">
											<select name="expire_from_now_denominator" id="expire_from_now_denominator">												
												<option value="hour" <?php selected( $form_values['expire_from_now_denominator'], 'hour' ); ?>><?php esc_html_e( 'Hours', 'melapress-login-security' ); ?></option>
												<option value="day" <?php selected( $form_values['expire_from_now_denominator'], 'day' ); ?>><?php esc_html_e( 'Days', 'melapress-login-security' ); ?></option>
												<option value="week" <?php selected( $form_values['expire_from_now_denominator'], 'week' ); ?>><?php esc_html_e( 'Weeks', 'melapress-login-security' ); ?></option>
												<option value="month" <?php selected( $form_values['expire_from_now_denominator'], 'month' ); ?>><?php esc_html_e( 'Months', 'melapress-login-security' ); ?></option>				
											</select>
											<?php esc_html_e( 'from now', 'melapress-login-security' ); ?>
										</label><br><br>

										<input type="radio" id="expire_from_first_use" name="login_expire" value="expire_from_first_use" <?php checked( $form_values['login_expire'], 'expire_from_first_use' ); ?>>
										<label for="expire_from_first_use"><?php esc_html_e( 'Expire', 'melapress-login-security' ); ?> 
										<input type="number" id="expire_from_login_number" name="expire_from_login_number" value="<?php echo esc_attr( $form_values['expire_from_login_number'] ); ?>" size="4" class="inline-input ltr" min="1">
											<select name="expire_from_login_denominator" id="expire_from_login_denominator">												
												<option value="hour" <?php selected( $form_values['expire_from_login_denominator'], 'hour' ); ?>><?php esc_html_e( 'Hours', 'melapress-login-security' ); ?></option>
												<option value="day" <?php selected( $form_values['expire_from_login_denominator'], 'day' ); ?>><?php esc_html_e( 'Days', 'melapress-login-security' ); ?></option>
												<option value="week" <?php selected( $form_values['expire_from_login_denominator'], 'week' ); ?>><?php esc_html_e( 'Weeks', 'melapress-login-security' ); ?></option>
												<option value="month" <?php selected( $form_values['expire_from_login_denominator'], 'month' ); ?>><?php esc_html_e( 'Months', 'melapress-login-security' ); ?></option>				
											</select>

											<?php esc_html_e( 'from initial access', 'melapress-login-security' ); ?>
										</label><br><br>
										
										<input type="radio" id="custom_expiry" name="login_expire" value="custom_expiry" <?php checked( $form_values['login_expire'], 'custom_expiry' ); ?>>
										<label for="custom_expiry"><?php esc_html_e( 'Expire on specific date & time', 'melapress-login-security' ); ?>
											<span>
												<input type="text" id="mls-datepicker" placeholder="DD/MM/YYYY" name="custom_date" value="<?php echo esc_attr( $form_values['custom_date'] ); ?>">
												<input type="text" id="mls-timepicker" placeholder="00:00" name="custom_time" value="<?php echo esc_attr( $form_values['custom_time'] ); ?>">
											</span>
										</label>									
									</td>
								</tr>

								<tr class="form-field">
									<th scope="row">
										<label for="language"><?php esc_html_e( 'Max logins', 'melapress-login-security' ); ?></label>
									</th>
									<td scope="row">
										<label for="expire_from_first_use"><?php esc_html_e( 'Expire account after', 'melapress-login-security' ); ?> 
											<input type="number" id="max_logins" name="max_logins" value="<?php echo esc_attr( $form_values['max_logins'] ); ?>" size="4" class="inline-input ltr" min="1">
											<?php esc_html_e( 'logins', 'melapress-login-security' ); ?>
										</label>
									</td>
								</tr>

								<?php if ( ! $cancel_id ) { ?>
								<tr class="form-field">
									<th scope="row">
										<label for="language"><?php esc_html_e( 'Current login count', 'melapress-login-security' ); ?></label>
									</th>
									<td scope="row">
										<label for="login_count"><?php esc_html_e( 'Current login count', 'melapress-login-security' ); ?> 
											<input type="number" id="login_count" name="login_count" value="<?php echo esc_attr( $form_values['login_count'] ); ?>" size="4" class="inline-input ltr" min="1">
										</label>
									</td>
								</tr>
								<?php } ?>

								<tr class="form-field">
									<th scope="row">
										<label for="language"><?php esc_html_e( 'Language', 'melapress-login-security' ); ?></label>
									</th>
									<td scope="row" class="mls-language-dropdown">
										<?php
											wp_dropdown_languages(
												array(
													'name' => 'locale',
													'selected' => $form_values['locale'],
													'languages' => $languages,
													'show_available_translations' => $can_install_translations,
													'show_option_site_default' => true,
												)
											);
										?>
										<small>
											<?php esc_html_e( 'Language will be installed if not already.', 'melapress-login-security' ); ?>
										</small>
									</td>
								</tr>
								
								<?php if ( $cancel_id ) { ?>
								<tr class="form-field">
									<th scope="row">
										<label for="language"><?php esc_html_e( 'Send email', 'melapress-login-security' ); ?></label>
									</th>
									<td scope="row">
										<input type="checkbox" id="send_email" name="send_email" value="send_email">
										<label for="send_email"><?php esc_html_e( 'Send new user the link via email', 'melapress-login-security' ); ?></label>
									</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
						
						<div>
							<p class="submit">
								<a href="#" data-nonce="<?php echo esc_attr( wp_create_nonce( MLS_PREFIX . '-create-login' ) ); ?>" class="button button-primary" id="mls-create-login-submit"><?php echo esc_attr( $save_button_label ); ?></a> <a href="<?php echo esc_attr( $cancel_href ); ?>" class="button button-secondary" <?php echo wp_kses_post( $cancel_id ); ?>><?php esc_html_e( 'Cancel', 'melapress-login-security' ); ?></a>
							</p>

							<span id="mls-create-login-result" ></span>

							<?php
							if ( $display_form ) {
								wp_nonce_field( MLS_PREFIX . '-edit-login', MLS_PREFIX . '-edit-login-nonce' );
							}
							?>
							<?php wp_nonce_field( MLS_PREFIX . '-create-login', MLS_PREFIX . '-create-login-nonce' ); ?>
						</div>
					</form>

					<p>
						<?php esc_html_e( 'Below is a list of temporary logins currently active.', 'melapress-login-security' ); ?>
					</p>
					<form id="melapress_temp_logins" method="post">
						<?php
							$roles_table = new Temporary_Logins_Table();
							$roles_table->prepare_items();
							$roles_table->display();
						?>
					</form>
				</div>
			<?php
		}

		/**
		 * Create login link.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function create_login_link() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$post_array = filter_input_array( INPUT_POST );
			$data       = array();
			$allowed_fields = array(
				'user_id',
				'user_first_name',
				'user_last_name',
				'user_email',
				'role',
				'login_expire',
				'expire_number',
				'expire_from_now_denominator',
				'expire_from_login_number',
				'expire_from_login_denominator',
				'custom_date',
				'custom_time',
				'max_logins',
				'login_count',
				'redirect_to',
				'locale',
				'send_email',
				'skip_2fa',
				'mls-create-login-nonce',
				'mls-edit-login-nonce',
			);

			if ( empty( $post_array['form_data'] ) || ! is_array( $post_array['form_data'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid request.', 'melapress-login-security' ) ) );
			}

			foreach ( $post_array['form_data'] as $posted_setting ) {
				if ( ! is_array( $posted_setting ) || ! isset( $posted_setting['name'], $posted_setting['value'] ) || ! is_scalar( $posted_setting['name'] ) || ! is_scalar( $posted_setting['value'] ) ) {
					continue;
				}

				$name = sanitize_key( $posted_setting['name'] );
				if ( in_array( $name, $allowed_fields, true ) ) {
					$data[ $name ] = $posted_setting['value'];
				}
			}

			$data['user_id'] = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : 0;
			$data            = self::validate_login_data( $data );

			$nonce         = isset( $data['mls-create-login-nonce'] ) ? $data['mls-create-login-nonce'] : false;
			$edit_nonce    = isset( $data['mls-edit-login-nonce'] ) ? $data['mls-edit-login-nonce'] : false;
			$is_valid_edit = false;

			// Check nonce.
			if ( ! isset( $data['user_id'] ) || ! \current_user_can( 'manage_options' ) || ! $nonce || ! wp_verify_nonce( $nonce, MLS_PREFIX . '-create-login' ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Nonce check failed.', 'melapress-login-security' ) ) );
			}

			if ( ( 0 === $data['user_id'] && empty( $data['user_email'] ) ) || ( ! empty( $data['user_email'] ) && ! is_email( $data['user_email'] ) ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Please provide a valid email.', 'melapress-login-security' ) ) );
			}

			if ( empty( $data['user_first_name'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Please provide at least a first name.', 'melapress-login-security' ) ) );
			}

			if ( $edit_nonce && wp_verify_nonce( $edit_nonce, MLS_PREFIX . '-edit-login' ) && isset( $data['user_id'] ) ) {
				$is_valid_edit = true;
			}

			if ( $is_valid_edit ) {
				$result = self::update_user( $data['user_id'], $data, $edit_nonce );
			} else {
				$result = self::create_new_user( $data );
			}

			if ( isset( $result['error'] ) && ! empty( $result['error'] ) ) {
				$return = array(
					'message' => $result['message'],
				);

				wp_send_json_error( $return );
			}

			$return = array(
				'message'    => ( $is_valid_edit ) ? esc_html__( 'Login updated.', 'melapress-login-security' ) : esc_html__( 'Login created.', 'melapress-login-security' ),
				'link'       => ( isset( $result['user_id'] ) ) ? self::get_login_url( $result['user_id'] ) : false,
				'event_data' => $result,
			);

			wp_send_json_success( $return );
		}

		/**
		 * Allowlist and bound all temporary-login form values.
		 *
		 * @param array $data Submitted form values.
		 * @return array
		 */
		private static function validate_login_data( array $data ): array {
			$expiry_modes = array( 'expire_from_now', 'expire_from_first_use', 'custom_expiry' );
			$units        = array( 'hour', 'day', 'week', 'month' );
			$redirects    = array( 'wp_dashboard', 'system_default', 'home_page' );

			$data['login_expire']                  = isset( $data['login_expire'] ) && in_array( $data['login_expire'], $expiry_modes, true ) ? $data['login_expire'] : 'expire_from_now';
			$data['expire_from_now_denominator']   = isset( $data['expire_from_now_denominator'] ) && in_array( $data['expire_from_now_denominator'], $units, true ) ? $data['expire_from_now_denominator'] : 'day';
			$data['expire_from_login_denominator'] = isset( $data['expire_from_login_denominator'] ) && in_array( $data['expire_from_login_denominator'], $units, true ) ? $data['expire_from_login_denominator'] : 'day';
			$data['expire_number']                 = isset( $data['expire_number'] ) ? min( 3650, max( 1, absint( $data['expire_number'] ) ) ) : 1;
			$data['expire_from_login_number']      = isset( $data['expire_from_login_number'] ) ? min( 3650, max( 1, absint( $data['expire_from_login_number'] ) ) ) : 1;
			$data['max_logins']                    = isset( $data['max_logins'] ) ? min( 10000, max( 1, absint( $data['max_logins'] ) ) ) : 5;
			$data['login_count']                   = isset( $data['login_count'] ) ? min( $data['max_logins'], absint( $data['login_count'] ) ) : 0;
			$data['redirect_to']                   = isset( $data['redirect_to'] ) && in_array( $data['redirect_to'], $redirects, true ) ? $data['redirect_to'] : 'wp_dashboard';
			$data['custom_date']                   = isset( $data['custom_date'] ) ? sanitize_text_field( $data['custom_date'] ) : '';
			$data['custom_time']                   = isset( $data['custom_time'] ) ? sanitize_text_field( $data['custom_time'] ) : '';

			if ( 'custom_expiry' === $data['login_expire'] && ( ! preg_match( '/^\d{1,2}\/\d{1,2}\/\d{4}$/', $data['custom_date'] ) || ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $data['custom_time'] ) ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Please provide a valid expiry date and time.', 'melapress-login-security' ) ) );
			}

			$locale         = isset( $data['locale'] ) ? sanitize_text_field( $data['locale'] ) : get_locale();
			$data['locale'] = preg_match( '/^[A-Za-z][A-Za-z0-9_@-]{1,19}$/', $locale ) ? $locale : get_locale();

			return $data;
		}

		/**
		 * Create a new user
		 *
		 * @param array $data - New user data.
		 *
		 * @return array|int|WP_Error
		 */
		public static function create_new_user( $data ) {

			$nonce = isset( $data['mls-create-login-nonce'] ) ? $data['mls-create-login-nonce'] : false;

			// Check nonce.
			if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'create_users' ) || ! current_user_can( 'promote_users' ) || ! $nonce || ! wp_verify_nonce( $nonce, MLS_PREFIX . '-create-login' ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Nonce check failed.', 'melapress-login-security' ) ) );
			}

			$result = array(
				'error' => true,
			);

			$expiry_option = ! empty( $data['login_expire'] ) ? $data['login_expire'] : 'expire_from_now';

			// Grab date depending on desired expiry type.
			if ( 'expire_from_now' === $expiry_option ) {
				$expire_number      = ! empty( $data['expire_number'] ) ? $data['expire_number'] : 1;
				$expire_denominator = ! empty( $data['expire_from_now_denominator'] ) ? $data['expire_from_now_denominator'] : 'days';
				$date               = $expire_number . ' ' . $expire_denominator;
				$time               = '';
			} elseif ( 'expire_from_first_use' === $expiry_option ) {
				$expire_number      = ! empty( $data['expire_from_login_number'] ) ? $data['expire_from_login_number'] : 1;
				$expire_denominator = ! empty( $data['expire_from_login_denominator'] ) ? $data['expire_from_login_denominator'] : 'days';
				$date               = $expire_number . ' ' . $expire_denominator;
				$time               = '';
			} else {
				$date = ! empty( $data['custom_date'] ) ? $data['custom_date'] : '';
				$time = ! empty( $data['custom_time'] ) ? $data['custom_time'] : '';
			}

			$max_login_limit = ! empty( $data['max_logins'] ) ? max( 1, absint( $data['max_logins'] ) ) : 5;

			$send_email = ! empty( $data['send_email'] ) ? $data['send_email'] : false;

			$password    = self::generate_password();
			$username    = self::create_username( $data );
			$first_name  = isset( $data['user_first_name'] ) ? sanitize_text_field( $data['user_first_name'] ) : '';
			$last_name   = isset( $data['user_last_name'] ) ? sanitize_text_field( $data['user_last_name'] ) : '';
			$email       = isset( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : '';
			$role        = ! empty( $data['role'] ) ? sanitize_key( $data['role'] ) : 'subscriber';
			$redirect_to = ! empty( $data['redirect_to'] ) ? sanitize_text_field( $data['redirect_to'] ) : 'wp_dashboard';
			$skip_2fa    = ! empty( $data['skip_2fa'] ) ? 1 : 0;

			if ( ! array_key_exists( $role, get_editable_roles() ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'You cannot assign the requested role.', 'melapress-login-security' ) ) );
			}

			$user_args = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => sanitize_email( $email ),
				'role'       => $role,
			);

			$user_id = \wp_insert_user( $user_args );

			if ( is_wp_error( $user_id ) ) {
				$code = $user_id->get_error_code();

				$result['errcode'] = $code;
				$result['message'] = $user_id->get_error_message( $code );

			} else {

				/*
				 * Temporary accounts must never become network super administrators.
				 * Even a current super admin can create a normal account and promote it
				 * later through WordPress's dedicated, audited network-admin workflow.
				 */

				$locale = ! empty( $data['locale'] ) ? $data['locale'] : 'en_US';

				self::check_and_install_language( $locale );

				\update_user_meta( $user_id, self::TEMP_USER_META_KEY, true );
				\update_user_meta( $user_id, 'mls_temp_user_created_on', self::get_current_gmt_timestamp() );
				\update_user_meta( $user_id, 'mls_temp_user_expires_on', self::get_user_expire_time( $expiry_option, $date, $time ) );
				\update_user_meta( $user_id, 'mls_temp_user_expires_on_date', $date );
				\update_user_meta( $user_id, 'mls_temp_user_max_login_limit', $max_login_limit );

				if ( ! self::store_token( $user_id, self::generate_mls_temporary_token( $user_id ) ) ) {
					// The token cannot be stored safely, so there is no login to
					// hand out. Remove the account rather than leave a broken one.
					if ( ! function_exists( '\wp_delete_user' ) ) {
						require_once ABSPATH . 'wp-admin/includes/user.php';
					}

					\wp_delete_user( $user_id );

					$result['errcode'] = 'token_encryption_unavailable';
					$result['message'] = \esc_html__( 'This site cannot encrypt temporary login tokens, so the temporary login was not created. Enable the PHP sodium or OpenSSL extension and try again.', 'melapress-login-security' );

					return $result;
				}

				\update_user_meta( $user_id, 'mls_temp_token_created_at', time() );
				\update_user_meta( $user_id, 'mls_temp_user_redirect_to', $redirect_to );
				\update_user_meta( $user_id, 'mls_temp_user_skip_2fa', $skip_2fa );

				if ( (bool) $skip_2fa ) {
					\update_user_meta( $user_id, 'wp_2fa_enforcement_state', 'excluded' );
				} else {
					\delete_user_meta( $user_id, 'wp_2fa_enforcement_state' );
				}
				\update_user_meta( $user_id, 'show_welcome_panel', 0 );
				\update_user_meta( $user_id, 'locale', $locale );

				if ( $send_email ) {
					self::send_login_link( sanitize_email( $email ), $user_id );
				}

				$result['error']   = false;
				$result['user_id'] = $user_id;
			}

			return $result;
		}

		/**
		 * Check if locale is already installed and if it is not, install it.
		 *
		 * @param   string $locale  Locale to check.
		 *
		 * @return  void
		 *
		 * @since 2.1.0
		 */
		private static function check_and_install_language( $locale ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! in_array( $locale, get_available_languages(), true ) ) {
				if ( ! function_exists( '\wp_can_install_language_pack' ) ) {
					require_once ABSPATH . 'wp-admin/includes/translation-install.php';
				}

				if ( current_user_can( 'install_languages' ) && \wp_can_install_language_pack() ) {
					\wp_download_language_pack( $locale );
				}
			}
		}

		/**
		 * Update a user.
		 *
		 * @param   int   $user_id  User ID to update.
		 * @param   array $data     Updated data.
		 * @param   array $nonce    Nonce.
		 *
		 * @return  mixed           Result.
		 *
		 * @since 2.1.0
		 */
		public static function update_user( $user_id = 0, $data = array(), $nonce = false ) {
			$user_id = absint( $user_id );
			$target  = get_user_by( 'ID', $user_id );
			if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_user', $user_id ) || ! current_user_can( 'promote_user', $user_id ) || ! $nonce || ! wp_verify_nonce( $nonce, MLS_PREFIX . '-edit-login' ) || 0 === $user_id || ! $target || ( is_multisite() && is_super_admin( $user_id ) ) || ! self::is_valid_temp_user( $target ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Nonce check failed.', 'melapress-login-security' ) ) );
			}

			$expiry_option   = ! empty( $data['login_expire'] ) ? $data['login_expire'] : 'expire_from_now';
			$max_login_limit = ! empty( $data['max_logins'] ) ? max( 1, absint( $data['max_logins'] ) ) : 5;
			$send_email      = ! empty( $data['send_email'] ) ? $data['send_email'] : false;
			$first_name      = isset( $data['user_first_name'] ) ? sanitize_text_field( $data['user_first_name'] ) : '';
			$last_name       = isset( $data['user_last_name'] ) ? sanitize_text_field( $data['user_last_name'] ) : '';
			$email           = isset( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : '';
			$role            = ! empty( $data['role'] ) ? sanitize_key( $data['role'] ) : 'subscriber';
			$redirect_to     = ! empty( $data['redirect_to'] ) ? sanitize_text_field( $data['redirect_to'] ) : 'wp_dashboard';
			$login_count     = ! empty( $data['login_count'] ) ? sanitize_text_field( $data['login_count'] ) : 0;
			$skip_2fa        = ! empty( $data['skip_2fa'] ) ? 1 : 0;

			if ( ! array_key_exists( $role, get_editable_roles() ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'You cannot assign the requested role.', 'melapress-login-security' ) ) );
			}

			// Grab date depending on desired expiry type.
			if ( 'expire_from_now' === $expiry_option ) {
				$expire_number      = ! empty( $data['expire_number'] ) ? $data['expire_number'] : 1;
				$expire_denominator = ! empty( $data['expire_from_now_denominator'] ) ? $data['expire_from_now_denominator'] : 'days';
				$date               = $expire_number . ' ' . $expire_denominator;
				$time               = '';
			} elseif ( 'expire_from_first_use' === $expiry_option ) {
				$expire_number      = ! empty( $data['expire_from_login_number'] ) ? $data['expire_from_login_number'] : 1;
				$expire_denominator = ! empty( $data['expire_from_login_denominator'] ) ? $data['expire_from_login_denominator'] : 'days';
				$date               = $expire_number . ' ' . $expire_denominator;
				$time               = '';
			} else {
				$date = ! empty( $data['custom_date'] ) ? $data['custom_date'] : '';
				$time = ! empty( $data['custom_time'] ) ? $data['custom_time'] : '';
			}

			$user_args = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'role'       => $role,
				'ID'         => $user_id,
			);

			$user_id = wp_update_user( $user_args );

			if ( is_wp_error( $user_id ) ) {
				$code = $user_id->get_error_code();

				return array(
					'error'   => true,
					'errcode' => $code,
					'message' => $user_id->get_error_message( $code ),
				);
			}

			// Super-admin promotion is deliberately unavailable to temporary logins.

			$locale = ! empty( $data['locale'] ) ? $data['locale'] : 'en_US';

			self::check_and_install_language( $locale );

			if ( (bool) $skip_2fa ) {
				update_user_meta( $user_id, 'wp_2fa_enforcement_state', 'excluded' );
			} else {
				delete_user_meta( $user_id, 'wp_2fa_enforcement_state' );
			}

			update_user_meta( $user_id, 'mls_temp_user_updated', self::get_current_gmt_timestamp() );
			update_user_meta( $user_id, 'mls_temp_user_expires_on', self::get_user_expire_time( $expiry_option, $date, $time ) );
			update_user_meta( $user_id, 'mls_temp_user_expires_on_date', $date );
			update_user_meta( $user_id, 'mls_temp_user_max_login_limit', $max_login_limit );
			update_user_meta( $user_id, 'mls_temp_user_redirect_to', $redirect_to );
			update_user_meta( $user_id, 'mls_temp_user_skip_2fa', $skip_2fa );
			update_user_meta( $user_id, 'locale', $locale );
			update_user_meta( $user_id, 'mls_login_count', $login_count );

			delete_user_meta( $user_id, 'mls_temp_user_expired' );

			if ( $send_email ) {
				self::send_login_link( sanitize_email( $email ) );
			}

			return $user_id;
		}

		/**
		 * Generate Temporary Login Token
		 *
		 * @param int $user_id - User ID.
		 *
		 * @return false|string
		 *
		 * @since 2.1.0
		 */
		/**
		 * Meta key holding the login token.
		 *
		 * @var string
		 *
		 * @since 2.4.0
		 */
		const TOKEN_META = 'mls_temp_user_token';

		/**
		 * Meta key holding the deterministic lookup hash for the token.
		 *
		 * The token itself is stored encrypted, and encryption is randomised,
		 * so the ciphertext cannot be used to find the user. This keyed hash
		 * can: it is stable for a given token and reveals nothing about it.
		 *
		 * @var string
		 *
		 * @since 2.4.0
		 */
		const TOKEN_LOOKUP_META = 'mls_temp_user_token_lookup';

		/**
		 * Marker identifying an encrypted token, and its format version.
		 *
		 * @var string
		 *
		 * @since 2.4.0
		 */
		const TOKEN_CIPHER_PREFIX = 'mlsenc1:';

		/**
		 * Key used to encrypt stored login tokens.
		 *
		 * Derived from the site's salts, which live in wp-config.php rather
		 * than the database — so a leaked database dump, a user-export plugin
		 * or SQL injection elsewhere on the site yields ciphertext rather than
		 * working login links.
		 *
		 * @return string - 32 raw bytes.
		 *
		 * @since 2.4.0
		 */
		private static function token_encryption_key() {
			return hash_hmac( 'sha256', 'mls-temporary-login-token', wp_salt( 'secure_auth' ), true );
		}

		/**
		 * Deterministic lookup hash for a raw token.
		 *
		 * @param string $token - Raw token.
		 *
		 * @return string
		 *
		 * @since 2.4.0
		 */
		public static function token_lookup_hash( $token ) {
			return hash_hmac( 'sha256', (string) $token, wp_salt( 'secure_auth' ) );
		}

		/**
		 * Encrypt a token for storage.
		 *
		 * Returns false when the platform offers neither sodium nor AES-GCM. It
		 * used to return the raw token instead, which quietly turned a database
		 * read into a working set of login links — the one thing the encryption
		 * is for — and did so silently, so nobody would know the protection was
		 * absent. The compatibility argument for a fallback applies to reading
		 * tokens issued before 2.4.0, which `decrypt_token()` still handles; it
		 * does not apply here, where the token being stored is brand new.
		 *
		 * In practice this cannot happen: sodium has been bundled with PHP since
		 * 7.2 and this plugin requires 8.0.
		 *
		 * @param string $token - Raw token.
		 *
		 * @return string|false Ciphertext, or false if it cannot be encrypted.
		 *
		 * @since 2.4.0
		 */
		public static function encrypt_token( $token ) {
			$token = (string) $token;
			$key   = self::token_encryption_key();

			if ( function_exists( 'sodium_crypto_secretbox' ) ) {
				$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

				return self::TOKEN_CIPHER_PREFIX . base64_encode( $nonce . sodium_crypto_secretbox( $token, $nonce, $key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			}

			if ( function_exists( 'openssl_encrypt' ) && in_array( 'aes-256-gcm', openssl_get_cipher_methods(), true ) ) {
				$iv     = random_bytes( 12 );
				$tag    = '';
				$cipher = openssl_encrypt( $token, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );

				if ( false !== $cipher ) {
					return self::TOKEN_CIPHER_PREFIX . base64_encode( $iv . $tag . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				}
			}

			return false;
		}

		/**
		 * Decrypt a stored token.
		 *
		 * Anything without the marker is treated as a legacy clear text token
		 * and returned as-is, so links issued before 2.4.0 keep working.
		 *
		 * @param string $stored - Stored value.
		 *
		 * @return string - Raw token, or empty string when it cannot be read.
		 *
		 * @since 2.4.0
		 */
		public static function decrypt_token( $stored ) {
			$stored = (string) $stored;

			if ( '' === $stored || 0 !== strpos( $stored, self::TOKEN_CIPHER_PREFIX ) ) {
				return $stored;
			}

			$payload = base64_decode( substr( $stored, strlen( self::TOKEN_CIPHER_PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			if ( false === $payload ) {
				return '';
			}

			$key = self::token_encryption_key();

			if ( function_exists( 'sodium_crypto_secretbox_open' ) && strlen( $payload ) > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
				$nonce  = substr( $payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
				$cipher = substr( $payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
				$plain  = sodium_crypto_secretbox_open( $cipher, $nonce, $key );

				if ( false !== $plain ) {
					return $plain;
				}
			}

			if ( function_exists( 'openssl_decrypt' ) && strlen( $payload ) > 28 ) {
				$iv     = substr( $payload, 0, 12 );
				$tag    = substr( $payload, 12, 16 );
				$cipher = substr( $payload, 28 );
				$plain  = openssl_decrypt( $cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );

				if ( false !== $plain ) {
					return $plain;
				}
			}

			return '';
		}

		/**
		 * Store a token for a user: encrypted value plus its lookup hash.
		 *
		 * @param int    $user_id - User the token belongs to.
		 * @param string $token   - Raw token.
		 *
		 * @return bool True when stored; false when the token could not be
		 *              encrypted, in which case nothing is written.
		 *
		 * @since 2.4.0
		 */
		public static function store_token( $user_id, $token ) {
			$ciphertext = self::encrypt_token( $token );

			if ( false === $ciphertext ) {
				/*
				 * Fail closed. Storing the token in clear text would make a
				 * database dump directly usable as a set of login links, so the
				 * temporary login is left without a usable token instead and the
				 * caller reports the failure.
				 */
				return false;
			}

			\update_user_meta( (int) $user_id, self::TOKEN_META, $ciphertext );
			\update_user_meta( (int) $user_id, self::TOKEN_LOOKUP_META, self::token_lookup_hash( $token ) );

			return true;
		}

		/**
		 * Raw token for a user, for display in the admin.
		 *
		 * @param int $user_id - User to read.
		 *
		 * @return string
		 *
		 * @since 2.4.0
		 */
		public static function get_raw_token( $user_id ) {
			return self::decrypt_token( \get_user_meta( (int) $user_id, self::TOKEN_META, true ) );
		}

		public static function generate_mls_temporary_token( $user_id ) {
			$byte_length = 64;

			// PHP 7.3+ guarantees random_bytes() availability — no weak fallback needed.
			return bin2hex( random_bytes( $byte_length ) );
		}

		/**
		 * Rotate the temporary login token for a user.
		 *
		 * Generates a new token and invalidates the old one. The new login
		 * link must be retrieved via the admin UI or re-sent via email.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return bool True when a new token was stored.
		 *
		 * @since 2.4.1
		 */
		public static function rotate_temporary_token( $user_id ) {
			if ( ! self::store_token( $user_id, self::generate_mls_temporary_token( $user_id ) ) ) {
				// The existing token stays in place rather than being replaced by
				// one that could not be encrypted.
				return false;
			}

			\update_user_meta( $user_id, 'mls_temp_token_created_at', time() );

			return true;
		}

		/**
		 * Get current GMT date time
		 *
		 * @return false|int
		 * @since 2.1.0
		 */
		public static function get_current_gmt_timestamp() {
			return strtotime( gmdate( 'Y-m-d H:i:s', time() ) );
		}

		/**
		 * Check if current IP is rate limited for token attempts.
		 *
		 * @return bool
		 *
		 * @since 2.3.0
		 */
		private static function is_token_rate_limited() {
			$attempts = get_transient( self::get_rate_limit_key() );

			return ( ! empty( $attempts ) && (int) $attempts >= self::MAX_TOKEN_ATTEMPTS );
		}

		/**
		 * Record a failed token lookup attempt for rate limiting.
		 *
		 * Counted per source address only. There used to be a second counter
		 * keyed on the token being tried, which could not do anything useful: a
		 * lookup fails only when the token does not exist, so that counter
		 * recorded attempts against tokens that were never real, and blocking
		 * further attempts at one wrong guess stops nobody. What it did do was
		 * let the caller choose the transient name — one new row in the options
		 * table per distinct string tried — so the guessing that the limit exists
		 * to stop also filled the database.
		 *
		 * @return void
		 *
		 * @since 2.3.0
		 */
		private static function record_failed_token_attempt() {
			// Incremented in the store, so parallel guesses each cost an
			// attempt. See OptionsHelper::increment_counter().
			\MLS\Helpers\OptionsHelper::increment_counter( self::get_rate_limit_key(), self::TOKEN_ATTEMPT_WINDOW );
		}

		/**
		 * Build the rate-limiting transient key for this request's source.
		 *
		 * One key per source address, so an attacker cannot choose how many
		 * counters exist.
		 *
		 * @return string Transient key (max 45 chars for WP compat).
		 *
		 * @since 2.4.1
		 */
		private static function get_rate_limit_key() {
			// The source address, via the shared resolver.
			//
			// The User-Agent used to be part of this key. It is supplied by the
			// caller, so every distinct value opened a fresh counter and a
			// single host could exhaust neither the per-token limit nor the
			// site-wide one simply by varying a header on each request — the
			// exact bypass the limit exists to prevent. Nothing the client
			// controls can be part of a rate-limit key; get_client_ip() reads
			// only REMOTE_ADDR unless the *site* has opted into a trusted-proxy
			// resolver of its own.
			$ip = \MLS\Login_Page_Control::get_client_ip();
			$ip = '' !== $ip ? $ip : 'unknown';

			// Keyed hash so the transient name cannot be predicted; truncated to
			// fit the transient name limit.
			$hash = substr( hash_hmac( 'sha256', $ip, wp_salt() ), 0, 20 );

			return 'mls_tkn_att_' . $hash;
		}

		/**
		 * Serialize use of a temporary bearer token for one user.
		 *
		 * add_option() is an atomic insert, unlike separate user-meta reads and
		 * writes. A short stale-lock timeout prevents a terminated PHP request
		 * from making the temporary account unusable indefinitely.
		 *
		 * @param int $user_id Temporary user ID.
		 * @return bool
		 */
		private static function acquire_login_lock( $user_id ) {
			$option_name = self::LOGIN_LOCK_PREFIX . absint( $user_id );
			$now         = time();
			$added       = is_multisite() ? add_site_option( $option_name, $now ) : add_option( $option_name, $now, '', false );

			if ( $added ) {
				return true;
			}

			$created_at = absint( is_multisite() ? get_site_option( $option_name, 0 ) : get_option( $option_name, 0 ) );
			if ( $created_at && ( $now - $created_at ) > 30 ) {
				if ( is_multisite() ) {
					delete_site_option( $option_name );
					return add_site_option( $option_name, $now );
				}

				delete_option( $option_name );
				return add_option( $option_name, $now, '', false );
			}

			return false;
		}

		/** Release a temporary bearer-token lock. */
		private static function release_login_lock( $user_id ) {
			$option_name = self::LOGIN_LOCK_PREFIX . absint( $user_id );
			if ( is_multisite() ) {
				delete_site_option( $option_name );
				return;
			}

			delete_option( $option_name );
		}

		/**
		 * Generate new password for user
		 *
		 * @param int   $length - PW length.
		 * @param bool  $special_chars - Special chars.
		 * @param false $extra_special_chars - Additonal chars.
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public static function generate_password( $length = 15, $special_chars = true, $extra_special_chars = false ) {
			$length = absint( $length );
			$chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

			if ( $special_chars ) {
				$chars .= '!@#$%^&*()';
			}

			if ( $extra_special_chars ) {
				$chars .= '-_ []{}<>~`+=,.;:/?|';
			}

			$password = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$password .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
			}

			return $password;
		}

		/**
		 * Create a random username for the temporary user
		 *
		 * @param array $data - User data.
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public static function create_username( $data ) {
			$first_name = isset( $data['user_first_name'] ) ? $data['user_first_name'] : '';
			$last_name  = isset( $data['user_last_name'] ) ? $data['user_last_name'] : '';
			$email      = isset( $data['user_email'] ) ? $data['user_email'] : '';

			$name = '';
			if ( ! empty( $first_name ) || ! empty( $last_name ) ) {
				$name = str_replace( array( '.', '+' ), '', strtolower( trim( $first_name . $last_name ) ) );
			} elseif ( ! empty( $email ) ) {
				$explode = explode( '@', $email );
				$name    = str_replace( array( '.', '+' ), '', $explode[0] );
			}

			if ( username_exists( $name ) ) {
				$name = $name . substr( uniqid( '', true ), - 6 );
			}

			$username = sanitize_user( $name, true );

			if ( empty( $username ) ) {
				$username = self::random_username();
			}

			return sanitize_user( $username, true );
		}

		/**
		 * Generate username
		 *
		 * @param int $length - Length.
		 *
		 * @return string
		 *
		 * @since @since 2.1.0
		 */
		public static function random_username( $length = 10 ) {
			$characters      = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$last_index      = strlen( $characters ) - 1;
			$random_username = '';

			for ( $i = 0; $i < $length; $i++ ) {
				// wp_rand() rather than rand(): predictable temporary-account
				// names help an attacker find them. The upper bound is the last
				// index, not the length — rand( 0, strlen() ) could return one
				// past the end, which yields an empty string and a warning.
				$random_username .= $characters[ wp_rand( 0, $last_index ) ];
			}

			return sanitize_user( strtolower( $random_username ), true );
		}

		/**
		 * Get user expiry.
		 *
		 * @param string $expiry_option - Expire settng for user..
		 * @param string $date - Custom date.
		 * @param string $time - Custom time..
		 *
		 * @return array - Results
		 *
		 * @since 2.1.0
		 */
		public static function get_user_expire_time( $expiry_option = 'expire_from_now', $date = '', $time = '' ) {
			if ( 'custom_expiry' === $expiry_option ) {
				$current_offset = get_option( 'gmt_offset' );
				$tzstring       = get_option( 'timezone_string' );

				// Remove old Etc mappings. Fallback to gmt_offset.
				if ( \strpos( $tzstring, 'Etc/GMT' ) !== false ) {
					$tzstring = '';
				}

				if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists.
					if ( 0 === $current_offset ) {
						$tzstring = 'UTC+0';
					} elseif ( $current_offset < 0 ) {
						$tzstring = 'UTC' . $current_offset;
					} else {
						$tzstring = 'UTC+' . $current_offset;
					}
				}

				$time_string = str_replace( '/', '-', $date . ' ' . $time );
				return strtotime( $time_string . ' ' . $tzstring );
			} elseif ( 'expire_from_first_use' === $expiry_option ) {
				return $expiry_option;
			} else {
				$current_timestamp = self::get_current_gmt_timestamp();
				return strtotime( '+' . $date );
			}
		}

		/**
		 * Get list of logins created by us.
		 *
		 * @return array - Results
		 *
		 * @since 2.1.0
		 */
		public static function get_temporary_logins() {

			global $wpdb;

			$sql = '
				SELECT  ID, display_name
				FROM        ' . $wpdb->users . ' INNER JOIN ' . $wpdb->usermeta . '
				ON          ' . $wpdb->users . '.ID   =       ' . $wpdb->usermeta . '.user_id
				AND     (
			';

			$sql     .= ' ' . $wpdb->usermeta . '.meta_key = %s ';
			$sql     .= ' ) ';
			$sql     .= ' ORDER BY ID ';

			// Table names are trusted $wpdb properties and cannot be placeholders;
			// the meta key is the only value, and it is prepared.
			$user_ids = $wpdb->get_col( $wpdb->prepare( $sql, self::TEMP_USER_META_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			return $user_ids;
		}

		/**
		 * Get temporary login url
		 *
		 * @param int $user_id - ID for user.
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public static function get_login_url( $user_id ) {
			if ( empty( $user_id ) ) {
				return '';
			}

			$is_valid_temporary_login = self::is_valid_temporary_login( $user_id, false );
			if ( ! $is_valid_temporary_login ) {
				return '';
			}

			// Decrypted on demand so the admin can still copy the link or send
			// it again — the stored form is ciphertext.
			$mls_temp_user_token = self::get_raw_token( $user_id );
			if ( empty( $mls_temp_user_token ) ) {
				return '';
			}

			$login_url = add_query_arg( 'mls_temp_user_token', $mls_temp_user_token, trailingslashit( admin_url() ) );

			return apply_filters( 'mls_temporary_login_link', $login_url, $user_id );
		}

		/**
		 * Checks whether user is valid temporary user
		 *
		 * @param int  $user_id - ID to check.
		 * @param bool $check_expiry - Check expiry or not.
		 *
		 * @return bool
		 *
		 * @since 2.1.0
		 */
		public static function is_valid_temporary_login( $user_id = 0, $check_expiry = true ) {

			if ( empty( $user_id ) ) {
				return false;
			}

			$check = get_user_meta( $user_id, self::TEMP_USER_META_KEY, true );

			if ( ! empty( $check ) && $check_expiry ) {
				$check = ! ( self::is_login_expired( $user_id ) );
			}

			return ! empty( $check ) ? true : false;
		}

		/**
		 * Checks if given user is valid temporary one or not.
		 *
		 * @param \WP_User|\stdClass $user - The user to check. Could be either WP User object, or class with valid ID property.
		 *
		 * @return boolean
		 *
		 * @since 2.1.2
		 */
		public static function is_valid_temp_user( $user ): bool {
			if ( ! is_object( $user ) || empty( $user->ID ) ) {
				return false;
			}

			$check = \get_user_meta( $user->ID, self::TEMP_USER_META_KEY, true );

			if ( $check ) {
				return true;
			}

			return false;
		}

		/**
		 * Get valid temporary user based on token
		 *
		 * @param string $token - Token to lookup.
		 *
		 * @return \WP_User|\WP_Error|bool
		 *
		 * @since 2.1.0
		 */
		public static function get_valid_user_based_on_token( $token = '' ) {
			if ( empty( $token ) ) {
				return false;
			}

			// Look up by the keyed hash. The token itself is stored encrypted
			// with a randomised nonce, so the ciphertext is different every
			// time and cannot be matched on.
			$users = \get_users(
				array(
					'meta_key'   => self::TOKEN_LOOKUP_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => self::token_lookup_hash( $token ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'number'     => 1,
				)
			);

			if ( ! empty( $users ) ) {
				return $users[0];
			}

			// Links issued before 2.4.0 stored the token in clear text and have
			// no lookup hash. Honour them once, and convert the account as we
			// go so the clear text does not survive the visit.
			//
			// The stored ciphertext is explicitly not accepted here. Without
			// this guard the legacy equality lookup matches the stored value
			// itself, which would make a database dump directly usable as a
			// login link — the very thing the encryption is for.
			if ( 0 === strpos( (string) $token, self::TOKEN_CIPHER_PREFIX ) ) {
				return false;
			}

			$legacy = \get_users(
				array(
					'meta_key'   => self::TOKEN_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $token, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'number'     => 1,
				)
			);

			if ( empty( $legacy ) ) {
				return false;
			}

			self::store_token( $legacy[0]->ID, $token );

			return $legacy[0];
		}

		/**
		 * Initialize Temporary Login
		 *
		 * Hooked to init action to initialize temporary logins
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function manage_temporary_logins() {
			if ( ! empty( $_GET['mls_temp_user_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$mls_temp_user_token = \sanitize_key( $_GET['mls_temp_user_token'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				// Rate limit token attempts to prevent brute force.
				if ( self::is_token_rate_limited() ) {
					\wp_die(
						\esc_html__( 'Too many login attempts. Please try again later.', 'melapress-login-security' ),
						\esc_html__( 'Rate Limited', 'melapress-login-security' ),
						array( 'response' => 429 )
					);
				}

				$user = self::get_valid_user_based_on_token( $mls_temp_user_token );

				/*
				 * There used to be a `usleep( wp_rand( 50000, 150000 ) )` here,
				 * described as a constant-time delay. A random delay is not
				 * constant time and does not remove a timing signal — averaging
				 * repeated samples recovers it. What it did do was hold a PHP
				 * worker for up to 150ms per request, so anyone could tie up the
				 * pool with unauthenticated requests far more cheaply than they
				 * could measure the timing difference it was meant to hide. The
				 * rate limit above is the defence that works; the token is 128
				 * hex characters, so there is nothing here worth guessing at.
				 */

				// Track failed token attempts.
				if ( empty( $user ) || \is_wp_error( $user ) ) {
					self::record_failed_token_attempt();
				}

				$temporary_user = '';
				if ( ! empty( $user ) && ! \is_wp_error( $user ) ) {
					$temporary_user = $user;
				}

				if ( ! empty( $temporary_user ) ) {
					$temporary_user_id = $temporary_user->ID;
					if ( ! self::acquire_login_lock( $temporary_user_id ) ) {
						\wp_die(
							\esc_html__( 'This temporary login is already being used. Please try again.', 'melapress-login-security' ),
							\esc_html__( 'Login In Progress', 'melapress-login-security' ),
							array( 'response' => 409 )
						);
					}
					$do_login          = true;
					$do_login          = apply_filters( 'mls_temporary_login_pre_check', $do_login, $temporary_user_id );

					if ( \is_user_logged_in() ) {
						$current_user_id = \get_current_user_id();
						if ( $temporary_user_id !== $current_user_id ) {
							\wp_logout();
						} else {
							$do_login = false;
						}
					}

					if ( $do_login ) {
						self::integrate_wp2fa();

						// user_login, not login. WP_User has no `login` property and it is
						// not in $back_compat_keys, so __get() fell through to
						// get_user_meta( $ID, 'login' ) and returned an empty string,
						// which was then handed to the wp_login action — so every
						// temporary-login event reached audit logging with no username.
						$temporary_user_login = $temporary_user->user_login;

						if ( self::is_login_expired( $temporary_user_id ) ) {
							self::record_failed_token_attempt( $mls_temp_user_token );
							self::release_login_lock( $temporary_user_id );
							\wp_safe_redirect( home_url() );
							exit();
						}

						// Check absolute token lifetime (90 days max regardless of user expiry).
						$token_created = \get_user_meta( $temporary_user_id, 'mls_temp_token_created_at', true );
						$max_token_lifetime = 90 * DAY_IN_SECONDS;
						if ( ! empty( $token_created ) && ( time() - absint( $token_created ) ) > $max_token_lifetime ) {
							// Token has exceeded maximum lifetime — regenerate and redirect.
							self::rotate_temporary_token( $temporary_user_id );
							self::release_login_lock( $temporary_user_id );
							\wp_safe_redirect( \home_url() );
							exit();
						}

						// Consume the bearer URL before issuing a session cookie.
						self::rotate_temporary_token( $temporary_user_id );
						\update_user_meta( $temporary_user_id, 'mls_last_login', self::get_current_gmt_timestamp() );
						\wp_set_current_user( $temporary_user_id, $temporary_user_login );
						\wp_set_auth_cookie( $temporary_user_id );

						$login_count_key = 'mls_login_count';
						$login_count     = \get_user_meta( $temporary_user_id, $login_count_key, true );

						if ( ! empty( $login_count ) ) {
							++$login_count;
						} else {
							$login_count = 1;
						}

						\update_user_meta( $temporary_user_id, $login_count_key, $login_count );

						\do_action( 'wp_login', $temporary_user_login, $temporary_user );
						\do_action( 'mls_after_login_success', $temporary_user_id );
					}

					$request_uri     = self::get_request_uri();
					$redirect_to_url = \apply_filters( 'mls_login_redirect', apply_filters( 'login_redirect', network_site_url( remove_query_arg( 'mls_temp_user_token', $request_uri ) ), false, $temporary_user ), $temporary_user );

					self::release_login_lock( $temporary_user_id );
				} else {
					// User not found.
					$redirect_to_url = \home_url();
				}

				\wp_safe_redirect( $redirect_to_url );
				exit();
			}

			// Ensure expired users are blocked, or any remaining temporary users cant access specific pages.
			if ( \is_user_logged_in() ) {
				$user_id = \get_current_user_id();

				if ( ! empty( $user_id ) && self::is_valid_temporary_login( $user_id, false ) ) {
					if ( self::is_login_expired( $user_id ) ) {
						\wp_logout();
						\wp_safe_redirect( \home_url() );
						exit();
					} else {
						global $pagenow;
						$blocked_pages = self::get_blocked_pages();
						$page          = ! empty( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

						if ( ( ! empty( $page ) && in_array( $page, $blocked_pages, true ) ) || ( ! empty( $pagenow ) && ( in_array( $pagenow, $blocked_pages, true ) ) ) || ( ! empty( $pagenow ) && ( 'users.php' === $pagenow && isset( $_GET['action'] ) && ( 'deleteuser' === $_GET['action'] || 'delete' === $_GET['action'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							wp_die( esc_attr__( "You don't have permission to access this page", 'melapress-login-security' ) );
						}
					}
				}
			}
		}

		/**
		 * Get all paged temporary users cannot access.
		 *
		 * @return array - Pages
		 *
		 * @since 2.1.0
		 */
		public static function get_blocked_pages() {
			$blocked_pages = array( 'user-new.php', 'user-edit.php', 'profile.php' );
			$blocked_pages = apply_filters( 'mls_restricted_pages_for_temporary_users', $blocked_pages );

			return $blocked_pages;
		}

		/**
		 * Check if current login is expired or over the limit for this user.
		 *
		 * @param int $user_id - User logging in.
		 *
		 * @return bool - Result.
		 *
		 * @since 2.1.0
		 */
		public static function is_login_expired( $user_id = 0 ) {
			if ( empty( $user_id ) ) {
				$user_id = get_current_user_id();
			}

			if ( empty( $user_id ) ) {
				return false;
			}

			$expire          = get_user_meta( $user_id, 'mls_temp_user_expires_on', true );
			$expire_date     = get_user_meta( $user_id, 'mls_temp_user_expires_on_date', true );
			$already_expired = get_user_meta( $user_id, 'mls_temp_user_expired', true );
			$login_count     = get_user_meta( $user_id, 'mls_login_count', true );
			$login_limit     = get_user_meta( $user_id, 'mls_temp_user_max_login_limit', true );

			if ( ! empty( get_user_meta( $user_id, 'mls_temp_user_expired', true ) ) ) {
				return true;
			}

			if ( $login_limit && $login_count >= $login_limit ) {
				update_user_meta( $user_id, 'mls_temp_user_expired', self::get_current_gmt_timestamp() );
				return true;
			}

			// User is logging in so update expiry based on first login.
			if ( ! is_numeric( $expire ) ) {
				update_user_meta( $user_id, 'mls_temp_user_expires_on', strtotime( $expire_date ) );
				$expire = strtotime( $expire_date );
			}

			if ( ! empty( $expire ) && is_numeric( $expire ) && self::get_current_gmt_timestamp() >= floatval( $expire ) ) {
				update_user_meta( $user_id, 'mls_temp_user_expired', self::get_current_gmt_timestamp() );
				return true;
			}

			return false;
		}

		/**
		 * Get the Request URI
		 *
		 * @return mixed|string|string[] - Result.
		 *
		 * @since 2.1.0
		 */
		public static function get_request_uri() {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore

			if ( ! is_multisite() ) {
				$component = wp_parse_url( get_site_url(), PHP_URL_PATH );
				if ( ! empty( $component ) ) {
					$component   = trim( $component );
					$component  .= '/';
					$request_uri = str_replace( $component, '', $request_uri );
				}
			}

			return $request_uri;
		}

		/**
		 * Send login link.
		 *
		 * @param string $email - Email to send to.
		 * @param int    $user_id - User ID.
		 *
		 * @return $status - Result.
		 *
		 * @since 2.1.0
		 */
		public static function send_login_link( $email = false, $user_id = false ) {
			$post_array = filter_input_array( INPUT_POST );

			if ( ! current_user_can( 'manage_options' ) || ! isset( $post_array['nonce'] ) ) {
				return;
			}

			$data        = array();
			$mls         = melapress_login_security();
			$is_internal = ( isset( $post_array['form_data'] ) && $email ) ? true : false;

			// Check if is internal request.
			if ( ! $is_internal && ! wp_verify_nonce( $post_array['nonce'], MLS_PREFIX . '-email-login' ) ) {
				$return = array(
					'message' => esc_html__( 'Nonce failure.', 'melapress-login-security' ),
				);
				wp_send_json_success( $return );
				return;
			}

			// Populating data for email.
			$to            = ( $email ) ? $email : $post_array['email'];
			$subject       = esc_html__( '{site_name} - Your tempoary login link', 'melapress-login-security' );
			$email_user_id = ( $user_id ) ? $user_id : $post_array['user_id'];
			$link          = ( isset( $post_array['link'] ) ) ? $post_array['link'] : self::get_login_url( $user_id );

			$subject = \MLS\EmailAndMessageStrings::replace_email_strings( $subject, $email_user_id );
			$message = \MLS\EmailAndMessageStrings::get_email_template_setting( 'temporary_login_created_email_body' );
			$args    = array( 'temporary_login_link' => '<a href="' . esc_url( $link ) . '">' . esc_html__( 'by clicking here', 'melapress-login-security' ) . '</a>' );
			$message = \MLS\EmailAndMessageStrings::replace_email_strings( $message, $email_user_id, $args );

			$from_email = $mls->options->mls_setting->from_email ? $mls->options->mls_setting->from_email : Emailer::get_default_email_address();

			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;
			$headers[]  = 'Content-Type: text/html; charset=UTF-8';

			$status = Emailer::send_email( $to, $subject, $message, $headers );

			if ( ! $email ) {
				$return = array(
					'message'    => esc_html__( 'Email sent.', 'melapress-login-security' ),
					'event_data' => $status,
				);

				wp_send_json_success( $return );
			}

			return $status;
		}

		/**
		 * Delete user.
		 *
		 * @param int $user_id - ID to remove.
		 *
		 * @return bool - Result.
		 *
		 * @since 2.1.0
		 */
		public static function delete_user( $user_id ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Network super administrators are never managed by the temporary-login
			// workflow, including legacy temporary accounts created by older builds.
			if ( is_multisite() && is_super_admin( $user_id ) ) {
				return false;
			}

			$delete_user = \wp_delete_user( $user_id, get_current_user_id() );

			// Handle networks.
			if ( is_multisite() ) {
				$delete_user = wpmu_delete_user( $user_id );
			}

			return $delete_user;
		}

		/**
		 * Filter Redirect URL
		 *
		 * @param string  $redirect_to_url - Current redirect.
		 * @param WP_User $temporary_user - User.
		 *
		 * @return mixed|string|void|WP_Error
		 *
		 * @since 2.1.0
		 */
		public static function redirect_after_login( $redirect_to_url, $temporary_user ) {
			$redirect_to_key = 'mls_temp_user_redirect_to';
			$redirect_to     = get_user_meta( $temporary_user->ID, $redirect_to_key, true );

			if ( isset( $_REQUEST['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return $_REQUEST['redirect_to']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
			} elseif ( empty( $redirect_to ) ) {
				return $redirect_to_url;
			} elseif ( 'wp_dashboard' === $redirect_to ) {
				return admin_url();
			} elseif ( 'home_page' === $redirect_to ) {
				return home_url();
			} elseif ( 'system_default' === $redirect_to ) {
				return $redirect_to_url;
			} else {
				return $redirect_to_url;
			}
		}
	}
}
