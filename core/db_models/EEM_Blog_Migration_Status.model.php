<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EE_Blogs
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEM_Blog_Migration_Status extends EEM_Base{
	/**
	 * private instance of the EEM_Answer object
	 * @type EEM_Blog_Migration_Status
	 */
	private static $_instance = NULL;
	/**
	 *		This function is a singleton method used to instantiate the EEM_Answer object
	 *
	 *		@access public
	 *		@return EEM_Blog_Migration_Status
	 */
	public static function instance(){

		// check if instance of EEM_Answer already exists
		if ( ! self::$_instance instanceof EEM_Answer ) {
			// instantiate Espresso_model
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * 	constructor
	 */
	protected function __construct(){
		$this->singular_item = __('Blog Migration Status','event_espresso');
		$this->plural_item = __('Blog Migration Stati','event_espresso');
		$this->_tables = array(
			'Blog_Migration_Status'=> new EE_Primary_Table('esp_blog_migration_status', 'BMS_ID')
		);
		$this->_fields = array(
			'Blog_Migration_Status'=>array(
				'BMS_ID' => new EE_Primary_Key_Int_Field('BMS_ID', __('Blog Migraiton Status ID','event_espresso')),
				'blog_id' => new EE_Foreign_Key_Int_Field('blog_Id', __('Blog ID','event_espresso'), FALSE, 0, 'Blog'),
				'STS_ID' => new EE_Foreign_Key_String_Field('STS_ID', __( 'Status', 'event_espresso' ), FALSE, self::status_unsure, 'Status' ),
				'BMS_last_requested' => new EE_Datetime_Field('BMS_last_requested', __('Last Request for this Blog', 'event_espresso'), FALSE, current_time('mysql') ),
			));
		$this->_model_relations = array(
			'Site'=>new EE_Belongs_To_Relation()
		);

		parent::__construct();
	}


	/**
	 * refreshes this list
	 */
	public function refresh(){

	}
}

// End of file EE_Blogs.model.php