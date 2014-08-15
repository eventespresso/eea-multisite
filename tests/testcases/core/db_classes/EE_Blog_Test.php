<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Blog_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Blog_Test extends EE_UnitTestCase{

	public function test_get_blog_option(){
		//these two don't need to be migrated
		$this->factory->blog->create_many( 2 );
		//grab one
		$blog1 = EEM_Blog::instance()->get_one();
		$blog2 = EEM_Blog::instance()->get_one( array( array( 'blog_id' => array( '!=', $blog1->ID() ) ) ) );
		$this->assertNotEquals( $blog1->get_blog_option( 'blogname', 'not_found' ), $blog2->get_blog_option( 'blogname', 'not_found') );
	}

	public function test_cache_blog_options(){
		//these two don't need to be migrated
		$this->factory->blog->create_many( 2 );
		//grab the 2nd blog, just for variety
		$blog2 = EEM_Blog::instance()->get_one( array( 'limit' => array( 1, 1 ) ) );
		$blog2->cache_blog_options( array( 'blogname', 'blogdescription', 'siteurl' ) );
		global $wp_actions;
		$blog_switch_count = $wp_actions[ 'switch_blog' ];
		//now grab those things from the clasa. And check they're not empty
		$this->assertNotEmpty( $blog2->get_blog_option( 'blogname' ) );
		$this->assertNotEmpty( $blog2->get_blog_option( 'blogdescription' ) );
		$this->assertNotEmpty( $blog2->get_blog_option( 'siteurl' ) );
		//now check we didn't actually switch blog
		$this->assertEquals( $blog_switch_count, $wp_actions[ 'switch_blog' ] );

	}
}

// End of file EE_Blog_Test.php