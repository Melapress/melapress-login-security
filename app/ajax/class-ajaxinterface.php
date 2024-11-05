<?php
/**
 * Interface for building ajax actions.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Ajax;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An abstract class to be used when creating ajax actions. This ensures a consistent
 * way of using them and invoking them.
 *
 * @since 2.0.0
 */
interface AjaxInterface {

	/**
	 * Register the handler and nonce, this is the entrypoint.
	 *
	 * @method register
	 *
	 * @since 2.0.0
	 */
	public function register();

	/**
	 * The action to run.
	 *
	 * This can be added directly in the registration or called by another
	 * method that is used during the registration hook.
	 *
	 * @method action
	 *
	 * @since 2.0.0
	 */
	public function action();

	/**
	 * Checks the nonce passed for this action.
	 *
	 * NOTE: always pass a nonce.
	 *
	 * @method check_nonce
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public static function check_nonce();
}
