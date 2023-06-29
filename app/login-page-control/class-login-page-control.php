<?php
/**
 * PPM Email Settings
 *
 * @package    WordPress
 * @subpackage wpassword
 * @author     WP White Security
 */

 use \PPMWP\Helpers\OptionsHelper;
 use \PPMWP\Helpers\PPM_EmailStrings;

if ( ! class_exists( 'MLS_Login_Page_Control' ) ) {

	/**
	 * Manipulate Users' Password History
	 */
	class MLS_Login_Page_Control {


		private $is_login_page;

		/**
		 * Init settings hooks.
		 *
		 * @return void
		 */
		public function init() {
			add_filter( 'ppmwp_settings_page_nav_tabs', array( $this, 'settings_tab_link' ), 20, 1 );
			add_filter( 'ppmwp_settings_page_content_tabs', array( $this, 'settings_tab' ), 10, 1 );

			$ppm = ppm_wp();
			if ( isset( $ppm->options->ppm_setting->custom_login_url ) && ! empty( $ppm->options->ppm_setting->custom_login_url ) ) {
				add_filter( 'site_url', array( $this, 'login_control_site_url' ), 10, 4 );
				add_filter( 'network_site_url', array( $this, 'login_control_network_site_url' ), 10, 3 );
				add_filter( 'wp_redirect', array( $this, 'login_control_wp_redirect' ), 10, 2 );
				add_filter( 'site_option_welcome_email_content', array( $this, 'welcome_email_content' ) );
				add_filter( 'user_request_action_email_content', array( $this, 'user_request_action_email_content' ), 999, 2 );
				remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
				add_filter( 'login_url', array( $this, 'login_control_login_url' ), 10, 3 );
			}
		}

		/**
		 * Add link to tabbed area within settings.
		 *
		 * @param  string $markup - Currently added content.
		 * @return string $markup - Appended content.
		 */
		public function settings_tab_link( $markup ) {
			return $markup . '<a href="#login-page-settings" class="nav-tab" data-tab-target=".ppm-login-page-settings">' . esc_attr__( 'Login page', 'ppm-wp' ) . '</a>';
		}

		/**
		 * Add settings tab content to settings area
		 *
		 * @param  string $markup - Currently added content.
		 * @return string $markup - Appended content.
		 */
		public function settings_tab( $markup ) {
			ob_start(); ?>
			<div class="settings-tab ppm-login-page-settings">
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
				<br>
				<h3><?php esc_html_e( 'Change the login page URL', 'ppm-wp' ); ?></h3>
				<p class="description" style="max-width: none;">
			<?php esc_html_e( 'The default WordPress login page URL is /wp-admin/ or /wp-login.php. Improve the security of your website by changing the URL of the WordPress login page to anything you want, thus preventing easy access to bots and attackers.', 'ppm-wp' ); ?>
				</p>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
			<?php esc_html_e( 'Login page URL', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo trailingslashit( site_url() ); ?></p>
							<input type="text" name="_ppm_options[custom_login_url]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->custom_login_url ) ? $ppm->options->ppm_setting->custom_login_url : '' ); ?>" id="ppm-custom_login_url" style="float: left; display: block; width: 250px;" />
						</fieldset>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="ppm-from-email">
			<?php esc_html_e( 'Old login page URL redirect', 'ppm-wp' ); ?>
					</th>
					<td>
						<fieldset>
							<p style="display: inline-block; float: left; margin-right: 6px;"><?php echo trailingslashit( site_url() ); ?></p>
							<input type="text" name="_ppm_options[custom_login_redirect]" value="<?php echo esc_attr( isset( $ppm->options->ppm_setting->custom_login_redirect ) ? $ppm->options->ppm_setting->custom_login_redirect : '' ); ?>" id="ppm-custom_login_redirect" style="float: left; display: block; width: 250px;" />
							<br>
							<br>
							<p class="description">
			<?php esc_html_e( 'Redirect anyone who tries to access the default WordPress login page URL to the above configured URL.', 'ppm-wp' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			<?php
		}

		/**
		 * Manually load the login template where it would not typically wish to load.
		 *
		 * @return void
		 */
		private function load_login_template() {
			global $pagenow;
			$pagenow = 'index.php';
			if ( ! defined( 'WP_USE_THEMES' ) ) {
				define( 'WP_USE_THEMES', true );
			}
			wp();
			if ( $_SERVER['REQUEST_URI'] === $this->context_trailingslashit( str_repeat( '-/', 10 ) ) ) {
				$_SERVER['REQUEST_URI'] = $this->context_trailingslashit( '/wp-login-php/' );
			}
			include_once ABSPATH . WPINC . '/template-loader.php';
			die;
		}

		/**
		 * Simple checker function to determine if trailing slashes are needed based on user permalink setup.
		 *
		 * @return bool
		 */
		private function trailing_slashes_needed() {
			return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
		}

		/**
		 * Wraps or unwraps a slash where needed.
		 *
		 * @param  string $string - String to modify.
		 * @return string $string - Modified string.
		 */
		private function context_trailingslashit( $string ) {
			return $this->trailing_slashes_needed() ? trailingslashit( $string ) : untrailingslashit( $string );
		}

		/**
		 * Handles returning the needed slug for login page access.
		 *
		 * @return string $slug
		 */
		private function custom_login_slug() {
			$ppm_setting = get_site_option( PPMWP_PREFIX . '_setting' );
			if ( ( $slug = $ppm_setting['custom_login_url'] ) || ( is_multisite() && is_plugin_active_for_network( PPM_WP_BASENAME ) && ( $slug = $ppm_setting['custom_login_url'] ) )
				|| ( $slug = 'login' )
			) {
				return $slug;
			}
		}

		/**
		 * Handles returning the needed login url for login page access.
		 *
		 * @return string $slug
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
		 */
		public function is_login_check() {
			$ppm_setting = get_site_option( PPMWP_PREFIX . '_setting' );
			if ( ! empty( $ppm_setting['custom_login_url'] ) ) {
				global $pagenow;
				$request = parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) );
				if ( ! is_multisite() && ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-signup' ) !== false || strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-activate' ) !== false ) ) {
					wp_die( __( 'This feature is not enabled.', 'ppm-wp' ) );
				}

				if ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-login.php' ) !== false || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' ) ) ) && ! is_admin() ) {
					$this->is_login_page     = true;
					$_SERVER['REQUEST_URI'] = $this->context_trailingslashit( '/' . str_repeat( '-/', 10 ) );
					$pagenow                = 'index.php';

				} elseif ( ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === home_url( $this->custom_login_slug(), 'relative' ) ) || ( ! get_option( 'permalink_structure' ) && isset( $_GET[ $this->custom_login_slug() ] ) && empty( $_GET[ $this->custom_login_slug() ] ) ) ) {
					$pagenow = 'wp-login.php';

				} elseif ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-register.php' ) !== false || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-register', 'relative' ) ) ) && ! is_admin() ) {
					$this->is_login_page     = true;
					$_SERVER['REQUEST_URI'] = $this->context_trailingslashit( '/' . str_repeat( '-/', 10 ) );
					$pagenow                = 'index.php';
				}
			}
		}

		/**
		 * Handles the user redirection based on results of what occured in plugins_loaded.
		 *
		 * @return void
		 */
		public function redirect_user() {
			global $pagenow;
			$ppm_setting = get_site_option( PPMWP_PREFIX . '_setting' );
			$request     = parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) );

			if ( ! empty( $ppm_setting['custom_login_url'] ) ) {
				if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) ) {
					if ( empty( $ppm_setting['custom_login_redirect'] ) || ! $ppm_setting['custom_login_redirect'] ) {
						wp_safe_redirect( '/' );
					} else {
						wp_safe_redirect( '/' . $ppm_setting['custom_login_redirect'] );
					}
					die();
				}

				if ( $pagenow === 'wp-login.php' && $request['path'] !== $this->context_trailingslashit( $request['path'] ) && get_option( 'permalink_structure' ) ) {
					wp_safe_redirect( $this->context_trailingslashit( $this->custom_login_url() ) . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );
					die;

				} elseif ( $this->is_login_page ) {
					if ( ( $referer = wp_get_referer() ) && strpos( $referer, 'wp-activate.php' ) !== false && ( $referer = parse_url( $referer ) ) && ! empty( $referer['query'] ) ) {
						parse_str( $referer['query'], $referer );

						if ( ! empty( $referer['key'] ) && ( $result = wpmu_activate_signup( $referer['key'] ) ) && is_wp_error( $result ) && ( $result->get_error_code() === 'already_active' || $result->get_error_code() === 'blog_taken' ) ) {
							wp_safe_redirect( $this->custom_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );
							die;
						}
					}

					$this->load_login_template();

				} elseif ( $pagenow === 'wp-login.php' ) {
					global $error, $interim_login, $action, $user_login;
					@include_once ABSPATH . 'wp-login.php';
					die;
				}
			}
		}

		/**
		 * Update site_url to reflect our slug.
		 *
		 * @param  string $url
		 * @param  string $path
		 * @param  string $scheme
		 * @param  int    $blog_id
		 * @return string - Filtred url.
		 */
		public function login_control_site_url( $url, $path, $scheme, $blog_id ) {
			return $this->login_control_login_url_filter( $url, $scheme );
		}

		/**
		 * Update networl_site_url to reflect our slug.
		 *
		 * @param  string $url
		 * @param  string $path
		 * @param  string $scheme
		 * @param  int    $blog_id
		 * @return string - Filtred url.
		 */
		public function login_control_network_site_url( $url, $path, $scheme ) {
			return $this->login_control_login_url_filter( $url, $scheme );
		}

		/**
		 * Ensure our custom URL is filtered into wp_redirect
		 *
		 * @param  string $location
		 * @param  int    $status
		 * @return string - Filtered location.
		 */
		public function login_control_wp_redirect( $location, $status ) {
			return $this->login_control_login_url_filter( $location );
		}

		/**
		 * Function to take current URL/location and update it based on if user wishes it to be modified or not.
		 *
		 * @param  string      $url
		 * @param  string|null $scheme
		 * @return string - Updated URL.
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
		 * @return string $value - Modified string.
		 */
		public function welcome_email_content( $value ) {
			$ppm_setting  = get_site_option( PPMWP_PREFIX . '_setting' );
			return $value = str_replace( 'wp-login.php', trailingslashit( $ppm_setting['custom_login_url'] ), $value );
		}

		/**
		 * Filters text used within user action request emails and replaced the login slug with our value.
		 *
		 * @param  string $email_text
		 * @param  array  $email_data
		 * @return string $email_text - Modified test.
		 */
		public function user_request_action_email_content( $email_text, $email_data ) {
			$ppm = ppm_wp();
			if ( ! empty( $ppm->options->ppm_setting->custom_login_url ) ) {
				$email_text = str_replace( '###CONFIRM_URL###', esc_url_raw( str_replace( $ppm->options->ppm_setting->custom_login_url . '/', 'wp-login.php', $email_data['confirm_url'] ) ), $email_text );
			}

			return $email_text;
		}

		/**
		 * Returns an array of slugs which are reserved, for use with validation to ensure no clashes.
		 *
		 * @return void
		 */
		public function protected_slugs() {
			$wp = new WP();
			return array_merge( $wp->public_query_vars, $wp->private_query_vars );
		}

		/**
		 * Ensure we dont give away the correct url in any context.
		 *
		 * @param $login_url
		 * @param $redirect
		 * @param $force_reauth
		 *
		 * @return string
		 */
		public function login_control_login_url( $login_url, $redirect, $force_reauth ) {
			if ( is_404() ) {
				return '#';
			}

			if ( $force_reauth === false ) {
				return $login_url;
			}

			if ( empty( $redirect ) ) {
				return $login_url;
			}

			$redirect = explode( '?', $redirect );

			if ( isset( $redirect[0] ) && $redirect[0] === admin_url( 'options.php' ) ) {
				$login_url = admin_url();
			}

			return $login_url;
		}
	}
}
