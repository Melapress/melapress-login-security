<?php
/**
 * Update page content.
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

<div class="wrap features-wrap">
	<div class="c4wp-upgrade-section">
		
		<div class="content-block">
			<div class="logo-wrap">
				<img src="<?php echo esc_url( MLS_PLUGIN_URL . 'assets/images/password-policy-manager.png' ); ?>" alt="">
			</div>
			<p><?php esc_html_e( 'The security of your WordPress website & WooCommerce store is as strong as the weakest password!', 'melapress-login-security' ); ?></p>
			<p><?php esc_html_e( 'Weak passwords should not jeopardize the security of your website. Configure strong password policies with Melapress Login Security and ensure your team, customers & subscribers use strong passwords.', 'melapress-login-security' ); ?></p>
			<div class="premium-cta">
			<a href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugins&utm_medium=mls&utm_campaign=premium_features_page_1" target="_blank" rel="noopener">Upgrade to Premium</a>
			</div>
		</div>

		<div class="content-block">
			<table class="c21 feature-table">
				<tbody>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10 c4"><span class="c5"><?php esc_html_e( 'Feature', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8 row-head" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><?php esc_html_e( 'Premium', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c12 row-head" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><?php esc_html_e( 'Enterprise', 'melapress-login-security' ); ?></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Everything in Free', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Enforce policies on WooCommerce and third-party plugin forms', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Limit login page traffic by country (geoblocking)', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Automatically lock inactive users', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Alerts for logins from unrecognized devices', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Security questions', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Session policies (default and remember-me session expiry)', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Users login activity and passwords reports', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Bulk user import via CSV', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Require current password to change password', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Alerts when a second session starts on the same account', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Let users view and remove their own recognised devices', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Security question required to change email address', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Set how long a device stays recognised', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Restrict user login times', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-no"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Restrict the IP addresses users can log in from', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-no"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
					<tr class="c2">
						<td class="c6" colspan="1" rowspan="1">
							<p class="c10"><span class="c5"><?php esc_html_e( 'Editable email templates', 'melapress-login-security' ); ?></span></p>
						</td>
						<td class="c8" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-no"></span></span></p>
						</td>
						<td class="c12" colspan="1" rowspan="1">
							<p class="c7"><span class="c5"><span class="dashicons dashicons-saved"></span></span></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="premium-cta">
			<a href="https://melapress.com/wordpress-login-security/pricing/?utm_source=plugins&utm_medium=mls&utm_campaign=premium_features_page_2" target="_blank" rel="noopener"><?php esc_html_e( 'Upgrade to Premium', 'melapress-login-security' ); ?></a>
		</div>

	</div>
</div>

<style type="text/css">
	#postbox-container-1 {
		display: none;
	}
	.features-wrap {
		background: #fff;
		padding: 25px 30px;
		max-width: 880px;
	}

	.features-wrap h2 {
		font-size: 28px;
			margin-bottom: 30px;
	}

	.features-wrap p {
		font-size: 14px;
		line-height: 28px;
		font-weight: normal;
	}

	.feature-list {
		margin-bottom: 20px;
	}

	.feature-list li {
		margin-bottom: 10px;
		font-size: 15px;
	}

	.feature-list li .dashicons {
		color: #dd2b10;
	}

	.premium-cta {
		margin: 25px 0 15px;
		text-align: center;
	}

	.premium-cta .text-link {
		color: #dd2b10;
		background: transparent;
		border: #dd2b10;
		text-decoration: dashed;
	}

	/*
	 * Melapress red, with the darker red from the same logo on hover, and the
	 * plain rectangular shape a WordPress plugin notice button has rather than
	 * the pill this used to be.
	 */
	.premium-cta a, .table-link {
		background-color: #dd2b10;
		color: #fff;
		padding: 8px 12px;
		border-radius: 5px;
		font-size: 14px;
		line-height: 1;
		white-space: nowrap;
		text-decoration: none;
		display: inline-block;
		margin-right: 15px;
		border: none;
	}

	.premium-cta a:hover, .premium-cta a:focus, .table-link:hover, .table-link:focus {
		background-color: #7a262a;
		color: #fff;
	}

	.premium-cta a.inverse, .table-link.inverse {
		background-color: #fff;
		color: #dd2b10;
		border: 1px solid #dd2b10;
	}

	.premium-cta a.inverse:hover, .premium-cta a.inverse:focus {
		color: #fff;
		background-color: #7a262a;
		border-color: #7a262a;
	}

	.content-block {
		margin-bottom: 26px;
		border-bottom: 1px solid #eee;
		padding-bottom: 15px;
		overflow: hidden;
	}

	.feature-table strong {
		font-size: 16px;
		clear: both;
		display: block;
	}
	
	.feature-table tr td {
		text-align: center;
		min-width: 200px
	}
	.feature-table tr td:first-of-type {
		text-align: left;
		font-weight: 500;
	}
	.feature-table td p {
		margin-top: 0;
	}
	.row-head span {
		font-size: 17px;
		font-weight: 700;
	}
	.feature-table .dashicons {
		color: #50284E;
	}
	.feature-table .dashicons-no {
		color: red;
	}
	.table-link {
		font-size: 14px;
		padding: 9px;
		width: 193px;
		margin-top: 10px;
	}
	.pull-up {
		position: relative;
		top: -23px;
	}

	.logo-wrap img {
		max-width: 230px;
		margin-top: 20px;
	}

	.logo-wrap {
		float: left;
		margin-right: 30px;
	}

</style>
