<?php
/**
 * Handles policies admin area.
 *
 * @package WordPress
 * @subpackage wpassword
 */

 $sidebar_required    = false;
 /* @free:start */
 // Override in free edition.
 $sidebar_required    = true;
 /* @free:end */
 $form_class = ( $sidebar_required ) ? 'sidebar-present' : '';
?>

<div class="wrap ppm-wrap">
	<form method="post" id="ppm-wp-settings" class="<?php esc_attr_e( $form_class ); ?>">
		<div class="ppm-settings">

			<!-- getting started -->
			<div class="page-head">
				<h2><?php esc_html_e( 'Forms & Placement', 'ppm-wp' ); ?></h2>
			</div>

			<div class="ppm-general-settings">
				<table class="form-table">
					<tbody>
						<tr class="setting-heading" valign="top">
							<th scope="row">
								<label for="ppm-send-summary-email">
									<h3><?php esc_html_e( 'Standard forms', 'ppm-wp' ); ?></h3>
								</label>
							</th>
						</tr>

						<tr valign="top">
							<th scope="row">
								<label for="ppm-enable_ld_register">
									<?php esc_attr_e( 'Wordpress forms', 'ppm-wp' ); ?>
								</label>
							</th>
							<td>
								<fieldset>
									<label for="ppm-enable_wp_reset_form" class="disabled">
										<input name="_ppm_options[enable_wp_reset_form]" type="checkbox" id="ppm-enable_wp_reset_form"
												value="yes" checked class="disabled"/>
												<?php esc_attr_e( 'This website\'s password reset page', 'ppm-wp' ); ?>
									</label>
								</fieldset>
								<fieldset>
									<label for="ppm-enable_wp_profile_form" class="disabled">
										<input name="_ppm_options[enable_wp_profile_form]" type="checkbox" id="ppm-enable_wp_profile_form"
												value="yes" checked class="disabled"/>
												<?php esc_attr_e( 'User profile page', 'ppm-wp' ); ?>
									</label>
								</fieldset>
							</td>
								</tr>

					</tbody>
				</table>
			</div>

			<?php
				$scripts_required = false;
				$additonal_tabs   = apply_filters( 'ppmwp_forms_settings_page_content_tabs', '' );
			?>

		</div>

		<?php wp_nonce_field( PPMWP_PREFIX . '_nonce_form', PPMWP_PREFIX . '_nonce' ); ?>
		
		<div class="submit">
			<input type="submit" name="_ppm_save" class="button-primary"
		value="<?php echo esc_attr( __( 'Save Changes', 'ppm-wp' ) ); ?>" />
		</div>
	</form>

	<?php
	/* @free:start */
	require_once PPM_WP_PATH . 'admin/templates/views/upgrade-sidebar.php';
	/* @free:end */

	?>

</div> 
