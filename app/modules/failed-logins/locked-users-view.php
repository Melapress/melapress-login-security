<?php
/**
 * Inactive Users List Table.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

$scripts_required = false;

?>
<div class="wrap ppm-wrap">
	<div class="page-head">
		<h2><?php esc_html_e( 'User Management', 'melapress-login-security' ); ?></h2>
	</div>

	<?php
		$tab_links = apply_filters( 'mls_user_management_page_nav_tabs', '' );

	if ( ! empty( $tab_links ) ) {
		?>
				<div class="nav-tab-wrapper">
					<a href="#locked-users" class="nav-tab nav-tab-active" data-tab-target=".mls-locked-users"><?php esc_html_e( 'Locked Users', 'melapress-login-security' ); ?></a>
				<?php echo wp_kses_post( $tab_links ); ?>
				</div>
			<?php
	}
	?>
	
	<div class="settings-tab mls-locked-users">
		<?php require_once MLS_PATH . 'app/modules/failed-logins/inactive-users.php'; ?>
	</div>

	<?php
		$additional_tabs = apply_filters( 'mls_user_management_page_content_tabs', '' );
