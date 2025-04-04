<?php
/**
 * MLS Temporary logins class.
 *
 * @package MelapressLoginSecurity
 * @since 2.1.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;
use MLS\Emailer;

/**
 * Check if this class already exists.
 *
 * @since 2.1.0
 */
if ( ! class_exists( '\MLS\TemporaryLogins' ) ) {

	/**
	 * Declare SessionsManager Class
	 *
	 * @since 2.1.0
	 */
	class TemporaryLogins {

		/**
		 * Init hooks.
		 *
		 * @since 2.1.0
		 */
		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
			add_action( 'wp_ajax_mls_create_login_link', array( __CLASS__, 'create_login_link' ) );
			add_action( 'wp_ajax_mls_send_login_link', array( __CLASS__, 'send_login_link' ) );
			add_action( 'admin_init', array( __CLASS__, 'monitor_admin_actions' ) );
			add_action( 'admin_menu', array( __CLASS__, 'replace_admin_link' ), 11 );
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
		 * Monitor for form submimssions.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function monitor_admin_actions() {
			if ( ! current_user_can( 'manage_options' ) || ! isset( $_REQUEST['action'] ) || empty( $_REQUEST['action'] ) || empty( $_REQUEST['user_id'] ) || ! isset( $_REQUEST['nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$user_id  = sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$base_url = menu_page_url( 'mls-temporary-logins', false );

			if ( 'delete_link' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), MLS_PREFIX . '-delete-login-link' ) ) {
					self::delete_user( $user_id );
					add_action( 'admin_notices', array( __CLASS__, 'user_deleted_notice' ) );

					wp_redirect( $base_url, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit();
				}
			} elseif ( 'disable_link' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), MLS_PREFIX . '-disable-login-link' ) ) {
					update_user_meta( $user_id, 'mls_temp_user_expired', self::get_current_gmt_timestamp() );
					add_action( 'admin_notices', array( __CLASS__, 'user_disabled_notice' ) );

					wp_redirect( $base_url, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit();
				}
			} elseif ( 'enable_link' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), MLS_PREFIX . '-disable-login-link' ) ) {
					delete_user_meta( $user_id, 'mls_temp_user_expired' );
					add_action( 'admin_notices', array( __CLASS__, 'user_reenabled_notice' ) );

					wp_redirect( $base_url, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit();
				}
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
			add_action( "load-$hook_name", array( '\MLS\Admin\Admin', 'admin_enqueue_scripts' ) );
		}

		/**
		 * Our admin page.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function admin_area() {
			if ( ! current_user_can( 'manage_options' ) ) {
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

			$languages                = get_available_languages();
			$can_install_translations = current_user_can( 'install_languages' ) && \wp_can_install_language_pack();

			$form_values = array(
				'user_email'                    => '',
				'user_first_name'               => '',
				'user_last_name'                => '',
				'user-role'                     => '',
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
				$user = get_user_by( 'ID', sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$form_values = array(
					'user_id'                       => $user->ID,
					'user_email'                    => $user->user_email,
					'user_first_name'               => $user->first_name,
					'user_last_name'                => $user->last_name,
					'user-role'                     => implode( ',', $user->roles ),
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
							$roles_table = new \MLS\TemporaryLogins\Temporary_Logins_Table();
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

			foreach ( $post_array['form_data'] as $index => $posted_setting ) {
				$data[ $posted_setting['name'] ] = $posted_setting['value'];
			}

			$nonce         = isset( $data['mls-create-login-nonce'] ) ? $data['mls-create-login-nonce'] : false;
			$edit_nonce    = isset( $data['mls-edit-login-nonce'] ) ? $data['mls-edit-login-nonce'] : false;
			$is_valid_edit = false;

			// Check nonce.
			if ( ! isset( $data['user_id'] ) || ! current_user_can( 'manage_options' ) || ! $nonce || ! wp_verify_nonce( $nonce, MLS_PREFIX . '-create-login' ) ) {
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
		 * Create a new user
		 *
		 * @param array $data - New user data.
		 *
		 * @return array|int|WP_Error
		 */
		public static function create_new_user( $data ) {

			$nonce  = isset( $data['mls-create-login-nonce'] ) ? $data['mls-create-login-nonce'] : false;

			// Check nonce.
			if ( ! current_user_can( 'manage_options' ) || ! $nonce || ! wp_verify_nonce( $nonce, MLS_PREFIX . '-create-login' ) ) {
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

			$max_login_limit = ! empty( $data['max_logins'] ) ? $data['max_logins'] : 5;

			$send_email = ! empty( $data['send_email'] ) ? $data['send_email'] : false;

			$password    = self::generate_password();
			$username    = self::create_username( $data );
			$first_name  = isset( $data['user_first_name'] ) ? sanitize_text_field( $data['user_first_name'] ) : '';
			$last_name   = isset( $data['user_last_name'] ) ? sanitize_text_field( $data['user_last_name'] ) : '';
			$email       = isset( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : '';
			$role        = ! empty( $data['role'] ) ? $data['role'] : 'subscriber';
			$redirect_to = ! empty( $data['redirect_to'] ) ? sanitize_text_field( $data['redirect_to'] ) : 'wp_dashboard';

			$user_args = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => sanitize_email( $email ),
				'role'       => $role,
			);

			$user_id = wp_insert_user( $user_args );

			if ( is_wp_error( $user_id ) ) {
				$code = $user_id->get_error_code();

				$result['errcode'] = $code;
				$result['message'] = $user_id->get_error_message( $code );

			} else {

				if ( is_multisite() && ! empty( $data['super_admin'] ) && 'on' === $data['super_admin'] ) {
					grant_super_admin( $user_id );
					$sites = get_sites( array( 'deleted' => '0' ) );

					if ( ! empty( $sites ) && count( $sites ) > 0 ) {
						foreach ( $sites as $site ) {
							if ( ! is_user_member_of_blog( $user_id, $site->blog_id ) ) {
								add_user_to_blog( $site->blog_id, $user_id, 'administrator' );
							}
						}
					}
				}

				$locale = ! empty( $data['locale'] ) ? $data['locale'] : 'en_US';

				self::check_and_install_language( $locale );

				update_user_meta( $user_id, 'mls_temp_user', true );
				update_user_meta( $user_id, 'mls_temp_user_created_on', self::get_current_gmt_timestamp() );
				update_user_meta( $user_id, 'mls_temp_user_expires_on', self::get_user_expire_time( $expiry_option, $date, $time ) );
				update_user_meta( $user_id, 'mls_temp_user_expires_on_date', $date );
				update_user_meta( $user_id, 'mls_temp_user_max_login_limit', $max_login_limit );
				update_user_meta( $user_id, 'mls_temp_user_token', self::generate_mls_temporary_token( $user_id ) );
				update_user_meta( $user_id, 'mls_temp_user_redirect_to', $redirect_to );
				update_user_meta( $user_id, 'show_welcome_panel', 0 );
				update_user_meta( $user_id, 'locale', $locale );

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
			if ( ! current_user_can( 'manage_options' ) || ! $nonce || ! wp_verify_nonce( $nonce, MLS_PREFIX . '-edit-login' ) || ( 0 === $user_id ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Nonce check failed.', 'melapress-login-security' ) ) );
			}

			$expiry_option   = ! empty( $data['login_expire'] ) ? $data['login_expire'] : 'expire_from_now';
			$max_login_limit = ! empty( $data['max_logins'] ) ? $data['max_logins'] : 5;
			$send_email      = ! empty( $data['send_email'] ) ? $data['send_email'] : false;
			$first_name      = isset( $data['user_first_name'] ) ? sanitize_text_field( $data['user_first_name'] ) : '';
			$last_name       = isset( $data['user_last_name'] ) ? sanitize_text_field( $data['user_last_name'] ) : '';
			$email           = isset( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : '';
			$role            = ! empty( $data['role'] ) ? $data['role'] : 'subscriber';
			$redirect_to     = ! empty( $data['redirect_to'] ) ? sanitize_text_field( $data['redirect_to'] ) : 'wp_dashboard';
			$login_count     = ! empty( $data['login_count'] ) ? sanitize_text_field( $data['login_count'] ) : 0;

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

			if ( is_multisite() && ! empty( $data['super_admin'] ) && 'on' === $data['super_admin'] ) {
				grant_super_admin( $user_id );
			}

			$locale = ! empty( $data['locale'] ) ? $data['locale'] : 'en_US';

			self::check_and_install_language( $locale );

			update_user_meta( $user_id, 'mls_temp_user_updated', self::get_current_gmt_timestamp() );
			update_user_meta( $user_id, 'mls_temp_user_expires_on', self::get_user_expire_time( $expiry_option, $date, $time ) );
			update_user_meta( $user_id, 'mls_temp_user_expires_on_date', $date );
			update_user_meta( $user_id, 'mls_temp_user_max_login_limit', $max_login_limit );
			update_user_meta( $user_id, 'mls_temp_user_redirect_to', $redirect_to );
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
		public static function generate_mls_temporary_token( $user_id ) {
			$byte_length = 64;

			if ( function_exists( 'random_bytes' ) ) {
				try {
					return bin2hex( random_bytes( $byte_length ) ); // phpcs:ignore
				} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				}
			}

			if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
				$crypto_strong = false;

				$bytes = openssl_random_pseudo_bytes( $byte_length, $crypto_strong );
				if ( true === $crypto_strong ) {
					return bin2hex( $bytes );
				}
			}

			$str  = $user_id . microtime() . uniqid( '', true );
			$salt = substr( md5( $str ), 0, 32 );

			return hash( 'sha256', $str . $salt );
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
		 * Create a ranadom username for the temporary user
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
			$random_username = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$random_username .= $characters[ rand( 0, strlen( $characters ) ) ]; // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
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
			$meta_key = 'mls_temp_user';

			global $wpdb;

			$sql = '
				SELECT  ID, display_name
				FROM        ' . $wpdb->users . ' INNER JOIN ' . $wpdb->usermeta . '
				ON          ' . $wpdb->users . '.ID   =       ' . $wpdb->usermeta . '.user_id
				AND     (
			';

			$sql     .= ' ' . $wpdb->usermeta . '.meta_key    =   \'' . $meta_key . '\' ';
			$sql     .= ' ) ';
			$sql     .= ' ORDER BY ID ';
			$user_ids = $wpdb->get_col( $sql ); // phpcs:ignore

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

			$mls_temp_user_token = get_user_meta( $user_id, 'mls_temp_user_token', true );
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

			$check = get_user_meta( $user_id, 'mls_temp_user', true );

			if ( ! empty( $check ) && $check_expiry ) {
				$check = ! ( self::is_login_expired( $user_id ) );
			}

			return ! empty( $check ) ? true : false;
		}

		/**
		 * Get valid temporary user based on token
		 *
		 * @param string $token - Token to lookip.
		 *
		 * @return array|bool
		 *
		 * @since 2.1.0
		 */
		public static function get_valid_user_based_on_token( $token = '' ) {
			if ( empty( $token ) ) {
				return false;
			}

			global $wpdb;
			$meta_key = 'mls_temp_user_expires_on';

			$sql = '
				SELECT  ID, display_name
				FROM        ' . $wpdb->users . ' INNER JOIN ' . $wpdb->usermeta . '
				ON          ' . $wpdb->users . '.ID             =       ' . $wpdb->usermeta . '.user_id
				AND     (
			';

			$sql       .= ' ' . $wpdb->usermeta . '.meta_value =   "' . $token . '" ';
			$sql       .= ' ) ';
			$sql       .= ' ORDER BY ID ';
			$users_data = $wpdb->get_col( $sql ); // phpcs:ignore

			if ( empty( $users_data ) ) {
				return false;
			}

			return $users_data;
		}

		/**
		 * Initialize Temporary Login
		 *
		 * Hooked to init action to initilize tlwp
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function manage_temporary_logins() {
			if ( ! empty( $_GET['mls_temp_user_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$mls_temp_user_token = sanitize_key( $_GET['mls_temp_user_token'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$users               = self::get_valid_user_based_on_token( $mls_temp_user_token );

				$temporary_user = '';
				if ( ! empty( $users ) ) {
					$temporary_user = get_user_by( 'ID', $users[0] );
				}

				if ( ! empty( $temporary_user ) ) {
					$temporary_user_id = $temporary_user->ID;
					$do_login          = true;
					$do_login          = apply_filters( 'mls_temporary_login_pre_check', $do_login, $temporary_user_id );

					if ( is_user_logged_in() ) {
						$current_user_id = get_current_user_id();
						if ( $temporary_user_id !== $current_user_id ) {
							wp_logout();
						} else {
							$do_login = false;
						}
					}

					if ( $do_login ) {
						$temporary_user_login = $temporary_user->login;

						if ( self::is_login_expired( $temporary_user_id ) ) {
							wp_safe_redirect( home_url() );
							exit();
						}

						update_user_meta( $temporary_user_id, 'mls_last_login', self::get_current_gmt_timestamp() );
						wp_set_current_user( $temporary_user_id, $temporary_user_login );
						wp_set_auth_cookie( $temporary_user_id );

						$login_count_key = 'mls_login_count';
						$login_count     = get_user_meta( $temporary_user_id, $login_count_key, true );

						if ( ! empty( $login_count ) ) {
							++$login_count;
						} else {
							$login_count = 1;
						}

						update_user_meta( $temporary_user_id, $login_count_key, $login_count );
						do_action( 'wp_login', $temporary_user_login, $temporary_user );
						do_action( 'mls_after_login_success', $temporary_user_id );
					}

					$request_uri     = self::get_request_uri();
					$redirect_to_url = apply_filters( 'mls_login_redirect', apply_filters( 'login_redirect', network_site_url( remove_query_arg( 'mls_temp_user_token', $request_uri ) ), false, $temporary_user ), $temporary_user );

				} else {
					// User not found.
					$redirect_to_url = home_url();
				}

				wp_safe_redirect( $redirect_to_url );
				exit();
			}

			// Ensure expired users are blocked, or any remaining temporary users cant access specific pages.
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();

				if ( ! empty( $user_id ) && self::is_valid_temporary_login( $user_id, false ) ) {
					if ( self::is_login_expired( $user_id ) ) {
						wp_logout();
						wp_safe_redirect( home_url() );
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

			if ( $login_count > $login_limit ) {
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

			$from_email = $mls->options->mls_setting->from_email ? $mls->$options->mls_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );

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

			$delete_user = wp_delete_user( $user_id, get_current_user_id() );

			// Handle networks.
			if ( is_multisite() ) {
				if ( is_super_admin( $user_id ) ) {
					revoke_super_admin( $user_id );
				}

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
