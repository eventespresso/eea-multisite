<?php

namespace EventSmart\Multisite\core\services\database\service\tools\EventVenueMigration;

use EventEspressoBatchRequest\Helpers\JobParameters;
use EventEspressoBatchRequest\Helpers\JobStepResponse;
use EventSmart\Multisite\core\services\database\service\BlogsServiced;
use EventSmart\Multisite\core\services\database\service\DbServiceJobHandler;
use Exception;

/**
 * Class JobAssessment
 * evaluates how many blogs have detached venues caused by migration timeouts
 *
 * @author  Brent Christensen
 * @package EventSmart\Multisite\core\services\database\service\tools\EventVenueMigration
 * @since   $VID:$
 */
class JobAssessment extends DbServiceJobHandler
{
    /**
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     */
    public function create_job(JobParameters $job_parameters)
    {
        $this->setJobSize(count($this->blog_ids));
        return new JobStepResponse(
            $job_parameters,
            $this->feedback
        );
    }


    /**
     * @param JobParameters $job_parameters
     * @throws Exception
     */
    protected function executeJob(JobParameters $job_parameters)
    {
        $this->updateText("<strong>Blog $this->current_blog_id Assessment</strong>");
        $this->blogs_serviced->findBlog($this->current_blog_id);
        $event_IDs = $this->processBlog();
        $this->processResults($event_IDs);
    }


    /**
     * @return array
     */
    private function processBlog(): array
    {
        switch_to_blog($this->current_blog_id);
        $event_IDs = $this->getIDsForEventsWithDetachedVenues();
        restore_current_blog();
        return $event_IDs;
    }


    /**
     * @param array $event_IDs
     */
    private function processResults(array $event_IDs)
    {
        $detached_venues = count($event_IDs);
        if ($detached_venues) {
            $this->blogs_serviced->requiresServicing($this->current_blog_id);
            $feedback = $detached_venues === 1
                ? " ⦿ $detached_venues event requires servicing"
                : " ⦿ $detached_venues events require servicing";
        } else {
            $this->blogs_serviced->servicingNotNeeded($this->current_blog_id);
            $feedback = ' ⨯ no servicing required';
        }
        $this->updateText($feedback);
        $this->blogs_serviced->addBlogData($this->current_blog_id, ['event_IDs' => $event_IDs]);
        $this->processed++;
    }


    /**
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     */
    public function advance_job(JobParameters $job_parameters): JobStepResponse
    {
        $this->blogs_serviced = new BlogsServiced($this->db_option_name);
        $blogs                = 0;
        $events               = 0;
        $blog_data            = $this->blogs_serviced->getAll();
        foreach ($blog_data as $blog) {
            $event_IDs       = $blog['data']['event_IDs'] ?? [];
            $detached_venues = count($event_IDs);
            $blogs           += $detached_venues ? 1 : 0;
            $events          += $detached_venues;
        }
        $this->updateTextHeader(__('Service Assessment Completed', 'event_espresso'));

        $start_button = "<a class='button button--primary' href='$this->service_job_url'>begin update process</a>";
        switch ($blogs) {
            case 0:
                $this->updateText("There are $blogs blogs requiring update.");
                $this->updateText("<a class='button button--primary' href='$this->return_url'>Finish Job</a>");
                $job_parameters->deleteJobRecord();
                break;
            case 1:
                $this->updateText(
                    $this->infoWrapper(
                        "There is $blogs blog with a total of $events events that have detached venues requiring an update."
                    )
                );
                $this->updateText($start_button);
                $job_parameters->dontDeleteJobRecord();
                break;
            case 2:
            default:
                $this->updateText(
                    $this->infoWrapper(
                        "There are $blogs blogs with a total of $events events that have detached venues requiring an update"
                    )
                );
                $this->updateText($start_button);
                $job_parameters->dontDeleteJobRecord();
                break;
        }
        $job_parameters->set_status(JobParameters::status_pause);
        return new JobStepResponse($job_parameters, $this->feedback);
    }


    /**
     * @param JobParameters $job_parameters
     * @return JobStepResponse
     */
    public function cleanup_job(JobParameters $job_parameters)
    {
        $job_parameters->dontDeleteJobRecord();
        return new JobStepResponse($job_parameters, $this->feedback);
    }


    /**
     * @return array
     */
    private function getIDsForEventsWithDetachedVenues(): array
    {
        global $wpdb;
        $query     = <<<QRY
SELECT em.EVT_ID
FROM {$wpdb->prefix}esp_event_meta AS em
JOIN {$wpdb->prefix}esp_event_venue AS ev
	ON (em.EVT_ID = ev.EVT_ID)
WHERE em.VNU_ID = 0
	AND ev.VNU_ID != 0;
QRY;
        $results   = $wpdb->get_results($query, ARRAY_A);
        $event_IDs = [];
        foreach ($results as $event_ID) {
            $event_IDs[] = is_array($event_ID) && isset($event_ID['EVT_ID']) ? $event_ID['EVT_ID'] : $event_ID;
        }
        return $event_IDs;
    }
}
