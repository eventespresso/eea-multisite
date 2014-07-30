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
	 * The blog might be out of date. EE core or an addon has been upgraded
	 * and we havent checked if it needs to be migrated
	 */
	const status_unsure = 'BUN';
	/**
	 * This blog is definetely out of date and should be migrated
	 */
	const status_out_of_date = 'BOD';
	/**
	 * this blog is currently being migrated by the EE multisite addon
	 * (others may be getting migrated without the EE multisite addon)
	 */
	const status_migrating = 'BCM';
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
				'BLG_last_requested' => new EE_Datetime_Field('BLG_last_requested', __('Last Request for this Blog', 'event_espresso'), FALSE, current_time('timestamp') ),
			));
		$this->_model_relations = array(
			'Site'=>new EE_Belongs_To_Relation()
		);

		parent::__construct();
	}

	/**
	 * Counts all the blogs which MIGHT need to be mgirated
	 * @param array $query_params @see EEM_Base::get_all()
	 * @return int
	 */
	public function count_blogs_maybe_needing_migration( $query_params = array() ){
		return $this->count( $this->_add_where_query_params_for_maybe_needs_migrating( $query_params )  );
	}

	/**
	 * Gets all blogs that might need to be migrated
	 * @param array $query_params @see EEM_Base::get_all()
	 * @return EE_Blog[]
	 */
	public function get_all_blogs_maybe_needing_migration( $query_params = array() ){
		return $this->get_all( $this->_add_where_query_params_for_maybe_needs_migrating( $query_params ));
	}

	/**
	 * Adds teh where conditions to get all the blogs that might need to be migrated (unsure)
	 * @param array $query_params @see EEM_base::get_all()
	 * @return array @see EEM_Base::get_all()
	 */
	private function _add_where_query_params_for_maybe_needs_migrating( $query_params = array() ){
		$query_params[0]['OR*maybe_needs_migrating'] = array(
					'STS_ID*unsure' => self::status_unsure,
					'STS_ID*null' => array('IS NULL')
			);
		return $query_params;
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

	/**
	 * Gets the blog which is marked as currently updating, or
	 * @return EE_Blog
	 */
	public function get_migrating_blog_or_most_recently_requested(){
		$currently_migrating = $this->get_one( array(
			array(
				'STS_ID' => self::status_migrating
			)
		));
		if( $currently_migrating ){
			return $currently_migrating;
		}else{
			return $this->get_one( array(
				array(
					'STS_ID' => self::status_out_of_date
				),
				'order_by' => array(
					'BLG_last_requested' => 'DESC'
				)
			));
		}
	}

	/**
	 * Assesses $num_to_assess blogs and finds whether they need ot be migrated or not,
	 * and updates their status. Returns the number that were found to need migrating
	 * (NOT the total number needing migrating. For that, use EEM_Blog::count_blogs_needing_migration())
	 * @param int $num_to_assess
	 * @return int number of blogs needing to be migrated, amongst those inspected
	 */
	public function assess_sites_needing_migration( $num_to_assess = 10 ){
		$blogs = EEM_Blog::instance()->get_all_blogs_maybe_needing_migration( array( 'LIMIT' => $num_to_assess ) );
		foreach( $blogs as $blog ){
			//switch to that blog and assess whether or not it needs to be migrated
			switch_to_blog( $blog->ID() );
			EE_Data_Migration_Manager::reset();
			$needs_migrating = EE_Maintenance_Mode::instance()->set_maintenance_mode_if_db_old();
			restore_current_blog();
			if( $needs_migrating ){
				$blog->set_STS_ID( EEM_Blog::status_out_of_date );
			}else{
				$blog->set_STS_ID( EEM_Blog::status_up_to_date );
			}
			$blog->save();
		}
		EE_Data_Migration_Manager::reset();
	}
}

// End of file EE_Blogs.model.php