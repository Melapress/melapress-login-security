<?php
/**
 * Inactive Users List Table.
 *
 * @since 2.1.0
 *
 * @package wordpress
 */

namespace PPMWP\Views\Tables;

use PPMWP\Helpers\OptionsHelper;

/**
 * Class for listing inactive users in a list table.
 */
class InactiveUsersTable extends \WP_List_Table {

	/**
	 * Sets up the table class, calls the prepair method and enqueus a script.
	 *
	 * @method __construct
	 * @since  2.1.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Inactive User', 'ppm-wp' ),
				'plural'   => __( 'Inactive Users', 'ppm-wp' ),
				'ajax'     => true,
			)
		);
		$this->prepare_items();
		wp_enqueue_script( 'ppmwp-inactive-users' );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 2.1.0
	 */
	public function no_items() {
		esc_html_e( 'Currently there are no inactive users to display.', 'ppm-wp' );
	}

	/**
	 * Gets the list of valid cols for this list table.
	 *
	 * @method get_columns
	 * @since  2.1.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'             => '<input type="checkbox" />',
			'user'           => __( 'User', 'ppm-wp' ),
			'roles'          => __( 'Roles', 'ppm-wp' ),
			'locked_reason'  => __( 'Locked because of', 'ppm-wp' ),
			'inactive_since' => __( 'Inactive Since', 'ppm-wp' ),
			'actions'        => __( 'Actions', 'ppm-wp' ),
		);
	}

	/**
	 * Gets the array of available bulk actions for this list table.
	 *
	 * @method get_bulk_actions
	 * @since  2.1.0
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'unlock' => __( 'Unlock', 'ppm-wp' ),
		);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 2.1.0
	 * @param string $which either 'top' or 'bottom'.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			?>
			<button class="button-primary" id="ppmwp_inactive_check_now" type="button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ppmwp_inactive_cron_trigger' ) ); ?>"><?php esc_html_e( 'Run Inactive Check Now', 'ppm-wp' ); ?></button>
			<?php
		}
	}

	/**
	 * The checkbox column for bulk action selections.
	 *
	 * @method column_cb
	 * @since  2.1.0
	 * @param  \WP_User $user A user object to use making the col.
	 * @return string
	 */
	public function column_cb( $user ) {
		return '<input type="checkbox" value="' . $user->ID . '" name="' . esc_attr( $this->_args['singular'] ) . '[]" />';
	}

	/**
	 * Prepairs the data for the table, performing bulk actions before setting
	 * the items property.
	 *
	 * @method prepare_items
	 * @since  2.1.0
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$inactive_users = OptionsHelper::get_inactive_users();

		// Lets also grab an user IDs who are locked out from further login attempts.
		$failed_logins = new \PPM_Failed_Logins();
		$blocked_users = $failed_logins->get_all_currently_login_locked_users();

		// Merge them to avoid duplicates.
		$inactive_users = array_merge( $blocked_users, $inactive_users );

		// bail early if we don't have any users to display.
		if ( empty( $inactive_users ) ) {
			return;
		}

		// get WP_User objects.
		$users_query = new \WP_User_Query(
			array(
				'include' => $inactive_users,
				'fields'  => 'all',
			)
		);
		$this->items = $users_query->results;

	}

	/**
	 * Handles the bulk actions for the inactive users table.
	 *
	 * @method process_bulk_action
	 * @since  2.1.0
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
		switch ( $action ) {
			case 'unlock':
				$ppm = ppm_wp();
				foreach ( $user_ids as $user_id ) {
					OptionsHelper::set_user_last_expiry_time( current_time( 'timestamp' ), $user_id );
					// remove from the inactive users list.
					// phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict -- don't care about type juggling.
					if ( isset( $inactive_users ) && in_array( $user_id, $inactive_users ) ) {
						$key = array_search( $user_id, $inactive_users );
						// phpcs:enable
						// remove this user from the inactive array
						// NOTE: checking for false explictly to prevent 0 = false equality.
						if ( 0 === $key || false !== $key ) {
							$inactive_array_modified = true;
							unset( $inactive_users[ $key ] );
						}
					}
					$ppm->inactive->send_inactive_user_reset_email( $user_id );
					$count++;
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
			OptionsHelper::set_inactive_users_array( $inactive_users );
		}
	}

	/**
	 * Define what data to show on each column of the table that doesn't have a
	 * better matching method.
	 *
	 * @param  array  $item        Data.
	 * @param  string $column_name Current column name.
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				return __( 'No data to display...', 'ppm-wp' );
		}
	}

	/**
	 * Defines the output for the 'user' col that shows the user the row is for
	 * linked to their edit page.
	 *
	 * @method column_user
	 * @since  2.1.0
	 * @param  \WP_User $user A user which we are making a row for.
	 * @return string
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
	 * @since  2.1.0
	 * @param  \WP_User $user A user which we are making a row for.
	 * @return string
	 */
	public function column_roles( $user ) {
		$roles = esc_html__( 'None', 'ppm-wp ' );
		if ( is_array( $user->roles ) ) {
			$roles = implode( ', ', $user->roles );
		}
		return $roles;
	}

	public function column_locked_reason( $user ) {
		$is_user_blocked = get_user_meta( $user->ID, PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY, true );
		return ( $is_user_blocked ) ? __( 'failed logins', 'ppm-wp' ) : __( 'inactivty', 'ppm-wp' );
	}

	/**
	 * The 'inactive since' col that outputs a data when the user was inactive.
	 *
	 * @method column_inactive_since
	 * @since  2.1.0
	 * @param  \WP_User $user A user which we are making a row for.
	 * @return string
	 */
	public function column_inactive_since( $user ) {
		$display      = __( 'No data to display...', 'ppm-wp' );
		$inactive_time = OptionsHelper::get_inactive_user_time( $user->ID );
		if ( $inactive_time ) {
			$display = date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $inactive_time );
		}
		return esc_html( $display );

	}

	/**
	 * The 'actions' col containing buttons for doing things with the each
	 * individual user for that row.
	 *
	 * @method column_actions
	 * @since  2.1.0
	 * @param  \WP_User $user A user which we are making a row for.
	 * @return string
	 */
	public function column_actions( $user ) {
		$is_user_blocked = ( get_user_meta( $user->ID, PPMWP_USER_BLOCK_FURTHER_LOGINS_KEY, true ) ) ? 'true' : 'false';
		return sprintf(
			'<button type="button" value="%1$d" class="button-primary unlock-inactive-user-button" data-is-blocked-user="%2$s">%3$s</button>',
			$user->ID,
			$is_user_blocked,
			esc_html__( 'Unlock', 'ppm-wp' )
		);
	}

	/**
	 * Prints JavaScropt object with some data for use in the table.
	 *
	 * @method _js_vars
	 * @since  2.1.0
	 */
	public function _js_vars() {
		$args = array(
			'screen' => array(
				'id'   => $this->screen->id,
				'base' => $this->screen->base,
			),
			'nonce'  => wp_create_nonce( \PPMWP\Ajax\UnlockInactiveUser::NONCE_KEY ),
		);

		printf(
			"<script type='text/javascript'>inactiveUsersData = %s;</script>\n",
			wp_json_encode( $args )
		);
	}

}
