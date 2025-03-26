<?php
/**
 * Handles policies admin area.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sidebar_required = false;

/* @free:start */

// Override in free edition.
$sidebar_required = true;
/* @free:end */

$form_class = ( $sidebar_required ) ? 'sidebar-present' : '';
$mls        = melapress_login_security();
?>

<div class="wrap ppm-wrap">
	<form method="post" id="ppm-wp-settings" class="<?php echo esc_attr( $form_class ); ?>">
		<div class="mls-settings">

			<!-- getting started -->
			<div class="page-head" style="padding-right: 0">
				<h2><?php esc_html_e( 'Forms & Placement', 'melapress-login-security' ); ?></h2>
				<p class="description" style="max-width: none"><?php esc_html_e( 'By default, the login and password security policies configured in this plugin are only be enforced on the native WordPress forms. However, the plugin has out-of-the-box support for popular third-party plugins such as WooCommerce and BuddyPress. Use the checkboxes below to select the forms on which you\'d like to enforce the configured policies. The list of plugins is sorted in alphabetical order.', 'melapress-login-security' ); ?></p>
				<br>
			</div>

			<div class="ppm-general-settings">
				<table class="form-table">
					<tbody>
						<tr class="setting-heading" valign="top">
							<th scope="row">
								<h3><?php esc_html_e( 'Standard forms', 'melapress-login-security' ); ?></h3>							
							</th>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php esc_attr_e( 'WordPress forms', 'melapress-login-security' ); ?>
							</th>
							<td>
								<fieldset>
									<label for="ppm-enable_wp_reset_form">
										<input name="mls_options[enable_wp_reset_form]" type="checkbox" id="ppm-enable_wp_reset_form"
												value="yes" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->enable_wp_reset_form ) ); ?>/>
												<?php esc_attr_e( 'This website\'s password reset page', 'melapress-login-security' ); ?>
									</label>
								</fieldset>
								<fieldset>
									<label for="ppm-enable_wp_profile_form">
										<input name="mls_options[enable_wp_profile_form]" type="checkbox" id="ppm-enable_wp_profile_form"
												value="yes" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->enable_wp_profile_form ) ); ?>/>
												<?php esc_attr_e( 'User profile page', 'melapress-login-security' ); ?>
									</label>
								</fieldset>
							</td>
								</tr>

					</tbody>
				</table>
			</div>

			<?php
				$scripts_required = false;
				$additional_tabs  = apply_filters( 'mls_forms_settings_page_content_tabs', '' );
			?>

		</div>

		<?php wp_nonce_field( MLS_PREFIX . '_nonce_form', MLS_PREFIX . '_nonce' ); ?>
		
		<div class="submit">
			<input type="submit" name="_ppm_save" class="button-primary"
		value="<?php echo esc_attr( __( 'Save Changes', 'melapress-login-security' ) ); ?>" />
		</div>
	</form>

	<?php
	/* @free:start */
	require_once MLS_PATH . 'admin/templates/views/upgrade-sidebar.php';
	/* @free:end */

	?>

</div> 
