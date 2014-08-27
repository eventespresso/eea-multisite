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
		$this->assertTrue( ! empty( $blog2_creator_id ) );
		$this->assertEquals( '2', get_user_meta( $blog2_creator_id, 'primary_blog', TRUE ) );

		//and just to be sure, try it again. this time we'll delete the original
		//blog admin, create a subscriber, then the new blog admin, then another
		//subscriber. We should return the existing blog admin
		switch_to_blog( $blog3->ID() );
		$best_choice = EED_Multisite::get_default_creator_id();
		wp_delete_user( $best_choice );
		$best_choice = EED_Multisite::get_default_creator_id();
		//we shouldn't find anyone
		$this->assertNull( $best_choice );

		//early subscriber
		$other_user = $this->factory->user->create_and_get();
		update_user_meta( $other_user->ID, 'primary_blog', get_current_blog_id() );
		$this->assertFalse( user_can( $other_user->ID, 'administrator' ) );
		//we shouldn't find anyone. we'd rather use the network admin
		//than some low-privileged subscriber or something
		$this->assertNull( $best_choice );

		//new admin
		$new_admin_user = $this->factory->user->create_and_get();
		wp_update_user( array(
			'ID' => $new_admin_user->ID,
			'role' => 'administrator'
		));
		update_user_meta( $new_admin_user->ID, 'primary_blog', get_current_blog_id() );
		$this->assertTrue( user_can( $new_admin_user->ID, 'administrator' ) );
		//we should find that admin
		$best_choice = EED_Multisite::get_default_creator_id();
		$this->assertTrue( ! empty( $best_choice ) );
		$this->assertEquals( '3', get_user_meta( $best_choice, 'primary_blog', TRUE ) );
		$this->assertEquals( $new_admin_user->ID, $best_choice );

		//later subscriber
		$later_user = $this->factory->user->create_and_get();
		update_user_meta( $later_user->ID, 'primary_blog', get_current_blog_id() );
		$this->assertFalse( user_can( $later_user->ID, 'administrator' ) );
		//ok, find the new admin! go get it boy!
		$best_choice = EED_Multisite::get_default_creator_id();
		$this->assertTrue( ! empty( $best_choice ) );
		$this->assertEquals( '3', get_user_meta( $best_choice, 'primary_blog', TRUE ) );
		$this->assertEquals( $new_admin_user->ID, $best_choice );


	}



	protected function _count_all_users() {
		global $wpdb;
		return $wpdb->get_var( "SELECT count(*) FROM $wpdb->users" );
	}

	/**
	 * we don't test that the EE_System and EE_Addon hooks are fired correctly (already tested in EE_System_Test
	 * and EE_System_Test_With_Addon and EE_Addon_Test in core. We just test that our callbacks are
	 * correctly set on them and that when that callback is called the correctly behaviour happens
	 */
	public function test_possible_maintenance_mode_change_detected(){
		$actions_that_should_call_callback = array(
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
		foreach( $actions_that_should_call_callback as $action ){
			$this->assertTrue( FALSE != has_action( $action, array( 'EED_Multisite', 'possible_maintenance_mode_change_detected' ) ) );
		}
		//we COULD do integration testing to verify that EE_System is calling those hooks at the right time
		//and because EED_Multisite::possible_maintenance_mode_change_detected() mostly just calls
		//EEM_Blog::instance()->mark_all_blogs_migration_status_as_unsure() we will leave that
		//to be tested in EEM_Blog_Test

	}



}

// End of file EED_Multisite_Test.php