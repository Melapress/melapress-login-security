<?php
/**
 * Handles policies admin area.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

$sidebar_required = false;

// @free:start

// Override in free edition.
$sidebar_required = true;
// @free:end
$form_class = ( $sidebar_required ) ? 'sidebar-present' : '';
?>

<div class="wrap ppm-wrap">
	<form method="post" id="ppm-wp-settings" class="<?php echo esc_attr( $form_class ); ?>">
		<div class="mls-settings">

			<!-- getting started -->
			<div class="page-head">
				<h2><?php esc_html_e( 'Plugin settings', 'melapress-login-security' ); ?></h2>
			</div>

			<?php
				$tab_links = apply_filters( 'mls_settings_page_nav_tabs', '' );

			if ( ! empty( $tab_links ) ) {
				?>
					<div class="nav-tab-wrapper">
						<a href="#general-settings" class="nav-tab nav-tab-active" data-tab-target=".ppm-general-settings"><?php esc_html_e( 'General settings', 'melapress-login-security' ); ?></a>
						<?php echo wp_kses( $tab_links, OptionsHelper::get_allowed_kses_args() ); ?>
					</div>
				<?php
			}
			?>

			<div class="settings-tab ppm-general-settings">
				<table class="form-table">
					<tbody>

						<tr valign="top">
							<br>
							<h1><?php esc_html_e( 'General Settings', 'melapress-login-security' ); ?></h1>
							<p class="description"><?php esc_html_e( 'On this page you can edit and manage the plugin\'s general settings.', 'melapress-login-security' ); ?></p>
							<br>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Weekly Summary', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e( 'Send me a weekly summary of newly inactive and blocked users, and those whom have reset their password in the last week.', 'melapress-login-security' ); ?>
										</span>
									</legend>
									<label for="ppm-send-summary-email">
										<input name="mls_options[send_summary_email]" type="checkbox" id="ppm-send-summary-email"
												value="yes" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$options->mls_setting->send_summary_email ) ); ?>/>
												<?php esc_html_e( 'Enable weekly summary emails.', 'melapress-login-security' ); ?>
												<p class="description">
													<?php esc_html_e( 'Send me a weekly summary of newly inactive and blocked users, and those whom have reset their password in the last week. Uses from/default address set below.', 'melapress-login-security' ); ?>
												</p>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Weekly Summary day', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<label for="ppm-send-summary-email-day">
									<select id="reset-role-select" name="mls_options[send_summary_email_day]">
										<?php
										$days = array(
											'Sunday',
											'Monday',
											'Tuesday',
											'Wednesday',
											'Thursday',
											'Friday',
											'Saturday',
										);
										foreach ( $days as $day ) {
											echo '<option value="' . esc_attr( strtolower( $day ) ) . '" ' . selected( strtolower( $day ), self::$options->mls_setting->send_summary_email_day, false ) . '>' . wp_kses_post( $day ) . '</option>';
										}
										?>
									</select>
										<p class="description">
											<?php esc_html_e( 'Select which day the summary should send.', 'melapress-login-security' ); ?>
										</p>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th>
								<?php esc_html_e( 'Users exempted from all password policies', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<input type="text" id="ppm-exempted" style="float: left; display: block; width: 250px;">
									<input type="hidden" id="ppm-exempted-users" name="mls_options[exempted][users]" value="<?php echo ( isset( self::$options->mls_setting->exempted['users'] ) && ! empty( self::$options->mls_setting->exempted['users'] ) ) ? esc_attr( htmlentities( wp_json_encode( self::$options->mls_setting->exempted['users'] ), ENT_QUOTES, 'UTF-8' ) ) : ''; ?>">
									<p class="description" style="clear:both;">
										<?php
										esc_html_e( 'Users in this list will be exempted from all the policies.', 'melapress-login-security' );
										?>
									</p>
									<ul id="ppm-exempted-list">
										<?php
										if ( isset( self::$options->mls_setting->exempted['users'] ) && is_array( self::$options->mls_setting->exempted['users'] ) ) {
											foreach ( self::$options->mls_setting->exempted['users'] as $user_id ) {
												$user = get_userdata( $user_id );
												if ( $user ) :
													?>
													<li class="ppm-exempted-list-item ppm-exempted-users user-btn button button-secondary" data-id="<?php echo esc_attr( $user_id ); ?>">
														<?php echo esc_html( $user->user_login ); ?>
														<a href="#" class="remove remove-item"></a>
													</li>
													<?php
												endif;
											}
										}
										?>
									</ul>
								</fieldset>
							</td>
						</tr>						

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Instantly terminate session on password expire or reset', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e( 'Instantly terminate session on password expire or reset', 'melapress-login-security' ); ?>
										</span>
									</legend>
									<label for="ppm-terminate-session-password">
										<input name="mls_options[terminate_session_password]" type="checkbox" id="ppm-terminate-session-password"
											value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$options->mls_setting->terminate_session_password ) ); ?>/>
											<?php esc_html_e( 'Terminate session on password expire', 'melapress-login-security' ); ?>
										<p class="description">
											<?php esc_html_e( "By default when a user's password expires or is reset, their current session is not terminated, and they are asked to reset their password once they log out and log back in. Enable this option to instantly terminate the users' sessions once the password expires or is reset.", 'melapress-login-security' ); ?>
										</p>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Reset key expiry time', 'melapress-login-security' ); ?>
							</th>
							<td>

								<?php
								ob_start();
								$units = array(
									'days'  => __( 'days', 'melapress-login-security' ),
									'hours' => __( 'hours', 'melapress-login-security' ),
								);
								?>
								<input type="number" id="ppm-reset-key-expiry-value" name="mls_options[password_reset_key_expiry][value]"
											value="<?php echo esc_attr( self::$options->mls_setting->password_reset_key_expiry['value'] ); ?>" size="4" class="small-text ltr" min="1" required>
								<select id="ppm-reset-key-expiry-unit" name="mls_options[password_reset_key_expiry][unit]">
									<?php
									foreach ( $units as $key => $unit ) {
										?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, self::$options->mls_setting->password_reset_key_expiry['unit'] ); ?>><?php echo esc_html( $unit ); ?></option>
										<?php
									}
									?>
								</select>
								<?php
								$input_expiry = ob_get_clean();
								/* translators: %s: Configured password expiry period. */
								printf( esc_html__( 'Passwords’ reset keys should automatically expire in %s', 'melapress-login-security' ), wp_kses( $input_expiry, OptionsHelper::get_allowed_kses_args() ) );
								?>
								<p class="description">
									<?php esc_html_e( 'By default when a user requests a password reset, the reset key will expire with 24 hours. Use this option to control this expiry time.', 'melapress-login-security' ); ?>
								</p>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Reset password generation', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e( 'Do not auto-generate a new password on the password reset screen', 'melapress-login-security' ); ?>
										</span>
									</legend>
									<label for="ppm-stop_pw_generate">
										<input name="mls_options[stop_pw_generate]" type="checkbox" id="ppm-stop_pw_generate"
											value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$options->mls_setting->stop_pw_generate ) ); ?>/>
											<?php esc_html_e( 'Do not auto-generate a new password on the password reset screen', 'melapress-login-security' ); ?>
										<p class="description">
											<?php esc_html_e( 'By default, when a user resets their password, a new password is generated automatically when configuring a new password. Use the above setting to disable this feature.', 'melapress-login-security' ); ?>
										</p>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Policy priority for users with multiple roles', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<label for="ppm-users-have-multiple-roles">
										<input name="mls_options[users_have_multiple_roles]" type="checkbox" id="ppm-users-have-multiple-roles"
												value="yes" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$options->mls_setting->users_have_multiple_roles ) ); ?>/>
										<?php esc_html_e( 'Configure User role priority for password and login policies enforcement', 'melapress-login-security' ); ?>
										<p class="description">
										<?php esc_html_e( 'By default our plugin will apply the policy based on the 1st role found for a user, if your users are able to have multiple roles the correct policies may not be applied. To control this, sort the roles below into order priority (the higher the role means policies for this role will override subsequent policies which may also be applicable to a user).', 'melapress-login-security' ); ?>
										</p>
									</label>

									<?php
									$roles_obj  = wp_roles();
									$role_names = $roles_obj->get_names();

									$saved_order = ( isset( self::$options->mls_setting->multiple_role_order ) && ! empty( self::$options->mls_setting->multiple_role_order ) ) ? self::$options->mls_setting->multiple_role_order : array();

									// Newly added roles.
									$new_roles = array_diff( array_values( $role_names ), $saved_order );
									if ( ! empty( $new_roles ) ) {
										$saved_order = $saved_order + $new_roles;
									}

									// Removed roles.
									$obselete_roles = array_diff( $saved_order, array_keys( $role_names ) );
									if ( ! empty( $obselete_roles ) ) {
										foreach ( $obselete_roles as $index => $role_to_remove ) {
											$key = array_search( $role_to_remove, $saved_order, true );
											if ( false !== $key ) {
												unset( $saved_order[ $key ] );
											}
										}
									}

									$roles_names_array = ( ! empty( $saved_order ) && is_array( $saved_order ) ) ? $saved_order : $roles_obj->get_names();
									$roles_list_items  = '';

									foreach ( $roles_names_array as $key => $label ) {
										$roles_list_items .= '<li class="ui-state-default" data-role-key="' . strtolower( str_replace( ' ', '_', $label ) ) . '"><span class="dashicons dashicons-leftright"></span>' . ucwords( str_replace( '_', ' ', $label ) ) . '</li>';
									}

									$value_string = implode( ',', $roles_names_array );
									?>

									<div id="sortable_roles_holder" class="disabled">
										<ul id="roles_sortable"> 
											<?php echo wp_kses( $roles_list_items, OptionsHelper::get_allowed_kses_args() ); ?>
										</ul>

										<p class="description">
											<?php esc_html_e( 'Higher roles will superceed lower roles, meaning if a user has the role "subscriber" and also "author", to ensure "author" policies apply place it above "subscriber" to give these policies priority.', 'melapress-login-security' ); ?>
										</p>
									</div>

									<input type="hidden" id="multiple-role-order" name="mls_options[multiple_role_order]" value='<?php echo esc_html( $value_string ); ?>' />
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Password Expiry Email Limit', 'melapress-login-security' ); ?>
							</th>
							<td>
								<p class="description"><?php esc_html_e( 'Configure how many password reset emails users receive when their password expires. Limiting emails helps prevent potential abuse while ensuring users can reset their passwords. If a user attempts to log in multiple times with an expired password, the plugin will only send reset emails up to the configured limit.', 'melapress-login-security' ); ?></p>
								<br>

								<fieldset>
									<label for="password_expiry_email_limit_one">
										<input type="radio" name="mls_options[password_expiry_email_limit]" id="password_expiry_email_limit_one" value="limit_to_one" <?php checked( self::$options->mls_setting->password_expiry_email_limit, 'limit_to_one' ); ?> />
										<?php esc_html_e( 'Limit to one email - Send only one password reset email per user when their password expires, regardless of how many times they attempt to log in.', 'melapress-login-security' ); ?>
									</label>
									<br>
									<label for="password_expiry_email_limit_multiple">
										<input type="radio" name="mls_options[password_expiry_email_limit]" id="password_expiry_email_limit_multiple" value="send_multiple" <?php checked( self::$options->mls_setting->password_expiry_email_limit, 'send_multiple' ); ?> />
										<?php esc_html_e( 'Send multiple emails - send up to', 'melapress-login-security' ); ?>
									</label>
									<input type="number" id="password_expiry_email_limit_count" name="mls_options[password_expiry_email_limit_count]" value="<?php echo esc_attr( self::$options->mls_setting->password_expiry_email_limit_count ); ?>" min="2" max="20" style="width: 60px; margin-left: 5px; <?php echo ( self::$options->mls_setting->password_expiry_email_limit !== 'send_multiple' ) ? 'display: none;' : ''; ?>" />
									<span id="password_expiry_email_limit_count_text" style="<?php echo ( self::$options->mls_setting->password_expiry_email_limit !== 'send_multiple' ) ? 'display: none;' : ''; ?>"><?php esc_html_e( 'emails', 'melapress-login-security' ); ?></span>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'From email address', 'melapress-login-security' ); ?>
							</th>
							<td>
								<p class="description"><?php esc_html_e( 'By default the plugin sends email notifications from "mls@yourdomain.com" where "yourdomain.com" is the domain name of the WordPress website. This is done to ensure optimal email deliverability. However, you can change the email address notifications are sent from using the settings below.', 'melapress-login-security' ); ?></p>
								<br>

								<?php
								$from_address = self::$options->mls_setting->from_email ? self::$options->mls_setting->from_email : '';
								$from_name    = self::$options->mls_setting->from_display_name ? self::$options->mls_setting->from_display_name : '';
								?>

								<fieldset>
									<?php $use_email = self::$options->mls_setting->use_custom_from_email; ?>
									<label for="default_email">
										<input type="radio" name="mls_options[use_custom_from_email]" id="default_email" value="default_email" <?php checked( $use_email, 'default_email' ); ?> />
										<?php esc_html_e( 'Use the default email address', 'melapress-login-security' ); ?> <?php echo wp_kses_post( \MLS\Emailer::get_default_email_address() ); ?>
									</label>
									<br>
									<label for="custom_email">
										<input type="radio" name="mls_options[use_custom_from_email]" id="custom_email" value="custom_email" <?php checked( $use_email, 'custom_email' ); ?> />
										<?php esc_html_e( 'Use another email address', 'melapress-login-security' ); ?>
									</label>
									<br>
									<label for="from-email">
										<?php esc_html_e( 'Email Address', 'melapress-login-security' ); ?>
										<input type="email" id="from-email" name="mls_options[from_email]" value="<?php echo esc_attr( $from_address ); ?>" />
									</label>
									<br>
									<label for="from-display-name">
										<?php esc_html_e( 'Display Name', 'melapress-login-security' ); ?>&nbsp;
										<input type="text" id="from-display-name" name="mls_options[from_display_name]" value="<?php echo esc_attr( $from_name ); ?>" />
									</label>
								</fieldset>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label>
									<?php esc_html_e( 'Email Test', 'melapress-login-security' ); ?>
							</th>
							<td>
								<button type="button" class="button-secondary" id="ppm-wp-test-email"><?php esc_html_e( 'Send Test Email', 'melapress-login-security' ); ?></button>
								<span id="ppm-wp-test-email-loading" class="spinner" style="float:none"></span>
								<p class="description" style="clear:both;max-width:570px">
									<?php
									esc_html_e(
										'The plugin uses emails to alert users that their password has expired.
									Use the test button below to send a test email to my email address and confirm email functionality.',
										'melapress-login-security'
									);
									?>
								</p>
							</td>
						</tr>

						<tr valign="top" style="border: 1px solid red;">
							<th scope="row" style="padding-left: 15px;">
								<?php esc_html_e( 'Delete database data upon uninstall', 'melapress-login-security' ); ?>
							</th>
							<td style="padding-right: 15px;">
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e( 'Delete database data upon uninstall', 'melapress-login-security' ); ?>
										</span>
									</legend>
									<label for="ppm-clear-history">
										<input name="mls_options[clear_history]" type="checkbox" id="ppm-clear-history"
											value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$options->mls_setting->clear_history ) ); ?>/>
											<?php esc_html_e( 'Delete database data upon uninstall', 'melapress-login-security' ); ?>
										<p class="description">
											<?php esc_html_e( 'Enable this setting to delete the plugin\'s data from the database upon uninstall.', 'melapress-login-security' ); ?>
										</p>
									</label>
								</fieldset>
							</td>
						</tr>

					</tbody>
				</table>
			</div>

			<?php
				$scripts_required = false;
				$additional_tabs  = apply_filters( 'mls_settings_page_content_tabs', '' );
			if ( ! empty( $additional_tabs ) ) {
				$scripts_required = true;
				echo $additional_tabs; // phpcs:ignore
			}
			?>

		</div>

		<?php wp_nonce_field( MLS_PREFIX . '_nonce_form', MLS_PREFIX . '_nonce' ); ?>
		
		<div class="submit">
			<input type="submit" name="_ppm_save" class="button-primary"
		value="<?php echo esc_attr( __( 'Save Changes', 'melapress-login-security' ) ); ?>" />
		</div>
	</form>

	<?php
	// @free:start
	require_once MLS_PATH . 'admin/templates/views/upgrade-sidebar.php';
	// @free:end

	?>
