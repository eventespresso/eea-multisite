<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
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
		 // ajax hooks
		 add_action( 'wp_ajax_get_multisite', array( 'EED_Multisite', '_get_multisite' ));
		 add_action( 'wp_ajax_nopriv_get_multisite', array( 'EED_Multisite', '_get_multisite' ));
		 self::set_hooks_both();
	 }

	 protected static function set_hooks_both(){
		 //set hooks for detecting an upgrade to EE or an addon
		 $actions_that_could_change_mm = array(
			 'AHEE__EE_System__detect_if_activation_or_upgrade__new_activation',
			 'AHEE__EE_System__detect_if_activation_or_upgrade__new_activation_but_not_installed',
			 'AHEE__EE_System__detect_if_activation_or_upgrade__reactivation',
			 'AHEE__EE_System__detect_if_activation_or_upgrade__upgrade',
			 'AHEE__EE_System__detect_if_activation_or_upgrade__downgrade'
		 );
		 foreach( array_keys( get_object_vars( EE_Registry::instance()->addons ) ) as $addon_classname ){
			$actions_that_could_cause_mm_from_addon = array(
					   "AHEE__{$addon_classname}__detect_activations_or_upgrades__new_activation",
					   "AHEE__{$addon_classname}__detect_activations_or_upgrades__new_activation_but_not_installed",
					   "AHEE__{$addon_classname}__detect_activations_or_upgrades__reactivation",
					   "AHEE__{$addon_classname}__detect_activations_or_upgrades__upgrade",
					   "AHEE__{$addon_classname}__detect_activations_or_upgrades__downgrade");
			$actions_that_could_change_mm = array_merge( $actions_that_could_change_mm, $actions_that_could_cause_mm_from_addon );
		 }
		 foreach( $actions_that_could_change_mm as $action_name ){
			add_action( $action_name, array('EED_Multisite', 'possible_maintenance_mode_change_detected' ) );
		 }
	 }

	 /**
	  * Called when maintenance mode made have been set or unset
	  *
	  * This is usually a good point to mark all blogs as status 'unsure'
	  * in regards to their migration needs
	  */
	 public function possible_maintenance_mode_change_detected(){
		 /* only mark blogs as unsure migration status when the main site has a possible
		  * change to maintenance mode. Otherwise, as an example, when a new version of
		  * EE is activated, this will occur again for EACH blog
		  */
		 if( is_main_site() ){
			EEM_Blog::instance()->mark_all_blogs_migration_status_as_unsure();
		 }
	 }

	 /**
	  * Run on frontend requests to update when the blog was last updated
	  */
	 public static function update_last_requested(){
		 global $current_site;
		 $current_blog_id = get_current_blog_id();
		 switch_to_blog( $current_site->blog_id );
		 EEM_Blog::instance()->update_last_requested( $current_blog_id );
		 restore_current_blog();
	 }




	/**
	 *    config
	 *
	 * @return EE_Multisite_Config
	 */
	public function config(){
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
		 add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));
	 }






	/**
	 * 	enqueue_scripts - Load the scripts and css
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function enqueue_scripts() {
		//Check to see if the multisite css file exists in the '/uploads/espresso/' directory
		if ( is_readable( EVENT_ESPRESSO_UPLOAD_DIR . "css/multisite.css")) {
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
	 *		@ override magic methods
	 *		@ return void
	 */
	public function __set($a,$b) { return FALSE; }
	public function __get($a) { return FALSE; }
	public function __isset($a) { return FALSE; }
	public function __unset($a) { return FALSE; }
	public function __clone() { return FALSE; }
	public function __wakeup() { return FALSE; }
	public function __destruct() { return FALSE; }

 }
// End of file EED_Multisite.module.php
// Location: /wp-content/plugins/espresso-multisite/EED_Multisite.module.php
