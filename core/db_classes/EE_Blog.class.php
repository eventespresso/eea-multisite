<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
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
class EE_Blog extends EE_Soft_Delete_Base_Class {

	/**
	 *
	 * @param array $props_n_values
	 * @return EE_Blog
	 */
	public static function new_instance( $props_n_values = array( ) ) {
		$has_object = parent::_check_for_object( $props_n_values, __CLASS__ );
		return $has_object ? $has_object : new self( $props_n_values );
	}



	/**
	 *
	 * @param array $props_n_values
	 * @return EE_Blog
	 */
	public static function new_instance_from_db( $props_n_values = array( ) ) {
		return new self( $props_n_values, TRUE );
	}



	/**
	 * Gets site_id
	 * @return int
	 */
	function site_id() {
		return $this->get( 'site_id' );
	}



	/**
	 * Sets site_id
	 * @param int $site_id
	 * @return boolean
	 */
	function set_site_id( $site_id ) {
		return $this->set( 'site_id', $site_id );
	}



	/**
	 * Gets domain
	 * @return string
	 */
	function domain() {
		return $this->get( 'domain' );
	}



	/**
	 * Sets domain
	 * @param string $domain
	 * @return boolean
	 */
	function set_domain( $domain ) {
		return $this->set( 'domain', $domain );
	}



	/**
	 * Gets registered
	 * @return string
	 */
	function registered() {
		return $this->get( 'registered' );
	}



	/**
	 * Sets registered
	 * @param string $registered
	 * @return boolean
	 */
	function set_registered( $registered ) {
		return $this->set( 'registered', $registered );
	}



	/**
	 * Gets STS_ID
	 * @return string
	 */
	function STS_ID() {
		return $this->get( 'STS_ID' );
	}



	/**
	 * Sets STS_ID
	 * @param string $STS_ID
	 * @return boolean
	 */
	function set_STS_ID( $STS_ID ) {
		return $this->set( 'STS_ID', $STS_ID );
	}



	/**
	 * Gets last_requested
	 * @return string
	 */
	function last_requested() {
		return $this->get( 'BLG_last_requested' );
	}



	/**
	 * Sets last_requested
	 * @param string $last_requested
	 * @return boolean
	 */
	function set_last_requested( $last_requested ) {
		return $this->set( 'BLG_last_requested', $last_requested );
	}



	protected $_cached_blog_options = array( );

	/**
	 * Gets the blog's name in the wp option 'blogname'
	 * @return string
	 */
	function name() {
		return $this->get_blog_option( 'blogname', __( 'Unknown', 'event_espresso' ) );
	}



	/**
	 * Gets the blog's description in the wp option 'blogdescription'
	 * @return string
	 */
	function description() {
		return $this->get_blog_option( 'blogdescription', __( 'Unknown', 'event_espresso' ) );
	}



	/**
	 * Gets the blog's url in the wp option 'siteurl'
	 * @return string
	 */
	function site_url() {
		return $this->get_blog_option( 'siteurl', '' );
	}



	/**
	 * Gets the blog's home url in teh wp option 'home'
	 * @return string
	 */
	function home_url() {
		return $this->get_blog_option( 'home', '' );
	}



	/**
	 * Gets the blog's admin email in the wp option 'wp_email'
	 * @return string
	 */
	function admin_email() {
		return $this->get_blog_option( 'admin_email', '' );
	}



	/**
	 * Grabs the specified option for this blog.
	 *
	 * Switches to that blog, grabs, the option, and returns the 'current blog' to previously-set blog.
	 * Because this can be an expensive process, we cache the options on this class and
	 * return those when possible
	 * @param string $option_name the name of the wp option you want from this blog
	 * @param mixed $default value if the blog option doesn't exist
	 */
	public function get_blog_option( $option_name, $default = FALSE ) {
		if ( !isset( $this->_cached_blog_options[ $option_name ] ) ) {
			$this->cache_blog_options( array( $option_name ) );
		}
		return $this->_get_cached_blog_option( $option_name, $default );
	}



	/**
	 * Gets the blog option for this blog as cached on this model object
	 * @param string $option_name
	 * @param mixed $default
	 * @return mixed
	 */
	protected function _get_cached_blog_option( $option_name, $default = FALSE ) {
		if ( isset( $this->_cached_blog_options[ $option_name ] ) ) {
			return $this->_cached_blog_options[ $option_name ];
		} else {
			return $default;
		}
	}



	/**
	 * Grabs the specified blog options and caches them on this object
	 * for later retrieval.
	 *
	 * Switches to that blog, grabs, the option, and returns the 'current blog' to previously-set blog.
	 * Can be used similarly to how get_blog_option() could be used with several values
	 * @param string[] $option_names
	 * @return void
	 */
	public function cache_blog_options( $option_names ) {
		//make sure they passed an array
		if ( !is_array( $option_names ) ) {
			$option_names = array( $option_names );
		}
		EED_Multisite::skip_system_reset();
		switch_to_blog( $this->ID() );
		foreach ( $option_names as $option_name ) {
			$this->_cached_blog_options[ $option_name ] = get_option( $option_name );
		}
		/* reminder: switches to PREVIOUS blog, NOT the one originally requested by client
		 * @see http://codex.wordpress.org/WPMU_Functions/restore_current_blog
		 */
		restore_current_blog();
	}

	/**
	 * return an i18n string of the blog's EE status
	 * @return string
	 */
	public function pretty_status(){
		switch( $this->STS_ID() ) {
			case 'BRK':
				return __( 'Broken', 'event_espresso' );
			case 'BOD':
				return __( 'Out-of-Date', 'event_espresso' );
			case 'BUN':
				return __( 'Unsure', 'event_espresso' );
			case 'BCM':
				return __( 'Currently Migrating', 'event_espresso' );
			case 'BUD':
				return __( 'Up-to-Date', 'event_espresso' );
			default:
				return __( 'Invalid State', 'event_espresso' );
		}
	}



}

// End of file EE_Blog.class.php