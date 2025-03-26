<?php
/**
 * MLS Temporary logins class.
 *
 * @package MelapressLoginSecurity
 * @since 2.1.0
 */

declare(strict_types=1);

namespace MLS\TemporaryLogins;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;
use MLS\Helpers\SettingsHelper;

/**
 * Check if this class already exists.
 *
 * @since 2.1.0
 */
if ( ! class_exists( '\MLS\TemporaryLogins\Temporary_Logins_Table' ) ) {


	/**
	 * Create a new table class that will extend the WP_List_Table
	 *
	 * @since 2.1.0
	 */
	class Temporary_Logins_Table extends \WP_List_Table {

		/**
		 * Prepare the items for the table to process
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public function prepare_items() {
			$this->process_bulk_action();

			$columns  = $this->get_columns();
			$hidden   = $this->get_hidden_columns();
			$sortable = $this->get_sortable_columns();

			$data = $this->table_data();
			usort( $data, array( &$this, 'sort_data' ) );

			$per_page     = 15;
			$current_page = $this->get_pagenum();
			$total_items  = count( $data );

			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
				)
			);

			$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->items           = $data;
		}

		/**
		 * Override the parent columns method. Defines the columns to use in your listing table
		 *
		 * @return array - Column main data.
		 *
		 * @since 2.1.0
		 */
		public function get_columns() {
			return array(
				'cb'           => '<input type="checkbox" />',
				'user_login'   => esc_html__( 'Users', 'melapress-login-security' ),
				'created_on'   => esc_html__( 'Created on', 'melapress-login-security' ),
				'user_role'    => esc_html__( 'Role', 'melapress-login-security' ),
				'last_login'   => esc_html__( 'Last login', 'melapress-login-security' ),
				'logins_count' => esc_html__( 'Login count/max logins', 'melapress-login-security' ),
				'expires_on'   => esc_html__( 'Login expiry', 'melapress-login-security' ),
				'actions'      => esc_html__( 'Actions', 'melapress-login-security' ),
			);
		}

		/**
		 * Define which columns are hidden
		 *
		 * @return array - Hidden columns.
		 *
		 * @since 2.1.0
		 */
		public function get_hidden_columns() {
			return array();
		}

		/**
		 * Define the sortable columns
		 *
		 * @return array - Sortable columns.
		 *
		 * @since 2.1.0
		 */
		public function get_sortable_columns() {
			return array(
				'user_login' => array( 'user_login', false ),
				'created_on' => array( 'created_on', false ),
				'expires_on' => array( 'expires_on', false ),
				'user_role'  => array( 'user_role', false ),
			);
		}

		/**
		 * Get the table data
		 *
		 * @return array - Table data.
		 *
		 * @since 2.1.0
		 */
		private function table_data() {
			$users = \MLS\TemporaryLogins::get_temporary_logins();
			$data  = array();

			$current = \MLS\TemporaryLogins::get_current_gmt_timestamp();

			foreach ( $users as $user_id ) {
				if ( is_multisite() ) {
					if ( ! is_user_member_of_blog( $user_id, get_current_blog_id() ) ) {
						continue;
					}
				}
				$user        = get_user_by( 'ID', $user_id );
				$login_url   = \MLS\TemporaryLogins::get_login_url( $user->data->ID );
				$expires_on  = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) get_user_meta( $user->data->ID, 'mls_temp_user_expires_on', true ) ), get_option( 'date_format', get_option( 'date_format' ), true ) . ' ' . get_option( 'time_format', get_option( 'time_format' ), true ) );
				$login_count = ( get_user_meta( $user->data->ID, 'mls_login_count', true ) ) ? get_user_meta( $user->data->ID, 'mls_login_count', true ) : 0;

				if ( 0 === $login_count && 'expire_from_first_use' === get_user_meta( $user->data->ID, 'mls_temp_user_expires_on', true ) ) {
					$expires_on = '<span class="from_login">' . get_user_meta( $user->data->ID, 'mls_temp_user_expires_on_date', true ) . ' ' . esc_html__( 'from first login', 'melapress-login-security' ) . '</span>';
				} elseif ( get_user_meta( $user->data->ID, 'mls_temp_user_expired', true ) ) {
					$expires_on = '<span class="expired">' . esc_html__( 'Expired', 'melapress-login-security' ) . '</span>';
				}

				$max_logins = ( get_user_meta( $user->data->ID, 'mls_temp_user_max_login_limit', true ) ) ? get_user_meta( $user->data->ID, 'mls_temp_user_max_login_limit', true ) : 5;

				$last_login = ( get_user_meta( $user->data->ID, 'mls_last_login', true ) ) ? get_user_meta( $user->data->ID, 'mls_last_login', true ) : 0;

				if ( ! $last_login ) {
					$last_login = esc_html__( 'Not logged in', 'melapress-login-security' );
				} else {
					$last_login = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) $last_login ), get_option( 'date_format', get_option( 'date_format' ), true ) . ' ' . get_option( 'time_format', get_option( 'time_format' ), true ) );
				}

				$created_on = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) get_user_meta( $user->data->ID, 'mls_temp_user_created_on', true ) ), get_option( 'date_format', get_option( 'date_format' ), true ) . ' ' . get_option( 'time_format', get_option( 'time_format' ), true ) );

				if ( $login_count > $max_logins ) {
					$login_count = $max_logins;
				}

				$data[] = array(
					'user_login'   => $user->data->user_login,
					'user_id'      => $user->data->ID,
					'user_email'   => $user->data->user_email,
					'user_role'    => ucfirst( implode( ',', $user->roles ) ),
					'last_login'   => $last_login,
					'logins_count' => $login_count . '/' . $max_logins,
					'expires_on'   => $expires_on,
					'created_on'   => $created_on,
					'actions'      => array(
						'login_link'    => $login_url,
						'email_address' => $user->data->user_email,
					),
				);
			}

			return $data;
		}

		/**
		 * Define what data to show on each column of the table
		 *
		 * @param  array  $item - Data.
		 * @param  string $column_name - Current column name.
		 *
		 * @return mixed
		 *
		 * @since 2.1.0
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'cb':
				case 'user_role':
				case 'expires_on':
				case 'logins_count':
				case 'created_on':
				case 'last_login':
					return $item[ $column_name ];

				case 'actions':
					return '<a href="#" aria-label="' . esc_html__( 'Copy link to clipboard', 'melapress-login-security' ) . '" class="hint--top hint--rounded" data-mls-copy-link="' . $item['actions']['login_link'] . '"><span class="dashicons dashicons-admin-links"></span></a>' . ' ' . '<a href="#" aria-label="' . esc_html__( 'Email link to user', 'melapress-login-security' ) . '" class="hint--top hint--rounded" data-user-id="' . $item['user_id'] . '" data-mls-email-temp-link="' . $item['actions']['email_address'] . '" data-nonce="' . esc_attr( wp_create_nonce( MLS_PREFIX . '-email-login' ) ) . '"><span class="dashicons dashicons-email-alt"></span></a>'; // phpcs:ignore Generic.Strings.UnnecessaryStringConcat.Found

				default:
					return print_r( $item, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}
		}

		/**
		 * The checkbox column for bulk action selections.
		 *
		 * @param array $role - Role data.
		 *
		 * @return string - Checkbox markup.
		 *
		 * @since 2.1.0
		 */
		public function column_cb( $role ) {
			return '<input type="checkbox" value="' . $role['user_id'] . '" name="user_ids[]" />';
		}

		/**
		 * Allows you to sort the data by the variables set in the $_GET
		 *
		 * @param array $array_to_sort_a - Array A.
		 * @param array $array_to_sort_b - Array B.
		 *
		 * @return array - Column data
		 *
		 * @since 2.1.0
		 */
		private function sort_data( $array_to_sort_a, $array_to_sort_b ) {
			$orderby = 'user_login';
			$order   = 'asc';

			if ( ! empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$orderby = sanitize_key( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			if ( ! empty( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$order = sanitize_key( wp_unslash( $_GET['order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			$result = strcmp( $array_to_sort_a[ $orderby ], $array_to_sort_b[ $orderby ] );

			if ( 'asc' === $order ) {
				return $result;
			}

			return -$result;
		}

		/**
		 * Gets the array of available bulk actions for this list table.
		 *
		 * @method get_bulk_actions
		 *
		 * @return array
		 *
		 * @since 2.1.0
		 */
		public function get_bulk_actions() {
			return array(
				'reactivate' => esc_html__( 'Reactivate/deactivate selected', 'melapress-login-security' ),
				'delete'     => esc_html__( 'Delete selected', 'melapress-login-security' ),
			);
		}

		/**
		 * Single row markup.
		 *
		 * @param array $item - Data.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public function single_row( $item ) {
			echo '<tr>';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		/**
		 * Single column data.
		 *
		 * @param array $item - Data.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public function single_row_columns( $item ) {
			list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

			foreach ( $columns as $column_name => $column_display_name ) {
				$classes = "$column_name column-$column_name";
				if ( $primary === $column_name ) {
					$classes .= ' has-row-actions column-primary';
				}

				if ( in_array( $column_name, $hidden, true ) ) {
					$classes .= ' hidden';
				}

				/*
				* Comments column uses HTML in the display name with screen reader text.
				* Strip tags to get closer to a user-friendly string.
				*/
				$data = 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '"';

				$attributes = "class='$classes' $data";

				if ( 'cb' === $column_name ) {
					echo '<th scope="row" class="check-column">';
					echo $this->column_cb( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</th>';
				} elseif ( method_exists( $this, '_column_' . $column_name ) ) {
					echo wp_kses_post( call_user_func( array( $this, '_column_' . $column_name ), $item, $classes, $data, $primary ) );
				} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
					echo wp_kses_post( "<td $attributes>" );
					echo wp_kses_post( call_user_func( array( $this, 'column_' . $column_name ), $item ) );
					echo wp_kses_post( $this->handle_row_actions( $item, $column_name, $primary ) );
					echo '</td>';
				} else {
					echo wp_kses_post( "<td $attributes>" );
					echo wp_kses_post( $this->column_default( $item, $column_name ) );
					echo wp_kses_post( $this->handle_row_actions( $item, $column_name, $primary ) );
					echo '</td>';
				}
			}
		}

		/**
		 * Create links for each row.
		 *
		 * @param array $item - Item.
		 *
		 * @return string - Link markup.
		 *
		 * @since 2.1.0
		 */
		public function column_user_login( $item ) {
			$delete_nonce = wp_create_nonce( MLS_PREFIX . 'delete_role_nonce' );
			$label        = isset( $_REQUEST['page'] ) ? sanitize_title( wp_unslash( $_REQUEST['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$actions      = array(
				'disable' => sprintf( '<a href="?page=%s&action=%s&user_id=%s">' . esc_html__( 'Disable', 'melapress-login-security' ) . '</a>', $label, 'disable_link', sanitize_key( $item['user_id'] ) ),
				'delete'  => sprintf( '<a href="?page=%s&action=%s&user_id=%s">' . esc_html__( 'Delete', 'melapress-login-security' ) . '</a>', $label, 'delete_link', sanitize_key( $item['user_id'] ) ),
				'edit'    => sprintf( '<a href="?page=%s&action=%s&user_id=%s" data-delete-role="%s" data-nonce="%s">' . esc_html__( 'Edit', 'melapress-login-security' ) . '</a>', $label, 'edit_link', sanitize_key( $item['user_id'] ), sanitize_key( $item['user_login'] ), $delete_nonce ),
			);

			if ( get_user_meta( $item['user_id'], 'mls_temp_user_expired', true ) ) {
				$actions['disable'] = sprintf( '<a href="?page=%s&action=%s&user_id=%s">' . esc_html__( 'Enable', 'melapress-login-security' ) . '</a>', $label, 'enable_link', sanitize_key( $item['user_id'] ) );
			}
			$link = get_edit_user_link( sanitize_key( $item['user_id'] ) );
			return sprintf( '%1$s %2$s', sprintf( '<a href="%s">' . $item['user_login'] . '</a><br>' . $item['user_email'] . '', $link, $label, 'user-edit', sanitize_key( $item['user_id'] ) ), $this->row_actions( $actions ) );
		}

		/**
		 * Handle bulk actions
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public function process_bulk_action() {
			$post_array = filter_input_array( INPUT_POST );
			$action     = $this->current_action();

			switch ( $action ) {
				case 'delete':
					if ( isset( $post_array['user_ids'] ) ) {
						foreach ( $post_array['user_ids'] as $user_id ) {
							\MLS\TemporaryLogins::delete_user( $user_id );
						}
						echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Temporary logins deleted.', 'melapress-login-security' ) . '</p></div>';
					}

					break;

				case 'reactivate':
					if ( isset( $post_array['user_ids'] ) ) {
						foreach ( $post_array['user_ids'] as $user_id ) {
							if ( ! empty( get_user_meta( $user_id, 'mls_temp_user_expired', true ) ) ) {
								delete_user_meta( $user_id, 'mls_temp_user_expired' );
							} else {
								update_user_meta( $user_id, 'mls_temp_user_expired', \MLS\TemporaryLogins::get_current_gmt_timestamp() );
							}
						}
						echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Temporary logins reactivated/deactivated.', 'melapress-login-security' ) . '</p></div>';
					}

					break;

				default:
					return;
			}
		}
	}
}
