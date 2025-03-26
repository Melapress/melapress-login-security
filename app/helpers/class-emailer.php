<?php
/**
 * Handle emails.
 *
 * @package MFM
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

/**
 * Utility file and directory functions.
 *
 * @since 2.0.0
 */
class Emailer {

	/**
	 * Filter the mail content type.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public static function set_html_content_type() {
		return 'text/html';
	}

	/**
	 * Send Email.
	 *
	 * @param string $email_address - Email Address.
	 * @param string $subject       - Email subject.
	 * @param string $content       - Email content.
	 * @param string $headers       Email headers.
	 * @param array  $attachments   Email attachments.
	 *
	 * @return bool
	 *
	 * self::send_email
	 */
	public static function send_email( $email_address, $subject, $content, $headers = '', $attachments = array() ) {

		if ( ! empty( $headers ) ) {
			$headers = array_merge_recursive( (array) $headers, array( 'Content-Type: ' . self::set_html_content_type() . '; charset=UTF-8' ) );
		} else {
			$headers = array( 'Content-Type: ' . self::set_html_content_type() . '; charset=UTF-8' );
		}

		// @see: http://codex.wordpress.org/Function_Reference/wp_mail
		\add_filter( 'wp_mail_from', array( __CLASS__, 'custom_wp_mail_from' ), PHP_INT_MAX );
		\add_filter( 'wp_mail_from_name', array( __CLASS__, 'custom_wp_mail_from_name' ) );

		$subject = apply_filters( 'mls_emailer_subject_filter', $subject );
		$content = apply_filters( 'mls_emailer_content_filter', wpautop( $content ) );

		if ( ! self::send_plain_email() ) {
			$result = \wp_mail( $email_address, $subject, self::wrap_email( $content ), $headers, $attachments );
		} else {
			$result = \wp_mail( $email_address, $subject, \wpautop( $content ), $headers, $attachments );
		}

		/**
		 * Reset content-type to avoid conflicts.
		 *
		 * @see http://core.trac.wordpress.org/ticket/23578
		 */
		\remove_filter( 'wp_mail_from', array( __CLASS__, 'custom_wp_mail_from' ), PHP_INT_MAX );
		\remove_filter( 'wp_mail_from_name', array( __CLASS__, 'custom_wp_mail_from_name' ) );

		return $result;
	}

	/**
	 * Should plugin send plain or HTML email.
	 *
	 * @return  bool - Should send plain or not.
	 */
	public static function send_plain_email() {
		$mls = melapress_login_security();
		return OptionsHelper::string_to_bool( $mls->options->mls_setting->send_plain_text_emails );
	}

	/**
	 * Wrap email in nice header and footer before output.
	 *
	 * @param   string $content  Content to wrap.
	 *
	 * @return  string           Wrapped content.
	 */
	public static function wrap_email( $content ) {
		$media['mls-logo']          = trailingslashit( MLS_PLUGIN_URL ) . 'assets/images/mls-email-header.png';
		$media['documentation']     = trailingslashit( MLS_PLUGIN_URL ) . 'assets/images/mails/daily-notification/documentation.png';
		$media['support']           = trailingslashit( MLS_PLUGIN_URL ) . 'assets/images/mails/daily-notification/support.png';
		$media['melapress-icon']    = trailingslashit( MLS_PLUGIN_URL ) . 'assets/images/mails/daily-notification/melapress-icon@2x.png';
		$media['wsal-dg-footer-bg'] = trailingslashit( MLS_PLUGIN_URL ) . 'assets/images/mails/daily-notification/wsal-dg-footer-bg.png';

		$header = '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
			<title>WP Activity Log</title>
		
			<!--[if mso]>
				<style>
				  body,table,td,h2,h3,span,p {
				  font-family: \'Quicksand\', \'Helvetica Neue\', Helvetica, Arial, \'Lucida Grande\', sans-serif !important;
				  }
				</style>
			<![endif]-->
		
			<link rel="preconnect" href="https://fonts.googleapis.com"/>
			<link rel="preconnect" href="https://fonts.gstatic.com"/>
			<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet"/>
		
			<style type="text/css">
				html, body {
					margin: 0 auto !important;
					padding: 0 !important;
					height: 100% !important;
					width: 100% !important;
				}
		
				/* Override blue links in footer */
				.footer-text a[x-apple-data-detectors] {
					color: #ffffff !important;
					font-size: inherit !important;
					font-family: inherit !important;
					font-weight: inherit !important;
					line-height: inherit !important;
				}
		
				u+#body .footer-text a {
					color: #ffffff !important;
					font-size: inherit !important;
					font-family: inherit !important;
					font-weight: inherit !important;
					line-height: inherit !important;
				}
		
