<?php
if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}
/*
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version		$VID:$
 *
 * ------------------------------------------------------------------------
 */



/**
 * Class  EED_Multisite
 *
 * @package               Event Espresso
 * @subpackage            espresso-multisite
 * @author                Brent Christensen
 * ------------------------------------------------------------------------
 */
class EED_Multisite_Auto_Site_Cleanup extends EED_Module
{

    /**
     *    set_hooks - for hooking into EE Core, other modules, etc
     *
     * @access    public
     * @return    void
     */
    public static function set_hooks()
    {
        self::set_hooks_both();
    }



    /**
     *    set_hooks_admin - for hooking into EE Admin Core, other modules, etc
     *
     * @access    public
     * @return    void
     */
    public static function set_hooks_admin()
    {
        self::set_hooks_both();
        add_action('wp_loaded', array('EED_Multisite_Auto_Site_Cleanup', 'track_admin_visits' ));
    }



    protected static function set_hooks_both()
    {
        //don't do multisite stuff if multisite isn't enabled
        if (is_multisite()) {
            //check for admin visits
            //setup cron task
            //handle cron task callback
            //redirect to splash if first visit in x months
        }
    }



    /**
     * Tracks when an admin user visits the site, which is handy for knowing when to cleanup the site.
     * This callback is only added when it's a visit to the admin dashboard
     *
     * @throws EE_Error|Exception
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \InvalidArgumentException
     */
    public static function track_admin_visits()
    {
        if (! get_transient('ee_user_site_visit_record')
            && self::current_user_is_tracked()
        ) {
            $current_blog_id = get_current_blog_id();
            EEM_Blog::instance()->update_by_ID(
                array(
                    'BLG_last_admin_visit' => current_time('mysql', true)
                ),
                $current_blog_id
            );
            set_transient('ee_user_site_visit_record', 1, DAY_IN_SECONDS);
        }
    }



    /**
     * Returns whether or not we should track visits by this user
     */
    public static function current_user_is_tracked()
    {
        $cap_that_determines_track_worthiness = defined( 'EE_MULTISITE_TRACK_CAP')
            ? EE_MULTISITE_TRACK_CAP
            : 'ee_read_ee';

        return apply_filters(
            'FHEE__EED_Multisite_Auto_Site_Cleanup__current_user_is_tracked',
            current_user_can($cap_that_determines_track_worthiness) && ! is_super_admin()
        );
    }




    /**
     * Declare unused abstract method
     */
    public function run($WP){}
}

// End of file EED_Multisite.module.php
// Location: /wp-content/plugins/espresso-multisite/EED_Multisite.module.php
