<?php
/**
 * Upgrade sidebar.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="upgrade-sidebar postbox-container">
	<div class="postbox">
		<h3 class="hndle" style="text-align: center;">
			<img src="<?php echo esc_url( MLS_PLUGIN_URL . 'assets/images/password-policy-manager.png' ); ?>" style="max-width: 80px; display: inline-block; margin: 10px 0 15px;">
			<span style="display: block"><?php esc_html_e( 'Upgrade to Premium to benefit from', 'melapress-login-security' ); ?></span>
		</h3>
		<div class="inside">
			<div>
				<ul class="c4wp-pro-features-ul">
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'One-click integration with WooCommerce, LearnDash & many other plugins', 'melapress-login-security' ); ?></li>
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'Hide the WordPress login page', 'melapress-login-security' ); ?></li>
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'Block or allow login page traffic per country', 'melapress-login-security' ); ?></li>
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'Lock inactive user accounts', 'melapress-login-security' ); ?></li>
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'Control the days and times when users can log in', 'melapress-login-security' ); ?></li>
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'Security questions for password resets etc', 'melapress-login-security' ); ?></li>
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'Edit all the email templates and users\' notifications to fit your business\' branding', 'melapress-login-security' ); ?></li>
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'Receive weekly summary of all password resets, locked users & failed logins', 'melapress-login-security' ); ?></li>
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'Reports that give you an overview of the latest user activity, password resets & more', 'melapress-login-security' ); ?></li>
					<li class="dashicons-before dashicons-yes-alt"> <?php esc_html_e( 'No adverts!', 'melapress-login-security' ); ?></li>
				</ul>
				<p style="text-align: center; margin: auto"><a class="premium-link" href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugins&utm_medium=link&utm_campaign=mls" target="_blank"><?php esc_html_e( 'Upgrade to Premium', 'melapress-login-security' ); ?></a>
			</div>
		</div>
	</div>
</div>