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
class EEM_Blog extends EEM_Soft_Delete_Base{

	/**
	 * private instance of the EEM_Answer object
	 * @type EEM_Blog
	 */
	private static $_instance = NULL;
	/**
	 *		This function is a singleton method used to instantiate the EEM_Answer object
	 *
	 *		@access public
	 *		@return EEM_Blog
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
		$this->singular_item = __('Blog','event_espresso');
		$this->plural_item = __('Blogs','event_espresso');
		$this->_tables = array(
			'Blog'=> new EE_Primary_Table('blogs', 'blog_id')
		);
		$this->_fields = array(
			'Blog'=>array(
				'blog_id' => new EE_Primary_Key_Int_Field('blog_id', __('Blog ID','event_espresso')),
				'site_id' => new EE_Foreign_Key_Int_Field('site_id', __('Site ID','event_espresso'), FALSE, 0, 'Site'),
				'domain' => new EE_Plain_Text_Field('domain', __( 'Domain', 'event_espresso' ), FALSE ),
				'registered' => new EE_Datetime_Field('registered', __('Registered', 'event_espresso'), FALSE, current_time('mysql') ),
				'last_updated' => new EE_Datetime_Field('last_updated', __('Last Updated', 'event_espresso'), FALSE, current_time('mysql') ),
				'public' => new EE_Boolean_Field('public', __('Public?', 'event_espresso'), FALSE, TRUE ),
				'archived' => new EE_Boolean_Field('archived', __('Archived', 'event_espresso'), FALSE, FALSE ),
				'mature' => new EE_Boolean_Field('mature', __('Mature', 'event_espresso'), FALSE, FALSE ),
				'spam' => new EE_Boolean_Field('spam', __('Spam?', 'event_espresso'), FALSE, FALSE ),
				'deleted' => new EE_Trashed_Flag_Field('deleted', __('Deleted?', 'event_espresso'), FALSE, FALSE ),
				'lang_id' => new EE_Integer_Field('lang_id', __('Language ID', 'event_espresso'), FALSE, 0 )
			));
		$this->_model_relations = array(
			'Site'=>new EE_Belongs_To_Relation()
		);

		parent::__construct();
	}
}

// End of file EE_Blogs.model.php