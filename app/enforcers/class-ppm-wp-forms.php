<?php

/**
 * @package wordpress
 * @subpackage wpassword
 *
 */

use PPMWP\Helpers\OptionsHelper;

if ( ! class_exists( 'PPM_WP_Forms' ) ) {

	/**
	 * Modify Forms where Reset Password UI appears
	 */
	class PPM_WP_Forms {

		/**
		 *
		 * @var array Plugin Options
		 */
		private $options;

		/**
		 *
		 * @var PPM_WP_Msgs Instance of PPM_WP_Msgs
		 */
		private $msgs;

		/**
		 *
		 * @var PPM_WP_Regex Instance of PPM_WP_Regex
		 */
		private $regex;

		/**
		 * User Options
		 */
		private $role_options;

		/**
		 * Initialise
		 */
		public function __construct() {

			$ppm = ppm_wp();

			$this->options = $ppm->options;

			$this->msgs = $ppm->msgs;

			$this->regex = $ppm->regex;

			$this->role_options = $ppm->options->users_options;
		}

		/**
		 * Hook into WP to modify forms
		 */
		public function hook(){

			// Check if the filter is being hit.
			$scripts_required = apply_filters( 'ppm_enable_custom_form', [] );

			// If so, fire up function.
			if ( ! empty( $scripts_required  ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enable_custom_form' ) );
			}

			if ( $this->role_options == null || ! OptionsHelper::getPluginIsEnabled() ) {
				return;
			}

			// deregister default scripts and register custom
			// user-edit screen
			add_action( 'load-user-edit.php', array( $this, 'user_edit' ) );

			// profile screen
			add_action( 'load-profile.php', array( $this, 'load_profile' ) );

			// add new user screen
			add_action( 'load-user-new.php', array( $this, 'user_new' ) );

			// reset password form
			add_action( 'validate_password_reset', array( $this, 'reset_pass' ), 10, 2 );

			// localise js objects
			add_action( 'admin_print_styles-user-edit.php', array( $this, 'localise' ) );
			add_action( 'admin_print_styles-profile.php', array( $this, 'localise' ) );
			add_action( 'admin_print_styles-user-new.php', array( $this, 'localise' ) );
			add_action( 'validate_password_reset', array( $this, 'localise' ), 10, 2 );

			// Add styles for the various forms.
			add_action( 'admin_print_styles-user-edit.php', array( $this, 'add_admin_css' ) );
			add_action( 'admin_print_styles-profile.php', array( $this, 'add_admin_css' ) );
			add_action( 'admin_print_styles-user-new.php', array( $this, 'add_admin_css' ) );
			add_action( 'validate_password_reset', array( $this, 'add_frontend_css' ) );
			add_action( 'resetpass_form', array( $this, 'add_hint_to_reset_form' ) );

			// Remove WC password strength meter.
			add_action( 'wp_print_scripts', array( $this, 'remove_wc_password_strength' ), 10 );
		}

		/**
		 * Enable policy for custom form
		 */
		public function enable_custom_form( $shortcode_attributes = [] ) {

			$custom_form = [];

			// apply policy for custom forms
			$custom_form = apply_filters( 'ppm_enable_custom_form',
				array(
					'element'	       => '',
					'button_class'     => '',
					'elements_to_hide' => '',
				)
			);

			// Override if shortcode present.
			if ( is_array( $shortcode_attributes ) && ! empty( $shortcode_attributes ) ) {
				$custom_form['element']          = $shortcode_attributes['element'];
				$custom_form['button_class']     = $shortcode_attributes['button_class'];
				$custom_form['elements_to_hide'] = $shortcode_attributes['elements_to_hide'];
			}

			if ( ! empty( $custom_form['element'] ) ) {
				wp_deregister_script( 'user-profile' );
				wp_deregister_script( 'password-strength-meter' );

				wp_register_script( 'password-strength-meter', PPM_WP_URL . "assets/js/password-strength-meter.js", array( 'jquery', 'zxcvbn-async' ), false, 1 );

				wp_localize_script( 'password-strength-meter', 'pwsL10n', $this->msgs->pwsL10n );
				wp_localize_script( 'password-strength-meter', 'ppmPolicyRules', json_decode( json_encode( $this->regex ), true ) );

				wp_enqueue_script( 'user-profile', PPM_WP_URL . "assets/js/custom-form.js", array( 'jquery', 'password-strength-meter', 'wp-util' ), false, 1 );

				wp_localize_script( 'user-profile', 'userProfileL10n', $this->msgs->userProfileL10n );

				// Variables to check shortly.
				$element_to_apply_form_js_to      = $custom_form['element'];
				$button_class_to_apply_form_js_to = isset( $custom_form['button_class'] ) ? $custom_form['button_class'] : '';
				$elements_to_hide                 = isset( $custom_form['elements_to_hide'] ) ? $custom_form['elements_to_hide'] : '';

				wp_localize_script( 'user-profile', 'PPM_Custom_Form',
					array(
						'policy'	       => $this->password_hint(),
						'element'	       => $element_to_apply_form_js_to,
						'button_class'     => $button_class_to_apply_form_js_to,
						'elements_to_hide' => $elements_to_hide,
					)
				);

				wp_localize_script( 'user-profile', 'ppmErrors', $this->msgs->error_strings );
				wp_localize_script( 'user-profile', 'ppmJSErrors', $this->msgs->js_error_strings );
				wp_localize_script( 'user-profile', 'ppmPolicyRules', json_decode( json_encode( $this->regex ), true ));

				add_filter( 'password_hint', array( $this, 'password_hint' ) );

				$this->add_frontend_css();
			}
		}

		/**
		 *
		 * @global type $user_id
		 */
		public function user_edit() {
			global $user_id;

			// we don't want to overwrite the global if WP doesn't want to set it yet
			$userid = $user_id;


			$userid = isset( $_GET[ 'user_id' ] ) ? $_GET[ 'user_id' ] : $userid;

			$this->modify_user_scripts( $userid );
		}

		/**
		 *
		 * @global type $user_id
		 */
		public function load_profile() {
			global $user_id;

			// we don't want to overwrite the global if WP doesn't want to set it yet
			$userid = $user_id;

			$userid = empty( $userid ) ? get_current_user_id() : false;

			$this->modify_user_scripts( $userid );
		}

		/**
		 *
		 * @global type $user_id
		 */
		public function user_new() {
			global $user_id;

			$this->modify_user_scripts( $user_id );
		}

		/**
		 *
		 * @global type $user_id
		 * @param type $errors
		 * @param type $user
		 */
		public function reset_pass( $errors, $user ) {
			global $user_id;

			// we don't want to overwrite the global if WP doesn't want to set it yet
			$userid = $user_id;

			if ( empty( $userid ) && ! empty( $user ) ) {
				$userid = $user->ID;
			}

			$this->modify_user_scripts( $userid );
		}

		/**
		 *
		 * @param type $user_id
		 * @return type
		 */
		private function modify_user_scripts( $user_id ) {

			if ( ppm_is_user_exempted( $user_id ) ) {
				return;
			}

			// $suffix = WP_DEBUG ? '' : '.min';
			$suffix = '';

			wp_deregister_script( 'user-profile' );
			wp_deregister_script( 'password-strength-meter' );

			wp_register_script( 'password-strength-meter', PPM_WP_URL . "assets/js/password-strength-meter$suffix.js", array( 'jquery', 'zxcvbn-async' ), false, 1 );

			wp_localize_script( 'password-strength-meter', 'pwsL10n', $this->msgs->pwsL10n );

			wp_localize_script( 'password-strength-meter', 'ppmPolicyRules', json_decode( json_encode( $this->regex ), true ) );

			wp_add_inline_script( 'password-strength-meter', 'jQuery(document).ready(function() { jQuery(\'.pw-weak\').remove();});' );

			wp_register_script( 'user-profile', PPM_WP_URL . "assets/js/user-profile$suffix.js", array( 'jquery', 'password-strength-meter', 'wp-util' ), false, 1 );

			wp_localize_script( 'user-profile', 'userProfileL10n', $this->msgs->userProfileL10n );
			wp_localize_script( 'user-profile', 'ppmErrors', $this->msgs->error_strings );
			wp_localize_script( 'user-profile', 'ppmJSErrors', $this->msgs->js_error_strings );
		}

		/**
		 *
		 * @global type $user_id
		 * @param type $errors
		 * @param type $user
		 * @return type
		 */
		public function localise( $errors = false, $user = false ) {

			global $user_id;

			if ( empty( $user_id ) && ! empty( $user ) ) {
				$user_id = $user->ID;
			}

			if ( ppm_is_user_exempted( $user_id ) ) {
				return;
			}

			/*
			 * If we are on the page after password is reset then bail early.
			 * This prevents 'headers already sent' messages caused by output
			 * of scripts and styles into places where a cookie is being set.
			 */
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WP doesn't use a nonce for login/reset form.
			if ( isset( $_REQUEST['action'] ) && 'resetpass' === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) || isset( $_REQUEST['wc_reset_password'] ) && $_REQUEST['wc_reset_password'] ) {
				return;
			}
			// phpcs:enable

			add_filter( 'password_hint', array( $this, 'password_hint' ) );

			wp_localize_script( 'user-profile', 'userProfileL10n', $this->msgs->userProfileL10n );
			wp_localize_script( 'user-profile', 'ppmErrors', $this->msgs->error_strings );
			wp_localize_script( 'user-profile', 'ppmJSErrors', $this->msgs->js_error_strings );
		}

		/**
		 * Prints CSS into the page for the frontend/reset password form.
		 *
		 * @method add_frontend_css
		 * @since  2.1.0
		 */
		public function add_frontend_css() {
			/*
			 * If we are on the page after password is reset then bail early.
			 * This prevents 'headers already sent' messages caused by output
			 * of scripts and styles into places where a cookie is being set.
			 */
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WP doesn't use a nonce for login/reset form.
			if ( isset( $_REQUEST['action'] ) && 'resetpass' === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) || isset( $_REQUEST['wc_reset_password'] ) && $_REQUEST['wc_reset_password'] ) {
				return;
			}
			// phpcs:enable
			wp_enqueue_style( 'ppmwp-form-css', PPM_WP_URL . 'assets/css/styling.css', [ 'login' ] );
		}

		/**
		 * Prints CSS into the page for the backend new/edit user password form.
		 *
		 * @method add_admin_css
		 * @since  2.1.0
		 */
		public function add_admin_css() {
			wp_enqueue_style( 'ppmwp-admin-css', PPM_WP_URL . 'admin/assets/css/backend-styling.css');
		}

		/**
		 * Filter password hint
		 *
		 * @return new hint
		 */
		public function password_hint() {
			ob_start();
			?>
			<div class="pass-strength-result">
			<strong><?php esc_html_e('Hints for a strong password', 'ppm-wp'); ?>:</strong></p>
			<ul>
				<?php
				unset( $this->msgs->error_strings['history'] );

				$is_needed                   = isset( $this->role_options->rules['exclude_special_chars'] ) && PPMWP\Helpers\OptionsHelper::string_to_bool( $this->role_options->rules['exclude_special_chars'] );
				$do_we_have_chars_to_exclude = isset( $this->role_options->excluded_special_chars ) && ! empty( $this->role_options->excluded_special_chars );

				/**
				 * Edge case when all special characters are excluded in the excluded characters
				 * can return false positive when new password is set
				 */
				if ( ! PPMWP\Helpers\OptionsHelper::string_to_bool( $this->role_options->rules[ 'special_chars' ] ) && isset( $this->msgs->error_strings[ 'special_chars' ] ) ) {
					unset( $this->msgs->error_strings[ 'special_chars' ] );
				}

				if ( ! $is_needed || ! $do_we_have_chars_to_exclude ) {
					// doesn't have any characters excluded.
					unset( $this->msgs->error_strings[ 'exclude_special_chars' ] );
				}

				foreach ( array_filter( $this->msgs->error_strings ) as $key => $error ) {
					?>
					<li class="<?php echo esc_attr( $key ); ?>"><?php echo $error; ?></li>
					<?php
				}
				?>
			</ul>
			</div>
			<p><?php
				return ob_get_clean();
			}

			/**
			 * Add password hints to reset password form.
			 */
			public function add_hint_to_reset_form() {
				if ( isset( $_GET['action'] ) && 'resetpass' === $_GET['action'] ) {
					echo $this->password_hint();
					echo '<style>.indicator-hint { display: none; } #pass1-text { margin-bottom: 0; }</style><br>';
				}
			}

			/**
			 * Remove WCs built in PW meter to avoid conflics -
			 * see https://github.com/WPWhiteSecurity/password-policy-manager/issues/298.
			 */
			public function remove_wc_password_strength() {
				wp_dequeue_script( 'wc-password-strength-meter' );
			}

		}

	}
