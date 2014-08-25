<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EEM_Site. The DB Site, of course, actually being the NETWORK
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEM_Site extends EEM_Base {

	/**
	 * private instance of the EEM_Answer object
	 * @type EEM_Answer
	 */
	private static $_instance = NULL;

	/**
	 * 		This function is a singleton method used to instantiate the EEM_Answer object
	 *
	 * 		@access public
	 * 		@return EEM_Blog
	 */
	public static function instance() {

		// check if instance of EEM_Answer already exists
		if ( !self::$_instance instanceof EEM_Site ) {
			// instantiate Espresso_model
			self::$_instance = new self();
		}
		return self::$_instance;
	}



	/**
	 * resets the model and returns it
	 * @return EEM_Site
	 */
	public static function reset() {
		self::$_instance = NULL;
		return self::instance();
	}



	/**
	 * 	constructor
	 */
	protected function __construct() {
		$this->singular_item = __( 'Site', 'event_espresso' );
		$this->plural_item = __( 'Sites', 'event_espresso' );
		$this->_tables = array(
			'Sites' => new EE_Primary_Table( 'site', 'id' )
		);
		$this->_fields = array(
			'Sites' => array(
				'id' => new EE_Primary_Key_Int_Field( 'id', __( 'Site ID', 'event_espresso' ) ),
				'domain' => new EE_Plain_Text_Field( 'domain', __( 'Domain', 'event_espresso' ), FALSE, 'localhost' ),
				'path' => new EE_Plain_Text_Field( 'path', __( 'Path', 'event_espresso' ), FALSE, '/' )
			) );
		$this->_model_relations = array(
			'Blog' => new EE_Has_Many_Relation(),
			'Site_Meta' => new EE_Has_Many_Any_Relation()
		);

		parent::__construct();
	}



}

// End of file EEM_Site.model.php