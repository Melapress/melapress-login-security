<?php
	// Plugin adverts sidebar
	require_once 'sidebar.php';
?>
<div class="ppm-help-main">
	<!-- getting started -->
	<div class="title">
		<h2><?php _e( 'Getting Started', 'ppm-wp' ); ?></h2>
	</div>
	<p><?php _e( 'It is easy to get started with the WPassword. Simply enable and configure the password policies you want to enforce. Below are a few links of guides to help you get started:', 'ppm-wpp' ); ?></p>
	<ul>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://www.wpwhitesecurity.com/support/kb/getting-started-wpassword/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ), __( 'Getting started with the WPassword', 'ppm-wp' ) ); ?></li>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://www.wpwhitesecurity.com/support/kb/configure-different-password-policies-wordpress-user-roles/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ), __( 'Configure different password policies for different user roles', 'ppm-wp' ) ); ?></li>
		<li><?php echo wp_sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://www.wpwhitesecurity.com/support/kb/exclude-user-roles-wordpress-password-policies/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ), __( 'How to exclude users or roles from the password policies', 'ppm-wp' ) ); ?></li>
	</ul>
	<!-- End -->
	<br>
	<p><iframe title="<?php _e( 'Getting Started', 'ppm-wp' ); ?>" class="wsal-youtube-embed" width="100%" height="315" src="https://www.youtube.com/embed/gXaMw4D_yo8" frameborder="0" allowfullscreen></iframe></p>

	<!-- Plugin documentation -->
	<div class="title">
		<h2><?php _e( 'Plugin Documentation', 'ppm-wp' ); ?></h2>
	</div>
	<p><?php _e( 'For more technical information about the WPassword plugin please visit the plugin’s knowledge base.', 'ppm-wp' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/support/kb/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ); ?>" class="button" target="_blank"><?php _e( 'Knowledge Base', 'ppm-wp' ); ?></a>
	</div>
	<!-- End -->

	<!-- Plugin support -->
	<div class="title">
		<h2><?php _e( 'Plugin Support', 'ppm-wp' ); ?></h2>
	</div>
	<p><?php _e( 'Have you encountered or noticed any issues while using the WPassword plugin? Or do you want to report something to us?', 'ppm-wp' ); ?></p>
	<div class="btn">
		<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/support/submit-ticket/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ); ?>" class="button" target="_blank"><?php _e( 'Open support ticket', 'ppm-wp' ); ?></a>
		<a href="<?php echo esc_url( 'https://www.wpwhitesecurity.com/contact-wp-white-security/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' ); ?>" class="button" target="_blank"><?php _e( 'Contact Us', 'ppm-wp' ); ?></a>
	</div>
	<!-- End -->
</div>
