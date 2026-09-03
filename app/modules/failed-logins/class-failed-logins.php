<?php
/**
 * Melapress Login Security failed logins check.
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

use MLS\Emailer;
use MLS\Helpers\OptionsHelper;

/**
 * Check if this class already exists.
 *
 * @since 2.0.0
 */
if ( ! class_exists( '\MLS\Failed_Logins' ) ) {
	/**
	 * Declare Failed_Logins Class
	 *
	 * @since 2.0.0
	 */
	class Failed_Logins {

		/**
		 * Init hooks.
		 *
		 * @since 2.0.0
		 */
		/**
		 * Action and nonce name behind the "Refresh users lock status" button.
		 *
		 * @var string
		 *
		 * @since 2.4.0
		 */
		public const REFRESH_LOCK_STATUS_ACTION = 'mls_refresh_lock_status';

		public static function init() {
			add_filter( 'mls_login_policies_settings', array( __CLASS__, 'failed_login_settings_markup' ), 50, 2 );

			// Only load further if needed.
			if ( ! OptionsHelper::get_plugin_is_enabled() ) {
				return;
			}

			add_action( 'wp_login', array( __CLASS__, 'clear_failed_login_data' ), 100, 2 );
			// Count Learndash failed logins.
			add_filter( 'learndash_safe_redirect_location', array( __CLASS__, 'learndash_login_error_check' ), 10, 3 );
			// Add JS to Memberpress login page.
			add_action( 'mepr-login-form-before-submit', array( __CLASS__, 'memberpress_login_form_js' ), 10 );
			add_action( 'admin_init', array( __CLASS__, 'register_ajax' ) );
			add_action( 'mls_enqueue_admin_scripts', array( __CLASS__, 'register_scripts' ) );
		}

		/**
		 * Add JS into memberpress login form to create front-end error messages in case of login failure
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function memberpress_login_form_js() {
			?>
			<script type="text/javascript">
				if ( window.location.href.indexOf('mls_errors') > 0 ) {
					setTimeout(() => {
						var errorString = window.location.href.split('errors=')[1];
						var errorArray = errorString.split(',');			

						var lockedLockedMarkup = '<div class="mepr_pro_error" id="mepr_jump"><svg xmlns="http://www.w3.org/2000/svg" style="min-width: 48px;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><ul><li>Too many failed login attempts. Access from this device or network is temporarily blocked.</li></ul></div>';		
			
						jQuery.each( errorArray, function ( index, value ) {
							if ( jQuery.trim( value ) == 'mls_login_locked' ) {
								jQuery( '.mepro-login-contents' ).prepend( lockedLockedMarkup );	
							}

							var left = jQuery.trim( value ).split( '=' );

							if ( jQuery.trim( value ).indexOf( 'attempts_remaining' ) >= 0 ) {
								var remainingMarkup = '<div class="mepr_pro_error" id="mepr_jump"><svg xmlns="http://www.w3.org/2000/svg" style="min-width: 48px;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><ul><li>You have ' + left[1] + ' attempts remaining.</li></ul></div>';	
								jQuery( '.mepro-login-contents' ).prepend( remainingMarkup );		
							}
						});
						
						var basicErrorMarkup = '<div class="mepr_pro_error" id="mepr_jump"><svg xmlns="http://www.w3.org/2000/svg"  width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><ul><li>Your username or password was incorrect</li></ul></div>';	
			
						jQuery( '.mepro-login-contents' ).prepend( basicErrorMarkup );	

					}, 50);
				}
			</script>
			<?php
		}

		/**
		 * This function runs on Learndash's redirect function which they use to handle login failures.
		 * It passes the usernaem into our logic check so the failure can be counted
		 *
		 * @param  string $location - Current location.
		 * @param  string $status - Error status.
		 * @param  string $context - Error context.
		 *
		 * @return string $location - Current location, unmodified by us.
		 *
		 * @since 2.0.0
		 */
		public static function learndash_login_error_check( $location, $status, $context ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			$found = strpos( $location, 'login=failed#login' );
			if ( false !== $found ) {
				$username = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				self::failed_login_check( $username, 'learndash_login_failure_count' );
			}

			return $location;
		}

		/**
		 * Check login to determine if the user is currently blocked
		 *
		 * @param  mixed  $user         WP_User if the user is authenticated. WP_Error or null otherwise.
		 * @param  string $username     Username or email address.
		 * @param  string $password     ser password.
		 *
		 * @return null|WP_User|WP_Error
		 *
		 * @since 2.0.0
		 */
		public static function pre_login_check( $user, $username, $password ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			// If WP has already created an error at this point, pass it back and bail.
			if ( is_wp_error( $user ) || null === $user ) {
				return $user;
			}

			// Get the user ID, either from the user object if we have it, or by SQL query if we dont.
			if ( $user instanceof \WP_User && isset( $user->ID ) ) {
				$user_id = $user->ID;
			} else {
				$user    = \get_user_by( 'login', $username );
				$user_id = ( $user instanceof \WP_User && isset( $user->ID ) ) ? $user->ID : null;
			}

			// If we still have nothing, stop here.
			if ( ! $user_id ) {
				return $user;
			}

			// Return if this user is exempt.
			if ( \MLS_Core::is_user_exempted( $user_id ) || get_user_meta( $user_id, 'mls_reset_pw_on_login', true ) ) {
				return $user;
			}

			$user = get_user_by( 'id', $user_id );

			$role_options = OptionsHelper::get_preferred_role_options( $user->roles );

			if ( OptionsHelper::string_to_bool( $role_options->failed_login_policies_enabled ) ) {
				$global_attempts = (int) get_site_transient( self::get_global_source_attempts_transient_name() );
				$source_limit    = max( 1, (int) $role_options->failed_login_attempts );

				// This source is abusing the login form across accounts, not just
				// this one. Nothing an individual account holder does should lift
				// it, so it stays a flat refusal that gives nothing away.
				if ( $global_attempts >= max( 20, $source_limit * 5 ) ) {
					return new \WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: The username or password is incorrect.', 'melapress-login-security' ) );
				}

				// This account has run out of attempts from this source.
				//
				// Reported as a lockout rather than as a bad password, for two
				// reasons. It is true — the credentials are no longer being
				// checked at all — and telling the caller their password was
				// wrong when it was not sends them to reset a password that
				// works. It also carries the error code the rest of the plugin
				// keys off, so the security-question prompt is offered and the
				// account holder has a way back in. Answering correctly clears
				// this counter through clear_failed_login_data().
				//
				// The throttle deliberately stays scoped to the account/source
				// pair: no account-wide flag is set, so an unauthenticated
				// attacker still cannot lock a known username out of its own
				// account from somewhere else.
				if ( self::is_source_locked_out( $user_id, $source_limit ) ) {
					return new \WP_Error(
						MLS_PREFIX . '_login_attempts_exceeded',
						__( 'Too many failed login attempts from this device or network. Access from here is temporarily blocked.', 'melapress-login-security' )
					);
				}

				// Aggregate backstop: too many failures against this account
				// from too many different sources. Short and self-expiring, and
				// reported with the same code so the security question is
				// offered — an account holder with answers stored can end it
				// immediately instead of waiting out an attacker.
				if ( self::is_account_rate_limited( $user_id ) ) {
					return new \WP_Error(
						MLS_PREFIX . '_login_attempts_exceeded',
						__( 'Too many failed login attempts on this account. Logins are temporarily paused.', 'melapress-login-security' )
					);
				}

				if ( 'timed' === $role_options->failed_login_unlock_setting ) {

					$login_attempts_transient = self::get_users_stored_transient_data( $user_id, true );
					$current_time             = time();

					// See if enough time has passed since last failed attempt.
					$time_difference = ( ! empty( $login_attempts_transient ) ) ? $current_time - $login_attempts_transient < $role_options->failed_login_reset_hours * 60 : false;

					// Enough time has passed and the user is allowed to reset.
					// R4 — Use full unlock so the activity timestamp resets and user
					// is not immediately re-locked by the inactivity policy.
					if ( ! $time_difference ) {
						OptionsHelper::fully_unlock_user( $user_id, 'blocked' );
					}
				}

				// The account-wide block flag this used to read is no longer
				// written by anything — the failed-login policy enforces via the
				// per-source throttle checked above, which has already returned
				// by this point. Reading it here only made the branch look live.
			}

			// We must return the user, regardless.
			return $user;
		}

		/**
		 * Logs failed attempt in a transient and determine if this failed attempt surpasses the threshold number of allowed attempts.
		 *
		 * @param  Array  $username Currently logging in user name.
		 * @param  Object $error    Current errors object.
		 *
		 * @return $error           Error object with our errors appended to it.
		 *
		 * @since 2.0.0
		 */
		public static function failed_login_check( $username, $error = false ) {

			// If user is using an email, act accordingly.
			if ( filter_var( $username, FILTER_VALIDATE_EMAIL ) ) {
				$userdata = get_user_by( 'email', $username );
			} else {
				$userdata = get_user_by( 'login', $username );
			}

			// If we still have nothing, stop here.
			if ( ! $userdata || ! $error ) {
				return;
			}

			/*
			 * Do not count an attempt this plugin refused itself.
			 *
			 * `wp_authenticate()` fires `wp_login_failed` for *any* WP_Error it
			 * ends up returning, including the ones produced above — so a request
			 * rejected because the account is already locked came back here and was
			 * counted as a fresh failure. The caller's own allowance was being
			 * burned by a lockout somebody else caused, and with the attempts
			 * countdown restored below it also produced two contradictory
			 * sentences on one screen: "Access from here is temporarily blocked"
			 * next to "You have 2 attempts remaining."
			 *
			 * The intent was already here — the list of codes was simply missing
			 * the plugin's own lockout and the ones the other enforcers raise.
			 */
			$own_refusals = array(
				'login_not_allowed',
				'password_expired',
				MLS_PREFIX . '_login_attempts_exceeded',
				MLS_PREFIX . '_login_attempts_blocked',
			);

			foreach ( $own_refusals as $own_refusal ) {
				if ( isset( $error->errors[ $own_refusal ] ) ) {
					return;
				}
			}

			// Return if this user is exempt.
			if ( \MLS_Core::is_user_exempted( $userdata->ID ) || get_user_meta( $userdata->ID, 'mls_reset_pw_on_login', true ) ) {
				return;
			}

			$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

			// Check if user is already locked by any mechanism (R3 — no stacking).
			// A user who is already locked cannot accumulate additional locks.
			$existing_lock = OptionsHelper::is_user_locked_by_any_mechanism( $userdata->ID );

			if ( OptionsHelper::string_to_bool( $role_options->failed_login_policies_enabled ) && ! $existing_lock ) {
				// Setup needed variables for later.
				$max_login_attempts            = max( 1, (int) $role_options->failed_login_attempts );
				$login_attempts_transient_name = self::get_source_attempts_transient_name( $userdata->ID );

				$user_id         = $userdata->ID;
				$attempts_timer  = (int) ( ( ! isset( $role_options->failed_login_reset_attempts ) ) ? 1440 : $role_options->failed_login_reset_attempts );
				$transient_timer = $attempts_timer * 60;

				/*
				 * A count, incremented in the store. This used to be a list of
				 * timestamps read into PHP, appended to and written back, which
				 * lost every concurrent failure but one — so failing logins in
				 * parallel bought as many extra guesses as the attacker cared to
				 * open connections. Only the number was ever read back out.
				 */
				$attempts_so_far = OptionsHelper::increment_counter( $login_attempts_transient_name, $transient_timer, true );

				OptionsHelper::increment_counter( self::get_global_source_attempts_transient_name(), $transient_timer, true );

				// Throttle only this account/source pair. Hard account-wide lockout lets
				// an unauthenticated attacker deny service to any known username.
				if ( $attempts_so_far >= $max_login_attempts ) {
					// Record it for the administrator, separately from the
					// transient that actually enforces it. Transients keyed by
					// account and source cannot be enumerated, so without this
					// the lockout is invisible on the Locked Users screen and
					// there is nothing for the unlock action to clear.
					self::record_lock_event( $user_id, $login_attempts_transient_name, $transient_timer );

					do_action( 'mls_login_source_rate_limited', $userdata->ID, self::get_request_ip() );

					/*
					 * Say so on the attempt that trips the lock, not only on the
					 * next one.
					 *
					 * pre_login_check() runs before this attempt is counted, so it
					 * saw an account that was not yet throttled and let the request
					 * through to the password check. WordPress then produced its
					 * plain "the password you entered is incorrect" and that was the
					 * only thing the user saw — on the very attempt that locked
					 * them out. They discovered the lock by trying again. Reported
					 * by QA as the wrong message on lockout.
					 *
					 * `wp_login_failed` receives the same WP_Error object that
					 * wp-login.php goes on to render, so adding to it here is what
					 * puts the sentence on the page. Same message and code as the
					 * next attempt would produce, so the two are consistent.
					 */
					if ( is_wp_error( $error ) && ! isset( $error->errors[ MLS_PREFIX . '_login_attempts_exceeded' ] ) ) {
						$lock_notice = \MLS\EmailAndMessageStrings::replace_email_strings(
							\MLS\EmailAndMessageStrings::get_email_template_setting( 'user_exceeded_failed_logins_count_message' ),
							$user_id
						);

						$error->add( MLS_PREFIX . '_login_attempts_exceeded', '<br>' . $lock_notice );

						if ( function_exists( 'wc_add_notice' ) ) {
							\wc_add_notice( wp_strip_all_tags( $lock_notice ), 'error' );
						}

						if ( class_exists( '\UM_Functions' ) ) {
							\UM()->form()->add_error( MLS_PREFIX . '_login_attempts_exceeded', wp_strip_all_tags( $lock_notice ) );
						}
					}
				} else {
					/*
					 * Tell the account holder how much rope is left.
					 *
					 * Without this the only feedback anyone gets is WordPress's
					 * plain "the password you entered is incorrect", and then, with
					 * no warning at all, the lockout notice. This countdown was
					 * removed in 2.4.0 development along with the account-wide lock
					 * it used to be written beside; the notice it produces is the
					 * point of a warning threshold, so it is restored here against
					 * the per-source counter.
					 *
					 * `wp_login_failed` receives the same WP_Error object that
					 * wp-login.php goes on to render, so adding to it here is what
					 * puts the sentence on the page.
					 */
					$attempts_left = max( 0, $max_login_attempts - $attempts_so_far );

					if ( $attempts_left > 0 && is_wp_error( $error ) ) {
						$error_string = sprintf(
							esc_html(
								/* translators: %d: Number of attempts remaining */
								_n(
									'You have %d attempt remaining.',
									'You have %d attempts remaining.',
									$attempts_left,
									'melapress-login-security'
								)
							),
							$attempts_left
						);

						$error->add( MLS_PREFIX . '_login_attempts_blocked', '<br>' . $error_string );

						if ( function_exists( 'wc_add_notice' ) ) {
							\wc_add_notice( $error_string, 'notice' );
						}

						// UM error handling.
						if ( class_exists( '\UM_Functions' ) ) {
							\UM()->form()->add_error( MLS_PREFIX . '_login_attempts_blocked', $error_string );
						}

						/*
						 * MemberPress renders its own form, so the count travels in
						 * the query string and the inline script in init() turns it
						 * into a notice. That script is still present and has been
						 * looking for an `attempts_remaining` marker that nothing
						 * produced since the countdown was removed.
						 */
						if ( isset( $_POST['mepr_process_login_form'] ) && class_exists( '\MeprUtils' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
							$mepr_options = \MeprOptions::fetch();
							$account_url  = $mepr_options->login_page_url();
							$delim        = \MeprAppCtrl::get_param_delimiter_char( $account_url );
							$url          = add_query_arg( 'mls_errors', 'attempts_remaining=' . $attempts_left, $account_url . $delim );

							\MeprUtils::wp_redirect( $url );
						}
					}

				}

				// Count the attempt against the account as well as the source.
				//
				// Per-source throttling on its own puts no ceiling on a
				// distributed attack: spread the guessing across addresses and
				// each one gets the full allowance, so a rotating proxy pool
				// meets no limit at all. This is the aggregate backstop.
				self::record_account_attempt( $user_id, $max_login_attempts, $transient_timer );

				return $error;
			}
		}

		/**
		 * User meta suffix holding the administrator-visible record of
		 * failed-login lockouts. Concatenated after MLS_PREFIX at each use.
		 *
		 * @var string
		 *
		 * @since 2.4.0
		 */
		const LOCK_EVENTS_META = '_failed_login_locks';

		/**
		 * Return the user meta key holding lockout records.
		 *
		 * @return string
		 *
		 * @since 2.4.0
		 */
		private static function lock_events_meta_key() {
			return MLS_PREFIX . self::LOCK_EVENTS_META;
		}

		/**
		 * Note that this account was locked out from a given source.
		 *
		 * This record is deliberately **not** what authentication consults —
		 * `is_source_throttled()` remains the only thing that refuses a login,
		 * and it is scoped to the account/source pair. Keeping the two apart is
		 * what stops an account-wide lockout coming back: an attacker tripping
		 * the throttle from their own address leaves a record an administrator
		 * can see, but does not deny the account holder access from theirs.
		 *
		 * The transient key is stored because that is what an unlock has to
		 * delete. An administrator clearing the lock is on a different address
		 * to whoever caused it, so they could not otherwise derive the key.
		 *
		 * @param int    $user_id       Account that was locked out.
		 * @param string $transient_key Transient enforcing the lockout.
		 * @param int    $ttl           Lifetime of that transient, in seconds.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function record_lock_event( $user_id, $transient_key, $ttl ) {
			$user_id = (int) $user_id;

			if ( ! $user_id || '' === (string) $transient_key ) {
				return;
			}

			$events = self::get_lock_events( $user_id );

			$events[ $transient_key ] = array(
				'time'    => time(),
				'expires' => time() + max( 1, (int) $ttl ),
				// A short digest only: enough to tell two sources apart in the
				// admin screen, not enough to recover the address.
				'source'  => substr( hash_hmac( 'sha256', self::get_request_ip(), wp_salt() ), 0, 12 ),
			);

			update_user_meta( $user_id, self::lock_events_meta_key(), $events );
		}

		/**
		 * Live lockout records for an account, pruning any that have expired.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return array Keyed by the transient enforcing each lockout.
		 *
		 * @since 2.4.0
		 */
		public static function get_lock_events( $user_id ) {
			$user_id = (int) $user_id;

			if ( ! $user_id ) {
				return array();
			}

			$events = get_user_meta( $user_id, self::lock_events_meta_key(), true );

			if ( ! is_array( $events ) ) {
				return array();
			}

			$live = array();
			$now  = time();

			foreach ( $events as $key => $event ) {
				if ( is_array( $event ) && isset( $event['expires'] ) && (int) $event['expires'] > $now ) {
					$live[ $key ] = $event;
				}
			}

			// Keep the stored row in step so it cannot grow without bound.
			if ( count( $live ) !== count( $events ) ) {
				if ( empty( $live ) ) {
					delete_user_meta( $user_id, self::lock_events_meta_key() );
				} else {
					update_user_meta( $user_id, self::lock_events_meta_key(), $live );
				}
			}

			return $live;
		}

		/**
		 * Whether this account is locked out from any source.
		 *
		 * For administrative display only — see record_lock_event() for why
		 * authentication must not use this.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		public static function has_active_lock_event( $user_id ) {
			if ( ! empty( self::get_lock_events( $user_id ) ) ) {
				return true;
			}

			return self::has_legacy_account_lock( $user_id );
		}

		/**
		 * Whether the account carries the account-wide lock flag written by 2.3.x.
		 *
		 * This is an account-level state, not a per-source one, and it has to be
		 * honoured as such — including when deciding a login.
		 *
		 * The per-source throttle that replaced it is deliberately scoped to the
		 * request, so that one source burning its allowance cannot lock an
		 * account for everyone. That reasoning does not extend to this flag:
		 * nothing in the plugin writes it any more, so it cannot be created by an
		 * attacker, and it is exactly what an administrator sees and clears on
		 * the Locked Users screen.
		 *
		 * Treating it as part of the per-source branch meant a site that upgraded
		 * mid-lockout showed the account as locked in the interface while letting
		 * it log in — reported by QA against 2.4.0, where a user locked under
		 * 2.3.0 got "the password you entered is incorrect" instead of the lock
		 * message. Inactivity locks were unaffected because that check was always
		 * account-level.
		 *
		 * Cleared by clear_lock_events() and by clear_failed_login_data(), so the
		 * administrator unlock and the security-question unlock both release it.
		 *
		 * @param int $user_id - User to check.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		public static function has_legacy_account_lock( $user_id ): bool {
			$user_id = (int) $user_id;

			if ( ! $user_id ) {
				return false;
			}

			return ! empty( get_user_meta( $user_id, MLS_USER_BLOCK_FURTHER_LOGINS_META_KEY, true ) );
		}

		/**
		 * Release every recorded lockout for an account.
		 *
		 * Deletes the transients that enforce them, so an administrator can
		 * unlock somebody who was locked out from an address the administrator
		 * has never seen.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function clear_lock_events( $user_id ) {
			$user_id = (int) $user_id;

			if ( ! $user_id ) {
				return;
			}

			foreach ( array_keys( self::get_lock_events( $user_id ) ) as $transient_key ) {
				// Network-wide store: see OptionsHelper::increment_counter().
				delete_site_transient( $transient_key );
				// Also drop any per-blog row left by a build that stored these
				// counters per site, so an old row cannot outlive the release.
				delete_transient( $transient_key );
			}

			delete_user_meta( $user_id, self::lock_events_meta_key() );
			delete_user_meta( $user_id, MLS_USER_BLOCK_FURTHER_LOGINS_META_KEY );
		}

		/**
		 * How many times the per-source allowance an account may burn through,
		 * across every source, before the aggregate cooldown applies.
		 *
		 * Deliberately generous. This is a backstop against an attack spread
		 * over many addresses, not a second per-source limit, and every point
		 * it is lowered makes it cheaper for an attacker to pause an account
		 * deliberately.
		 *
		 * @var int
		 *
		 * @since 2.4.0
		 */
		const AGGREGATE_MULTIPLIER = 10;

		/**
		 * How long the aggregate cooldown lasts, in seconds.
		 *
		 * Bounded on purpose. An account-wide refusal that an unauthenticated
		 * attacker can trigger must expire on its own, or it is a denial of
		 * service with extra steps — which is exactly what the account-wide
		 * lock flag was, and why it was removed.
		 *
		 * @var int
		 *
		 * @since 2.4.0
		 */
		const AGGREGATE_COOLDOWN = 900;

		/**
		 * Transient holding the account-wide failed attempt count.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return string
		 *
		 * @since 2.4.0
		 */
		private static function get_account_attempts_transient_name( $user_id ) {
			return MLS_PREFIX . '_acct_att_' . substr( hash_hmac( 'sha256', 'attempts|' . absint( $user_id ), wp_salt( 'auth' ) ), 0, 20 );
		}

		/**
		 * Transient marking an account as in its aggregate cooldown.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return string
		 *
		 * @since 2.4.0
		 */
		private static function get_account_cooldown_transient_name( $user_id ) {
			return MLS_PREFIX . '_acct_cool_' . substr( hash_hmac( 'sha256', 'cooldown|' . absint( $user_id ), wp_salt( 'auth' ) ), 0, 20 );
		}

		/**
		 * Count a failed attempt against the account, across all sources.
		 *
		 * @param int $user_id      Account attempted.
		 * @param int $source_limit The per-source allowance.
		 * @param int $window       How long attempts are remembered, in seconds.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		private static function record_account_attempt( $user_id, $source_limit, $window ) {
			$user_id = (int) $user_id;

			if ( ! $user_id ) {
				return;
			}

			$attempts = OptionsHelper::increment_counter(
				self::get_account_attempts_transient_name( $user_id ),
				max( 60, (int) $window ),
				true
			);

			if ( $attempts < self::get_aggregate_limit( $source_limit ) ) {
				return;
			}

			$cooldown = (int) apply_filters( 'mls_failed_login_aggregate_cooldown', self::AGGREGATE_COOLDOWN, $user_id );
			$cooldown = max( 60, $cooldown );

			set_site_transient( self::get_account_cooldown_transient_name( $user_id ), time() + $cooldown, $cooldown );

			// Visible to the administrator like any other lockout, and cleared
			// by the same unlock action.
			self::record_lock_event( $user_id, self::get_account_cooldown_transient_name( $user_id ), $cooldown );

			/**
			 * Fires when an account is paused after failures from many sources.
			 *
			 * @param int $user_id  Account affected.
			 * @param int $attempts Attempts counted in the window.
			 */
			do_action( 'mls_login_account_rate_limited', $user_id, $attempts );
		}

		/**
		 * The account-wide ceiling for a given per-source allowance.
		 *
		 * @param int $source_limit Per-source allowance.
		 *
		 * @return int
		 *
		 * @since 2.4.0
		 */
		public static function get_aggregate_limit( $source_limit ) {
			$limit = max( 20, (int) $source_limit * self::AGGREGATE_MULTIPLIER );

			return max( (int) $source_limit + 1, (int) apply_filters( 'mls_failed_login_aggregate_limit', $limit, $source_limit ) );
		}

		/**
		 * Whether this account is inside its aggregate cooldown.
		 *
		 * Unlike is_source_throttled() this *is* account-wide, so it is
		 * deliberately hard to reach and short-lived, and the refusal it causes
		 * offers the security question — an account holder with answers stored
		 * can end it immediately rather than waiting an attacker out.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		public static function is_account_rate_limited( $user_id ) {
			$user_id = (int) $user_id;

			if ( ! $user_id ) {
				return false;
			}

			return (bool) get_site_transient( self::get_account_cooldown_transient_name( $user_id ) );
		}

		/**
		 * Drop the account-wide counters for an account.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function clear_account_rate_limit( $user_id ) {
			$user_id = (int) $user_id;

			if ( ! $user_id ) {
				return;
			}

			delete_site_transient( self::get_account_attempts_transient_name( $user_id ) );
			delete_site_transient( self::get_account_cooldown_transient_name( $user_id ) );
		}

		/**
		 * Whether this account has run out of login attempts from this source.
		 *
		 * The failed-login policy no longer sets an account-wide flag, because
		 * that let an unauthenticated attacker lock any known username out of
		 * its own account. The lock now lives in a transient scoped to the
		 * account/source pair — so this is the only thing that can answer "is
		 * this account currently locked out?", and both the refusal in
		 * pre_login_check() and the security-question prompt have to agree on
		 * it or the account holder is left with no way back in.
		 *
		 * @param int      $user_id      User ID.
		 * @param int|null $source_limit Allowed attempts; resolved from the
		 *                               user's policy when not supplied.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		/**
		 * Whether this account is locked out for the source making this request.
		 *
		 * Two stores hold the same fact and they have different reach:
		 *
		 * - the attempt counter is a transient, and on multisite transients live
		 *   in the current blog's options table, so it is per-site;
		 * - the lock record is user meta, which is shared across the network.
		 *
		 * Enforcement used to consult only the counter. On multisite that meant a
		 * user locked out on one subsite could log in on another — the record was
		 * written, network-wide, and then ignored everywhere except the site that
		 * happened to record it. Reported by QA as a brute-force bypass: move to
		 * another site in the network and the lockout is not there.
		 *
		 * The record is keyed by the same (account, source) transient name, so it
		 * can be matched exactly without widening the lock to the whole account.
		 * That matters: an account-wide lock is what lets an unauthenticated
		 * attacker deny a known username access to its own account, which is why
		 * the throttle is source-scoped in the first place. Scoping by source
		 * rather than by blog keeps that property and fixes the bypass.
		 *
		 * @param int      $user_id      - Account to check.
		 * @param int|null $source_limit - Attempt allowance, resolved from policy when null.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		public static function is_source_locked_out( $user_id, $source_limit = null ) {
			$user_id = (int) $user_id;

			if ( ! $user_id ) {
				return false;
			}

			if ( self::is_source_throttled( $user_id, $source_limit ) ) {
				return true;
			}

			// Only consult the record while the policy is on, so that switching
			// the policy off does not leave old records enforcing themselves.
			if ( ! self::failed_login_policy_enabled( $user_id ) ) {
				return false;
			}

			$events = self::get_lock_events( $user_id );

			return isset( $events[ self::get_source_attempts_transient_name( $user_id ) ] );
		}

		/**
		 * Whether the failed-login policy applies to this account.
		 *
		 * @param int $user_id - Account to check.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		private static function failed_login_policy_enabled( $user_id ): bool {
			$user = get_user_by( 'id', (int) $user_id );

			if ( ! $user instanceof \WP_User ) {
				return false;
			}

			$role_options = OptionsHelper::get_preferred_role_options( $user->roles );

			return isset( $role_options->failed_login_policies_enabled )
				&& OptionsHelper::string_to_bool( $role_options->failed_login_policies_enabled );
		}

		public static function is_source_throttled( $user_id, $source_limit = null ) {
			$user_id = (int) $user_id;

			if ( ! $user_id ) {
				return false;
			}

			if ( null === $source_limit ) {
				$user = get_user_by( 'id', $user_id );

				if ( ! $user instanceof \WP_User ) {
					return false;
				}

				$role_options = OptionsHelper::get_preferred_role_options( $user->roles );

				if ( ! isset( $role_options->failed_login_policies_enabled )
					|| ! OptionsHelper::string_to_bool( $role_options->failed_login_policies_enabled ) ) {
					return false;
				}

				$source_limit = max( 1, (int) $role_options->failed_login_attempts );
			}

			return self::count_attempts( get_site_transient( self::get_source_attempts_transient_name( $user_id ) ) ) >= (int) $source_limit;
		}

		/**
		 * Read an attempt counter that may still be in the pre-2.4.0 shape.
		 *
		 * Attempts used to be stored as a list of timestamps. Transients written
		 * by an earlier version are still live when the plugin updates, so both
		 * shapes are accepted; only the count was ever used.
		 *
		 * @param mixed $stored Whatever the transient holds.
		 *
		 * @return int
		 *
		 * @since 2.4.0
		 */
		private static function count_attempts( $stored ) {
			if ( is_array( $stored ) ) {
				return count( $stored );
			}

			return (int) $stored;
		}

		/**
		 * Return a non-enumerable transient key scoped to an account and source.
		 *
		 * @param int $user_id User ID.
		 * @return string
		 */
		private static function get_source_attempts_transient_name( $user_id ) {
			$ip = self::get_request_ip();
			$scope = hash_hmac( 'sha256', absint( $user_id ) . '|' . $ip, wp_salt( 'auth' ) );
			return MLS_PREFIX . '_login_' . substr( $scope, 0, 24 );
		}

		/** Return the site-wide transient key for one authentication source. */
		private static function get_global_source_attempts_transient_name() {
			$scope = hash_hmac( 'sha256', self::get_request_ip(), wp_salt( 'auth' ) );
			return MLS_PREFIX . '_login_src_' . substr( $scope, 0, 20 );
		}

		/** Return the direct peer IP used for rate-limit scoping. */
		private static function get_request_ip() {
			$ip = \MLS\Login_Page_Control::get_client_ip();

			return '' !== $ip ? $ip : 'unknown';
		}

		/**
		 * Remove the "user blocked" usermeta and any currently held transients upon a succesful login.
		 *
		 * @param  string $username Currently logged in user.
		 * @param  object $user     Currently logged in user object.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function clear_failed_login_data( $username, $user ) {

			// Get the user ID, either from the user object if we have it, or by SQL query if we dont.
			if ( is_numeric( $username ) ) {
				$user_id = $username;
			} else {
				if ( isset( $user->ID ) ) {
					$user_id = $user->ID;
				} else {
					$looked_up = \get_user_by( 'login', $username );
					if ( ! $looked_up ) {
						$looked_up = \get_user_by( 'email', $username );
					}
					$user_id = $looked_up ? $looked_up->ID : 0;
				}
			}

			if ( ! $user_id ) {
				return;
			}

			/*
			 * Two very different callers reach this method.
			 *
			 * An administrator unlocking an account comes through
			 * OptionsHelper::fully_unlock_user(), which passes false for $user.
			 * That must release everything.
			 *
			 * A successful login comes through the `wp_login` action, which
			 * passes the WP_User. Resetting the attempt counter there is right —
			 * you proved the password from this source. Deleting the *lock
			 * records* is not: under unlock-by-admin a lockout is only an
			 * administrator's to lift, and a login that clears it removes the
			 * only trace that the account was ever locked. QA saw exactly that:
			 * one successful login on another site in the network and the lock
			 * was gone, with nothing in User Management to show it had existed.
			 */
			$administrative = empty( $user );

			$login_attempts_transient_name = MLS_PREFIX . '_user_' . $user_id . '_failed_login_attempts';

			delete_transient( $login_attempts_transient_name );
			delete_site_transient( self::get_source_attempts_transient_name( $user_id ) );

			if ( $administrative || ! self::lock_is_admin_release_only( $user_id ) ) {
				// Also release lockouts recorded against other sources, so an
				// administrator unlocking from their own address clears the one
				// the account holder is actually stuck behind.
				self::clear_lock_events( $user_id );
				self::clear_account_rate_limit( $user_id );

				delete_user_meta( $user_id, MLS_USER_BLOCK_FURTHER_LOGINS_META_KEY );
				delete_user_meta( $user_id, MLS_USER_BLOCK_FURTHER_LOGINS_TIMESTAMP_META_KEY );
				delete_user_meta( $user_id, MLS_PREFIX . 'is_blocked_user' );

				// mark as recently unlocked.
				update_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked', true );
				update_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_time', time() );
				update_user_meta( $user_id, MLS_PREFIX . '_recently_unlocked_reason', 'blocked' );
			}
		}

		/**
		 * Whether a lockout on this account may only be lifted by an administrator.
		 *
		 * With the timed policy a lockout lifts itself when the record expires, so
		 * a successful login clearing it early changes nothing an attacker could
		 * use. With unlock-by-admin it is the whole point of the setting.
		 *
		 * @param int $user_id - Account to check.
		 *
		 * @return bool
		 *
		 * @since 2.4.0
		 */
		private static function lock_is_admin_release_only( $user_id ): bool {
			$user = get_user_by( 'id', (int) $user_id );

			if ( ! $user instanceof \WP_User ) {
				return false;
			}

			$role_options = OptionsHelper::get_preferred_role_options( $user->roles );

			if ( ! isset( $role_options->failed_login_unlock_setting ) ) {
				return false;
			}

			return 'unlock-by-admin' === $role_options->failed_login_unlock_setting;
		}

		/**
		 * Small helper function to return all, or the most recently stored failed login attempts.
		 *
		 * @param  int     $user_id                  User id to lookup.
		 * @param  boolean $return_latest_entry_only Flag to determine if we only want the most recent attempt.
		 *
		 * @return mixed                             Stored failure attempts.
		 *
		 * @since 2.0.0
		 */
		public static function get_users_stored_transient_data( $user_id, $return_latest_entry_only = false ) {
			$login_attempts_transient_name = MLS_PREFIX . '_user_' . $user_id . '_failed_login_attempts';
			$transient_data                = get_transient( $login_attempts_transient_name );
			$current_time                  = time();
			$current_time_minus_24_hours   = $current_time - 86400;

			// Remove any attempts older than 24 hours.
			if ( ! empty( $transient_data ) ) {
				foreach ( $transient_data as $key => $login_attempt_timestamp ) {
					if ( $login_attempt_timestamp < $current_time_minus_24_hours ) {
						unset( $transient_data[ $key ] );
					}
				}
			}

			if ( $return_latest_entry_only && ! empty( $transient_data ) ) {
				$transient_data = end( $transient_data );
			}

			return $transient_data;
		}

		/**
		 * Retreive all IDs for users who are currently blocked.
		 *
		 * @return array Array of user IDs.
		 *
		 * @since 2.0.0
		 */
		public static function get_all_currently_login_locked_users() {
			global $wpdb;

			// Reads the lockout records written by record_lock_event().
			//
			// This used to match `mls_is_blocked_%`, a meta key nothing in the
			// plugin has ever written, so the screen was always empty for
			// failed-login lockouts however many accounts were locked out.
			$users = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"
				SELECT DISTINCT ID FROM $wpdb->users
				INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id
				WHERE $wpdb->usermeta.meta_key IN ( %s, %s )
				",
					array(
						self::lock_events_meta_key(),
						MLS_USER_BLOCK_FURTHER_LOGINS_META_KEY,
					)
				)
			);
			$users = array_map(
				function ( $user ) {
					// has_active_lock_event() prunes records whose transient has
					// since expired, so a lockout that has already lapsed does
					// not linger on the screen as though it were still in force.
					if ( ! \MLS_Core::is_user_exempted( $user->ID ) && self::has_active_lock_event( $user->ID ) ) {
							return (int) $user->ID;
					}
				},
				$users
			);
			$users = ( ! empty( $users ) ) ? array_values( array_filter( $users ) ) : array();

			return $users;
		}

		/**
		 * Send user a notification email once the account has been unblocked, also reset password if required.
		 *
		 * @param int  $user_id        -User ID to notify.
		 * @param bool $reset_password - Is PW reset.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function send_logins_unblocked_notification_email_to_user( $user_id, $reset_password ) {

			// Access plugin instance.
			$mls = melapress_login_security();

			// Grab user data object.
			$user_data = get_userdata( $user_id );

			// Redefining user_login ensures we return the right case in the email.
			$user_login = $user_data->user_login;
			$user_email = $user_data->user_email;
			$login_page = \MLS\Helpers\OptionsHelper::get_password_reset_page();
			$args       = array();
			$key        = false;

			// Only reset the password if the role has this option enabled.
			if ( $reset_password ) {
				$key = get_password_reset_key( $user_data );

				if ( is_wp_error( $key ) ) {
					// No reset key could be issued — `allow_password_reset` can
					// be filtered off, including by this plugin's own policies.
					// Fall back to the plain "account unlocked" notice: without
					// this the error object is interpolated into the reset URL
					// below and the request dies with a fatal.
					$key            = false;
					$reset_password = false;
				} else {
					$update = update_user_meta( $user_id, MLS_USER_RESET_PW_ON_LOGIN_META_KEY, $key );
				}
			}

			// Prepare email details.
			$from_email = $mls->options->mls_setting->from_email ? $mls->options->mls_setting->from_email : Emailer::get_default_email_address();
			$from_email = sanitize_email( $from_email );
			$headers[]  = 'From: ' . $from_email;

			if ( $reset_password ) {
				if ( \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->disable_user_unlocked_reset_needed_email ) ) {
					return;
				}
				$args['reset_url'] = esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) );
				$title = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_unlocked_reset_needed_email_subject' );
			} else {
				if ( \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->disable_user_unlocked_email ) ) {
					return;
				}
				$title = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_unlocked_email_subject' );
			}

			if ( $reset_password ) {
				$login_page = OptionsHelper::get_password_reset_page();
				if ( $key && ! is_wp_error( $key ) ) {
					$args['reset_or_continue'] = esc_url_raw( network_site_url( "$login_page?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) ) . "\n";
				} else {
					$args['reset_or_continue'] = esc_url_raw( network_site_url( $login_page ) ) . "\n";
				}
			}

			if ( $reset_password ) {
				$content = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_unlocked_reset_needed_email_body' );
			} else {
				$content = \MLS\EmailAndMessageStrings::get_email_template_setting( 'user_unlocked_email_body' );
			}

			$title         = \MLS\EmailAndMessageStrings::replace_email_strings( $title, $user_id );
			$email_content = \MLS\EmailAndMessageStrings::replace_email_strings( $content, $user_id, $args );

			// Only send the email if applicable.
			if ( isset( $mls->options->mls_setting->send_user_unblocked_email ) && \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->send_user_unblocked_email ) ) {
				// Fire off the mail.
				Emailer::send_email( $user_email, wp_specialchars_decode( $title ), $email_content, $headers );
			}
		}

		/**
		 * Add form markup to role policies.
		 *
		 * @param string $markup - Existing markup.
		 * @param object $settings_tab - Current tab.
		 *
		 * @return string - Markup.
		 *
		 * @since 2.0.0
		 */
		public static function failed_login_settings_markup( $markup, $settings_tab ) {
			$mls = melapress_login_security();
			ob_start();
			?>
				<tr valign="top">
					<th scope="row">
						<?php esc_attr_e( 'Failed login policies', 'melapress-login-security' ); ?>
					</th>
					<td>
						<fieldset>
							<input name="mls_options[failed_login_policies_enabled]" type="checkbox" id="ppm-failed-login-policies-enabled" data-toggle-other-areas=".ppmwp-login-block-options" value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( $settings_tab->failed_login_policies_enabled ) ); ?>>
							<?php esc_attr_e( 'Activate failed login policies', 'melapress-login-security' ); ?>

							<p class="description">
								<?php esc_attr_e( 'Use this setting to specify how long should the plugin keep a count of failed logins. Once this time passes, the failed login count is reset to 0.', 'melapress-login-security' ); ?>
							</p>
							<br>
						</fieldset>

						<fieldset class="ppmwp-login-block-options">
							<legend class="screen-reader-text">
								<span>
									<?php esc_attr_e( 'Number of failed login attempts, from one device or network, before access from it is blocked', 'melapress-login-security' ); ?>
								</span>
							</legend>
							<p class="description">
								<?php esc_html_e( 'Failed attempts are counted per device or network, not per account. Reaching the limit blocks further attempts from that source only, so nobody can lock a user out of their own account by guessing at it from elsewhere. A separate, much higher ceiling applies across all sources and pauses logins to the account briefly if it is reached.', 'melapress-login-security' ); ?>
							</p>
							<label for="ppm-failed-login-attempts">
								<?php esc_attr_e( 'Number of failed login attempts, from one device or network, before access from it is blocked:', 'melapress-login-security' ); ?>
								<input type="number" id="ppm-failed-login-attempts" name="mls_options[failed_login_attempts]"
											value="<?php echo esc_attr( $settings_tab->failed_login_attempts ); ?>" size="4" class="tiny-text ltr" min="1" required>
							</label>
							<br>
							<label for="ppm-failed-login-reset-attempts">
								<?php esc_attr_e( 'Time required to reset failed logins count to 0:', 'melapress-login-security' ); ?>
								<input style="width: 54px;" type="text" id="ppm-failed-login-reset-attempts" name="mls_options[failed_login_reset_attempts]"
											value="<?php echo esc_attr( $settings_tab->failed_login_reset_attempts ); ?>" size="6" class="tiny-text ltr" min="60" required>
											<?php esc_attr_e( ' minutes', 'melapress-login-security' ); ?>
							</label>
						</fieldset>

						<fieldset class="ppmwp-login-block-options">
							<p class="description" style="display: inline;"><?php esc_attr_e( 'When access is blocked: ', 'melapress-login-security' ); ?></p>
							<span style="display: inline-table;">
								<input type="radio" id="unlock-by-admin" name="mls_options[failed_login_unlock_setting]" value="unlock-by-admin" <?php checked( $settings_tab->failed_login_unlock_setting, 'unlock-by-admin' ); ?>>
								<label for="unlock-by-admin"><?php esc_attr_e( 'it can be only unlocked by the administrator', 'melapress-login-security' ); ?></label><br>
								<input type="radio" id="timed" name="mls_options[failed_login_unlock_setting]" value="timed" <?php checked( $settings_tab->failed_login_unlock_setting, 'timed' ); ?>>
								<label for="timed"><?php esc_attr_e( 'unlock it after', 'melapress-login-security' ); ?> <input type="number" id="ppm-failed-login-reset-hours" name="mls_options[failed_login_reset_hours]" value="<?php echo esc_attr( $settings_tab->failed_login_reset_hours ); ?>" size="4" class="tiny-text ltr" min="5" required> <?php esc_attr_e( 'minutes', 'melapress-login-security' ); ?></label>
							</span>
						</fieldset>

						<fieldset class="ppmwp-login-block-options">
							<label for="ppm-failed-login-reset-on-unblock">
								<input name="mls_options[failed_login_reset_on_unblock]" type="checkbox" id="ppm-failed-login-reset-on-unblock" value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( $settings_tab->failed_login_reset_on_unblock ) ); ?>>
								<?php esc_html_e( 'Require blocked users to reset password on unblock.', 'melapress-login-security' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'By ticking this checkbox, unblocked users will be required to configure a new password before logging in.', 'melapress-login-security' ); ?>
							</p>
							<br>
							<p class="description">
								<?php
									$messages_settings = '<a href="' . add_query_arg( 'page', 'mls-settings#message-settings', network_admin_url( 'admin.php' ) ) . '"> ' . __( 'User notices templates', 'melapress-login-security' ) . '</a>';
								?>
								<?php echo wp_kses_post( wp_sprintf( /* translators: %s: Link to settings. */ __( 'To customize the notification displayed to users should they fail a prompt, please visit the %s plugin settings.', 'melapress-login-security' ), wp_kses_post( $messages_settings ) ) ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			<?php
			return $markup . ob_get_clean();
		}

		/**
		 * Add admin page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function add_locked_users_admin_menu() {
			global $submenu;
			$main_menu = 'mls-policies';

			if ( isset( $submenu[ $main_menu ] ) && in_array( 'mls-locked-users', wp_list_pluck( $submenu[ $main_menu ], 2 ), true )
			) {
				return;
			}

			$capability = is_multisite() ? 'manage_network_options' : 'manage_options';

			// Add admin submenu page for settings.
			$locked_users_hook_submenu = add_submenu_page(
				MLS_MENU_SLUG,
				__( 'Locked Users', 'melapress-login-security' ),
				__( 'Locked Users', 'melapress-login-security' ),
				$capability,
				'mls-locked-users',
				array(
					__CLASS__,
					'ppm_display_locked_users_page',
				),
				3
			);

			add_action( "load-$locked_users_hook_submenu", array( '\MLS\Admin\Admin', 'admin_enqueue_scripts' ) );
		}

		/**
		 * Display settings page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function ppm_display_locked_users_page() {
			?>
			<div class="wrap ppm-wrap">
				<div class="page-head">
					<h2><?php esc_html_e( 'Locked Users', 'melapress-login-security' ); ?></h2>
				</div>

				<?php include_once MLS_PATH . 'app/modules/failed-logins/inactive-users.php'; ?>
			</div>
			<?php
		}

		/**
		 * Register the inactive users ajax endpoints.
		 *
		 * @method register_ajax
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function register_ajax() {
			$unlock_ajax = new \MLS\Ajax\UnlockInactiveUser( null );
			$unlock_ajax->register();

			\add_action( 'wp_ajax_' . self::REFRESH_LOCK_STATUS_ACTION, array( __CLASS__, 'handle_refresh_lock_status' ) );
		}

		/**
		 * Re-read the lock status of every account that carries a lock record.
		 *
		 * get_all_currently_login_locked_users() asks has_active_lock_event() about
		 * each account as it builds the list, and that prunes any lockout whose
		 * window has run out — so reading it is what brings the stored records back
		 * in step with reality.
		 *
		 * Accounts an administrator locked by hand have no expiry and are left for
		 * an explicit unlock.
		 *
		 * @return int Accounts still locked once lapsed records have been dropped.
		 *
		 * @since 2.4.0
		 */
		public static function refresh_lock_status() {
			return count( self::get_all_currently_login_locked_users() );
		}

		/**
		 * The "Refresh users lock status" button.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function handle_refresh_lock_status() {
			\check_ajax_referer( self::REFRESH_LOCK_STATUS_ACTION );

			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( array( 'message' => \esc_html__( 'You are not allowed to do that.', 'melapress-login-security' ) ), 403 );
			}

			/*
			 * Order matters. The inactive users check is the one thing that can
			 * add to this list, so it runs first and the count below reports what
			 * is locked afterwards. It is passed the fact that this request has
			 * already been verified, so it does not look for its own nonce.
			 */
			$inactive_checked = false;

			if ( \has_action( 'mls_inactive_users_check' ) ) {
				/*
				 * The live instance is already listening; the argument tells it the
				 * request has been verified, so it does not look for the nonce its
				 * own endpoint uses. Cron fires the same hook with no argument.
				 *
				 * On a manual run that check answers the request itself and ends it,
				 * so the response below is what the free edition returns. Both
				 * shapes carry `dispatched`, which is what the button waits for.
				 */
				\do_action( 'mls_inactive_users_check', true );
				$inactive_checked = true;
			}

			\wp_send_json_success(
				array(
					'locked'          => self::refresh_lock_status(),
					'inactiveChecked' => $inactive_checked,
					'dispatched'      => true,
				)
			);
		}

		/**
		 * Registers scripts used for handling inactive users features.
		 *
		 * NOTE: this class registers scripts but enqueue should happen later, this
		 * is to ensure that they are only there on pages that need them.
		 *
		 * @method register_scripts
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function register_scripts() {
			// this script is only registered here so enqueue it at a later point.
			wp_register_script( 'ppmwp-inactive-users', MLS_PLUGIN_URL . 'app/modules/failed-logins/inactiveUsers.js', array(), MLS_VERSION, true );
			wp_localize_script(
				'ppmwp-inactive-users',
				'inactiveUsersStrings',
				array(
					'resettingUser'   => esc_html__( 'Resetting...', 'melapress-login-security' ),
					'resetDone'       => esc_html__( 'User Reset', 'melapress-login-security' ),
					'noUsers'         => esc_html__( 'Currently there are no locked users.', 'melapress-login-security' ),
					'buttonReloading' => esc_html__( 'Reloading...', 'melapress-login-security' ),
					'refreshing'      => esc_html__( 'Refreshing...', 'melapress-login-security' ),
					'nonce'           => wp_create_nonce( \MLS\Ajax\UnlockInactiveUser::NONCE_KEY ),
				)
			);
		}
	}
}
