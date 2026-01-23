<?php
/**
 * Policy settings table.
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

?>
	<tr class="setting-heading" valign="top">
		<th scope="row">
			<h3><?php esc_html_e( 'Password policies', 'melapress-login-security' ); ?></h3>
		</th>
	</tr>
	<tr valign="top" class="neaten-tr">
		<th scope="row">
			<?php esc_html_e( 'Password Policies', 'melapress-login-security' ); ?>
		</th>
		<td>
			<input name="mls_options[activate_password_policies]" data-toggle-other-areas=".password-policies-section" type="checkbox" id="ppm-activate-password-policies" value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->activate_password_policies ) ); ?>>
			<?php esc_attr_e( 'Activate password policies', 'melapress-login-security' ); ?>
			<br>
			<p class="description">
				<?php esc_html_e( 'Use the settings below to setup your specific password requirements', 'melapress-login-security' ); ?>
			</p>			
		</td>
	</tr>

	<tr valign="top" class="neaten-tr password-policies-section">
		<th scope="row">
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Password Length', 'melapress-login-security' ); ?>
					</span>
				</legend>
				<label for="ppm-min-length">

					<?php
					ob_start();
					?>
					<input type="number" id="ppm-min-length" name="mls_options[min_length]"
							value="<?php echo esc_attr( self::$setting_tab->min_length ); ?>" size="4" class="tiny-text ltr" min="1" required>
							<?php
							$input_length = ob_get_clean();
								/* translators: %s: Configured miniumum password length. */
							printf( esc_html__( 'Passwords must be %s characters minimum.', 'melapress-login-security' ), wp_kses( $input_length, OptionsHelper::get_allowed_kses_args() ) );
							?>
				</label>
			</fieldset>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Mixed Case', 'melapress-login-security' ); ?>
					</span>
				</legend>
				<label for="ppm-mix-case">
					<input name="mls_options[ui_rules][mix_case]" type="checkbox" id="ppm-mix-case"
							value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->ui_rules['mix_case'] ) ); ?>/>
							<?php esc_html_e( 'Password must contain at least one uppercase and one lowercase character.', 'melapress-login-security' ); ?>
				</label>
			</fieldset>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Numbers', 'melapress-login-security' ); ?>
					</span>
				</legend>
				<label for="ppm-numeric">
					<input name="mls_options[ui_rules][numeric]" type="checkbox" id="ppm-numeric"
							value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->ui_rules['numeric'] ) ); ?>/>
							<?php
							printf(
								/* translators: 1 - example of numeral */
								esc_html__( 'Password must contain at least one numeric character (%1$s).', 'melapress-login-security' ),
								'<code>0-9</code>'
							);
							?>
				</label>
			</fieldset>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Special Characters', 'melapress-login-security' ); ?>
					</span>
				</legend>
				<label for="ppm-special">
					<input name="mls_options[ui_rules][special_chars]" type="checkbox" id="ppm-special"
							value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->ui_rules['special_chars'] ) ); ?>/>
						<?php esc_html_e( 'Password must contain at least one special character, i.e., a character that is not a letter or a number, such as ( , ? € ! @ # * etc.', 'melapress-login-security' ); ?>
				</label>
			</fieldset>
			<fieldset class="col-indented">
				<input name="mls_options[ui_rules][exclude_special_chars]" type="checkbox" id="ppm-exclude-special"
					value="1" <?php ( isset( self::$setting_tab->ui_rules['exclude_special_chars'] ) ) ? checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->ui_rules['exclude_special_chars'] ) ) : ''; ?>/>
				<label for="ppm-excluded-special-chars">
					<?php esc_html_e( 'Do not allow these special characters in passwords:', 'melapress-login-security' ); ?>
				</label>
				<input
					type="text"
					name="mls_options[excluded_special_chars]"
					id="ppm-excluded-special-chars"
					class="small-input"
					value="<?php echo esc_attr( ( isset( self::$setting_tab->excluded_special_chars ) ) ? self::$setting_tab->excluded_special_chars : self::$options->default_setting['excluded_special_chars'] ); ?>"
			
					onkeypress="accept_only_special_chars_input( event )"
				/>
				<p class="description" style="clear:both;max-width:570px">
					<?php esc_html_e( 'To enter multiple special characters simply type them in one next to the other.', 'melapress-login-security' ); ?>
				</p>
			</fieldset>
			<br>
		</td>
	</tr>

	<tr valign="top" class="neaten-tr">
		<th scope="row">
			<?php esc_html_e( 'Password Expiration Policy', 'melapress-login-security' ); ?>
		</th>
		<td>

			<input name="mls_options[activate_password_expiration_policies]" data-toggle-other-areas=".password-expiration-policies-section" type="checkbox" id="ppm-activate-password-expiration-policies" value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->activate_password_expiration_policies ) ); ?>>
			<?php esc_attr_e( 'Activate password expiration policies', 'melapress-login-security' ); ?>
			<br>

			<p class="description">
				<?php esc_html_e( 'Set to 0 to disable automatic expiration.', 'melapress-login-security' ); ?>
			</p>
			<br>
			
			<fieldset class="password-expiration-policies-section">
			<?php
			ob_start();
			$test_mode = apply_filters( 'mls_enable_testing_mode', false );
			$units     = array(
				'hours'  => __( 'hours', 'melapress-login-security' ),
				'days'   => __( 'days', 'melapress-login-security' ),
				'months' => __( 'months', 'melapress-login-security' ),
			);
			if ( $test_mode ) {
				$units['seconds'] = __( 'seconds', 'melapress-login-security' );
			}
			?>
			<input type="number" id="ppm-expiry-value" name="mls_options[password_expiry][value]"
					value="<?php echo esc_attr( self::$setting_tab->password_expiry['value'] ); ?>" size="4" class="tiny-text ltr" min="0" required>
			<select id="ppm-expiry-unit" name="mls_options[password_expiry][unit]">
				<?php
				foreach ( $units as $key => $unit ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( esc_attr( $key ), self::$setting_tab->password_expiry['unit'], true ); ?>><?php echo esc_html( $unit ); ?></option>
					<?php
				}
				?>
			</select>
			<?php
			$input_expiry = ob_get_clean();
			/* translators: %s: Configured password expiry period. */
			printf( esc_html__( 'Passwords should automatically expire in %s', 'melapress-login-security' ), wp_kses( $input_expiry, OptionsHelper::get_allowed_kses_args() ) );
			?>
			</fieldset>
		</td>
	</tr>

	<tr valign="top" class="neaten-tr password-expiration-policies-section">
		<th scope="row"></th>
		<td>
			<fieldset>
				<input name="mls_options[notify_password_expiry]" type="checkbox" id="ppm-enable-expiry-notify"
					value="1" <?php ( isset( self::$setting_tab->notify_password_expiry ) ) ? checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->notify_password_expiry ) ) : ''; ?>/>
				<label for="ppm-enable-expiry-notify">
					<?php esc_html_e( 'Advise users that their password is about to expire from', 'melapress-login-security' ); ?>
				</label>
				<input name="mls_options[notify_password_expiry_days]" type="number" id="ppm-history" value="<?php echo esc_attr( self::$setting_tab->notify_password_expiry_days ); ?>" min="1" max="100" size="4" class="tiny-text ltr"/>
				<select id="ppm-expiry-notice-unit" name="mls_options[notify_password_expiry_unit]">
					<?php
					foreach ( $units as $key => $unit ) {
						if ( 'months' === $key ) {
							continue;
						}
						?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( esc_attr( $key ), self::$setting_tab->notify_password_expiry_unit, true ); ?>><?php echo esc_html( $unit ); ?></option>
						<?php
					}
					?>
				</select>
				<label for="ppm-expiry-notice-unit">
					<?php esc_html_e( ' before', 'melapress-login-security' ); ?>
				</label>
			</fieldset>
		</td>
	</tr>
	
	<tr valign="top" class="neaten-tr password-expiration-policies-section">
		<th scope="row"></th>
		<td class="col-indented">
			<fieldset>
				<input name="mls_options[notify_password_reset_on_login]" type="checkbox" id="ppm-enable-expiry-reset_on_login"
					value="1" <?php ( isset( self::$setting_tab->notify_password_reset_on_login ) ) ? checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->notify_password_reset_on_login ) ) : ''; ?>/>
				<label for="ppm-excluded-special-chars">
					<?php esc_html_e( 'Once dismissed, show notice to users again upon next login.', 'melapress-login-security' ); ?>
				</label>
			</fieldset>
			<br>
		</td>
	</tr>
	
	<tr valign="top">
		<th scope="row">
			<?php esc_html_e( 'Disallow old passwords on reset', 'melapress-login-security' ); ?>
		</th>
		<td>
			<input name="mls_options[activate_password_recycle_policies]" data-toggle-other-areas=".password-recycle-policies-section" type="checkbox" id="ppm-activate-password-recyclen-policies" value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->activate_password_recycle_policies ) ); ?>>
			<?php esc_attr_e( 'Activate password recycle policies', 'melapress-login-security' ); ?>
			<br>

			<p class="description">
				<?php esc_html_e( 'You can configure the plugin to remember up to 100 previously used passwords that users cannot use. It will remember the last 1 password by default (minimum value: 1).', 'melapress-login-security' ); ?>
			</p>
			<br>

			<fieldset class="password-recycle-policies-section">
				<label for="ppm-history">
					<?php
					ob_start();
					?>
					<input name="mls_options[password_history]" type="number" id="ppm-history"
							value="<?php echo esc_attr( self::$setting_tab->password_history ); ?>" min="1" max="100" size="4" class="tiny-text ltr" required/>
							<?php
							$input_history = ob_get_clean();
								/* translators: %s: Configured number of old password to check for duplication. */
							printf( esc_html__( "Don't allow users to use the last %s passwords when they reset their password.", 'melapress-login-security' ), wp_kses( $input_history, OptionsHelper::get_allowed_kses_args() ) );
							?>
											</label>
			</fieldset>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php esc_html_e( 'Reset password on first login', 'melapress-login-security' ); ?>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Delete database data upon uninstall', 'melapress-login-security' ); ?>
					</span>
				</legend>
				<label for="ppm-initial-password">
					<input name="mls_options[change_initial_password]" type="checkbox" id="ppm-initial-password"
							value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->change_initial_password ) ); ?> />
							<?php esc_html_e( 'Reset password on first login', 'melapress-login-security' ); ?>
					<p class="description">
						<?php esc_html_e( 'Enable this setting to force new users to reset their password the first time they login.', 'melapress-login-security' ); ?>
					</p>
				</label>
			</fieldset>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<?php esc_html_e( 'Disable sending of password reset links', 'melapress-login-security' ); ?>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php esc_html_e( 'Disable sending of password reset links', 'melapress-login-security' ); ?>
					</span>
				</legend>
				<label for="disable-self-reset">
					<input name="mls_options[disable_self_reset]" type="checkbox" id="disable-self-reset" onclick="admin_lockout_check( event )" 
							value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->disable_self_reset ) ); ?> />
							<?php esc_html_e( 'Do not send password reset links', 'melapress-login-security' ); ?>
					<p class="description">
						<?php esc_html_e( 'By default users who forget their password can request a password reset link that is sent to their email address. Enable this setting to stop WordPress sending these links, so users have to contact the website administrator if they forgot their password and need to reset it.', 'melapress-login-security' ); ?>
					</p>
				</label>
			</fieldset>
			<div class="disabled-reset-message-wrapper disabled" style="margin-top: 10px;">
				<p class="description" style="margin-bottom: 10px; display: block;">
					<?php
						$messages_settings = '<a href="' . add_query_arg( 'page', 'mls-settings#message-settings', network_admin_url( 'admin.php' ) ) . '"> ' . __( 'User notices templates', 'ppw-wp' ) . '</a>';
					?>
					<?php echo wp_kses_post( wp_sprintf( /* translators: %s: Link to plugin settings. */  __( 'To customize the notification displayed to users, please visit the %s plugin settings.', 'melapress-login-security' ), $messages_settings ) ); ?>
				</p>
			</div>
		</td>
	</tr>
	
	<?php
		$additional = apply_filters( 'ppm_settings_additional_settings', '', self::$setting_tab );
		echo wp_kses( $additional, OptionsHelper::get_allowed_kses_args() );
	?>
