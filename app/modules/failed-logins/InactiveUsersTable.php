<?php
/**
 * Inactive Users List Table.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Views\Tables;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Admin\User_Helper;
use MLS\Helpers\OptionsHelper;

/**
 * Class for listing inactive users in a list table.
 *
 * @since 2.0.0
 */
class InactiveUsersTable extends \WP_List_Table {

	/**
	 * Total number of users.
	 *
	 * @var int
	 *
	 * @since 2.0.0
	 */
	public $total_found_users;

	/**
	 * Sets up the table class, calls the prepair method and enqueus a script.
	 *
	 * @method __construct
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Inactive User', 'melapress-login-security' ),
				'plural'   => __( 'Inactive Users', 'melapress-login-security' ),
				'ajax'     => true,
			)
		);
		$this->prepare_items();
		wp_enqueue_script( 'ppmwp-inactive-users' );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function no_items() {
		esc_html_e( 'Currently there are no locked users.', 'melapress-login-security' );
	}

	/**
	 * Gets the list of valid cols for this list table.
	 *
	 * @method get_columns
	 *
	 * @return array
	 *
	 * @since 2.0.0
	 */
	public function get_columns() {
		return array(
			'cb'             => '<input type="checkbox" />',
			'user'           => __( 'User', 'melapress-login-security' ),
			'roles'          => __( 'Roles', 'melapress-login-security' ),
			'locked_reason'  => __( 'Lock reason', 'melapress-login-security' ),
			'inactive_since' => __( 'Inactive Since', 'melapress-login-security' ),
			'actions'        => __( 'Actions', 'melapress-login-security' ),
		);
	}

