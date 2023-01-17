<div class="wrap help-wrap">
	<div class="page-head">
		<h2><?php _e( 'Help', 'ppm-wp' ); ?></h2>
	</div>
	<div class="nav-tab-wrapper">
		<?php
			// Get current tab
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'help';
		?>
		<a href="<?php echo esc_url( remove_query_arg( 'tab' ) ); ?>" class="nav-tab<?php echo 'help' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Help', 'ppm-wp' ); ?></a>
		<?php
		?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'contact-us' ) ); ?>" class="nav-tab<?php echo 'contact-us' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Contact Us', 'ppm-wp' ); ?></a>
		<?php
		?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'system-info' ) ); ?>" class="nav-tab<?php echo 'system-info' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'System Info', 'ppm-wp' ); ?></a>
	</div>
	<div class="ppm-help-section nav-tabs">
		<?php
			// Require page content. Default help.php
			require_once $current_tab . '.php';
		?>
	</div>
</div>