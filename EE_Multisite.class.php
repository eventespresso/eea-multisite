<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit();
}
/**
 * ------------------------------------------------------------------------
 *
 * Class  EE_Multisite
 *
 * @package			Event Espresso
 * @subpackage		espresso-multisite
 * @author			    Brent Christensen
 * @ version		 	$VID:$
 *
 * ------------------------------------------------------------------------
 */
// define the plugin directory path and URL
define( 'EE_MULTISITE_BASENAME', plugin_basename( EE_MULTISITE_PLUGIN_FILE ) );
define( 'EE_MULTISITE_PATH', plugin_dir_path( __FILE__ ) );
define( 'EE_MULTISITE_URL', plugin_dir_url( __FILE__ ) );
define( 'EE_MULTISITE_ADMIN', EE_MULTISITE_PATH . 'admin' . DS . 'multisite' . DS );

Class EE_Multisite extends EE_Addon {


	public static function register_addon() {
		//add_filter( 'FHEE__EEM_Status__construct__status_types', array('EE_Multisite', 'add_blog_stati_types') );
		$registration_params = array(
			'version' => EE_MULTISITE_VERSION,
			'min_core_version' => '4.6.0.dev.000',
			'main_file_path' => EE_MULTISITE_PLUGIN_FILE,
			'admin_path' => EE_MULTISITE_ADMIN,
			'admin_callback' => 'additional_admin_hooks',
			'config_class' => 'EE_Multisite_Config',
			'config_name' => 'EE_Multisite',
			'autoloader_paths' => array(
				'EE_Multisite' => EE_MULTISITE_PATH . 'EE_Multisite.class.php',
				'EE_Multisite_Config' => EE_MULTISITE_PATH . 'EE_Multisite_Config.php',
				'Multisite_Admin_Page' => EE_MULTISITE_ADMIN . 'Multisite_Admin_Page.core.php',
				'Multisite_Admin_Page_Init' => EE_MULTISITE_ADMIN . 'Multisite_Admin_Page_Init.core.php',
				'EE_Multisite_Migration_Manager' => EE_MULTISITE_PATH . 'EE_Multisite_Migration_Manager.php',
			),
			'module_paths' => array( EE_MULTISITE_PATH . 'EED_Multisite.module.php' ),
//			'shortcode_paths' => array( EE_MULTISITE_PATH . 'EES_Multisite.shortcode.php' ),
//			'widget_paths' => array( EE_MULTISITE_PATH . 'EEW_Multisite.widget.php' ),
			// if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
			'pue_options' => array(
				'pue_plugin_slug' => 'espresso_multisite',
				'plugin_basename' => EE_MULTISITE_BASENAME,
				'checkPeriod' => '24',
				'use_wp_update' => FALSE,
			),
			'model_paths' => array( EE_MULTISITE_PATH . 'core/db_models' ),
			'class_paths' => array( EE_MULTISITE_PATH . 'core/db_classes' ),
		);
		//only register the DMS if on the main site. This way we avoid adding tables, and trying to remove tables,
		//from blogs which aren't the main one
		if ( is_main_site() ) {
			$registration_params[ 'dms_paths' ] = array( EE_MULTISITE_PATH . 'core' . DS . 'data_migration_scripts' . DS );
		}
		// register addon via Plugin API
		EE_Register_Addon::register( 'Multisite', $registration_params );
	}



	/**
	 * Adds 'blog' as a valid status on the EEM_Status's field 'STS_type'.
	 * However, we'd also need to modify the database column to allow 'blog' in the set
	 * in order for this to properly work. For now this isn't used.
	 * @param array $valid_stati_types
	 * @return array
	 */
	public static function add_blog_stati_types( $valid_stati_types ) {
		$valid_stati_types[ 'blog' ] = __( 'Blog', 'event_espresso' );
		return $valid_stati_types;
	}



	/**
	 * 	additional_admin_hooks
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function additional_admin_hooks() {
		// is admin and not in M-Mode ?
		if ( is_admin() && is_network_admin() ) {
			add_filter( 'network_admin_plugin_action_links', array( $this, 'plugin_actions' ), 10, 2 );
		}
	}



	/**
	 * plugin_actions
	 *
	 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
	 * @param $links
	 * @param $file
	 * @return array
	 */
	public function plugin_actions( $links, $file ) {
		if ( $file == EE_MULTISITE_BASENAME ) {


			// before other links
			array_unshift( $links, '<a href="admin.php?page=espresso_multisite">' . __( 'Settings' ) . '</a>' );
		}
		return $links;
	}



}

// End of file EE_Multisite.class.php
// Location: wp-content/plugins/espresso-multisite/EE_Multisite.class.php
