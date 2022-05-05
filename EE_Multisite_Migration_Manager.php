<?php

use EventEspresso\core\domain\services\custom_post_types\RewriteRules;

/**
 * EE_Multisite_Migration_Manager
 *
 * @package       Event Espresso
 * @subpackage    EventEspresso\Multisite
 * @author        Mike Nelson
 */
class EE_Multisite_Migration_Manager
{

    /**
     * @var EE_Multisite_Migration_Manager|null $_instance
     */
    private static $_instance;


    /**
     * @singleton method used to instantiate class object
     * @return EE_Multisite_Migration_Manager
     */
    public static function instance(): EE_Multisite_Migration_Manager
    {
        // check if class object is instantiated
        if (! self::$_instance instanceof EE_Multisite_Migration_Manager) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * resets the singleton to its brand-new state (but does NOT delete old references to the old singleton. Meaning,
     * all new usages of the singleton should be made with Classname::instance()) and returns it
     *
     * @return EE_Multisite_Migration_Manager
     */
    public static function reset(): EE_Multisite_Migration_Manager
    {
        self::$_instance = null;
        return self::instance();
    }


    /**
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function __construct()
    {
        EE_Registry::instance()->load_core('Data_Migration_Manager');
    }


    /**
     * Migrates $records_to_migrate records from the currently-migrating blog
     * on its currently-migrating script. When done the current migration script on
     * the current blog, returns the number of records migrated so far.
     *
     * @param int     $records_to_migrate
     * @return array {
     * @type string   $current_blog_name         ,
     * @type string[] $current_blog_script_names ,
     * @type array    $current_dms               {
     * @type int      $records_to_migrate        from the current migration script,
     * @type int      $records_migrated          from the current migration script,
     * @type string   $status                    one of EE_Data_Migration_Manager::status_*,
     * @type string   $script                    verbose name of the current DMS,
     *                                           }
     * @type string   $message                   string describing what was done during this step
     *                                           }
     * @throws ReflectionException
     * @throws ReflectionException
     */
    public function migration_step(int $records_to_migrate): array
    {
        $num_migrated                = 0;
        $multisite_migration_message = '';
        $current_script_names        = [];
        // in addition to limiting the number of records we migrate during each step,
        // see https://events.codebasehq.com/projects/event-espresso/tickets/8332
        $max_blogs_to_migrate = max(
            1,
            defined('EE_MIGRATION_STEP_SIZE_BLOGS') ? EE_MIGRATION_STEP_SIZE_BLOGS : 5
        );
        $results              = [];
        $blogs_migrated       = 0;
        $blog_to_migrate      = null;
        $this->setupFilterToAvoidFlushingPermalinks();
        try {
            // while we have more records and blogs to migrate, step through each blog
            while ($blogs_migrated++ < $max_blogs_to_migrate
                   && $num_migrated < $records_to_migrate
                   && $blog_to_migrate = EEM_Blog::instance()->get_migrating_blog_or_most_recently_requested()
            ) {
                EED_Multisite::do_full_reset();
                switch_to_blog($blog_to_migrate->ID());
                // and keep hammering that blog so long as it has stuff to migrate
                do {
                    // pretend each call is a separate request
                    $results = EE_Data_Migration_Manager::reset()->migration_step($records_to_migrate - $num_migrated);

                    $num_migrated                += $results['records_migrated'];
                    $multisite_migration_message .= "<br>" . $results['message'];
                    switch ($results['status']) {
                        case EE_Data_Migration_Manager::status_completed:
                        case EE_Data_Migration_Manager::status_continue:
                            $status_indicates_continue = true;
                            break;
                        case EE_Data_Migration_Manager::status_no_more_migration_scripts:
                        case EE_Data_Migration_Manager::status_fatal_error:
                        default:
                            $status_indicates_continue = false;
                    }
                } while ($num_migrated < $records_to_migrate && $status_indicates_continue);

                // if we're done this migration step, grab the remaining scripts for this blog
                // before we switch back to the network admin
                if ($num_migrated >= $records_to_migrate) {
                    $current_script_names = $this->_get_applicable_dms_names();
                }

                // if appropriate, update this blog's status
                if ($results['status'] == EE_Data_Migration_Manager::status_fatal_error) {
                    $blog_to_migrate->set_STS_ID(EEM_Blog::status_borked);

                    $multisite_migration_message = sprintf(
                        esc_html__('%1$sSkipping migration of %2$s%3$s', 'event_espresso'),
                        '<del>',
                        $blog_to_migrate->name(),
                        '</del>'
                    ) . '<br>' . $multisite_migration_message;

                    $crash_email_subject = sprintf(
                        esc_html__('Multisite Migration Error migrating %s', 'event_espresso'),
                        $blog_to_migrate->name()
                    );

                    $crash_email_body = sprintf(
                        esc_html__(
                            'The blog at %1$s had a fatal error while migrating. Here is their migration history: %2$s',
                            'event_espresso'
                        ),
                        $blog_to_migrate->site_url(),
                        print_r(EEM_System_Status::instance()->get_ee_migration_history(), true)
                    );

                    // switch blog now so we email the network admin, not the blog admin
                    restore_current_blog();
                    wp_mail(get_site_option('admin_email'), $crash_email_subject, $crash_email_body);
                } elseif ($results['status'] == EE_Data_Migration_Manager::status_no_more_migration_scripts) {
                    $blog_to_migrate->set_STS_ID(EEM_Blog::status_up_to_date);

                    $multisite_migration_message = sprintf(
                        esc_html__('%1$sFinished migrating %2$s%3$s', 'event_espresso'),
                        '<h3>',
                        $blog_to_migrate->name(),
                        '</h3>'
                    ) . '<br>' . $multisite_migration_message;

                    restore_current_blog();
                } else {
                    $blog_to_migrate->set_STS_ID(EEM_Blog::status_migrating);
                    restore_current_blog();
                }

                $blog_to_migrate->save();
            }

            if ($blog_to_migrate) {
                return [
                    'current_blog_name'         => $blog_to_migrate->name(),
                    'current_blog_script_names' => $current_script_names,
                    'current_dms'               => $results,
                    'message'                   => $multisite_migration_message,
                ];
            } else {
                // theoretically we could receive another request like this when there are no
                // more blogs that need to be migrated
                return [
                    'current_blog_name'         => '',
                    'current_blog_script_names' => [],
                    'current_dms'               => [
                        'records_to_migrate' => 1,
                        'records_migrated'   => 1,
                        'status'             => EE_Data_Migration_Manager::status_no_more_migration_scripts,
                        'script'             => esc_html__("Data Migration Completed Successfully", "event_espresso"),
                    ],
                    'message'                   => esc_html__('All blogs up-to-date', 'event_espresso'),
                ];
            }
        } catch (EE_Error $e) {
            return [
                'current_blog_name'         => esc_html__('Unable to determine current blog', 'event_espresso'),
                'current_blog_script_names' => [],
                'current_dms'               => [
                    'records_to_migrate' => 1,
                    'records_migrated'   => 0,
                    'status'             => EE_Data_Migration_Manager::status_fatal_error,
                    'script'             => esc_html__('Error finding current script to migrate', 'event_espresso'),
                ],
                'message'                   => $e->getMessage() . (WP_DEBUG ? $e->getTraceAsString() : ''),
            ];
        }
    }


    /**
     * Sets up a filter to avoid flushing permalinks during multisite migration
     *
     * @since $VID:$
     */
    protected function setupFilterToAvoidFlushingPermalinks()
    {
        add_filter(
            $this->getEeRewriteRulesOptionName(),
            [$this, 'dontFlushPermalinksDuringMigration']
        );
    }


    /**
     * Gets the name of the filter used to get the option storing whether EE's permalinks should be flushed or not.
     *
     * @return string
     */
    protected function getEeRewriteRulesOptionName(): string
    {
        return 'pre_option_' . RewriteRules::OPTION_KEY_FLUSH_REWRITE_RULES;
    }


    /**
     * Filter callback that prevents flushing permalinks during a multisite migration, which led to broken permalinks.
     * See https://github.com/eventespresso/eventsmart.com-website/issues/562.
     *
     * @param $flush_permalink_flag_option_value
     * @return null
     * @since $VID:$
     */
    public function dontFlushPermalinksDuringMigration($flush_permalink_flag_option_value)
    {
        // I assume this is being called from event-espresso-core/core/domain/services/custom_post_types/RewriteRules.php.
        // where, when running on multisite, we don't want to actually call flush_rewrite_rules() because of the
        // reasons mentioned on
        // https://jeremyfelt.com/2015/07/17/flushing-rewrite-rules-in-wordpress-multisite-for-fun-and-profit/.
        // So instead, just delete the rewrite rules (they'll get re-generated on next normal visit to the site)
        delete_option('rewrite_rules');
        // and pretend the option to refresh permalinks wasn't set.
        // But of course, in order to do that, avoid an infinite loop by temporarily removing this filter.
        remove_filter(
            $this->getEeRewriteRulesOptionName(),
            [$this, 'dontFlushPermalinksDuringMigration']
        );
        update_option(RewriteRules::OPTION_KEY_FLUSH_REWRITE_RULES, false);
        // then put it back in place for the next site migrated during this request.
        $this->setupFilterToAvoidFlushingPermalinks();
        return null;
    }


    /**
     * Gets the pretty names for all the data migration scripts needing to run
     * on the current blog
     *
     * @return string[]
     * @throws EE_Error
     */
    protected function _get_applicable_dms_names(): array
    {
        $scripts      = EE_Data_Migration_Manager::instance()->check_for_applicable_data_migration_scripts();
        $script_names = [];
        foreach ($scripts as $script) {
            $script_names[] = $script->pretty_name();
        }
        return $script_names;
    }


    /**
     * Assesses $num_to_assess blogs and finds whether they need to be migrated or not,
     * and updates their status. Returns the number that were found to need migrating
     * (NOT the total number needing migrating. For that, use EEM_Blog::count_blogs_needing_migration())
     *
     * @param int $num_to_assess
     * @return int number of blogs needing to be migrated, amongst those inspected
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function assess_sites_needing_migration(int $num_to_assess = 10): int
    {
        $blogs = EEM_Blog::instance()->get_all_blogs_maybe_needing_migration(['limit' => $num_to_assess]);

        $blogs_needing_to_migrate = 0;
        foreach ($blogs as $blog) {
            // switch to that blog and assess whether or not it needs to be migrated
            EED_Multisite::do_full_reset();
            switch_to_blog($blog->ID());
            $needs_migrating = EE_Maintenance_Mode::instance()->set_maintenance_mode_if_db_old();
            if ($needs_migrating) {
                $blog->set_STS_ID(EEM_Blog::status_out_of_date);
                $blogs_needing_to_migrate++;
            } else {
                $blog->set_STS_ID(EEM_Blog::status_up_to_date);
            }
            restore_current_blog();
            $blog->save();
        }
        return $blogs_needing_to_migrate;
    }
}
// End of file EE_Multisite_Migration_Manager.php
