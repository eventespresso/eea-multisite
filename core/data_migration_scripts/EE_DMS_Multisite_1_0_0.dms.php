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
			$this->_table_is_new_in_this_version('esp_blog_meta', "
				BLM_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
				blog_id_fk int(10) unsigned,
				STS_ID VARCHAR(10) NOT NULL,
				BLG_last_requested datetime NOT NULL default '0000-00-00 00:00:00',
				PRIMARY KEY  (BLM_ID)"
					);
//			$this->insert_default_status_codes();
		}
	}
	/**
	 * inserts other status codes for blogs... except type 'blog' isn't allowed,
	 * because the MYSQL column is a set and its tricky changing what it allows.
	 * Besides, even if we add these stati, if the addon were deactivated then these
	 * stati in that table would become invalid and probably throw errors (and so would need
	 * to be removed). For now we don't need these to be rows in the status table.
	 * However, if we do need that one day, here's the function.
	 *
	 * 	@access public
	 * 	@static
	 * 	@return void
	 */
	public static function insert_default_status_codes() {
		global $wpdb;
		$table = $wpdb->get_var("SHOW TABLES LIKE '" . EEM_Status::instance()->table() . "'");

		if ( $table == EEM_Status::instance()->table()) {

			$SQL = "DELETE FROM " . EEM_Status::instance()->table() . " WHERE STS_ID IN ( 'BOD','BUN','BUD' );";
			$wpdb->query($SQL);

			$SQL = "INSERT INTO " . EEM_Status::instance()->table() . "
					(STS_ID, STS_code, STS_type, STS_can_edit, STS_desc, STS_open) VALUES
					('BOD', 'OUT_OF_DATE', 'blog', 0, NULL, 0),
					('BUN', 'UNSURE', 'blog', 0, NULL, 0),
					('BUD', 'UP_TO_DATE', 'blog', 0, NULL, 1),
					('BRK', 'BORKED', 'blog', 0, NULL, 0);";
			$wpdb->query($SQL);

		}

	}
}

// End of file EE_DMS_Multisite_0_0_1.dms.php