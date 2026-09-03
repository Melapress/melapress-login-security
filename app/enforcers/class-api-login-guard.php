<?php
/**
 * Applies the plugin's login policies to Application Password authentication.
 *
 * Every policy this plugin enforces — IP allowlist, country restriction, timed
 * logins, the failed-login throttle, per-user IP restriction and account
 * lockout — is registered on the `authenticate` filter. That filter is only
 * reached through wp_authenticate().
 *
 * Basic-auth Application Passwords do not go through wp_authenticate(). Core
 * hooks wp_validate_application_password() onto `determine_current_user`, and
 * that function calls wp_authenticate_application_password() directly. None of
 * the plugin's `authenticate` callbacks run on that path, so an account blocked
 * at the login form authenticated over the REST API regardless — and an account
 * an administrator had locked kept full API access.
 *
 * Core provides wp_authenticate_application_password_errors for exactly this:
 * it fires after a valid Application Password has been matched, with a WP_Error
 * to add to. Adding to it makes wp_authenticate_application_password() return
 * the error instead of the user, which refuses the request.
 *
 * @package MLS
 *
 * @since 2.4.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\MLS\Api_Login_Guard' ) ) {

	/**
	 * Enforces login policies on the Application Password channel.
	 *
	 * @since 2.4.0
	 */
	class Api_Login_Guard {

		/**
		 * The policy callbacks whose verdict must also apply to an API request.
		 *
		 * These are matched against what is actually registered on
		 * `authenticate`, so this channel enforces exactly the policies the
		 * login form enforces — never more. A policy whose feature is switched
		 * off, or whose module is absent from the build, is not registered and
		 * is therefore not consulted.
		 *
		 * Two `authenticate` callbacks are deliberately absent:
		 *
		 * - Login_Page_Control::login_errors_pre_check only rewrites the wording
		 *   of an error another callback already produced. It makes no decision.
		 *
		 * - Restrict_Login_Credentials::check_desired_credentials re-validates
		 *   the submitted password with wp_authenticate_username_password(). An
		 *   Application Password is not the account password, so replaying that
		 *   check here would refuse every API request. The policy it enforces —
		 *   "log in with your username, not your email address" — is a statement
		 *   about the login form, and core validates this credential type
		 *   itself.
		 *
		 * @var string[]
		 *
		 * @since 2.4.0
		 */
		private const POLICY_CALLBACKS = array(
			'MLS\Failed_Logins::pre_login_check',
			'MLS\RestrictLogins::pre_login_check',
			'MLS\Timed_Logins::check_allow_login',
			'MLS\Login_Page_Control::enforce_login_ip_restriction',
			'MLS\Login_Page_Control::enforce_login_geo_restriction',
			'MLS\Admin\User_Helper::check_user_is_locked',
			'MLS\User_Profile::refuse_until_password_reset',
		);

		/**
		 * Register the guard.
		 *
		 * Covers the REST API and XML-RPC together: core decides whether an
		 * Application Password is acceptable for the request from
		 * `application_password_is_api_request`, which it derives from both
		 * REST_REQUEST and XMLRPC_REQUEST, and this action fires inside the same
		 * function for either.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function init() {
			\add_action(
				'wp_authenticate_application_password_errors',
				array( __CLASS__, 'enforce_policy_on_application_password' ),
				10,
				4
			);
		}

		/**
		 * Refuse an Application Password when policy would refuse the account.
		 *
		 * @param \WP_Error $error    - Error object to add to. Core returns it in place of the user when it has errors.
		 * @param \WP_User  $user     - The user the Application Password belongs to.
		 * @param array     $item     - The Application Password record.
		 * @param string    $password - The password that was supplied.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function enforce_policy_on_application_password( $error, $user, $item = array(), $password = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			if ( ! $error instanceof \WP_Error || ! $user instanceof \WP_User ) {
				return;
			}

			// Core has already refused this request; nothing to add.
			if ( $error->has_errors() ) {
				return;
			}

			$refusal = self::refusal_for( $user );

			if ( ! \is_wp_error( $refusal ) ) {
				return;
			}

			foreach ( $refusal->errors as $code => $messages ) {
				foreach ( $messages as $message ) {
					$error->add( $code, $message );
				}
			}
		}

		/**
		 * Ask the active login policies whether this account may authenticate.
		 *
		 * @param \WP_User $user - The account being authenticated.
		 *
		 * @return \WP_Error|null WP_Error when a policy refuses, null when none does.
		 *
		 * @since 2.4.0
		 */
		public static function refusal_for( \WP_User $user ) {
			$result = $user;

			foreach ( self::active_policy_callbacks() as $callback ) {
				$result = \call_user_func( $callback, $result, $user->user_login, '' );

				if ( \is_wp_error( $result ) ) {
					return $result;
				}

				/*
				 * A callback that returns nothing is expressing no opinion, not a
				 * refusal. In the real `authenticate` chain a void return collapses
				 * to null and wp_authenticate() turns that into a generic failure;
				 * reproducing that here would revoke API access whenever a policy
				 * option is missing, so carry the user forward instead.
				 */
				if ( ! $result instanceof \WP_User ) {
					$result = $user;
				}
			}

			return null;
		}

		/**
		 * The policy callbacks currently registered on `authenticate`.
		 *
		 * Registration style varies across the plugin — a literal class name, a
		 * `__CLASS__` constant and a live object instance are all used — so the
		 * live filter list is read and normalised rather than matched by
		 * callback id.
		 *
		 * @return callable[] Keyed by "Class::method" so a double registration is consulted once.
		 *
		 * @since 2.4.0
		 */
		private static function active_policy_callbacks(): array {
			global $wp_filter;

			if ( empty( $wp_filter['authenticate'] ) || ! isset( $wp_filter['authenticate']->callbacks ) ) {
				return array();
			}

			$active = array();

			foreach ( $wp_filter['authenticate']->callbacks as $priority_group ) {
				foreach ( $priority_group as $registered ) {
					if ( ! isset( $registered['function'] ) || ! is_array( $registered['function'] ) ) {
						continue;
					}

					$callback = $registered['function'];

					if ( 2 !== count( $callback ) || ! is_string( $callback[1] ) ) {
						continue;
					}

					$class = is_object( $callback[0] )
						? \get_class( $callback[0] )
						: ltrim( (string) $callback[0], '\\' );

					$id = $class . '::' . $callback[1];

					if ( in_array( $id, self::POLICY_CALLBACKS, true ) ) {
						$active[ $id ] = $callback;
					}
				}
			}

			return $active;
		}
	}
}
