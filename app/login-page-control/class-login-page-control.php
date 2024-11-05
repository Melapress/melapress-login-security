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

if ( ! class_exists( '\MLS\Login_Page_Control' ) ) {

	/**
	 * Manipulate Users' Password History
	 *
	 * @since 2.0.0
	 */
	class Login_Page_Control {

		/**
		 * Keeps track of login page status.
		 *
		 * @var bool
		 *
		 * @since 2.0.0
		 */
		private $is_login_page;

		/**
		 * Keeps check of geo blocking status.
		 *
		 * @var bool
		 *
		 * @since 2.0.0
		 */
		private $is_geo_check_required;

		/**
		 * Keeps check of IP restriction status.
		 *
		 * @var bool
		 */
		private $is_ip_check_required;

		/**
		 * Init settings hooks.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function init() {
			$mls = melapress_login_security();
			if ( isset( $mls->options->mls_setting->custom_login_url ) && ! empty( $mls->options->mls_setting->custom_login_url ) ) {
				add_filter( 'site_url', array( $this, 'login_control_site_url' ), 10, 4 );
				add_filter( 'network_site_url', array( $this, 'login_control_network_site_url' ), 10, 3 );
				add_filter( 'wp_redirect', array( $this, 'login_control_wp_redirect' ), 10, 2 );
				add_filter( 'site_option_welcome_email_content', array( $this, 'welcome_email_content' ) );
				add_filter( 'user_request_action_email_content', array( $this, 'user_request_action_email_content' ), 999, 2 );
				remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
				add_filter( 'login_url', array( $this, 'login_control_login_url' ), 10, 3 );
			}


			if ( isset( $mls->options->mls_setting->enable_gdpr_banner ) && \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->enable_gdpr_banner ) ) {
				add_filter( 'login_footer', array( $this, 'insert_banner_markup' ), 50, 1 );
				add_shortcode( 'mls-gdpr-banner', array( $this, 'banner_shortcode' ) );
			}
		}

		/**
		 * Insert banner into login footer.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function insert_banner_markup() {
			echo self::gdpr_banner_markup( true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Allow banner to be shown via shortcode.
		 *
		 * @return string - Markup.
		 *
		 * @since 2.0.0
		 */
		public function banner_shortcode() {
			return self::gdpr_banner_markup();
		}

		/**
		 * Banner HTML markup.
		 *
		 * @param boolean $style_needed - Is needed.
		 *
		 * @return string - Markup.
		 *
		 * @since 2.0.0
		 */
		public static function gdpr_banner_markup( $style_needed = false ) {
			$mls                    = melapress_login_security();
			$current_banner_content = isset( $mls->options->mls_setting->gdpr_banner_message ) && ! empty( $mls->options->mls_setting->gdpr_banner_message ) ? $mls->options->mls_setting->gdpr_banner_message : esc_html__( 'By logging in to this website you are consenting this website to process the IP address and browser information for security purposes.', 'melapress-login-security' );
			$custom_css             = apply_filters( 'mls_gdpr_banner_styling', '' );

			$markup  = '<div id="mls_gdpr_banner">' . $current_banner_content . '</div>';
			$markup .= '<style type="text/css">';

			if ( $style_needed ) {
				$markup .= '				
				#mls_gdpr_banner {
					position: fixed;
					bottom: 0;
					background: #232323;
					color: #fff;
					text-align: center;
					padding: 10px 40px;
					width: 100vw;
				}
				@media all and (max-width: 768px) {
					#mls_gdpr_banner {
						position: absolute;
						padding: 10px 10px;
						width: calc( 100vw - 20px );
					}
				}
				';
			}
			$markup .= $current_banner_content;

			$markup .= $custom_css;

			$markup .= '</style>';

			return $markup;
		}

		/**
		 * Add link to tabbed area within settings.
		 *
		 * @param  string $markup - Currently added content.
		 *
		 * @return string $markup - Appended content.
		 *
		 * @since 2.0.0
		 */
		public function settings_tab_link( $markup ) {
			return $markup . '<a href="#integrations" class="nav-tab" data-tab-target=".ppm-integrations">' . esc_attr__( 'Integrations', 'melapress-login-security' ) . '</a>';
		}

		/**
		 * Add settings tab content to settings area
		 *
		 * @param  string $markup - Currently added content.
		 *
		 * @return string $markup - Appended content.
		 *
		 * @since 2.0.0
		 */
		public function settings_tab( $markup ) {
			ob_start(); ?>
			<div class="settings-tab ppm-integrations">
				<table class="form-table">
					<tbody>
						<?php self::render_integration_settings(); ?>
					</tbody>
				</table>
			</div>
			<?php
			return $markup . ob_get_clean();
		}

		/**
		 * Display settings markup for email templates.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function render_integration_settings() {
			$mls = melapress_login_security();
			?>
				
				<tr valign="top">
					<br>
					<h1><?php esc_html_e( 'Integrations', 'melapress-login-security' ); ?></h1>
					<p class="description"><?php esc_html_e( 'On this page you can manage the plugin’s integrations with other services and plugins.', 'melapress-login-security' ); ?></p>
					<br>
				</tr>

				<tr>
					<th><label><?php esc_html_e( 'IPLocate API Key:', 'melapress-login-security' ); ?></label></th>
					<td>
						<p class="description mb-10">
							<?php
							$link = '<a href="https://www.iplocate.io/" target="_blank">' . esc_html__( 'click here', 'melapress-login-security' ) . '</a>';
							echo wp_sprintf( __( 'IP checking is handled by IPLocate.io, please %s to get your own key.', 'melapress-login-security' ), $link );
							?>
						</p><br>
						<input type="text" id="iplocate_api_key" class="regular regular-text" name="_ppm_options[iplocate_api_key]" placeholder="" value="<?php echo esc_attr( isset( $mls->options->mls_setting->iplocate_api_key ) ? rtrim( $mls->options->mls_setting->iplocate_api_key, '/' ) : '' ); ?>" minlength="32">
					</td>
				</tr>
				<?php
		}

		/**
		 * Display settings markup for email templates.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function render_login_page_url_settings() {
			$mls = melapress_login_security();
			?>
				<br>
				<?php if ( is_multisite() ) { ?>
				<i class="description" style="max-width: none;">
					<?php esc_html_e( 'Please note: this will affect all sites on the network.', 'melapress-login-security' ); ?>
				</i>
				<?php } ?>
						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Login page URL', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo esc_url( trailingslashit( site_url() ) ); ?></p>
									<input type="text" name="_ppm_options[custom_login_url]" value="<?php echo esc_attr( isset( $mls->options->mls_setting->custom_login_url ) ? rtrim( $mls->options->mls_setting->custom_login_url, '/' ) : '' ); ?>" id="ppm-custom_login_url" style="float: left; display: block; width: 250px;" />
									<p style="display: inline-block; float: left; margin-right: 6px; margin-left: 6px;">/</p>
								</fieldset>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Redirect old login page URL to', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo esc_url( trailingslashit( site_url() ) ); ?></p>
									<input type="text" name="_ppm_options[custom_login_redirect]" value="<?php echo esc_attr( isset( $mls->options->mls_setting->custom_login_redirect ) ? rtrim( $mls->options->mls_setting->custom_login_redirect, '/' ) : '' ); ?>" id="ppm-custom_login_redirect" style="float: left; display: block; width: 250px;" />
									<p style="display: inline-block; float: left; margin-right: 6px; margin-left: 6px;">/</p>
									<br>
									<br>
									<p class="description">
										<?php esc_html_e( 'Redirect anyone who tries to access the default WordPress login page URL to the above configured URL.', 'melapress-login-security' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>

				<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function( $ ) {
					jQuery( 'body' ).on( 'click', '[name="_ppm_save"]', function ( e ) {
						
						if ( jQuery( '#ppm_enable_login_allowed_ips' ).is(':checked') ) {
							if ( '' == jQuery( '#restrict_login_allowed_ips' ).val() ) {
								e.preventDefault();
								jQuery( 'html' ).animate({
									scrollTop: jQuery( '#ppm_enable_login_allowed_ips' ).offset().top
								}, 800 );
								jQuery( '#restrict_login_allowed_ips_input' ).css( 'border-color', 'red' );
							} else {
								jQuery( '#restrict_login_allowed_ips_input' ).css( 'border-color', '#8c8f94' );
							}
						}
		
					});
				});
				//]]>
				</script>

				<table class="form-table">
					<tbody>
						<br>
						<h3><?php esc_html_e( 'Limit login page access by IP address(es)', 'melapress-login-security' ); ?></h3>
						<p class="description" style="max-width: none;">
							<?php esc_html_e( 'Use the below setting to limit access to the login page to an IP address or a number of IP addresses. All the other settings in this section are optional.', 'melapress-login-security' ); ?>
						</p>

						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'Restrict login page access by IP address(es)', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<label for="ppm_enable_login_allowed_ips">
										<input type="checkbox" id="ppm_enable_login_allowed_ips" name="_ppm_options[enable_login_allowed_ips]" data-toggle-target=".limit-ip-row" value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->enable_login_allowed_ips ) ); ?>>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr valign="top" class="limit-ip-row">
							<th scope="row">
								<?php esc_html_e( 'IP address(es)', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<p class="description">
										<?php esc_html_e( 'Please specify an IP address and click Add IP to add it to the list. Wildcards and IP address ranges are not supported. Only individual IP addresses.', 'melapress-login-security' ); ?>
									</p>
									<br>
									<input type="text" id="restrict_login_allowed_ips_input" placeholder="e.g. 192.168.1.26"><a href="#" class="button button-primary" id="add-restrict_login_allowed_ips">Add IP</a><div id="restrict_login_allowed_ips-userfacing"></div>
									<input type="text" id="restrict_login_allowed_ips" name="_ppm_options[restrict_login_allowed_ips]" class="hidden" value="<?php echo esc_attr( isset( $mls->options->mls_setting->restrict_login_allowed_ips ) ? rtrim( $mls->options->mls_setting->restrict_login_allowed_ips, '/' ) : '' ); ?>" >
									
								</fieldset>
							</td>
						</tr>

						<tr valign="top" class="limit-ip-row">
							<th scope="row">
								<?php esc_html_e( 'Redirect restricted IP address to', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo esc_url( trailingslashit( site_url() ) ); ?></p>
									<input type="text" name="_ppm_options[restrict_login_redirect_url]" value="<?php echo esc_attr( isset( $mls->options->mls_setting->restrict_login_redirect_url ) ? rtrim( $mls->options->mls_setting->restrict_login_redirect_url, '/' ) : '' ); ?>" id="ppm-restrict_login_redirect_url" style="float: left; display: block; width: 250px;" />
									<p style="display: inline-block; float: left; margin-right: 6px; margin-left: 6px;">/</p>
									<br>
									<br>
									<p class="description">
										<?php esc_html_e( 'By default when someone tries to access the login page from a non allowed IP address the web server responds with HTTP 403 (Resource is forbidden) status code. If you specify a URL above, the visitor will be redirected to this URL instead.', 'melapress-login-security' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>

						<tr valign="top" class="limit-ip-row">
							<th scope="row">
								<?php esc_html_e( 'Bypass IP restriction URL', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo esc_url( trailingslashit( site_url() ) ); ?></p>
									<input type="text" name="_ppm_options[restrict_login_bypass_slug]" value="<?php echo esc_attr( isset( $mls->options->mls_setting->restrict_login_bypass_slug ) ? rtrim( $mls->options->mls_setting->restrict_login_bypass_slug, '/' ) : '' ); ?>" id="ppm-restrict_login_bypass_slug" style="float: left; display: block; width: 250px;" />
									<p style="display: inline-block; float: left; margin-right: 6px; margin-left: 6px;">/</p>
									<br>
									<br>
									<p class="description">
										<?php esc_html_e( 'This is an optional fallback setting. You can specify a URL here and when this URL is accessed you will access the login page, even if it is from a restricted IP address listed above.', 'melapress-login-security' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
			<?php
		}

		/**
		 * Add settings markup.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function render_login_gdpr_settings() {
			$mls                    = melapress_login_security();
			$current_banner_content = isset( $mls->options->mls_setting->gdpr_banner_message ) && ! empty( $mls->options->mls_setting->gdpr_banner_message ) ? $mls->options->mls_setting->gdpr_banner_message : esc_html__( 'By logging in to this website you are consenting this website to process the IP address and browser information for security purposes.', 'melapress-login-security' );
			?>
				<br>
				<h3><?php esc_html_e( 'Show a consent message on the login page', 'melapress-login-security' ); ?></h3>
				<p class="description" style="max-width: none;">
					<?php esc_html_e( 'Enable this setting to add a login page notice advising users that their IP address will be processed by the plugin, thus making the process GDPR compliant.', 'melapress-login-security' ); ?>
				</p>

				<tr valign="top">
					<th scope="row">
						<?php esc_html_e( 'Enable consent message on login page', 'melapress-login-security' ); ?>
					</th>
					<td>
						<fieldset>
							<label for="ppm_enable_gdpr_banner">
								<input type="checkbox" id="ppm_enable_gdpr_banner" name="_ppm_options[enable_gdpr_banner]"
										value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->enable_gdpr_banner ) ); ?>>
							</label>
						</fieldset>
					</td>
				</tr>

				<tr valign="top" id="gdpr-row">
					<th scope="row">
						<?php esc_html_e( 'Consent message', 'melapress-login-security' ); ?>
					</th>
					<td>
						<fieldset>
							<textarea id="gdpr_banner_message" name="_ppm_options[gdpr_banner_message]" rows="2" cols="60"><?php echo wp_kses_post( $current_banner_content ); ?></textarea>
							<p class="description" style="margin-bottom: 10px; display: block;">
							<?php esc_html_e( 'Use the following shortcode to display this notice text on a page ', 'melapress-login-security' ); ?> <code style="text-wrap: nowrap;">[mls-gdpr-banner]</code>
							</p>
						</fieldset>
					</td>
				</tr>
				
			<?php
		}

		/**
		 * Add settings markup.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function render_login_geo_settings() {
			$mls              = melapress_login_security();
			$iplocate_api_key = isset( $mls->options->mls_setting->iplocate_api_key ) ? $mls->options->mls_setting->iplocate_api_key : false;

			$inactive_users_url = add_query_arg(
				array(
					'page' => 'mls-settings#integrations',
				),
				network_admin_url( 'admin.php' )
			);

			?>
				<br>
				<h3><?php esc_html_e( 'Block or allow access to the login page by countries', 'melapress-login-security' ); ?></h3>
				<p class="description" style="max-width: none;">
					<?php
						printf(
							/* translators: %s: link to notes. */
							esc_html__( 'Use the below setting to either block access to the login page for IP addresses from certain countries, or to restrict access to the login page to IP addresses from certain countries. To add a country enter its respective %1$1s in the field below. To use this feature you will need to provide an API key via %2$2s.', 'melapress-login-security' ),
							sprintf(
								'<a target="_blank" href="https://www.iso.org/obp/ui/#search/code/">%s</a>',
								esc_html__( 'ISO country code', 'melapress-login-security' )
							),
							sprintf(
								'<a target="_blank" href="%1$s">%2$s</a>',
								esc_url( $inactive_users_url ),
								esc_html__( 'integration settings', 'melapress-login-security' )
							)
						);
					?>
				</p>
				<br>
				
				<table class="form-table">
					<tbody>					
						<tr valign="top" 
						<?php
						if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) {
							echo 'class="disabled"'; }
						?>
						>
							<th scope="row">
								<?php esc_html_e( 'Country Codes:', 'melapress-login-security' ); ?>
							</th>
							<td>
								<input type="text" id="login_geo_countries_input" placeholder="e.g. MT"><a href="#" class="button button-primary" id="add-login_denied-countries">Add Country</a><div id="login_geo_countries-countries-userfacing"></div>
								<input type="text" id="login_geo_countries" name="_ppm_options[login_geo_countries]" class="hidden" value="<?php echo esc_attr( isset( $mls->options->mls_setting->login_geo_countries ) ? rtrim( $mls->options->mls_setting->login_geo_countries, '/' ) : '' ); ?>" >
							</td>
						</tr>

						<tr valign="top" 
						<?php
						if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) {
							echo 'class="disabled"'; }
						?>
						>
							<th scope="row">
								<?php esc_html_e( 'Action:', 'melapress-login-security' ); ?>
							</th>
							<td>
								<select id="login_geo_method" class="regular toggleable" name="_ppm_options[login_geo_method]" style="display: inline-block;">
									<option value="default" <?php selected( 'default', $mls->options->mls_setting->login_geo_method, true ); ?>><?php esc_html_e( 'Do nothing', 'melapress-login-security' ); ?></option>
									<option value="allow_only" <?php selected( 'allow_only', $mls->options->mls_setting->login_geo_method, true ); ?>><?php esc_html_e( 'Allow access from the above countries only', 'melapress-login-security' ); ?></option>
									<option value="deny_list" <?php selected( 'deny_list', $mls->options->mls_setting->login_geo_method, true ); ?>><?php esc_html_e( 'Block access from the above countries', 'melapress-login-security' ); ?></option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				
				<br>
				<strong style="font-size: 14px;"><?php esc_html_e( 'What should blocked users see?', 'melapress-login-security' ); ?></strong>

				<table class="form-table">
					<tbody>

						<tr valign="top" 
						<?php
						if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) {
							echo 'class="disabled"'; }
						?>
						>
							<th scope="row">
								<?php esc_html_e( 'Blocked user handling:', 'melapress-login-security' ); ?>
							</th>
							<td>
								<select id="login_geo_action" class="regular toggleable" name="_ppm_options[login_geo_action]" style="display: inline-block;">
									<option value="deny_to_url" <?php selected( 'deny_to_url', $mls->options->mls_setting->login_geo_action, true ); ?>><?php esc_html_e( 'Send blocked users to below URL', 'melapress-login-security' ); ?></option>
									<option value="deny_to_home" <?php selected( 'deny_to_home', $mls->options->mls_setting->login_geo_action, true ); ?>><?php esc_html_e( 'Send blocked users to homepage', 'melapress-login-security' ); ?></option>
								</select>
							</td>
						</tr>

						<tr valign="top" 
						<?php
						if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) {
							echo 'class="disabled"'; }
						?>
						>
							<th scope="row">
								<?php esc_html_e( 'Redirect requests to default login page to', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo esc_url( trailingslashit( site_url() ) ); ?></p>
									<input type="text" name="_ppm_options[login_geo_redirect_url]" value="<?php echo esc_attr( isset( $mls->options->mls_setting->login_geo_redirect_url ) ? rtrim( $mls->options->mls_setting->login_geo_redirect_url, '/' ) : '' ); ?>" id="ppm-custom_login_url" style="float: left; display: block; width: 250px;" />
									<p style="display: inline-block; float: left; margin-right: 6px; margin-left: 6px;">/</p>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>
				<?php
		}

		/**
		 * Manually load the login template where it would not typically wish to load.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		private function load_login_template() {
			global $pagenow;
			$pagenow = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			if ( ! defined( 'WP_USE_THEMES' ) ) {
				define( 'WP_USE_THEMES', true );
			}
			wp();
			if ( isset( $_SERVER['REQUEST_URI'] ) && $_SERVER['REQUEST_URI'] === $this->context_trailingslashit( str_repeat( '-/', 10 ) ) ) {
				$_SERVER['REQUEST_URI'] = $this->context_trailingslashit( '/wp-login-php/' );
			}
			include_once ABSPATH . WPINC . '/template-loader.php';
			die;
		}

		/**
		 * Simple checker function to determine if trailing slashes are needed based on user permalink setup.
		 *
		 * @return bool
		 *
		 * @since 2.0.0
		 */
		private function trailing_slashes_needed() {
			return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
		}

		/**
		 * Wraps or unwraps a slash where needed.
		 *
		 * @param  string $incoming_string - String to modify.
		 *
		 * @return string $incoming_string - Modified string.
		 *
		 * @since 2.0.0
		 */
		private function context_trailingslashit( $incoming_string ) {
			return $this->trailing_slashes_needed() ? trailingslashit( $incoming_string ) : untrailingslashit( $incoming_string );
		}

		/**
		 * Handles returning the needed slug for login page access.
		 *
		 * @return string $slug
		 *
		 * @since 2.0.0
		 */
		private function custom_login_slug() {
			$mls_setting = get_site_option( MLS_PREFIX . '_setting' );
			$slug        = isset( $mls_setting['custom_login_url'] ) ? $mls_setting['custom_login_url'] : '';

			if ( is_multisite() && is_plugin_active_for_network( MLS_BASENAME ) ) {
				return $slug;
			} else {
				return $slug;
			}
		}

		/**
		 * Handles returning the needed slug for login page access.
		 *
		 * @return string $slug
		 *
		 * @since 2.0.0
		 */
		private function restrict_login_bypass_slug() {
			$mls_setting = get_site_option( MLS_PREFIX . '_setting' );
			$slug        = isset( $mls_setting['restrict_login_bypass_slug'] ) ? $mls_setting['restrict_login_bypass_slug'] : '';

			if ( is_multisite() && is_plugin_active_for_network( MLS_BASENAME ) ) {
				return $slug;
			} else {
				return $slug;
			}
		}

		/**
		 * Handles returning the needed login url for login page access.
		 *
		 * @param string $scheme - Scheme.
		 *
		 * @return string - URL.
		 *
		 * @since 2.0.0
		 */
		public function custom_login_url( $scheme = null ) {
			if ( get_option( 'permalink_structure' ) ) {
				return $this->context_trailingslashit( home_url( '/', $scheme ) . $this->custom_login_slug() );
			} else {
				return home_url( '/', $scheme ) . '?' . $this->custom_login_slug();
			}
		}

		/**
		 * Runs early in a page cycle to check and setup local variables to load the login page if needed.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function is_login_check() {
			$mls_setting    = get_site_option( MLS_PREFIX . '_setting' );
			$request        = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( rawurldecode( sanitize_text_field( $_SERVER['REQUEST_URI'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$request_string = rawurldecode( sanitize_text_field( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			global $pagenow;

			if ( ! empty( $mls_setting['custom_login_url'] ) ) {
				if ( ! is_multisite() && ( strpos( $request_string, 'wp-signup.php' ) !== false || strpos( $request_string, 'wp-activate.php' ) !== false ) ) {
					wp_die( esc_html__( 'This feature is not enabled.', 'melapress-login-security' ) );
				}

				if ( ( strpos( $request_string, 'wp-login.php' ) !== false || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' ) ) ) && ! is_admin() ) {
					$this->is_login_page    = true;
					$_SERVER['REQUEST_URI'] = $this->context_trailingslashit( '/' . str_repeat( '-/', 10 ) );
					$pagenow                = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

				} elseif ( ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === home_url( $this->custom_login_slug(), 'relative' ) ) || ( ! get_option( 'permalink_structure' ) && isset( $_GET[ $this->custom_login_slug() ] ) && empty( $_GET[ $this->custom_login_slug() ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

				} elseif ( ( strpos( $request_string, 'wp-register.php' ) !== false || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-register', 'relative' ) ) ) && ! is_admin() ) {
					$this->is_login_page    = true;
					$_SERVER['REQUEST_URI'] = $this->context_trailingslashit( '/' . str_repeat( '-/', 10 ) );
					$pagenow                = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}

			if ( 'wp-login.php' === $pagenow || $this->is_login_page ) {
				$this->is_geo_check_required = false;
				if ( ( isset( $mls_setting['login_geo_countries'] ) && ! empty( $mls_setting['login_geo_countries'] ) ) && ( isset( $mls_setting['login_geo_method'] ) && 'default' !== $mls_setting['login_geo_method'] ) ) {
					$this->is_geo_check_required = true;
				}
				$this->is_ip_check_required = false;
				if ( isset( $mls_setting['enable_login_allowed_ips'] ) && \MLS\Helpers\OptionsHelper::string_to_bool( $mls_setting['enable_login_allowed_ips'] ) && isset( $mls_setting['restrict_login_allowed_ips'] ) && ! empty( $mls_setting['restrict_login_allowed_ips'] ) ) {
					$this->is_ip_check_required = true;
				}
			} elseif ( isset( $mls_setting['enable_login_allowed_ips'] ) && \MLS\Helpers\OptionsHelper::string_to_bool( $mls_setting['enable_login_allowed_ips'] ) && isset( $mls_setting['restrict_login_allowed_ips'] ) && ! empty( $mls_setting['restrict_login_allowed_ips'] ) ) {
				if ( ! empty( $this->restrict_login_bypass_slug() ) ) {
					if ( ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === home_url( $this->restrict_login_bypass_slug(), 'relative' ) ) || ( ! get_option( 'permalink_structure' ) && isset( $_GET[ $this->restrict_login_bypass_slug() ] ) && empty( $_GET[ $this->restrict_login_bypass_slug() ] ) ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$this->is_ip_check_required = true;
						$pagenow                    = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					}
				}
			}
		}

		/**
		 * Handles the user redirection based on results of what occurred in plugins_loaded.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function redirect_user() {
			global $pagenow;
			$mls_setting = get_site_option( MLS_PREFIX . '_setting' );
			$request     = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( rawurldecode( sanitize_text_field( $_SERVER['REQUEST_URI'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash

			if ( $this->is_geo_check_required ) {
				$is_blocked = self::is_blocked_country( true, true, self::sanitize_incoming_ip( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( $is_blocked ) {
					if ( 'deny_to_url' === $mls_setting['login_geo_action'] && ! empty( $mls_setting['login_geo_redirect_url'] ) ) {
						wp_safe_redirect( '/' . rtrim( $mls_setting['login_geo_redirect_url'], '/' ) );
					} else {
						wp_safe_redirect( '/' );
					}
					die();
				}
			}

			if ( $this->is_ip_check_required && isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$is_ip_blocked = self::is_ip_blocked( self::sanitize_incoming_ip( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( $is_ip_blocked && ! isset( $_REQUEST['wp-submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( ( ! empty( $this->restrict_login_bypass_slug() ) && ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === home_url( $this->restrict_login_bypass_slug(), 'relative' ) ) ) || ( ! get_option( 'permalink_structure' ) && isset( $_GET[ $this->restrict_login_bypass_slug() ] ) && empty( $_GET[ $this->restrict_login_bypass_slug() ] ) ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						global $error, $interim_login, $action, $user_login;
						@include_once ABSPATH . 'wp-login.php';
						die;
					} else {
						if ( isset( $_REQUEST['action'] ) && 'logout' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							return;
						}
						if ( ! empty( $mls_setting['restrict_login_redirect_url'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							wp_safe_redirect( '/' . rtrim( $mls_setting['restrict_login_redirect_url'], '/' ) );
						} else {
							header( 'HTTP/1.0 403 Forbidden' );
							die();
						}
						die();
					}
				}
			}

			if ( ! empty( $mls_setting['custom_login_url'] ) ) {
				if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) ) {
					if ( empty( $mls_setting['custom_login_redirect'] ) || ! $mls_setting['custom_login_redirect'] ) {
						wp_safe_redirect( '/' );
					} else {
						wp_safe_redirect( '/' . rtrim( $mls_setting['custom_login_redirect'], '/' ) );
					}
					die();
				}

				if ( 'wp-login.php' === $pagenow && $request['path'] !== $this->context_trailingslashit( $request['path'] ) && get_option( 'permalink_structure' ) ) {
					wp_safe_redirect( $this->context_trailingslashit( $this->custom_login_url() ) . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) : '' ) );
					die;

				} elseif ( $this->is_login_page ) {
					$referer       = wp_get_referer();
					$referer_parse = wp_parse_url( $referer );

					if ( $referer && strpos( $referer, 'wp-activate.php' ) !== false && $referer_parse && ! empty( $referer['query'] ) ) {
						parse_str( $referer['query'], $referer );
						$result = wpmu_activate_signup( $referer['key'] );
						if ( ! empty( $referer['key'] ) && is_wp_error( $result ) && ( $result->get_error_code() === 'already_active' || $result->get_error_code() === 'blog_taken' ) ) {
							wp_safe_redirect( $this->custom_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) : '' ) );
							die;
						}
					} else {
						if ( empty( $mls_setting['custom_login_redirect'] ) || ! $mls_setting['custom_login_redirect'] ) {
							wp_safe_redirect( '/' );
						} else {
							wp_safe_redirect( '/' . rtrim( $mls_setting['custom_login_redirect'], '/' ) );
						}
						die();
					}

					$this->load_login_template();

				} elseif ( 'wp-login.php' === $pagenow ) {
					global $error, $interim_login, $action, $user_login;
					@include_once ABSPATH . 'wp-login.php';
					die;
				}
			}
		}

		/**
		 * Update site_url to reflect our slug.
		 *
		 * @param  string $url - Original URL.
		 * @param  string $path - Path.
		 * @param  string $scheme - Scheme.
		 * @param  int    $blog_id - Blog ID.
		 *
		 * @return string - Filtered url.
		 *
		 * @since 2.0.0
		 */
		public function login_control_site_url( $url, $path, $scheme, $blog_id ) {
			return $this->login_control_login_url_filter( $url, $scheme );
		}

		/**
		 * Update networl_site_url to reflect our slug.
		 *
		 * @param  string $url - Original URL.
		 * @param  string $path - Path.
		 * @param  string $scheme - Scheme.
		 *
		 * @return string - Filtred url.
		 *
		 * @since 2.0.0
		 */
		public function login_control_network_site_url( $url, $path, $scheme ) {
			return $this->login_control_login_url_filter( $url, $scheme );
		}

		/**
		 * Ensure our custom URL is filtered into wp_redirect
		 *
		 * @param  string $location - Location.
		 * @param  int    $status - Status.
		 *
		 * @return string - Filtered location.
		 *
		 * @since 2.0.0
		 */
		public function login_control_wp_redirect( $location, $status ) {
			return $this->login_control_login_url_filter( $location );
		}

		/**
		 * Function to take current URL/location and update it based on if user wishes it to be modified or not.
		 *
		 * @param  string      $url - Url.
		 * @param  string|null $scheme - Scheme.
		 *
		 * @return string - Updated URL.
		 *
		 * @since 2.0.0
		 */
		public function login_control_login_url_filter( $url, $scheme = null ) {
			if ( strpos( $url, 'wp-login.php' ) !== false ) {
				if ( is_ssl() ) {
					$scheme = 'https';
				}
				$args = explode( '?', $url );
				if ( isset( $args[1] ) ) {
					parse_str( $args[1], $args );
					$url = add_query_arg( $args, $this->custom_login_url( $scheme ) );
				} else {
					$url = $this->custom_login_url( $scheme );
				}
			}
			return $url;
		}

		/**
		 * Replace login url with modified value.
		 *
		 * @param  string $value - Original string.
		 *
		 * @return string $value - Modified string.
		 *
		 * @since 2.0.0
		 */
		public function welcome_email_content( $value ) {
			$mls_setting = get_site_option( MLS_PREFIX . '_setting' );
			return str_replace( 'wp-login.php', trailingslashit( $mls_setting['custom_login_url'] ), $value );
		}

		/**
		 * Filters text used within user action request emails and replaced the login slug with our value.
		 *
		 * @param  string $email_text - Original text.
		 * @param  array  $email_data - Data.
		 *
		 * @return string $email_text - Modified test.
		 *
		 * @since 2.0.0
		 */
		public function user_request_action_email_content( $email_text, $email_data ) {
			$mls = melapress_login_security();
			if ( ! empty( $mls->options->mls_setting->custom_login_url ) ) {
				$email_text = str_replace( '###CONFIRM_URL###', esc_url_raw( str_replace( rtrim( $mls->options->mls_setting->custom_login_url, '/' ) . '/', 'wp-login.php', $email_data['confirm_url'] ) ), $email_text );
			}

			return $email_text;
		}

		/**
		 * Returns an array of slugs which are reserved, for use with validation to ensure no clashes.
		 *
		 * @return array - Array of slugs.
		 *
		 * @since 2.0.0
		 */
		public function protected_slugs() {
			$wp = new \WP();
			return array_merge( $wp->public_query_vars, $wp->private_query_vars );
		}

		/**
		 * Ensure we dont give away the correct url in any context.
		 *
		 * @param string $login_url - Existing URL.
		 * @param string $redirect - Redirect url.
		 * @param bool   $force_reauth - Force reauth.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function login_control_login_url( $login_url, $redirect, $force_reauth ) {
			if ( is_404() ) {
				return '#';
			}

			if ( false === $force_reauth ) {
				return $login_url;
			}

			if ( empty( $redirect ) ) {
				return $login_url;
			}

			$redirect = explode( '?', $redirect );

			if ( isset( $redirect[0] ) && admin_url( 'options.php' ) === $redirect[0] ) {
				$login_url = admin_url();
			}

			return $login_url;
		}

		/**
		 * Check if submission is from a country we wish to allow/block.
		 *
		 * @param bool   $currently_allowed - Is allowed.
		 * @param bool   $current_verify - Currently verified.
		 * @param string $ip - IP.
		 * @param string $context - Context.
		 *
		 * @return boolean
		 *
		 * @since 2.0.0
		 */
		public static function is_blocked_country( $currently_allowed, $current_verify, $ip, $context = 'default' ) {
			$is_spam = false;
			$mls     = melapress_login_security();

			$method           = $mls->options->mls_setting->login_geo_method;
			$target_countries = $mls->options->mls_setting->login_geo_countries;
			$iplocate_api_key = $mls->options->mls_setting->iplocate_api_key;

			if ( empty( $iplocate_api_key ) || ! $iplocate_api_key ) {
				return false;
			}

			if ( empty( $method ) || empty( $target_countries ) || 'default' === $method ) {
				return false;
			}

			$denied_countries = ! empty( $target_countries ) ? explode( ',', $target_countries ) : array();

			$response = wp_safe_remote_get(
				esc_url_raw(
					sprintf(
						'https://www.iplocate.io/api/lookup/%s?apikey=%s',
						self::format_incoming_ip( $ip ),
						$iplocate_api_key
					),
					'https'
				)
			);

			if ( is_wp_error( $response ) ) {
				return ( $currently_allowed ) ? false : true;
			}

			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return ( $currently_allowed ) ? false : true;
			}

			$body = (string) wp_remote_retrieve_body( $response );

			$json = json_decode( $body, true );

			// If invalid, pass.
			if ( ! is_array( $json ) ) {
				return ( $currently_allowed ) ? false : true;
			}

			// If empty, country not obtained, pass.
			if ( empty( $json['country_code'] ) ) {
				return false;
			}

			// Uppercase should be passed, but just in case.
			$country = strtoupper( $json['country_code'] );

			// Check length.
			if ( empty( $country ) || strlen( $country ) !== 2 ) {
				return ( $currently_allowed ) ? false : true;
			}

			if ( 'deny_list' === $method || 'deny_to_home' === $method ) {
				if ( in_array( $country, $denied_countries, true ) ) {
					$is_spam = true;
				}
			} elseif ( 'allow_only' === $method ) {
				if ( ! in_array( $country, $denied_countries, true ) ) {
					$is_spam = true;
				}
			}

			return $is_spam;
		}

		/**
		 * Prepare ip for check.
		 *
		 * @param string  $ip - IP to check.
		 * @param boolean $cut_end - Cut end.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		private static function prepare_ip( $ip, $cut_end = true ) {
			$separator = ( self::check_ip_format( $ip ) ? '.' : ':' );

			return str_replace(
				( $cut_end ? strrchr( $ip, $separator ) : strstr( $ip, $separator ) ),
				'',
				$ip
			);
		}

		/**
		 * Format incoming IP before check.
		 *
		 * @param string $ip - IP to format.
		 *
		 * @return mixed
		 *
		 * @since 2.0.0
		 */
		private static function format_incoming_ip( $ip ) {
			if ( self::check_ip_format( $ip ) ) {
				return self::prepare_ip( $ip ) . '.0';
			}

			return self::prepare_ip( $ip, false ) . ':0:0:0:0:0:0:0';
		}

		/**
		 * Validate IP.
		 *
		 * @param string $ip - IP to format.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		private static function check_ip_format( $ip ) {
			if ( function_exists( 'filter_var' ) ) {
				return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false;
			} else {
				return preg_match( '/^\d{1,3}(\.\d{1,3}){3}$/', $ip );
			}
		}

		/**
		 * Clean incoming IP.
		 *
		 * @param string $raw_ip - IP to format.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public static function sanitize_incoming_ip( $raw_ip ) {

			if ( strpos( $raw_ip, ',' ) !== false ) {
				$ips    = explode( ',', $raw_ip );
				$raw_ip = trim( $ips[0] );
			}
			if ( function_exists( 'filter_var' ) ) {
				return (string) filter_var(
					$raw_ip,
					FILTER_VALIDATE_IP
				);
			}

			return (string) preg_replace(
				'/[^0-9a-f:. ]/si',
				'',
				$raw_ip
			);
		}

		/**
		 * Check if submission is from a country we wish to allow/block.
		 *
		 * @param string $ip - IP.
		 *
		 * @return boolean
		 *
		 * @since 2.0.0
		 */
		public static function is_ip_blocked( $ip ) {
			$mls         = melapress_login_security();
			$allowed_ips = explode( ',', $mls->options->mls_setting->restrict_login_allowed_ips );

			if ( ! $ip || in_array( $ip, $allowed_ips, true ) ) {
				return false;
			}

			return true;
		}
	}
}
