<?php
/**
 * Help area sidebar.
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

<div class="our-wordpress-plugins side-bar">
	<h3><?php esc_html_e( 'Our WordPress Plugins', 'melapress-login-security' ); ?></h3>
	<ul>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( MLS_PLUGIN_URL . 'assets/images/wp-security-audit-log-img.jpeg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Keep a log of users and under the hood site activity.', 'melapress-login-security' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugins',
									'utm_medium'   => 'link',
									'utm_campaign' => 'mls',
								),
								'https://melapress.com/wordpress-activity-log/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'melapress-login-security' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( MLS_PLUGIN_URL . 'assets/images/wp-2fa.jpeg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Add an extra layer of security to your login pages with 2FA & require your users to use it.', 'melapress-login-security' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugins',
									'utm_medium'   => 'link',
									'utm_campaign' => 'mls',
								),
								'https://melapress.com/wordpress-2fa/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'melapress-login-security' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( MLS_PLUGIN_URL . 'assets/images/c4wp.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Protect website forms & login pages from spambots & automated attacks.', 'melapress-login-security' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugins',
									'utm_medium'   => 'link',
									'utm_campaign' => 'mls',
								),
								'https://melapress.com/wordpress-captcha/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'melapress-login-security' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( MLS_PLUGIN_URL . 'assets/images/mre.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Create, edit, and delete and easily manage WordPerss user roles like a pro.', 'melapress-login-security' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugins',
									'utm_medium'   => 'link',
									'utm_campaign' => 'mls',
								),
								'https://melapress.com/wordpress-user-roles-editor/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'melapress-login-security' ); ?></a>
					</div>
				</div>
			</div>
		</li>
	</ul>
</div>
