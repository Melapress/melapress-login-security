<?php
/**
 * WP Pointer class for new installs.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\MLS\Pointer' ) ) {

	/**
	 * Provides pointer popup after installing the plugin
	 *
	 * @since 2.0.0
	 */
	class Pointer {

		/**
		 * Constructor.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'init_pointers' ) );
		}

		/**
		 * Init our pointers.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function init_pointers() {
			$pointers = array(
				array(
					'id'       => 'password_policy_manager_after_install',
					'screen'   => 'plugins',
					'target'   => '#toplevel_page_mls-policies',
					'title'    => __( 'Configure the password policies', 'melapress-login-security' ),
					'content'  => wp_sprintf( '%s <a href="%s" class="ppm-pointer-close" style="text-decoration: none;">%s</a> %s', __( 'By default the password policies are disabled.', 'melapress-login-security' ), esc_url( add_query_arg( 'page', 'mls-policies', network_admin_url( 'admin.php' ) ) ), __( 'Click here', 'melapress-login-security' ), __( 'to configure the policies in the site\'s settings.', 'melapress-login-security' ) ),
					'position' => array(
						'edge'  => 'left', // top, bottom, left, right.
						'align' => 'right', // top, bottom, left, right, middle.
					),
				),
			);
			new \MLS\WP_Admin_Pointer( $pointers );
		}
	}
	new \MLS\Pointer();
}
