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
            add_filter(
                'FHEE__EEH_Activation__get_cron_tasks',
                array('EED_Multisite_Auto_Site_Cleanup','add_cron_task')
            );
            //handle cron task callback
            add_action(
                'AHEE__EED_Multisite_Auto_Site_Cleanup__check_for_cleanup_tasks',
                array(
                    'EED_Multisite_Auto_Site_Cleanup',
                    'check_for_cleanup_tasks'
                )
            );
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
     * Adds a weekly cron task to check for site cleanup tasks
     * @param $existing_cron_task_data
     */
    public static function add_cron_task($existing_cron_task_data)
    {
        $existing_cron_task_data['AHEE__EED_Multisite_Auto_Site_Cleanup__check_for_cleanup_tasks'] = 'weekly';
        return $existing_cron_task_data;
    }



    /**
     * Gets a list of time intervals when an action should take place.
     * Keys are their labels, values are the time values associated with them
     * @return array
     */
    public static function get_cleanup_task()
    {
        return array(
                'first_warning' => '22 months',
                'second_warning' => '23 months',
                'bluff_archive' => '24 months',
                'really_archive' => '25 months',
            );
    }



    /**
     * Gets the name used for the EE extra meta that records when the action
     * for this interval was given
     *
     * @param $interval_label
     * @return string
     */
    protected static function _get_action_record_name($interval_label)
    {
        return  sanitize_key($interval_label . '_event');
    }
    /**
     * Checks for blogs that meet the criteria for cleanup tasks,
     * and for each it finds, it fires a WP action with that cleanup task.
     * When doing the last cleanup task, also archives the site.
     */
    public static function check_for_cleanup_tasks()
    {
        $previous_interval_label = null;
        $intervals = EED_Multisite_Auto_Site_Cleanup::get_cleanup_task();
        $last_interval = end($intervals);
        reset($intervals);
        foreach($intervals as $label => $interval) {
            $date = date(EE_Datetime_Field::mysql_timestamp_format, strtotime('-' . $interval));
            $query = array(
                array(
                    'BLG_last_admin_visit' => array('<', $date),
                )
            );
            if($previous_interval_label !== null) {
                $query[0] = array_merge(
                    $query[0],
                    array(
                        'Extra_Meta.EXM_key' => EED_Multisite_Auto_Site_Cleanup::_get_action_record_name($previous_interval_label),
                        'Extra_Meta.EXM_value' => array('IS_NOT_NULL')
                    )
                );
            }
            $blogs_matching_criteria = EEM_Blog::instance()->get_all($query);
            foreach($blogs_matching_criteria as $blog) {
                if($last_interval === $interval) {
                    //it's the last interval. Cleanup time
                    $blog->save(
                        array(
                        'archived' =>true
                        )
                    );
                }
                //record that it's been fired
                $blog->add_extra_meta(EED_Multisite_Auto_Site_Cleanup::_get_action_record_name($label), current_time('mysql', true));
                //fire an action other plugins can listen for
                do_action('AHEE__EED_Multisite_Auto_Site_Cleanup', $blog, $label, $interval);
            }
            //remember this label during the next iteration
            $previous_interval_label = $label;
        }
    }



    /**
     * Declare unused abstract method
     */
    public function run($WP){}
}

// End of file EED_Multisite.module.php
// Location: /wp-content/plugins/espresso-multisite/EED_Multisite.module.php
