<?php
/**
 * Page partial template that displays the inactive-users list view.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// list table should be defined by here but just incase check first.
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
require_once MLS_PATH . 'app/modules/failed-logins/InactiveUsersTable.php';

$sidebar_required = false;
$master_policy    = \MLS\Helpers\OptionsHelper::get_master_policy_options();

/* @free:start */

// Override in free edition.
$sidebar_required = true;
/* @free:end */
$form_class = ( $sidebar_required ) ? 'sidebar-present' : '';
?>
<div id="inactive_users_page" class="<?php echo esc_attr( $form_class ); ?>">
	<?php
	// display the table view + message if the feature is enabled, otherwise
	// show an error message to tell user what is required to turn this on.
	$inactive_feature_enabled = \MLS\Helpers\OptionsHelper::should_inactive_users_feature_be_active( true );
	if ( isset( $master_policy->failed_login_policies_enabled ) && \MLS\Helpers\OptionsHelper::string_to_bool( $master_policy->failed_login_policies_enabled ) ) {
		$inactive_feature_enabled = true;
	}
	if ( ! class_exists( '\MLS\InactiveUsers' ) ) {
		?>
		<p>
			<?php
			printf(
				/* translators: %s: link to policies. */
				esc_html__( 'In this section you can see a list of locked users. Users can be locked if they have had too many failed login attempts. More information on the %1$s', 'melapress-login-security' ),
				sprintf(
					'<a target="_blank" href="https://melapress.com/support/kb/melapress-login-security-inactive-users-policy-wordpress/?utm_source=plugins&utm_medium=link&utm_campaign=mls">%s</a>',
					esc_html__( 'Failed logins policy', 'melapress-login-security' )
				)
			);
			?>
		</p>
		<form method="post">
			<?php
			$table = new \MLS\Views\Tables\InactiveUsersTable();
			$table->display();
			?>
		</form>
		<?php
	} elseif ( $inactive_feature_enabled ) {
		?>
		<p>
			<?php
			printf(
				/* translators: %s: link to policies. */
				esc_html__( 'In this section you can see a list of locked users. Users can be locked if they have been inactive for a long time, or they have had too many failed login attempts. More information on the %1$s', 'melapress-login-security' ),
				sprintf(
					'<a target="_blank" href="https://melapress.com/support/kb/melapress-login-security-inactive-users-policy-wordpress/?utm_source=plugins&utm_medium=link&utm_campaign=mls">%s</a>',
					esc_html__( 'Inactive users policy', 'melapress-login-security' )
				)
			);
			?>
		</p>
		<form method="post">
			<?php
			$table = new \MLS\Views\Tables\InactiveUsersTable();
			$table->display();
			?>
		</form>
		<?php
	} else {
		?>
		<p>
			<?php
			printf(
				/* translators: %s: link to policies. */
				esc_html__( 'In this section you can see a list of inactive WordPress users on your website if you enable the %1$s, or the %2$s', 'melapress-login-security' ),
				sprintf(
					'<a target="_blank" rel="nofollow" href="https://melapress.com/support/kb/melapress-login-security-inactive-users-policy-wordpress/?utm_source=plugins&utm_medium=link&utm_campaign=mls">%s</a>',
					esc_html__( 'Inactive users policy', 'melapress-login-security' )
				),
				sprintf(
					'<a target="_blank" rel="nofollow" href="https://melapress.com/support/kb/melapress-login-security-failed-logins-policy-wordpress/?utm_source=plugins&utm_medium=link&utm_campaign=mls">%s</a>',
					esc_html__( 'block failed logins policy', 'melapress-login-security' )
				),
			);
			?>
		</p>
		<?php
	}
	?>
</div>

<?php
	/* @free:start */
	require_once MLS_PATH . 'admin/templates/views/upgrade-sidebar.php';
	/* @free:end */

?>
