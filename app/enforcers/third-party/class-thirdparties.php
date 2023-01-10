<?php
/**
 * PPM ThirdParties
 *
 * @package wordpress
 * @subpackage wpassword
 * @author WP White Security
 */

// If check class exists OR not.
if ( ! class_exists( 'PPM_ThirdParties' ) ) {
	/**
	 * Declare PPM_ThirdParties Class
	 */
	class PPM_ThirdParties {

		/**
		 * Init hooks.
		 */
		public function init() { 

			$ppm = ppm_wp();

			// WooCommerce.
			if ( $ppm->options->ppm_setting->enable_wc_pw_reset ) {
				add_filter( 'woocommerce_save_account_details_errors', array( $this, 'ppmwp_detect_pw_errors' ), 10, 2 );
				add_filter( 'validate_password_reset', array( $this, 'ppmwp_validate_wc_password_reset' ), 10, 2 );
				add_filter( 'ppm_enable_custom_forms_array', array( $this, 'ppm_enable_custom_form' ), 20 );
			}

			// BuddyPress User Reg.
			if ( isset( $ppm->options->ppm_setting->enable_bp_register ) && $ppm->options->ppm_setting->enable_bp_register ) {
				add_filter( 'ppm_enable_custom_forms_array', array( $this, 'ppm_enable_custom_bp_user_profile_form' ), 20 );
				add_action( 'bp_members_validate_user_password', array( $this, 'bp_validate_pw_change' ), 10, 4 );
			}

			// BuddyPress PW Update.
			if ( isset( $ppm->options->ppm_setting->enable_bp_pw_update ) && $ppm->options->ppm_setting->enable_bp_pw_update ) {
				add_filter( 'ppm_enable_custom_forms_array', array( $this, 'ppm_enable_custom_bp_reg_form' ), 20 );
				add_action( 'bp_members_validate_user_password', array( $this, 'bp_validate_pw_change' ), 10, 4 );
			}			

			// LearnDash User Reg.
			if ( isset( $ppm->options->ppm_setting->enable_ld_register ) && $ppm->options->ppm_setting->enable_ld_register ) {
				add_filter( 'ppm_enable_custom_forms_array', array( $this, 'ppm_enable_custom_leanrdash_reg_form' ), 20 );
				add_filter( 'registration_errors', array( $this, 'learndash_registration_form_validate' ) );
				add_filter( 'learndash-registration-errors', array( $this, 'learndash_registration_errors' ) );
			}

			// Ultimate Member Register.
			if ( isset( $ppm->options->ppm_setting->enable_um_register ) && $ppm->options->ppm_setting->enable_um_register ) {
				add_filter( 'ppm_enable_custom_forms_array', array( $this, 'ppm_enable_custom_um_reg_form' ), 20 );
				add_action( 'um_add_error_on_form_submit_validation', array( $this, 'um_error_on_form_submit_validation' ), 10, 3 );
			}
			
			// Ultimate PW Update.
			if ( isset( $ppm->options->ppm_setting->enable_um_pw_update ) && $ppm->options->ppm_setting->enable_um_pw_update ) {
				add_filter( 'ppm_enable_custom_forms_array', array( $this, 'ppm_enable_custom_um_pw_update_form' ), 20 );
				add_action( 'um_change_password_errors_hook', array( $this, 'um_change_password_errors' ), 10, 1 );
			}

			// BBpress PW Update.
			if ( isset( $ppm->options->ppm_setting->enable_bbpress_pw_update ) && $ppm->options->ppm_setting->enable_bbpress_pw_update ) {
				add_filter( 'ppm_enable_custom_forms_array', array( $this, 'ppm_enable_custom_bbpress_pw_update_form' ), 20 );
			}
		}			

		public function ppm_enable_custom_form( $args ) {
			$new = array(
				array(
					'form_selector'        => '.edit-account',
					'pw_field_selector'    => '#password_1',
					'form_submit_selector' => '#submit_password',
					'elements_to_hide'     => '#old_pw_hints',
				)
			);
			return array_merge( $args, $new );
		}

		public function ppm_enable_custom_bp_user_profile_form( $args ) {
			$new = array(
				array(
					'form_selector'        => '#your-profile .user-pass1-wrap',
					'pw_field_selector'    => '#pass1',
					'form_submit_selector' => '.button',
					'elements_to_hide'     => '#pass-strength-result',
				)
			);
			return array_merge( $args, $new );
		}

		public function ppm_enable_custom_bp_reg_form( $args ) {
			$new = array(
				array(
					'form_selector'        => '.register-page .user-pass1-wrap',
					'pw_field_selector'    => '#pass1',
					'form_submit_selector' => '.button',
					'elements_to_hide'     => '#pass-strength-result',
				)
			);
			return array_merge( $args, $new );
		}

		public function ppm_enable_custom_leanrdash_reg_form( $args ) {
			$new = array(
				array(
					'form_selector'        => '.learndash-registration-field-password',
					'pw_field_selector'    => '#password',
					'form_submit_selector' => '.button',
					'elements_to_hide'     => '.learndash-password-strength',
				)
			);
			return array_merge( $args, $new );
		}

		public function ppm_enable_custom_um_reg_form( $args ) {
			$new = array(
				array(
					'form_selector'        => '.um-register',
					'pw_field_selector'    => '.um-field-password [data-key="user_password"]',
					'form_submit_selector' => '.button',
					'elements_to_hide'     => '#pass-strength-result',
				)
			);
			return array_merge( $args, $new );
		}

		public function ppm_enable_custom_um_pw_update_form( $args ) {
			$new = array(
				array(
					'form_selector'        => '.um-account',
					'pw_field_selector'    => '#um_field_password_user_password input',
					'form_submit_selector' => '.button',
					'elements_to_hide'     => '#pass-strength-result',
				)
			);
			return array_merge( $args, $new );
		}

		public function ppm_enable_custom_bbpress_pw_update_form( $args ) {
			$new = array(
				array(
					'form_selector'        => '.user-pass1-wrap',
					'pw_field_selector'    => '#pass1',
					'form_submit_selector' => '.button',
					'elements_to_hide'     => '#pass-strength-result',
				)
			);
			return array_merge( $args, $new );
		}
		
		public function ppmwp_detect_pw_errors( $errors, $user ) {
			if ( isset( $user->ID ) ) {
				$ppmwp = new \PPM_WP_Password_Check();
				$password_errors = new \WP_Error;

				// Get input value for password we want to check.
				$password = $user->user_pass;

				// Fire off validity check.
				$is_valid = $ppmwp->validate_for_user( $user->ID, $password, 'reset-form', $password_errors );

				if ( $password_errors->errors ) {
					// If we have errors, it means the PW did not meet policy requirements.
					// $errors contains simple array of useful messages/reasons for failure.
					foreach ( $password_errors->errors as $key => $message ) {
						$errors->add( $key, $message[0] );
					}

				}
			}
			return $errors;
		}

		public  function ppmwp_validate_wc_password_reset( $errors, $user ) {
			if ( isset( $user->ID ) ) {
				$ppmwp = new \PPM_WP_Password_Check();
				$password_errors = new \WP_Error;

				// Get input value for password we want to check.
				$password = isset( $_POST[ 'password_1' ] ) ? $_POST[ 'password_1' ] : false;

				// Fire off validity check.
				$is_valid = $ppmwp->validate_for_user( $user->ID, $password, 'reset-form', $password_errors );

				if ( $password_errors->errors ) {
					// If we have errors, it means the PW did not meet policy requirements.
					// $errors contains simple array of useful messages/reasons for failure.
					foreach ( $password_errors->errors as $key => $message ) {
						$errors->add( $key, $message[0] );
					}

				}
			}
		}

		public function bp_validate_pw_change( $errors, $pass, $confirm_pass, $userdata ) {			
			
			if ( isset( $userdata['ID'] ) ) {
				$ppmwp = new \PPM_WP_Password_Check();
				$password_errors = new \WP_Error;

				// Get input value for password we want to check.
				$password = isset( $_POST[ 'pass1' ] ) ? $_POST[ 'pass1' ] : false;

				// Fire off validity check.
				$is_valid = $ppmwp->validate_for_user( $userdata['ID'], $password, 'reset-form', $password_errors );

				if ( $password_errors->errors ) {
					// If we have errors, it means the PW did not meet policy requirements.
					// $errors contains simple array of useful messages/reasons for failure.
					foreach ( $password_errors->errors as $key => $message ) {
						$errors->add( $key, $message[0] );
					}

				}
			} else if ( isset( $_POST['signup_username'] ) ) {
				$bp = buddypress();
				$ppm = ppm_wp();
				$pwd_check = new PPM_WP_Password_Check();
				$does_violate_rules = $pwd_check->does_violate_rules( $_POST[ 'signup_password' ], true );

				if ( $does_violate_rules ) {
					$error_strings = $ppm->msgs->error_strings;
					foreach ( $does_violate_rules as $violation => $value ) {
						$errors->add( $violation, $error_strings[ $violation ] );
					}
				}
			}
			return $errors;
		}	

		public function learndash_registration_form_validate( $errors ) {
			if ( isset( $_POST['ld_register_id'] ) ) {
				$ppm = ppm_wp();
				$pwd_check = new PPM_WP_Password_Check();
				$does_violate_rules = $pwd_check->does_violate_rules( $_POST[ 'password' ], true );

				if ( $does_violate_rules ) {
					$error_strings = $ppm->msgs->error_strings;
					foreach ( $does_violate_rules as $violation => $value ) {
						$errors->add( $violation, $error_strings[ $violation ] );
					}
				}
			}

			return $errors;
		}

		public function learndash_registration_errors( $errors_conditions ) {
			$ppm = ppm_wp();
			$errors_conditions = array_merge( $errors_conditions, $ppm->msgs->error_strings );
			return $errors_conditions;
		}

		public function um_error_on_form_submit_validation( $array, $key, $args ) {
			if ( isset( $args[ 'user_password' ] ) ) {
				$ppm = ppm_wp();
				$pwd_check = new PPM_WP_Password_Check();
				$does_violate_rules = $pwd_check->does_violate_rules( $args[ 'user_password' ], true );

				if ( $does_violate_rules ) {
					$error_strings = $ppm->msgs->error_strings;
					foreach ( $does_violate_rules as $violation => $value ) {
						UM()->form()->add_error( $violation, $error_strings[ $violation ] );
					}
				}
			}
		}

		public function um_change_password_errors( $args ) {
			if ( isset( $_POST[ 'user_password' ] ) ) {
				$ppm = ppm_wp();
				$pwd_check = new PPM_WP_Password_Check();
				$does_violate_rules = $pwd_check->does_violate_rules( $args[ 'user_password' ], true );

				if ( $does_violate_rules ) {
					$error_strings = $ppm->msgs->error_strings;
					foreach ( $does_violate_rules as $violation => $value ) {
						UM()->form()->add_error( 'user_password', $error_strings[ $violation ] );
					}
					return;
				}
			}
		}
	}
}
