<?php
/**
 * Helper class to hide other admin notices.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

if ( ! class_exists( '\MLS\Helpers\SettingsImporter' ) ) {

	/**
	 * Import and export settings class.
	 *
	 * @since 2.0.0
	 */
	class SettingsImporter {

		/**
		 * Init settings hooks.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function init() {
			\add_filter( 'mls_settings_page_nav_tabs', array( $this, 'settings_tab_link' ), 50, 1 );
			\add_filter( 'mls_settings_page_content_tabs', array( $this, 'settings_tab' ), 50, 1 );
			\add_action( 'wp_ajax_mls_export_settings', array( $this, 'export_settings' ) );
			\add_action( 'wp_ajax_mls_check_setting_and_handle_import', array( $this, 'check_setting_and_handle_import' ) );
			\add_action( 'admin_enqueue_scripts', array( $this, 'selectively_enqueue_admin_script' ) );
		}

		/**
		 * Add scripts when needed.
		 *
		 * @param string $hook - Current hook.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function selectively_enqueue_admin_script( $hook ) {
			if ( ! str_contains( (string) $hook, 'mls-settings' ) ) {
				return;
			}

			$mls = melapress_login_security();

			\wp_enqueue_script( 'mls_settings_importexport', MLS_PLUGIN_URL . 'admin/assets/js/settings-importexport.js', array( 'ppm-wp-settings' ), MLS_VERSION, true );

			\wp_localize_script(
				'mls_settings_importexport',
				'wpws_import_data',
				array(
					'wp_import_nonce'       => \wp_create_nonce( 'mls-import-settings' ),
					'checkingMessage'       => \esc_html__( 'Checking import contents', 'melapress-login-security' ),
					'checksPassedMessage'   => \esc_html__( 'Ready to import', 'melapress-login-security' ),
					'checksFailedMessage'   => \esc_html__( 'Issues found', 'melapress-login-security' ),
					'importingMessage'      => \esc_html__( 'Importing settings', 'melapress-login-security' ),
					'importedMessage'       => \esc_html__( 'Settings imported', 'melapress-login-security' ),
					'helpMessage'           => \esc_html__( 'Help', 'melapress-login-security' ),
					'notFoundMessage'       => \esc_html__( 'The role, user or post type contained in your settings are not currently found in this website. Importing such settings could lead to abnormal behavour. For more information and / or if you require assistance, please', 'melapress-login-security' ),
					'notSupportedMessage'   => \esc_html__( 'Currently this data is not supported by our export/import wizard.', 'melapress-login-security' ),
					'restrictAccessMessage' => \esc_html__( 'To avoid accidental lock-out, this setting is not imported.', 'melapress-login-security' ),
					'wrongFormat'           => \esc_html__( 'Please upload a valid JSON file.', 'melapress-login-security' ),
					'cancelMessage'         => \esc_html__( 'Cancel', 'melapress-login-security' ),
					'readyMessage'          => \esc_html__( 'The settings file has been tested and the configuration is ready to be imported. Would you like to proceed?', 'melapress-login-security' ),
					'proceedMessage'        => \esc_html__( 'The configuration has been successfully imported. Click OK to close this window', 'melapress-login-security' ),
					'proceed'               => \esc_html__( 'Proceed', 'melapress-login-security' ),
					'ok'                    => \esc_html__( 'OK', 'melapress-login-security' ),
					'helpPage'              => '',
					'helpLinkText'          => \esc_html__( 'Contact Us', 'melapress-login-security' ),
					'isUsingCustomEmail'    => ( $mls->options->mls_setting->from_email && ! empty( $mls->options->mls_setting->from_email ) ) ? $mls->options->mls_setting->from_email : false,
				)
			);
		}

		/**
		 * Add link to tabbed area within settings.
		 *
		 * @param  string $markup - Currently added content.
		 *
		 * @return string $markup - Appended content.
		 *
		 * @since 2.0.0
		 */
		public function settings_tab_link( $markup ) {
			return $markup . '<a href="#settings-export" class="nav-tab" data-tab-target=".mls-settings-export">' . \esc_attr__( 'Settings Import/Export', 'melapress-login-security' ) . '</a>';
		}

		/**
		 * Add settings tab content to settings area
		 *
		 * @param  string $markup - Currently added content.
		 *
		 * @return string $markup - Appended content.
		 *
		 * @since 2.0.0
		 */
		public function settings_tab( $markup ) {
			ob_start(); ?>
				<div class="settings-tab mls-settings-export">
					<table class="form-table">
						<tbody>
							<?php
							self::render_settings();
							?>
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
		 *
		 * @since 2.0.0
		 */
		public static function render_settings() {
			$nonce = \wp_create_nonce( 'mls-export-settings' );
			?>

					<tr valign="top">
						<br>
						<h1><?php \esc_html_e( 'Settings Import/Export', 'melapress-login-security' ); ?></h1>
						<p class="description"><?php \esc_html_e( 'On this page you can import and export your plugins settings.', 'melapress-login-security' ); ?></p>
						<br>
					</tr>

					<tr>
						<th><label><?php \esc_html_e( 'Export settings', 'melapress-login-security' ); ?></label></th>
						<td>
							<fieldset>
								<input type="button" id="export-settings" class="button-primary"
										value="<?php \esc_html_e( 'Export', 'melapress-login-security' ); ?>"
										data-export-wpws-settings data-nonce="<?php echo \esc_attr( $nonce ); ?>">
								<p class="description">
								<?php \esc_html_e( 'Once the settings are exported a download will automatically start. The settings are exported to a JSON file.', 'melapress-login-security' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th><label><?php \esc_html_e( 'Import settings', 'melapress-login-security' ); ?></label></th>
						<td>
							<fieldset>

								<input type="file" id="wpws-settings-file" name="filename"><br>
								<input style="margin-top: 7px;" type="submit" id="import-settings" class="button-primary" data-import-wpws-settings data-nonce="<?php echo \esc_attr( $nonce ); ?>" value="<?php \esc_html_e( 'Validate & Import', 'melapress-login-security' ); ?>">
								<p class="description">
								<?php \esc_html_e( 'Once you choose a JSON settings file, it will be checked prior to being imported to alert you of any issues, if there are any.', 'melapress-login-security' ); ?>
								</p>
								<div id="import-settings-modal">
									<div class="modal-content">
										<h3 id="wpws-modal-title"></h3>
										<span class="import-settings-modal-close">&times;</span>
										<span><ul id="wpws-settings-file-output"></ul></span>
									</div>
								</div>
							</fieldset>
						</td>
					</tr>

					<style type="text/css">
						li[data-wpws-option-name] span {
							width: auto;
							margin-left: 10px;
							display: inline-block;
						}

						li[data-wpws-option-name] span span, li[data-wpws-option-name] [data-help] {
							width: auto;
							font-size: 14px;
							font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
							position: relative;
							margin: 0;
							top: -5px;
						}

						#import-settings-modal {
							display: none;
							position: fixed;
							z-index: 9999;
							left: 0;
							top: 0;
							width: 100%;
							height: 100%;
							overflow: auto;
							background-color: rgb(0, 0, 0);
							background-color: rgba(0, 0, 0, 0.4);
						}

						#import-settings-modal .modal-content {
							background-color: #fefefe;
							margin: 5% auto;
							padding: 20px;
							border: 1px solid #888;
							width: 80%;
							max-width: 800px;
						}

						.import-settings-modal-close {
							color: #aaa;
							float: right;
							font-size: 28px;
							font-weight: bold;
						}

						.import-settings-modal-close:hover, .import-settings-modal-close:focus {
							color: black;
							text-decoration: none;
							cursor: pointer;
						}

						[data-wpws-option-name] {
							line-height: 25px !important;
						}

						[data-wpws-option-name]>div {
							display: inline-block;
							min-width: 285px;
							font-size: 15px;
							font-weight: 500;
							text-transform: capitalize;
						}

						[data-wpws-option-name]:last-of-type {
							margin-bottom: 30px;
						}

						#wpws-modal-title {
							max-width: 500px;
							display: inline-block;
							margin: 0 15px 1px 0;
							font-size: 24px;
						}

						li[data-wpws-option-name] [data-help] {
							position:relative; /* making the .tooltip span a container for the tooltip text */
							border-bottom:1px dashed #000; /* little indicater to indicate it's hoverable */
						}

						li[data-wpws-option-name] [data-help]:before {
							content: attr(data-help-text); /* here's the magic */
							position:absolute;

							/* vertically center */
							top:50%;
							transform:translateY(-50%);

							/* move to right */
							left:100%;
							margin-left:15px; /* and add a small left margin */

							/* basic styles */
							width:200px;
							padding:10px;
							border-radius:10px;
							background:#000;
							color: #fff;
							text-align:center;

							display:none; /* hide by default */
						}

						.button-primary#export-settings, .button-primary#import-settings {
							min-width: 126px;
						}

						li[data-wpws-option-name] [data-help] .tooltip {
							content: attr(data-help-text); /* here's the magic */
							position:absolute;
							top:50%;
							transform:translateY(-50%);
							left:100%;
							margin-left:15px;
							width:200px;
							padding:10px;
							border-radius:10px;
							background:#000;
							color: #fff;
							text-align:center;
							line-height: 18px;
							font-size: 13px;
						}

						li[data-wpws-option-name] [data-help] .tooltip a {
							font-weight: bold;
							color: #fff;
						}

						#wpws-import-read.disabled {
							opacity: 0.5;
							pointer-events: none;
						}

						#ready-text {
							display: block;
							margin-bottom: 15px;
						}

						#wpws-import-read input {
							float: left;
						}
						.dashicons-info + .dashicons-yes-alt {
							visibility: hidden;
						}
					</style>
				<?php
		}

		/**
		 * Creates a JSON file containing settings.
		 *
		 * @return void.
		 *
		 * @since 2.0.0
		 */
		public function export_settings() {
			// Grab POSTed data.
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

			// Check nonce.
			if ( ! current_user_can( 'manage_options' ) || empty( $nonce ) || ! wp_verify_nonce( $nonce, 'mls-export-settings' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
			}

			$results = array();

			global $wpdb;

			/*
			 * Underscore-delimited and LIKE-escaped. `MLS_PREFIX . '%'` also
			 * matched the table-prefix namespace of any site whose prefix begins
			 * with the same letters, so an export from a site with prefix `mlst_`
			 * carried that site's `mlst_user_roles` — its entire role and
			 * capability map — into the downloaded settings file, and an import
			 * would write it back over the target site's roles.
			 */
			$prefix_like = $wpdb->esc_like( MLS_PREFIX . '_' ) . '%';

			if ( is_multisite() ) {
				$prepared_query = $wpdb->prepare(
					"SELECT `meta_key`, `meta_value` FROM `{$wpdb->sitemeta}` WHERE `meta_key` LIKE %s ORDER BY `meta_key` ASC",
					$prefix_like
				);
			} else {
				$prepared_query = $wpdb->prepare(
					"SELECT `option_name`, `option_value` FROM `{$wpdb->options}` WHERE `option_name` LIKE %s ORDER BY `option_name` ASC",
					$prefix_like
				);
			}

			/**
			 * Fire of action for others to observe.
			 */
			do_action( 'mls_settings_exported' );

			$results = $wpdb->get_results( $prepared_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			/*
			 * Keep only what can meaningfully be moved to another site.
			 *
			 * OptionsHelper::is_portable_option() covers three groups: licence
			 * credentials, options describing this installation rather than its
			 * configuration, and keys belonging to WordPress. The same predicate
			 * gates the import, so the two directions cannot drift apart — an
			 * option the export omits can no longer be accepted by the import from
			 * a hand-edited or older file.
			 */
			$name_key = is_multisite() ? 'meta_key' : 'option_name';
			$results  = array_values(
				array_filter(
					$results,
					function ( $row ) use ( $name_key ) {
						return \MLS\Helpers\OptionsHelper::is_portable_option( $row->$name_key );
					}
				)
			);

			// Convert serialized PHP values to JSON strings for a safer export format.
			$value_key = is_multisite() ? 'meta_value' : 'option_value';
			foreach ( $results as &$row ) {
				if ( is_serialized( $row->$value_key ) ) {
					$unserialized = unserialize( $row->$value_key, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
					if ( false !== $unserialized ) {
						/*
						 * Secrets nested inside the settings array cannot be
						 * excluded by option name; drop them here, while the
						 * value is still structured.
						 */
						$row->$value_key = wp_json_encode( \MLS\Helpers\OptionsHelper::redact_secret_settings( $unserialized ) );
					}
				}
			}
			unset( $row );

			wp_send_json_success( wp_json_encode( $results ) );
		}

		/**
		 * Checks settings before importing.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function check_setting_and_handle_import() {
			// Grab POSTed data.
			// Check capability first to short-circuit early and reduce attack surface.
			if ( ! current_user_can( 'manage_options' ) || ! wp_doing_ajax() ) {
				wp_send_json_error( esc_html__( 'Permission denied.', 'melapress-login-security' ) );
				return;
			}

			$nonce      = null;
			$valid_call = false;

			// Check if has signature of valid request.
			if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) === 'xmlhttprequest' ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$valid_call = true;
			}

			if ( isset( $_POST['nonce'] ) ) {
				$nonce = \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) );
			}

			// Check nonce.
			if ( ! $valid_call || empty( $nonce ) || ! wp_verify_nonce( $nonce, 'mls-export-settings' ) ) {
				wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'melapress-login-security' ) );
				return;
			}

			$setting_name = null;
			if ( isset( $_POST['setting_name'] ) ) {
				$setting_name = \sanitize_text_field( \wp_unslash( $_POST['setting_name'] ) );
			}
			$process_import = null;
			if ( isset( $_POST['process_import'] ) ) {
				$process_import = \sanitize_text_field( \wp_unslash( $_POST['process_import'] ) );
			}

			/*
			 * The target option name comes from the uploaded file. `mls`/`ppm`
			 * as a bare prefix let it name anything that merely starts that way,
			 * including keys in the table-prefix namespace of a site whose prefix
			 * begins with the same letters. Require an underscore-delimited
			 * prefix we own, and refuse keys that belong to WordPress.
			 */
			$owned = false;
			foreach ( OptionsHelper::owned_key_prefixes() as $owned_prefix ) {
				if ( 0 === strpos( (string) $setting_name, $owned_prefix ) ) {
					$owned = true;
					break;
				}
			}

			if ( ! $setting_name || '' === $setting_name || ! $owned ) {
				wp_send_json_error( esc_html__( 'Invalid setting given.', 'melapress-login-security' ) );
				return;
			}

			/*
			 * Refuse anything that cannot meaningfully cross between sites, by the
			 * same predicate the export uses: licence credentials, options that
			 * describe one installation rather than its configuration, and keys
			 * belonging to WordPress.
			 *
			 * The export no longer offers these, but a file can be hand-edited or
			 * carried over from a version that did — `mls_activation` in particular
			 * used to import successfully, and it is the fallback "last password
			 * change" baseline, so importing another site's value changed whether
			 * users' passwords counted as expired.
			 */
			if ( ! OptionsHelper::is_portable_option( $setting_name ) ) {
				$message = array( 'setting_checked' => $setting_name );

				$message['failure_reason']      = esc_html__( 'This setting is specific to one website and cannot be imported.', 'melapress-login-security' );
				$message['failure_reason_type'] = 'not_supported';
				wp_send_json_error( $message );
				return;
			}

			/*
			 * filter_input() returns null when the field is absent, so indexing
			 * element zero warned and yielded null, which then travelled on as
			 * though a value had been supplied.
			 */
			$setting_value = filter_input( INPUT_POST, 'setting_value', FILTER_DEFAULT, FILTER_FORCE_ARRAY );

			if ( ! is_array( $setting_value ) || ! array_key_exists( 0, $setting_value ) ) {
				wp_send_json_error( esc_html__( 'No setting value given.', 'melapress-login-security' ) );
				return;
			}

			$setting_value = $setting_value[0];

			$message = array(
				'setting_checked' => $setting_name,
			);

			$failed = false;

			// Check if relevant data is present for setting to be operable before import.
			if ( ! empty( $setting_value ) ) {
				if ( 'true' !== $process_import && $failed ) {
					wp_send_json_error( $message );
				}
			}

			$mls_options     = new \MLS\MLS_Options();
			$policy_keys     = array_keys( $mls_options->default_options );
			$setting_keys    = array_keys( $mls_options->default_setting );
			$known_keys      = array_merge( $policy_keys, $setting_keys );
			$processed_value = false;

			/*
			 * Whether this option is one the importer knows how to handle at all,
			 * as distinct from whether it ended up with a value worth writing.
			 * Conflating the two is what let unrecognised options report success.
			 */
			$recognised = false;

			$known_other_keys = array(
				'mls_activation',
				'mls_active_version',
				'mls_reset_timestamp',
				'mls_wizard_complete',
				'ppmwp_activation',
				'ppmwp_active_version',
				'ppmwp_reset_timestamp',
				'ppmwp_wizard_complete',
			);

			// Try JSON decode first (current export format).
			$value_arr = null;
			if ( is_string( $setting_value ) ) {
				$json_decoded = json_decode( $setting_value, true );
				if ( JSON_ERROR_NONE === json_last_error() && is_array( $json_decoded ) ) {
					$value_arr = $json_decoded;
				}
			}

			// Backward compatibility: handle old exports with PHP serialized values.
			if ( null === $value_arr && is_serialized( $setting_value ) ) {
				$value_arr = unserialize( $setting_value, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			}

			if ( is_array( $value_arr ) && ! empty( $value_arr ) ) {
				/*
				 * Credentials nested in the settings array are dropped rather than
				 * written. The export strips them now, but `iplocate_api_key` is a
				 * legitimate member of default_setting — so before this, a file
				 * produced by an earlier version, or edited by hand, would have had
				 * its API key installed on the target site by an import.
				 */
				$secret_keys = OptionsHelper::secret_setting_keys();

				$processed_value = array();
				foreach ( $value_arr as $array_key => $array_value ) {
					if ( in_array( (string) $array_key, $secret_keys, true ) ) {
						continue;
					}

					if ( in_array( $array_key, $known_keys, true ) ) {
						$processed_value[ $array_key ] = OptionsHelper::sanitise_value_by_key( $array_key, \wp_unslash( $array_value ) );
						$recognised                    = true;
					}
				}
			} elseif ( in_array( $setting_name, $known_other_keys, true ) ) {
				$processed_value = OptionsHelper::sanitise_value_by_key( $setting_name, \wp_strip_all_tags( \wp_unslash( $setting_value ) ) );
				$recognised      = true;
			}

			/*
			 * Report whenever nothing will be written, and mirror the condition the
			 * import below actually uses.
			 *
			 * This tested only for an *empty array*. An option the importer does not
			 * recognise at all leaves $processed_value as `false`, and `is_array(
			 * false )` is false — so it slipped past this check, then failed the
			 * truthiness test on the import itself, and the screen showed a green
			 * tick for a setting that had been silently ignored. Exporting and
			 * re-importing an untouched 2.4.0 site reported success for
			 * `mls_plugin_version` and `mls_group_toggles_migrated` on exactly that
			 * path, while `mls_inactive_users` — which produces an empty array
			 * rather than false, because a list of user IDs decodes to an array
			 * whose numeric keys match no known setting — was the only row telling
			 * the truth.
			 */
			if ( ! $processed_value ) {
				$message['failure_reason']      = $recognised
					? esc_html__( 'No value to import for this setting.', 'melapress-login-security' )
					: esc_html__( 'Setting not supported for import.', 'melapress-login-security' );
				$message['failure_reason_type'] = 'not_supported';
				wp_send_json_error( $message );
				return;
			}

			if ( ( 'ppmwp_setting' === $setting_name || 'mls_setting' === $setting_name ) && isset( $_POST['from_email_to_use'] ) && ! empty( $_POST['from_email_to_use'] ) && is_array( $processed_value ) && ! empty( $processed_value ) ) {
				if ( \is_email( \sanitize_email( \wp_unslash( $_POST['from_email_to_use'] ) ) ) ) {
					$processed_value['from_email'] = \sanitize_email( \wp_unslash( $_POST['from_email_to_use'] ) );
				}
			}

			// If set to import the data once checked, then do so.
			if ( $processed_value && 'true' === $process_import && ! isset( $message['failure_reason'] ) ) {
				/**
				 * Fire of action for others to observe.
				 */
				do_action( 'mls_settings_imported' );

				// Merge with existing values so keys added in newer versions are preserved.
				if ( is_array( $processed_value ) ) {
					$existing = get_site_option( $setting_name, array() );
					if ( is_array( $existing ) ) {
						$processed_value = array_merge( $existing, $processed_value );
					}

					// Auto-enable group toggles for older exports that lack them.
					if ( 'mls_options' === $setting_name || 'ppmwp_options' === $setting_name ) {
						$processed_value = self::maybe_enable_group_toggles( $processed_value );
					}
				}

				$updated                        = ( ! update_site_option( $setting_name, $processed_value ) ) ? esc_html__( 'Setting updated', 'melapress-login-security' ) : esc_html__( 'Setting created', 'melapress-login-security' );
				$message['import_confirmation'] = $updated;
				wp_send_json_success( $message );
			}

			wp_send_json_success( $message );
			exit;
		}


		/**
		 * Gets value ready for checking when needed.
		 *
		 * @param mixed $value Value.
		 *
		 * @return string - Result.
		 *
		 * @since 2.0.0
		 */
		public function trim_and_explode( $value ) {
			if ( is_array( $value ) ) {
				return explode( ',', $value[0] );
			} else {
				$setting_value = trim( $value, '"' );

				return str_replace( '""', '"', explode( ',', $setting_value ) );
			}
		}

		/**
		 * Enable group toggles when child policies are active (backwards compat for pre-2.4 exports).
		 *
		 * @param array $options The merged options array.
		 *
		 * @return array
		 *
		 * @since 2.4.0
		 */
		private static function maybe_enable_group_toggles( array $options ): array {
			$password_policies = array( 'activate_password_policies', 'activate_password_expiration_policies', 'activate_password_recycle_policies' );
			foreach ( $password_policies as $key ) {
				if ( isset( $options[ $key ] ) && 'yes' === $options[ $key ] ) {
					$options['enable_password_policies_group'] = 'yes';
					break;
				}
			}

			if ( isset( $options['enable_sessions_policies'] ) && 'yes' === $options['enable_sessions_policies'] ) {
				$options['enable_session_policies_group'] = 'yes';
			}

			if ( isset( $options['enable_device_policies'] ) && 'yes' === $options['enable_device_policies'] ) {
				$options['enable_device_policies_group'] = 'yes';
			}

			if ( isset( $options['failed_login_policies_enabled'] ) && 'yes' === $options['failed_login_policies_enabled'] ) {
				$options['enable_login_policies_group'] = 'yes';
			}

			return $options;
		}
	}
}
