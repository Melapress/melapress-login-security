<?php
/**
 * Responsible for showing the upgrade message in the plugins page.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to manage and display last login times.
 *
 * @since 2.0.0
 */
if ( ! class_exists( '\MLS\Admin\UserLastLoginTime' ) ) {
	/**
	 * Class to manage and display last login times.
	 *
	 * @since 2.0.0
	 */
	class UserLastLoginTime {

		/**
		 * Init class.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			add_filter( 'manage_users_custom_column', array( __CLASS__, 'populate_users_dashboard_column' ), 10, 3 );
			add_filter( 'manage_users_columns', array( __CLASS__, 'add_users_dashboard_column' ) );
			add_action( 'wp_login', array( __CLASS__, 'track_last_login' ), 10, 2 );
			add_filter( 'mls_reports_page_additional_tabs', array( __CLASS__, 'addition_reports_tab' ), 10, 2 );
			add_filter( 'mls_reports_page_additional_content', array( __CLASS__, 'addition_reports_content' ), 10, 3 );
		}

		/**
		 * Add new tab link
		 *
		 * @param string $current_additional - Current additional content.
		 * @param string $current_tab - Current additional tabs.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public static function addition_reports_tab( $current_additional, $current_tab ) {
			ob_start();
			?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'last-login' ) ); ?>" class="nav-tab<?php echo 'last-login' === $current_tab ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Last login time', 'melapress-login-security' ); ?></a>
			<?php
			return $current_additional . ob_get_clean();
		}

		/**
		 * Add content to new tab,
		 *
		 * @param string $current_additional - Current additional content.
		 * @param string $current_tab - Current additional tabs.
		 * @param object $current_table - Table class.
		 *
		 * @return string - Our output.
		 *
		 * @since 2.0.0
		 */
		public static function addition_reports_content( $current_additional, $current_tab, $current_table ) {
			global $wp_roles;
			$roles = $wp_roles->get_names();
			ob_start();
			?>
			<?php if ( 'last-login' === $current_tab ) { ?>
				<div class="wrap">
					<p class="filter-text"><?php esc_html_e( 'Show users with last login time by role', 'melapress-login-security' ); ?> 
						<select id="reset-role-select">
							<option value="all" selected>All</option>
							<?php
							foreach ( $roles as $key => $value ) {
								echo '<option value="' . esc_attr( strtolower( $value ) ) . '">' . wp_kses_post( $value ) . '</option>';
							}
							?>
						</select>
						<a href="#apply" class="button button-primary"><?php esc_html_e( 'Apply', 'melapress-login-security' ); ?></a>
					</p>
					<?php $current_table->display(); ?>
				</div>
			<?php } ?>
			<?php
			return $current_additional . ob_get_clean();
		}

		/**
		 * Track user logins.
		 *
		 * @param string  $user_login - User login name.
		 * @param WP_User $user - User object.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function track_last_login( $user_login, $user ) {
			if ( isset( $user->ID ) ) {
				$current_time = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
				update_user_meta( $user->ID, MLS_PREFIX . '_last_login_time', $current_time );
			}
		}

		/**
		 * Add new column to user management dashboard.
		 *
		 * @param array $columns - Current columns.
		 *
		 * @return array - Modified array.
		 *
		 * @since 2.0.0
		 */
		public static function add_users_dashboard_column( $columns ) {
			$new_columns = array(
				'mls-last-login' => esc_html__( 'Last login time', 'text_domain' ),
			);
			return array_merge( $columns, $new_columns );
		}

		/**
		 * Add data to new column
		 *
		 * @param string $output - Current output.
		 * @param string $column_name - Column ID.
		 * @param int    $user_id - User ID.
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public static function populate_users_dashboard_column( $output, $column_name, $user_id ) {
			if ( 'mls-last-login' === $column_name ) {
				$output = self::get_users_last_login_time_by_id( $user_id );
			}
			return $output;
		}

		/**
		 * Get users last login time for a given ID.
		 *
		 * @param int $user_id - Lookup ID.
		 *
		 * @return int|string - Result or fail message.
		 *
		 * @since 2.0.0
		 */
		public static function get_users_last_login_time_by_id( $user_id ) {
			$time = false;
			$time = get_user_meta( $user_id, MLS_PREFIX . '_last_login_time', true );

			if ( $time ) {
				return date_i18n( get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' ), $time );
			}

			return esc_html__( 'No data to display', 'melapress-login-security' );
		}
	}
}