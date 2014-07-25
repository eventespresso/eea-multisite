<?php

if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');

/**
 *
 * EE_DMS_Multisite_0_0_1
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_DMS_Multisite_1_0_0 extends EE_Data_Migration_Script_Base{
	/**
	 * only run when Multisite is at exactly version 0.0.1
	 * @param type $version_string
	 * @return boolean
	 */
	public function can_migrate_from_version($version_string) {
		//this DMS NEVER migrates from NOTHIN'
		return FALSE;
	}

	public function schema_changes_after_migration() {

	}

	public function schema_changes_before_migration() {
		if( is_main_site() ){
			$this->_table_is_new_in_this_version('esp_blog_migration_status', "
				BMS_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
				blog_id int(10) unsigned,
				STS_ID VARCHAR(10) NOT NULL,
				BMS_last_requested datetime NOT NULL default '0000-00-00 00:00:00',
				PRIMARY KEY  (BMS_ID)"
					);
		}
	}
}

// End of file EE_DMS_Multisite_0_0_1.dms.php