	/**
	 * Gets the array of available bulk actions for this list table.
	 *
	 * @method get_bulk_actions
	 *
	 * @return array
	 *
	 * @since 2.0.0
	 */
	public function get_bulk_actions() {
		return array(
			'unlock' => __( 'Unlock', 'melapress-login-security' ),
		);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param string $which either 'top' or 'bottom'.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function extra_tablenav( $which ) {
		$page = isset( $_GET['current_page'] ) ? (int) sanitize_key( wp_unslash( $_GET['current_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'top' === $which ) {
			?>
			<button class="button-primary" id="mls_inactive_check_now" type="button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'mls_inactive_cron_trigger' ) ); ?>"><?php esc_html_e( 'Run Inactive Check Now', 'melapress-login-security' ); ?></button>
				<?php
				if ( $this->total_found_users > 0 ) {
					?>
				<strong><?php esc_html_e( 'Total blocked users:', 'melapress-login-security' ); ?></strong> <?php echo esc_attr( $this->total_found_users ); ?>
					<?php
					if ( $this->total_found_users > 50 ) {
						?>
					<div class="alignright actions" style="padding-right: 0px;">
						<span style="position: relative; top: 6px; right: 4px; }"><?php esc_html_e( 'Page', 'melapress-login-security' ); ?> <strong><?php echo esc_attr( $page ); ?></strong> <?php esc_html_e( 'of', 'melapress-login-security' ); ?>  <strong><?php echo esc_attr( ceil( $this->total_found_users / 50 ) ); ?></strong></span>
						<a href="<?php echo esc_url( add_query_arg( 'current_page', $page - 1 ) ); ?>" class="button-secondary" style="padding: 0 5px;"><span style="font-size: 15px; position: relative; top: 6px; right: 1px;" class="dashicons dashicons-arrow-left-alt2"></span></a>
						<a href="<?php echo esc_url( add_query_arg( 'current_page', $page + 1 ) ); ?>" class="button-secondary" style="padding: 0 5px;"><span  style="font-size: 15px; position: relative; top: 6px; right: 1px;"class="dashicons dashicons-arrow-right-alt2"></span></a>
					</div>
						<?php
					}
					?>
					<?php
				}
				?>
			<?php
		}
	}

	/**
	 * The checkbox column for bulk action selections.
	 *
	 * @method column_cb
	 * @param  \WP_User $user A user object to use making the col.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public function column_cb( $user ) {
		return '<input type="checkbox" value="' . $user->ID . '" name="' . esc_attr( $this->_args['singular'] ) . '[]" />';
	}

	/**
	 * Prepairs the data for the table, performing bulk actions before setting
	 * the items property.
	 *
	 * @method prepare_items
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$inactive_users = OptionsHelper::get_inactive_users();

		// Lets also grab an user IDs who are locked out from further login attempts.
		$failed_logins = new \MLS\Failed_Logins();
		$blocked_users = $failed_logins->get_all_currently_login_locked_users();

		// Merge them to avoid duplicates.
		$inactive_users = array_merge( $blocked_users, $inactive_users );

		// bail early if we don't have any users to display.
		if ( empty( $inactive_users ) ) {
			return;
		}

		$count = isset( $_GET['user_count'] ) ? (int) sanitize_key( wp_unslash( $_GET['user_count'] ) ) : 50; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page  = isset( $_GET['current_page'] ) ? (int) sanitize_key( wp_unslash( $_GET['current_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$user_args = array(
			'include'     => $inactive_users,
			'fields'      => 'all',
			'number'      => $count,
			'count_total' => true,
			'paged'       => $page,
		);

		if ( is_multisite() ) {
			$user_args['blog_id'] = 0;
		}

		// get WP_User objects.
		$users_query = new \WP_User_Query( $user_args );

		$this->items             = $users_query->results;
		$this->total_found_users = $users_query->total_users;
	}

	/**
	 * Handles the bulk actions for the inactive users table.
	 *
	 * @method process_bulk_action
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function process_bulk_action() {

		$action   = $this->current_action();
		$user_ids = isset( $_REQUEST['inactiveuser'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['inactiveuser'] ) ) : array();

		// if we have no users to work with no point in continuing.
		if ( empty( $user_ids ) ) {
			return;
		}

		check_admin_referer( 'bulk-inactiveusers' );

		$count = 0;
		// by this point we have passed nonce and know we have users to check.
		$inactive_users = OptionsHelper::get_inactive_users();

		// Lets also grab an user IDs who are locked out from further login attempts.
		$failed_logins = new \MLS\Failed_Logins();
		$blocked_users = $failed_logins->get_all_currently_login_locked_users();

		$mls = melapress_login_security();

		// Merge them to avoid duplicates.
		$inactive_users_all = array_merge( $blocked_users, $inactive_users );

		switch ( $action ) {
			case 'unlock':
				$mls = melapress_login_security();
				foreach ( $user_ids as $user_id ) {
					OptionsHelper::set_user_last_expiry_time( current_time( 'timestamp' ), $user_id ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

					// remove from the inactive users list.
					// phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict -- don't care about type juggling.
					if ( isset( $inactive_users_all ) && in_array( $user_id, $inactive_users_all ) ) {
						$keys = array_keys( $inactive_users_all, $user_id );
						// phpcs:enable
						// remove this user from the inactive array
						// NOTE: checking for false explictly to prevent 0 = false equality.
						if ( ! empty( $keys ) ) {
							$inactive_array_modified = true;
							foreach ( $keys as $key ) {
								unset( $inactive_users_all[ $key ] );
							}
						}
					}
					$userdata     = get_user_by( 'id', $user_id );
					$role_options = OptionsHelper::get_preferred_role_options( $userdata->roles );

					$failed_logins->clear_failed_login_data( $user_id, false );

					if ( in_array( $user_id, $blocked_users, true ) ) {
						$reset_password = OptionsHelper::string_to_bool( $role_options->failed_login_reset_on_unblock );
						$failed_logins->send_logins_unblocked_notification_email_to_user( $userdata->ID, $reset_password );
					} else {
						$reset_password = OptionsHelper::string_to_bool( $role_options->inactive_users_reset_on_unlock );
					}

					if ( ! \MLS\Helpers\OptionsHelper::string_to_bool( $mls->options->mls_setting->disable_user_unlocked_email ) ) {
						$failed_logins->send_logins_unblocked_notification_email_to_user( $userdata->ID, $reset_password );
					}
					++$count;
				}

				add_settings_error(
					'bulk_action',
					'bulk_action',
					/* translators: %d: Number of users. */
					sprintf( _n( 'Unlocked user %d', 'Unlocked %d users', $count ), $count ),
					'success'
				);
				break;
		}

		// if we counted a change then update the inactive array.
		if ( $count ) {
			OptionsHelper::set_inactive_users_array( $inactive_users_all );
		}
	}

