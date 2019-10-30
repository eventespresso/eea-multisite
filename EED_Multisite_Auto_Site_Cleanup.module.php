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
 *
 *
 * ------------------------------------------------------------------------
 */



/**
 * Class  EED_Multisite_Auto_Site_Cleanup
 * Tracks when admin users (but not network-admins, and possibly other special users) visit the site.
 * Also defines a set of "cleanup tasks" that should occur after a certain number of time since the previous "admin visit".
 * A cron task is setup from `EE_Multisite` (it would have been added here, but this hooks in too late) to check
 * for sites that haven't been visited in a while, and performs the "cleanup tasks" that qualify.
 * Performing a "cleanup task" actually just involves firing a WP action, that other plugins can listen for
 * (e.g., they could send an email when that happens).
 * When the last cleanup task is done, however, the site is archived (but not permanently deleted).
 * @package               Event Espresso
 * @subpackage            espresso-multisite
 * @author                Mike Nelson
 * ------------------------------------------------------------------------
 */
class EED_Multisite_Auto_Site_Cleanup extends EED_Module
{

    /**
     * label for site cleanup task that gives the user their first warning
     */
    const FIRST_WARNING_LABEL     = 'first_warning';

    /**
     * The time to wait before doing the first warning cleanup task
     */
    const FIRST_WARNING_WAIT_TIME = '22 months';

    /**
     * label for site cleanup tasks that gives the user their second warning
     */
    const SECOND_WARNING_LABEL = 'second_warning';

    /**
     * The time to wait before doing the second warning cleanup task
     */
    const SECOND_WARNING_WAIT_TIME = '23 months';

    /**
     * label for site cleanup task that says its going to delete the user's
     * site, but it's just bluffing
     */
    const ARCHIVE_SITE_BLUFF_LABEL = 'archive_site_bluff';

    /**
     * The time to wait before doing the bluff site archival cleanup task
     */
    const ARCHIVE_SITE_BLUFF_WAIT_TIME = '24 months';

    /**
     * label for site cleanup task that actually archives the user's site
     */
    const ARCHIVE_SITE_REAL_LABEL = 'archive_site_real';

    /**
     * The time to wait before doing the real site archival task
     */
    const ARCHIVE_SITE_REAL_WAIT_TIME = '25 months';

