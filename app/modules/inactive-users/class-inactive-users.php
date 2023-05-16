<?php
/**
 * Bootstrapper for the Inactive Users feature.
 *
 * @since 2.1.0
 *
 * @package WordPress
 */

namespace PPMWP;

use \PPMWP\Helpers\OptionsHelper;

/**
 * This is the main class for handling all the Inactive Users features.
 *
 * @since 2.1.0
 */
class InactiveUsers {

	/**
	 * A period of time in seconds which indicates dormancy.
	 *
	 * NOTE: should pass through the `ppmwp_adjust_dormancy_period` filter when
	 * used to allow it being changed.
	 *
	 * @var int
	 */
	const DORMANCY_PERIOD = MONTH_IN_SECONDS;

	/**
	 * User meta key for when user is flagged as inactive.
	 *
	 * @var string
	 */
	const DORMANT_USER_FLAG_KEY = 'is_inactive_user';

	/**
	 * User meta key for the time user was set to inactive.
	 *
	 * @var string
	 */
	const DORMANT_SET_TIME = 'inactive_set_time';

	/**
	 * An instance of this plugins main class.
	 *
	 * @var PPM_WP
	 */
	public $ppm;

	/**
	 * Should hold a flag for if this feature is active or not.
	 *
	 * @var bool
	 */
	private $feature_enabled = null;

	/**
	 * Should hold roles that are already deemed to be exempt to prevent need
	 * to check the same role several times in a run.
	 *
	 * @var array
	 */
	private $exempt_roles = array();

	/**
	 * Sets up the properties for the inactive users feature.
	 *
	 * @method __construct
	 * @since  2.1.0
	 * @param  PPM_WP $ppm An instance of the main plugin class.
	 */
	public function __construct( $ppm ) {
		$this->ppm = $ppm;
		// should inactive feature be active?
		$this->feature_enabled = OptionsHelper::should_inactive_users_feature_be_active();

		$this->menu_name = 'ppm_wp_settings';

		// Detect user login/log out.
		add_action( 'wp_login', array( $this, 'ppm_detect_user_login' ), 10, 1 );
		add_action( 'clear_auth_cookie', array( $this, 'ppm_detect_user_login' ), 10, 1 );
	}

	/**
	 * Add admin page.
	 *
	 * @return void
	 */
	public function admin_menu() {
		// Add admin submenu page for settings.
		$locked_users_hook_submenu = add_submenu_page(
			$this->menu_name,
			__( 'Locked Users', 'ppm-wp' ),
			__( 'Locked Users', 'ppm-wp' ),
			'manage_options',
			'ppm-locked-users',
			array(
				$this,
				'ppm_display_locked_users_page',
			),
			2
		);

		add_action( "load-$locked_users_hook_submenu", array( '\PPMWP\Admin\PPM_WP_Admin', 'admin_enqueue_scripts' ) );
	}


	/**
	 * Display settings page.
	 */
	public function ppm_display_locked_users_page() {
		require_once PPM_WP_PATH . 'app/modules/inactive-users/locked-users-view.php';
	}

	/**
	 * Sets the feature enabled flag.
	 *
	 * NOTE: can be either true or false;
	 *
	 * @method set_feature_enabled
	 * @since  2.1.0
	 * @param  bool $enabled flag if the feature is enabled or not.
	 */
	public function set_feature_enabled( $enabled ) {
		$this->feature_enabled = (bool) $enabled;
	}

	/**
	 * Returns if the feature is enabled or not.
	 *
	 * @method is_feature_enabled
	 * @since  2.1.0
	 * @return boolean
	 */
	public function is_feature_enabled() {
		return $this->feature_enabled;
	}

	/**
	 * Adds a user role to the list of exempt roles.
	 *
	 * @method add_exempt_role
	 * @since  2.1.0
	 * @param  string $role a key that identifies a user role.
	 */
	public function add_exempt_role( $role = '' ) {
		// bail early if no role was passed.
		if ( empty( $role ) ) {
			return;
		}
		$this->exempt_roles[ $role ] = strtolower( $role );
	}

	/**
	 * Returns if the user role is exempt or not.
	 *
	 * @method is_role_exempt
	 * @since  2.1.0
	 * @param  string $role a key that identifies a user role.
	 * @return boolean
	 */
	public function is_role_exempt( $role = '' ) {
		$exempt = false;
		if ( in_array( strtolower( $role ), $this->exempt_roles, true ) ) {
			$exempt = true;
		}
		return $exempt;
	}

