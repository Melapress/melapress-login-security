<?php
/**
 * EDD Plan class for Melapress Login Security plugin.
 *
 * Lightweight plan representation for EDD licensing, analogous to
 * Freemius's FS_Plugin_Plan. Used by the extension loader to determine
 * which premium modules are available for the current license.
 *
 * @since      2.4.0
 * @package    MelapressLoginSecurity
 * @subpackage Licensing
 * @copyright  2026 Melapress
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link       https://melapress.com/wordpress-login-security/
 */

declare(strict_types=1);

namespace MLS\Licensing;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use MLS\Licensing\EDD_Provider;

if ( ! class_exists( '\MLS\Licensing\EDD_Plan' ) ) {

	/**
	 * Represents an EDD license plan.
	 *
	 * @since 2.4.0
	 */
	class EDD_Plan {

		/**
		 * Get the normalized plan name for the current license.
		 *
		 * @return string - Normalized plan name ('premium' or 'enterprise').
		 *
		 * @since 2.4.0
		 */
		public static function get_plan_name(): string {

			$license_data = EDD_Provider::get_license();
			$item_name    = isset( $license_data['item_name'] ) ? $license_data['item_name'] : '';
			$plan_name    = self::resolve_plan_name( $item_name );

			return $plan_name;
		}

		/**
		 * Resolve a normalized plan name from the EDD item name.
		 *
		 * Uses substring matching (case-insensitive) to detect the plan tier.
		 * This is resilient to product name changes as long as the tier
		 * keyword ("Premium" or "Enterprise") remains in the name.
		 *
		 * @param string $item_name - The item name from the EDD license response.
		 *
		 * @return string - Normalized plan name ('premium' or 'enterprise').
		 *
		 * @since 2.4.0
		 */
		private static function resolve_plan_name( string $item_name ): string {
			if ( stripos( $item_name, 'Enterprise' ) !== false ) {
				return 'enterprise';
			}

			if ( stripos( $item_name, 'Premium' ) !== false ) {
				return 'premium';
			}

			// Default fallback.
			return 'premium';
		}
	}
}
