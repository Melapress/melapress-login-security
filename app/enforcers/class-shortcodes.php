<?php
/**
 * PPM New User Register
 *
 * @package wordpress
 * @subpackage wpassword
 * @author WP White Security
 */

use \PPMWP\Helpers\OptionsHelper;

/**
 * Check if this class already exists.
 */
if ( ! class_exists( 'PPM_Shortcodes' ) ) {

  /**
   * Declare PPM_Shortcodes Class
   */
  class PPM_Shortcodes {

    /**
     * Init hooks.
     */
    public function init() {
      // Only load further if needed.
      if ( ! OptionsHelper::getPluginIsEnabled() ) {
        return;
      }

      add_shortcode( 'ppmwp-custom-form', [ $this, 'custom_form_shortcode' ] );
    }

    /**
    * Simple function to add custom form support via a shortcode to avoid
    * loading assets on all front-end pages.
    *
    * @param  array $atts Attributes (css classes, IDs) passed to shortcode.
    */
    public function custom_form_shortcode( $atts ) {
      $shortcode_attributes = shortcode_atts(
        [
          'element'	         => '',
          'button_class'     => '',
          'elements_to_hide' => '',
        ],
        $atts,
        'ppmwp-custom-form'
      );

      $custom_forms = new PPM_WP_Forms();
      $custom_forms->enable_custom_form( $shortcode_attributes );
    }
  }
}
