<?php
/**
 * Contact us wrapper.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);
use MLS\Licensing\Licensing_Factory;

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
		<h2><?php esc_html_e( 'Contact Us', 'melapress-login-security' ); ?></h2>
	</div>
	<style type="text/css">
		.fs-secure-notice {
			position: relative !important;
			top: 0 !important;
			left: 0 !important;
		}
		.fs-full-size-wrapper {
			margin: 10px 20px 0 2px !important;
		}
	</style>
	<?php
	if ( strtolower( 'freemius' ) === strtolower( Licensing_Factory::get_provider_type() ) ) {
		$freemius_id = Licensing_Factory::provider_call( 'get_id' );
		$vars        = array( 'id' => $freemius_id );
		if ( function_exists( 'fs_get_template' ) ) {
			echo fs_get_template( 'contact.php', $vars ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	?>
</div>
