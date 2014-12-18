<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EED_Multisite_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EED_Multisite_Test extends EE_Multisite_UnitTestCase {
	/**
	 * Make sure it's ok that we switch to a blog that hasn't had EE setup on
	 * (although it is active on)
	 */
	public function test_switch_to_blog__no_ee(){
		global $wp_actions;
		//make another blog on this site
		$blog = $this->factory->blog->create_and_get();

		switch_to_blog( $blog->blog_id );
		$this->assertTableDoesNotExist( "esp_attendee_meta" );
		//when we switch to it using EED_Multisite, EE should be installed on it
		$activation_hook_fired = $wp_actions[ 'AHEE__EE_System__detect_if_activation_or_upgrade__new_activation' ];
		//allow the creation of these tables, because we know they're temporary
		remove_all_filters( 'FHEE__EEH_Activation__create_table__short_circuit' );
		EED_Multisite::switch_to_blog( $blog->blog_id );
		//and put the filters back in place
		add_filter( 'FHEE__EEH_Activation__create_table__short_circuit', '__return_true' );
		$this->assertEquals( EE_System::req_type_new_activation, EE_System::instance()->detect_req_type() );
		$this->assertEquals( $activation_hook_fired + 1, $wp_actions[ 'AHEE__EE_System__detect_if_activation_or_upgrade__new_activation' ] );
		global $wpdb;
		$this->assertEquals( 'wptests_' . $blog->blog_id . '_', $wpdb->prefix );
		$this->assertTableExists( $wpdb->prefix . "esp_attendee_meta" );
	}

		/**
	 * Make sure it's ok that we switch to a blog that hasn't had EE setup on
	 * (although it is active on)
	 */
	public function test_switch_to_blog__in_mm(){
		//make another blog on this site
		$ee_blog = $this->_create_a_blog_with_ee();
		$this->_pretend_ee_upgraded();
		EED_Multisite::switch_to_blog( $ee_blog->ID() );
		$this->assertEquals( EE_Maintenance_Mode::level_2_complete_maintenance, EE_Maintenance_Mode::instance()->real_level() );
	}
}

// End of file EED_Multisite_Test.php