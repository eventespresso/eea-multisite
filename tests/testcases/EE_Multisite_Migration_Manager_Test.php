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
class EE_Multisite_Migration_Manager_Test extends EE_Multisite_UnitTestCase {

	public function test_assess_sites_needing_migration() {
		//pretend multisite with 2 blogs
		$blog1 = $this->_create_a_blog_with_ee();
		$blog2 = $this->_create_a_blog_with_ee();

		//pretend there was an upgrade that has a DMS that needs to run
		$this->_pretend_ee_upgraded();

		//now check that all 3 sites need migrating (the main site, and the 2 newly-created ones)
		$needing_migration = EE_Multisite_Migration_Manager::instance()->assess_sites_needing_migration( 10 );
		$this->assertEquals( 3, $needing_migration );
	}



	public function test_migration_step() {


		//pretend multisite with 2 blogs
		$blog1 = $this->_create_a_blog_with_ee();

		//pretend there was an upgrade that has a DMS that needs to run
		$this->_pretend_ee_upgraded();

		//now check that all 3 sites need migrating
		$needing_migration = EE_Multisite_Migration_Manager::instance()->assess_sites_needing_migration( 10 );
		$this->assertEquals( 2, $needing_migration );

		//now let's pretend a migration step was requested by ajax
		$step_size = 200;
		$records_per_dms = 333;
		$records_should_be_migrated = 0;
		$step_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		$last_ran_dms = EE_Data_Migration_Manager::instance()->get_last_ran_script();
		//so we should have just migrated a few records from the main site
		$this->assertEquals( 'Test Blog', $step_results[ 'current_blog_name' ] );
		//we should only know about the 1st migration for now. Maybe someday EE_Data_Migration_Manager will be smart
		//enough to know about the 2nd, but not currently
		$this->assertEquals( array( 'Multisite Mock Migration' ), $step_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration', 'event_espresso' ), $step_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $step_size, $step_results[ 'current_dms' ][ 'records_migrated' ] );


		$step_2_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		//remember that each of the two DMSs has 333 records to migrate each (see tests/mocks/data_migration_scripts/
		//but remember that once we have finished a single DMS, EE_Data_Migration_Manager stops there
		//so we only expect to finish the 1st DMS on this 2nd request
		$this->assertEquals( 'Test Blog', $step_2_results[ 'current_blog_name' ] );
		//only the 2nd migration should be left to do
		$this->assertEquals( array( 'Multisite Mock Migration Two' ), $step_2_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration', 'event_espresso' ), $step_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $records_per_dms, $step_2_results[ 'current_dms' ][ 'records_migrated' ] );

		$step_3_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		//we should have gotten 200 records into the 2nd DMS for blog 1
		$this->assertEquals( 'Test Blog', $step_3_results[ 'current_blog_name' ] );
		//only the 2nd migration should be left to do
		$this->assertEquals( array( 'Multisite Mock Migration Two' ), $step_3_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration Two', 'event_espresso' ), $step_3_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $step_size, $step_3_results[ 'current_dms' ][ 'records_migrated' ] );

		$step_4_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		//we should have finished the 2nd DMS for the 1st blog and taken it out of MM
		$this->assertEquals( 'Test Blog', $step_4_results[ 'current_blog_name' ] );
		//only the 2nd migration should be left to do
		$this->assertEquals( array( ), $step_4_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration Two Completed', 'event_espresso' ), $step_4_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $records_per_dms, $step_4_results[ 'current_dms' ][ 'records_migrated' ] );

		$step_5_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		//we should have gotten 200 records into the 2nd blog's 1st DMS
		$this->assertEquals( 'Site 1', $step_5_results[ 'current_blog_name' ] );
		//only the 2nd migration should be left to do
		$this->assertEquals( array( 'Multisite Mock Migration' ), $step_5_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration', 'event_espresso' ), $step_5_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $step_size, $step_5_results[ 'current_dms' ][ 'records_migrated' ] );
	}



	/**
	 * to pretend EE had an upgrade, we just register a core DMS that applies.
	 * It should be removed during tearDown()
	 */
	private function _pretend_ee_upgraded() {
		$this->_pretend_addon_hook_time();
		EE_Register_Data_Migration_Scripts::register( 'Monkey', array(
			'dms_paths' => array( EE_MULTISITE_PATH . 'tests/mocks/data_migration_scripts/' )
		) );
		$all_dmss = EE_Data_Migration_Manager::reset()->get_all_data_migration_scripts_available();
		$this->assertArrayHasKey( 'EE_DMS_Core_9_9_9', $all_dmss );
		$this->assertArrayHasKey( 'EE_DMS_Core_9_9_10', $all_dmss );
		$applicable_dmss = EE_Data_Migration_Manager::reset()->check_for_applicable_data_migration_scripts();
		$this->assertArrayHasKey( 'EE_DMS_Core_9_9_9', $applicable_dmss );
	}



	public function test_migration_step_afterwards() {
		$applicable_dmss = EE_Data_Migration_Manager::reset()->check_for_applicable_data_migration_scripts();
		$this->assertEmpty( $applicable_dmss );
	}



	public function tearDown() {
		//make sure we de-register the added DMS
		EE_Register_Data_Migration_Scripts::deregister( 'Monkey' );
		parent::tearDown();
	}



}

// End of file EE_Multisite_Migration_Manager_Test.php