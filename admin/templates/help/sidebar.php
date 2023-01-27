<?php
/**
 * Help area sidebar.
 *
 * @package WordPress
 * @subpackage wpassword
 */

?>

<div class="our-wordpress-plugins side-bar">
	<h3><?php esc_html_e( 'Our WordPress Plugins', 'ppm-wp' ); ?></h3>
	<ul>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( PPM_WP_URL . 'assets/images/wp-security-audit-log-img.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Keep a log of users and under the hood site activity.', 'ppm-wp' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugin',
									'utm_medium'   => 'referral',
									'utm_campaign' => 'WSAL',
									'utm_content'  => 'PPMWP+banner',
								),
								'https://wpactivitylog.com'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'ppm-wp' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( PPM_WP_URL . 'assets/images/wp-2fa.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Add an extra layer of security to your login pages with 2FA & require your users to use it.', 'ppm-wp' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugin',
									'utm_medium'   => 'referral',
									'utm_campaign' => 'WP2FA',
									'utm_content'  => 'PPMWP+banner',
								),
								'https://wp2fa.io/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'ppm-wp' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( PPM_WP_URL . 'assets/images/c4wp.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Protect website forms & login pages from spambots & automated attacks.', 'ppm-wp' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugin',
									'utm_medium'   => 'referral',
									'utm_campaign' => 'WP2FA',
									'utm_content'  => 'WSAL+banner',
								),
								'https://wpactivitylog.com/wordpress-plugins/captcha-plugin-wordpress/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'ppm-wp' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( PPM_WP_URL . 'assets/images/website-file-changes-monitor.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'Automatically identify unauthorized file changes on your WordPress site.', 'ppm-wp' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugin',
									'utm_medium'   => 'referral',
									'utm_campaign' => 'WFCM',
									'utm_content'  => 'PPMWP+banner',
								),
								'https://www.wpwhitesecurity.com/wordpress-plugins/website-file-changes-monitor/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'ppm-wp' ); ?></a>
					</div>
				</div>
			</div>
		</li>
		<li>
			<div class="plugin-box">
				<div class="plugin-img">
					<img src="<?php echo esc_url( PPM_WP_URL . 'assets/images/activity-log-for-mainwp.jpg' ); ?>" alt="">
				</div>
				<div class="plugin-desc">
					<p><?php esc_html_e( 'See the activity logs of all child sites from one central place - the MainWP dashboard.', 'ppm-wp' ); ?></p>
					<div class="cta-btn">
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'utm_source'   => 'plugin',
									'utm_medium'   => 'referral',
									'utm_campaign' => 'AL4MWP',
									'utm_content'  => 'PPMWP+banner',
								),
								'https://wpactivitylog.com/extensions/mainwp-activity-log/'
							)
						);
						?>
						" target="_blank"><?php esc_html_e( 'LEARN MORE', 'ppm-wp' ); ?></a>
					</div>
				</div>
			</div>
		</li>
	</ul>
</div>
