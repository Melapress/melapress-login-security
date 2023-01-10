<?php
/**
 * @package WordPress
 * @subpackage wpassword
 */

use \PPMWP\Helpers\OptionsHelper;

if ( ! class_exists( 'PPM_Email_Settings' ) ) {

	/**
	 * Manipulate Users' Password History
	 */
	class PPM_Email_Settings {

		/**
		 * Array of setting names and default strings for each.
		 *
		 * @var array
		 */
		public static $default_strings = array(
			'user_unlocked_email_title'             => '[{blogname}] Account Unlocked',
			'user_unlocked_email_reset_message'     => 'Please visit the following URL to reset your password:',
			'user_unlocked_email_continue_message'  => 'You may continue to login as normal',
			'user_unblocked_email_title'            => '[{blogname}] Account logins unblocked',
			'user_unblocked_email_reset_message'    => 'Please visit the following URL to reset your password:',
			'user_unblocked_email_continue_message' => 'You may continue to login as normal',
			'user_reset_next_login_title'           => '[{blogname}] Password Reset',
			'user_delayed_reset_title'              => '[{blogname}] Password Reset',
			'user_password_expired_title'           => '[{blogname}] Password Expired',
		);

		/**
		 * Get default string for desired setting.
		 *
		 * @param string $wanted - Desired string.
		 * @return string|bool - Located string, or false.
		 */
		public static function get_default_string( $wanted ) {
			return isset( self::$default_strings[ $wanted ] ) ? self::$default_strings[ $wanted ] : false;
		}

		/**
		 * Neat holder for default email body texts.
		 *
		 * @param string] $template - Desired template.
		 * @return string - Message text.
		 */
		public static function default_message_contents( $template ) {

			$message = '';

			if ( 'user_unlocked' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your user account has been unlocked by the website administrator. Below are the details:', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n";
				$message .= "\n" . '{reset_or_continue}' . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on ', 'ppm-wp' ) . '{admin_email}' . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'user_unblocked' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your user account has been unblocked from further login attempts by the website administrator. Below are the details:', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n";
				$message .= "\n" . '{reset_or_continue}' . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on ', 'ppm-wp' ) . '{admin_email}' . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'reset_next_login' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your user password was reset by the website administrator. Below are the details:', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n\n";
				$message .= __( 'You will be asked to reset your password when you next login. Otherwise, you can visit the following URL to reset your password: ', 'ppm-wp' ) . '{reset_url}' . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on ', 'ppm-wp' ) . '{admin_email}' . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'global_delayed_reset' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your user password was reset by the website administrator. Below are the details:', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n\n";
				$message .= __( 'Please be aware your password has been reset by the sites administrator and you will be required to provide a new one upon next login. If you have any questions or require assistance contact your website administrator on ', 'ppm-wp' ) . '{admin_email}' . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'password_expired' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Your password for the user {user_login_name} on the website {home_url} has expired.', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Please visit the following URL to reset your password: {reset_url}', 'ppm-wp' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on {admin_email}.', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";

			} elseif ( 'password_reset' === $template ) {
				$message  = __( 'Hello', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Website: ', 'ppm-wp' ) . '{home_url}' . "\n";
				$message .= __( 'Username: ', 'ppm-wp' ) . '{user_login_name}' . "\n\n";
				$message .= __( 'Please visit the following URL to reset your password: {reset_url}', 'ppm-wp' ) . "\n\n";
				$message .= __( 'If you have any questions or require assistance contact your website administrator on {admin_email}.', 'ppm-wp' ) . "\n\n";
				$message .= __( 'Thank you. ', 'ppm-wp' ) . "\n";
			}

			return $message;

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
						{home_url} <i>- Site URL</i><br>
						{site_name} <i>- Site Name</i><br>
						{user_login_name} <i>- User Login Name</i><br>
						{user_first_name} <i>- User First Name</i><br>
						{user_last_name} <i>- User Last Name</i><br>
						{user_display_name} <i>- User Display Name</i><br>
						{admin_email} <i>- Site Admin Email</i><br>
						{blogname} <i>- Blog Name</i><br>    
						{reset_or_continue} <i>- Reset/Continue Message</i><br>
						{reset_url} <i>- Reset URL</i><br>
					</div>
				</div>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
							<?php esc_html_e( 'User Account Unlocked Title', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<input type="text" name="_ppm_options[user_unlocked_email_title]" value="<?php esc_attr_e( isset( $ppm->options->ppm_setting->user_unlocked_email_title ) ? $ppm->options->ppm_setting->user_unlocked_email_title : self::$default_strings['user_unlocked_email_title'] ); ?>" id="ppm-user_unlocked_email_title" style="float: left; display: block; width: 450px;" />
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
							<input type="text" name="_ppm_options[user_unlocked_email_reset_message]" value="<?php esc_attr_e( isset( $ppm->options->ppm_setting->user_unlocked_email_reset_message ) ? $ppm->options->ppm_setting->user_unlocked_email_reset_message : self::$default_strings['user_unlocked_email_reset_message'] ); ?>" id="ppm-user_email_reset_message" style="float: left; display: block; width: 450px;" />
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
							<input type="text" name="_ppm_options[user_unlocked_email_continue_message]" value="<?php esc_attr_e( isset( $ppm->options->ppm_setting->user_unlocked_email_continue_message ) ? $ppm->options->ppm_setting->user_unlocked_email_continue_message : self::$default_strings['user_unlocked_email_continue_message'] ); ?>"  id="ppm-user_unlocked_email_continue_message" style="float: left; display: block; width: 450px;" />
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
							$message   = isset( $ppm->options->ppm_setting->user_unlocked_email_body ) ? $ppm->options->ppm_setting->user_unlocked_email_body : self::default_message_contents( 'user_unlocked' );
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
							<input type="text" name="_ppm_options[user_unblocked_email_title]" value="<?php esc_attr_e( isset( $ppm->options->ppm_setting->user_unblocked_email_title ) ? $ppm->options->ppm_setting->user_unblocked_email_title : self::$default_strings['user_unblocked_email_title'] ); ?>" id="ppm-user_unblocked_email_title" style="float: left; display: block; width: 450px;" />
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
							<input type="text" name="_ppm_options[user_unblocked_email_reset_message]" value="<?php esc_attr_e( isset( $ppm->options->ppm_setting->user_unblocked_email_reset_message ) ? $ppm->options->ppm_setting->user_unblocked_email_reset_message : self::$default_strings['user_unblocked_email_reset_message'] ); ?>" id="ppm-user_email_reset_message" style="float: left; display: block; width: 450px;" />
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
							<input type="text" name="_ppm_options[user_unblocked_email_continue_message]" value="<?php esc_attr_e( isset( $ppm->options->ppm_setting->user_unblocked_email_continue_message ) ? $ppm->options->ppm_setting->user_unblocked_email_continue_message : self::$default_strings['user_unblocked_email_continue_message'] ); ?>"  id="ppm-user_unblocked_email_continue_message" style="float: left; display: block; width: 450px;" />
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
							$message   = isset( $ppm->options->ppm_setting->user_unblocked_email_body ) ? $ppm->options->ppm_setting->user_unblocked_email_body : self::default_message_contents( 'user_unblocked' );
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
							<input type="text" name="_ppm_options[user_reset_next_login_title]" value="<?php esc_attr_e( isset( $ppm->options->ppm_setting->user_reset_next_login_title ) ? $ppm->options->ppm_setting->user_reset_next_login_title : self::$default_strings['user_reset_next_login_title'] ); ?>"  id="ppm-user_reset_next_login_title" style="float: left; display: block; width: 450px;" />
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
							$message   = isset( $ppm->options->ppm_setting->user_reset_next_login_email_body ) ? $ppm->options->ppm_setting->user_reset_next_login_email_body : self::default_message_contents( 'reset_next_login' );
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
							<input type="text" name="_ppm_options[user_delayed_reset_title]" value="<?php esc_attr_e( isset( $ppm->options->ppm_setting->user_delayed_reset_title ) ? $ppm->options->ppm_setting->user_delayed_reset_title : self::$default_strings['user_delayed_reset_title'] ); ?>"  id="ppm-user_delayed_reset_title" style="float: left; display: block; width: 450px;" />
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
							$message   = isset( $ppm->options->ppm_setting->user_delayed_reset_email_body ) ? $ppm->options->ppm_setting->user_delayed_reset_email_body : self::default_message_contents( 'global_delayed_reset' );
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
							$message   = isset( $ppm->options->ppm_setting->user_reset_email_body ) ? $ppm->options->ppm_setting->user_reset_email_body : self::default_message_contents( 'password_reset' );
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
							<input type="text" name="_ppm_options[user_password_expired_title]" value="<?php esc_attr_e( isset( $ppm->options->ppm_setting->user_password_expired_title ) ? $ppm->options->ppm_setting->user_password_expired_title : self::$default_strings['user_password_expired_title'] ); ?>"  id="ppm-user_password_expired_title" style="float: left; display: block; width: 450px;" />
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
							$message   = isset( $ppm->options->ppm_setting->user_password_expired_email_body ) ? $ppm->options->ppm_setting->user_password_expired_email_body : self::default_message_contents( 'password_expired' );
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

		/**
		 * Replace our tags with the relevent data when sending the email.
		 *
		 * @param string $input - Original text.
		 * @param string $user_id - Applicable user ID.
		 * @param array  $args - Extra args.
		 * @return string $final_output - Final message text.
		 */
		public static function replace_email_strings( $input = '', $user_id = '', $args = array() ) {

			$ppm  = ppm_wp();
			$user = get_userdata( $user_id );

			// Prepare email details.
			$from_email = $ppm->options->ppm_setting->from_email ? $ppm->options->ppm_setting->from_email : 'wordpress@' . str_ireplace( 'www.', '', parse_url( network_site_url(), PHP_URL_HOST ) );

			// These are the strings we are going to search for, as well as there respective replacements.
			$replacements = array(
				'{home_url}'          => esc_url( get_bloginfo( 'url' ) ),
				'{site_name}'         => sanitize_text_field( get_bloginfo( 'name' ) ),
				'{user_login_name}'   => sanitize_text_field( $user->user_login ),
				'{user_first_name}'   => sanitize_text_field( $user->firstname ),
				'{user_last_name}'    => sanitize_text_field( $user->lastname ),
				'{user_display_name}' => sanitize_text_field( $user->display_name ),
				'{admin_email}'       => $from_email,
				'{blogname}'          => ( is_multisite() ) ? get_network()->site_name : wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				'{reset_or_continue}' => ( ! empty( $args ) && isset( $args['reset_or_continue'] ) ) ? sanitize_text_field( $args['reset_or_continue'] ) : '',
				'{reset_url}'         => ( ! empty( $args ) && isset( $args['reset_url'] ) ) ? sanitize_text_field( $args['reset_url'] ) : '',
			);

			$final_output = str_replace( array_keys( $replacements ), array_values( $replacements ), $input );
			return $final_output;
		}
	}
}