	/**
	 * Define what data to show on each column of the table that doesn't have a
	 * better matching method.
	 *
	 * @param  array  $item        Data.
	 * @param  string $column_name Current column name.
	 *
	 * @return mixed
	 *
	 * @since 2.0.0
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				return __( 'No data to display...', 'melapress-login-security' );
		}
	}

	/**
	 * Defines the output for the 'user' col that shows the user the row is for
	 * linked to their edit page.
	 *
	 * @method column_user
	 * @param  \WP_User $user A user which we are making a row for.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public function column_user( $user ) {
		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( get_edit_user_link( $user->ID ) ),
			$user->user_login
		);
	}

	/**
	 * Shows the 'roles' col with roles the user is part of.
	 *
	 * @method column_roles
	 * @param  \WP_User $user A user which we are making a row for.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public function column_roles( $user ) {
		$roles = esc_html__( 'None', 'ppm-wp ' );
		if ( is_array( $user->roles ) ) {
			$roles = implode( ', ', $user->roles );
		}
		return $roles;
	}

	/**
	 * Shows the reason a user was locked.
	 *
	 * @param \WP_User $user - User details.
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public function column_locked_reason( $user ) {
		$is_user_blocked = get_user_meta( $user->ID, MLS_USER_BLOCK_FURTHER_LOGINS_META_KEY, true );
		return ( $is_user_blocked ) ? __( 'failed logins', 'melapress-login-security' ) : User_Helper::get_user_locked_reason_label( $user->ID );
	}

	/**
	 * The 'inactive since' col that outputs a data when the user was inactive.
	 *
	 * @method column_inactive_since
	 * @param  \WP_User $user A user which we are making a row for.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public function column_inactive_since( $user ) {
		$display       = __( 'No data to display...', 'melapress-login-security' );
		$inactive_time = OptionsHelper::get_inactive_user_time( $user->ID );
		if ( $inactive_time ) {
			$display = gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $inactive_time );
		}
		return esc_html( $display );
	}

	/**
	 * The 'actions' col containing buttons for doing things with the each
	 * individual user for that row.
	 *
	 * @method column_actions
	 * @param  \WP_User $user A user which we are making a row for.
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */
	public function column_actions( $user ) {
		$is_user_blocked = ( get_user_meta( $user->ID, MLS_USER_BLOCK_FURTHER_LOGINS_META_KEY, true ) ) ? 'true' : 'false';
		return sprintf(
			'<button type="button" value="%1$d" class="button-primary unlock-inactive-user-button" data-is-blocked-user="%2$s" style="float: right;">%3$s</button>',
			$user->ID,
			$is_user_blocked,
			esc_html__( 'Unlock', 'melapress-login-security' )
		);
	}

	/**
	 * Prints JavaScropt object with some data for use in the table.
	 *
	 * @method _js_vars
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function _js_vars() {
		$args = array(
			'screen' => array(
				'id'   => $this->screen->id,
				'base' => $this->screen->base,
			),
			'nonce'  => wp_create_nonce( \MLS\Ajax\UnlockInactiveUser::NONCE_KEY ),
		);

		printf(
			"<script type='text/javascript'>inactiveUsersData = %s;</script>\n",
			wp_json_encode( $args )
		);
	}
}
