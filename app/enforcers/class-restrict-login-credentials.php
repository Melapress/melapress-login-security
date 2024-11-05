<?php
/**
 * Melapress Login Security Restrict_Login_Credentials Class.
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

/**
 * Restrict login to email only.
 *
 * @since @since 2.0.0
 */
class Restrict_Login_Credentials {

	/**
	 * Main constructor.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'ppm_settings_additional_settings', array( __CLASS__, 'settings_markup' ), 20, 2 );
		add_filter( 'authenticate', array( __CLASS__, 'check_desired_credentials' ), 999999, 3 );
		add_action( 'ppm_message_settings_markup_footer', array( __CLASS__, 'add_template_settings' ), 70 );
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
	public static function add_template_settings( $mls_settings ) {
		?>
		<table class="form-table has-sticky-bar">
			<tbody>

				<tr valign="top">
					<h3><?php esc_html_e( 'User attempts to log in using restricted credentials', 'melapress-login-security' ); ?></h3>
					<p class="description"><?php esc_html_e( 'This warning is shown when a user attempts to log in with credentials restricted by an active Login Security Policy.', 'melapress-login-security' ); ?></p>
				</tr>

				<tr valign="top">
					<th scope="row">
						<?php esc_html_e( 'Message', 'melapress-login-security' ); ?>
					</th>
					<td style="padding-right: 15px;">
						<fieldset>
							<?php
							$content   = \MLS\EmailAndMessageStrings::get_email_template_setting( 'restrict_logins_prompt_failure_message' );
							$editor_id = '_ppm_options_restrict_logins_prompt_failure_message';
							$settings  = array(
								'media_buttons' => false,
								'editor_height' => 200,
								'textarea_name' => '_ppm_options[restrict_logins_prompt_failure_message]',
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
	 * Check if correct user name is used.
	 *
	 * @param \WP_User $user - User to check.
	 * @param string   $username - Username.
	 * @param string   $password - Password.
	 *
	 * @return \WP_User|\WP_Error
	 *
	 * @since 2.0.0
	 */
	public static function check_desired_credentials( $user, $username, $password ) {
		if ( ! isset( $user->roles ) ) {
			return $user;
		}

		if ( \MLS_Core::is_user_exempted( $user->ID ) ) {
			return $user;
		}

		$role_options = OptionsHelper::get_preferred_role_options( $user->roles );
		$type         = $role_options->restrict_login_credentials;

		if ( 'default' !== $role_options->restrict_login_credentials ) {
			$error_content = \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'restrict_logins_prompt_failure_message' ), $user->ID );
			$error_message = new \WP_Error( 'ppm_login_error', $error_content );

			if ( 'username-only' === $type ) {
				$result = \wp_authenticate_username_password( $error_message, $username, $password );
				if ( is_wp_error( $result ) ) {
					return $error_message;
				}
			} elseif ( 'email-only' === $type ) {
				$result = \wp_authenticate_email_password( $error_message, $username, $password );
				if ( is_wp_error( $result ) ) {
					return $error_message;
				}
			}
		}

		return $user;
	}

	/**
	 * Get class instance
	 *
	 * @return object - Instance.
	 *
	 * @since 2.0.0
	 */
	public static function get_instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Add markup to admin area.
	 *
	 * @param string $markup - Existing markup.
	 * @param object $settings_tab - New markup.
	 *
	 * @return string final markup.
	 *
	 * @since 2.0.0
	 */
	public static function settings_markup( $markup, $settings_tab ) {
		ob_start();
		?>
			<!-- Inactive Users Setting -->
			<tr class="setting-heading user-login-policies-heading" valign="top">
				<th scope="row">
					<h3 class="mt-40"><?php esc_html_e( 'User login policies', 'melapress-login-security' ); ?></h3>
				</th>
			</tr>	

			<tr valign="top">
				<th scope="row">
					<?php esc_attr_e( 'Restrict username/email address login', 'melapress-login-security' ); ?>
				</th>
				<td>
					<fieldset>
						<p class="description"><?php esc_attr_e( 'Use this setting to specify what the users can use to log in. Available options include either their username or email address, username only, or email address only.', 'melapress-login-security' ); ?></p><br>
						<span style="display: inline-table;">
							<input type="radio" id="default" name="_ppm_options[restrict_login_credentials]" value="default" <?php checked( $settings_tab->restrict_login_credentials, 'default' ); ?>>
							<label for="default"><?php esc_attr_e( 'Users can log in with either their username or email address', 'melapress-login-security' ); ?></label><br>
							<input type="radio" id="email-only" name="_ppm_options[restrict_login_credentials]" value="email-only" <?php checked( $settings_tab->restrict_login_credentials, 'email-only' ); ?>>
							<label for="email-only"><?php esc_attr_e( 'Users can log in with their email address only', 'melapress-login-security' ); ?></label><br>
							<input type="radio" id="username-only" name="_ppm_options[restrict_login_credentials]" value="username-only" <?php checked( $settings_tab->restrict_login_credentials, 'username-only' ); ?>>
							<label for="username-only"><?php esc_attr_e( 'Users can log in with their username only', 'melapress-login-security' ); ?></label><br>
						</span>
					</fieldset>

					<fieldset class="restrict-message-field">
						<div style="margin-top: 30px;">
							<p class="description" style="margin-bottom: 10px; display: block;">
								<?php
									$messages_settings = '<a href="' . add_query_arg( 'page', 'mls-settings#message-settings', network_admin_url( 'admin.php' ) ) . '"> ' . __( 'User notification templates', 'ppw-wp' ) . '</a>';
								?>
								<?php
								/* translators: %s: link to settings. */
								echo wp_sprintf( esc_html__( 'To customize the notification displayed to users should they fail the above check, please visit the %s plugin settings.', 'melapress-login-security' ), wp_kses_post( $messages_settings ) );
								?>
							</p>
						</div>
					</fieldset>
				</td>
			</tr>
		<?php
		return $markup . ob_get_clean();
	}
}