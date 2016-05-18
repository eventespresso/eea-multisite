<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Multisite_UnitTestCase
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Multisite_UnitTestCase extends EE_UnitTestCase {

	/**
	 * Sets up a blog with the latest EE installed on it
	 * @return EE_Blog
	 */
	protected function _create_a_blog_with_ee() {
		global $wpdb;
		$blog = $this->factory->blog->create_and_get();

		//reset EED_Multisite to remove any records of blogs that had EE_System::reset() run on it in case
		//the same blog_id is used again when created.
		EED_Multisite::reset();
		//allow the creation of these tables, because we know they're temporary
		remove_all_filters( 'FHEE__EEH_Activation__create_table__short_circuit' );

		switch_to_blog( $blog->blog_id );
		//and put the filters back in place
		add_filter( 'FHEE__EEH_Activation__create_table__short_circuit', '__return_true' );
		$this->assertNotEquals( EE_Maintenance_Mode::level_2_complete_maintenance, EE_Maintenance_Mode::instance()->level() );
		$this->assertTableExists( $wpdb->prefix . 'esp_attendee_meta' );
		$this->assertNotEquals( 0, EEM_Status::reset()->count() );

		//also create an admin for this blog
		$admin = $this->factory->user->create_and_get();
		wp_update_user( array(
			'ID' => $admin->ID,
			'role' => 'administrator',
		) );
		update_user_meta( $admin->ID, 'primary_blog', get_current_blog_id() );
		restore_current_blog();

		return EEM_Blog::instance()->get_one_by_ID( $blog->blog_id );
	}

	/**
	 * to pretend EE had an upgrade, we just register a core DMS that applies.
	 * It should be removed during tearDown() by resetting EE_Data_Migration_Manager
	 * and resetting hooks (which are done by EE_UnitTestCase)
	 */
	protected function _pretend_ee_upgraded() {
		//when EE gets upgraded, all the blogs stati are switched to "unsure"
		EEM_Blog::instance()->mark_all_blogs_migration_status_as_unsure();
		$this->_pretend_addon_hook_time();
		EE_Register_Data_Migration_Scripts::register( 'Pretend_Upgrade', array(
			'dms_paths' => array( EE_MULTISITE_PATH . 'tests/mocks/data_migration_scripts/' )
		) );
		$all_dmss = EE_Data_Migration_Manager::reset()->get_all_data_migration_scripts_available();
		$this->assertArrayHasKey( 'EE_DMS_Core_9_9_9', $all_dmss );
		$this->assertArrayHasKey( 'EE_DMS_Core_9_9_10', $all_dmss );
		$applicable_dmss = EE_Data_Migration_Manager::reset()->check_for_applicable_data_migration_scripts();
		$this->assertArrayHasKey( 'EE_DMS_Core_9_9_9', $applicable_dmss );
		EE_Registry::instance()->load_helper('Activation');
		$latest_dms = EE_Registry::instance()->load_dms( 'EE_DMS_Core_9_9_9' );
		EE_Data_Migration_Manager::instance()->update_current_database_state_to( $latest_dms->migrates_to_version());

		//now double-check that NO DMSs apply to the main blog because we upgraded
		$all_dmss = EE_Data_Migration_Manager::reset()->check_for_applicable_data_migration_scripts();

		//reset module so if EE_System was already reset in this request it will get called again.
		EED_Multisite::reset();
		$this->assertEquals( array(), $all_dmss );
	}

	public function tearDown(){
		//in case we called _pretend_ee_upgraded(), which added some DMSs, deregister them
		EE_Register_Data_Migration_Scripts::deregister('Pretend_Upgrade');
		parent::tearDown();
	}





}

// End of file EE_Multisite_UnitTestCase.php