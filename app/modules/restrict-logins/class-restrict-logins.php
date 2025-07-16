<?php
/**
 * Melapress Login SecurityEmail Settings
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

if ( ! class_exists( '\MLS\RestrictLogins' ) ) {

	/**
	 * Handles login restrictions.
	 *
	 * @since 2.0.0
	 */
	class RestrictLogins {

		/**
		 * Add settings markup
		 *
		 * @param  string $markup - Current HTML.
		 * @param  object $settings_tab - Current settings.
		 *
		 * @return string $markup - Appended markup.
		 *
		 * @since 2.0.0
		 */
		public static function settings_markup( $markup, $settings_tab ) {
			$wp_kses_args = OptionsHelper::get_allowed_kses_args();
			ob_start(); ?>
		   
			<tr valign="top" class="timed-logins-tr">
				<th scope="row">
					<?php esc_html_e( 'Limit the IP addresses users can log in from', 'melapress-login-security' ); ?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span>
								<?php esc_html_e( 'Activate IP addresses restrictions', 'melapress-login-security' ); ?>
							</span>
						</legend>
						<label for="mls-restrict-login-ip">
							<input name="mls_options[restrict_login_ip]" type="checkbox" id="mls-restrict-login-ip" value="1" <?php checked( OptionsHelper::string_to_bool( $settings_tab->restrict_login_ip ) ); ?> />
								<?php esc_html_e( 'Activate IP addresses restrictions', 'melapress-login-security' ); ?>		
								<br>    				
								<p class="description">
									<?php esc_html_e( "Use the below setting to specify the number of different IP addresses a user can log in from. If a user does not have any recorded IP addresses, the plugin will start keeping a log of the different IP addresses the user logs in from. Logins will be limited to the first number of recorded IP addresses based on the below-configured limit. You can always remove or edit IP addresses from the users' profile page.", 'melapress-login-security' ); ?>
								</p>
								<br>
								
								<div class="restrict-login-option">
									<?php
										ob_start();
									?>
									<input name="mls_options[restrict_login_ip_count]" type="number" value="<?php echo esc_attr( $settings_tab->restrict_login_ip_count ); ?>" min="1" max="10" size="4" class="tiny-text ltr" required/>
									<?php
										$input_history = ob_get_clean();
										/* translators: %s: Configured number of old password to check for duplication. */
										printf( esc_html__( 'Allow users to log in from %s different IP addresses.', 'melapress-login-security' ), wp_kses( $input_history, $wp_kses_args ) );
									?>
								</div>

								<div class="restrict-login-option" style="margin-top: 30px;">
									<p class="description" style="margin-bottom: 10px; display: block;">
										<?php
											$messages_settings = '<a href="' . add_query_arg( 'page', 'mls-settings#message-settings', network_admin_url( 'admin.php' ) ) . '"> ' . __( 'User notification templates', 'ppw-wp' ) . '</a>';
										?>
										<?php echo wp_kses_post( wp_sprintf( /* translators: %s: Link to settings. */ __( 'To customize the notification displayed to users when a login is blocked due to restrictions, please visit the %s plugin settings.', 'melapress-login-security' ), wp_kses_post( $messages_settings ) ) ); ?>
									</p>
								</div>
						</label>
						<br>                           						
					</fieldset>
				</td>
			</tr>

			<?php
			return $markup . ob_get_clean();
		}

		/**
		 * Add settings to message templates area.
		 *
		 * @param array $mls_settings - Settings.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function add_template_settings( $mls_settings ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			?>
				<table class="form-table has-sticky-bar">
					<tbody>

						<tr valign="top">
							<h3><?php esc_html_e( 'User attempts login from a restricted location', 'melapress-login-security' ); ?></h3>
							<p class="description"><?php esc_html_e( 'This warning is shown when user tries to log in using an IP originating from a location that is on the geo-blocked list.', 'melapress-login-security' ); ?></p>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Message', 'melapress-login-security' ); ?>
							</th>
							<td style="padding-right: 15px;">
								<fieldset>
									<?php
									$content   = \MLS\EmailAndMessageStrings::get_email_template_setting( 'restrict_login_ip_login_blocked_message' );
									$editor_id = 'mls_options_restrict_login_ip_login_blocked_message';
									$settings  = array(
										'media_buttons' => false,
										'editor_height' => 200,
										'textarea_name' => 'mls_options[restrict_login_ip_login_blocked_message]',
									);
									wp_editor( $content, $editor_id, $settings );
									?>
								</fieldset>
							</td>
						</tr>	
					</tbody>
				</table>
			<?php
		}

		/**
		 * Get default message if none provided.
		 *
		 * @return string
		 */
		public static function get_default_restrict_login_message() {
			$admin_email = get_site_option( 'admin_email' );
			$email_link  = '<a href="mailto:' . sanitize_email( $admin_email ) . '">' . __( 'website administrator', 'melapress-login-security' ) . '</a>';
			/* translators: %s: Admin email. */
			return sprintf( __( 'Your are unable to login from this IP. Please contact the %1s for further information.', 'melapress-login-security' ), $email_link );
		}

		/**
		 * Handle reset of individual password.
		 *
		 * @param WP_User $user - user to reset.
		 * @return void
		 */
		public static function add_user_profile_field( $user ) {
			// Get current user, we going to need this regardless.
			$current_user = wp_get_current_user();

			// Bail if we still dont have an object.
			if ( ! is_a( $user, '\WP_User' ) || ! is_a( $current_user, '\WP_User' ) ) {
				return;
			}

			$userdata     = get_user_by( 'id', $user->ID );
			$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

			if ( ! OptionsHelper::string_to_bool( $role_options->restrict_login_ip ) ) {
				return;
			}

			if ( \MLS_Core::is_user_exempted( $user->ID ) ) {
				return;
			}

			$ips   = get_user_meta( $user->ID, 'mls_login_ips', true );
			$value = ! empty( $ips ) ? implode( ', ', $ips ) : '';

			if ( current_user_can( 'manage_options' ) ) {
				?>
				<table class="form-table" role="presentation">
					<tbody><tr id="password" class="user-pass1-wrap">
						<th><label for="reset_password"><?php esc_html_e( 'User login IP address restrictions', 'melapress-login-security' ); ?></label></th>
						<td>
							<label for="reset_password_on_next_login">
								<?php esc_html_e( 'Below is the list of the currently stored IP address(es) for this user. You can delete or edit any any of the below IP addresses. Changes will be saved when you click the Update Profile button to save the user profile changes.', 'melapress-login-security' ); ?>
								<?php wp_nonce_field( 'mls_update_users_ips', 'mls_user_ips_nonce' ); ?>
								<br>
								<br>

								<input type="text" name="mls_user_ips" value="<?php echo esc_attr( $value ); ?>" />
							</label>
							<br>
						</td>
						</tr>
					</tbody>
				</table>
				<script type="text/javascript">
					jQuery( document ).ready( function() {
						mls_build_ip_list();

						jQuery( document ).on( 'click', '[data-mls-user-ip-item] [data-edit-ip]', function ( event ) {
							var currentValue = jQuery( this ).parent().attr( 'data-mls-user-ip-item' );
							event.preventDefault();
							let person = prompt( "Edit IP below or leave blank to delete this IP", currentValue );

							if (person != null) { 
								var currentInputValue = jQuery( '[name="mls_user_ips"]' ).val();
								var newtext = currentInputValue.replace( currentValue, person ).trim();

								var lastChar = newtext.slice(-1);
								if (lastChar == ',') {
									newtext = newtext.slice(0, -1);
								}
								newtext = newtext.replace(/^,/, '');

								jQuery( '[name="mls_user_ips"]' ).val( newtext.trim() );
								mls_build_ip_list();  
							}

						} );

						jQuery( document ).on( 'click', '[data-mls-user-ip-item] [data-remove-ip]', function ( event ) {
							event.preventDefault();
							var currentValue = jQuery( this ).parent().attr( 'data-mls-user-ip-item' );
							var currentInputValue = jQuery( '[name="mls_user_ips"]' ).val();
							var newtext = currentInputValue.replace( currentValue, '' ).trim();

							var lastChar = newtext.slice(-1);
							if (lastChar == ',') {
								newtext = newtext.slice(0, -1);
							}
							newtext = newtext.replace(/^,/, '');

							jQuery( '[name="mls_user_ips"]' ).val( newtext.trim() );
							mls_build_ip_list();  

						} );
					});

					function mls_build_ip_list() {
						jQuery( '#mls_user_ip_list' ).remove();
						var inputText = jQuery( '[name="mls_user_ips"]' ).val().trim();
						if ( inputText.length > 0 ) {
							var temp = inputText.split(", ");
							var str = '';
							jQuery.each(temp, function(i,v) {
								str += "<div data-mls-user-ip-item="+v+"><span data-edit-ip>"+v+"<span class='dashicons dashicons-edit'></span></span><span data-remove-ip><span class='dashicons dashicons-no'></span><span></div>";
							});
							var div = document.createElement('div');
							div.innerHTML = str.trim();
							jQuery( div ).attr( 'id', 'mls_user_ip_list' );

							jQuery('[name="mls_user_ips"]' ).after( div );
						}
					}
				</script>
				<style type="text/css">
					[name="mls_user_ips"] {
						display: none;
					}
					div[data-mls-user-ip-item] {
						cursor: pointer;
						display: inline-block;
						border-width: 1px;
						border-style: solid;
						padding: 4px 2px 4px 8px;
						margin: 2px 0 0 2px;
						border-radius: 3px;
						color: #2271b1;
						border-color: #2271b1;
						background: #f6f7f7;
						position: relative;
					}
					div[data-mls-user-ip-item] .dashicons-edit {
						color: #666;
						font-size: 18px;
						margin-left: 3px;
					}
					div[data-mls-user-ip-item] .dashicons-no {
						background: red;
						height: 14px;
						width: 14px;
						position: absolute;
						color: #fff;
						border-radius: 7px;
						line-height: 14px;
						text-align: center;
						font-size: 10px;
						display: block;
						right: -7px;
						top: -5px;
					}
				</style>
				<?php
			}
		}

		/**
		 * Handles saving of user profile fields.
		 *
		 * @param  int $user_id - user ID.
		 * @return void
		 */
		public static function save_user_profile_field( $user_id ) {
			if ( ! current_user_can( 'manage_options' ) || ( isset( $_POST['mls_user_ips_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mls_user_ips_nonce'] ) ), 'mls_update_users_ips' ) ) ) {
				return;
			}

			if ( \MLS_Core::is_user_exempted( $user_id ) ) {
				return;
			}

			if ( isset( $_POST['mls_user_ips'] ) ) {
				$ips = empty( $_POST['mls_user_ips'] ) ? array() : explode( ',', sanitize_text_field( wp_unslash( $_POST['mls_user_ips'] ) ) );
				update_user_meta( $user_id, 'mls_login_ips', $ips );
			}
		}

		/**
		 * Check login to determine if the user is currently blocked
		 *
		 * @param  mixed  $user         WP_User if the user is authenticated. WP_Error or null otherwise.
		 * @param  string $username     Username or email address.
		 * @param  string $password     ser password.
		 *
		 * @return null|WP_User|WP_Error
		 */
		public static function pre_login_check( $user, $username, $password ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			// If WP has already created an error at this point, pass it back and bail.
			if ( is_wp_error( $user ) ) {
				return $user;
			}

			// Get the user ID, either from the user object if we have it, or by SQL query if we dont.
			$user_id       = ( isset( $user->ID ) ) ? $user->ID : \get_user_by( 'login', $username )->ID;

			// If we still have nothing, stop here.
			if ( ! $user_id ) {
				return $user;
			}

			// Return if this user is exempt.
			if ( \MLS_Core::is_user_exempted( $user_id ) ) {
				return $user;
			}

			$role_options = OptionsHelper::get_preferred_role_options( $user->roles );

			if ( OptionsHelper::string_to_bool( $role_options->restrict_login_ip ) ) {
				$stored_ips   = self::get_user_stored_ips( $user_id );
				$user_addr    = isset( $_SERVER['REMOTE_ADDR'] ) ? \MLS\Login_Page_Control::sanitize_incoming_ip( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$error_string = \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'restrict_login_ip_login_blocked_message' ), $user_id );

				if ( ! $user_addr ) {
					/**
					 * Fire of action for others to observe.
					 */
					do_action( 'mls_user_login_blocked_due_to_ip_restrictions', $user_id );
					return new \WP_Error(
						'login_not_allowed',
						$error_string
					);
				}

				// User has no IP set.
				if ( empty( $stored_ips ) ) {
					$add_ip = self::add_to_user_stored_ips( $user_id, $user_addr );
				} else {
					$add_ip = self::add_to_user_stored_ips( $user_id, $user_addr );
					// Is allowed?
					$is_login_allowed = self::is_ip_allowed( $user_id, $user_addr );
					if ( ! $is_login_allowed || ! $add_ip ) {
						/**
						 * Fire of action for others to observe.
						 */
						do_action( 'mls_user_login_blocked_due_to_ip_restrictions', $user_id );

						// UM error handling.
						if ( class_exists( '\UM_Functions' ) ) {
							\UM()->form()->add_error( 'ppmwp_login_attempts_blocked', $error_string );
						}

						return new \WP_Error(
							'login_not_allowed',
							$error_string
						);
					}
				}
			}

			// We must return the user, regardless.
			return $user;
		}

		/**
		 * Check if IP is ok to logins.
		 *
		 * @param int    $user_id - ID to check.
		 * @param string $user_addr - IP to check.
		 * @return boolean
		 */
		public static function is_ip_allowed( $user_id, $user_addr ) {
			$result   = false;
			$user_ips = self::get_user_stored_ips( $user_id );

			if ( \MLS_Core::is_user_exempted( $user_id ) ) {
				return true;
			}

			if ( in_array( $user_addr, $user_ips, true ) ) {
				$result = true;
			}
			return $result;
		}

		/**
		 * Get users stored IPs.
		 *
		 * @param  int $user_id - User ID to get.
		 * @return array $ips - Found IPs.
		 */
		public static function get_user_stored_ips( $user_id ) {
			$ips = get_user_meta( $user_id, 'mls_login_ips', true );

			if ( ! $ips || empty( $ips ) ) {
				$ips = array();
			}

			return $ips;
		}

		/**
		 * Store an IP is enough space is allowed by admin, otherwise if this new IP exceeds the amount allowed, login wont be allowed.
		 *
		 * @param int    $user_id - ID to store.
		 * @param string $incoming - To add.
		 * @return bool   Did update.
		 */
		public static function add_to_user_stored_ips( $user_id, $incoming ) {
			$ips          = self::get_user_stored_ips( $user_id );
			$userdata     = get_user_by( 'id', $user_id );
			$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );
			$max_allowed  = (int) str_replace( '0', '', $role_options->restrict_login_ip_count );

			if ( count( $ips ) < $max_allowed ) {
				// IP is stored already.
				if ( in_array( $incoming, $ips, true ) ) {
					return true;
					// Add new IP.
				} else {
					array_push( $ips, $incoming );
					update_user_meta( $user_id, 'mls_login_ips', $ips );
				}

				return true;
			} else {
				return in_array( $incoming, $ips, true );
			}

			return false;
		}
	}
}
