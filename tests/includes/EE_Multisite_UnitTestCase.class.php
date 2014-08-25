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
class EE_Multisite_UnitTestCase extends EE_UnitTestCase{

	/**
	 * Sets up a blog with the latest EE installed on it
	 * @return EE_Blog
	 */
	protected function _create_a_blog_with_ee(){
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

		//also create an admin for this blog
		$admin = $this->factory->user->create_and_get();
		wp_update_user( array(
			'ID' => $admin->ID,
			'role' => 'Administrator',
		));
		update_user_meta( $admin->ID, 'primary_blog', get_current_blog_id() );
		restore_current_blog();

		return EEM_Blog::instance()->get_one_by_ID( $blog->blog_id );
	}
}

// End of file EE_Multisite_UnitTestCase.php