				#MessageViewBody .footer-text a {
					color: #ffffff !important;
					font-size: inherit !important;
					font-family: inherit !important;
					font-weight: inherit !important;
					line-height: inherit !important;
				}
		
				table, td {
					border-spacing: 0;
					mso-table-lspace: 0;
					mso-table-rspace: 0;
				}
		
				img {
					border: 0;
					height: auto;
					line-height: 100%;
					outline: none;
					text-decoration: none;
					-ms-interpolation-mode: bicubic;
				}
		
				a {
					color: #0000EE;
					text-decoration: underline;
				}
		
				a:hover, a:hover img {
					opacity: 0.5;
					filter: alpha(opacity=50);
					transition: opacity .2s ease-in-out;
				}
		
				.applelink-white a {
					color: #ffffff !important;
				}
				
				/* Zebra striping for tables */
				table.zebra-striped tr:nth-child(even) {
				  background-color: #F0F4FE;
				}
				
				table.zebra-striped tr:nth-child(odd) {
				  background-color: #ffffff;
				}
		
				@media only screen and (max-width: 599px), only screen and (max-device-width: 599px) {
					.hide {
						display: none !important;
					}
		
					.responsive-full {
						width: 100% !important;
						min-width: 100% !important;
					}
		
					.responsive {
						width: 100% !important;
						min-width: 100% !important;
						padding-left: 30px !important;
						padding-right: 30px !important;
					}
		
					.inner-td {
						padding-bottom: 60px !important;
					}
		
					.responsive-image img {
						width: 50%% !important;
						min-width: 50%% !important;
					}
		
					.responsive-icon img {
						width: 48px !important;
						min-width: 48px !important;
					}
		
					.responsive-stack {
						width: 100% !important;
						display: block !important;
					}
		
					.mob-body-text {
						font-size: 20px !important;
						line-height: 28px !important;
					}
		
					.mob-title-text {
						font-size: 40px !important;
						line-height: 56px !important;
					}
		
					.center {
						text-align: center !important;
						padding-bottom: 15px !important;
					}
				}
			</style>
		</head>
		
		<body style="margin:0;padding:0;min-width:100%;background-color:#ffffff;font-family: \'Helvetica Neue\', Helvetica, Arial, \'Lucida Grande\', sans-serif; font-size:18px;line-height:24px;color:#1A3060;font-weight: 400;" id="body" class="body">
		<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="min-width: 100%;">
				<tbody><tr>
					<td align="center">
		';

		$wrapped                                  =
		'<table role="presentation" width="640" border="0" cellpadding="0" cellspacing="0" role="presentation" class="responsive">
			<tr>
			<td align="center">
				
				<!-- Logo Start -->
				<table role="presentation" width="640" border="0" cellpadding="0" cellspacing="0" role="presentation" class="responsive">
					<tr>
						<td style="padding: 0px;">
							<table role="presentation" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="min-width: 100%;">
								<tr>
									<td style="padding: 20px 0 15px;">
										<a href="https://melapress.com/wordpress-login-security/" style="color:#1A3060; font-weight: 700;" target="_blank">
											<img src="' . $media['mls-logo'] . '" border="0" width="400" height="80" style="display: block;" alt="WP Activity Log"/>
										</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<!-- Logo End -->
				
				<!-- Title Start -->
				<table role="presentation" width="640" border="0" cellpadding="0" cellspacing="0" role="presentation" class="responsive">
						<tr>
							<td align="center">
								<table width="100%" cellpadding="0" cellspacing="0" border="0">
									<tr>
										<td style="font-family: \'Helvetica Neue\', Helvetica, Arial, \'Lucida Grande\', sans-serif; font-weight: normal; font-size: 18px; line-height: 24px; color: #1A3060; text-align: left; padding-bottom: 20px;">';
										$wrapped .= $content;

		$wrapped .= '		
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>';

		$wrapped .= '</td></tr></tbody></table>
		<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#78262a" style="min-width: 100%; background-image: url(' . $media['wsal-dg-footer-bg'] . '); background-repeat: no-repeat; background-position: top center; background-position-y: -1px;">
				<tbody><tr>
					<td width="100%" align="center" style="padding: 105px 0 16px 0;">
						<a href="https://melapress.com" target="_blank"><img src="' . $media['melapress-icon'] . '" width="42" height="42" border="0" style="display: block;" alt="Melapress"></a>
					</td>
				</tr>
				<tr>
					<td align="center" style="font-family: \'Helvetica Neue\', Helvetica, Arial, \'Lucida Grande\', sans-serif; font-size: 16px; line-height: 22px; color: #ffffff; padding-top: 25px;" class="footer-text">
						If you\'re finding Melapress Login Security helpful, consider trying our other plugins:<br> <a href="https://melapress.com/wordpress-2fa/?utm_source=wpal_email&amp;utm_medium=email&amp;utm_campaign=product_email&amp;utm_content=cta_footer" target="_blank" style="color:#ffffff;">WP 2FA</a> and <a href="https://melapress.com/wordpress-login-security/?utm_source=wpal_email&amp;utm_medium=email&amp;utm_campaign=product_email&amp;utm_content=cta_footer" target="_blank" style="color:#ffffff;">WP Activity Log</a>.
					</td>
				</tr>
				<tr>
					<td width="100%" align="center" style="padding: 16px 0 16px;">
						<table role="presentation" align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
							<tbody><tr>
								<td align="center" style="font-family: \'Helvetica Neue\', Helvetica, Arial, \'Lucida Grande\', sans-serif; font-size: 13px; line-height: 22px; color: #E5EFB0; padding-top: 25px;" class="footer-text">
									This email was sent by Melapress Login Security - boost WordPress security with login & password policies<br><br>
									<a href="https://melapress.com/wordpress-activity-log/?utm_source=wpal_email&amp;utm_medium=email&amp;utm_campaign=product_email&amp;utm_content=cta_footer_byline" target="_blank" style="color: #E5EFB0;">Melapress Login Security</a> is developed and maintained by <a href="https://melapress.com/?utm_source=wpal_email&amp;utm_medium=email&amp;utm_campaign=product_email&amp;utm_content=cta_footer_byline" target="_blank" style="color: #E5EFB0;">Melapress</a>.<br><span style="white-space: nowrap;">Melapress Blaak 520 Rotterdam,</span> <span style="white-space: nowrap;">Zuid-Holland 3011 TA Netherlands</span>
								</td>
							</tr>
						</tbody></table>
					</td>
				</tr>
			</tbody></table>';

		$footer = '</body></html>';

		return $header . $wrapped . $footer;
	}

	/**
	 * Return if there is a from-email in the setting or the original passed.
	 *
	 * @param string $original_email_from – Original passed.
	 *
	 * @return string
	 *
	 * @since 2.1.0
	 */
	public static function custom_wp_mail_from( $original_email_from ) {
		$mls        = melapress_login_security();
		$use_email  = $mls->options->mls_setting->use_custom_from_email;
		$email_from = $mls->options->mls_setting->from_email ? $mls->options->mls_setting->from_email : 'mls@' . str_ireplace( 'www.', '', wp_parse_url( network_site_url(), PHP_URL_HOST ) );
		if ( ! empty( $email_from ) && 'custom_email' === $use_email ) {
			return $email_from;
		} else {
			return $original_email_from;
		}
	}

	/**
	 * Return if there is a display-name in the setting or the original passed.
	 *
	 * @param string $original_email_from_name – Original passed.
	 *
	 * @return string
	 *
	 * @since 2.1.0
	 */
	public static function custom_wp_mail_from_name( $original_email_from_name ) {
		$mls             = melapress_login_security();
		$use_email       = $mls->options->mls_setting->use_custom_from_email;
		$email_from_name = $mls->options->mls_setting->from_display_name;
		if ( ! empty( $email_from_name ) && 'custom_email' === $use_email ) {
			return $email_from_name;
		} else {
			if ( ! empty( self::get_default_email_address() ) ) {
				return self::get_default_email_address();
			}

			return $original_email_from_name;
		}
	}

	/**
	 * Builds and returns the default email address used for the "from" email address when email is send
	 *
	 * @return string
	 *
	 * @since 2.1.0
	 */
	public static function get_default_email_address(): string {
		$sitename = \wp_parse_url( \network_home_url(), PHP_URL_HOST );

		$from_email = '';

		if ( null !== $sitename ) {
			$from_email = 'mls@';
			if ( \str_starts_with( $sitename, 'www.' ) ) {
				$sitename = substr( $sitename, 4 );
			}

			$from_email .= $sitename;
		}

		return $from_email;
	}
}
