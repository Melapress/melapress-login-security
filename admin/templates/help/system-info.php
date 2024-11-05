<?php
/**
 * System info
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin adverts sidebar.
require_once 'sidebar.php';
?>
<div class="mls-help-main">
	<!-- getting started -->
	<div class="title">
		<h2><?php esc_html_e( 'System information', 'melapress-login-security' ); ?></h2>
	</div>
	<?php $mls = melapress_login_security(); ?>
	<form method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="wsal-sysinfo"><?php echo esc_html( $mls->get_sysinfo() ); ?></textarea>
		<p class="submit">
			<input type="hidden" name="ppmwp-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'ppmwp-download-sysinfo', false ); ?>
		</p>
	</form>
</div>
