<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EE_Blogs. The "blog" being each individual "blog" (UI "site"). NOT the network.
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEM_Blog extends EEM_Soft_Delete_Base{

	/**
	 * This blog is definetely out of date and should be migrated
	 */
	const status_out_of_date = 'BOD';
	/**
	 * The blog might be out of date. EE core or an addon has been upgraded
	 * and we havent checked if it needs to be migrated
	 */
	const status_unsure = 'BUN';
	/**
	 * The blog has been updated and EE core and its addons havent been updated since
	 */
	const status_up_to_date = 'BUD';

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
		if ( ! self::$_instance instanceof EEM_Blog ) {
			// instantiate Espresso_model
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * resets the model and returns it
	 * @return EEM_Blog
	 */
	public static function reset(){
		self::$_instance = NULL;
		return self::instance();
	}
	/**
	 * 	constructor
	 */
	protected function __construct(){
		$this->singular_item = __('Blog','event_espresso');
		$this->plural_item = __('Blogs','event_espresso');
		$this->_tables = array(
			'Blog'=> new EE_Primary_Table('blogs', 'blog_id'),
			'Blog_Meta' => new EE_Secondary_Table('esp_blog_meta', 'BLM_ID', 'blog_id_fk' )
		);
		$this->_fields = array(
			'Blog'=>array(
				'blog_id' => new EE_Primary_Key_Int_Field('blog_id', __('Blog ID','event_espresso')),
				'site_id' => new EE_Foreign_Key_Int_Field('site_id', __('Site ID','event_espresso'), FALSE, 0, 'Site'),
				'domain' => new EE_Plain_Text_Field('domain', __( 'Domain', 'event_espresso' ), FALSE ),
				'registered' => new EE_Datetime_Field('registered', __('Registered', 'event_espresso'), FALSE, current_time('timestamp') ),
				'last_updated' => new EE_Datetime_Field('last_updated', __('Last Updated', 'event_espresso'), FALSE, current_time('timestamp') ),
				'public' => new EE_Boolean_Field('public', __('Public?', 'event_espresso'), FALSE, TRUE ),
				'archived' => new EE_Boolean_Field('archived', __('Archived', 'event_espresso'), FALSE, FALSE ),
				'mature' => new EE_Boolean_Field('mature', __('Mature', 'event_espresso'), FALSE, FALSE ),
				'spam' => new EE_Boolean_Field('spam', __('Spam?', 'event_espresso'), FALSE, FALSE ),
				'deleted' => new EE_Trashed_Flag_Field('deleted', __('Deleted?', 'event_espresso'), FALSE, FALSE ),
				'lang_id' => new EE_Integer_Field('lang_id', __('Language ID', 'event_espresso'), FALSE, 0 )
			),
			'Blog_Meta'=>array(
				'BLM_ID' => new EE_DB_Only_Int_Field('BLM_ID', __('Blog Meta ID','event_espresso'), FALSE, 0),
				'blog_id_fk' => new EE_DB_Only_Int_Field('blog_id_fk', __('Blog ID','event_espresso'), FALSE, 0),
				'STS_ID' => new EE_Foreign_Key_String_Field('STS_ID', __( 'Status', 'event_espresso' ), FALSE, self::status_unsure, 'Status' ),
				'BLM_last_requested' => new EE_Datetime_Field('BLM_last_requested', __('Last Request for this Blog', 'event_espresso'), FALSE, current_time('timestamp') ),
			));
		$this->_model_relations = array(
			'Site'=>new EE_Belongs_To_Relation()
		);

		parent::__construct();
	}

	/**
	 * Counts all the blogs which MIGHT need to be mgirated
	 * @return int
	 */
	public function count_blogs_maybe_needing_migration(){
		return $this->count( array(
			array(
				'OR' => array(
					'STS_ID*unsure' => self::status_unsure,
					'STS_ID*null' => array('IS NULL'))
			)
		));
	}

	/**
	 * Counts all blogs which DEFINETELY DO need to be migrated
	 * @return int
	 */
	public function count_blogs_needing_migration(){
		return $this->count( array(
			array(
				'STS_ID' => self::status_out_of_date
			)
		));
	}

	public function count_blogs_up_to_date(){
		return $this->count( array(
			array(
				'STS_ID' => self::status_up_to_date
			)
		));
	}
}

// End of file EE_Blogs.model.php