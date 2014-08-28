<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'NO direct script access allowed' );
}

/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @package		Event Espresso
 * @author			Event Espresso
 * @copyright 	(c) 2009-2014 Event Espresso All Rights Reserved.
 * @license			http://eventespresso.com/support/terms-conditions/  ** see Plugin Licensing **
 * @link				http://www.eventespresso.com
 * @version			EE4
 *
 * ------------------------------------------------------------------------
 *
 * Multisite_Admin_Page
 *
 * This contains the logic for setting up the Multisite Addon Admin related pages.  Any methods without PHP doc comments have inline docs with parent class.
 *
 *
 * @package			Multisite_Admin_Page (multisite addon)
 * @subpackage 	admin/Multisite_Admin_Page.core.php
 * @author				Darren Ethier, Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class Multisite_Admin_Page extends EE_Admin_Page {

	protected function _init_page_props() {
		$this->page_slug = MULTISITE_PG_SLUG;
		$this->page_label = MULTISITE_LABEL;
		$this->_admin_base_url = EE_MULTISITE_ADMIN_URL;
		$this->_admin_base_path = EE_MULTISITE_ADMIN;
	}



	protected function _ajax_hooks() {
		add_action( 'wp_ajax_multisite_migration_assessment_step', array( $this, 'assessing_sites_needing_migration' ) );
		add_action( 'wp_ajax_multisite_migration_step', array( $this, 'migrating' ) );
		add_action( 'wp_ajax_multisite_migration_error', array( $this, 'migration_error' ) );
	}



	protected function _define_page_props() {
		$this->_admin_page_title = MULTISITE_LABEL;
		$this->_labels = array(
			'publishbox' => __( 'Update Settings', 'event_espresso' )
		);
	}



	protected function _set_page_routes() {
		$this->_page_routes = array(
			'default' => '_migration_page',
			'settings' => '_basic_settings',
			'update_settings' => array(
				'func' => '_update_settings',
				'noheader' => TRUE
			),
			'force_reassess' => array(
				'func' => '_force_reassess',
				'noheader' => TRUE
			),
			'usage' => '_usage'
		);
	}



	protected function _set_page_config() {

		$this->_page_config = array(
			'default' => array(
				'nav' => array(
					'label' => __( 'Settings', 'event_espresso' ),
					'order' => 10
				),
				'metaboxes' => array_merge( $this->_default_espresso_metaboxes, array( '_publish_post_box' ) ),
				'require_nonce' => FALSE
			),
			'usage' => array(
				'nav' => array(
					'label' => __( 'Multisite Usage', 'event_espresso' ),
					'order' => 30
				),
				'require_nonce' => FALSE
			)
		);
	}



	protected function _add_screen_options() {

	}



	protected function _add_screen_options_default() {

	}



	protected function _add_feature_pointers() {

	}



	public function load_scripts_styles() {
		wp_enqueue_script( 'ee_admin_js' );
		wp_register_script( 'espresso_multisite_admin', EE_MULTISITE_ADMIN_ASSETS_URL . 'espresso_multisite_admin.js', array( 'espresso_core', 'ee-dialog', ), EE_MULTISITE_VERSION, TRUE );
		wp_enqueue_script( 'espresso_multisite_admin' );

		EE_Registry::$i18n_js_strings[ 'confirm_reset' ] = __( 'Are you sure you want to reset ALL your Event Espresso Multisite Information? This cannot be undone.', 'event_espresso' );
		wp_localize_script( 'espresso_multisite_admin', 'eei18n', EE_Registry::$i18n_js_strings );


		//steal the styles etc from the normal maintenance page
		wp_register_style( 'espresso_multisite_migration', EE_MULTISITE_ADMIN_ASSETS_URL . 'espresso_multisite_admin.css', array( ), EE_MULTISITE_VERSION );
		wp_enqueue_style( 'espresso_multisite_migration' );
	}



	public function admin_init() {

	}



	public function admin_notices() {

	}



	public function admin_footer_scripts() {

	}



	protected function _migration_page() {
		EE_Registry::instance()->load_helper( 'Form_Fields' );
		if( EE_Maintenance_Mode::instance()->models_can_query() ){
			$this->_template_path = EE_MULTISITE_ADMIN_TEMPLATE_PATH . 'multisite_migration.template.php';

			$this->_template_args[ 'reassess_url' ] = EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'force_reassess' ), EE_MULTISITE_ADMIN_URL );
			$this->_set_add_edit_form_tags( 'update_settings' );
			$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
			$this->_template_args[ 'admin_page_content' ] = EEH_Template::display_template( $this->_template_path, $this->_template_args, TRUE );
			wp_localize_script( 'espresso_multisite_admin', 'ee_i18n_text', array(
				'done_assessment' => __( 'Assessment Complete', 'event_espresso' ),
				'network_needs_migration' => __( 'Network requires migration', 'event_espresso' ),
				'no_migrations_required' => __( 'No migrations are required', 'event_espresso' ),
				'ajax_error' => __( 'An error occurred communicating with the server. Please contact support', 'event_espresso' ),
				'all_done' => __( 'All done migrating network', 'event_espresso' )
			) );
			$this->display_admin_page_with_sidebar();
		}else{
			$migration_page = get_admin_url( get_current_blog_id(), 'admin.php?page=espresso_maintenance_settings' );
			$this->_template_args['admin_page_content'] = EEH_Template::display_template( EE_MULTISITE_ADMIN_TEMPLATE_PATH . 'multisite_migration_in_mm.template.php', array('migration_page_url' => $migration_page), TRUE);
//			$this->_template_args['whatever'] ='';
//			$this->_set_add_edit_form_tags( 'update_settings' );
//			$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
//
//		$this->_template_args[ 'reassess_url' ] = EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'force_reassess' ), EE_MULTISITE_ADMIN_URL );
			$this->display_admin_page_with_sidebar();
		}
	}



	protected function _basic_settings() {
		$this->_settings_page( 'multisite_basic_settings.template.php' );
	}



	/**
	 * _settings_page
	 * @param $template
	 */
	protected function _settings_page( $template ) {
		EE_Registry::instance()->load_helper( 'Form_Fields' );
		$this->_template_args[ 'multisite_config' ] = EE_Config::instance()->get_config( 'addons', 'EED_Espresso_Multisite', 'EE_Multisite_Config' );
		add_filter( 'FHEE__EEH_Form_Fields__label_html', '__return_empty_string' );
		$this->_template_args[ 'yes_no_values' ] = array(
			EE_Question_Option::new_instance( array( 'QSO_value' => 0, 'QSO_desc' => __( 'No', 'event_espresso' ) ) ),
			EE_Question_Option::new_instance( array( 'QSO_value' => 1, 'QSO_desc' => __( 'Yes', 'event_espresso' ) ) )
		);

		$this->_template_args[ 'return_action' ] = $this->_req_action;
		$this->_template_args[ 'reset_url' ] = EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'reset_settings', 'return_action' => $this->_req_action ), EE_MULTISITE_ADMIN_URL );
		$this->_set_add_edit_form_tags( 'update_settings' );
		$this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
		$this->_template_args[ 'admin_page_content' ] = EEH_Template::display_template( EE_MULTISITE_ADMIN_TEMPLATE_PATH . $template, $this->_template_args, TRUE );
		$this->display_admin_page_with_sidebar();
	}



	protected function _usage() {
		$this->_template_args[ 'admin_page_content' ] = EEH_Template::display_template( EE_MULTISITE_ADMIN_TEMPLATE_PATH . 'multisite_usage_info.template.php', array( ), TRUE );
		$this->display_admin_page_with_no_sidebar();
	}



	protected function _update_settings() {
		EE_Registry::instance()->load_helper( 'Class_Tools' );
		if ( isset( $_POST[ 'reset_multisite' ] ) && $_POST[ 'reset_multisite' ] == '1' ) {
			$config = new EE_Multisite_Config();
			$count = 1;
		} else {
			$config = EE_Config::instance()->get_config( 'addons', 'EED_Espresso_Multisite', 'EE_Multisite_Config' );
			$count = 0;
			//otherwise we assume you want to allow full html
			foreach ( $this->_req_data[ 'multisite' ] as $top_level_key => $top_level_value ) {
				if ( is_array( $top_level_value ) ) {
					foreach ( $top_level_value as $second_level_key => $second_level_value ) {
						if ( EEH_Class_Tools::has_property( $config, $top_level_key ) && EEH_Class_Tools::has_property( $config->$top_level_key, $second_level_key ) && $second_level_value != $config->$top_level_key->$second_level_key ) {
							$config->$top_level_key->$second_level_key = $this->_sanitize_config_input( $top_level_key, $second_level_key, $second_level_value );
							$count++;
						}
					}
				} else {
					if ( EEH_Class_Tools::has_property( $config, $top_level_key ) && $top_level_value != $config->$top_level_key ) {
						$config->$top_level_key = $this->_sanitize_config_input( $top_level_key, NULL, $top_level_value );
						$count++;
					}
				}
			}
		}
		EE_Config::instance()->update_config( 'addons', 'EED_Espresso_Multisite', $config );
		$this->_redirect_after_action( $count, 'Settings', 'updated', array( 'action' => $this->_req_data[ 'return_action' ] ) );
	}



	/**
	 * resets the multisite data and redirects to where they came from
	 */
