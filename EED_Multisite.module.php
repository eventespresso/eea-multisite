<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}
/*
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version		$VID:$
 *
 * ------------------------------------------------------------------------
 */

/**
 * Class  EED_Multisite
 *
 * @package			Event Espresso
 * @subpackage		espresso-multisite
 * @author 				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EED_Multisite extends EED_Module {

	/**
	 * @var 		bool
	 * @access 	public
	 */
	public static $shortcode_active = FALSE;

	/**
	 * 	set_hooks - for hooking into EE Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks() {
		EE_Config::register_route( 'multisite', 'EED_Multisite', 'run' );
		add_action( 'wp_loaded', array( 'EED_Multisite', 'update_last_requested' ) );

		self::set_hooks_both();
	}



	/**
	 * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks_admin() {

		self::set_hooks_both();

		//true admin-only hooks
//		if( ! EE_Maintenance_Mode::instance()->models_can_query() ){
//			add_filter('FHEE__EE_Admin_Page_Loader___get_installed_pages__installed_refs', array('EED_Multisite','show_multisite_admin_in_mm'), 110 );
//		}
		add_action('network_admin_notices',array('EED_Multisite','check_network_maintenance_mode'));
		add_action('network_admin_notices',array('EED_Multisite','check_main_blog_maintenance_mode'));

		//filter the existing maintenance mode messages in EE core
		add_filter( 'FHEE__Maintenance_Admin_Page_Init__check_maintenance_mode__notice', array( 'EED_Multisite', 'check_main_blog_maintenance_mode' ), 10 );
	}

	public static function show_multisite_admin_in_mm( $admin_page_folder_names){
		$admin_page_folder_names[ 'multisite' ] = EE_MULTISITE_ADMIN;
		return $admin_page_folder_names;
	}



	protected static function set_hooks_both() {
		add_action( 'AHEE__EE_Data_Migration_Manager__check_for_applicable_data_migration_scripts__scripts_that_should_run', array( 'EED_Multisite', 'mark_blog_as_up_to_date_if_no_migrations_needed' ), 10, 1 );
	}

	/**
	 * Checks if there are no migrations needed on a particular site, then we can mark it as being up-to-date right?
	 * @param EE_Data_Migration_Script_Base[] $migration_scripts_needed
	 */
	public static function mark_blog_as_up_to_date_if_no_migrations_needed( $migration_scripts_needed) {
		if( empty( $migration_scripts_needed ) ){
			EEM_Blog::instance()->mark_current_blog_as_up_to_date();
		}
	}

	/**
	 * Checks if we're in maintenance mode, and if so we notify the admin adn tell them how to take the site OUT of maintenance mode
	 */
	public static function check_network_maintenance_mode(){
		if( EE_Maintenance_Mode::instance()->level() != EE_Maintenance_Mode::level_2_complete_maintenance ){
			if ( is_network_admin() ) {
				//check that all the blogs are up-to-date
				$blogs_needing_migration = EEM_Blog::instance()->count_blogs_maybe_needing_migration();
				if( $blogs_needing_migration ){
					$network = EE_Admin_Page::add_query_args_and_nonce(array(), EE_MULTISITE_ADMIN_URL);
						echo '<div class="error">
						<p>'. sprintf(__('A change has been detected to your Event Espresso plugin or addons. Blogs on your network may require migration. %1$sClick here to check%2$s', "event_espresso"),"<a href='$network'>","</a>").
					'</div>';
				}
			}
		}
	}






	public static function check_main_blog_maintenance_mode( $notice = ''){
		$new_notice = '';
		if( EE_Maintenance_Mode::instance()->level() == EE_Maintenance_Mode::level_2_complete_maintenance ){
			$maintenance_page_url = EE_Admin_Page::add_query_args_and_nonce(array(), EE_MAINTENANCE_ADMIN_URL);
			if ( is_main_site() ) {
					$new_notice = '<div class="error">
					<p>'. sprintf(__('Your main site\'s Event Espresso data is out of date %1$sand needs to be migrated.%2$s After doing this, you should check that the other blogs on your network are up-to-date.', "event_espresso"),"<a href='$maintenance_page_url'>","</a>").
				'</div>';
			 } else {
				$new_notice = '<div class="error">
				<p>' . __('Your event site is in the process of being updated and is currently in maintainance mode.  It has been bumped to the front of the queue and you should be able to have full access again in about 5 minutes.', 'event_espresso' ) . '</p>' .
				'</div>';
			}
		}

		if ( ! empty( $notice ) ) {
			$notice = $new_notice;
			return $new_notice;
		} else {
			$notice = $new_notice;
			echo $notice;
		}
	}



	/**
	 * Run on frontend requests to update when the blog was last updated
	 */
	public static function update_last_requested() {
		global $current_site;
		$current_blog_id = get_current_blog_id();
		switch_to_blog( $current_site->blog_id );
		EEM_Blog::instance()->update_last_requested( $current_blog_id );
		restore_current_blog();
	}



	/**
	 * Similar to wp's switch_to_blog(), but also reset
	 * a few EE singletons that need to be
	 * reset too
	 * @param int $new_blog_id
	 * @param int $old_blog_id
	 */
	public static function switch_to_blog( $new_blog_id ) {
		switch_to_blog( $new_blog_id );
		EE_Registry::reset();
		EE_System::reset();
		EE_Multisite::reset();
	}



	/**
	 * The same as wp's restore_current_blog(), but also takes care of restoring
	 * a few EE-speicifc singletons
	 */
	public static function restore_current_blog() {
		restore_current_blog();
		EE_Registry::reset();
		EE_System::reset();
		EE_Multisite::reset();
	}



	/**
	 *    config
	 *
	 * @return EE_Multisite_Config
	 */
	public function config() {
		// config settings are setup up individually for EED_Modules via the EE_Configurable class that all modules inherit from, so
		// $this->config();  can be used anywhere to retrieve it's config, and:
		// $this->_update_config( $EE_Config_Base_object ); can be used to supply an updated instance of it's config object
		// to piggy back off of the config setup for the base EE_Multisite class, just use the following (note: updates would have to occur from within that class)
		return EE_Registry::instance()->addons->EE_Multisite->config();
	}



	/**
	 *    run - initial module setup
	 *
	 * @access    public
	 * @param  WP $WP
	 * @return    void
	 */
	public function run( $WP ) {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}



	/**
	 * 	enqueue_scripts - Load the scripts and css
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function enqueue_scripts() {
		//Check to see if the multisite css file exists in the '/uploads/espresso/' directory
		if ( is_readable( EVENT_ESPRESSO_UPLOAD_DIR . "css/multisite.css" ) ) {
			//This is the url to the css file if available
			wp_register_style( 'espresso_multisite', EVENT_ESPRESSO_UPLOAD_URL . 'css/espresso_multisite.css' );
		} else {
			// EE multisite style
			wp_register_style( 'espresso_multisite', EE_MULTISITE_URL . 'css/espresso_multisite.css' );
		}
		// multisite script
		wp_register_script( 'espresso_multisite', EE_MULTISITE_URL . 'scripts/espresso_multisite.js', array( 'jquery' ), EE_MULTISITE_VERSION, TRUE );

		// is the shortcode or widget in play?
		if ( EED_Multisite::$shortcode_active ) {
			wp_enqueue_style( 'espresso_multisite' );
			wp_enqueue_script( 'espresso_multisite' );
		}
	}


	/**
	 * 		@ override magic methods
	 * 		@ return void
	 */
	public function __set( $a, $b ) {
		return FALSE;
	}



	public function __get( $a ) {
		return FALSE;
	}



	public function __isset( $a ) {
		return FALSE;
	}



	public function __unset( $a ) {
		return FALSE;
	}



	public function __clone() {
		return FALSE;
	}



	public function __wakeup() {
		return FALSE;
	}



	public function __destruct() {
		return FALSE;
	}



}

// End of file EED_Multisite.module.php
// Location: /wp-content/plugins/espresso-multisite/EED_Multisite.module.php
