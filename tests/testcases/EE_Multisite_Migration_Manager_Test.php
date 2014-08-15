<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Multisite_Migration_Manager_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Multisite_Migration_Manager_Test extends EE_UnitTestCase{

	public function test_assess_sites_needing_migration(){
		//pretend multisite with 2 blogs
		$blog1 = $this->_create_a_blog_with_ee();
		$blog2 = $this->_create_a_blog_with_ee();

		//pretend there was an upgrade that has a DMS that needs to run
		$this->_pretend_ee_upgraded();

		//now check that all 3 sites need migrating
		$needing_migration = EE_Multisite_Migration_Manager::instance()->assess_sites_needing_migration( 10 );
		$this->assertEquals( 3, $needing_migration );
	}

	public function test_migration_step(){


		//pretend multisite with 2 blogs
		$blog1 = $this->_create_a_blog_with_ee();
		$blog2 = $this->_create_a_blog_with_ee();

		//pretend there was an upgrade that has a DMS that needs to run
		$this->_pretend_ee_upgraded();

		//now check that all 3 sites need migrating
		$needing_migration = EE_Multisite_Migration_Manager::instance()->assess_sites_needing_migration( 10 );
		$this->assertEquals( 3, $needing_migration );

		$next_blog_to_migrate = EEM_Blog::instance()->get_migrating_blog_or_most_recently_requested();
		$this->assertInstanceOf( 'EE_Blog', $next_blog_to_migrate );
		//now let's pretend a migration step was requested by ajax
		$step_results = EE_Multisite_Migration_Manager::instance()->migration_step( 25 );
		$last_ran_dms = EE_Data_Migration_Manager::instance()->get_last_ran_script();
		//so we should have just migrated the first blog a few items
		$this->assertEquals( 'Test Blog', $step_results[ 'current_blog_name' ] );
		$this->assertEquals( array( 'Multisite Mock Migration' ), $step_results['current_blog_script_names' ] );
		$this->assertEquals( 25, $step_results['current_dms']['records_migrated'] );
	}

	/**
	 * Sets up a blog with the latest EE installed on it
	 * @return EE_Blog
	 */
	private function _create_a_blog_with_ee(){
		global $wpdb;
		$blog = $this->factory->blog->create_and_get();
		//grab one
		switch_to_blog( $blog->blog_id );
		EE_Data_Migration_Manager::reset();
		$this->assertTableDoesNotExist( $wpdb->prefix . "esp_attendee_meta" );
		$this->assertTableExists( $wpdb->prefix . 'posts' );
		$newest_dms_name = EE_Data_Migration_Manager::reset()->get_most_up_to_date_dms();
		$newest_dms = EE_Registry::instance()->load_dms( $newest_dms_name );
		//allow the creation of these tables, because we know they're temporary
		remove_all_filters( 'FHEE__EEH_Activation__create_table__short_circuit' );
		$newest_dms->schema_changes_before_migration();
		//and put the filters back in place
		add_filter( 'FHEE__EEH_Activation__create_table__short_circuit', '__return_true' );
		$this->assertTableExists( $wpdb->prefix . "esp_status" );
		$this->assertEquals( 0, EEM_Status::reset()->count() );
		EE_System::instance()->initialize_db_if_no_migrations_required();
		$this->assertNotEquals( EE_Maintenance_Mode::level_2_complete_maintenance, EE_Maintenance_Mode::instance()->level() );
		$this->assertTableExists( $wpdb->prefix . 'esp_attendee_meta' );
		$this->assertNotEquals( 0, EEM_Status::reset()->count() );
		restore_current_blog();

		return EEM_Blog::instance()->get_one_by_ID( $blog->blog_id );
	}
	/**
	 * to pretend EE had an upgrade, we just register a core DMS that applies.
	 * It should be removed during tearDown()
	 */
	private function _pretend_ee_upgraded(){
		$this->_pretend_addon_hook_time();
		EE_Register_Data_Migration_Scripts::register('Monkey', array(
			'dms_paths' => array( EE_MULTISITE_PATH . 'tests/mocks/data_migration_scripts/' )
		));
		$all_dmss = EE_Data_Migration_Manager::reset()->get_all_data_migration_scripts_available();
		$this->assertArrayHasKey( 'EE_DMS_Core_9_9_9', $all_dmss );
		$this->assertArrayHasKey( 'EE_DMS_Core_9_9_10', $all_dmss );
		$applicable_dmss = EE_Data_Migration_Manager::reset()->check_for_applicable_data_migration_scripts();
		$this->assertArrayHasKey( 'EE_DMS_Core_9_9_9', $applicable_dmss );
	}
	public function test_migration_step_afterwards(){
		$applicable_dmss = EE_Data_Migration_Manager::reset()->check_for_applicable_data_migration_scripts();
		$this->assertEmpty( $applicable_dmss );
	}
	public function tearDown(){
		//make sure we de-register the added DMS
		EE_Register_Data_Migration_Scripts::deregister( 'Monkey' );
		parent::tearDown();
	}
}

// End of file EE_Multisite_Migration_Manager_Test.php