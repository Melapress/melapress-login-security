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

if ( ! class_exists( '\MLS\Restrict_Login_Credentials' ) ) {

	/**
	 * Restrict login to email only.
	 *
	 * @since @since 2.0.0
	 */
	class Restrict_Login_Credentials {

		/**
		 * WP 2FA plugin slug.
		 *
		 * @var string
		 */
		private const WP2FA_PLUGIN_SLUG = 'wp-2fa';

		/**
		 * WP 2FA plugin basename.
		 *
		 * @var string
		 */
		private const WP2FA_PLUGIN_BASENAME = 'wp-2fa/wp-2fa.php';

		/**
		 * Admin-post action used to install/activate/open WP 2FA.
		 *
		 * @var string
		 */
		private const WP2FA_INSTALL_ACTION = 'mls_install_or_open_wp2fa';

		/**
		 * Init hooks.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			add_filter( 'mls_login_policies_settings', array( __CLASS__, 'settings_markup' ), 20, 2 );
			add_filter( 'authenticate', array( __CLASS__, 'check_desired_credentials' ), 999999, 3 );
			add_action( 'ppm_message_settings_markup_footer', array( __CLASS__, 'add_template_settings' ), 70 );
			add_action( 'admin_post_' . self::WP2FA_INSTALL_ACTION, array( __CLASS__, 'handle_wp2fa_install_or_open' ) );
			add_action( 'admin_notices', array( __CLASS__, 'render_wp2fa_status_notice' ) );
			add_action( 'network_admin_notices', array( __CLASS__, 'render_wp2fa_status_notice' ) );
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
						<h3><?php esc_html_e( 'Restricted credentials used', 'melapress-login-security' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Shown when a user attempts to log in using credentials blocked by an active Login Security Policy.', 'melapress-login-security' ); ?></p>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Message', 'melapress-login-security' ); ?>
						</th>
						<td style="padding-right: 15px;">
							<fieldset>
								<?php
								$content   = \MLS\EmailAndMessageStrings::get_email_template_setting( 'restrict_logins_prompt_failure_message' );
								$editor_id = 'mls_options_restrict_logins_prompt_failure_message';
								$settings  = array(
									'media_buttons' => false,
									'editor_height' => 200,
									'textarea_name' => 'mls_options[restrict_logins_prompt_failure_message]',
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

			if ( ! ( \property_exists( $role_options, 'restrict_login_credentials' ) ) ) {
				return $user;
			}

			$type         = $role_options->restrict_login_credentials;

			if ( 'default' !== $role_options->restrict_login_credentials ) {
				$error_content = \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'restrict_logins_prompt_failure_message' ), $user->ID );
				$error_message = new \WP_Error( 'ppm_login_error', $error_content );

				if ( 'username-only' === $type ) {
					$result = \wp_authenticate_username_password( $error_message, $username, $password );
					if ( is_wp_error( $result ) ) {
						/**
						 * Fire of action for others to observe.
						 */
						do_action( 'mls_user_login_blocked_due_to_wrong_credentials', $user->ID, $type );
						return $error_message;
					}
				} elseif ( 'email-only' === $type ) {
					$result = \wp_authenticate_email_password( $error_message, $username, $password );
					if ( is_wp_error( $result ) ) {
						/**
						 * Fire of action for others to observe.
						 */
						do_action( 'mls_user_login_blocked_due_to_wrong_credentials', $user->ID, $type );
						return $error_message;
					}
				}
			}

			return $user;
		}

		/**
		 * Whether WP 2FA is installed.
		 *
		 * @return bool
		 */
		private static function is_wp2fa_installed() {
			return file_exists( WP_PLUGIN_DIR . '/wp-2fa/wp-2fa.php' );
		}

		/**
		 * Get WP 2FA policies URL.
		 *
		 * @param bool $network_context Whether URL should be network-admin URL.
		 *
		 * @return string
		 */
		private static function get_wp2fa_policies_url( $network_context = false ) {
			$admin_url = $network_context ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' );
			return add_query_arg( 'page', 'wp-2fa-policies', $admin_url );
		}

		/**
		 * Get MLS policies URL.
		 *
		 * @param bool $network_context Whether URL should be network-admin URL.
		 *
		 * @return string
		 */
		private static function get_mls_policies_url( $network_context = false ) {
			$admin_url = $network_context ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' );
			return add_query_arg( 'page', 'mls-policies', $admin_url );
		}

		/**
		 * Handle install/activate/open flow for WP 2FA.
		 *
		 * @return void
		 */
		public static function handle_wp2fa_install_or_open() {
			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_die( esc_html__( 'You do not have permission to install plugins.', 'melapress-login-security' ) );
			}

			check_admin_referer( self::WP2FA_INSTALL_ACTION );

			$network_context = isset( $_GET['mls_network'] ) && '1' === sanitize_key( wp_unslash( $_GET['mls_network'] ) );
			$fallback_url    = self::get_mls_policies_url( $network_context );

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$status = 'opened';

			if ( ! self::is_wp2fa_installed() ) {
				$api = plugins_api(
					'plugin_information',
					array(
						'slug'   => self::WP2FA_PLUGIN_SLUG,
						'fields' => array(
							'sections' => false,
						),
					)
				);

				if ( is_wp_error( $api ) || empty( $api->download_link ) ) {
					wp_safe_redirect( add_query_arg( 'mls_wp2fa_status', 'install-failed', $fallback_url ) );
					exit;
				}

				$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
				$result   = $upgrader->install( $api->download_link );

				if ( is_wp_error( $result ) || ! $result || ! self::is_wp2fa_installed() ) {
					wp_safe_redirect( add_query_arg( 'mls_wp2fa_status', 'install-failed', $fallback_url ) );
					exit;
				}

				$status = 'installed';
			}

			if ( ! is_plugin_active( self::WP2FA_PLUGIN_BASENAME ) ) {
				$activation_result = activate_plugin( self::WP2FA_PLUGIN_BASENAME, '', $network_context );
				if ( is_wp_error( $activation_result ) ) {
					wp_safe_redirect( add_query_arg( 'mls_wp2fa_status', 'activate-failed', $fallback_url ) );
					exit;
				}

				$status = 'installed' === $status ? 'installed-activated' : 'activated';
			}

			wp_safe_redirect( add_query_arg( 'mls_wp2fa_status', $status, self::get_wp2fa_policies_url( $network_context ) ) );
			exit;
		}

		/**
		 * Render admin notice for WP 2FA action statuses.
		 *
		 * @return void
		 */
		public static function render_wp2fa_status_notice() {
			$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status = isset( $_GET['mls_wp2fa_status'] ) ? sanitize_key( wp_unslash( $_GET['mls_wp2fa_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 'wp-2fa-policies' !== $page ) {
				return;
			}

			if ( ! in_array( $status, array( 'opened', 'activated', 'installed', 'installed-activated' ), true ) ) {
				return;
			}

			$message = __( 'WP 2FA is ready. Configure your two-factor authentication policies below.', 'melapress-login-security' );

			if ( 'activated' === $status ) {
				$message = __( 'WP 2FA was activated successfully. Configure your two-factor authentication policies below.', 'melapress-login-security' );
			} elseif ( 'installed' === $status || 'installed-activated' === $status ) {
				$message = __( 'WP 2FA was installed and activated successfully. Configure your two-factor authentication policies below.', 'melapress-login-security' );
			}

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
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
			$network_context = is_network_admin();
			$wp2fa_installed = self::is_wp2fa_installed();
			$action_url      = add_query_arg(
				array(
					'action'      => self::WP2FA_INSTALL_ACTION,
					'mls_network' => $network_context ? '1' : '0',
				),
				admin_url( 'admin-post.php' )
			);

			$action_url = wp_nonce_url( $action_url, self::WP2FA_INSTALL_ACTION );
			$status     = isset( $_GET['mls_wp2fa_status'] ) ? sanitize_key( wp_unslash( $_GET['mls_wp2fa_status'] ) ) : '';

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
								<input type="radio" id="default" name="mls_options[restrict_login_credentials]" value="default" <?php checked( $settings_tab->restrict_login_credentials, 'default' ); ?>>
								<label for="default"><?php esc_attr_e( 'Users can log in with either their username or email address', 'melapress-login-security' ); ?></label><br>
								<input type="radio" id="email-only" name="mls_options[restrict_login_credentials]" value="email-only" <?php checked( $settings_tab->restrict_login_credentials, 'email-only' ); ?>>
								<label for="email-only"><?php esc_attr_e( 'Users can log in with their email address only', 'melapress-login-security' ); ?></label><br>
								<input type="radio" id="username-only" name="mls_options[restrict_login_credentials]" value="username-only" <?php checked( $settings_tab->restrict_login_credentials, 'username-only' ); ?>>
								<label for="username-only"><?php esc_attr_e( 'Users can log in with their username only', 'melapress-login-security' ); ?></label><br>
							</span>
						</fieldset>

						<fieldset class="restrict-message-field">
							<div style="margin-top: 30px;">
								<p class="description" style="margin-bottom: 10px; display: block;">
									<?php
										$messages_settings = '<a href="' . add_query_arg( 'page', 'mls-settings#message-settings', network_admin_url( 'admin.php' ) ) . '"> ' . __( 'User notices templates', 'melapress-login-security' ) . '</a>';
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

				<tr valign="top">
					<th scope="row">
						<?php esc_attr_e( 'Add 2FA to user accounts', 'melapress-login-security' ); ?>
					</th>
					<td>
						<fieldset>
							<p class="description"><?php esc_html_e( 'Add an extra layer of login security by requiring users to verify their identity with a second authentication method.', 'melapress-login-security' ); ?></p>
							<p class="description"><?php esc_html_e( 'The button below installs and activates the free WP 2FA plugin, which you can use to configure two-factor authentication policies for your users.', 'melapress-login-security' ); ?></p>

							<p style="margin-top: 12px;">
								<a class="button button-secondary" href="<?php echo esc_url( $action_url ); ?>">
									<?php
									echo esc_html(
										$wp2fa_installed
											? __( 'View 2FA policies page', 'melapress-login-security' )
											: __( 'Install WP 2FA plugin', 'melapress-login-security' )
									);
									?>
								</a>
							</p>

							<?php if ( 'install-failed' === $status || 'activate-failed' === $status ) : ?>
								<p class="description" style="color: #b32d2e;">
									<?php esc_html_e( 'WP 2FA could not be installed or activated. Please try again, or install and activate WP 2FA manually from Plugins > Add New.', 'melapress-login-security' ); ?>
								</p>
							<?php endif; ?>
						</fieldset>
					</td>
				</tr>
			<?php
			return $markup . ob_get_clean();
		}
	}
}
