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

	/**
	 * Cache for _default_creator_id.
	 * Gets reset on switch/reset blog.
	 *
	 * @var int
	 */
	protected static $_default_creator_id = null;


	public static function register_addon() {
		$registration_params = array(
			'version' => EE_MULTISITE_VERSION,
			'min_core_version' => EE_MULTISITE_CORE_VERSION_REQUIRED,
			'main_file_path' => EE_MULTISITE_PLUGIN_FILE,
			'admin_path' => EE_MULTISITE_ADMIN,
			'admin_callback' => 'additional_admin_hooks',
			'config_class' => 'EE_Multisite_Config',
			'config_name' => 'ee_multisite',
			'autoloader_paths' => array(
				'EE_Multisite' => EE_MULTISITE_PATH . 'EE_Multisite.class.php',
				'EE_Multisite_Config' => EE_MULTISITE_PATH . 'EE_Multisite_Config.php',
				'Multisite_Admin_Page' => EE_MULTISITE_ADMIN . 'Multisite_Admin_Page.core.php',
				'Multisite_Admin_Page_Init' => EE_MULTISITE_ADMIN . 'Multisite_Admin_Page_Init.core.php',
				'EE_Multisite_Migration_Manager' => EE_MULTISITE_PATH . 'EE_Multisite_Migration_Manager.php',
				'EventEspressoBatchRequest\JobHandlers\MultisiteMigration' => EE_MULTISITE_PATH . 'job_handlers' . DS . 'MultisiteMigration.php'
			),
			'module_paths' => array(
				EE_MULTISITE_PATH . 'EED_Multisite.module.php',
				EE_MULTISITE_PATH . 'EED_Multisite_Site_List_Table.module.php'
			),
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
		//autoload device detector
		EE_Psr4AutoloaderInit::psr4_loader()->addNamespace( 'DeviceDetector', EE_MULTISITE_PATH . 'core' . DS . 'libraries' . DS . 'device-detector-master' );
		// register addon via Plugin API
		EE_Register_Addon::register( 'Multisite', $registration_params );
		self::set_early_hooks();
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

	/**
	 * sets hooks that need to be set quite early, before modules are initialized (so couldn't be placed
	 * in a module).
	 * Mostly these hooks are used when EE_System::detect_if_activation_or_upgrade() is ran
	 */
	public static  function set_early_hooks(){
		//set hooks for detecting an upgrade to EE or an addon
		$actions_that_could_change_mm = array(
			'AHEE__EE_System__detect_if_activation_or_upgrade__new_activation',
			'AHEE__EE_System__detect_if_activation_or_upgrade__new_activation_but_not_installed',
			'AHEE__EE_System__detect_if_activation_or_upgrade__reactivation',
			'AHEE__EE_System__detect_if_activation_or_upgrade__upgrade',
			'AHEE__EE_System__detect_if_activation_or_upgrade__downgrade',
			"AHEE__EE_Addon__detect_activations_or_upgrades__new_activation",
			"AHEE__EE_Addon__detect_activations_or_upgrades__new_activation_but_not_installed",
			"AHEE__EE_Addon__detect_activations_or_upgrades__reactivation",
			"AHEE__EE_Addon__detect_activations_or_upgrades__upgrade",
			"AHEE__EE_Addon__detect_activations_or_upgrades__downgrade"
		);
		foreach ( $actions_that_could_change_mm as $action_name ) {
			add_action( $action_name, array( 'EE_Multisite', 'possible_maintenance_mode_change_detected' ) );
		}

		//a very specific hook for when running the EE_DMS_Core_4_5_0
		add_filter( 'FHEE__EEH_Activation__get_default_creator_id__pre_filtered_id', array( 'EE_Multisite', 'filter_get_default_creator_id' ) );
	}

	/**
	 * Called when maintenance mode made have been set or unset
	 *
	 * This is usually a good point to mark all blogs as status 'unsure'
	 * in regards to their migration needs
	 */
	public static  function possible_maintenance_mode_change_detected() {
		/* only mark blogs as unsure migration status when the main site has a possible
		 * change to maintenance mode. Otherwise, as an example, when a new version of
		 * EE is activated, this will occur again for EACH blog
		 */
		if ( is_main_site() ) {
			EEM_Blog::instance()->mark_all_blogs_migration_status_as_unsure();
		}
	}

	/**
	 * resets whatever state was stored on EED_Multisite
	 */
	public static function reset(){
		self::$_default_creator_id = NULL;
	}

	/**
	 * When running the EE_DMS_Core_4_5_0 migration, user each blog admin's ID,
	 * not the network admin's
	 * @global type $wpdb
	 * @param type $network_admin_id
	 * @return int
	 */
	public static function filter_get_default_creator_id( $network_admin_id ) {

		if ( $user_id = self::get_default_creator_id() ) {
			return $user_id;
		} else {
			return $network_admin_id;
		}
	}
	/**
	 * Tries to find the oldest admin for this blog. If there are no admins for this blog,
	 * then we return NULL
	 * @global type $wpdb
	 * @return int WP_User ID
	 */
	public static function get_default_creator_id() {
		if ( !empty( self::$_default_creator_id ) ) {
			return self::$_default_creator_id;
		}

		//find the earliest admin id for the current blog
		global $wpdb;
		$offset = 0;

		$role_to_check = apply_filters( 'FHEE__EE_Multisite__get_default_creator_id__role_to_check', 'administrator' );
		do{
			$query = $wpdb->prepare( "SELECT user_id from {$wpdb->usermeta} WHERE meta_key='primary_blog' AND meta_value=%s ORDER BY user_id ASC LIMIT %d, 1", get_current_blog_id(), $offset++ );

			$user_id = $wpdb->get_var( $query );
		}while( $user_id && ! user_can( $user_id, $role_to_check ) );

		$user_id = apply_filters( 'FHEE__EE_Multisite__get_default_creator_id__user_id', $user_id );
		if ( $user_id && intval( $user_id ) ) {
			self::$_default_creator_id =  intval( $user_id );
			return self::$_default_creator_id;
		} else {
			return NULL;
		}
	}



}

// End of file EE_Multisite.class.php
// Location: wp-content/plugins/espresso-multisite/EE_Multisite.class.php
