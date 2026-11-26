<?php
/**
 * Plugin deactivation class.
 *
 * Adds a form to request the reason for deactivation.
 *
 * @package Melapress_Deactivation_Feedback_Plugin
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MLS_Deactivation_Feedback;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\MLS_Deactivation_Feedback\Plugin_Deactivation' ) ) {

	/**
	 * Plugin deactivation class.
	 */
	class Plugin_Deactivation {

		/**
		 * The plugin slug.
		 *
		 * This is used to identify and catch the deactivation trigger.
		 *
		 * Example: 'wp-security-audit-log'
		 *
		 * ! The server enforces an allowlist of slugs — see
		 * ! `Request_Schema::PLUGIN_SLUGS`. A slug that is not on it is
		 * ! rejected with a 400 and the feedback is discarded silently, since
		 * ! the client navigates away regardless of the response status. Add
		 * ! the slug there and deploy the server plugin BEFORE shipping a
		 * ! client with a new one.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		const PLUGIN_SLUG = 'melapress-login-security';

		/**
		 * The plugin's own main file, relative to the plugins directory.
		 *
		 * Used by `plugin_version()` to read this plugin's own version via
		 * `get_plugin_data()`, exactly the way `PREMIUM_PLUGIN_FILE` is used
		 * to detect whether the premium version is the one active.
		 *
		 * Example: 'wp-security-audit-log/wp-security-audit-log.php'
		 *
		 * @var string
		 *
		 * @since 1.3.0
		 */
		const PLUGIN_FILE = 'melapress-login-security/melapress-login-security.php';

		/**
		 * The premium plugin file, if the plugin has a premium version.
		 *
		 * Example: 'wp-security-audit-log-premium/wp-security-audit-log.php'
		 *
		 * ! Leave empty if the plugin does not have a premium version.
		 * ! Must be set together with PREMIUM_PLUGIN_SLUG (both set, or both empty).
		 *
		 * @var string
		 *
		 * @since 1.0.1
		 */
		const PREMIUM_PLUGIN_FILE = 'melapress-login-security-premium/melapress-login-security-premium.php';

		/**
		 * The premium plugin slug, if the plugin has a premium version.
		 *
		 * Example: 'wp-security-audit-log-premium'
		 *
		 * ! Leave empty if the plugin does not have a premium version.
		 * ! Must be set together with PREMIUM_PLUGIN_FILE (both set, or both empty).
		 *
		 * @var string
		 *
		 * @since 1.0.1
		 */
		const PREMIUM_PLUGIN_SLUG = 'melapress-login-security-premium';

		/**
		 * The remote API URL to send the deactivation reason to.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		const REMOTE_URL = 'https://proxytron.melapress.com';


		/**
		 * The plugin logo URL, used in the uninstall feedback form popover.
		 *
		 * Example: WSAL_BASE_URL . 'img/wp-activity-log-logo-full-colour-horiz-rgb.svg'
		 *
		 * @var string
		 *
		 * @since 1.0.1
		 */
		const PLUGIN_LOGO_URL = \MLS_PLUGIN_URL . 'assets/images/password-policy-manager.png';

		/**
		 * Whether init() has already registered its hook.
		 *
		 * @var bool
		 *
		 * @since 1.0.2
		 */
		private static $initialised = false;

		/**
		 * Get the plugin slug.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function plugin_slug() {
			// PREMIUM_PLUGIN_FILE and PREMIUM_PLUGIN_SLUG must be configured together:
			// set both for a premium version, or leave both empty. When empty,
			// is_plugin_active() returns false and we fall back to the free slug.
			if ( \is_plugin_active( self::PREMIUM_PLUGIN_FILE ) ) {
				return self::PREMIUM_PLUGIN_SLUG;
			}

			return self::PLUGIN_SLUG;
		}

		/**
		 * Get the version of the plugin being deactivated.
		 *
		 * Chooses the premium or free plugin file exactly the way
		 * `plugin_slug()` chooses between the two slugs, then reads that
		 * file's own `Version` header via `get_plugin_data()`.
		 *
		 * `get_plugin_data()` is called with `$markup = false` and
		 * `$translate = false` — this value is sent in an API request, not
		 * rendered, so neither the HTML markup nor the i18n lookup
		 * `get_plugin_data()` otherwise applies is wanted here.
		 *
		 * @return string The plugin version, or an empty string if the
		 *                 relevant plugin file cannot be read (e.g. a
		 *                 misconfigured `PLUGIN_FILE`/`PREMIUM_PLUGIN_FILE`).
		 *
		 * @since 1.3.0
		 */
		public static function plugin_version() {
			$file = \is_plugin_active( self::PREMIUM_PLUGIN_FILE ) ? self::PREMIUM_PLUGIN_FILE : self::PLUGIN_FILE;

			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_data = \get_plugin_data( WP_PLUGIN_DIR . '/' . $file, false, false );

			return (string) $plugin_data['Version'];
		}

		/**
		 * The base URL of the deactivation-feedback server.
		 *
		 * Returns `MELAPRESS_DEACTIVATION_FEEDBACK_SERVER_URL` when a
		 * `wp-config.php` constant of that name is defined, non-empty and a
		 * valid `http(s)` URL; otherwise falls back to `self::REMOTE_URL`.
		 * The constant lets a staging or test client be pointed at a
		 * different server without editing this file — one definition in
		 * `wp-config.php` redirects every Melapress client on that site,
		 * since this file is embedded, unmodified, in each of them.
		 *
		 * Every rejection below falls back to `REMOTE_URL` silently — no
		 * exception, no `error_log()`, no admin notice. A fatal in
		 * `admin_footer` on a customer's Plugins screen is a far worse
		 * outcome than feedback going to the default server, and a
		 * translated error message here would also break the installer's
		 * and `ClientTemplateContractTest`'s i18n-call-count invariant.
		 *
		 * @return string The server base URL, with no trailing slash.
		 *
		 * @since 1.5.0
		 */
		public static function remote_url(): string {
			if ( ! defined( 'MELAPRESS_DEACTIVATION_FEEDBACK_SERVER_URL' ) ) {
				return self::REMOTE_URL;
			}

			$override = MELAPRESS_DEACTIVATION_FEEDBACK_SERVER_URL;

			// A constant can be defined as any type — guard against
			// `define( 'MELAPRESS_DEACTIVATION_FEEDBACK_SERVER_URL', true )`,
			// which is a real mistake a person can make, before it reaches
			// wp_parse_url().
			if ( ! is_string( $override ) ) {
				return self::REMOTE_URL;
			}

			$override = trim( $override );

			if ( '' === $override ) {
				return self::REMOTE_URL;
			}

			$scheme = \wp_parse_url( $override, PHP_URL_SCHEME );
			$host   = \wp_parse_url( $override, PHP_URL_HOST );

			// Scheme allowlist, not a blocklist: esc_url() at the output point
			// already strips disallowed protocols, but the value must be
			// validated where it is read, not merely escaped where it is
			// printed.
			if ( ! is_string( $scheme ) || ! in_array( strtolower( $scheme ), array( 'http', 'https' ), true ) ) {
				return self::REMOTE_URL;
			}

			if ( ! is_string( $host ) || '' === $host ) {
				return self::REMOTE_URL;
			}

			// The call sites concatenate this with '/?rest_route=...'; a
			// trailing slash on the override would yield a doubled slash
			// whose path does not necessarily route to index.php.
			return \untrailingslashit( $override );
		}

		/**
		 * Hooks the deactivation popover into the admin footer.
		 *
		 * Safe to call more than once — only the first call registers the hook,
		 * so a plugin that wires this up from two bootstrap paths does not
		 * render the popover twice.
		 *
		 * @return void
		 *
		 * @since 1.0.2
		 */
		public static function init() {
			if ( self::$initialised ) {
				return;
			}

			self::$initialised = true;

			\add_action( 'admin_footer', array( __CLASS__, 'maybe_add_script' ) );
		}

		/**
		 * Maybe add the script to the admin footer.
		 *
		 * @return void
		 */
		public static function maybe_add_script() {
			// Check if we're in the plugins page.
			if ( ! function_exists( 'get_current_screen' ) || ! \get_current_screen() || ( 'plugins' !== \get_current_screen()->id && 'plugins-network' !== \get_current_screen()->id ) ) {
				return;
			}
			self::the_popover();
			self::the_inline_script();
			self::the_inline_style();
		}

		/**
		 * The popover.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		protected static function the_popover() {
			// One string feeds both the placeholder and the accessible name.
			$alternative_plugin_hint = \__( 'What is the name of the other plugin?', 'melapress-login-security' );
			// One string feeds both the placeholder and the accessible name.
			$other_details_hint = \__( 'What could we have done better?', 'melapress-login-security' );

			$reasons = array(
				array(
					'id'    => 'unexpected-behavior',
					'label' => \__( 'The plugin isn\'t working, caused issues, or has a bug', 'melapress-login-security' ),
				),
				array(
					'id'    => 'found-better-plugin',
					'label' => \__( 'I found a better alternative', 'melapress-login-security' ),
				),
				array(
					'id'    => 'missing-feature',
					'label' => \__( 'The plugin is missing a specific feature', 'melapress-login-security' ),
				),
				array(
					'id'    => 'hard-to-understand',
					'label' => \__( 'The plugin is too hard to set up or understand', 'melapress-login-security' ),
				),
				array(
					'id'    => 'temporary-deactivation',
					'label' => \__( 'This is a temporary deactivation', 'melapress-login-security' ),
				),
			);

			// Randomize the order of the reasons.
			shuffle( $reasons );

			// Add the "other" reason at the end.
			$reasons[] = array(
				'id'    => 'other',
				'label' => \__( 'Other', 'melapress-login-security' ),
			);

			?>
		<div id="<?php echo \esc_attr( self::plugin_slug() ); ?>-popover" popover>
			<div class="mdfs-logo-wrapper">
				<img src="<?php echo \esc_url( self::PLUGIN_LOGO_URL ); ?>" alt="<?php \esc_attr_e( 'Plugin Logo', 'melapress-login-security' ); ?>">
			</div>
			<h1><?php \esc_html_e( "We're sorry to see you go", 'melapress-login-security' ); ?></h1>
			<p><?php \esc_html_e( 'If you have a moment, please let us know why you are deactivating this plugin:', 'melapress-login-security' ); ?></p>
			<form>
				<?php foreach ( $reasons as $reason ) : ?>
					<div class="mdfs-reason-wrapper" data-reason="<?php echo \esc_attr( $reason['id'] ); ?>">
						<span class="mdfs-radio-wrapper">
							<input
								id="deactivate-<?php echo \esc_attr( self::plugin_slug() ); ?>-reason-<?php echo \esc_attr( $reason['id'] ); ?>"
								type="radio"
								name="reason"
								value="<?php echo \esc_attr( $reason['id'] ); ?>"
							>
							<label for="deactivate-<?php echo \esc_attr( self::plugin_slug() ); ?>-reason-<?php echo \esc_attr( $reason['id'] ); ?>">
								<?php echo \esc_html( $reason['label'] ); ?>
							</label>
						</span>
						<?php if ( 'found-better-plugin' === $reason['id'] ) : ?>
							<div class="mdfs-alternative-plugin" hidden>
								<input
									id="deactivate-<?php echo \esc_attr( self::plugin_slug() ); ?>-alternative-plugin"
									class="mdfs-alternative-plugin-input"
									type="text"
									maxlength="100"
									autocomplete="off"
									aria-required="true"
									aria-label="<?php echo \esc_attr( $alternative_plugin_hint ); ?>"
									placeholder="<?php echo \esc_attr( $alternative_plugin_hint ); ?>"
								>
								<p id="deactivate-<?php echo \esc_attr( self::plugin_slug() ); ?>-alternative-plugin-empty" class="mdfs-validation mdfs-alternative-plugin-empty" hidden role="alert" aria-atomic="true">
									<?php \esc_html_e( 'Please enter the name of the other plugin.', 'melapress-login-security' ); ?>
								</p>
								<p id="deactivate-<?php echo \esc_attr( self::plugin_slug() ); ?>-alternative-plugin-invalid" class="mdfs-validation mdfs-alternative-plugin-invalid" hidden role="alert" aria-atomic="true">
									<?php \esc_html_e( 'Name not allowed. Please only use letters, numbers, spaces and hyphen "-".', 'melapress-login-security' ); ?>
								</p>
							</div>
						<?php endif; ?>
						<?php if ( 'other' === $reason['id'] ) : ?>
							<div class="mdfs-other-details" hidden>
								<?php // No whitespace between the textarea tags: it would become the default value and hide the placeholder. ?>
								<textarea
									id="deactivate-<?php echo \esc_attr( self::plugin_slug() ); ?>-other-details"
									class="mdfs-other-details-input"
									rows="3"
									maxlength="280"
									autocomplete="off"
									aria-required="true"
									aria-label="<?php echo \esc_attr( $other_details_hint ); ?>"
									placeholder="<?php echo \esc_attr( $other_details_hint ); ?>"
								></textarea>
								<p id="deactivate-<?php echo \esc_attr( self::plugin_slug() ); ?>-other-details-empty" class="mdfs-validation mdfs-other-details-empty" hidden role="alert" aria-atomic="true">
									<?php \esc_html_e( 'Please tell us a little more.', 'melapress-login-security' ); ?>
								</p>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>

				<p class="mdfs-validation mdfs-reason-validation" hidden role="alert" aria-atomic="true" >
					<?php
					echo \wp_kses(
						sprintf(
							/* translators: 1: Bolded "Submit & Deactivate" label. 2: Bolded "Skip & Deactivate" label. */
							\esc_html__( 'Please select one of the reasons and click %1$s. If you would like to deactivate the plugin without sharing feedback, click %2$s.', 'melapress-login-security' ),
							'<strong>' . \esc_html__( 'Submit & Deactivate', 'melapress-login-security' ) . '</strong>',
							'<strong>' . \esc_html__( 'Skip & Deactivate', 'melapress-login-security' ) . '</strong>'
						),
						array( 'strong' => array() )
					);
					?>
				</p>
				<div class="mdfs-actions">
					<button type="button" class="mdfs-submit"><?php \esc_html_e( 'Submit & Deactivate', 'melapress-login-security' ); ?></button>
					<button type="button" class="mdfs-dismiss"><?php \esc_html_e( 'Skip & Deactivate', 'melapress-login-security' ); ?></button>
					<button type="button" class="mdfs-cancel"><?php \esc_html_e( 'Keep Plugin Active', 'melapress-login-security' ); ?></button>
				</div>
			</form>
		</div>
			<?php
		}

		/**
		 * The inline script.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		protected static function the_inline_script() {
			?>
			<script>
				(function() {
					// A helper function to make AJAX requests.
					const deactivatePluginFeedbackAjaxRequest = ( { url, data, action } ) => {
						const http = new XMLHttpRequest();
						http.open( 'POST', url, true );
						http.onreadystatechange = () => {
							if ( 4 !== http.readyState ) {
								return;
							}
							let response;
							try {
								response = JSON.parse( http.response );
							} catch ( error ) {
								console.warn( 'Failed to parse AJAX response:', error );
							}
							return action( response );
						};
						const dataForm = new FormData();
						for ( const [ key, value ] of Object.entries( data ) ) {
							dataForm.append( key, value );
						}
						http.send( dataForm );
					}

					// Add an event listener to the deactivate button.
					const deactivateButton = document.querySelector(  'a[href*="action=deactivate"][href*="plugin=<?php echo \esc_attr( self::plugin_slug() ); ?>%2F"]');
					
					const deactivationPopover = document.getElementById( '<?php echo \esc_attr( self::plugin_slug() ); ?>-popover' );
					if ( deactivateButton && deactivationPopover ) {
						// The default button: keeps the plugin active. Queried once so it can be focused on every open.
						const cancelButton = deactivationPopover.querySelector( 'button.mdfs-cancel' );

						deactivateButton.addEventListener( 'click', ( clickEvent ) => {
							clickEvent.preventDefault();
							deactivationPopover.showPopover();
							// A browser may restore a checked reason without firing `change`, so make the name field match it on every open. This never clears a value for a reason that is still selected.
							syncAlternativePlugin();
							syncOtherDetails();
							// Focus explicitly rather than via the autofocus attribute, which would make this hidden popover a page-load autofocus candidate.
							if ( cancelButton ) {
								cancelButton.focus();
							}
						} );

						const validationMessage = deactivationPopover.querySelector( '.mdfs-reason-validation' );

						// The optional "name of the other plugin" field, shown only for the found-better-plugin reason.
						const betterPluginReason = 'found-better-plugin';
						const alternativePluginPattern = /^[A-Za-z0-9 -]{1,100}$/;
						const alternativeWrapper = deactivationPopover.querySelector( '.mdfs-alternative-plugin' );
						const alternativeInput = deactivationPopover.querySelector( '.mdfs-alternative-plugin-input' );
						const alternativeEmpty = deactivationPopover.querySelector( '.mdfs-alternative-plugin-empty' );
						const alternativeInvalid = deactivationPopover.querySelector( '.mdfs-alternative-plugin-invalid' );

						// The required free-text box, shown only for the other reason. Checked on submit only, never live.
						const otherReason = 'other';
						const otherWrapper = deactivationPopover.querySelector( '.mdfs-other-details' );
						const otherDetailsInput = deactivationPopover.querySelector( '.mdfs-other-details-input' );
						const otherDetailsEmpty = deactivationPopover.querySelector( '.mdfs-other-details-empty' );

						// Hide both name errors and clear the matching accessibility attributes.
						const hideAlternativeErrors = () => {
							if ( alternativeEmpty ) {
								alternativeEmpty.hidden = true;
							}
							if ( alternativeInvalid ) {
								alternativeInvalid.hidden = true;
							}
							if ( alternativeInput ) {
								alternativeInput.removeAttribute( 'aria-invalid' );
								alternativeInput.removeAttribute( 'aria-describedby' );
							}
						};

						// Show one name error, tie it to the input and move focus there.
						const showAlternativeError = ( message ) => {
							hideAlternativeErrors();
							if ( ! message ) {
								return;
							}
							message.hidden = false;
							if ( alternativeInput ) {
								alternativeInput.setAttribute( 'aria-invalid', 'true' );
								alternativeInput.setAttribute( 'aria-describedby', message.id );
								alternativeInput.focus();
							}
						};

						// Hide the empty-text error and clear the matching accessibility attributes.
						const hideOtherDetailsError = () => {
							if ( otherDetailsEmpty ) {
								otherDetailsEmpty.hidden = true;
							}
							if ( otherDetailsInput ) {
								otherDetailsInput.removeAttribute( 'aria-invalid' );
								otherDetailsInput.removeAttribute( 'aria-describedby' );
							}
						};

						// Show the empty-text error, tie it to the box and move focus there.
						const showOtherDetailsError = () => {
							if ( ! otherDetailsEmpty ) {
								return;
							}
							otherDetailsEmpty.hidden = false;
							if ( otherDetailsInput ) {
								otherDetailsInput.setAttribute( 'aria-invalid', 'true' );
								otherDetailsInput.setAttribute( 'aria-describedby', otherDetailsEmpty.id );
								otherDetailsInput.focus();
							}
						};

						// Show the field only while found-better-plugin is selected. Any other reason empties it and clears its error.
						const syncAlternativePlugin = () => {
							if ( ! alternativeWrapper ) {
								return;
							}
							const checkedReason = deactivationPopover.querySelector( 'input[name="reason"]:checked' );
							const isBetterPlugin = !! checkedReason && betterPluginReason === checkedReason.value;
							alternativeWrapper.hidden = ! isBetterPlugin;
							if ( ! isBetterPlugin ) {
								if ( alternativeInput ) {
									alternativeInput.value = '';
								}
								hideAlternativeErrors();
							}
						};

						// Show the box only while the other reason is selected. Any other reason empties it and clears its error.
						const syncOtherDetails = () => {
							if ( ! otherWrapper ) {
								return;
							}
							const checkedReason = deactivationPopover.querySelector( 'input[name="reason"]:checked' );
							const isOther = !! checkedReason && otherReason === checkedReason.value;
							otherWrapper.hidden = ! isOther;
							if ( ! isOther ) {
								if ( otherDetailsInput ) {
									otherDetailsInput.value = '';
								}
								hideOtherDetailsError();
							}
						};

						// Selecting a reason wrapper checks its radio and clears any validation message.
						deactivationPopover.querySelectorAll( '.mdfs-reason-wrapper' ).forEach( ( reasonWrapper ) => {
							reasonWrapper.addEventListener( 'click', () => {
								const radio = reasonWrapper.querySelector( 'input[type="radio"]' );
								if ( radio ) {
									radio.checked = true;
								}
								if ( validationMessage ) {
									validationMessage.hidden = true;
								}
								// Setting `checked` in code fires no `change` event, so sync here too.
								syncAlternativePlugin();
								syncOtherDetails();
							} );
						} );

						// Arrow-key selection in the radio group fires `change`, not `click` on the wrapper.
						deactivationPopover.querySelectorAll( 'input[name="reason"]' ).forEach( ( radio ) => {
							radio.addEventListener( 'change', syncAlternativePlugin );
							radio.addEventListener( 'change', syncOtherDetails );
						} );

						// Stop the Enter key in the text field from natively submitting the form.
						const popoverForm = deactivationPopover.querySelector( 'form' );
						if ( popoverForm ) {
							popoverForm.addEventListener( 'submit', ( formEvent ) => {
								formEvent.preventDefault();
							} );
						}

						// Handle clicking on the keep-active button: close the popover, send nothing, leave the plugin active.
						if ( cancelButton ) {
							cancelButton.addEventListener( 'click', ( cancelEvent ) => {
								cancelEvent.preventDefault();
								if ( validationMessage ) {
									validationMessage.hidden = true;
								}
								hideAlternativeErrors();
								// The reason does not change here, so the typed text is kept.
								hideOtherDetailsError();
								deactivationPopover.hidePopover();
							} );
						}

						// Handle clicking on the dismiss button.
						deactivationPopover.querySelector( 'button.mdfs-dismiss' ).addEventListener( 'click', ( dismissEvent ) => {
							dismissEvent.preventDefault();
							window.location.href = deactivateButton.href;
						} );

						// Handle clicking on the submit button.
						deactivationPopover.querySelector( 'button.mdfs-submit' ).addEventListener( 'click', ( submitEvent ) => {
							submitEvent.preventDefault();

							const selectedReason = deactivationPopover.querySelector( 'input[name="reason"]:checked' );

							// If no reason is selected, show the validation message and return early.
							if ( ! selectedReason ) {
								if ( validationMessage ) {
									validationMessage.hidden = false;
								}
								return;
							}

							// The name is required for found-better-plugin and is checked only here, never live.
							const submittedReason = selectedReason.value;
							let alternativePlugin = '';
							if ( betterPluginReason === submittedReason ) {
								alternativePlugin = alternativeInput ? alternativeInput.value.trim() : '';
								if ( '' === alternativePlugin ) {
									showAlternativeError( alternativeEmpty );
									return;
								}
								if ( ! alternativePluginPattern.test( alternativePlugin ) ) {
									showAlternativeError( alternativeInvalid );
									return;
								}
								hideAlternativeErrors();
							}

							// The text is required for other and is checked only here, never live. Trimming is the only test: the server cleans the rest.
							let otherDetails = '';
							if ( otherReason === submittedReason ) {
								otherDetails = otherDetailsInput ? otherDetailsInput.value.trim() : '';
								if ( '' === otherDetails ) {
									showOtherDetailsError();
									return;
								}
								hideOtherDetailsError();
							}

							const requestData = {
								action: 'plugin_deactivation',
								plugin: '<?php echo \esc_attr( self::plugin_slug() ); ?>',
								site: '<?php echo \esc_attr( \get_site_url() ); ?>',
								plugin_version: '<?php echo \esc_attr( self::plugin_version() ); ?>',
							};
							deactivatePluginFeedbackAjaxRequest( {
								// Get a nonce from the remote server.
								url: '<?php echo \esc_url( self::remote_url() ); ?>/?rest_route=/deactivation-feedback-server/v1/get-nonce',
								data: requestData,
								action: ( response ) => {
									response = response || {};
									// Add the nonce and the selected reason to the request data.
									requestData.nonce = response.nonce;
									const formData = new FormData( deactivationPopover.querySelector( 'form' ) );
									requestData.reason = formData.get( 'reason' );
									// Added only now, so the get-nonce request (which shares this object) never carries it.
									if ( betterPluginReason === submittedReason ) {
										requestData.alternative_plugin = alternativePlugin;
									}
									if ( otherReason === submittedReason ) {
										requestData.other_details = otherDetails;
									}

									// Make the request to the remote server to submit the feedback.
									deactivatePluginFeedbackAjaxRequest( {
										url: '<?php echo \esc_url( self::remote_url() ); ?>/?rest_route=/deactivation-feedback-server/v1/submit-feedback',
										data: requestData,
										action: () => {
											window.location.href = deactivateButton.href;
										},
									} );
								},
							} );

							// Submit the form.
							deactivationPopover.hidePopover();
						} );
					}
				})();
			</script>
			<?php
		}

		/**
		 * The inline style.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		protected static function the_inline_style() {
			$popover_id = self::plugin_slug() . '-popover';
			?>
			<style>
				#<?php echo \esc_attr( $popover_id ); ?> {
					border: 1px solid #ccc;
					padding: 2rem;
					border-radius: 8px;
				}

				#<?php echo \esc_attr( $popover_id ); ?>::backdrop {
					background-color: rgba(0, 0, 0, 0.5);
				}

				#<?php echo \esc_attr( $popover_id ); ?> form {
					display: flex;
					flex-direction: column;
					gap: 1rem;
				}

				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-logo-wrapper {
					text-align: center;
					margin-bottom: 1.5rem;
				}

				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-logo-wrapper img {
					width: 150px;
					margin-bottom: 1rem;
				}

				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-reason-wrapper {
					display: flex;
					gap: 1rem;
					flex-direction: column;
				}

				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-validation {
					color: #b00020;
					margin: 0;
					max-width: 400px;
				}

				/* No `display` here: an author `display` would override the [hidden] rule and the wrapper would never hide. */
				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-alternative-plugin {
					margin-left: 1.75rem;
				}

				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-alternative-plugin input {
					width: 100%;
					max-width: 400px;
					box-sizing: border-box;
				}

				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-alternative-plugin .mdfs-validation {
					margin-top: 0.5rem;
				}

				/* No `display` here either, for the same [hidden] reason. */
				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-other-details {
					margin-left: 1.75rem;
				}

				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-other-details textarea {
					width: 100%;
					max-width: 400px;
					box-sizing: border-box;
					resize: vertical;
				}

				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-other-details .mdfs-validation {
					margin-top: 0.5rem;
				}

				#<?php echo \esc_attr( $popover_id ); ?> .mdfs-actions {
					display: flex;
					flex-wrap: wrap;
					gap: 1rem;
				}

				#<?php echo \esc_attr( $popover_id ); ?> button {
					padding: 0.5rem 1rem;
					border-radius: 4px;
					border: 1px solid #ccc;
					background-color: #5437D1;
					cursor: pointer;
					color: #fff;
					margin: 0;
				}

				#<?php echo \esc_attr( $popover_id ); ?> button.mdfs-dismiss,
				#<?php echo \esc_attr( $popover_id ); ?> button.mdfs-cancel {
					background-color: #fff;
					cursor: pointer;
					color: #000;
				}
			</style>
			<?php
		}
	}
}