    /**
     * The name of the transient used to record an admin visited the site already today
     * so we don't need to again update BLG_last_admin_visit
     */
    const SITE_ADMIN_VISIT_RECORD = 'ee_user_site_visit_record';
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
        // don't do multisite stuff if multisite isn't enabled
        if (is_multisite()) {
            // handle cron task callback
            add_action(
                'AHEE__EED_Multisite_Auto_Site_Cleanup__check_for_cleanup_tasks',
                array(
                    'EED_Multisite_Auto_Site_Cleanup',
                    'check_for_cleanup_tasks'
                )
            );
            // redirect to splash if first visit in x months
        }
    }



    /**
     * Tracks when an admin user visits the site, which is handy for knowing when to cleanup the site.
     * This callback is only added when it's a visit to the admin dashboard (not ajax requests
     * because we don't want to change those and we may want to change the response)
     *
     * @throws EE_Error|Exception
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \InvalidArgumentException
     * @throws \DomainException
     */
    public static function track_admin_visits()
    {
        if (! get_transient(EED_Multisite_Auto_Site_Cleanup::SITE_ADMIN_VISIT_RECORD)
            &&  EED_Multisite_Auto_Site_Cleanup::current_user_is_tracked()
            && ! wp_doing_ajax()
        ) {
            $current_blog = EEM_Blog::instance()->get_one_by_ID(get_current_blog_id());
            $current_blog->save(
                array(
                    'BLG_last_admin_visit'=> EEM_Blog::instance()->current_time_for_query('BLG_last_admin_visit')
                )
            );
            set_transient(
                EED_Multisite_Auto_Site_Cleanup::SITE_ADMIN_VISIT_RECORD,
                1,
                apply_filters(
                    'FHEE__EED_Multisite_Auto_Site_Cleanup__track_admin_visits__frequency',
                    DAY_IN_SECONDS
                )
            );
            // fetch the first cleanup tasks' label, so we can check if it was already done
            // (don't just assume someone hasn't filtered the get_cleanup_tasks method and changed it)
            $cleanup_tasks = EED_Multisite_Auto_Site_Cleanup::get_cleanup_tasks();
            $cleanup_task_labels = array_keys($cleanup_tasks);
            $first_cleanup_task = reset($cleanup_task_labels);
            switch_to_blog(1);
            if ($current_blog->get_extra_meta(
                EED_Multisite_Auto_Site_Cleanup::get_action_record_extra_meta_name(
                    $first_cleanup_task
                ),
                true,
                false
            )
            ) {
                // ok forget we ever sent them any warnings etc
                foreach (EED_Multisite_Auto_Site_Cleanup::get_cleanup_tasks() as $label => $time_threshold) {
                    $current_blog->delete_extra_meta(EED_Multisite_Auto_Site_Cleanup::get_action_record_extra_meta_name($label));
                }
                restore_current_blog();
                // tell them we won't be deleting their site anymore
                $site_details = get_blog_details();
                $blog_name = trim($site_details->blogname) === '' ? $site_details->domain : $site_details->blogname;
                $content = EEH_Template::display_template(
                    EE_MULTISITE_PATH . 'templates/multisite_site_archival_aborted.template.php',
                    array(
                        'blog_name' => $blog_name,
                    ),
                    true
                );
                wp_die($content, esc_html__('Thanks for Coming Back!', 'event_espresso'), 200);
            }
            restore_current_blog();
        }
    }



    /**
     * Returns whether or not we should track visits by this user
     */
    public static function current_user_is_tracked()
    {
        $cap_that_determines_track_worthiness = defined('EE_MULTISITE_TRACK_CAP')
            ? EE_MULTISITE_TRACK_CAP
            : 'ee_read_ee';

        return apply_filters(
            'FHEE__EED_Multisite_Auto_Site_Cleanup__current_user_is_tracked',
            current_user_can($cap_that_determines_track_worthiness) && ! is_super_admin()
        );
    }



    /**
     * Gets a list of time intervals when an action should take place.
     * Keys are their labels, values are the time values associated with them
     * @return array
     */
    public static function get_cleanup_tasks()
    {
        return apply_filters(
            'FHEE__EED_Multisite_Auto_Site_Cleanup__get_cleanup_tasks',
            array(
                EED_Multisite_Auto_Site_Cleanup::FIRST_WARNING_LABEL      => EED_Multisite_Auto_Site_Cleanup::FIRST_WARNING_WAIT_TIME,
                EED_Multisite_Auto_Site_Cleanup::SECOND_WARNING_LABEL     => EED_Multisite_Auto_Site_Cleanup::SECOND_WARNING_WAIT_TIME,
                EED_Multisite_Auto_Site_Cleanup::ARCHIVE_SITE_BLUFF_LABEL => EED_Multisite_Auto_Site_Cleanup::ARCHIVE_SITE_BLUFF_WAIT_TIME,
                EED_Multisite_Auto_Site_Cleanup::ARCHIVE_SITE_REAL_LABEL  => EED_Multisite_Auto_Site_Cleanup::ARCHIVE_SITE_REAL_WAIT_TIME,
            )
        );
    }



    /**
     * Gets the name used for the EE extra meta that records when the action
     * for this interval was given
     *
     * @param $interval_label
     * @return string
     */
    public static function get_action_record_extra_meta_name($interval_label)
    {
        return  $interval_label === null
            ? null
            : sanitize_key($interval_label . '_event');
    }



    /**
     * Checks for blogs that meet the criteria for cleanup tasks,
     * and for each it finds, it fires a WP action with that cleanup task.
     * When doing the last cleanup task, also archives the site.
     *
     * @throws \EE_Error
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \InvalidArgumentException
     */
    public static function check_for_cleanup_tasks()
    {
        // You would think we'd only run this from the main site? Well, nope. Somehow it runs from elsewhere.
        if (! is_main_site()) {
            // This should only run from the main site, otherwise we're duplicating efforts.
            // Also, never run this again from this site. Thank you.
            wp_unschedule_hook('AHEE__EED_Multisite_Auto_Site_Cleanup__check_for_cleanup_tasks');
            // Also, clean up the mess we left on this site. These records should only be on the main site.
            EEM_Extra_Meta::instance()->delete(
                [
                    [
                        'EXM_type' => 'Blog',
                        'EXM_key' => EED_Multisite_Auto_Site_Cleanup::get_action_record_extra_meta_name('first_warning')
                    ]
                ]
            );
            // Good day to you! *slam*
            return;
        }
        $previous_interval_label = null;
        $intervals = EED_Multisite_Auto_Site_Cleanup::get_cleanup_tasks();
        $last_interval = end($intervals);
        reset($intervals);
        foreach ($intervals as $label => $interval) {
            $threshold_time = strtotime('-' . $interval);
            $blogs_matching_criteria = EEM_Blog::instance()->get_all_logged_into_since_time_with_extra_meta(
                $threshold_time,
                EED_Multisite_Auto_Site_Cleanup::get_action_record_extra_meta_name(
                    $previous_interval_label
                ),
                EED_Multisite_Auto_Site_Cleanup::get_action_record_extra_meta_name(
                    $label
                ),
                EED_Multisite_Auto_Site_Cleanup::get_protected_blogs()
            );
            foreach ($blogs_matching_criteria as $blog) {
                if ($last_interval === $interval) {
                    // it's the last interval. Cleanup time
                    $blog->set('archived', true);
                }
                // in case there was a mixup and this action is getting fired much later than it should
                // avoid sending all the events in rapid succession by making sure the last recorded
                // visit by an admin matches what this action expected it to. This means if we send a
                // message saying the site will be archived in 4 months, and it's actually 1 month from
                // the date, because we're sending the message late somehow, we're actually delaying
                // the site's archival so that the message is correct.
                $blog->set('BLG_last_admin_visit', $threshold_time);
                // record that it's been fired
                $blog->add_extra_meta(EED_Multisite_Auto_Site_Cleanup::get_action_record_extra_meta_name($label), current_time('mysql', true));
                // fire an action other plugins can listen for
                do_action('AHEE__EED_Multisite_Auto_Site_Cleanup', $blog, $label, $interval);
                $blog->save();
            }
            // remember this label during the next iteration
            $previous_interval_label = $label;
        }
    }



    /**
     * Gets an array of all the blog IDs that are "protected" from being automatically archived etc.
     * @return array
     */
    public static function get_protected_blogs()
    {
        $protected_blogs = isset(EE_Registry::instance()->CFG->addons->ee_multisite->delete_site_excludes) ? EE_Registry::instance()->CFG->addons->ee_multisite->delete_site_excludes : array();
        // always make sure that the main site is excluded from any deletes and that we've typecast the values in the array.
        $protected_blogs[] = 1;
        $protected_blogs = array_map('absint', $protected_blogs);
        return $protected_blogs;
    }



    /**
     * Declare unused abstract method
     */
    public function run($WP)
    {
    }
}

// End of file EED_Multisite.module.php
// Location: /wp-content/plugins/espresso-multisite/EED_Multisite.module.php