</div> 

<?php
if ( $scripts_required ) {
	?>
<script type="text/javascript">
	function showTab( ) {
		var activeTab = jQuery( '.nav-tab-wrapper .nav-tab-active' ).attr( 'data-tab-target' );
		jQuery( '.settings-tab' ).hide();
		jQuery('body').find( '' + activeTab + '' ).show();
	}

	var tabsArray = [
		'#email-settings',
		'#message-settings',
		'#forms-and-placement-settings',
		'#login-page-settings',
		'#users-export',
		'#integrations',
		'#settings-export',
	];

	jQuery.each( tabsArray, function( i, val ) {
		if (window.location.href.indexOf( val ) > -1 ) {
			jQuery( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
			jQuery( 'a[href="' + val + '"]' ).addClass( 'nav-tab-active' );
			showTab();		
		}
	});

	// Needs improvement.
	if (window.location.href.indexOf( "#email-settings" ) > -1 ) {
		jQuery( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
		jQuery( 'a[href="#email-settings"]' ).addClass( 'nav-tab-active' );
		showTab();		
	}


	jQuery( document ).ready( function( $ ) {
		showTab();	

		$( "body" ).on( 'click', 'a[data-tab-target]', function( event ) {
			$( 'body' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
			$(this).addClass( 'nav-tab-active' );
			showTab();
		});

		// Toggle password expiry email limit input field
		$( 'input[name="mls_options[password_expiry_email_limit]"]' ).on( 'change', function() {
			if ( $(this).val() === 'send_multiple' ) {
				$( '#password_expiry_email_limit_count' ).show();
				$( '#password_expiry_email_limit_count_text' ).show();
			} else {
				$( '#password_expiry_email_limit_count' ).hide();
				$( '#password_expiry_email_limit_count_text' ).hide();
			}
		});
	} );

</script>
	<?php
}
?>
