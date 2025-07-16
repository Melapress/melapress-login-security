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
		\add_filter( 'wp_ajax_mls_export_settings', array( $this, 'export_settings' ), 10, 1 );
		\add_filter( 'wp_ajax_mls_check_setting_and_handle_import', array( $this, 'check_setting_and_handle_import' ), 10, 1 );
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

		wp_enqueue_script( 'mls_settings_importexport', MLS_PLUGIN_URL . 'admin/assets/js/settings-importexport.js', array( 'ppm-wp-settings' ), MLS_VERSION, true );

		wp_localize_script(
			'mls_settings_importexport',
			'wpws_import_data',
			array(
				'wp_import_nonce'       => wp_create_nonce( 'mls-import-settings' ),
				'checkingMessage'       => esc_html__( 'Checking import contents', 'melapress-login-security' ),
				'checksPassedMessage'   => esc_html__( 'Ready to import', 'melapress-login-security' ),
				'checksFailedMessage'   => esc_html__( 'Issues found', 'melapress-login-security' ),
				'importingMessage'      => esc_html__( 'Importing settings', 'melapress-login-security' ),
				'importedMessage'       => esc_html__( 'Settings imported', 'melapress-login-security' ),
				'helpMessage'           => esc_html__( 'Help', 'melapress-login-security' ),
				'notFoundMessage'       => esc_html__( 'The role, user or post type contained in your settings are not currently found in this website. Importing such settings could lead to abnormal behavour. For more information and / or if you require assistance, please', 'melapress-login-security' ),
				'notSupportedMessage'   => esc_html__( 'Currently this data is not supported by our export/import wizard.', 'melapress-login-security' ),
				'restrictAccessMessage' => esc_html__( 'To avoid accidental lock-out, this setting is not imported.', 'melapress-login-security' ),
				'wrongFormat'           => esc_html__( 'Please upload a valid JSON file.', 'melapress-login-security' ),
				'cancelMessage'         => esc_html__( 'Cancel', 'melapress-login-security' ),
				'readyMessage'          => esc_html__( 'The settings file has been tested and the configuration is ready to be imported. Would you like to proceed?', 'melapress-login-security' ),
				'proceedMessage'        => esc_html__( 'The configuration has been successfully imported. Click OK to close this window', 'melapress-login-security' ),
				'proceed'               => esc_html__( 'Proceed', 'melapress-login-security' ),
				'ok'                    => esc_html__( 'OK', 'melapress-login-security' ),
				'helpPage'              => '',
				'helpLinkText'          => esc_html__( 'Contact Us', 'melapress-login-security' ),
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
		return $markup . '<a href="#settings-export" class="nav-tab" data-tab-target=".mls-settings-export">' . esc_attr__( 'Settings Import/Export', 'melapress-login-security' ) . '</a>';
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
		$nonce = wp_create_nonce( 'mls-export-settings' );
		?>
				
				<tr valign="top">
					<br>
					<h1><?php esc_html_e( 'Settings Import/Export', 'melapress-login-security' ); ?></h1>
					<p class="description"><?php esc_html_e( 'On this page you can import and export your plugins settings.', 'melapress-login-security' ); ?></p>
					<br>
				</tr>

				<tr>
					<th><label><?php esc_html_e( 'Export settings', 'melapress-login-security' ); ?></label></th>
					<td>
						<fieldset>
							<input type="button" id="export-settings" class="button-primary"
									value="<?php esc_html_e( 'Export', 'melapress-login-security' ); ?>"
									data-export-wpws-settings data-nonce="<?php echo esc_attr( $nonce ); ?>">
							<p class="description">
							<?php esc_html_e( 'Once the settings are exported a download will automatically start. The settings are exported to a JSON file.', 'melapress-login-security' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th><label><?php esc_html_e( 'Import settings', 'melapress-login-security' ); ?></label></th>
					<td>
						<fieldset>

							<input type="file" id="wpws-settings-file" name="filename"><br>
							<input style="margin-top: 7px;" type="submit" id="import-settings" class="button-primary" data-import-wpws-settings data-nonce="<?php echo esc_attr( $nonce ); ?>" value="<?php esc_html_e( 'Validate & Import', 'melapress-login-security' ); ?>">
							<p class="description">
							<?php esc_html_e( 'Once you choose a JSON settings file, it will be checked prior to being imported to alert you of any issues, if there are any.', 'melapress-login-security' ); ?>
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

		if ( is_multisite() ) {
			$prepared_query = $wpdb->prepare(
				"SELECT `meta_key`, `meta_value` FROM `{$wpdb->sitemeta}` WHERE `meta_key` LIKE %s ORDER BY `meta_key` ASC",
				MLS_PREFIX . '%'
			);
		} else {
			$prepared_query = $wpdb->prepare(
				"SELECT `option_name`, `option_value` FROM `{$wpdb->options}` WHERE `option_name` LIKE %s ORDER BY `option_name` ASC",
				MLS_PREFIX . '%'
			);
		}

		/**
		 * Fire of action for others to observe.
		 */
		do_action( 'mls_settings_exported' );

		$results = $wpdb->get_results( $prepared_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

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

		if ( ! current_user_can( 'manage_options' ) || ! wp_doing_ajax() ) {
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

		if ( ! $setting_name || '' === $setting_name || ! strpos( $setting_name, 'mls' ) === 0 || 0 === ! strpos( $setting_name, 'ppm' ) ) {
			wp_send_json_error( esc_html__( 'Invalid setting given.', 'melapress-login-security' ) );
			return;
		}

		$setting_value = filter_input( INPUT_POST, 'setting_value', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
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

		if ( is_serialized( $setting_value ) ) {
			$processed_value = array();
			$value_arr       = unserialize( $setting_value, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

			if ( $value_arr && ! empty( $value_arr ) ) {
				foreach ( $value_arr as $array_key => $array_value ) {
					if ( in_array( $array_key, $known_keys, true ) ) {
						$processed_value[ $array_key ] = OptionsHelper::sanitise_value_by_key( $array_key, \wp_unslash( $array_value ) );
					}
				}
			}
		} elseif ( in_array( $setting_name, $known_other_keys, true ) ) {
			$processed_value = OptionsHelper::sanitise_value_by_key( $setting_name, \wp_strip_all_tags( \wp_unslash( $setting_value ) ) );
		}

		// If we reached this point with nothing, do not continue further.
		if ( is_array( $processed_value ) && empty( $processed_value ) ) {
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
}
