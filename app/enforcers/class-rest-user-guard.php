<?php
/**
 * Applies the plugin's password and profile policies to REST user writes.
 *
 * @package MelapressLoginSecurity
 * @since 2.4.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if this class already exists.
 *
 * @since 2.4.0
 */
if ( ! class_exists( '\MLS\Rest_User_Guard' ) ) {

	/**
	 * REST counterpart to the `user_profile_update_errors` enforcement.
	 *
	 * Every password control in this plugin used to hang off
	 * `user_profile_update_errors`. That hook fires from exactly one place in
	 * WordPress — `edit_user()` in wp-admin/includes/user.php — so it covers the
	 * profile screens and nothing else. `WP_REST_Users_Controller` assigns
	 * `user_pass` itself and calls `wp_update_user()` directly, which fires no
	 * such hook, so `PUT /wp/v2/users/me` walked straight past the password
	 * rules, the password history, the "current password required" gate and the
	 * security question on email change.
	 *
	 * That mattered most for exactly the controls it defeated: requiring the
	 * current password exists to stop somebody holding a stolen session from
	 * taking permanent ownership of the account, and the email-change question
	 * is what blocks the change-email-then-request-a-reset takeover. Both were
	 * reachable by a subscriber acting on their own account.
	 *
	 * `rest_request_before_callbacks` is used rather than the more obvious
	 * `rest_pre_insert_user`, because core does not check for a WP_Error
	 * returned from that filter: `update_item()` assigns `$user->ID` on the
	 * result and passes it to `wp_update_user()` regardless, so returning an
	 * error there corrupts the write instead of stopping it. Returning a
	 * WP_Error here aborts cleanly with a proper HTTP response.
	 *
	 * Each check registers itself from the enforcer that owns it, so it
	 * inherits that enforcer's existing enablement conditions rather than
	 * re-deriving them.
	 *
	 * @since 2.4.0
	 */
	class Rest_User_Guard {

		/**
		 * Cover password composition rules and password history.
		 *
		 * Called from Password_Check::hook(), which only runs when the policy
		 * master switch is on and password enforcement is not disabled.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function hook_password_policy() {
			\add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'check_password_policy' ), 10, 3 );
		}

		/**
		 * Cover the "current password required" gate.
		 *
		 * Called from Require_Current_Password::init().
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function hook_current_password() {
			\add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'check_current_password' ), 10, 3 );
		}

		/**
		 * Cover the security question on email change.
		 *
		 * Called from Security_Prompt::init().
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function hook_email_question() {
			\add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'check_email_question' ), 10, 3 );
		}

		/**
		 * Identify a user create/update handled by the core users controller.
		 *
		 * Returns null for everything else, including requests another filter
		 * has already short-circuited.
		 *
		 * @param mixed           $response Current short-circuit value.
		 * @param array           $handler  Route handler.
		 * @param \WP_REST_Request $request Request.
		 *
		 * @return array|null { user_id: int, creating: bool } or null.
		 *
		 * @since 2.4.0
		 */
		private static function resolve_target( $response, $handler, $request ) {
			// Somebody has already decided the outcome; stay out of the way.
			if ( \is_wp_error( $response ) || $response instanceof \WP_REST_Response ) {
				return null;
			}

			if ( ! \class_exists( '\WP_REST_Users_Controller' ) ) {
				return null;
			}

			if ( ! is_array( $handler ) || empty( $handler['callback'] ) || ! is_array( $handler['callback'] ) ) {
				return null;
			}

			$controller = isset( $handler['callback'][0] ) ? $handler['callback'][0] : null;
			$method     = isset( $handler['callback'][1] ) ? (string) $handler['callback'][1] : '';

			if ( ! $controller instanceof \WP_REST_Users_Controller ) {
				return null;
			}

			switch ( $method ) {
				case 'create_item':
					return array(
						'user_id'  => 0,
						'creating' => true,
					);

				case 'update_item':
					return array(
						'user_id'  => \absint( $request['id'] ),
						'creating' => false,
					);

				case 'update_current_item':
					// `/users/me` only gets its `id` populated by the permission
					// callback, which runs *after* this filter.
					return array(
						'user_id'  => \get_current_user_id(),
						'creating' => false,
					);
			}

			return null;
		}

		/**
		 * Whether the caller is allowed to perform this write at all.
		 *
		 * This filter runs immediately before the route's permission callback,
		 * so without this the guard would answer a policy error to callers who
		 * should simply have been refused. Let core produce the 401/403.
		 *
		 * @param array $target Resolved target.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		private static function caller_may_write( $target ) {
			if ( $target['creating'] ) {
				return \current_user_can( 'create_users' );
			}

			return $target['user_id'] > 0 && \current_user_can( 'edit_user', $target['user_id'] );
		}

		/**
		 * The submitted password, or '' when none is being set.
		 *
		 * Never sanitised: a password is an opaque string and filtering it here
		 * would validate something other than what gets stored.
		 *
		 * @param \WP_REST_Request $request Request.
		 *
		 * @return string
		 *
		 * @since 2.4.0
		 */
		private static function submitted_password( $request ) {
			if ( ! isset( $request['password'] ) || ! is_scalar( $request['password'] ) ) {
				return '';
			}

			return (string) $request['password'];
		}

		/**
		 * Turn a set of policy violations into readable messages.
		 *
		 * The two validators disagree on shape — validate_for_user() returns a
		 * list of violation names, does_violate_rules() returns them as keys —
		 * so accept either.
		 *
		 * @param mixed $violations Violations from either validator.
		 *
		 * @return array
		 *
		 * @since 2.4.0
		 */
		private static function violation_messages( $violations ) {
			if ( empty( $violations ) || ! is_array( $violations ) ) {
				return array();
			}

			$names = array();
			foreach ( $violations as $key => $value ) {
				$names[] = is_string( $key ) ? $key : (string) $value;
			}

			$strings  = isset( \MLS\MLS_Messages::$error_strings ) ? \MLS\MLS_Messages::$error_strings : array();
			$messages = array();

			foreach ( array_unique( $names ) as $name ) {
				if ( isset( $strings[ $name ] ) ) {
					$messages[] = \wp_strip_all_tags( (string) $strings[ $name ] );
				}
			}

			return $messages;
		}

		/**
		 * Reject a password that does not satisfy the policy.
		 *
		 * @param mixed            $response Current short-circuit value.
		 * @param array            $handler  Route handler.
		 * @param \WP_REST_Request $request  Request.
		 *
		 * @return mixed
		 *
		 * @since 2.4.0
		 */
		public static function check_password_policy( $response, $handler, $request ) {
			$target = self::resolve_target( $response, $handler, $request );

			if ( null === $target || ! self::caller_may_write( $target ) ) {
				return $response;
			}

			$password = self::submitted_password( $request );

			if ( '' === $password ) {
				return $response;
			}

			$check  = new Password_Check();
			$errors = new \WP_Error();

			if ( $target['creating'] ) {
				// No account yet, so no per-user policy to resolve: check
				// against the active policy, as the registration integrations do.
				$violations = $check->does_violate_rules( $password, true );
			} else {
				// Resolves the target's role policy and covers password history.
				$violations = $check->validate_for_user( $target['user_id'], $password, 'reset-form-return', $errors );
			}

			$messages = self::violation_messages( $violations );

			if ( empty( $messages ) ) {
				return $response;
			}

			return new \WP_Error(
				'mls_password_policy',
				\esc_html__( 'The password does not meet the security policy for this site.', 'melapress-login-security' ),
				array(
					'status'     => 400,
					'violations' => $messages,
				)
			);
		}

		/**
		 * Require the current password before changing it.
		 *
		 * Only ever asked of somebody changing their own password. An
		 * administrator resetting another account cannot know that account's
		 * password, and this control exists to protect the account holder
		 * against a hijacked session rather than to constrain administrators.
		 *
		 * @param mixed            $response Current short-circuit value.
		 * @param array            $handler  Route handler.
		 * @param \WP_REST_Request $request  Request.
		 *
		 * @return mixed
		 *
		 * @since 2.4.0
		 */
		public static function check_current_password( $response, $handler, $request ) {
			if ( ! \class_exists( '\MLS\Require_Current_Password' ) ) {
				return $response;
			}

			$target = self::resolve_target( $response, $handler, $request );

			if ( null === $target || $target['creating'] || ! self::caller_may_write( $target ) ) {
				return $response;
			}

			if ( $target['user_id'] !== \get_current_user_id() ) {
				return $response;
			}

			if ( '' === self::submitted_password( $request ) ) {
				return $response;
			}

			if ( \MLS_Core::is_user_exempted( $target['user_id'] ) ) {
				return $response;
			}

			if ( ! Require_Current_Password::is_policy_enabled_for_user( $target['user_id'] ) ) {
				return $response;
			}

			$submitted = isset( $request['mls_current_password'] ) && is_scalar( $request['mls_current_password'] )
				? (string) $request['mls_current_password']
				: '';

			if ( '' === $submitted ) {
				return new \WP_Error(
					'mls_current_password_required',
					\esc_html__( 'You must supply your current password, as mls_current_password, to change your password.', 'melapress-login-security' ),
					array( 'status' => 400 )
				);
			}

			$user_data = \get_userdata( $target['user_id'] );

			if ( ! $user_data || ! \wp_check_password( $submitted, $user_data->user_pass, $target['user_id'] ) ) {
				return new \WP_Error(
					'mls_current_password_incorrect',
					\esc_html__( 'The current password you entered is incorrect.', 'melapress-login-security' ),
					array( 'status' => 403 )
				);
			}

			return $response;
		}

		/**
		 * Require the security question before an email change.
		 *
		 * Mirrors validate_email_change_security_question(), including the
		 * shared rate limiter, and uses the same field names so a client only
		 * has to learn one contract.
		 *
		 * Self-service only, matching the wp-admin side: that check is
		 * registered on `personal_options_update`, which fires for a user
		 * editing their own profile and not for an administrator editing
		 * somebody else's.
		 *
		 * @param mixed            $response Current short-circuit value.
		 * @param array            $handler  Route handler.
		 * @param \WP_REST_Request $request  Request.
		 *
		 * @return mixed
		 *
		 * @since 2.4.0
		 */
		public static function check_email_question( $response, $handler, $request ) {
			if ( ! \class_exists( '\MLS\Security_Prompt' ) ) {
				return $response;
			}

			$target = self::resolve_target( $response, $handler, $request );

			if ( null === $target || $target['creating'] || ! self::caller_may_write( $target ) ) {
				return $response;
			}

			if ( $target['user_id'] !== \get_current_user_id() ) {
				return $response;
			}

			if ( ! isset( $request['email'] ) || ! is_scalar( $request['email'] ) ) {
				return $response;
			}

			$user = \get_userdata( $target['user_id'] );

			if ( ! $user ) {
				return $response;
			}

			$new_email = \sanitize_email( (string) $request['email'] );

			if ( '' === $new_email || $new_email === $user->user_email ) {
				return $response;
			}

			if ( ! Security_Prompt::is_email_change_sq_required_for_user( $user ) ) {
				return $response;
			}

			$question_id = isset( $request['mls_email_change_security_question_id'] ) && is_scalar( $request['mls_email_change_security_question_id'] )
				? \sanitize_text_field( (string) $request['mls_email_change_security_question_id'] )
				: '';
			$answer      = isset( $request['mls_email_change_security_answer'] ) && is_scalar( $request['mls_email_change_security_answer'] )
				? \sanitize_text_field( (string) $request['mls_email_change_security_answer'] )
				: '';

			if ( '' === $answer ) {
				return new \WP_Error(
					'mls_email_change_sq_required',
					\esc_html__( 'Please answer your security question, as mls_email_change_security_answer, to change your email address.', 'melapress-login-security' ),
					array( 'status' => 400 )
				);
			}

			if ( Security_Prompt::is_unlock_rate_limited( $target['user_id'] )
				|| ! Security_Prompt::verify_answer( $target['user_id'], $question_id, $answer ) ) {

				Security_Prompt::record_failed_unlock_attempt( $target['user_id'] );

				return new \WP_Error(
					'mls_email_change_sq_failed',
					\esc_html__( 'Incorrect security question answer. Your email address has not been changed.', 'melapress-login-security' ),
					array( 'status' => 403 )
				);
			}

			Security_Prompt::clear_unlock_attempts( $target['user_id'] );

			return $response;
		}
	}
}
