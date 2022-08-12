<?php

namespace EventSmart\Multisite\core\services\database\service;

use EventEspressoBatchRequest\Helpers\BatchRequestException;
use EventEspressoBatchRequest\Helpers\JobParameters;
use EventEspressoBatchRequest\Helpers\JobStepResponse;
use EventEspressoBatchRequest\JobHandlerBaseClasses\JobHandler;
use Exception;
use RuntimeException;

/**
 * Class DbServiceJobHandler
 *
 * @author  Brent Christensen
 * @package EventSmart\Multisite\core\services\database\service
 * @since   $VID:$
 */
abstract class DbServiceJobHandler extends JobHandler
{
    protected const DATA_KEY_BATCH_SIZE_CAP = 'batch_size_cap';

    protected const DATA_KEY_NEXT_BLOG_ID   = 'next_blog_id';

    /**
     * @var array
     */
    protected $blog_ids = [];

    /**
     * @var BlogsServiced|null
     */
    protected $blogs_serviced = null;

    /**
     * @var bool
     */
    protected $auto_redirect_on_complete = true;

    /**
     * @var bool
     */
    protected $auto_run_service = true;

    /**
     * @var bool
     */
    protected $ignore_errors = true;

    /**
     * @var int
     */
    protected $batch_size_cap = 25;

    /**
     * @var int
     */
    protected $current_blog_id = 0;

    /**
     * @var int
     */
    protected $first_blog_id = 0;

    /**
     * @var int
     */
    protected $last_blog_id = 0;

    /**
     * @var int
     */
    protected $job_size = 0;

    /**
     * @var int
     */
    protected $processed = 0;

    /**
     * @var string
     */
    protected $assessment_notice = '';

    /**
     * @var string
     */
    protected $job_start_notice = '';

    /**
     * @var string
     */
    protected $job_code = '';

    /**
     * @var string
     */
    protected $job_handler = '';

    /**
     * @var string
     */
    protected $job_name = '';

    /**
     * @var string
     */
    protected $db_option_name = '';

    /**
     * @var string
     */
    protected $service_job_url = '';

    /**
     * @var string
     */
    protected $return_action = '';

    /**
     * @var string
     */
    protected $return_url = '';


    /**
     * @throws RuntimeException
     */
    public function initialize()
    {
        // gather request parameters
        $this->assessment_notice         = $this->request_data['assessment_notice'] ?? '';
        $this->auto_redirect_on_complete = $this->request_data['auto_redirect_on_complete'] ?? true;
        $this->auto_run_service          = $this->request_data['auto_run_service'] ?? false;
        $this->job_start_notice          = $this->request_data['job_start_notice'] ?? '';
        $this->ignore_errors             = $this->request_data['ignore_errors'] ?? false;
        $this->job_code                  = $this->request_data['job_code'] ?? '';
        $this->job_handler               = $this->request_data['job_handler'] ?? '';
        $this->job_name                  = $this->request_data['job_name'] ?? '';
        $this->db_option_name            = $this->request_data['db_option_name'] ?? '';
        $this->service_job_url           = $this->request_data['service_job_url'] ?? '';
        $this->return_action             = $this->request_data['return'] ?? '';
        $this->return_url                = $this->request_data['return_url'] ?? '';

        // gather blog data
        $this->blog_ids       = $this->getBlogIDs();
        $this->blogs_serviced = new BlogsServiced($this->db_option_name);
        $this->blogs_serviced->initialize($this->blog_ids);
        $this->first_blog_id = $this->blogs_serviced->firstBlogID();
        $this->last_blog_id  = $this->blogs_serviced->lastBlogId();

        if (! $this->first_blog_id) {
            throw new RuntimeException('Could not retrieve an ID for the first blog!');
        }
        if (! $this->last_blog_id) {
            throw new RuntimeException('Could not retrieve an ID for the last blog!');
        }
    }


    /**
     * utilized in newer batch job implementations, but forwarding to existing methods for now.
     * Performs any necessary setup for starting the job. This is also a good
     * place to setup the $job_arguments which will be used for subsequent HTTP requests
     * when continue_job will be called
     *
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     * @throws BatchRequestException
     */
    public function createJob(JobParameters $job_parameters): JobStepResponse
    {
        if ($this->assessment_notice) {
            $this->updateTextHeader($this->assessment_notice . $this->spinner());
        }
        $job_response = $this->create_job($job_parameters);
        $job_parameters->set_job_size($this->job_size);
        $job_parameters->add_extra_data(self::DATA_KEY_BATCH_SIZE_CAP, 10);
        $this->addResponseData(
            ['auto_redirect_on_complete' => $job_parameters->request_datum('auto_redirect_on_complete')]
        );
        return $job_response;
    }


    /**
     * utilized in newer batch job implementations, but forwarding to existing methods for now.
     * Performs another step of the job
     *
     * @param JobParameters $job_parameters
     * @param int           $batch_size
     * @return JobStepResponse
     * @throws BatchRequestException
     * @throws Exception
     */
    public function continueJob(JobParameters $job_parameters, int $batch_size = 50): JobStepResponse
    {
        $job_response = $this->continue_job($job_parameters, $batch_size);
        $job_parameters->set_units_processed($this->processed);
        $this->updateText("processed this batch: $this->processed");
        $this->displayJobFinalResults($job_parameters);
        return $job_response;
    }


