<?php
/**
 * @package wordpress
 * @subpackage wpassword
 * 
 * @author Naili Concescu <djanym@gmail.com>
 */

defined('ABSPATH') or die();

if( ! class_exists('PPM_WP_Pointer') ){

	/**
	 * Provides pointer popup after installing the plugin
	 */
	class PPM_WP_Pointer {

		public function __construct(){
			add_action('admin_enqueue_scripts', [$this, 'init_pointers']);
		}

		public function init_pointers(){
			$pointers = array(
				array(
					'id'       => 'password_policy_manager_after_install',
					'screen'   => 'plugins',
					'target'   => '#toplevel_page_ppm_wp_settings',
					'title'    => __('Configure the password policies', 'ppm-wp'),
					'content'  => wp_sprintf( '%s <a href="%s" class="ppm-pointer-close" style="text-decoration: none;">%s</a> %s', __( 'By default the password policies are disabled.', 'ppm-wp' ), esc_url( add_query_arg( 'page', 'ppm_wp_settings', network_admin_url( 'admin.php' ) ) ) , __( 'Click here', 'ppm-wp' ), __( 'to configure the policies in the site\'s settings.', 'ppm-wp' ) ),
					'position' => array(
						'edge'  => 'left', // top, bottom, left, right
						'align' => 'right' // top, bottom, left, right, middle
					)
				)
			);
			new WP_Admin_Pointer($pointers);
		}

	}
	new PPM_WP_Pointer();
}

if( ! class_exists('WP_Admin_Pointer') ){

	class WP_Admin_Pointer {
		public $screen_id;
		public $valid;
		public $pointers;

		/**
		 * Register variables and start up plugin
		 */
		public function __construct($pointers = array()){
			if( get_bloginfo('version') < '3.3' )
				return;
			$screen = get_current_screen();
			$this->screen_id = $screen->id;
			$this->register_pointers($pointers);
			add_action('admin_enqueue_scripts', array($this, 'add_pointers'), 1000);
			add_action('admin_print_footer_scripts', array($this, 'add_scripts'));
		}

		/**
		 * Register the available pointers for the current screen
		 */
		public function register_pointers($pointers){
			$screen_pointers = NULL;
			foreach($pointers as $ptr){
				if( $ptr['screen'] == $this->screen_id ){
					$options = array(
						'content'  => sprintf(
							'<h3> %s </h3> <p> %s </p>',
							$ptr['title'],
							$ptr['content']
						),
						'position' => $ptr['position']
					);
					$screen_pointers[ $ptr['id'] ] = array(
						'screen'  => $ptr['screen'],
						'target'  => $ptr['target'],
						'options' => $options
					);
				}
			}
			$this->pointers = $screen_pointers;
		}

		/**
		 * Add pointers to the current screen if they were not dismissed
		 */
		public function add_pointers(){
			if( ! $this->pointers || ! is_array($this->pointers) )
				return;
			// Get dismissed pointers
			$get_dismissed = get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true);
			$dismissed = explode(',', (string)$get_dismissed);
			// Check pointers and remove dismissed ones.
			$valid_pointers = array();
			foreach($this->pointers as $pointer_id => $pointer){
				if(
					in_array($pointer_id, $dismissed)
					|| empty($pointer)
					|| empty($pointer_id)
					|| empty($pointer['target'])
					|| empty($pointer['options'])
				)
					continue;
				$pointer['pointer_id'] = $pointer_id;
				$valid_pointers['pointers'][] = $pointer;
			}
			if( empty($valid_pointers) )
				return;
			$this->valid = $valid_pointers;
			wp_enqueue_style('wp-pointer');
			wp_enqueue_script('wp-pointer');
		}

		/**
		 * Print JavaScript if pointers are available
		 */
		public function add_scripts(){
			if( empty($this->valid) )
				return;
			$pointers = json_encode($this->valid);
			?>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function( $ ){
					var WPHelpPointer = <?php echo $pointers ?>;
					$.each(WPHelpPointer.pointers, function( i ){
						wp_help_pointer_open(i);
					});

					function wp_help_pointer_open( i ){
						pointer = WPHelpPointer.pointers[i];
						$(pointer.target).pointer(
							{
								content: pointer.options.content,
								position:
									{
										edge: pointer.options.position.edge,
										align: pointer.options.position.align
									},
								close: function(){
									$.post(ajaxurl,
										{
											pointer: pointer.pointer_id,
											action: 'dismiss-wp-pointer'
										});
								}
							}).pointer('open');
					}
				});
				//]]>
			</script>
			<?php
		}

	}
}