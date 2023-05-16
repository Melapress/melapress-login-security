<?php
/**
 * PPM Email Settings
 *
 * @package WordPress
 * @subpackage wpassword
 * @author WP White Security
 */

 use \PPMWP\Helpers\OptionsHelper;
 use \PPMWP\Helpers\PPM_EmailStrings;

if ( ! class_exists( 'PPM_Email_Settings' ) ) {

	/**
	 * Manipulate Users' Password History
	 */
	class PPM_Email_Settings {

		/**
		 * Init settings hooks.
		 *
		 * @return void
		 */
		public function init() {
			add_filter( 'ppmwp_settings_page_nav_tabs', array( $this, 'settings_tab_link' ), 10, 1 );
			add_filter( 'ppmwp_settings_page_content_tabs', array( $this, 'settings_tab' ), 10, 1 );
		}

		/**
		 * Add link to tabbed area within settings.
		 *
		 * @param  string $markup - Currently added content.
		 * @return string $markup - Appended content.
		 */
		public function settings_tab_link( $markup ) {
			return $markup . '<a href="#email-settings" class="nav-tab" data-tab-target=".ppm-email-settings">' . esc_attr__( 'Email templates', 'ppm-wp' ) . '</a>';
		}

		/**
		 * Add settings tab content to settings area
		 *
		 * @param  string $markup - Currently added content.
		 * @return string $markup - Appended content.
		 */
		public function settings_tab( $markup ) {
			ob_start(); ?>
			<div class="settings-tab ppm-email-settings">
				<table class="form-table">
					<tbody>
						<?php self::render_email_template_settings(); ?>
					</tbody>
				</table>
			</div>
			<?php
			return $markup . ob_get_clean();
		}

		/**
		 * Display settings markup for email tempplates.
		 *
		 * @return void
		 */
		public static function render_email_template_settings() {
			$ppm = ppm_wp();
			?>
				
				<div style="position: fixed; left: 980px; width: 260px; border-left: 1px solid #c3c4c7; padding: 20px;">
					<div style="position: sticky;">
					<p class="description"><?php esc_html_e( 'The following tags are available for use in all email template fields.', 'ppm-wp' ); ?><br><br>
						<b><?php esc_html_e( 'Available tags:', 'ppm-wp' ); ?></b><br>
						{home_url} <i><?php esc_html_e( '- Site URL', 'ppm-wp' ); ?></i><br>
						{site_name} <i><?php esc_html_e( '- Site Name', 'ppm-wp' ); ?></i><br>
						{user_login_name} <i><?php esc_html_e( '- User Login Name', 'ppm-wp' ); ?></i><br>
						{user_first_name} <i><?php esc_html_e( '- User First Name', 'ppm-wp' ); ?></i><br>
						{user_last_name} <i><?php esc_html_e( '- User Last Name', 'ppm-wp' ); ?></i><br>
						{user_display_name} <i><?php esc_html_e( '- User Display Name', 'ppm-wp' ); ?></i><br>
						{admin_email} <i><?php esc_html_e( '- From email address / site admin email', 'ppm-wp' ); ?></i><br>
						{blogname} <i><?php esc_html_e( '- Blog Name', 'ppm-wp' ); ?></i><br>    
						{reset_or_continue} <i><?php esc_html_e( '- Reset/Continue Message', 'ppm-wp' ); ?></i><br>
						{reset_url} <i><?php esc_html_e( '- Reset URL', 'ppm-wp' ); ?></i><br>
					</div>
				</div>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Account Unlocked Title', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_unlocked_email_title]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->user_unlocked_email_title ) ? $ppm->options->ppm_setting->user_unlocked_email_title :  \PPM_EmailStrings::get_default_string('user_unlocked_email_title') ); ?>" id="ppm-user_unlocked_email_title" style="float: left; display: block; width: 450px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Account proceed to reset message', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_unlocked_email_reset_message]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->user_unlocked_email_reset_message ) ? $ppm->options->ppm_setting->user_unlocked_email_reset_message :  \PPM_EmailStrings::get_default_string('user_unlocked_email_reset_message') ); ?>" id="ppm-user_email_reset_message" style="float: left; display: block; width: 450px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Account continue as normal', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_unlocked_email_continue_message]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->user_unlocked_email_continue_message ) ? $ppm->options->ppm_setting->user_unlocked_email_continue_message :  \PPM_EmailStrings::get_default_string('user_unlocked_email_continue_message') ); ?>"  id="ppm-user_unlocked_email_continue_message" style="float: left; display: block; width: 450px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-clear-history">
							<?php esc_html_e( 'User Account Unlocked Message', 'ppm-wp' ); ?>
						</label>
					</th>
					<td style="padding-right: 15px;">
						<fieldset>
							<?php
							$message   = isset( $ppm->options->ppm_setting->user_unlocked_email_body ) ? $ppm->options->ppm_setting->user_unlocked_email_body : \PPM_EmailStrings::default_message_contents( 'user_unlocked' );
							$content   = $message;
							$editor_id = '_ppm_options_user_unlocked_email_body';
							$settings  = array(
								'media_buttons' => false,
								'editor_height' => 200,
								'textarea_name' => '_ppm_options[user_unlocked_email_body]',
							);
							wp_editor( $content, $editor_id, $settings );
							?>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Account Unblocked Title', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_unblocked_email_title]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->user_unblocked_email_title ) ? $ppm->options->ppm_setting->user_unblocked_email_title :  \PPM_EmailStrings::get_default_string('user_unblocked_email_title') ); ?>" id="ppm-user_unblocked_email_title" style="float: left; display: block; width: 450px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Account Unblocked proceed to reset message', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_unblocked_email_reset_message]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->user_unblocked_email_reset_message ) ? $ppm->options->ppm_setting->user_unblocked_email_reset_message :  \PPM_EmailStrings::get_default_string('user_unblocked_email_reset_message') ); ?>" id="ppm-user_email_reset_message" style="float: left; display: block; width: 450px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Account Unblocked continue as normal', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_unblocked_email_continue_message]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->user_unblocked_email_continue_message ) ? $ppm->options->ppm_setting->user_unblocked_email_continue_message :  \PPM_EmailStrings::get_default_string('user_unblocked_email_continue_message') ); ?>"  id="ppm-user_unblocked_email_continue_message" style="float: left; display: block; width: 450px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-clear-history">
							<?php esc_html_e( 'User Account Unblocked Message', 'ppm-wp' ); ?>
						</label>
					</th>
					<td style="padding-right: 15px;">
						<fieldset>
							<?php
							$message   = isset( $ppm->options->ppm_setting->user_unblocked_email_body ) ? $ppm->options->ppm_setting->user_unblocked_email_body : \PPM_EmailStrings::default_message_contents( 'user_unblocked' );
							$content   = $message;
							$editor_id = '_ppm_options_user_unblocked_email_body';
							$settings  = array(
								'media_buttons' => false,
								'editor_height' => 200,
								'textarea_name' => '_ppm_options[user_unblocked_email_body]',
							);
							wp_editor( $content, $editor_id, $settings );
							?>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Reset on next login title', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_reset_next_login_title]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->user_reset_next_login_title ) ? $ppm->options->ppm_setting->user_reset_next_login_title :  \PPM_EmailStrings::get_default_string('user_reset_next_login_title') ); ?>"  id="ppm-user_reset_next_login_title" style="float: left; display: block; width: 450px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-clear-history">
							<?php esc_html_e( 'User Reset on Next Login Message', 'ppm-wp' ); ?>
						</label>
					</th>
					<td style="padding-right: 15px;">
						<fieldset>
							<?php
							$message   = isset( $ppm->options->ppm_setting->user_reset_next_login_email_body ) ? $ppm->options->ppm_setting->user_reset_next_login_email_body : \PPM_EmailStrings::default_message_contents( 'reset_next_login' );
							$content   = $message;
							$editor_id = '_ppm_options_user_reset_next_login_email_body';
							$settings  = array(
								'media_buttons' => false,
								'editor_height' => 200,
								'textarea_name' => '_ppm_options[user_reset_next_login_email_body]',
							);
							wp_editor( $content, $editor_id, $settings );
							?>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Password reset title', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_delayed_reset_title]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->user_delayed_reset_title ) ? $ppm->options->ppm_setting->user_delayed_reset_title :  \PPM_EmailStrings::get_default_string('user_delayed_reset_title') ); ?>"  id="ppm-user_delayed_reset_title" style="float: left; display: block; width: 450px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-clear-history">
							<?php esc_html_e( 'User Delayed Reset Message', 'ppm-wp' ); ?>
						</label>
					</th>
					<td style="padding-right: 15px;">
						<fieldset>
							<?php
							$message   = isset( $ppm->options->ppm_setting->user_delayed_reset_email_body ) ? $ppm->options->ppm_setting->user_delayed_reset_email_body : \PPM_EmailStrings::default_message_contents( 'global_delayed_reset' );
							$content   = $message;
							$editor_id = '_ppm_options_user_delayed_reset_email_body';
							$settings  = array(
								'media_buttons' => false,
								'editor_height' => 200,
								'textarea_name' => '_ppm_options[user_delayed_reset_email_body]',
							);
							wp_editor( $content, $editor_id, $settings );
							?>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-clear-history">
							<?php esc_html_e( 'User Reset Message', 'ppm-wp' ); ?>
						</label>
					</th>
					<td style="padding-right: 15px;">
						<fieldset>
							<?php
							$message   = isset( $ppm->options->ppm_setting->user_reset_email_body ) ? $ppm->options->ppm_setting->user_reset_email_body : \PPM_EmailStrings::default_message_contents( 'password_reset' );
							$content   = $message;
							$editor_id = '_ppm_options_user_reset_email_body';
							$settings  = array(
								'media_buttons' => false,
								'editor_height' => 200,
								'textarea_name' => '_ppm_options[user_reset_email_body]',
							);
							wp_editor( $content, $editor_id, $settings );
							?>
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Password Expired email title', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_password_expired_title]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->user_password_expired_title ) ? $ppm->options->ppm_setting->user_password_expired_title :  \PPM_EmailStrings::get_default_string('user_password_expired_title') ); ?>"  id="ppm-user_password_expired_title" style="float: left; display: block; width: 450px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-clear-history">
							<?php esc_html_e( 'User Password Expired Message', 'ppm-wp' ); ?>
						</label>
					</th>
					<td style="padding-right: 15px;">
						<fieldset>
							<?php
							$message   = isset( $ppm->options->ppm_setting->user_password_expired_email_body ) ? $ppm->options->ppm_setting->user_password_expired_email_body : \PPM_EmailStrings::default_message_contents( 'password_expired' );
							$content   = $message;
							$editor_id = '_ppm_options_user_password_expired_email_body';
							$settings  = array(
								'media_buttons' => false,
								'editor_height' => 200,
								'textarea_name' => '_ppm_options[user_password_expired_email_body]',
							);
							wp_editor( $content, $editor_id, $settings );
							?>
						</fieldset>
					</td>
				</tr>
			<?php
		}
	}
}
