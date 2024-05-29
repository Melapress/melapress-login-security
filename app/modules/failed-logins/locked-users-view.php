<?php
/**
 * Inactive Users List Table.
 *
 * @since 1.0.0
 *
 * @package wordpress
 */

 $scripts_required = false;

?>
<div class="wrap ppm-wrap">
	<div class="page-head">
		<h2><?php esc_html_e( 'User Management', 'ppm-wp' ); ?></h2>
	</div>

	<?php
		$tab_links = apply_filters( 'ppmwp_user_management_page_nav_tabs', '' );

		if ( ! empty( $tab_links ) ) {
			?>
				<div class="nav-tab-wrapper">
					<a href="#locked-users" class="nav-tab nav-tab-active" data-tab-target=".ppm-locked-users">Locked Users</a>
					<?php echo wp_kses_post( $tab_links ); ?>
				</div>
			<?php
		}
	?>
	
	<div class="settings-tab ppm-locked-users">
		<?php include_once PPM_WP_PATH . 'app/modules/failed-logins/inactive-users.php'; ?>
	</div>

	<?php
		$additonal_tabs   = apply_filters( 'ppmwp_user_management_page_content_tabs', '' );
