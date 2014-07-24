<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit(); }
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
define( 'EE_MULTISITE_PATH', plugin_dir_path( __FILE__ ));
define( 'EE_MULTISITE_URL', plugin_dir_url( __FILE__ ));
define( 'EE_MULTISITE_ADMIN', EE_MULTISITE_PATH . 'admin' . DS . 'multisite' . DS );
Class  EE_Multisite extends EE_Addon {

	/**
	 * class constructor
	 */
	public function __construct() {
	}

	public static function register_addon() {
		// register addon via Plugin API
		EE_Register_Addon::register(
			'Multisite',
			array(
				'version' 					=> EE_MULTISITE_VERSION,
				'min_core_version' => '4.3.0',
				'main_file_path' 				=> EE_MULTISITE_PLUGIN_FILE,
				'admin_path' 			=> EE_MULTISITE_ADMIN,
				'admin_callback'		=> 'additional_admin_hooks',
				'config_class' 			=> 'EE_Multisite_Config',
				'config_name' 		=> 'EE_Multisite',
				'autoloader_paths' => array(
					'EE_Multisite' 						=> EE_MULTISITE_PATH . 'EE_Multisite.class.php',
					'EE_Multisite_Config' 			=> EE_MULTISITE_PATH . 'EE_Multisite_Config.php',
					'Multisite_Admin_Page' 		=> EE_MULTISITE_ADMIN . 'Multisite_Admin_Page.core.php',
					'Multisite_Admin_Page_Init' => EE_MULTISITE_ADMIN . 'Multisite_Admin_Page_Init.core.php',
				),
//				'dms_paths' 			=> array( EE_MULTISITE_PATH . 'core' . DS . 'data_migration_scripts' . DS ),
				'module_paths' 		=> array( EE_MULTISITE_PATH . 'EED_Multisite.module.php' ),
				'shortcode_paths' 	=> array( EE_MULTISITE_PATH . 'EES_Multisite.shortcode.php' ),
				'widget_paths' 		=> array( EE_MULTISITE_PATH . 'EEW_Multisite.widget.php' ),
				// if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
				'pue_options'			=> array(
					'pue_plugin_slug' => 'espresso_multisite',
					'plugin_basename' => EE_MULTISITE_PLUGIN_FILE,
					'checkPeriod' => '24',
					'use_wp_update' => FALSE,
					),
				'capabilities' => array(
					'administrator' => array(
						'read_addon', 'edit_addon', 'edit_others_addon', 'edit_private_addon'
						),
					),
				'capability_maps' => array(
					new EE_Meta_Capability_Map_Edit( 'edit_addon', array( EEM_Event::instance(), '', 'edit_others_addon', 'edit_private_addon' ) )
					),
				'model_paths' => array ( EE_MULTISITE_PATH . 'core/db_models' ),
				'class_paths' => array( EE_MULTISITE_PATH . 'core/db_classes' ),
			)
		);
	}



	/**
	 * 	additional_admin_hooks
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function additional_admin_hooks() {
		// is admin and not in M-Mode ?
		if ( is_admin() && ! EE_Maintenance_Mode::instance()->level() ) {
			add_filter( 'plugin_action_links', array( $this, 'plugin_actions' ), 10, 2 );
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
		if ( $file == EE_MULTISITE_PLUGIN_FILE ) {
			// before other links
			array_unshift( $links, '<a href="admin.php?page=espresso_multisite">' . __('Settings') . '</a>' );
		}
		return $links;
	}






}
// End of file EE_Multisite.class.php
// Location: wp-content/plugins/espresso-multisite/EE_Multisite.class.php