	/**
	 * Sets up the inactive users feature.
	 *
	 * @method init
	 * @since  2.1.0
	 */
	public function init() {

		add_action( 'ppm_settings_add_inactive_users_settings', array( $this, 'inactive_users_settings_markup' ), 10, 2 );
		
		// bail early if inactive users feature isn't enabled.
		if ( ! $this->feature_enabled ) {
			return;
		}
		add_action( 'wp_loaded', array( $this, 'register_cron' ) );
		add_action( 'admin_init', array( $this, 'register_ajax' ) );
		add_action( 'ppmwp_enqueue_admin_scripts', array( $this, 'register_scripts' ) );
		// Because the expired pw check is hooked to pri 0 we need pri 0 too.
		add_filter( 'wp_authenticate_user', array( $this, 'inactive_login_checker' ), 0, 2 );
		add_action( 'lostpassword_post', array( $this, 'inactive_password_reset_request_check' ), 10, 2 );

		add_filter(
			'ppmwp_adjust_dormancy_period',
			function( $seconds ) {
				$period = (int) apply_filters( 'ppmwp_testing_mode_inactive_period', 0 );
				if ( 0 !== $period && $period > 0 && self::DORMANCY_PERIOD !== $period ) {
					$seconds = $period;
				}
				return $seconds;
			}
		);

	}

	/**
	 * Register the inactive users check crons.
	 *
	 * @method register_cron
	 * @since
	 * @return void
	 */
	public function register_cron() {
		require_once PPM_WP_PATH . 'app/modules/inactive-users/class-inactive-users-check-cron.php';
		// setup the cron for this.
		$this->ppm->crons['inactive_users'] = new Crons\InactiveUsersCheck( $this );
		$this->ppm->crons['inactive_users']->register();
	}

	/**
	 * Register the inactive users ajax endpoints.
	 *
	 * @method register_ajax
	 * @since  2.1.0
	 */
	public function register_ajax() {
		require_once PPM_WP_PATH . 'app/modules/inactive-users/class-unlock-inactive-user-ajax.php';
		$this->ppm->ajax['reset_inactive_user'] = new Ajax\UnlockInactiveUser( $this );
		$this->ppm->ajax['reset_inactive_user']->register();
	}

	/**
	 * Registers scripts used for handling inactive users features.
	 *
	 * NOTE: this class registers scripts but enqueue should happen later, this
	 * is to ensure that they are only there on pages that need them.
	 *
	 * @method register_scripts
	 * @since  2.1.0
	 */
	public function register_scripts() {
		// this script is only registered here so enqueue it at a later point.
		wp_register_script( 'ppmwp-inactive-users', PPM_WP_URL . 'app/modules/inactive-users/inactiveUsers.js', array( 'jquery' ), PPMWP_VERSION, true );
		wp_localize_script(
			'ppmwp-inactive-users',
			'inactiveUsersStrings',
			array(
				'resettingUser'   => esc_html__( 'Resetting...', 'ppm-wp' ),
				'resetDone'       => esc_html__( 'User Reset', 'ppm-wp' ),
				'noUsers'         => esc_html__( 'Currently there are no locked users.', 'ppm-wp' ),
				'buttonReloading' => esc_html__( 'Reloading...', 'ppm-wp' ),
			)
		);
	}

	/**
	 * Checks if this user is a inactive user.
	 *
	 * Return a WP_Error if inactive or $user if not.
	 *
	 * @method inactive_login_checker
	 * @since  2.1.0
	 * @param  integer $user the user logging in.
	 * @param  string  $password the password that the user entered.
	 * @return WP_Error|WP_User
	 */
	public function inactive_login_checker( $user = 0, $password = '' ) {
		// First check if we have an error, if so bail early - it's probably an
		// incorrect password.
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		// gets the user meta for this user and checks it they are inactive.
		// inactive users get an error returned.
		$inactive_flag = OptionsHelper::is_user_inactive( $user->ID );

		$admin_email = get_option( 'admin_email' );
		$email_link  = '<a href="mailto:' . sanitize_email( $admin_email ) . '">' . __( 'website administrator', 'ppm-wp' ) . '</a>';

		$ppm             = ppm_wp();
		$default_options = \PPMWP\Helpers\OptionsHelper::string_to_bool( $ppm->options->inherit['master_switch'] ) ? $ppm->options->inherit : array();

		// Get user by ID.
		$get_userdata = get_user_by( 'ID', $user->ID );
		$roles        = $get_userdata->roles;

		$roles   = \PPMWP\Helpers\OptionsHelper::prioritise_roles( $roles );
		$roles   = reset( $roles );
		$options = get_site_option( PPMWP_PREFIX . '_' . $roles . '_options', $default_options );
		$message = ( ! empty( $options['deactivated_account_message'] ) ) ? $options['deactivated_account_message'] : \PPM_WP_Options::get_default_account_deactivated_message();

		if ( $inactive_flag ) {
			// Password is supplied so we know its a login, treat as such.
			if ( ! empty( $password ) ) {
				// user is inactive and isn't within an admin set reset time.
				return new \WP_Error(
					'inactive_user',
					wp_kses_post( $message )
				);
				// No password supplied, user is attempting reset.
			} elseif ( ! OptionsHelper::is_inactive_user_allowed_to_reset( $user->ID ) ) {
				// user is inactive and isn't within an admin set reset time.
				return new \WP_Error(
					'inactive_user',
					wp_kses_post( $message )
				);
			}
		}
		return $user;
	}

