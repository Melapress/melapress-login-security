<?php
/**
 * Inactive/Locked Users List Table.
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

// list table should be defined by here but just incase check first.
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
// require_once MLS_PATH . 'app/modules/failed-logins/InactiveUsersTable.php';

if ( ! class_exists( '\MLS\Views\Tables\Inactive_Users_Table' ) ) {

	/**
	 * Class for listing inactive/locked users in a list table.
	 *
	 * @since 2.0.0
	 */
	class Inactive_Users_Table extends \WP_List_Table {

		/**
		 * Number of items per page.
		 *
		 * @var int
		 *
		 * @since 2.0.0
		 */
		private $per_page = 20;

		/**
		 * Total number of users.
		 *
		 * @var int
		 *
		 * @since 2.0.0
		 */
		public $total_found_users = 0;

		/**
		 * Available lock reasons for filtering.
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		private $lock_reasons = array();

		/**
		 * Sets up the table class, calls the prepare method and enqueues scripts.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			parent::__construct(
				array(
					'singular' => 'inactiveuser',
					'plural'   => 'inactiveusers',
					'ajax'     => false,
				)
			);

			$this->lock_reasons = array(
				'failed_logins' => __( 'Failed logins', 'melapress-login-security' ),
				'manual'        => __( 'Manually locked', 'melapress-login-security' ),
			);

			if ( class_exists( '\MLS\InactiveUsers' ) ) {
				$this->lock_reasons['inactivity'] = __( 'Inactivity', 'melapress-login-security' );
			}

			$this->prepare_items();
			wp_enqueue_script( 'ppmwp-inactive-users' );
		}

		/**
		 * Message to be displayed when there are no items.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function no_items() {
			$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// @free:start
			// Only the search narrows this list here, so only the search can be
			// the reason it came back empty.
			if ( ! empty( $search ) ) {
				esc_html_e( 'No locked users found matching your search criteria.', 'melapress-login-security' );
			} else {
				esc_html_e( 'Currently there are no locked users.', 'melapress-login-security' );
			}
			// @free:end
		}

		/**
		 * Gets the list of columns for this list table.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public function get_columns() {
			return array(
				'cb'             => '<input type="checkbox" />',
				'user'           => __( 'User', 'melapress-login-security' ),
				'email'          => __( 'Email', 'melapress-login-security' ),
				'user_id'        => __( 'User ID', 'melapress-login-security' ),
				'roles'          => __( 'Roles', 'melapress-login-security' ),
				'locked_reason'  => __( 'Lock reason', 'melapress-login-security' ),
				'inactive_since' => __( 'Locked since', 'melapress-login-security' ),
				'actions'        => __( 'Actions', 'melapress-login-security' ),
			);
		}

		/**
		 * Gets the sortable columns.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public function get_sortable_columns() {
			return array(
				'user'           => array( 'user_login', false ),
				'user_id'        => array( 'ID', false ),
				'inactive_since' => array( 'inactive_since', false ),
			);
		}

		/**
		 * Gets the array of available bulk actions for this list table.
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
		 * Extra controls to be displayed between bulk actions and pagination.
		 *
		 * @param string $which Either 'top' or 'bottom'.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function extra_tablenav( $which ) {
			if ( 'top' !== $which ) {
				return;
			}

			?>

			<?php
			/*
			 * Brings the list up to date: lock records that have run out are
			 * dropped, and where the inactive users feature is present its check
			 * runs too. Both builds have locks that lapse, so both get the button
			 * — it used to be behind a class_exists() test for a premium-only
			 * class, which meant the free edition never showed it at all.
			 */
			?>
			<button class="button-primary" id="mls_refresh_lock_status" type="button" data-nonce="<?php echo esc_attr( wp_create_nonce( \MLS\Failed_Logins::REFRESH_LOCK_STATUS_ACTION ) ); ?>"><?php esc_html_e( 'Refresh users lock status', 'melapress-login-security' ); ?></button>
			<?php
		}

		/**
		 * Generates the search box for the table.
		 *
		 * @param string $text     The submit button label.
		 * @param string $input_id The search input ID.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function search_box( $text, $input_id ) {
			$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<p class="search-box">
				<label for="<?php echo esc_attr( $input_id ); ?>"><small><?php esc_html_e( 'Search by username, email, or user ID', 'melapress-login-security' ); ?></small></label>
				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php echo esc_attr( $search ); ?>" />
				<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
			</p>
			<?php
		}

		/**
		 * The checkbox column for bulk action selections.
		 *
		 * @param \WP_User $user A user object.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function column_cb( $user ) {
			return '<input type="checkbox" value="' . esc_attr( $user->ID ) . '" name="inactiveuser[]" />';
		}

		/**
		 * Prepares the data for the table.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function prepare_items() {
			$columns  = $this->get_columns();
			$hidden   = array();
			$sortable = $this->get_sortable_columns();

			$this->_column_headers = array( $columns, $hidden, $sortable );

			$this->process_bulk_action();

			$inactive_users = OptionsHelper::get_inactive_users();

			// Also grab user IDs who are locked out from further login attempts.
			$failed_logins = new \MLS\Failed_Logins();
			$blocked_users = $failed_logins->get_all_currently_login_locked_users();

			// Merge them to avoid duplicates.
			$all_locked_user_ids = array_unique( array_merge( $blocked_users, $inactive_users ) );
			$all_locked_user_ids = array_filter( $all_locked_user_ids );

			// Bail early if we don't have any users to display.
			if ( empty( $all_locked_user_ids ) ) {
				$this->items             = array();
				$this->total_found_users = 0;
				$this->set_pagination_args(
					array(
						'total_items' => 0,
						'per_page'    => $this->per_page,
						'total_pages' => 0,
					)
				);
				return;
			}

			// Build WP_User_Query args.
			$paged = $this->get_pagenum();

			$user_args = array(
				'include'     => $all_locked_user_ids,
				'fields'      => 'all',
				'number'      => $this->per_page,
				'count_total' => true,
				'paged'       => $paged,
			);

			if ( is_multisite() ) {
				$user_args['blog_id'] = 0;
			}

			// Search filter.
			$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $search ) ) {
				if ( is_numeric( $search ) ) {
					$user_args['include'] = array_intersect( $all_locked_user_ids, array( (int) $search ) );
					if ( empty( $user_args['include'] ) ) {
						$this->items             = array();
						$this->total_found_users = 0;
						$this->set_pagination_args(
							array(
								'total_items' => 0,
								'per_page'    => $this->per_page,
								'total_pages' => 0,
							)
						);
						return;
					}
				} else {
					$user_args['search']         = '*' . $search . '*';
					$user_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
				}
			}


			// Sorting.
			$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'user_login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$allowed_orderby = array( 'user_login', 'ID', 'inactive_since' );
			if ( in_array( $orderby, $allowed_orderby, true ) ) {
				if ( 'inactive_since' === $orderby ) {
					$user_args['meta_key'] = MLS_PREFIX . '_blocked_since'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					$user_args['orderby']  = 'meta_value_num';
				} else {
					$user_args['orderby'] = $orderby;
				}
			}
			$user_args['order'] = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $order ) : 'ASC';

			// Get WP_User objects.
			$users_query = new \WP_User_Query( $user_args );
			$users       = $users_query->results;
			$total_users = $users_query->total_users;


			$this->items             = $users;
			$this->total_found_users = $total_users;

			$this->set_pagination_args(
				array(
					'total_items' => $total_users,
					'per_page'    => $this->per_page,
					'total_pages' => (int) ceil( $total_users / $this->per_page ),
				)
			);
		}

		/**
		 * Gets the lock reason key for a user.
		 *
		 * @param \WP_User $user          The user object.
		 * @param array    $blocked_users Array of user IDs blocked due to failed logins.
		 *
		 * @return string The lock reason key.
		 *
		 * @since 2.0.0
		 */
		private function get_user_lock_reason_key( $user, $blocked_users = array() ) {
			if ( in_array( $user->ID, $blocked_users, true ) ) {
				return 'failed_logins';
			}

			$detected = OptionsHelper::is_user_locked_by_any_mechanism( $user->ID, 'any' );

			return $detected ? $detected : 'manual';
		}

		/**
		 * Handles the bulk actions for the inactive users table.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function process_bulk_action() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$action   = $this->current_action();
			$user_ids = isset( $_REQUEST['inactiveuser'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['inactiveuser'] ) ) : array();

			if ( empty( $user_ids ) ) {
				return;
			}

			check_admin_referer( 'bulk-inactiveusers' );

			$count = 0;
			$mls   = melapress_login_security();

			switch ( $action ) {
				case 'unlock':
					foreach ( $user_ids as $user_id ) {
						$userdata = get_user_by( 'id', $user_id );
						if ( ! $userdata ) {
							continue;
						}

						$role_options  = OptionsHelper::get_preferred_role_options( $userdata->roles );
						// Was reading an account-wide flag nothing writes; the failed-login
						// lockout now lives in the per-source records.
						$is_blocked    = \MLS\Failed_Logins::has_active_lock_event( $user_id );

						// R1 — Single unlock: fully clear ALL lock types.
						OptionsHelper::fully_unlock_user( $user_id, $is_blocked ? 'blocked' : 'inactive' );

						// Send notification email unless disabled.
						if ( ! OptionsHelper::string_to_bool( $mls->options->mls_setting->disable_user_unlocked_email ) ) {
							if ( 'yes' === $is_blocked ) {
								$reset_password = OptionsHelper::string_to_bool( $role_options->failed_login_reset_on_unblock );
							} else {
								$reset_password = OptionsHelper::string_to_bool( $role_options->inactive_users_reset_on_unlock );
							}
							\MLS\Failed_Logins::send_logins_unblocked_notification_email_to_user( $userdata->ID, $reset_password );
						}
						++$count;
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						/* translators: %d: Number of users. */
						sprintf( _n( 'Unlocked user %d', 'Unlocked %d users', $count, 'melapress-login-security' ), $count ),
						'success'
					);
					break;
			}
		}

		/**
		 * Default column output.
		 *
		 * @param \WP_User $item        User object.
		 * @param string   $column_name Current column name.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function column_default( $item, $column_name ) {
			return __( 'No data to display...', 'melapress-login-security' );
		}

		/**
		 * The 'user' column showing username linked to edit page.
		 *
		 * @param \WP_User $user A user object.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function column_user( $user ) {
			return sprintf(
				'<strong><a href="%1$s">%2$s</a></strong>',
				esc_url( get_edit_user_link( $user->ID ) ),
				esc_html( $user->user_login )
			);
		}

		/**
		 * The 'email' column.
		 *
		 * @param \WP_User $user A user object.
		 *
		 * @return string
		 *
		 * @since 2.5.0
		 */
		public function column_email( $user ) {
			return esc_html( $user->user_email );
		}

		/**
		 * The 'user_id' column.
		 *
		 * @param \WP_User $user A user object.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function column_user_id( $user ) {
			return esc_html( (string) $user->ID );
		}

		/**
		 * The 'roles' column.
		 *
		 * @param \WP_User $user A user object.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function column_roles( $user ) {
			if ( is_array( $user->roles ) && ! empty( $user->roles ) ) {
				$role_names = array_map(
					function ( $role ) {
						return translate_user_role( ucfirst( $role ) );
					},
					$user->roles
				);
				return esc_html( implode( ', ', $role_names ) );
			}
			return esc_html__( 'None', 'melapress-login-security' );
		}

		/**
		 * The 'locked_reason' column.
		 *
		 * @param \WP_User $user User object.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function column_locked_reason( $user ) {
			$key   = $this->get_user_lock_reason_key( $user );
			$label = User_Helper::get_user_locked_reason_label( $user->ID );

			if ( ! $label ) {
				$label = __( 'locked', 'melapress-login-security' );
			}

			return '<span class="mls-reason-badge mls-reason-' . esc_attr( $key ) . '">' . esc_html( ucfirst( $label ) ) . '</span>';
		}

		/**
		 * The 'inactive_since' column.
		 *
		 * @param \WP_User $user A user object.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function column_inactive_since( $user ) {
			$inactive_time = OptionsHelper::get_inactive_user_time( $user->ID );
			if ( $inactive_time ) {
				$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				return esc_html( gmdate( $date_format, (int) $inactive_time ) );
			}
			return esc_html__( 'No data to display...', 'melapress-login-security' );
		}

		/**
		 * The 'actions' column with unlock button.
		 *
		 * @param \WP_User $user A user object.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function column_actions( $user ) {
			$is_user_blocked = \MLS\Failed_Logins::has_active_lock_event( $user->ID ) ? 'true' : 'false';
			return sprintf(
				'<button type="button" value="%1$d" class="button-primary unlock-inactive-user-button" data-is-blocked-user="%2$s">%3$s</button>',
				$user->ID,
				$is_user_blocked,
				esc_html__( 'Unlock', 'melapress-login-security' )
			);
		}

		/**
		 * Generates content for a single row of the table.
		 *
		 * @param \WP_User $item The user object for this row.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function single_row( $item ) {
			echo '<tr id="user-row-' . esc_attr( (string) $item->ID ) . '">';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		/**
		 * Prints JavaScript data for use in the table.
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
				"<script type='text/javascript'>var inactiveUsersData = %s;</script>\n",
				wp_json_encode( $args )
			);
		}
	}
}
