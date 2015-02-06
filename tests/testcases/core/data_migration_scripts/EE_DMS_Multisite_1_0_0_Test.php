<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_DMS_Multisite_1_0_0_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_DMS_Multisite_1_0_0_Test extends EE_UnitTestCase {

	public function test_added_blog_meta_table() {
		$this->assertTableExists( 'esp_blog_meta', 'Blog' );
	}



}

// End of file EE_DMS_Multisite_1_0_0_Test.php