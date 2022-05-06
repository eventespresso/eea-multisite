<?php

namespace EventEspressoBatchRequest\JobHandlers;

use EE_Blog;
use EE_Data_Migration_Manager;
use EE_Error;
use EE_Maintenance_Mode;
use EE_Multisite_Migration_Manager;
use EED_Multisite;
use EEM_Blog;
use EventEspressoBatchRequest\JobHandlerBaseClasses\JobHandler;
use EventEspressoBatchRequest\Helpers\JobParameters;
use EventEspressoBatchRequest\Helpers\JobStepResponse;
use ReflectionException;

/**
 * Class MultisiteMigration
 *
 * @author  Mike Nelson
 * @package EventEspressoBatchRequest\JobHandlers
 */
class MultisiteMigration extends JobHandler
{
    /**
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function create_job(JobParameters $job_parameters): JobStepResponse
    {
        $job_parameters->set_job_size(EEM_Blog::instance()->count());
        return new JobStepResponse(
            $job_parameters,
            esc_html__('Assessment (and possible migrations) started', 'event_espresso')
        );
    }


    /**
     * @param JobParameters $job_parameters
     * @param int           $step_size
     * @return JobStepResponse
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function continue_job(JobParameters $job_parameters, $step_size = 50): JobStepResponse
    {
        $assess_step_size_value      = defined('EE_MULTISITE_ASSESS_STEP_SIZE_VALUE')
            ? EE_MULTISITE_ASSESS_STEP_SIZE_VALUE
            : 100;
        $steps_taken                 = 0;
        $response_messages           = [];
        $blogs_assessed_and_migrated = 0;
        do {
            if (EEM_Blog::instance()->count_blogs_needing_migration()) {
                $migration_step_response = EE_Multisite_Migration_Manager::instance()->migration_step($step_size);
                $steps_taken             += $migration_step_response['num_migrated'];
                $response_messages[]     = sprintf(
                    esc_html__('Migrated %1$s records from %2$s during migration step %3$s.', 'event_espresso'),
                    $migration_step_response['num_migrated'],
                    $migration_step_response['current_blog_name'],
                    $migration_step_response['current_dms']['script']
                );
                if (
                    isset($migration_step_response['current_dms']['status'])
                    && $migration_step_response['current_dms']['status'] !== EE_Data_Migration_Manager::status_continue
                ) {
                    // ok so we've finished the last migration script of that site.
                    // we should count that as an assessment step (the wrap-up from
                    // a migration is quite expensive but isn't a "step" per say)
                    $blogs_assessed_and_migrated++;
                    $steps_taken += $assess_step_size_value;
                }
            } else {
                // no blogs need to be migrated right now.
                // Maybe we need to assess some more?
                $blogs_needing_assessment = EEM_Blog::instance()->get_all_blogs_maybe_needing_migration(['limit' => 1]);
                if (empty($blogs_needing_assessment)) {
                    // so none need migrating. And none need assessment. We're done right?
                    $job_parameters->set_status(JobParameters::status_complete);
                } else {
                    // so we found a blog that needs to be assessed. so assess it
                    $blog            = array_shift($blogs_needing_assessment);
                    $needs_migrating = $this->site_need_migration($blog);
                    if ($needs_migrating) {
                        $response_messages[] = sprintf(
                            esc_html__('Assessed %1$s. It needs to be migrated...', 'event_espresso'),
                            $blog->name()
                        );
                    } else {
                        $response_messages[] = sprintf(
                            esc_html__('Assessed %1$s. No migrations required.', 'event_espresso'),
                            $blog->name()
                        );
                        $blogs_assessed_and_migrated++;
                    }
                }
                $steps_taken += $assess_step_size_value;
            }
        } while ($steps_taken < $step_size && $job_parameters->status() === JobParameters::status_continue);

        $job_parameters->set_units_processed(EEM_Blog::instance()->count_blogs_up_to_date());

        return new JobStepResponse(
            $job_parameters,
            implode(
                apply_filters('FHEE__MultisiteMigration__continue_job_response_glue', ','),
                $response_messages
            )
        );
    }


    /**
     * @param EE_Blog $blog
     * @return boolean
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function site_need_migration(EE_Blog $blog): bool
    {
        EED_Multisite::do_full_reset();
        switch_to_blog($blog->ID());
        $needs_migrating = EE_Maintenance_Mode::instance()->set_maintenance_mode_if_db_old();
        if ($needs_migrating) {
            $blog->set_STS_ID(EEM_Blog::status_out_of_date);
            $needs_migrating = true;
        } else {
            $blog->set_STS_ID(EEM_Blog::status_up_to_date);
            $needs_migrating = false;
        }
        restore_current_blog();
        $blog->save();
        return $needs_migrating;
    }


    /**
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     */
    public function cleanup_job(JobParameters $job_parameters): JobStepResponse
    {
        return new JobStepResponse(
            $job_parameters,
            esc_html__('All done multisite migration and assessment', 'event_espresso')
        );
    }
}
