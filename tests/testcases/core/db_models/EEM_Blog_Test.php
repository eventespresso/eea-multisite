<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EEM_Blog_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEM_Blog_Test extends EE_UnitTestCase {

	public function test_get_all() {
		$this->assertEquals( 1, EEM_Blog::instance()->count() );
		//insert one using the normal WP way
		$this->factory->blog->create_many( 2 );
		$this->assertEquals( 3, EEM_Blog::instance()->count() );
		//insert one using the nomra l EE way
		$this->new_model_obj_with_dependencies( 'Blog' );
		$this->assertEquals( 4, EEM_Blog::instance()->count() );
	}



	public function test_count_blogs_needing_migration() {
		//these two don't need to be migrated
		$this->factory->blog->create_many( 2 );
		$blog_needing_migration = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_out_of_date ) );

		$blog_maybe_needing_migration = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_unsure ) );
		$blog_up_to_date = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_up_to_date ) );
		$this->assertEquals( 1, EEM_Blog::instance()->count_blogs_needing_migration() );
	}



	public function test_count_blogs_maybe_needing_migration() {
		//these two MIGHT need migrating, so MIGHT the main site
		$this->factory->blog->create_many( 2 );
		$blog_needing_migration = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_out_of_date ) );
		$blog_maybe_needing_migration = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_unsure ) );
		$blog_up_to_date = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_up_to_date ) );
		$this->assertEquals( 4, EEM_Blog::instance()->count_blogs_maybe_needing_migration() ); //main site, 2 created using factorya, and one with statu s'unsure'
		$this->assertEquals( 4, count( EEM_Blog::instance()->get_all_blogs_maybe_needing_migration() ) );
	}



	public function test_count_blogs_up_to_date() {
		//these two MIGHT need migrating, so MIGHT the main site
		$this->factory->blog->create_many( 2 );
		$blog_needing_migration = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_out_of_date ) );
		$blog_maybe_needing_migration = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_unsure ) );
		$blog_up_to_date = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_up_to_date ) );
		$this->assertEquals( 1, EEM_Blog::instance()->count_blogs_up_to_date() ); //just the last created one is KNOWN to be up-to-date
	}



	public function test_get_migrating_blog_or_most_recently_requested() {
		//these two MIGHT need migrating, so MIGHT the main site
		$this->factory->blog->create_many( 2 );

		$blog_needing_migration_last_long_ago = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_out_of_date, 'BLG_last_requested' => current_time( 'timestamp' ) - 1000 ) );
		$blog_needing_migration_last_requetsed_now = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_out_of_date, 'BLG_last_requested' => current_time( 'timestamp' ) ) );

		$blog_needing_migration_last_way_long_ago = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_out_of_date, 'BLG_last_requested' => current_time( 'timestamp' ) - 9000 ) );
		$blog_migrating = $this->new_model_obj_with_dependencies( 'Blog', array( 'STS_ID' => EEM_Blog::status_migrating ) );
		$this->assertEquals( $blog_migrating, EEM_Blog::instance()->get_migrating_blog_or_most_recently_requested() );
		$blog_migrating->delete();
		$this->assertEquals( $blog_needing_migration_last_requetsed_now, EEM_Blog::instance()->get_migrating_blog_or_most_recently_requested() );
	}



}

// End of file EEM_Blog_Test.php