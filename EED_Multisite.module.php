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
class EED_Multisite extends EED_Module
{

    /**
     * @var        bool
     * @access    public
     */
    public static $shortcode_active = false;

    /**
     * This is a flag used to indicate whether a full reset of EE singletons should be done on a `switch_to_blog` or
     * `restore_current_blog` call.  Typically this should be set to true when client code is calling any EE code (besides
     * EEM models) that could be specific to the site (eg. EE_Config, Data Migrations, Messages system).
     *
     * @var bool
     */
    protected static $_do_full_reset = false;



    /**
     *    set_hooks - for hooking into EE Core, other modules, etc
     *
     * @access    public
     * @return    void
     */
    public static function set_hooks()
    {
        EE_Config::register_route('multisite', 'EED_Multisite', 'run');
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
        //don't do multisite stuff if multisite isn't enabled
        if (is_multisite()) {
            add_action('network_admin_notices', array('EED_Multisite', 'check_network_maintenance_mode'));
            add_action('network_admin_notices', array('EED_Multisite', 'check_main_blog_maintenance_mode'));
            //filter the existing maintenance mode messages in EE core
            add_filter('FHEE__Maintenance_Admin_Page_Init__check_maintenance_mode__notice', array('EED_Multisite', 'check_main_blog_maintenance_mode'), 10);
        }
    }



    public static function show_multisite_admin_in_mm($admin_page_folder_names)
    {
        $admin_page_folder_names['multisite'] = EE_MULTISITE_ADMIN;
        return $admin_page_folder_names;
    }



    protected static function set_hooks_both()
    {
        //don't do multisite stuff if multisite isn't enabled
        if (is_multisite()) {
            add_action('AHEE__EE_Data_Migration_Manager__check_for_applicable_data_migration_scripts__scripts_that_should_run',
                array('EED_Multisite', 'mark_blog_as_up_to_date_if_no_migrations_needed'), 10, 1);
            add_action('wpmu_new_blog', array('EED_Multisite', 'new_blog_created'), 10, 1);
            add_action('wp_loaded', array('EED_Multisite', 'update_last_requested'));
            add_filter('delete_blog', array('EED_Multisite', 'delete_ee_custom_tables_too'), 10, 2);
        }
    }



    /**
     * Drop EE custom tables when a site is deleted and its tables are dropped.
     * Also, remove the site's users if they're not member of any other site
     *
     * @param int  $blog_id The site ID.
     * @param bool $drop    True if site's table should be dropped. Default is false.
     */
    public static function delete_ee_custom_tables_too($blog_id, $drop)
    {
        if($drop){
            EEM_Base::set_model_query_blog_id($blog_id);
            EEH_Activation::drop_espresso_tables();
            EEM_Base::set_model_query_blog_id();
            //clean up blog_meta table
            $tables = EEM_Blog::instance()->get_tables();
            if (isset($tables['Blog_Meta']) && $tables['Blog_Meta'] instanceof EE_Secondary_Table) {
                //the main blog entry is already deleted, let's clean up the entry in the secondary table
                global $wpdb;
                $wpdb->delete($tables['Blog_Meta']->get_table_name(), array('blog_id_fk' => $blog_id));
            }
            //delete all non super_admin users that were attached to that blog if configured to drop them
            //so long as they're not a member of another site (main site's ok; we want to delete them from there too)
            if (EE_Registry::instance()->CFG->addons->ee_multisite->delete_non_super_admin_users) {
                $users = get_users(array('blog_id' => $blog_id, 'fields' => 'ids'));
                foreach ($users as $user_id) {
                    if (is_super_admin($user_id)) {
                        continue;
                    }
                    //are they a member of another site (besides the main site)? If so, don't delete them
                    if (! array_diff_key(
                        get_blogs_of_user($user_id),
                        array(
                            1 => true,
                            $blog_id => true
                        )
                    )){
                        wpmu_delete_user($user_id);
                    }
                }
            }
        }
    }



    /**
     * Checks if there are no migrations needed on a particular site, then we can mark it as being up-to-date right?
     *
     * @param EE_Data_Migration_Script_Base[] $migration_scripts_needed
     */
    public static function mark_blog_as_up_to_date_if_no_migrations_needed($migration_scripts_needed)
    {
        if (empty($migration_scripts_needed)) {
            EEM_Blog::instance()->mark_current_blog_as(EEM_Blog::status_up_to_date);
        }
    }



