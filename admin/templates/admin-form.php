<?php
/**
 * Handles policies admin area.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

// Get wp all roles.
global $wp_roles;
$roles = $wp_roles->get_names();

// current tab.
$current_tab         = isset( $_REQUEST['role'] ) && in_array( wp_unslash( $_REQUEST['role'] ), array_keys( $roles ), true ) ? sanitize_text_field( wp_unslash( $_REQUEST['role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$master_switch_title = ! empty( $current_tab ) ? __( 'Inherit login security policies', 'melapress-login-security' ) : __( 'Enable login security policies', 'melapress-login-security' );
$sidebar_required    = false;

// @free:start

// Override in free edition.
$sidebar_required = true;
// @free:end

$form_class = ( $sidebar_required ) ? 'sidebar-present' : 'sidebar-present';
?>
<div class="wrap ppm-wrap">

	<div class="page-head">
		<h2><?php esc_html_e( 'Login Security Policies', 'melapress-login-security' ); ?></h2>

		<div class="action mls-reset-all-wrapper">
			<?php
			if ( 0 === self::get_global_reset_timestamp() ) {
				$reset_string = __( 'Reset All Passwords was never used', 'melapress-login-security' );
			} else {
				$reset_string = __( 'Last reset was on', 'melapress-login-security' ) . ' ' . get_date_from_gmt( date( 'Y-m-d H:i:s', (int) self::get_global_reset_timestamp() ), get_site_option( 'date_format', get_option( 'date_format' ) ) . ' ' . get_site_option( 'time_format', get_option( 'time_format' ) ) ); // phpcs:ignore.
			}
			?>
			<span class="mls-last-global-reset-time"><?php echo esc_html( $reset_string ); ?></span>

			<div id="reset-container">
				<input id="_mls_global_reset_button" type="submit"
						name="_mls_global_reset_button"
						class="button-secondary"
						value="<?php esc_attr_e( "Reset All Users' Passwords", 'melapress-login-security' ); ?>"/>
				<p class="description"></p>
			</div>
		</div>
	</div>

	<form method="post" id="ppm-wp-settings" class="<?php echo esc_attr( $form_class ); ?>">
		<input type="hidden" id="ppm-exempted-role" value="<?php echo $current_tab ? esc_attr( $current_tab ) : ''; ?>" name="mls_options[ppm-user-role]">

		<p class="short-message"><?php esc_html_e( 'The password policies configured in the All tab apply to all roles. To override the default policies and configure policies for a specific role disable the option Inherit policies in the role\'s tab.', 'melapress-login-security' ); ?></p>

		<div class="nav-tab-wrapper">
			<a href="<?php echo esc_url( add_query_arg( 'page', 'mls-policies', network_admin_url( 'admin.php' ) ) ); ?>" class="nav-tab<?php echo empty( $current_tab ) && ! isset( $_REQUEST['tab'] ) ? ' nav-tab-active' : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>"><?php esc_html_e( 'Site-wide policies', 'melapress-login-security' ); ?></a>
			<div id="ppmwp-role_tab_link_wrapper">
				<div id="ppmwp_links-inner-wrapper">
					<?php
					$title_active = isset( $roles[ $current_tab ] ) ? 'nav-tab-active' : '';
					if ( ! isset( $roles[ $current_tab ] ) ) {
						?>
						<span class="nav-tab <?php echo esc_attr( $title_active ); ?> dummy"><span style="opacity: 0.2" class="dashicons dashicons-admin-settings"></span><?php esc_html_e( 'Role-based policies', 'melapress-login-security' ); ?></span>
						<?php
					}
					if ( isset( $roles[ $current_tab ] ) ) {
						$first_item = array(
							$current_tab => $roles[ $current_tab ],
						);
						unset( $roles[ $current_tab ] );

						$roles = $first_item + $roles;
					}

					foreach ( $roles as $key => $value ) {
						$url = add_query_arg(
							array(
								'page' => 'mls-policies',
								'role' => $key,
							),
							network_admin_url( 'admin.php' )
						);
						// Active tab.
						$active       = ( $current_tab === $key ) ? ' nav-tab-active' : '';
						$settings_tab = get_site_option( MLS_PREFIX . '_' . $key . '_options' );
						$icon         = empty( $settings_tab ) || 1 === $settings_tab['master_switch'] ? '<span style="opacity: 0.2" class="dashicons dashicons-admin-settings"></span> ' : '<span class="dashicons dashicons-admin-settings"></span> ';
						?>
						<a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo esc_attr( $active ); ?>" id="<?php echo esc_attr( $key ); ?>"><?php echo wp_kses( $icon . $value, OptionsHelper::get_allowed_kses_args() ); ?></a>
						<?php
					}
					?>
				</div>
				<span class="dashicons dashicons-arrow-down"></span>
			</div>

		</div>
		<?php if ( ! isset( $_REQUEST['tab'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div>
			<table class="form-table" data-id="<?php echo esc_attr( $current_tab ); ?>">
				<tbody>
				<?php if ( ! empty( $current_tab ) ) : ?>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e( 'Do not enforce password & login policies for this role', 'melapress-login-security' ); ?>
						</th>
						<td>
							<fieldset>
								<label for="ppm_enforce_password">
									<input type="checkbox" id="ppm_enforce_password" name="mls_options[enforce_password]"
											value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( self::$setting_tab->enforce_password ) ); ?>>
								</label>
							</fieldset>
						</td>
					</tr>
					<?php endif; ?>
					<tr valign="top" class="master-switch">
						<th scope="row">
							<?php echo esc_html( $master_switch_title ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e( 'Password Length', 'melapress-login-security' ); ?>
									</span>
								</legend>
								<label for="ppm-min-length">
									<?php
									if ( isset( $_GET['role'] ) && in_array( wp_unslash( $_GET['role'] ), array_keys( $roles ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
										$master_key = self::$setting_tab->inherit_policies;
									} else {
										$master_key = self::$setting_tab->master_switch;
									}
									?>
									<input type="checkbox" id="ppm_master_switch" name="mls_options[master_switch]"
											value="1" <?php checked( \MLS\Helpers\OptionsHelper::string_to_bool( $master_key ) ); ?>>
									<?php if ( isset( $_GET['role'] ) && in_array( wp_unslash( $_GET['role'] ), array_keys( $roles ), true ) ) :  // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
									<input type="hidden" name="mls_options[inherit_policies]" value="<?php echo esc_attr( self::$setting_tab->inherit_policies ); ?>" id="inherit_policies">
									<?php endif; ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
		<div class="clear">&nbsp;</div>

		<?php wp_nonce_field( MLS_PREFIX . '_nonce_form', MLS_PREFIX . '_nonce' ); ?>
		<div class="mls-settings">
			<table class="form-table">
				<tbody>
					<?php require_once MLS_PATH . 'admin/templates/form-table.php'; ?>
				</tbody>
			</table>
		</div>
		<?php
		// we DON'T want this submit button on the inactive users page.
		if ( ! isset( $_REQUEST['tab'] ) || ( isset( $_REQUEST['tab'] ) && 'inactive-users' !== $_REQUEST['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<p class="submit">
				<input type="submit" name="_ppm_save" class="button-primary"
					value="<?php echo esc_attr( __( 'Save Changes', 'melapress-login-security' ) ); ?>" />
			</p>
			<?php
		}
		?>
	</form>

	<?php
	global $wp_roles;
	$roles = $wp_roles->get_names();
	?>

	<div class="mls-modal-main-wrapper" id="reset-all-modal">
		<div class="mls-modal-content">
			<div class="mls-modal-content-wrapper">
				<h3><?php esc_attr_e( 'Which users would you like to reset?', 'melapress-login-security' ); ?></h3>
				<p class="description"><?php esc_attr_e( 'Here you can choose if you want to reset the passwords for ALL users or just a specific sub set of users based on your desired criteria. Simply choose from the available options below and hit proceed when ready.', 'melapress-login-security' ); ?></p>
				<br>

				<fieldset>
					<p class="description" style="display: inline-block; min-width: 150px; position: relative; top: -3px;"><?php esc_attr_e( 'Choose user group: ', 'melapress-login-security' ); ?></p>
					<span style="display: inline-table; margin-left: 10px">
						<input type="radio" id="reset-all" name="reset_type" value="reset-all" checked>
						<label for="reset-all" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Reset all users', 'melapress-login-security' ); ?></label><br>

						<input type="radio" id="reset-role" name="reset_type" value="reset-role" data-active-shows-setting=".reset-role-panel">
						<label for="reset-role" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Reset by role', 'melapress-login-security' ); ?> </label><br>

						<div class="reset-role-panel hidden">
							<select id="reset-role-select">
								<?php
								foreach ( $roles as $key => $value ) {
									if ( 'subscriber' === strtolower( $value ) ) {
										echo '<option selected value="' . esc_attr( strtolower( $value ) ) . '">' . esc_attr( $value ) . '</option>';
									} else {

										echo '<option value="' . esc_attr( strtolower( $value ) ) . '">' . esc_attr( $value ) . '</option>';
									}
								}
								?>
							</select>
							<br>
							<br>
						</div>

						<input type="radio" id="reset-users" name="reset_type" value="reset-users" data-active-shows-setting=".reset-users-panel">
						<label for="reset-users" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Reset specific users', 'melapress-login-security' ); ?> </label><br>
						
						<div class="reset-users-panel hidden">
							<fieldset>
								<input type="text" id="ppm-exempted" class="reset-user-search" style="float: left; display: block; width: 250px;">
								<input type="hidden" id="ppm-exempted-users" name="mls_options[exempted][users]" value="<?php echo ! empty( self::$options->mls_setting->exempted['users'] ) ? esc_attr( htmlentities( wp_json_encode( self::$options->mls_setting->exempted['users'] ), ENT_QUOTES, 'UTF-8' ) ) : ''; ?>">
								<p class="description" style="clear:both;">
									<?php
									esc_html_e( 'Users in this list will reset.', 'melapress-login-security' );
									?>
								</p>
								<ul id="ppm-exempted-list" class="reset-user-list"></ul>
							</fieldset>
						</div>

						<input type="radio" id="reset-csv" name="reset_type" value="reset-csv" data-active-shows-setting=".reset-users-file">
						<label for="reset-csv" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Upload CSV of User IDs (.csv or .txt only)', 'melapress-login-security' ); ?> </label><br>
						
						<div class="reset-users-file hidden">
							<input type="file" id="users-reset-file" name="filename"><br>
						</div>
					</span>
				</fieldset>

				<br>
				<fieldset>
				<p class="description" style="display: inline-block; min-width: 150px; position: relative; top: -3px;"><?php esc_attr_e( 'Select reset processing: ', 'melapress-login-security' ); ?></p>
					<span style="display: inline-table; margin-left: 10px; max-width: 70%;">
						<input type="radio" id="reset-now" name="reset_when" value="reset-now" data-toggle-other-areas=".reset-now-blurb" checked>
						<label for="reset-now" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Reset passwords immediately', 'melapress-login-security' ); ?></label><br>
							<p class="reset-now-blurb" style="font-size: 12px;"><?php esc_attr_e( 'All passwords will reset right away and users will not be able to login untill a new password has been created.', 'melapress-login-security' ); ?></p>
						<input type="radio" id="reset-login" name="reset_when" value="reset-login" data-toggle-other-areas=".reset-later-blurb">
						<label for="reset-login" style="margin-bottom: 10px; display: inline-grid; margin-top: 6px; font-size: 12px;"><?php esc_attr_e( 'Reset passwords on next login', 'melapress-login-security' ); ?> </label><br>
						<p class="reset-later-blurb" style="font-size: 12px;"><?php esc_attr_e( 'Users will be able to login with their existing passwords, however they will be required to change there password upon login.', 'melapress-login-security' ); ?></p>
					</span>
				</fieldset>

				<br>
				<fieldset>
					<p class="description" style="display: inline-block; min-width: 150px; position: relative; top: -3px;"><?php esc_attr_e( 'Additional options: ', 'melapress-login-security' ); ?></p>
					<span style="display: inline-table; margin-left: 10px; max-width: 70%;">
						<input type="checkbox" id="send_reset_email" name="send_email" value="send-email" checked>
						<label for="send_reset_email"><?php esc_attr_e( 'Send email to users when resetting.', 'melapress-login-security' ); ?></label><br>
						<p class="reset-now-blurb" style="font-size: 12px;"><?php esc_attr_e( 'Check this will send an email notification to each user either explaining that the password has been reset, or thay they will need to reset when they next login, depending on your chosen \'reset processing\' selection.', 'melapress-login-security' ); ?></p>

						<input type="checkbox" id="terminate_sessions_on_reset" name="reset_self" value="reset-self" checked>
						<label for="terminate_sessions_on_reset"><?php esc_attr_e( 'Terminate sessions for reset users', 'melapress-login-security' ); ?></label><br>
						<p class="reset-now-blurb" style="font-size: 12px;"><?php esc_attr_e( 'Any currently logged in users will have their sessions terminated right away.', 'melapress-login-security' ); ?></p>

						<input type="checkbox" id="include_reset_self" name="reset_self" value="reset-self">
						<label for="include_reset_self"><?php esc_attr_e( 'Include yourself in password reset', 'melapress-login-security' ); ?></label><br>

					</span>
				</fieldset>

				<br>
				<fieldset>
				
				</fieldset>

				<br>
				<fieldset>
				</fieldset>
				
				
				<br>	
			</div>
			<div>
				<a href="#modal-cancel" data-modal-close-target="#reset-all-modal" class="button button-secondary"><?php esc_attr_e( 'Cancel', 'melapress-login-security' ); ?></a>  <a href="#modal-proceed" data-reset-nonce="<?php echo esc_attr( wp_create_nonce( 'mls_mass_reset' ) ); ?>" class="button button-primary"><?php esc_attr_e( 'Proceed', 'melapress-login-security' ); ?></a> 
			</div>
		</div>
	</div>

	<?php
	// @free:start
	require_once MLS_PATH . 'admin/templates/views/upgrade-sidebar.php';
	// @free:end

	?>
</div>