    /**
     * @param JobParameters $job_parameters
     * @param int           $batch_size
     * @return JobStepResponse
     * @throws Exception
     */
    public function continue_job(JobParameters $job_parameters, $batch_size = 50)
    {
        $start_time          = microtime(true);
        $batch_size          = min(
            $batch_size,
            $job_parameters->extra_datum(self::DATA_KEY_BATCH_SIZE_CAP, $this->batch_size_cap)
        );
        $processed_this_step = 0;
        $this->initializeDataForJobStep($job_parameters);

        do {
            try {
                $this->executeJob($job_parameters);
                if ($this->current_blog_id === $this->last_blog_id || $this->processed >= $this->job_size) {
                    // $status = $this->auto_run_service ? JobParameters::status_redirect : JobParameters::status_advance;
                    $status = $this->getStatusOnJobCompletion($job_parameters);
                } else {
                    $this->current_blog_id = $this->getNextBlogID($job_parameters);
                    $this->switchToNextBlog($job_parameters, $this->current_blog_id);
                    $status = $this->getStatusForJobStep($job_parameters);
                }
                $job_parameters->set_status($status);
                $processed_this_step++;
            } catch (Exception $exception) {
                if (! $this->ignore_errors) {
                    $job_parameters->add_extra_data('error', $exception->getMessage());
                }
            }
            $current_time = microtime(true);
        } while (
            $this->current_blog_id
            && $processed_this_step < $batch_size
            && $this->processed < $this->job_size
            && $current_time - $start_time < 45
            && $job_parameters->status() === JobParameters::status_continue
        );
        $job_parameters->add_extra_data('addOrReplace', 'prepend');
        return new JobStepResponse($job_parameters, $this->feedback);
    }


    /**
     * @param JobParameters $job_parameters
     * @return void
     */
    protected function initializeDataForJobStep(JobParameters $job_parameters)
    {
        $this->job_size        = $job_parameters->job_size();
        $this->processed       = $job_parameters->units_processed();
        $this->blogs_serviced  = new BlogsServiced($this->db_option_name);
        $this->current_blog_id = $this->getCurrentBlogID($job_parameters);
        if (! $this->current_blog_id) {
            $job_parameters->add_extra_data('error', 'Invalid or missing Blog ID');
            $job_parameters->set_status(JobParameters::status_error);
            $this->feedback[] = $this->errorWrapper("! ERROR - INVALID BLOG ID: $this->current_blog_id");
        }
    }


    /**
     * by default will return the very first blog ID (if nothing has been processed)
     * or whatever has been set in the JobParameters as the 'next_blog_id'.
     * OVERRIDE if alternate method for retrieving blog IDs is needed
     *
     * @param JobParameters $job_parameters
     * @return int
     */
    protected function getCurrentBlogID(JobParameters $job_parameters): int
    {
        return $this->processed === 0
            ? $this->first_blog_id
            : $job_parameters->extra_datum(DbServiceJobHandler::DATA_KEY_NEXT_BLOG_ID, 0);
    }


    /**
     * OVERRIDE if alternate method for retrieving next blog ID is needed
     *
     * @param JobParameters $job_parameters
     * @return int
     */
    protected function getNextBlogID(JobParameters $job_parameters): int
    {
        return $this->blogs_serviced->nextBlogID();
    }


    /**
     * OVERRIDE if alternate method for retrieving next blog ID is needed
     *
     * @param JobParameters $job_parameters
     * @param int           $next_blog_id
     */
    protected function switchToNextBlog(JobParameters $job_parameters, int $next_blog_id)
    {
        // set internal pointer
        $this->blogs_serviced->findBlog($next_blog_id);
        $this->updateText(" ⇆ switched to Blog $next_blog_id");
        $job_parameters->add_extra_data(DbServiceJobHandler::DATA_KEY_NEXT_BLOG_ID, $next_blog_id);
    }


    /**
     * OVERRIDE if additional logic is needed for determining job status
     *
     * @param JobParameters $job_parameters
     * @return string
     * @throws Exception
     */
    protected function getStatusForJobStep(JobParameters $job_parameters): string
    {
        return JobParameters::status_continue;
    }


    /**
     * OVERRIDE if additional logic is needed for determining job status
     *
     * @param JobParameters $job_parameters
     * @return string
     * @throws Exception
     */
    protected function getStatusOnJobCompletion(JobParameters $job_parameters): string
    {
        return JobParameters::status_advance;
    }


    /**
     * utilized in newer batch job implementations, but forwarding to existing methods for now.
     * used to advance from one batch job to another
     * primarily used for executing a job assessment phase where an accurate count of items to update can be made,
     * followed by the actual update job.
     *
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     */
    public function advanceJob(JobParameters $job_parameters): JobStepResponse
    {
        return $this->advance_job($job_parameters);
    }


    /**
     * utilized in newer batch job implementations, but forwarding to existing methods for now.
     * Performs any clean-up logic when we know the job is completed
     *
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     * @throws BatchRequestException
     */
    public function cleanupJob(JobParameters $job_parameters): JobStepResponse
    {
        return $this->cleanup_job($job_parameters);
    }


    /**
     * @return array
     */
    private function getBlogIDs(): array
    {
        global $wpdb;
        $query = "SELECT blog_id FROM $wpdb->blogs";
        // WHERE public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'
        return $wpdb->get_col($query);
    }


    /**
     * @param int $job_size
     */
    public function setJobSize(int $job_size): void
    {
        $this->job_size = $job_size;
    }


    /**
     * @param int $batch_size_cap
     */
    public function setBatchSizeCap(int $batch_size_cap): void
    {
        $this->batch_size_cap = $batch_size_cap;
    }
}
