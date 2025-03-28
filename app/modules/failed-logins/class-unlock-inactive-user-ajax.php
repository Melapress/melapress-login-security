<?php
/**
 * An ajax action to handle resetting inactive users,
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Ajax;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

/**
 * An abstract class to be used when creating ajax actions. This ensures a consistent
 * way of using them and invoking them.
 *
 * @since 2.0.0
 */
class UnlockInactiveUser implements AjaxInterface {

	/**
	 * The string used to generate/check nonces.
	 *
	 * @var string
	 *
	 * @since 2.0.0
	 */
	const NONCE_KEY = 'inactive_users_nonce';

	/**
	 * Caller.
	 *
	 * @var string
	 *
	 * @since 2.0.0
	 */
	public $caller;

	/**
	 * Sets up the properties for this ajax endpoint.
	 *
	 * @method __construct
	 * @param  {InactiveUsers} $caller Instance of the main InactiveUsers class.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function __construct( $caller ) {
		$this->caller = $caller;
	}


	/**
	 * Register the handler and nonce, this is the entrypoint.
	 *
	 * @method register
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function register() {
		add_action( 'wp_ajax_mls_unlock_inactive_user', array( $this, 'reset_inactive_user' ) );
	}

	/**
	 * Calls the method for this ajax action.
	 *
	 * @method reset_inactive_user
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function reset_inactive_user() {
		$this->action();
	}

	/**
	 * The action to run, optionally this can just register the hook.
	 *
	 * @method action
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function action() {
		check_ajax_referer( self::NONCE_KEY );
		// get the ids list as an array.
		$id = ( isset( $_POST['user'] ) ) ? (int) $_POST['user'] : 0;

		$reset_time = OptionsHelper::set_user_last_expiry_time( current_time( 'timestamp' ), $id ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		$userdata = get_user_by( 'id', $id );

		$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

		// Check if we are currently unblocking a user, rather than clearing a users inactivity.
		$currently_unlocking_user_logins = ( isset( $_POST['unblocking_user'] ) && 'true' === $_POST['unblocking_user'] ) ? true : false;

		if ( $currently_unlocking_user_logins ) {
			/**
			 * Fire of action for others to observe.
			 */
			do_action( 'mls_user_locked_due_to_failed_logins_unlocked', $userdata->ID );
			$failed_logins           = new \MLS\Failed_Logins();
			$clear_failed_login_data = $failed_logins->clear_failed_login_data( $userdata->ID, false );
			$reset_password          = OptionsHelper::string_to_bool( $role_options->failed_login_reset_on_unblock );
			$failed_logins->send_logins_unblocked_notification_email_to_user( $userdata->ID, $reset_password );
			// Now we can bailed, firing off a success beacon to anything watching.
			wp_send_json_success(
				array(
					'user_id'    => $id,
					'reset_time' => gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $reset_time ),
				)
			);
		}

		/**
		 * Fire of action for others to observe.
		*/
		do_action( 'mls_user_locked_due_to_inactivity_unlocked', $userdata->ID );

		// remove user from inactive list.
		OptionsHelper::clear_inactive_data_about_user( $userdata->ID, true );

		// remember this reset time.
		OptionsHelper::set_user_last_expiry_time( current_time( 'timestamp' ), $userdata->ID ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		$reset_password = OptionsHelper::string_to_bool( $role_options->inactive_users_reset_on_unlock );
		$this->send_unlocked_notification_email_to_user( $userdata->ID, $reset_password );

		wp_send_json_success(
			array(
				'user_id'    => $userdata->ID,
				'reset_time' => gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $reset_time ),
			)
		);
	}

	/**
	 * Can be used to check the nonce passed for this action.
	 *
	 * @method check_nonce
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public static function check_nonce() {
		check_ajax_referer( self::NONCE_KEY );
	}

	/**
	 * Send user a notification email once the account has been unlocked, also reset password if required.
	 *
	 * @param  int  $user_id                User ID to notify.
	 * @param  bool $reset_password     Set to true if we are resetting the users PW and emailing them a reset link.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function send_unlocked_notification_email_to_user( $user_id, $reset_password ) {

		// Access plugin instance.
		$mls = melapress_login_security();

		// Grab user data object.
		$user_data = get_userdata( $user_id );

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		// Prepare email details.
		$from_email = $mls->options->mls_setting->from_email ? $mls->options->mls_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
		$from_email = sanitize_email( $from_email );
		$headers[]  = 'From: ' . $from_email;

		// Only reset the password if the role has this option enabled.
		if ( $reset_password ) {
			$key = get_password_reset_key( $user_data );
			if ( ! is_wp_error( $key ) ) {
				$update = update_user_meta( $user_id, MLS_USER_RESET_PW_ON_LOGIN_META_KEY, $key );
			}
		}

		$args = array();

		if ( $reset_password ) {
			if ( \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->disable_user_unlocked_reset_needed_email ) ) {
				return;
			}
			$title = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_unlocked_reset_needed_email_subject' );
		} else {
			if ( \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->disable_user_unlocked_email ) ) {
				return;
			}
			$title = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_unlocked_email_subject' );
		}

		if ( $reset_password ) {
			$login_page                = OptionsHelper::get_password_reset_page();
			$args['reset_or_continue'] = esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) . "\n";
		}

		if ( $reset_password ) {
			$content = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_unlocked_reset_needed_email_body' );
		} else {
			$content = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_unlocked_email_body' );
		}

		$title         = \MLS\EmailAndMessageStrings::replace_email_strings( $title, $user_id );
		$email_content = \MLS\EmailAndMessageStrings::replace_email_strings( $content, $user_id, $args );

		// Only send the email if applicable.
		if ( ( ! isset( $mls->options->mls_setting->disable_user_unlocked_email ) ) || ( isset( $mls->options->mls_setting->disable_user_unlocked_email ) && ! \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->disable_user_unlocked_email ) ) ) {
			// Fire off the mail.
			\MLS\Emailer::send_email( $user_email, wp_specialchars_decode( $title ), $email_content, $headers );
		}
	}
}
