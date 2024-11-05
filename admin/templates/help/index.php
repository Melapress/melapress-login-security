<?php
/**
 * Help content.
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

<div class="wrap help-wrap">
	<div class="page-head">
		<h2><?php esc_html_e( 'Help', 'melapress-login-security' ); ?></h2>
	</div>
	<div class="nav-tab-wrapper">
		<?php
			$possible_tabs = array(
				'help',
				'contact-us',
				'system-info',
			);
			// Get current tab.
			$current_tab   = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'help'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
		<a href="<?php echo esc_url( remove_query_arg( 'tab' ) ); ?>" class="nav-tab<?php echo 'help' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Help', 'melapress-login-security' ); ?></a>
		<?php
		?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'system-info' ) ); ?>" class="nav-tab<?php echo 'system-info' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'System Info', 'melapress-login-security' ); ?></a>
	</div>
	<div class="mls-help-section nav-tabs">
		<?php
		if ( in_array( $current_tab, $possible_tabs, true ) ) {
			// Require page content. Default help.php.
			require_once $current_tab . '.php';
		}
		?>
	</div>
</div>
