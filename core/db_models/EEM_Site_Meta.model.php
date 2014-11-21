<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Site_meta. THe DB site actually bein ghte NETWORK
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEM_Site_Meta extends EEM_Soft_Delete_Base {

	/**
	 * private instance of the EEM_Answer object
	 * @type EEM_Site_Meta
	 */
	private static $_instance = NULL;

	/**
	 * 		This function is a singleton method used to instantiate the EEM_Answer object
	 *
	 * 		@access public
	 * 		@return EEM_Site_Meta
	 */
	public static function instance( $timezone = NULL ) {

		// check if instance of EEM_Answer already exists
		if ( !self::$_instance instanceof EEM_Site_Meta ) {
			// instantiate Espresso_model
			self::$_instance = new self( $timezone );
		}
		return self::$_instance;
	}



	/**
	 * resets the model and returns it
	 * @return EEM_Site_Meta
	 */
	public static function reset( $timezone = NULL ) {
		self::$_instance = NULL;
		return self::instance( $timezone );
	}



	/**
	 * 	constructor
	 */
	protected function __construct( $timezone = NULL ) {
		$this->singular_item = __( 'Site Meta', 'event_espresso' );
		$this->plural_item = __( 'Site Metas', 'event_espresso' );
		$this->_tables = array(
			'Site_Meta' => new EE_Primary_Table( 'sitemeta', 'blog_id' )
		);
		$this->_fields = array(
			'Site_Meta' => array(
				'meta_id' => new EE_Primary_Key_Int_Field( 'meta_id', __( 'Site Meta ID', 'event_espresso' ) ),
				'site_id' => new EE_Foreign_Key_Int_Field( 'site_id', __( 'Site ID', 'event_espresso' ), FALSE, 1, 'Site' ),
				'meta_key' => new EE_Plain_Text_Field( 'meta_key', __( 'Meta Key', 'event_espresso' ), FALSE, '' ),
				'meta_value' => new EE_Maybe_Serialized_Text_Field( 'meta_value', __( 'Value', 'event_espresso' ), TRUE )
			) );
		$this->_model_relations = array(
			'Site' => new EE_Belongs_To_Relation()
		);

		parent::__construct();
	}



}

// End of file EE_Site_meta.model.php
