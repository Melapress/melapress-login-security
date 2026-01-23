<?php
/**
 * MLS Security Prompt Class.
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
 * Check if this class already exists.
 *
 * @since 2.0.0
 */
if ( ! class_exists( '\MLS\Security_Prompt' ) ) {

	/**
	 * Declare SessionsManager Class
	 *
	 * @since 2.0.0
	 */
	class Security_Prompt {

		/**
		 * Is prompt currently deemed to be required?
		 *
		 * @var boolean
		 *
		 * @since 2.0.0
		 */
		public static $prompt_needed = false;

		/**
		 * User login name.
		 *
		 * @var boolean
		 *
		 * @since 2.0.0
		 */
		public static $user_login = '';

		/**
		 * Init hooks.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			add_action( 'ppm_settings_additional_settings', array( __CLASS__, 'settings_markup' ), 10, 2 );

			// Only load further if needed.
			if ( ! OptionsHelper::get_plugin_is_enabled() ) {
				return;
			}

			add_action( 'lostpassword_form', array( __CLASS__, 'security_prompt_markup' ) );
			add_action( 'lostpassword_errors', array( __CLASS__, 'validate_lostpassword_form' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
			add_action( 'wp_login_failed', array( __CLASS__, 'is_blocked_check' ), 100, 2 );
			add_action( 'login_form', array( __CLASS__, 'security_prompt_markup' ), 10 );
			add_filter( 'wp_authenticate_user', array( __CLASS__, 'validate_unlock' ), 0, 1 );
			add_filter( 'login_message', array( __CLASS__, 'render_login_page_message' ), 10, 1 );

			global $pagenow;
			if ( 'profile.php' !== $pagenow || 'user-edit.php' !== $pagenow ) {
				add_action( 'show_user_profile', array( __CLASS__, 'user_profile_form' ), 30 );
				add_action( 'edit_user_profile', array( __CLASS__, 'user_profile_form' ), 30 );
				add_action( 'personal_options_update', array( __CLASS__, 'save_profile_form' ) );
				add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile_form' ) );
			}

			add_filter( 'admin_notices', array( __CLASS__, 'answers_needed_notice' ), 10, 3 );
			add_action( 'ppm_message_settings_markup_footer', array( __CLASS__, 'add_template_settings' ), 100 );
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
						<h3><?php esc_html_e( 'Security prompt question response failure', 'melapress-login-security' ); ?></h3>
						<p class="description"><?php esc_html_e( 'This notification is shown on the password reset page if a user fails to provide the correct response to the security question.', 'melapress-login-security' ); ?></p>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Message', 'melapress-login-security' ); ?>
						</th>
						<td style="padding-right: 15px;">
							<fieldset>
								<?php
								$content   = \MLS\EmailAndMessageStrings::get_email_template_setting( 'security_prompt_response_failure_message' );
								$editor_id = 'mls_options_security_prompt_response_failure_message';
								$settings  = array(
									'media_buttons' => false,
									'editor_height' => 200,
									'textarea_name' => 'mls_options[security_prompt_response_failure_message]',
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
		 * Add settings markup.
		 *
		 * @param  string $markup - HTML.
		 * @param  object $settings_tab - Settings.
		 *
		 * @return string $markup - appended HTML.
		 *
		 * @since 2.0.0
		 */
		public static function settings_markup( $markup, $settings_tab ) {
			$test_mode = apply_filters( 'mls_enable_testing_mode', false );
			$units     = array(
				'hours'  => __( 'hours', 'melapress-login-security' ),
				'days'   => __( 'days', 'melapress-login-security' ),
				'months' => __( 'months', 'melapress-login-security' ),
			);
			if ( $test_mode ) {
				$units['seconds'] = __( 'seconds', 'melapress-login-security' );
			}

			ob_start();
			?>
				<tr valign="top">
					<th scope="row">
						<?php esc_attr_e( 'Security questions', 'melapress-login-security' ); ?>
					</th>
					<td>
						<fieldset>
							<input name="mls_options[enable_security_questions]" type="checkbox" id="ppm-enable-security-question" data-toggle-target=".security-questions-row" value="yes" <?php checked( OptionsHelper::string_to_bool( $settings_tab->enable_security_questions ) ); ?>>
							<?php esc_attr_e( 'Activate Security questions', 'melapress-login-security' ); ?>
							<p class="description">
								<?php esc_html_e( 'Activate this setting to require users to answer a pre-provided question to proceed with certain actions, such as, to reset a password.', 'melapress-login-security' ); ?>
							</p>
							<br>
						</fieldset>

						<div class="security-questions-row">
							<fieldset>
								<label for="ppm-enable_device_policies_admin_alerts">
									<input name="mls_options[enable_device_policies_admin_alerts]" type="checkbox" id="ppm-enable_device_policies_admin_alerts" data-toggle-target=".send-admin-alert-row" value="1" <?php checked( OptionsHelper::string_to_bool( $settings_tab->enable_device_policies_admin_alerts ) ); ?>>
									<?php esc_html_e( 'Require security question to initiate a password reset', 'melapress-login-security' ); ?>
								</label>
								<br>

								<label for="ppm-enable_device_policies_admin_alerts">
									<input name="mls_options[enable_device_policies_admin_alerts]" type="checkbox" id="ppm-enable_device_policies_admin_alerts" data-toggle-target=".send-admin-alert-row" value="1" <?php checked( OptionsHelper::string_to_bool( $settings_tab->enable_device_policies_admin_alerts ) ); ?>>
									<?php esc_html_e( 'Require security question to enable a disabled account', 'melapress-login-security' ); ?>
								</label>
								<br>
								<br>
								<?php
									ob_start();
								?>
								<input id="prompt-counter" name="mls_options[min_answered_needed_count]" type="number" value="<?php echo esc_attr( $settings_tab->min_answered_needed_count ); ?>" min="2" max="10" size="4" class="tiny-text ltr" required/>
								<?php
									$input_history = ob_get_clean();
									/* translators: %s: Configured number of old password to check for duplication. */
									printf( esc_html__( 'Users must have at least %s pre-saved questions and answers.', 'melapress-login-security' ), wp_kses( $input_history, OptionsHelper::get_allowed_kses_args() ) );
								?>
								<br>
								<br>
								<strong><?php esc_html_e( 'Configure the security questions:', 'melapress-login-security' ); ?></strong>
								<br>
								<br>

								<p class="description">
									<?php esc_html_e( 'Below you can configure the default list of questions available for the users.', 'melapress-login-security' ); ?>
								</p>
								<br>

								<ul id="questions-wrapper">
									<?php
									$default_question_list = array_keys( self::get_default_questions() );
									foreach ( self::get_questions( $settings_tab->enabled_questions ) as $id => $question ) {
										$checked = 'checked';
										$label   = 'Disable';
										$class   = '';
										$href    = '#disable';
										if ( ! empty( $settings_tab->enabled_questions ) && ! in_array( $id, array_keys( $settings_tab->enabled_questions ), true ) ) {
											$checked = '';
										}
										if ( empty( $checked ) ) {
											$class = 'disabled-question';
											$label = 'Enable';
										}
										if ( ! in_array( $id, $default_question_list, true ) ) {
											$class .= ' custom';
											$label  = 'Delete';
											$href   = '#remove';
										}

										echo '<li class="question-list-item ' . esc_attr( $class ) . ' "><span class="dashicons dashicons-sort"></span><label>' . wp_kses_post( $question ) . '</label> <input type="checkbox" id="' . esc_attr( $id ) . '" name="mls_options[enabled_questions][' . esc_attr( $id ) . ']" value="' . wp_kses_post( $question ) . '" ' . esc_attr( $checked ) . '><a href="' . esc_attr( $href ) . '">' . esc_attr( $label ) . '</a></li>';
									}
									?>
								</ul>
								<a href="#add-question" class="button button-secondary"><?php esc_html_e( 'Add question', 'melapress-login-security' ); ?></a>
							</fieldset>
						</div>
						
						<br>
						<p class="description">
							<?php
								$messages_settings = '<a href="' . add_query_arg( 'page', 'mls-settings#message-settings', network_admin_url( 'admin.php' ) ) . '"> ' . __( 'User notices templates', 'ppw-wp' ) . '</a>';
							?>
							<?php echo wp_sprintf( __( 'To customize the notification displayed to users should they fail a prompt, please visit the %s plugin settings.', 'melapress-login-security' ), wp_kses_post( $messages_settings ) ); ?>
						</p>
					</td>
			<?php
			return $markup . ob_get_clean();
		}

		/**
		 * Add form to user profile area.
		 *
		 * @param WP_User $user - user to reset.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function user_profile_form( $user ) {
			// Get current user, we going to need this regardless.
			$current_user = wp_get_current_user();

			// Bail if we still dont have an object.
			if ( ! is_a( $user, '\WP_User' ) || ! is_a( $current_user, '\WP_User' ) ) {
				return;
			}

			if ( ! self::is_feature_enabled_for_user( $user ) ) {
				return;
			}

			$role_options    = OptionsHelper::get_preferred_role_options( $user->roles );
			$users_responses = self::get_user_responses( $user->ID );

			$user_being_browsed = isset( $_GET['user_id'] ) ? get_user_by( 'id', (int) $_GET['user_id'] ) : false;
			$show_summary       = false;
			if ( isset( $user_being_browsed->ID ) && $user_being_browsed->ID !== $current_user->ID ) {
				$show_summary = true;
			}
			?>
			<h2><?php esc_html_e( 'Security questions', 'melapress-login-security' ); ?></h2>
			<table class="form-table" role="presentation" id="mls-user-security-form">
				<tbody><tr class="user-pass1-wrap">
					<th><label for="reset_password"><?php esc_html_e( 'Available questions', 'melapress-login-security' ); ?></label></th>
					<?php
					if ( $show_summary ) {
						$responses_count = self::get_user_responses( $user_being_browsed->ID, 'all', true );
						?>
						<td>	
							<?php
							/* translators: %s: Configured number of old password to check for duplication. */
							printf( esc_html__( 'User has provided a total of %s answers currently.', 'melapress-login-security' ), '<strong>' . esc_attr( count( $responses_count ) ) . '</strong>' );
							?>
						</td>
						<?php

					} else {
						?>
					<td>				
						<?php
						/* translators: %s: Configured number of old password to check for duplication. */
						printf( esc_html__( 'To improve the security of your account, please provide answers to at least %s of security questions. Click the "Add response" button to start. One of these questions will be used to verify your identity if you request a password reset or need to unlock your account after it has been locked.', 'melapress-login-security' ), '<strong>' . esc_attr( $role_options->min_answered_needed_count ) . '</strong>' );
						?>
						<br><br>
						<a href="#add-response" class="button button-secondary"><?php esc_html_e( 'Add response', 'melapress-login-security' ); ?></a>
						

						<div id="add-response-wrapper" style="display: none;">
							<label for="cars"><strong><?php esc_html_e( 'Select a question:', 'melapress-login-security' ); ?></strong></label><br>
							<p><?php esc_html_e( 'Please note, your provided answer will be case sensitive.', 'melapress-login-security' ); ?><br><br>
							<select name="cars" id="selected-response">
								<?php
								foreach ( $role_options->enabled_questions as $id => $question ) {
									$value = isset( $users_responses[ $id ] ) ? $users_responses[ $id ] : '';
									?>
										<option value="<?php echo esc_attr( $id ); ?>"><?php echo wp_kses_post( $question ); ?></option>
									<?php
								}
								?>
							</select>
							<input type="text" id="selected-response-value"> <a href="#confirm-response" class="button button-primary">Confirm</a> <a href="#cancel-response" class="button button-secondary"><?php esc_html_e( 'Cancel', 'melapress-login-security' ); ?></a>
						</div>

						<br>
						<br>
						
						<div id="users-responses-wraper">
							<?php
							foreach ( $role_options->enabled_questions as $id => $question ) {
								$value = isset( $users_responses[ $id ] ) ? $users_responses[ $id ] : '';
								?>
										<div class="user-response-item">
											<label class="question" for="<?php echo esc_attr( $id ); ?>"><strong><?php echo wp_kses_post( $question ); ?></strong></label> <input type="password" id="<?php echo esc_attr( $id ); ?>" name="mls_security_prompt_response[<?php echo esc_attr( $id ); ?>]" value="<?php echo esc_html( $value ); ?>" readonly> <a href="#remove-response"><?php esc_html_e( 'Remove', 'melapress-login-security' ); ?></a>
											<br>
											<br>
										</div>
									<?php
							}
							?>
						</div>
						<?php wp_nonce_field( 'pmls_reset_on_next_login', 'mls_user_profile_nonce' ); ?>
						<br>
					</td>
						<?php
					}
					?>
					</tr>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Notice shown to users if policies dictate they are to answer prompts but have not yet done so.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function answers_needed_notice() {
			$user = wp_get_current_user();

			if ( ! is_a( $user, '\WP_User' ) ) {
				return;
			}

			if ( self::is_feature_enabled_for_user( $user ) ) {
				$role_options = OptionsHelper::get_preferred_role_options( $user->roles );
				$needed       = (int) $role_options->min_answered_needed_count;
				$given_count  = count( self::get_user_responses( $user->ID, 'all', true ) );
				$remaining    = $needed - $given_count;
				if ( $given_count < $needed ) {
					?>
					<div class="notice notice-warning mls-security-notice" style="margin-left: 0;">
						<?php
						printf(
							'<p><strong>%1s</strong><br>%2s</p><p>%3s</p><p>%4s</p>',
							esc_html__( 'Notice:', 'melapress-login-security' ),
							esc_html__( 'You are required to provide an answer to a number of security questions to better protect your account.', 'melapress-login-security' ),
							esc_html__( 'Please provide a further ', 'melapress-login-security' ) . $remaining . esc_html__( ' responses to ensure your account is protected.', 'melapress-login-security' ),
							'<a class="button button-primary" href="' . esc_url( network_admin_url( 'profile.php' ) ) . '#mls-user-security-form">' . esc_html__( 'Visit your profile page', 'melapress-login-security' ) . '</a>',
						);
						?>
					</div>
					<?php
				}
			}
		}

		/**
		 * Check if the answer provide to a user when self-unlocking is correct.
		 *
		 * @param WP_User|WP_Error $user - Current user/error.
		 *
		 * @return WP_User|WP_Error
		 *
		 * @since 2.0.0
		 */
		public static function validate_unlock( $user ) {
			$nonce = isset( $_POST['mls_security_prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['mls_security_prompt'] ) ) : false;

			if ( wp_verify_nonce( $nonce, 'mls_security_prompt' ) && isset( $_POST['log'] ) && isset( $_POST['security-answer'] ) ) {
				$username        = sanitize_text_field( wp_unslash( $_POST['log'] ) );
				$user            = self::get_user_from_username( $username );
				$role_options    = OptionsHelper::get_preferred_role_options( $user->roles );
				$users_response  = sanitize_text_field( wp_unslash( self::get_user_responses( $user->ID, key( wp_unslash( $_POST['security-answer'] ) ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$posted_response = isset( $_POST['security-answer'][ key( wp_unslash( $_POST['security-answer'] ) ) ] ) ? sanitize_text_field( wp_unslash( $_POST['security-answer'][ key( wp_unslash( $_POST['security-answer'] ) ) ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( $posted_response !== $users_response ) {
					// Provided answer does not match.
					$query_args = array(
						'security-response' => 'fail',
						'user-id'           => $user->ID,
					);
					$login_url  = esc_url_raw( add_query_arg( $query_args, wp_login_url() ) );
					wp_safe_redirect( $login_url );

				} else {
					// Provided answer match.
					$failed_logins = new \MLS\Failed_Logins();
					$failed_logins->clear_failed_login_data( $user->ID, false );
					$reset_password = OptionsHelper::string_to_bool( $role_options->failed_login_reset_on_unblock ) || OptionsHelper::string_to_bool( $role_options->inactive_users_reset_on_unlock );
					$failed_logins->send_logins_unblocked_notification_email_to_user( $user->ID, $reset_password );
					OptionsHelper::clear_inactive_data_about_user( $user->ID );

					// remember this reset time.
					OptionsHelper::set_user_last_expiry_time( current_time( 'timestamp' ), $user->ID ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

					$query_args = array(
						'security-response' => 'pass',
					);
					$login_url  = esc_url_raw( add_query_arg( $query_args, wp_login_url() ) );
					wp_safe_redirect( $login_url );
				}
			}

			return $user;
		}

		/**
		 * Get users responses.
		 *
		 * @param int     $user_id - Lookup user ID.
		 * @param string  $all_or_single - 'all' to get all items, or specify an id.
		 * @param boolean $answered_responses_only - Return only questions to which an answer has been given, or all possible questions for this user.
		 *
		 * @return array - Responses.
		 *
		 * @since 2.0.0
		 */
		public static function get_user_responses( $user_id, $all_or_single = 'all', $answered_responses_only = false ) {
			$users_responses = get_user_meta( $user_id, MLS_PREFIX . '_security_prompt_responses' );
			$users_responses = isset( $users_responses[0] ) ? maybe_unserialize( $users_responses[0] ) : array();

			if ( empty( $users_responses ) ) {
				return $users_responses;
			}

			if ( 'all' !== $all_or_single ) {
				$users_responses = isset( $users_responses[ $all_or_single ] ) && ! empty( $users_responses[ $all_or_single ] ) ? $users_responses[ $all_or_single ] : '';
			}

			if ( $answered_responses_only ) {
				foreach ( $users_responses as $id => $answer ) {
					if ( empty( $answer ) ) {
						unset( $users_responses[ $id ] );
					}
				}
			}

			return $users_responses;
		}

		/**
		 * Save user profile data.
		 *
		 * @param int $user_id - Users profile ID.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function save_profile_form( $user_id ) {
			if ( ! isset( $_POST['mls_user_profile_nonce'] ) || ( isset( $_POST['mls_user_profile_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mls_user_profile_nonce'] ) ), 'pmls_reset_on_next_login' ) ) ) {
				return;
			}

			$user = get_user_by( 'id', $user_id );

			if ( ! is_a( $user, '\WP_User' ) ) {
				return;
			}

			if ( ! self::is_feature_enabled_for_user( $user ) || ! isset( $_POST['mls_security_prompt_response'] ) ) {
				return;
			}

			$role_options       = OptionsHelper::get_preferred_role_options( $user->roles );
			$needed             = (int) $role_options->min_answered_needed_count;
			$given_count        = 0;
			$provided_responses = array_map( 'sanitize_text_field', wp_unslash( $_POST['mls_security_prompt_response'] ) );

			foreach ( $provided_responses as $id => $answer ) {
				if ( ! empty( $answer ) ) {
					++$given_count;
				}
			}

			update_user_meta( $user_id, MLS_PREFIX . '_security_prompt_responses', maybe_serialize( $provided_responses ) );
		}

		/**
		 * Add notice to user profile if not enough answers are given.
		 *
		 * @param WP_Error $errors - Current errors.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function more_answers_needed( $errors ) {
			$errors->add( 'mls_update', __( 'Please provide further responses' ) );
		}

		/**
		 * Add sortable JS to admin.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function enqueue_scripts() {
			$screen = get_current_screen();
			if ( 'toplevel_page_mls-policies' === $screen->id ) {
				wp_enqueue_script( 'jquery-ui-sortable' );
			}
		}

		/**
		 * Default possible questions
		 *
		 * @return array - Our questions.
		 *
		 * @since 2.0.0
		 */
		public static function get_default_questions() {
			$list = array(
				'nickname'    => esc_attr__( 'What was your childhood nickname?', 'melapress-login-security' ),
				'birth'       => esc_attr__( 'What was your place of birth?', 'melapress-login-security' ),
				'meetpartner' => esc_attr__( 'In which city did you meet your spouse / partner?', 'melapress-login-security' ),
				'pet'         => esc_attr__( 'What was the name of your childhood pet?', 'melapress-login-security' ),
				'holiday'     => esc_attr__( 'Where did you go for holiday as a child?', 'melapress-login-security' ),
			);
			return apply_filters( 'mls_default_security_questions_list', $list );
		}

		/**
		 * Get array of default questions + any custom ones added.
		 *
		 * @param array $enabled_questions - List of enabled questions.
		 *
		 * @return array $questions - Full list.
		 *
		 * @since 2.0.0
		 */
		public static function get_questions( $enabled_questions ) {
			$defaults  = self::get_default_questions();
			$questions = array_merge( $enabled_questions, $defaults );
			return $questions;
		}

		/**
		 * Handle form markup for security prompt.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function security_prompt_markup() {
			$display_none = isset( $_REQUEST['action'] ) && 'lostpassword' === $_REQUEST['action'] ? '' : 'style="display: none;"';
			ob_start();

			if ( self::$prompt_needed && ! empty( self::$user_login ) ) {
				$user         = self::get_user_from_username( self::$user_login );
				$role_options = OptionsHelper::get_preferred_role_options( $user->roles );

				if ( count( self::get_user_responses( $user->ID, 'all', true ) ) === 0 ) {
					return;
				}

				$enabled_questions = $role_options->enabled_questions;
				$our_question      = array_rand( self::get_user_responses( $user->ID, 'all', true ) );

				// Check in case question has since been disable by admin and select another.
				if ( ! isset( $enabled_questions[ $our_question ] ) ) {
					$lookup       = array_rand( array_keys( $enabled_questions ) );
					$our_question = self::get_user_responses( $user->ID, $lookup, true );
				}

				$question_text = apply_filters( 'mls_security_prompt_question_label_text', $enabled_questions[ $our_question ], $our_question );
				?>
					<div id="mls-security-prompt" <?php echo esc_attr( $display_none ); ?>>
						<label><?php echo wp_kses_post( $question_text ); ?></label>
						<input id="security-prompt" name="security-answer[<?php echo esc_attr( $our_question ); ?>]" class="input" value="">
						<?php wp_nonce_field( 'mls_security_prompt', 'mls_security_prompt' ); ?>
					</div>
					<script type="text/javascript">
						window.addEventListener("load", (event) => {
							const button = document.getElementById( "show-prompt" );
							button.addEventListener("click", (event) => {
								document.getElementById( "mls-security-prompt" ).style.display = 'block';
								document.getElementById( "wp-submit" ).value = 'Proceed';
								document.getElementById( "user_pass" ).removeAttribute( 'required' );
								document.getElementById( "user_pass" ).value = 'prompt';
								
								document.getElementsByClassName('user-pass-wrap')[0].style.display = 'none';
							});
						});		
					</script>
				<?php
			}
			echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Check security response for lost password requests.
		 *
		 * @param WP_Error $errors - Current errors.
		 *
		 * @return WP_Error $errors - Current errors, with more added if needed.
		 *
		 * @since 2.0.0
		 */
		public static function validate_lostpassword_form( $errors ) {
			self::$user_login = ( isset( $_POST['user_login'] ) && ! empty( $_POST['user_login'] ) ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! empty( self::$user_login ) && ! isset( $_POST['security-answer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$user = self::get_user_from_username( self::$user_login );

				if ( ! is_a( $user, '\WP_User' ) ) {
					return $errors;
				}

				if ( ! self::is_feature_enabled_for_user( $user ) ) {
					return $errors;
				}

				// Check user has answers.
				$users_response = self::get_user_responses( $user->ID, 'all', true );
				if ( empty( $users_response ) ) {
					return $errors;
				}

				self::$prompt_needed = true;
				$errors              = new \WP_Error();
				$errors->add( 'password_reset_empty_space', __( 'Please provide the answer to the following security question' ) );

				return $errors;

			} elseif ( ! empty( self::$user_login ) && isset( $_POST['security-answer'] ) && ! empty( $_POST['security-answer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

				$user            = self::get_user_from_username( self::$user_login );
				$security_answer = array_map( 'esc_attr', wp_unslash( $_POST['security-answer'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitize, d WordPress.Security.NonceVerification.Missing
				$response_id     = key( $security_answer );
				$users_response  = self::get_user_responses( $user->ID, $response_id );
				$posted_response = isset( $_POST['security-answer'][ $response_id ] ) ? sanitize_text_field( wp_unslash( $_POST['security-answer'][ $response_id ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing


				if ( $posted_response !== $users_response ) {
					$errors = new \WP_Error();
					$errors->add( 'password_reset_empty_space', \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'security_prompt_response_failure_message' ), $user->ID ) );
					return $errors;
				}
			}

			return $errors;
		}

		/**
		 * Method: Render login page message.
		 *
		 * @param string $message - Login message.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public static function render_login_page_message( $message ) {
			if ( isset( $_GET['security-response'] ) && ! empty( $_GET['security-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['security-response'] ) && 'pass' === $_GET['security-response'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					// Get login message.
					$message = esc_html__( 'Account unlocked, you may now login normally.', 'melapress-login-security' );
				} elseif ( isset( $_GET['user-id'] ) && isset( $_GET['security-response'] ) && 'fail' === $_GET['security-response'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					// Get login message.
					$message = \MLS\EmailAndMessageStrings::replace_email_strings( \MLS\EmailAndMessageStrings::get_email_template_setting( 'security_prompt_response_failure_message' ), (int) sanitize_text_field( wp_unslash( $_GET['user-id'] ) ) );
				}
				$message = '<p class="message">' . $message . '</p>';
			}

			// Return message.
			return $message;
		}

		/**
		 * Get a user's data from the username, which could be email or loginn ame.
		 *
		 * @param string $username - Username to lookup.
		 *
		 * @return WP_User|bool - Return object of false.
		 *
		 * @since 2.0.0
		 */
		public static function get_user_from_username( $username ) {
			$user = false;
			if ( filter_var( $username, FILTER_VALIDATE_EMAIL ) ) {
				$user = get_user_by( 'email', $username );
			} else {
				$user = get_user_by( 'login', $username );
			}
			return $user;
		}

		/**
		 * Check if this feature is enabled for a given user.
		 *
		 * @param WP_User $user - Lookup user.
		 *
		 * @return boolean
		 *
		 * @since 2.0.0
		 */
		public static function is_feature_enabled_for_user( $user ) {
			if ( \MLS_Core::is_user_exempted( $user->ID ) ) {
				return false;
			}
			$role_options = OptionsHelper::get_preferred_role_options( $user->roles );
			if ( ! isset( $role_options->enable_security_questions ) || ! OptionsHelper::string_to_bool( $role_options->enable_security_questions ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Check login failures to see if they are for the reasons we monitor.
		 *
		 * @param string   $username - Username.
		 * @param WP_Error $errors - Current errors.
		 *
		 * @return boolean
		 *
		 * @since 2.0.0
		 */
		public static function is_blocked_check( $username, $errors ) {
			$user = self::get_user_from_username( $username );

			if ( ! is_a( $user, '\WP_User' ) ) {
				return;
			}

			if ( ! self::is_feature_enabled_for_user( $user ) ) {
				return;
			}

			if ( count( self::get_user_responses( $user->ID, 'all', true ) ) === 0 ) {
				return;
			}

			// Append message to error, if its one we target.
			if ( in_array( MLS_PREFIX . '_login_attempts_exceeded', array_keys( $errors->errors ), true ) ) {
				$errors->add( MLS_PREFIX . '_login_attempts_exceeded', '<br>Or <a href="#" id="show-prompt">click here</a> to answer a security question.' );
				self::$prompt_needed = true;
				self::$user_login    = $username;
			} elseif ( in_array( 'inactive_user', array_keys( $errors->errors ), true ) ) {
				$errors->add( 'inactive_user', '<br>Or <a href="#" id="show-prompt">click here</a> to answer a security question.' );
				self::$prompt_needed = true;
				self::$user_login    = $username;
			}
		}
	}
}
