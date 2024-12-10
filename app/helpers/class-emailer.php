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

		$result = \wp_mail( $email_address, $subject, $content, $headers, $attachments );

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

	/* @free:start */
	/**
	 * Append footer to email content.
	 *
	 * @param string $content - Existing content.
	 *
	 * @return string - appended content.
	 *
	 * @since 2.0.0
	 */
	public static function append_email_footer( $content ) {
		/* translators: %s: By email address. */
		$footer  = wp_sprintf( __( 'This email was sent by %s - boost WordPress security with login & password policies.', 'melapress-login-security' ), '<a href="https://melapress.com/wordpress-login-security/?utm_source=plugins&utm_medium=email&utm_campaign=mls" target="_blank">' . __( 'Melapress Login Security', 'melapress-login-security' ) . '</a>' );
		$content = $content . $footer;
		return $content;
	}
	/* @free:end */
}
