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

	function test_get_default_creator_id() {
		$this->assertEquals( 1, $this->_count_all_users() );
		$blog2 = $this->_create_a_blog_with_ee();
		$blog3 = $this->_create_a_blog_with_ee();
		//assert that creating a blog also creates an admin for that blog
		$this->assertEquals( 3, $this->_count_all_users() );

		//ok now let's go to test this
		switch_to_blog( $blog2->ID() );
		$blog2_creator_id = EED_Multisite::get_default_creator_id();
		$this->assertTrue( !empty( $blog2_creator_id ) );
		$this->assertEquals( '2', get_user_meta( $blog2_creator_id, 'primary_blog', TRUE ) );

		//and just to be sure, try it again
		switch_to_blog( $blog3->ID() );
		$blog3_creator_id = EED_Multisite::get_default_creator_id();
		$this->assertTrue( !empty( $blog3_creator_id ) );
		$this->assertEquals( '3', get_user_meta( $blog3_creator_id, 'primary_blog', TRUE ) );
	}



	protected function _count_all_users() {
		global $wpdb;
		return $wpdb->get_var( "SELECT count(*) FROM $wpdb->users" );
	}



}

// End of file EED_Multisite_Test.php