<?php

namespace EventSmart\Multisite\core\services\database\service\tools\ToggleRecaptcha;

use EE_Config;
use EED_Multisite;
use EventEspressoBatchRequest\Helpers\JobParameters;
use EventEspressoBatchRequest\Helpers\JobStepResponse;
use EventSmart\Multisite\core\services\database\service\BlogsServiced;
use EventSmart\Multisite\core\services\database\service\DbServiceJobHandler;
use Exception;

/**
 * Class JobHandler
 * updates the event meta table venue column with the corresponding venue id for all blogs
 *
 * @author  Brent Christensen
 * @package EventSmart\Multisite\core\services\database\service\tools\EventVenueMigration
 * @since   $VID:$
 */
class JobHandler extends DbServiceJobHandler
{
    /**
     * key for saving extra data within the JobParameters
     */
    private const DATA_KEY_BLOGS_NEEDING_SERVICING = 'blogs_using_recaptcha';


    /**
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     */
    public function create_job(JobParameters $job_parameters)
    {
        $this->blogs_serviced      = new BlogsServiced($this->db_option_name);
        $blogs_that_need_servicing = $this->blogs_serviced->findBlogsThatNeedServicing();
        $this->first_blog_id       = key($blogs_that_need_servicing) ?: 0;
        $blog_count                = $this->blogsUsingRecaptcha($blogs_that_need_servicing);
        $this->setJobSize($blog_count);
        $this->setBatchSizeCap(5);
        $job_parameters->add_extra_data(self::DATA_KEY_BLOGS_NEEDING_SERVICING, $blogs_that_need_servicing);
        $this->updateTextHeader(esc_html__('Servicing in progress...', 'event_espresso'));
        return new JobStepResponse($job_parameters, $this->feedback);
    }


    /**
     * @param array $blogs_that_need_servicing
     * @return int
     */
    private function blogsUsingRecaptcha(array $blogs_that_need_servicing): int
    {
        return array_reduce(
            $blogs_that_need_servicing,
            function ($count, $blog) {
                if (
                    isset($blog['status'], $blog['data']['uses_captcha'])
                    && $blog['status'] === BlogsServiced::STATUS_NEEDS_SERVICING
                ) {
                    $uses_captcha = $blog['data']['uses_captcha'] ?? false;
                    $count        += $uses_captcha ? 1 : 0;
                }
                return $count;
            },
            0
        );
    }


    /**
     * @param JobParameters $job_parameters
     * @return int
     */
    private function findNextBlogToService(JobParameters $job_parameters): int
    {
        return $this->blogs_serviced->nextBlogID();
    }


    /**
     * @param JobParameters $job_parameters
     * @throws Exception
     */
    protected function executeJob(JobParameters $job_parameters)
    {
        $this->feedback[] = "<strong>Servicing Blog $this->current_blog_id</strong>";
        $this->blogs_serviced->findBlog($this->current_blog_id);
        $updated = $this->processBlog();
        $this->processResults($job_parameters, $updated);
    }


    /**
     * @return bool
     */
    private function processBlog(): bool
    {
        EED_Multisite::perform_full_reset();
        switch_to_blog($this->current_blog_id);
        $config                            = EE_Config::instance();
        $config->registration->use_captcha = false;
        $updated                           = $config->update_espresso_config();
        restore_current_blog();
        return $updated;
    }


    /**
     * @param JobParameters $job_parameters
     * @param bool          $updated
     */
    private function processResults(JobParameters $job_parameters, bool $updated)
    {
        $this->blogs_serviced->addBlogData($this->current_blog_id, ['updated' => $updated]);
        if ($updated) {
            $this->updateText(' ☆ reCaptcha turned OFF');
            $this->blogs_serviced->serviceCompleted($this->current_blog_id);
            $blogs_that_need_servicing = $job_parameters->extra_datum(self::DATA_KEY_BLOGS_NEEDING_SERVICING, []);
            unset($blogs_that_need_servicing[ $this->current_blog_id ]);
            $job_parameters->add_extra_data(self::DATA_KEY_BLOGS_NEEDING_SERVICING, $blogs_that_need_servicing);
            $this->processed++;
        } else {
            $job_parameters->add_extra_data('error', "Failed to update reCaptcha for blog $this->current_blog_id");
        }
    }


    /**
     * used to advance from one batch job to another
     * primarily used for executing a job assessment phase where an accurate count of items to update can be made,
     * followed by the actual update job.
     * By default, this function won't do anything until overridden in a chile class.
     *
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     */
    public function advance_job(JobParameters $job_parameters): JobStepResponse
    {
        $this->updateTextHeader(__('Servicing Completed', 'event_espresso'));
        $this->updateText("<a class='button button--primary' href='$this->return_url'>Return</a>");
        // todo the above button needs to point to the complete step which then has to auto redirect
        $job_parameters->dontDeleteJobRecord();
        $job_parameters->set_status(JobParameters::status_pause);
        return new JobStepResponse($job_parameters, $this->feedback);
    }


    /**
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     */
    public function cleanup_job(JobParameters $job_parameters)
    {
        $this->blogs_serviced = new BlogsServiced($this->job_name);
        $this->blogs_serviced->deleteOption();
        $job_parameters->deleteJobRecord();
        return new JobStepResponse($job_parameters, $this->feedback);
    }
}
