<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EE_Blog
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Blog extends EE_Soft_Delete_Base_Class{
	/**
	 *
	 * @param array $props_n_values
	 * @return EE_Blog
	 */
	public static function new_instance( $props_n_values = array() ) {
		$has_object = parent::_check_for_object( $props_n_values, __CLASS__ );
		return $has_object ? $has_object : new self( $props_n_values );
	}



	/**
	 *
	 * @param array $props_n_values
	 * @return EE_Blog
	 */
	public static function new_instance_from_db( $props_n_values = array() ) {
		return new self( $props_n_values, TRUE );
	}
	/**
	 * Gets site_id
	 * @return int
	 */
	function site_id() {
		return $this->get('site_id');
	}

	/**
	 * Sets site_id
	 * @param int $site_id
	 * @return boolean
	 */
	function set_site_id($site_id) {
		return $this->set('site_id', $site_id);
	}
	/**
	 * Gets domain
	 * @return string
	 */
	function domain() {
		return $this->get('domain');
	}

	/**
	 * Sets domain
	 * @param string $domain
	 * @return boolean
	 */
	function set_domain($domain) {
		return $this->set('domain', $domain);
	}
	/**
	 * Gets registered
	 * @return string
	 */
	function registered() {
		return $this->get('registered');
	}

	/**
	 * Sets registered
	 * @param string $registered
	 * @return boolean
	 */
	function set_registered($registered) {
		return $this->set('registered', $registered);
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
		return $this->get('BLG_last_requested');
	}

	/**
	 * Sets last_requested
	 * @param string $last_requested
	 * @return boolean
	 */
	function set_last_requested($last_requested) {
		return $this->set('BLG_last_requested', $last_requested);
	}




}

// End of file EE_Blog.class.php