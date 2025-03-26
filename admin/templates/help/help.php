<?php
/**
 * Contact us wrapper.
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
		<h2><?php esc_html_e( 'Getting Started', 'melapress-login-security' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'It is easy to get started with the MelaPress Login Security. Simply enable and configure the password policies you want to enforce. Below are a few links of guides to help you get started:', 'ppm-wpp' ); ?></p>
	<ul>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://melapress.com/support/kb/melapress-login-security-getting-started/?utm_source=plugins&utm_medium=link&utm_campaign=mls' ), esc_html__( 'Getting started with MelaPress Login Security', 'melapress-login-security' ) ); ?></li>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://melapress.com/support/kb/melapress-login-security-configure-different-password-policies-wordpress-user-roles/?utm_source=plugins&utm_medium=link&utm_campaign=mls' ), esc_html__( 'Configure different password policies for different user roles', 'melapress-login-security' ) ); ?></li>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://melapress.com/support/kb/melapress-login-security-exclude-user-roles-wordpress-password-policies/?utm_source=plugins&utm_source=plugins&utm_medium=link&utm_campaign=mls' ), esc_html__( 'How to exclude users or roles from the password policies', 'melapress-login-security' ) ); ?></li>
	</ul>
	<!-- End -->
	<br>
	<p><iframe title="<?php esc_html_e( 'Getting Started', 'melapress-login-security' ); ?>" class="wsal-youtube-embed" width="100%" height="315" src="https://www.youtube.com/embed/gXaMw4D_yo8" frameborder="0" allowfullscreen></iframe></p>

	<?php
	/* @free:start */
	?>
	<div class="title">
		<h2 style="padding-left: 0;"><?php esc_html_e( 'Plugin Support', 'melapress-login-security' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'You can post your question on our support forum or send us an email for 1 to 1 support. Email support is provided to both free and premium plugin users.', 'melapress-login-security' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/melapress-login-security/' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Free support forum', 'melapress-login-security' ); ?></a>
		<a href="<?php echo esc_url( 'https://melapress.com/support/submit-ticket/?utm_source=plugins&utm_medium=link&utm_campaign=mls' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Free email support', 'melapress-login-security' ); ?></a>
	</div>
	<br>
	<!-- End -->
	<?php
	/* @free:end */
	?>

	<!-- Plugin documentation -->
	<div class="title">
		<h2><?php esc_html_e( 'Plugin Documentation', 'melapress-login-security' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'For more technical information about the MelaPress Login Security plugin please visit the plugin’s knowledge base.', 'melapress-login-security' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://melapress.com/support/kb/?utm_source=plugins&utm_medium=link&utm_campaign=mls' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Knowledge Base', 'melapress-login-security' ); ?></a>
	</div>
	<br>
	<!-- End -->

	<!-- Plugin support -->
	<div class="title">
		<h2><?php esc_html_e( 'Plugin Support', 'melapress-login-security' ); ?></h2>
	</div>
	<p><?php esc_html_e( 'Have you encountered or noticed any issues while using the MelaPress Login Security plugin? Or do you want to report something to us?', 'melapress-login-security' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://melapress.com/support/submit-ticket/?utm_source=plugins&utm_medium=link&utm_campaign=mlse' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Open support ticket', 'melapress-login-security' ); ?></a>
		<a href="<?php echo esc_url( 'https://melapress.com/contact/?utm_source=plugins&utm_medium=link&utm_campaign=mls' ); ?>" class="button" target="_blank"><?php esc_html_e( 'Contact Us', 'melapress-login-security' ); ?></a>
	</div>
	<!-- End -->
</div>
