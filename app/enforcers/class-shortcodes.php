<?php
/**
 * Melapress Login Security shortcodes.
 *
 * @package MelapressLoginSecurity
 * @since 2.0.0
 */

declare(strict_types=1);

namespace MLS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLS\Helpers\OptionsHelper;

/**
 * Check if this class already exists.
 *
 * @since 2.0.0
 */
if ( ! class_exists( '\MLS\Shortcodes' ) ) {

	/**
	 * Declare Shortcodes Class
	 *
	 * @since 2.0.0
	 */
	class Shortcodes {

		/**
		 * Init hooks.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function init() {
			// Only load further if needed.
			if ( ! OptionsHelper::get_plugin_is_enabled() ) {
				return;
			}

			add_shortcode( 'ppmwp-custom-form', array( $this, 'custom_form_shortcode' ) );
			add_shortcode( 'mls-custom-form', array( $this, 'mls_custom_form_shortcode' ) );
		}

		/**
		 * Simple function to add custom form support via a shortcode to avoid
		 * loading assets on all front-end pages.
		 *
		 * @param array $atts Attributes (css classes, IDs) passed to shortcode.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function custom_form_shortcode( $atts ) {
			$shortcode_attributes = shortcode_atts(
				array(
					'element'          => '',
					'button_class'     => '',
					'elements_to_hide' => '',
				),
				$atts,
				'ppmwp-custom-form'
			);

			$custom_forms = new \MLS\Forms();
			$custom_forms->enable_custom_form( $shortcode_attributes );
		}

		/**
		 * Simple function to add custom form support via a shortcode to avoid
		 * loading assets on all front-end pages.
		 *
		 * @param array $atts Attributes (css classes, IDs) passed to shortcode.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public function mls_custom_form_shortcode( $atts ) {
			$shortcode_attributes = shortcode_atts(
				array(
					'element'          => '',
					'button_class'     => '',
					'elements_to_hide' => '',
				),
				$atts,
				'mls-custom-form'
			);

			$custom_forms = new \MLS\Forms();
			$custom_forms->enable_custom_form( $shortcode_attributes );
		}
	}
}
