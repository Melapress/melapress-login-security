
	<tr valign="top">
		<th scope="row">
			<?php _e( 'Password Policies', 'ppm-wp' ); ?>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php _e( 'Password Length', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-min-length">

					<?php
					ob_start();
					?>
					<input type="number" id="ppm-min-length" name="_ppm_options[min_length]"
					       value="<?php echo esc_attr( $this->setting_tab->min_length ); ?>" size="4" class="tiny-text ltr" min="1" required>
					       <?php
					       $input_length = ob_get_clean();
								 /* translators: %s: Configured miniumum password length. */
					       printf( __( 'Passwords must be minimum %s characters.', 'ppm-wp' ), $input_length );
					       ?>
				</label>
			</fieldset>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php _e( 'Mixed Case', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-mix-case">
					<input name="_ppm_options[ui_rules][mix_case]" type="checkbox" id="ppm-mix-case"
					       value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->ui_rules[ 'mix_case' ] ) ); ?>/>
					       <?php _e( 'Password must contain a mix of uppercase and lowercase characters.', 'ppm-wp' ); ?>
				</label>
			</fieldset>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php _e( 'Numbers', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-numeric">
					<input name="_ppm_options[ui_rules][numeric]" type="checkbox" id="ppm-numeric"
					       value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->ui_rules[ 'numeric' ] ) ); ?>/>
					       <?php _e( 'Password must contain numeric digits (<code>0-9</code>).', 'ppm-wp' ); ?>
				</label>
			</fieldset>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">

		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php _e( 'Special Characters', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-special">
					<input name="_ppm_options[ui_rules][special_chars]" type="checkbox" id="ppm-special"
					       value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->ui_rules[ 'special_chars' ] ) ); ?>/>
						<?php
						printf(
							/* translators: 1 - a list of special characters wrapped in a code block */
							esc_html__( 'Password must contain special characters (eg: %1$s).', 'ppm-wp' ),
							'<code>' . esc_html( ppm_wp()->get_special_chars() ) . '</code>'
						);
						?>
				</label>
			</fieldset>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">

		</th>
		<td class="col-indented">
			<fieldset>
				<input name="_ppm_options[ui_rules][exclude_special_chars]" type="checkbox" id="ppm-exclude-special"
					value="1" <?php ( isset( $this->setting_tab->ui_rules['exclude_special_chars'] ) ) ? checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->ui_rules['exclude_special_chars'] ) ) : ''; ?>/>
				<label for="ppm-excluded-special-chars">
					<?php esc_html_e( 'Do not allow these special characters in passwords:', 'ppm-wp' ); ?>
				</label>
				<input
					type="text"
					name="_ppm_options[excluded_special_chars]"
					id="ppm-excluded-special-chars"
					class="small-input"
					value="<?php echo esc_attr( ( isset( $this->setting_tab->excluded_special_chars ) ) ? $this->setting_tab->excluded_special_chars : $this->options->default_setting['excluded_special_chars'] ); ?>"
					pattern="<?php echo esc_attr( ppm_wp()->get_special_chars() ); ?>*?"
					onkeypress="accept_only_special_chars_input( event )"
				/>
				<p class="description" style="clear:both;max-width:570px">
					<?php esc_html_e( 'To enter multiple special characters simply type them in one next to the other.', 'ppm-wp' ); ?>
				</p>
			</fieldset>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="ppm-expiry-value">
				<?php _e( 'Password Expiration Policy', 'ppm-wp' ); ?>
			</label>
		</th>
		<td>

			<?php
			ob_start();
			$test_mode = apply_filters( 'ppmwp_enable_testing_mode', false );
			$units = array(
				'days'   => __( 'days', 'ppm-wp' ),
				'hours'  => __( 'hours', 'ppm-wp' ),
				'months' => __( 'months', 'ppm-wp' ),
			);
			if( $test_mode ){
				$units['seconds'] = __( 'seconds', 'ppm-wp' );
			}
			?>
			<input type="number" id="ppm-expiry-value" name="_ppm_options[password_expiry][value]"
			       value="<?php echo esc_attr( $this->setting_tab->password_expiry[ 'value' ] ); ?>" size="4" class="small-text ltr" min="0" required>
			<select id="ppm-expiry-unit" name="_ppm_options[password_expiry][unit]">
				<?php foreach ( $units as $key => $unit ) {
					?>
					<option value="<?php echo $key; ?>" <?php selected( $key, $this->setting_tab->password_expiry[ 'unit' ], true ); ?>><?php echo $unit; ?></option>
					<?php
				}
				?>
			</select>
			<?php
			$input_expiry = ob_get_clean();
			/* translators: %s: Configured password expiry period. */
			printf( __( "Passwords should automatically expire in %s", 'ppm-wp' ), $input_expiry );
			?>
			<p class="description">
				<?php _e( 'Set to 0 to disable automatic expiration.', 'ppm-wp' ); ?>
			</p>
		</td>
	</tr>
	<!-- Inactive Users Setting -->
	<tr valign="top" id="ppmwp-inactive-setting-row">
		<th scope="row">
			<label for="ppm-inactive-users-enable">
				<?php esc_html_e( 'Inactive Users', 'ppm-wp' ); ?>
			</label>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span><?php esc_html_e( 'Enable Inactive Users Features', 'ppm-wp' ); ?></span>
				</legend>
				<label for="ppm-inactive-users-enabled">
					<input name="_ppm_options[inactive_users_enabled]" type="checkbox" id="ppm-inactive-users-enabled" value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->inactive_users_enabled ) ); ?>>
					<?php esc_html_e( 'Disable inactive user accounts if they are inactive for more than', 'ppm-wp' ); ?>
					<input type="number" id="ppm-dormany-value" name="_ppm_options[inactive_users_expiry][value]"
					       value="<?php echo esc_attr( $this->setting_tab->inactive_users_expiry[ 'value' ] ); ?>" size="4" class="small-text ltr" min="0" required>
					<select id="ppm-expiry-unit" name="_ppm_options[inactive_users_expiry][unit]">
						<?php 
						foreach ( $units as $index => $unit ) {
							?>
							<option value="<?php echo $index; ?>" <?php selected( $unit, $this->setting_tab->inactive_users_expiry[ 'unit' ], true ); ?>><?php echo $unit; ?></option>
							<?php
						}
						?>
					</select>
				</label>
				<p class="description">
					<?php
					$inactive_users_url = add_query_arg(
						array(
							'page' => 'ppm_wp_settings',
							'tab'  => 'inactive-users',
						),
						network_admin_url( 'admin.php' )
					);
					// Inactive users accounts will be will be locked. You can unlock users from the [Locked Users page](https://www.wpwhitesecurity.com/support/kb/dormant-users-policy-wordpress/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' in this plugin. To learn more about this policy read the Inactive users policy feature document.
					printf(
						esc_html__( 'Inactive users accounts will be will be locked. You can unlock users from the %1$s page in this plugin. To learn more about this policy, read the %2$s feature document', 'ppm-wp' ),
						sprintf(
							'<a href="%1$s">%2$s</a>',
							esc_url( $inactive_users_url ),
							esc_html__( 'Locked Users', 'ppm-wp' )
						),
						sprintf(
							'<a target="_blank" href="https://www.wpwhitesecurity.com/support/kb/dormant-users-policy-wordpress/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page">%s</a>',
							esc_html__( 'Inactive users policy', 'ppm-wp' )
						)
					);
					?>
				</p>
			</fieldset>
		</td>
	</tr>

	<tr valign="top" id="ppmwp-inactive-setting-reset-pw-row">
		<th scope="row">
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span><?php esc_html_e( 'Enable Inactive User Password Reset Feature', 'ppm-wp' ); ?></span>
				</legend>
				<label for="ppm-inactive-users-reset-on-unlock">
					<input name="_ppm_options[inactive_users_reset_on_unlock]" type="checkbox" id="ppm-inactive-users-reset-on-unlock" value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->inactive_users_reset_on_unlock ) ); ?>>
					<?php esc_html_e( 'Require inactive users to reset password on unlock.', 'ppm-wp' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'By default, when a inactive user has been unlocked, they are required to reset their password upon logging in - leave this unchecked to disable this behaviour.', 'ppm-wp' ); ?>
				</p>
			</fieldset>
		</td>
	</tr>

	<!-- End Inactive Users Settings -->
	<tr valign="top">
		<th scope="row">
			<label for="ppm-history">
				<?php _e( 'Disallow old passwords on reset', 'ppm-wp' ); ?>
			</label>
		</th>
		<td>
			<fieldset>
				<label for="ppm-history">
					<?php
					ob_start();
					?>
					<input name="_ppm_options[password_history]" type="number" id="ppm-history"
					       value="<?php echo $this->setting_tab->password_history; ?>" min="1" max="100" size="4" class="tiny-text ltr" required/>
					       <?php
					       $input_history = ob_get_clean();
								 /* translators: %s: Configured number of old password to check for duplication. */
					       printf( __( "Don't allow users to use the last %s passwords when they reset their password.", 'ppm-wp' ), $input_history );
					       ?>
					<p class="description">
						<?php _e( 'You can configure the plugin to remember up to 100 previously used passwords that users cannot use. It will remember the last 1 password by default (minimum value: 1).', 'ppm-wp' ); ?>
					</p>
				</label>
			</fieldset>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<label for="ppm-initial-password">
				<?php _e( 'Reset password on first login', 'ppm-wp' ); ?>
			</label>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php _e( 'Delete database data upon uninstall', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-initial-password">
					<input name="_ppm_options[change_initial_password]" type="checkbox" id="ppm-initial-password"
						   value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->change_initial_password ) ); ?> />
						   <?php _e( 'Reset password on first login', 'ppm-wp' ); ?>
					<p class="description">
						<?php _e( 'Enable this setting to force new users to reset their password the first time they login.', 'ppm-wp' ); ?>
					</p>
				</label>
			</fieldset>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<label for="ppm-disable-self-reset">
				<?php _e( 'Disable sending of password reset links', 'ppm-wp' ); ?>
			</label>
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php _e( 'Disable sending of password reset links', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="disable-self-reset">
					<input name="_ppm_options[disable_self_reset]" type="checkbox" id="disable-self-reset"
						   value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->disable_self_reset ) ); ?> />
						   <?php _e( 'Do not send password reset links', 'ppm-wp' ); ?>
					<p class="description">
						<?php _e( 'By default users who forget their password can request a password reset link that is sent to their email address. Enable this setting to stop WordPress sending these links, so users have to contact the website administrator if they forgot their password and need to reset it.', 'ppm-wp' ); ?>
					</p>
				</label>
			</fieldset>
			<div class="disabled-reset-message-wrapper disabled" style="margin-top: 30px;">
				<p class="description" style="margin-bottom: 10px; display: block;">
					<?php _e( 'Display the following message when a user requests a password reset.', 'ppm-wp' ); ?>
				</p>
				<textarea id="disable_self_reset_message" name="_ppm_options[disable_self_reset_message]" rows="2" cols="60"><?php echo esc_attr( $this->setting_tab->disable_self_reset_message ); ?></textarea>
			</div>
		</td>
	</tr>

	<tr valign="top">
		<th scope="row">
			<?php _e( 'Enable Failed Logins Policies', 'ppm-wp' ); ?>
		</th>
		<td>
			<fieldset>
				<input name="_ppm_options[failed_login_policies_enabled]" type="checkbox" id="ppm-failed-login-policies-enabled" value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->failed_login_policies_enabled ) ); ?>>
			</fieldset>
		</td>
	</tr>

	<tr valign="top" class="ppmwp-login-block-options">
		<th scope="row">
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php _e( 'Number of failed login attempts before locking a user:', 'ppm-wp' ); ?>
					</span>
				</legend>
				<label for="ppm-failed-login-attempts">
					<?php _e( 'Number of failed login attempts before locking a user:', 'ppm-wp' ); ?>
					<input type="number" id="ppm-failed-login-attempts" name="_ppm_options[failed_login_attempts]"
								 value="<?php echo esc_attr( $this->setting_tab->failed_login_attempts ) ?>" size="4" class="tiny-text ltr" min="1" required>
				</label>
				<p class="description">
					<?php _e( 'In this section you can configure the failed login attempts policies. With these policies you can configure the plugin to lock user accounts after a number of failed logins. The number of failed logins is reset every 24 hours.', 'ppm-wp' ); ?>
				</p>
			</fieldset>
		</td>
	</tr>

	<tr valign="top" class="ppmwp-login-block-options">
		<th scope="row">
		</th>
		<td>
			<fieldset>
				<p class="description" style="display: inline;"><?php _e( 'When a user is locked: ', 'ppm-wp' ); ?></p>
				<span style="display: inline-table;">
					<input type="radio" id="unlock-by-admin" name="_ppm_options[failed_login_unlock_setting]" value="unlock-by-admin" <?php checked( $this->setting_tab->failed_login_unlock_setting, 'unlock-by-admin' ); ?>>
					<label for="unlock-by-admin"><?php _e( 'it can be only unlocked by the administrator', 'ppm-wp' ); ?></label><br>
					<input type="radio" id="timed" name="_ppm_options[failed_login_unlock_setting]" value="timed" <?php checked( $this->setting_tab->failed_login_unlock_setting, 'timed' ); ?>>
					<label for="timed"><?php _e( 'unlock it after', 'ppm-wp' ); ?> <input type="number" id="ppm-failed-login-reset-hours" name="_ppm_options[failed_login_reset_hours]" value="<?php echo esc_attr( $this->setting_tab->failed_login_reset_hours ) ?>" size="4" class="tiny-text ltr" min="1" required> <?php _e( 'hour(s)', 'ppm-wp' ); ?></label>
				</span>
			</fieldset>
		</td>
	</tr>

	<tr valign="top" class="ppmwp-login-block-options" id="ppmwp-reset-pw-on-login-unblock">
		<th scope="row">
		</th>
		<td>
			<fieldset>
				<legend class="screen-reader-text">
					<span><?php esc_html_e( 'Enable Inactive User Password Reset Feature', 'ppm-wp' ); ?></span>
				</legend>
				<label for="ppm-failed-login-reset-on-unblock">
					<input name="_ppm_options[failed_login_reset_on_unblock]" type="checkbox" id="ppm-failed-login-reset-on-unblock" value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $this->setting_tab->failed_login_reset_on_unblock ) ); ?>>
					<?php esc_html_e( 'Require blocked users to reset password on unblock.', 'ppm-wp' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'By default, when a previously blocked user has been unblocked by an administrator, they are required to reset their password upon logging in - leave this unchecked to disable this behaviour.', 'ppm-wp' ); ?>
				</p>
			</fieldset>
		</td>
	</tr>