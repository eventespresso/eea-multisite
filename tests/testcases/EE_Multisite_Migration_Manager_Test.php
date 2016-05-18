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
		$blog1 = $this->factory->blog->create_and_get();
		$blog2 = $this->factory->blog->create_and_get();

		//pretend there was an upgrade that has a DMS that needs to run
		$this->_pretend_ee_upgraded();

		//now check that all new sites need migrating (NOT the main site, because it needs to be running, just the 2 newly-created ones)
		$needing_migration = EE_Multisite_Migration_Manager::instance()->assess_sites_needing_migration( 10 );
		$this->assertEquals( 2, $needing_migration );
	}

	/**
	 * Integration testing to verify that while assessing sites needing migration that
	 * we also upgrade sites that don't need to be migrated (ie, their data is old, but
	 * doesn't need migrating, just updating directly). Theoretically just EED_MUltisite_Test::test_switch_to_blog__no_ee()
	 * should have tested it enough but ticket https://events.codebasehq.com/projects/event-espresso/tickets/6904
	 * showed serious doubt on that
	 * @group current
	 */
	public function test_assess_sites_needing_migration__auto_upgrade() {
		global $wp_actions;
		//pretend multisite with 2 blogs
		$blog1 = $this->factory->blog->create_and_get();
		$blog2 = $this->factory->blog->create_and_get();
		//mark them as possibly being out of date (normally when sites are first created 
		//their DBs are instantiated as soon as they're visited. We changed them to start off as up-to-date)
		EEM_Blog::instance()->update( 
			array(
				'STS_ID' => EEM_Blog::status_unsure
			), 
			array(
				array(
					'blog_id' => array( 'IN', array( $blog1->blog_id, $blog2->blog_id ) ) 
				)
			)
		);
		//verify these blogs don't have the EE table yet
		wp_installing( true );
		switch_to_blog( $blog1->blog_id );
		$this->assertTableDoesNotExist( "esp_attendee_meta" );
		restore_current_blog();
		switch_to_blog( $blog2->blog_id );
		$this->assertTableDoesNotExist( "esp_attendee_meta" );
		restore_current_blog();
		wp_installing( false );
		$activation_hook_fired = $wp_actions[ 'AHEE__EE_System__detect_if_activation_or_upgrade__new_activation' ];
		//allow the creation of these tables, because we know they're temporary
		remove_all_filters( 'FHEE__EEH_Activation__create_table__short_circuit' );
		$needing_migration = EE_Multisite_Migration_Manager::instance()->assess_sites_needing_migration( 10 );
		//and put the filters back in place
		add_filter( 'FHEE__EEH_Activation__create_table__short_circuit', '__return_true' );

		//site shouldn't need migration. It should have just been upgraded automatically
		$this->assertEquals( 0, $needing_migration );
		$this->assertEquals( $activation_hook_fired + 2, $wp_actions[ 'AHEE__EE_System__detect_if_activation_or_upgrade__new_activation' ] );
		EED_Multisite::do_full_system_reset();
		switch_to_blog( $blog1->blog_id );
		global $wpdb;
		$this->assertEquals( 'wptests_' . $blog1->blog_id . '_', $wpdb->prefix );
		$this->assertTableExists( $wpdb->prefix . "esp_attendee_meta" );
		restore_current_blog();
		EED_Multisite::do_full_system_reset();
		switch_to_blog( $blog2->blog_id );
		global $wpdb;
		$this->assertEquals( 'wptests_' . $blog2->blog_id . '_', $wpdb->prefix );
		$this->assertTableExists( $wpdb->prefix . "esp_attendee_meta" );
		restore_current_blog();
	}

	
	public function test_migration_step() {


		//pretend multisite with 2 blogs
		$blog1 = $this->_create_a_blog_with_ee();
		$blog2 = $this->_create_a_blog_with_ee();

		//make blog2 last-requested a long time ago, so it will be migrated 2nd
		$blog2->set_last_requested( current_time( 'timestamp' ) - DAY_IN_SECONDS * 10 );

		//pretend there was an upgrade that has a DMS that needs to run
		$this->_pretend_ee_upgraded();

		//now check the new sites need migrating
		$needing_migration = EE_Multisite_Migration_Manager::instance()->assess_sites_needing_migration( 10 );
		$this->assertEquals( 2, $needing_migration );

		//now let's pretend a migration step was requested by ajax
		$step_size = 200;
		$records_per_dms = 333;
		$records_should_be_migrated = 0;
		$step_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		$last_ran_dms = EE_Data_Migration_Manager::instance()->get_last_ran_script();
		//so we should have just migrated a few records from the first site
		$this->assertEquals( $blog1->name(), $step_results[ 'current_blog_name' ] );
		//we should only know about the 1st migration for now. Maybe someday EE_Data_Migration_Manager will be smart
		//enough to know about the 2nd, but not currently
		$this->assertEquals( array( 'Multisite Mock Migration' ), $step_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration', 'event_espresso' ), $step_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $step_size, $step_results[ 'current_dms' ][ 'records_migrated' ] );


		$step_2_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		//remember that each of the two DMSs has 333 records to migrate each (see tests/mocks/data_migration_scripts/
		//but remember that once we have finished a single DMS, EE_Data_Migration_Manager stops there
		//so we only expect to finish the 1st DMS on this 2nd request
		$this->assertEquals( $blog1->name(), $step_2_results[ 'current_blog_name' ] );
		//only the 2nd migration should be left to do
		$this->assertEquals( array( 'Multisite Mock Migration Two' ), $step_2_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration', 'event_espresso' ), $step_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $records_per_dms, $step_2_results[ 'current_dms' ][ 'records_migrated' ] );

		$step_3_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		//we should have gotten 200 records into the 2nd DMS for blog 1
		$this->assertEquals( $blog1->name(), $step_3_results[ 'current_blog_name' ] );
		//only the 2nd migration should be left to do
		$this->assertEquals( array( 'Multisite Mock Migration Two' ), $step_3_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration Two', 'event_espresso' ), $step_3_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $step_size, $step_3_results[ 'current_dms' ][ 'records_migrated' ] );

		$step_4_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		//we should have finished the 2nd DMS for the 1st blog and taken it out of MM
		$this->assertEquals( $blog1->name(), $step_4_results[ 'current_blog_name' ] );
		//only the 2nd migration should be left to do
		$this->assertEquals( array( ), $step_4_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration Two Completed', 'event_espresso' ), $step_4_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $records_per_dms, $step_4_results[ 'current_dms' ][ 'records_migrated' ] );

		$step_5_results = EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
		//we should have gotten 200 records into the 2nd blog's 1st DMS
		$this->assertEquals( $blog2->name(), $step_5_results[ 'current_blog_name' ] );
		//only the 2nd migration should be left to do
		$this->assertEquals( array( 'Multisite Mock Migration' ), $step_5_results[ 'current_blog_script_names' ] );
		$this->assertEquals( __( 'Multisite Mock Migration', 'event_espresso' ), $step_5_results[ 'current_dms' ][ 'script' ] );
		$this->assertEquals( $step_size, $step_5_results[ 'current_dms' ][ 'records_migrated' ] );
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