    /**
     * Checks if we're in maintenance mode, and if so we notify the admin adn tell them how to take the site OUT of maintenance mode
     */
    public static function check_network_maintenance_mode()
    {
        if (EE_Maintenance_Mode::instance()->level() != EE_Maintenance_Mode::level_2_complete_maintenance) {
            if (is_network_admin()) {
                //check that all the blogs are up-to-date
                $blogs_needing_migration = EEM_Blog::instance()->count_blogs_maybe_needing_migration();
                if ($blogs_needing_migration) {
                    $network = EE_Admin_Page::add_query_args_and_nonce(array(), EE_MULTISITE_ADMIN_URL);
                    echo '<div class="error">
						<p>' . sprintf(__('A change has been detected to your Event Espresso plugin or addons. Blogs on your network may require migration. %1$sClick here to check%2$s',
                            "event_espresso"), "<a href='$network'>", "</a>") .
                         '</div>';
                }
            }
        }
    }



    public static function check_main_blog_maintenance_mode($notice = '')
    {
        $new_notice = '';
        if (EE_Maintenance_Mode::instance()->level() == EE_Maintenance_Mode::level_2_complete_maintenance) {
            $maintenance_page_url = EE_Admin_Page::add_query_args_and_nonce(array(), EE_MAINTENANCE_ADMIN_URL);
            if (is_main_site()) {
                $new_notice = '<div class="error">
					<p>'
                              . sprintf(__('Your main site\'s Event Espresso data is out of date %1$sand needs to be migrated.%2$s After doing this, you should check that the other blogs on your network are up-to-date.',
                        "event_espresso"), "<a href='$maintenance_page_url'>", "</a>")
                              .
                              '</div>';
            } else {
                $new_notice = '<div class="error">
				<p>'
                              . __('Your event site is in the process of being updated and is currently in maintainance mode.  It has been bumped to the front of the queue and you should be able to have full access again in about 5 minutes.',
                        'event_espresso')
                              . '</p>'
                              .
                              '</div>';
            }
        }
        if (! empty($notice)) {
            $notice = $new_notice;
            return $new_notice;
        } else {
            $notice = $new_notice;
            echo $notice;
        }
    }



    /**
     * Run on frontend requests to update when the blog was last updated
     */
    public static function update_last_requested()
    {
        //only record visits by non-bots, and non-cron
        //also, only do this on the main site when its out of maintenance mode;
        //other sites can do it fine in mainteannce mode
        $user_agent = isset($_SERVER['HTTP_USER_AGENT'])
            ?
            $_SERVER['HTTP_USER_AGENT']
            :
            '';
        if (! EED_Multisite::is_bot($user_agent)
            && ! defined('DOING_CRON')
        ) {
            $current_blog_id = get_current_blog_id();
            EEM_Blog::instance()->update_last_requested($current_blog_id);
        }
    }



    /**
     * Detects if the current user is a bot or what
     *
     * @param type $user_agent_string
     * @return boolean
     */
    public static function is_bot($user_agent_string)
    {
        $dd = new DeviceDetector\DeviceDetector($user_agent_string);
        $dd->discardBotInformation();
        $dd->parse();
        return $dd->isBot();
    }



    /**
     * Sets the $_do_full_reset property to true to flag that the next `switch_to_blog` SHOULD do a full reset of all
     * EE singletons.
     * The initial call to EED_Multisite::switch_to_blog after calling this method will reset this to false.
     */
    public static function do_full_reset()
    {
        self::$_do_full_reset = true;
    }



    /**
     * Callback for the WordPress switch_blog action that fires whenever switch_to_blog and restore_current_blog is called.
     * We use this to fire any resets on EE systems that are needed when the blog context changes.
     * Important:  If self::do_full_reset() is called BEFORE this callback gets executed, then a full reset of EE is done.
     * Otherwise, the only thing that will happen is the `$new_blog_id` will be set on EEM_Base.  Also this method will not
     * do anything if in a WordPress installing context or if the switch is being done to the same blog as what's being switched
     * from.
     *
     * @param int $new_blog_id
     * @param int $old_blog_id
     */
    public static function switch_to_blog($new_blog_id, $old_blog_id = 0)
    {
        //we DON'T call anything in here if wp is installing
        if (wp_installing() || (int)$new_blog_id == (int)$old_blog_id) {
            return;
        }
        //if made it here then we just set_model_query_blog_id
        EEM_Base::set_model_query_blog_id($new_blog_id);
        //if not a full reset then return
        if (! self::$_do_full_reset) {
            return;
        }
        //make sure that we reset _do_full_reset so the next switch doesn't happen.
        self::$_do_full_reset = false;
        self::perform_full_reset();
    }



    /**
     * Contains all the code for performing a full reset.
     *
     * @param bool $reset_models   If this is set to true then the models receive a full reset.  This is necessary when
     *                             it is desired that the entity maps in the models be cleared out.
     */
    public static function perform_full_reset($reset_models = false)
    {
        EE_Registry::reset(false, true, $reset_models);
        EE_Multisite::reset();
        EE_System::reset();
    }



    /**
     * The same as wp's restore_current_blog(), but also takes care of restoring
     * a few EE-specific singletons
     * This is no longer needed because we hook into the switch_blog core WP action.  So core switch_to_blog and restore_current_blog
     * can be used and everything will flow through EED_Multisite::switch_to_blog method.
     *
     * @deprecated 1.0.1.rc.000
     */
    public static function restore_current_blog()
    {
        return;
    }



    /**
     *    config
     *
     * @return EE_Multisite_Config
     */
    public function config()
    {
        // config settings are setup up individually for EED_Modules via the EE_Configurable class that all modules inherit from, so
        // $this->config();  can be used anywhere to retrieve it's config, and:
        // $this->_update_config( $EE_Config_Base_object ); can be used to supply an updated instance of it's config object
        // to piggy back off of the config setup for the base EE_Multisite class, just use the following (note: updates would have to occur from within that class)
        return EE_Registry::instance()->addons->EE_Multisite->config();
    }



    /**
     *    run - initial module setup
     *
     * @access    public
     * @param  WP $WP
     * @return    void
     */
    public function run($WP)
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }



    /**
     *    enqueue_scripts - Load the scripts and css
     *
     * @access    public
     * @return    void
     */
    public function enqueue_scripts()
    {
        //Check to see if the multisite css file exists in the '/uploads/espresso/' directory
        if (is_readable(EVENT_ESPRESSO_UPLOAD_DIR . "css/multisite.css")) {
            //This is the url to the css file if available
            wp_register_style('espresso_multisite', EVENT_ESPRESSO_UPLOAD_URL . 'css/espresso_multisite.css');
        } else {
            // EE multisite style
            wp_register_style('espresso_multisite', EE_MULTISITE_URL . 'css/espresso_multisite.css');
        }
        // multisite script
        wp_register_script('espresso_multisite', EE_MULTISITE_URL . 'scripts/espresso_multisite.js', array('jquery'), EE_MULTISITE_VERSION, true);
        // is the shortcode or widget in play?
        if (EED_Multisite::$shortcode_active) {
            wp_enqueue_style('espresso_multisite');
            wp_enqueue_script('espresso_multisite');
        }
    }



    /**
     * A blog was just created; let's immediately create its row in the blog meta table and
     * set its last updated time and status
     * (otherwise, if we wait, it's possible to get multiple simultenous requests
     * which will cause duplicate entries in the blog meta table)
     */
    public static function new_blog_created($blog_id)
    {
        EEM_Blog::instance()->update_by_ID(
            array(
                'BLG_last_requested' => current_time('mysql', true),
                'STS_ID'             => EEM_Blog::status_up_to_date,
            ),
            $blog_id
        );
    }



    /**
     * Used to reset all static properties in this module.
     * Typically used by unit tests and should NOT be used in production.
     */
    public static function reset()
    {
        self::$shortcode_active = false;
        self::$_do_full_reset = false;
    }



    /**
     *        @ override magic methods
     *        @ return void
     */
    public function __set($a, $b)
    {
        return false;
    }



    public function __get($a)
    {
        return false;
    }



    public function __isset($a)
    {
        return false;
    }



    public function __unset($a)
    {
        return false;
    }



    public function __clone()
    {
        return false;
    }



    public function __wakeup()
    {
        return false;
    }



    public function __destruct()
    {
        return false;
    }



}

// End of file EED_Multisite.module.php
// Location: /wp-content/plugins/espresso-multisite/EED_Multisite.module.php
