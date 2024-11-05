<?php
/**
 * Interface for defining cron actions.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS\Crons;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An abstract class to be used when creating crons. This ensures a consistent
 * way of using them and invoking them.
 *
 * @since 2.0.0
 */
interface CronInterface {

	/**
	 * Register the cron task here, this is the entrypoint.
	 *
	 * @method register
	 *
	 * @since 2.0.0
	 */
	public function register();

	/**
	 * The action to run, optionally this can just register the hook.
	 *
	 * @method action
	 *
	 * @since 2.0.0
	 */
	public function action();
}
