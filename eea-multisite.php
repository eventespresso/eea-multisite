<?php
/*
  Plugin Name: Event Espresso - Multisite (EE4.5+)
  Plugin URI: http://www.eventespresso.com
  Description: The Event Espresso Multisite addon helps network admins manage a network of Event Espresso-enabled blogs. Requires Event Espresso 4.5.0 or higher.
  Version: 1.0.2.rc.014
  Author: Event Espresso
  Author URI: http://www.eventespresso.com
  Copyright 2014 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version	 	EE4
 *
 * ------------------------------------------------------------------------
 */
define( 'EE_MULTISITE_CORE_VERSION_REQUIRED', '4.9.26.rc.000' );
define( 'EE_MULTISITE_VERSION', '1.0.2.rc.014' );
define( 'EE_MULTISITE_PLUGIN_FILE', __FILE__ );

function load_espresso_multisite() {
	if ( class_exists( 'EE_Addon' ) ) {
		// multisite version
		require_once ( plugin_dir_path( __FILE__ ) . 'EE_Multisite.class.php' );
		EE_Multisite::register_addon();
	}
}
add_action( 'AHEE__EE_System__load_espresso_addons', 'load_espresso_multisite' );

/**
 * add wp-installing function which isn't available pre wp4.4 (and we need for ES which is currently on an older
 * version of WP)
 */
global $wp_version;
if ( $wp_version < '4.4.0' && ! function_exists( 'wp_installing' ) ) {
	function wp_installing( $is_installing = null ) {
		static $installing = null;

		// Support for the `WP_INSTALLING` constant, defined before WP is loaded.
		if ( is_null( $installing ) ) {
			$installing = defined( 'WP_INSTALLING' ) && WP_INSTALLING;
		}

		if ( ! is_null( $is_installing ) ) {
			$old_installing = $installing;
			$installing = $is_installing;
			return (bool) $old_installing;
		}

		return (bool) $installing;
	}
}
// End of file espresso_multisite.php
// Location: wp-content/plugins/espresso-multisite/espresso_multisite.php
