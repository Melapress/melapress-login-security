<?php
/**
 * Melapress Login Security Require_Current_Password Class.
 *
 * Enforces that users must enter their current password before changing it on the profile page.
 *
 * @package MelapressLoginSecurity
 * @since 2.5.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

if ( ! class_exists( '\MLS\Require_Current_Password' ) ) {

}