//	protected function _reset_settings(){
//		EE_Config::instance()->addons['multisite'] = new EE_Multisite_Config();
//		EE_Config::instance()->update_espresso_config();
//		$this->_redirect_after_action(1, 'Settings', 'reset', array('action' => $this->_req_data['return_action']));
//	}
	private function _sanitize_config_input( $top_level_key, $second_level_key, $value ) {
		$sanitization_methods = array(
			'display' => array(
				'enable_multisite' => 'bool',
//				'multisite_height'=>'int',
//				'enable_multisite_filters'=>'bool',
//				'enable_category_legend'=>'bool',
//				'use_pickers'=>'bool',
//				'event_background'=>'plaintext',
//				'event_text_color'=>'plaintext',
//				'enable_cat_classes'=>'bool',
//				'disable_categories'=>'bool',
//				'show_attendee_limit'=>'bool',
			)
		);
		$sanitization_method = NULL;
		if ( isset( $sanitization_methods[ $top_level_key ] ) &&
				$second_level_key === NULL &&
				!is_array( $sanitization_methods[ $top_level_key ] ) ) {
			$sanitization_method = $sanitization_methods[ $top_level_key ];
		} elseif ( is_array( $sanitization_methods[ $top_level_key ] ) && isset( $sanitization_methods[ $top_level_key ][ $second_level_key ] ) ) {
			$sanitization_method = $sanitization_methods[ $top_level_key ][ $second_level_key ];
		}
//		echo "$top_level_key [$second_level_key] with value $value will be sanitized as a $sanitization_method<br>";
		switch ( $sanitization_method ) {
			case 'bool':
				return ( boolean ) intval( $value );
			case 'plaintext':
				return wp_strip_all_tags( $value );
			case 'int':
				return intval( $value );
			case 'html':
				return $value;
			default:
				$input_name = $second_level_key == NULL ? $top_level_key : $top_level_key . "[" . $second_level_key . "]";
				EE_Error::add_error( sprintf( __( "Could not sanitize input '%s' because it has no entry in our sanitization methods array", "event_espresso" ), $input_name ) );
				return NULL;
		}
	}



	/**
	 * forces EE to re-assess which sites are up-to-date and which need migration
	 */
	public function _force_reassess() {
		$count = EEM_Blog::instance()->mark_all_blogs_migration_status_as_unsure();
		$this->_redirect_after_action( $count, 'Blogs', 'migration status updated', array( 'action' => 'default' ) );
	}



	/**
	 * receives AJAX request to assess how many sites need to be migrated
	 */
	public function assessing_sites_needing_migration() {
		$original_unknown_status_blog_count = EEM_Blog::instance()->count_blogs_maybe_needing_migration();
		if ( $original_unknown_status_blog_count ) {
			//ok we still don't even know how many need to be migrated
			$step_size = max( 1, defined( 'EE_MIGRATION_STEP_SIZE' ) ? EE_MIGRATION_STEP_SIZE / 10 : 5  );
			$newly_found_needing_migration_count = EE_Multisite_Migration_Manager::instance()->assess_sites_needing_migration( $step_size );
		}
		$this->_template_args[ 'data' ] = array(
			'total_blogs' => EEM_Blog::instance()->count(),
			'up_to_date_blogs' => EEM_Blog::instance()->count_blogs_up_to_date(),
			'unknown_status_blogs' => EEM_Blog::instance()->count_blogs_maybe_needing_migration(),
			'out_of_date_blogs' => EEM_Blog::instance()->count_blogs_needing_migration(),
			'borked_blogs' => EEM_Blog::instance()->count_borked_blogs(),
		);
		$this->_return_json();
	}



	/**
	 * Receives AJAX requests from client, which first:
	 * assesses how many blogs are out of date, (by switching to them then checking for migration scripts)
	 * THEN grabs one of those blogs needing migration and migrates it (by swithcing to it and doing the normal migration step).
	 * Both of these tasks are done in small units in order to avoid timeouts
	 *
	 */
	public function migrating() {
		//we know how many need to be migrated. so let's do that
		$step_size = defined( 'EE_MIGRATION_STEP_SIZE' ) ? EE_MIGRATION_STEP_SIZE * 10 : 500;
		$migration_status = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		$blogs_left = EEM_Blog::instance()->count();
		if( $blogs_left == 0 ){
			$migration_status[ 'current_blog_name' ] = '';
			$migration_status[ 'current_blog_script_names' ] = array();
			$migration_status[ 'message' ] = __( 'All blogs up-to-date', 'event_espresso' );
		}
		$migration_status[ 'blogs_total' ] = $blogs_left;
		$migration_status[ 'blogs_needing_migration' ] = EEM_Blog::instance()->count_blogs_needing_migration();
		$this->_template_args[ 'data' ] = $migration_status;
		$this->_return_json();
	}

	public function migration_error(){
		//our last ajax response didn't send proper JSON
		//probably because of a fatal error or something
		//so update the last blog as borked
		//and ask its data migration manager to log the error
		$blog_migrating = EEM_Blog::instance()->get_migrating_blog_or_most_recently_requested();
		$blog_migrating->set_STS_ID( EEM_Blog::status_borked );
		$blog_migrating->save();
		EED_Multisite::switch_to_blog($blog_migrating->ID());
		EE_Data_Migration_Manager::instance()->add_error_to_migrations_ran( $this->_req_data[ 'message' ] );
		EED_Multisite::restore_current_blog();
		wp_mail( get_site_option( 'admin_email' ), sprintf( __( 'General error running multisite migration. Last ran blog was: %s', 'event_espresso' ), $blog_migrating->name() ), sprintf( __( 'Did not receive proper JSON response while running multisite migration. This was the response: %s', 'event_espresso' ), $this->_req_data[ 'message' ] ) );
	}



}

// End of file Multisite_Admin_Page.core.php
// Location: /wp-content/plugins/espresso-multisite/admin/multisite/Multisite_Admin_Page.core.php