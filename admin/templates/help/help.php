<?php
/**
 * Contact us wrapper.
 *
 * @package WordPress
 * @subpackage wpassword
 */

// Plugin adverts sidebar.
require_once 'sidebar.php';
?>
<div class="ppm-help-main">
	<!-- getting started -->
	<div class="title">
		<h2><?php esc_html_e( 'Getting Started', 'ppm-wp' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'It is easy to get started with the MelaPress Login Security. Simply enable and configure the password policies you want to enforce. Below are a few links of guides to help you get started:', 'ppm-wpp' ); ?></p>
	<ul>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://www.wpwhitesecurity.com/support/kb/getting-started-wpassword/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ), esc_html__( 'Getting started with the MelaPress Login Security', 'ppm-wp' ) ); ?></li>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://www.wpwhitesecurity.com/support/kb/configure-different-password-policies-wordpress-user-roles/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ), esc_html__( 'Configure different password policies for different user roles', 'ppm-wp' ) ); ?></li>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://www.wpwhitesecurity.com/support/kb/exclude-user-roles-wordpress-password-policies/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ), esc_html__( 'How to exclude users or roles from the password policies', 'ppm-wp' ) ); ?></li>
	</ul>
	<!-- End -->
	<br>
	<p><iframe title="<?php esc_html_e( 'Getting Started', 'ppm-wp' ); ?>" class="wsal-youtube-embed" width="100%" height="315" src="https://www.youtube.com/embed/gXaMw4D_yo8" frameborder="0" allowfullscreen></iframe></p>

	<?php
	?>
	<div class="title">
		<h2 style="padding-left: 0;"><?php esc_html_e( 'Plugin Support', 'ppm-wp' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'You can post your question on our support forum or send us an email for 1 to 1 support. Email support is provided to both free and premium plugin users.', 'ppm-wp' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/melapress-login-security/' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Free support forum', 'ppm-wp' ); ?></a>
		<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/support/submit-ticket/' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Free email support', 'ppm-wp' ); ?></a>
	</div>
	<br>
	<!-- End -->
	<?php
	?>

	<!-- Plugin documentation -->
	<div class="title">
		<h2><?php esc_html_e( 'Plugin Documentation', 'ppm-wp' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'For more technical information about the MelaPress Login Security plugin please visit the plugin’s knowledge base.', 'ppm-wp' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/support/kb/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Knowledge Base', 'ppm-wp' ); ?></a>
	</div>
	<br>
	<!-- End -->

	<!-- Plugin support -->
	<div class="title">
		<h2><?php esc_html_e( 'Plugin Support', 'ppm-wp' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'Have you encountered or noticed any issues while using the MelaPress Login Security plugin? Or do you want to report something to us?', 'ppm-wp' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/support/submit-ticket/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Open support ticket', 'ppm-wp' ); ?></a>
		<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/contact-wp-white-security/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Contact Us', 'ppm-wp' ); ?></a>
	</div>
	<!-- End -->
</div>
