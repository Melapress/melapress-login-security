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

$form_class    = ( $sidebar_required ) ? 'sidebar-present' : '';
$login_control = new \MLS\Login_Page_Control();
?>

<div class="wrap ppm-wrap">
	<form method="post" id="ppm-wp-settings" class="<?php echo esc_attr( $form_class ); ?>">
		<div class="mls-settings">

			<!-- getting started -->
			<div class="page-head" style="padding-right: 0">
				<h2><?php esc_html_e( 'Login Page Hardening', 'melapress-login-security' ); ?></h2>
			</div>

			<br>

			<h3><?php esc_html_e( 'Change the login page URL', 'melapress-login-security' ); ?></h3>
			<p class="description" style="max-width: none;">
				<?php esc_html_e( 'The default WordPress login page URL is /wp-admin/ or /wp-login.php. Improve the security of your website by changing the URL of the WordPress login page to anything you want, thus preventing easy access to bots and attackers. To change the URL just specify the new path in the placeholder below. Do not include the trailing slash.', 'melapress-login-security' ); ?>
			</p>

			<div class="settings-tab ppm-login-page-settings">
			<table class="form-table">
					<tbody>
						<?php echo $login_control::render_login_page_url_settings(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
					</tbody>
				</table>
				<table class="form-table">
					<tbody>
						<?php echo $login_control::render_login_gdpr_settings(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</tbody>
				</table>
				<?php
				?>
			</div>
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
