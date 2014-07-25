<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EE_Blog_Migration_Status
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Blog_Migration_Status extends EE_Soft_Delete_Base_Class{
	/**
	 *
	 * @param array $props_n_values
	 * @return EE_Blog_Migration_Status
	 */
	public static function new_instance( $props_n_values = array() ) {
		$has_object = parent::_check_for_object( $props_n_values, __CLASS__ );
		return $has_object ? $has_object : new self( $props_n_values );
	}



	/**
	 *
	 * @param array $props_n_values
	 * @return EE_Blog_Migration_Status
	 */
	public static function new_instance_from_db( $props_n_values = array() ) {
		return new self( $props_n_values, TRUE );
	}
	/**
	 * Gets blog_id
	 * @return int
	 */
	function blog_id() {
		return $this->get('BMS_blog_id');
	}

	/**
	 * Sets blog_id
	 * @param int $blog_id
	 * @return boolean
	 */
	function set_blog_id($blog_id) {
		return $this->set('BMS_blog_id', $blog_id);
	}
	/**
	 * Gets STS_ID
	 * @return string
	 */
	function STS_ID() {
		return $this->get('STS_ID');
	}

	/**
	 * Sets STS_ID
	 * @param string $STS_ID
	 * @return boolean
	 */
	function set_STS_ID($STS_ID) {
		return $this->set('STS_ID', $STS_ID);
	}
	/**
	 * Gets last_requested
	 * @return string
	 */
	function last_requested() {
		return $this->get('BMS_last_requested');
	}

	/**
	 * Sets last_requested
	 * @param string $last_requested
	 * @return boolean
	 */
	function set_last_requested($last_requested) {
		return $this->set('BMS_last_requested', $last_requested);
	}



}

// End of file EE_Blog.class.php