	/**
	 * Check if this user is already marked as inactive.
	 *
	 * When user is inactive an error message is returned.
	 *
	 * @method inactive_password_reset_request_check
	 * @since  2.1.0
	 * @param  WP_Error        $errors list of existing errors - will be empty.
	 * @param  WP_User|boolean $user_data either a WP_User object or false.
	 */
	public function inactive_password_reset_request_check( $errors, $user_data = false ) {
		// Assume NOT inactive untill checked.
		$inactive  = false;
		$user_data = isset( $_POST['user_login'] ) ? get_user_by( 'login', sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP doesn't pass a nonce in this form.
		// If we have user_data check if they're inactive.
		if ( $user_data ) {
			$inactive = $this->inactive_login_checker( $user_data, '' );
		}
		// If inactive check returned an error add it to the WP_Error object.
		if ( is_wp_error( $inactive ) ) {
			$errors->add( $inactive->get_error_code(), $inactive->get_error_message() );
		}
	}

	/**
	 * Sends a reset email to a inactive account.
	 *
	 * @method inactive_user_reset_email
	 * @since  2.1.0
	 * @param  int $user_id ID of the user account to be reset.
	 */
	public function send_inactive_user_reset_email( $user_id ) {
		$reset = new \PPM_WP_Reset();
		// if this fails it will return a message, rather than die, because 3rd
		// param is passed as true.
		$sent_message = $reset->send_reset_email( $user_id, 'admin', true );
	}

	/**
	 * Update user meta to show last activity time.
	 *
	 * @method ppm_detect_user_login
	 * @since  2.3.0
	 * @param  string $user User name, if available.
	 */
	public function ppm_detect_user_login( $user ) {

		if ( empty( $user ) ) {
			$user = wp_get_current_user();
		} else {
			$user = get_user_by( 'login', $user );
		}

		if ( is_a( $user, '\WP_User' ) ) {
			$inactive_flag = OptionsHelper::is_user_inactive( $user->ID );
			if ( ! $inactive_flag ) {
				update_user_meta( $user->ID, 'ppmwp_last_activity', current_time( 'timestamp' ) );
			}
		}
	}

	/**
	 * Add makrup to admin area.
	 *
	 * @param string $markup - Existing markup.
	 * @param string $settings_tab - New markup.
	 * @return string final markup.
	 */
	public function inactive_users_settings_markup( $markup, $settings_tab ) {
		$ppm       = ppm_wp();
		$test_mode = apply_filters( 'ppmwp_enable_testing_mode', false );
		$units     = array(
			'hours'  => __( 'hours', 'ppm-wp' ),
			'days'   => __( 'days', 'ppm-wp' ),
			'months' => __( 'months', 'ppm-wp' ),
		);
		if ( $test_mode ) {
			$units['seconds'] = __( 'seconds', 'ppm-wp' );
		}
		ob_start(); ?>
			<!-- Inactive Users Setting -->
			<tr class="setting-heading" valign="top">
				<th scope="row">
					<h3 class="mt-40"><?php esc_html_e( 'User account policies', 'ppm-wp' ); ?></h3>
				</th>
			</tr>
			<tr valign="top" id="ppmwp-inactive-setting-row">
				<th scope="row">
					<label for="ppm-inactive-users-enable">
						<?php esc_html_e( 'Inactive Users', 'ppm-wp' ); ?>
					</label>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Enable Inactive Users Features', 'ppm-wp' ); ?></span>
						</legend>
						<label for="ppm-inactive-users-enabled">
							<input name="_ppm_options[inactive_users_enabled]" type="checkbox" id="ppm-inactive-users-enabled" value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $settings_tab->inactive_users_enabled ) ); ?>>
							<?php esc_html_e( 'Disable inactive user accounts if they are inactive for more than', 'ppm-wp' ); ?>
							<input type="number" id="ppm-dormany-value" name="_ppm_options[inactive_users_expiry][value]"
								value="<?php echo esc_attr( $settings_tab->inactive_users_expiry['value'] ); ?>" size="4" class="small-text ltr" min="0" required>
							<select id="ppm-expiry-unit" name="_ppm_options[inactive_users_expiry][unit]">
								<?php
								foreach ( $units as $index => $unit ) {
									?>
									<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $index, $settings_tab->inactive_users_expiry['unit'], true ); ?>><?php echo esc_attr( $unit ); ?></option>
									<?php
								}
								?>
							</select>
						</label>
						<p class="description">
							<?php
							$inactive_users_url = add_query_arg(
								array(
									'page' => 'ppm-locked-users',
								),
								network_admin_url( 'admin.php' )
							);
							// Inactive users accounts will be will be locked. You can unlock users from the [Locked Users page](https://www.wpwhitesecurity.com/support/kb/dormant-users-policy-wordpress/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page' in this plugin. To learn more about this policy read the Inactive users policy feature document.
							printf(
								esc_html__( 'Inactive users accounts will be will be locked. You can unlock users from the %1$s page in this plugin. To learn more about this policy, read the %2$s feature document', 'ppm-wp' ),
								sprintf(
									'<a href="%1$s">%2$s</a>',
									esc_url( $inactive_users_url ),
									esc_html__( 'Locked Users', 'ppm-wp' )
								),
								sprintf(
									'<a target="_blank" href="https://www.wpwhitesecurity.com/support/kb/dormant-users-policy-wordpress/?utm_source=plugin&utm_medium=referral&utm_campaign=PPMWP&utm_content=help+page">%s</a>',
									esc_html__( 'Inactive users policy', 'ppm-wp' )
								)
							);
							?>
						</p>
					</fieldset>
				</td>
			</tr>

			<tr valign="top" id="ppmwp-inactive-setting-reset-pw-row">
				<th scope="row">
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Enable Inactive User Password Reset Feature', 'ppm-wp' ); ?></span>
						</legend>
						<label for="ppm-inactive-users-reset-on-unlock">
							<input name="_ppm_options[inactive_users_reset_on_unlock]" type="checkbox" id="ppm-inactive-users-reset-on-unlock" value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $settings_tab->inactive_users_reset_on_unlock ) ); ?>>
							<?php esc_html_e( 'Require inactive users to reset password on unlock.', 'ppm-wp' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'By default, when a inactive user has been unlocked, they are required to reset their password upon logging in - leave this unchecked to disable this behaviour.', 'ppm-wp' ); ?>
						</p>
					</fieldset>

					<div class="disabled-deactivated-message-wrapper" style="margin-top: 30px;">
						<p class="description" style="margin-bottom: 10px; display: block;">
							<?php esc_html_e( 'Display the following message when a user has been deactivated.', 'ppm-wp' ); ?>
						</p>
						<textarea id="deactivated_account_message" name="_ppm_options[deactivated_account_message]" rows="2" cols="60"><?php if ( empty( $settings_tab->deactivated_account_message ) ) { echo trim( \PPM_WP_Options::get_default_account_deactivated_message() );
							} else { echo wp_kses_post( $settings_tab->deactivated_account_message ); } ?></textarea>
					</div>
					
					<br>
					<fieldset>
						<legend class="screen-reader-text">
							<span><?php esc_html_e( 'Block Password reset requests from deactivated user', 'ppm-wp' ); ?></span>
						</legend>
						<label for="ppm-inactive-users-reset-on-unlock">
							<input name="_ppm_options[locked_user_disable_self_reset]" type="checkbox" id="ppm-inactive-users-disable-reset" value="1" <?php checked( \PPMWP\Helpers\OptionsHelper::string_to_bool( $settings_tab->locked_user_disable_self_reset ) ); ?>>
							<?php esc_html_e( 'Block Password reset requests from deactivated users (see locked users list)', 'ppm-wp' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'By default, when a inactive user has been unlocked, they are required to reset their password upon logging in - leave this unchecked to disable this behaviour.', 'ppm-wp' ); ?>
						</p>
					</fieldset>

					<div class="disabled-deactivated-message-wrapper" style="margin-top: 30px;">
						<p class="description" style="margin-bottom: 10px; display: block;">
							<?php esc_html_e( 'Display the following message when a user has been deactivated.', 'ppm-wp' ); ?>
						</p>
						<textarea id="deactivated_account_message" name="_ppm_options[locked_user_disable_self_reset_message]" rows="2" cols="60"><?php echo wp_kses_post( $settings_tab->locked_user_disable_self_reset_message ); ?></textarea>
					</div>
				</td>
			</tr>
			<!-- End Inactive Users Settings -->
		<?php
		return $markup . ob_get_clean();
	}
}
