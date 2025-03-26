<?php
/**
 * The plugins main options class.
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

if ( ! class_exists( '\MLS\MLS_Options' ) ) {

	/**
	 * Provides options used at run time
	 *
	 * @since 2.0.0
	 */
	class MLS_Options {

		/**
		 * Melapress Login Securitymain class object
		 *
		 * @var Object
		 *
		 * @since 2.0.0
		 */
		private $mls;

		/**
		 * The plugins main options.
		 *
		 * @var array plugin options
		 *
		 * @since 2.0.0
		 */
		private $options = array();

		/**
		 * Inherit Password Policies
		 *
		 * NOTE: this holds the master policy.
		 *
		 * @var $inherit array
		 *
		 * @since 2.0.0
		 */
		public $inherit = array();

		/**
		 * Setting options
		 *
		 * @var array plugin setting
		 *
		 * @since 2.0.0
		 */
		public $setting_options = array();

		/**
		 * Get option by role
		 *
		 * @var object $users_options array
		 *
		 * @since 2.0.0
		 */
		public $users_options = array();

		/**
		 * Melapress Login Securitysettings.
		 *
		 * @var $mls_setting array
		 *
		 * @since 2.0.0
		 */
		public $mls_setting = array();

		/**
		 * Stores an object with the individual roles specific options.
		 *
		 * NOTE: not filled by default, use $this->get_role_options() to access
		 * and fill. Returns an object with the options.
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		public $role_options = array();

		/**
		 * The default options used for the policies.
		 *
		 * @var array Default plugin options
		 *
		 * NOTE: The regex tester for passwords uses the 'rules' key from here
		 *       but setting page and save method looks at the `ui_rules` key.
		 *       The 'refactor' method clones the `ui_rules` into `rules` for
		 *       use with any password checks.
		 *
		 * @since 2.0.0
		 */
		public $default_options = array(
			'master_switch'                             => 'no',
			'activate_password_policies'                => 'no',
			'activate_password_expiration_policies'     => 'no',
			'activate_password_recycle_policies'        => 'no',
			'enforce_password'                          => 'no',
			'min_length'                                => 8,
			'password_history'                          => 1,
			'inherit_policies'                          => 'yes',
			'password_expiry'                           => array(
				'value' => 0,
				'unit'  => 'months',
			),
			'ui_rules'                                  => array(
				'history'               => 'yes',
				'username'              => 'yes',
				'length'                => 'yes',
				'numeric'               => 'yes',
				'mix_case'              => 'yes',
				'special_chars'         => 'yes',
				'exclude_special_chars' => 'no',
			),
			'rules'                                     => array(
				'length'                => 'yes',
				'numeric'               => 'yes',
				'upper_case'            => 'yes',
				'lower_case'            => 'yes',
				'special_chars'         => 'yes',
				'exclude_special_chars' => 'no',
			),
			'change_initial_password'                   => 'no',
			'timed_logins'                              => 'no',
			'timed_logins_schedule'                     => array(
				'monday'    => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'tuesday'   => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'wednesday' => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'thursday'  => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'friday'    => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'saturday'  => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
				'sunday'    => array(
					'enable'        => 'no',
					'from_hr'       => 00,
					'from_min'      => 00,
					'to_hr'         => 11,
					'to_min'        => 59,
					'from_am_or_pm' => 'am',
					'to_am_or_pm'   => 'pm',
				),
			),
			'inactive_users_enabled'                    => 'no',
			'inactive_users_expiry'                     => array(
				'value' => 30,
				'unit'  => 'days',
			),
			'inactive_users_reset_on_unlock'            => 'yes',
			'failed_login_policies_enabled'             => 'no',
			'failed_login_attempts'                     => 5,
			'failed_login_reset_attempts'               => 1440,
			'failed_login_unlock_setting'               => 'unlock-by-admin',
			'failed_login_reset_hours'                  => 60, // Mins @since 3.0.0.
			'failed_login_reset_on_unblock'             => 'yes',
			'disable_self_reset'                        => 'no',
			'disable_self_reset_message'                => '',
			'deactivated_account_message'               => '',
			'timed_login_message'                       => '',
			'locked_user_disable_self_reset'            => 'no',
			'locked_user_disable_self_reset_message'    => '',
			'restrict_login_ip'                         => 'no',
			'restrict_login_ip_count'                   => 3,
			'restrict_login_message'                    => '',
			'notify_password_expiry'                    => 'no',
			'notify_password_reset_on_login'            => 'no',
			'notify_password_expiry_days'               => 3,
			'notify_password_expiry_unit'               => 'days',
			'restrict_login_credentials'                => 'default',
			'restrict_login_credentials_message'        => '',
			'enable_sessions_policies'                  => 'no',
			'remember_session_expiry'                   => array(
				'value' => 14,
				'unit'  => 'days',
			),
			'default_session_expiry'                    => array(
				'value' => 2,
				'unit'  => 'days',
			),
			'enable_device_policies'                    => 'no',
			'enable_device_policies_admin_alerts'       => 'no',
			'enable_security_questions'                 => 'no',
			'enabled_questions'                         => array(),
			'device_policies_prompt_email_content'      => '',
			'device_policies_admin_alert_email_content' => '',
			'device_policies_prompt_email_subject'      => '',
			'device_policies_admin_alert_email_subject' => '',
			'min_answered_needed_count'                 => 3,
			'password_reset_request_disabled_message'   => '',
			'user_exceeded_failed_logins_count_message' => '',
			'password_expired_message'                  => '',
			'inactive_user_account_locked_message'      => '',
			'inactive_user_account_locked_reset_disabled_message' => '',
			'restrict_logins_prompt_failure_message'    => '',
			'timed_logins_login_blocked_message'        => '',
			'restrict_login_ip_login_blocked_message'   => '',
			'failed_logins_login_blocked_message'       => '',
			'security_prompt_response_failure_message'  => '',
			'timed_logins_auto_logout'                  => 'no',
			'login_failed_account_not_known'            => '',
			'login_failed_username_not_known'           => '',
			'login_failed_password_incorrect'           => '',
			'currently_editing_role'                    => '',
			'excluded_special_chars'                    => '',
		);

		/**
		 * Validator rules for default options
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		public static $default_options_validation_rules = array(
			'min_length'               => array(
				'typeRule' => 'number',
				'min'      => '1',
			),
			'password_expiry'          => array(
				'value' => array(
					'typeRule' => 'number',
					'min'      => '0',
				),
				'unit'  => array(
					'typeRule' => 'inset',
					'set'      => array(
						'months',
						'days',
						'hours',
						'seconds',
					),
				),
			),
			'password_history'         => array(
				'typeRule' => 'number',
				'min'      => '0',
				'max'      => '100',
			),
			'inactive_users_expiry'    => array(
				'value' => array(
					'typeRule' => 'number',
					'min'      => '0',
				),
				'unit'  => array(
					'typeRule' => 'inset',
					'set'      => array(
						'months',
						'days',
						'hours',
						'seconds',
					),
				),
			),
			'failed_login_attempts'    => array(
				'typeRule' => 'number',
				'min'      => '1',
			),
			'failed_login_reset_hours' => array(
				'typeRule' => 'number',
				'min'      => '1',
			),
		);

		/**
		 * Set plugin setting options.
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		public $default_setting = array(
			'send_summary_email'                         => 'yes',
			'send_summary_email_day'                     => 'Sunday',
			'exempted'                                   => array(
				'users' => array(),
			),
			'use_custom_from_email'                      => 'default_email',
			'from_email'                                 => '',
			'from_display_name'                          => '',
			'terminate_session_password'                 => 'no',
			'stop_pw_generate'                           => 'no',
			'users_have_multiple_roles'                  => 'no',
			'multiple_role_order'                        => '',
			'clear_history'                              => 'no',
			'excluded_special_chars'                     => '',
			'password_reset_key_expiry'                  => array(
				'value' => 24,
				'unit'  => 'hours',
			),
			'enable_wp_reset_form'                       => 'yes',
			'enable_wp_profile_form'                     => 'yes',
			'enable_wc_pw_reset'                         => 'no',
			'enable_wc_checkout_reg'                     => 'no',
			'enable_bp_register'                         => 'no',
			'enable_bp_pw_update'                        => 'no',
			'enable_ld_register'                         => 'no',
			'enable_um_register'                         => 'no',
			'enable_um_pw_update'                        => 'no',
			'enable_bbpress_pw_update'                   => 'no',
			'enable_mepr_register'                       => 'no',
			'enable_mepr_pw_update'                      => 'no',
			'enable_edd_register'                        => 'no',
			'enable_edd_pw_update'                       => 'no',
			'enable_pmp_register'                        => 'no',
			'enable_pmp_pw_update'                       => 'no',
			'enable_pmp_pw_reset'                        => 'no',
			'enable_profilepress_register'               => 'no',
			'enable_profilepress_pw_update'              => 'no',
			'enable_profilepress_pw_reset'               => 'no',
			'custom_login_url'                           => '',
			'custom_login_redirect'                      => '',
			'enable_login_allowed_ips'                   => 'no',
			'restrict_login_allowed_ips'                 => '',
			'restrict_login_redirect_url'                => '',
			'restrict_login_bypass_slug'                 => '',
			'send_user_unlocked_email'                   => 'yes',
			'send_user_unblocked_email'                  => 'yes',
			'send_user_pw_reset_email'                   => 'yes',
			'send_user_pw_expired_email'                 => 'yes',
			'login_geo_method'                           => 'default',
			'login_geo_action'                           => 'deny_to_url',
			'login_geo_countries'                        => '',
			'login_geo_redirect_url'                     => '',
			'login_geo_blocked_message'                  => '',
			'iplocate_api_key'                           => '',
			'gdpr_banner_message'                        => '',
			'enable_gdpr_banner'                         => 'no',
			// @since 2.0.0
			'disable_user_password_reset_email'          => 'no',
			'disable_user_delayed_password_reset_email'  => 'no',
			'disable_user_pw_expired_email'              => 'no',
			'disable_user_unlocked_reset_needed_email'   => 'no',
			'disable_device_policies_prompt_email'       => 'no',
			'disable_device_policies_prompt_admin_email' => 'no',
			'disable_user_imported_email'                => 'no',
			'disable_user_imported_forced_reset_email'   => 'no',
			'disable_user_unlocked_email'                => 'no',
			'user_unlocked_email_body'                   => '',
			'user_unblocked_email_body'                  => '',
			'user_reset_next_login_email_body'           => '',
			'send_plain_text_emails'                     => 'no',
			'enable_failure_message_overrides'           => 'no',
		);

		/**
		 * Array of text boolean policy settings.
		 *
		 * @var array
		 *
		 * @since 2.1.0
		 */
		public static $policy_boolean_options = array(
			'master_switch',
			'enforce_password',
			'change_initial_password',
			'timed_logins',
			'restrict_login_ip',
			'notify_password_expiry',
			'disable_self_reset',
			'locked_user_disable_self_reset',
			'failed_login_policies_enabled',
			'failed_login_reset_on_unblock',
			'inactive_users_reset_on_unlock',
			'inherit_policies',
			'inactive_users_enabled',
			'activate_password_policies',
			'activate_password_recycle_policies',
			'enable_sessions_policies',
			'enable_device_policies',
			'enable_security_questions',
			'notify_password_reset_on_login',
			'timed_logins_auto_logout',
			'activate_password_expiration_policies',
			'enable_device_policies_admin_alerts',
		);

		/**
		 * Array of text boolean based settings.
		 *
		 * @var array
		 *
		 * @since 2.1.0
		 */
		public static $settings_boolean_options = array(
			'send_summary_email',
			'terminate_session_password',
			'stop_pw_generate',
			'users_have_multiple_roles',
			'clear_history',
			'enable_wp_reset_form',
			'enable_wp_profile_form',
			'enable_wc_pw_reset',
			'enable_wc_checkout_reg',
			'enable_bp_register',
			'enable_bp_pw_update',
			'enable_ld_register',
			'enable_um_register',
			'enable_um_pw_update',
			'enable_bbpress_pw_update',
			'enable_mepr_register',
			'enable_mepr_pw_update',
			'enable_edd_register',
			'enable_edd_pw_update',
			'enable_pmp_register',
			'enable_pmp_pw_update',
			'enable_pmp_pw_reset',
			'enable_profilepress_register',
			'enable_profilepress_pw_update',
			'enable_profilepress_pw_reset',
			'enable_login_allowed_ips',
			'send_user_unlocked_email',
			'send_user_unblocked_email',
			'send_user_pw_reset_email',
			'send_user_pw_expired_email',
			'enable_gdpr_banner',
			'disable_user_password_reset_email',
			'disable_user_delayed_password_reset_email',
			'disable_user_pw_expired_email',
			'disable_user_unlocked_reset_needed_email',
			'disable_device_policies_prompt_email',
			'disable_device_policies_prompt_admin_email',
			'disable_user_imported_email',
			'disable_user_imported_forced_reset_email',
			'disable_user_unlocked_email',
			'enable_failure_message_overrides',
			'send_plain_text_emails',
		);

		/**
		 * Array of text area based settings.
		 *
		 * @var array
		 *
		 * @since 2.1.0
		 */
		public static $textarea_settings = array(
			'disable_self_reset_message',
			'deactivated_account_message',
			'timed_login_message',
			'locked_user_disable_self_reset_message',
			'restrict_login_message',
			'restrict_login_credentials_message',
			'device_policies_prompt_email_content',
			'device_policies_admin_alert_email_content',
			'device_policies_prompt_email_subject',
			'device_policies_admin_alert_email_subject',
			'password_reset_request_disabled_message',
			'user_exceeded_failed_logins_count_message',
			'password_expired_message',
			'inactive_user_account_locked_message',
			'inactive_user_account_locked_reset_disabled_message',
			'restrict_logins_prompt_failure_message',
			'timed_logins_login_blocked_message',
			'restrict_login_ip_login_blocked_message',
			'failed_logins_login_blocked_message',
			'security_prompt_response_failure_message',
			'login_failed_account_not_known',
			'login_failed_username_not_known',
			'login_failed_password_incorrect',
		);

		/**
		 * Array of Password UI boolean based settings.
		 *
		 * @var array
		 *
		 * @since 2.1.0
		 */
		public static $password_ui_boolean_options = array(
			'history',
			'username',
			'length',
			'numeric',
			'mix_case',
			'special_chars',
			'exclude_special_chars',
		);

		/**
		 * Array of Passwird rules boolean based settings.
		 *
		 * @var array
		 *
		 * @since 2.1.0
		 */
		public static $password_rules_boolean_options = array(
			'length',
			'numeric',
			'upper_case',
			'lower_case',
			'special_chars',
			'exclude_special_chars',
		);

		/**
		 * Validator rules for settings options
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		public static $settings_options_validation_rules = array(
			'password_reset_key_expiry' => array(
				'value' => array(
					'typeRule' => 'number',
					'min'      => '0',
				),
				'unit'  => array(
					'typeRule' => 'inset',
					'set'      => array(
						'days',
						'hours',
					),
				),
			),
		);

		/**
		 * Get deactivation message.
		 *
		 * @return string - Markup.
		 *
		 * @since 2.0.0
		 */
		public static function get_default_account_deactivated_message() {
			$admin_email = get_site_option( 'admin_email' );
			$email_link  = '<a href="mailto:' . sanitize_email( $admin_email ) . '">' . __( 'website administrator', 'melapress-login-security' ) . '</a>';
			/* translators: %s: link to admin email. */
			return sprintf( esc_html__( 'Your WordPress user has been deactivated. Please contact the %1s to activate back your user.', 'melapress-login-security' ), $email_link );
		}

		/**
		 * Get options from database and merge with default
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function init() {

			// Store ppm class object.
			$this->mls = melapress_login_security();
			// Default policy.
			$this->inherit                     = get_site_option( MLS_PREFIX . '_options', $this->default_options );
			$this->inherit['inherit_policies'] = 'yes';
			$this->inherit                     = wp_parse_args( $this->inherit, $this->default_options );

			// PPM setting option.
			$this->mls_setting = get_site_option( MLS_PREFIX . '_setting', $this->default_setting );
			if ( $this->mls_setting ) {
				$this->mls_setting = (object) wp_parse_args( $this->mls_setting, $this->default_setting );
			}

			/*
			 * Setting options.
			 */
			$tab_role = ! empty( $_GET['role'] ) ? '_' . sanitize_text_field( wp_unslash( $_GET['role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$mls_default_policy = $this->inherit;

			$settings_tab          = get_site_option( MLS_PREFIX . $tab_role . '_options', $mls_default_policy );
			$this->setting_options = (object) wp_parse_args( $settings_tab, $mls_default_policy );
			$user_role             = '';

			/**
			 * Get user ID Default 0.
			 */
			$user_id = 0;
			// If check user resetpassword key exists OR not.
			if ( isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ) {
				// Get user reset password cookie.
				$username = strstr( sanitize_text_field( wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ), ':', true );
				// Get user by user_login.
				$user_by_login = get_user_by( 'login', $username );
				if ( $user_by_login ) {
					$user_id = $user_by_login->ID;
				}
			} else {
				$user_id = get_current_user_id();
			}

			if ( $user_id ) {
				// If check multisite installed OR not.
				if ( is_multisite() ) {
					// Get user by ID.
					$blog_id = $this->mls->ppm_mu_get_blog_by_user_id( $user_id );
					// Passing an include will limit the User_Query.
					$user_by_id = ( ! wp_doing_cron() ) ? $this->mls->ppm_mu_user_by_blog_id(
						$blog_id,
						array(
							'include' => $user_id,
						)
					) : false;

					// Get user role.
					$roles = isset( $user_by_id[0] ) ? \MLS\Helpers\OptionsHelper::prioritise_roles( $user_by_id[0]->roles ) : false;
					if ( ! $roles ) {
						$user_role = false;
					} else {
						$user_role = reset( $roles );
					}
				} else {
					// Get userdata by user id.
					$userdata = get_userdata( $user_id );
					if ( isset( $userdata->roles ) ) {
						// Get user role.
						$roles     = \MLS\Helpers\OptionsHelper::prioritise_roles( $userdata->roles );
						$user_role = reset( $roles );
					}
				}
			}

			// Get current role in user edit page.
			$current_role = ! empty( $user_role ) ? '_' . $user_role : '';

			$settings = get_site_option( MLS_PREFIX . $current_role . '_options', $this->inherit );

			// Get current user setting.
			$this->options = wp_parse_args( $settings, $this->inherit );

			// Init user role settings.
			$this->user_role_policy();
		}

		/**
		 * Get options for a specific user role.
		 *
		 * @method get_role_options
		 * @param  string $role A role to get options for.
		 *
		 * @return object
		 *
		 * @since 2.0.0
		 */
		public function get_role_options( $role = '' ) {
			if ( empty( $this->role_options[ $role ] ) ) {
				$inherit = $this->inherit;
				$options = get_site_option( MLS_PREFIX . '_' . $role . '_options', $inherit );
				// Ensure we have something passed.
				$options = ( ! $options || empty( $options ) ) ? get_site_option( MLS_PREFIX . '_options', $inherit ) : $options;
				// ensure that we have an object and not an array.
				$options = (object) wp_parse_args( $options, $this->default_options );
				// store the fetched values in property so we don't need to
				// fetch again.
				$this->role_options[ $role ] = $options;
			}
			return $this->role_options[ $role ];
		}

		/**
		 * Current user role policy.
		 *
		 * @return     object|array
		 *
		 * @since 2.0.0
		 */
		public function user_role_policy() {

			global $pagenow;

			/**
			 * When generate password button is clicked (JS Ajax) @see user-profile.js line 261
			 * WP does not have user set in the globals
			 * But the form for resseting the password holds the hidden field with login name
			 * We pass that and check it against DB in order to extract proper user_id
			 *
			 * @todo That entire method depends on the user id in order to extract the proper rules
			 * but it does not accepts any parameters and relies on the globals - refactoring everything
			 * into controller and separate everything would be better approach
			 */
			if ( isset( $_POST['ppm_usr'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

				$user = get_user_by( 'login', sanitize_user( wp_unslash( $_POST['ppm_usr'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( false !== $user ) {
					$_REQUEST['user_id'] = $user->ID;
				}
			}

			/**
			 * Tries to exctract the proper user id - that is called when forms are submitted
			 */
			$get_user_id = isset( $_REQUEST['user_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) ) : get_current_user_id(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			/**
			 * Get user ID Default 0.
			 */
			$user_id = 0;
			/**
			 * The following logic happens when user is using password reset link, WP extracts parameter from
			 * the link and then stores login username in temporarily cookie.
			 * This tries to extract it (user login and ID) from what is stored there
			 *
			 * If there is no such cookie present, falls back to value stored in $get_user_id and continues
			 */
			// If check user resetpassword key exists OR not.
			if ( isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ) {

				$username = strstr( sanitize_text_field( wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) ), ':', true );

				// Get user data by login.
				$user_obj = get_user_by( 'login', $username );
				if ( $user_obj ) {
					$user_id = $user_obj->ID;
				}
			} else {

				$user_id = (int) $get_user_id;

			}

			// If check user ID.
			if ( ! $user_id || wp_doing_cron() ) {
				// If we have no ID, grab the default settings.
				$this->users_options = (object) get_site_option( MLS_PREFIX . '_options', $this->default_options );
				return $this->users_options;
			}

			// If check multisite installed OR not.
			if ( is_multisite() ) {
				// Get user by ID.
				$blog_id    = $this->mls->ppm_mu_get_blog_by_user_id( $user_id );
				$user_by_id = $this->mls->ppm_mu_user_by_blog_id(
					$blog_id,
					array(
						'include' => $user_id,
					)
				);
				// Get included user.
				$included_user = reset( $user_by_id );
				// Get user role.
				$roles = \MLS\Helpers\OptionsHelper::prioritise_roles( $included_user->roles );
				if ( ! $roles ) {
					$user_role = false;
				} else {
					$user_role = reset( $roles );
				}
			} else {
				// Get userdata by user id.
				$userdata = get_userdata( $user_id );
				// Get user role.
				$roles     = \MLS\Helpers\OptionsHelper::prioritise_roles( $userdata->roles );
				$user_role = ( is_array( $roles ) ) ? reset( $roles ) : array();
			}

			// Get current role in user edit page.
			$current_role = ! empty( $user_role ) ? '_' . $user_role : '';

			// Override current role if this is being called via the user-new.php admin screen
			// This means we can then apply the policy for the role submitted, rather than current_user.
			if ( isset( $_POST['action'] ) && 'createuser' === $_POST['action'] && isset( $_POST['role'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$post_array   = filter_input_array( INPUT_POST );
				$current_role = ! empty( $post_array['role'] ) ? '_' . $post_array['role'] : '';
			}

			$settings = get_site_option( MLS_PREFIX . $current_role . '_options' );
			if ( ( ! empty( $settings ) && 0 === \MLS\Helpers\OptionsHelper::string_to_bool( $settings['master_switch'] ) ) || 'user-new.php' === $pagenow ) {

				// Get current user setting.
				$this->users_options = (object) wp_parse_args( $settings, $this->inherit );

			} else {

				$settings = get_site_option( MLS_PREFIX . '_options' );

				if ( ! empty( $settings ) ) {
					if ( \MLS\Helpers\OptionsHelper::string_to_bool( $settings['master_switch'] ) ) {
						// Get current user setting.
						$this->users_options = (object) wp_parse_args( $settings, $this->inherit );
					} else {
						$this->users_options = (object) wp_parse_args( $settings, $this->inherit );

						$this->users_options->enforce_password = 1;
					}
				} else {
					$this->users_options = (object) wp_parse_args( $settings, $this->inherit );
				}
			}

			return $this->users_options;
		}

		/**
		 * Save plugin options in the db and the options object
		 *
		 * @param array $options The options array to save.
		 *
		 * @since 2.0.0
		 */
		public function mls_save_setting( $options ) {

			if ( isset( $options['from_email'] ) && $options['from_email'] ) {
				$options['from_email'] = sanitize_email( $options['from_email'] );
			}

			if ( isset( $options['custom_login_url'] ) && $options['custom_login_url'] ) {
				$options['custom_login_url'] = esc_attr( rtrim( $options['custom_login_url'], '/' ) );
			}
			if ( isset( $options['custom_login_redirect'] ) && $options['custom_login_redirect'] ) {
				$options['custom_login_redirect'] = esc_attr( rtrim( $options['custom_login_redirect'], '/' ) );
			}

			$mls_setting = wp_parse_args( $options, $this->mls_setting );

			$role_order = ( empty( $mls_setting['multiple_role_order'] ) ) ? array() : $mls_setting['multiple_role_order'];

			// Correct bool values.
			$mls_setting['terminate_session_password'] = \MLS\Helpers\OptionsHelper::bool_to_string( $mls_setting['terminate_session_password'] );
			$mls_setting['send_summary_email']         = \MLS\Helpers\OptionsHelper::bool_to_string( $mls_setting['send_summary_email'] );
			$mls_setting['users_have_multiple_roles']  = \MLS\Helpers\OptionsHelper::bool_to_string( $mls_setting['users_have_multiple_roles'] );
			$mls_setting['multiple_role_order']        = array_map( 'esc_attr', $role_order );
			$mls_setting['clear_history']              = \MLS\Helpers\OptionsHelper::bool_to_string( $mls_setting['clear_history'] );

			$this->mls_setting = (object) $mls_setting;

			return update_site_option( MLS_PREFIX . '_setting', $mls_setting );
		}

		/**
		 * Save plugin options in the db and the options object
		 *
		 * @param array $options The options array to save.
		 *
		 * @return bool - Did save or not.
		 *
		 * @since 2.0.0
		 */
		public function mls_save_policy( $options ) {

			$options = $this->refactor( $options );
			// We need options, not default options here in wp_parse_args.
			$this->options = wp_parse_args( $options, $this->options );
			// Get current tab role.
			$tab_role = ! empty( $this->options['ppm-user-role'] ) ? '_' . $this->options['ppm-user-role'] : '';

			$this->setting_options = $this->options;

			/**
			 * Fire of action for others to observe.
			 */
			do_action( 'mls_policies_updated', $this->options, get_site_option( MLS_PREFIX . $tab_role . '_options', false ) );

			return update_site_option( MLS_PREFIX . $tab_role . '_options', $this->options );
		}

		/**
		 * Refactor options submitted through settings form
		 *
		 * @param array $options The options.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		private function refactor( $options ) {

			if ( isset( $options['ui_rules'] ) ) {
				$options['rules']['upper_case'] = $options['ui_rules']['mix_case'];
				$options['rules']['lower_case'] = $options['ui_rules']['mix_case'];
				if ( isset( $options['min_length'] ) && $options['min_length'] > 0 ) {
					$options['rules']['length']    = true;
					$options['ui_rules']['length'] = true;
				}
				$options['rules']['numeric']               = $options['ui_rules']['numeric'];
				$options['rules']['special_chars']         = $options['ui_rules']['special_chars'];
				$options['rules']['exclude_special_chars'] = $options['ui_rules']['exclude_special_chars'];
			}
			if ( isset( $options['excluded_special_chars'] ) && ! empty( $options['excluded_special_chars'] ) ) {
				$options['excluded_special_chars'] = htmlentities( $options['excluded_special_chars'], 0, 'UTF-8' );
			}

			return $options;
		}

		/**
		 * Magic getter for option keys
		 *
		 * @param string $name The name of the option, same as the key in $options.
		 *
		 * @return boolean| mixed False, if can't find or the value of the key
		 *
		 * @since 2.0.0
		 */
		public function __get( $name ) {
			if ( array_key_exists( $name, $this->options ) ) {
				return $this->options[ $name ];
			}

			return false;
		}
	}

}
