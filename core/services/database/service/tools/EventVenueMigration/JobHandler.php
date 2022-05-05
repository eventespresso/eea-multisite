<?php

namespace EventSmart\Multisite\core\services\database\service\tools\EventVenueMigration;

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
	private const DATA_KEY_BLOGS_NEEDING_SERVICING = 'blogs_needing_servicing';


	/**
	 * @param JobParameters $job_parameters
	 * @return JobStepResponse
	 */
	public function create_job(JobParameters $job_parameters)
	{
		$this->blogs_serviced      = new BlogsServiced($this->db_option_name);
		$blogs_that_need_servicing = $this->blogs_serviced->findBlogsThatNeedServicing();
		$this->first_blog_id       = key($blogs_that_need_servicing) ?: 0;
		$event_count               = $this->getCountOfEventsThatNeedServicing($blogs_that_need_servicing);
		$this->setJobSize($event_count);
		$job_parameters->add_extra_data(self::DATA_KEY_BLOGS_NEEDING_SERVICING, $blogs_that_need_servicing);
		$this->updateTextHeader(esc_html__('Servicing in progress...', 'event_espresso'));
		return new JobStepResponse($job_parameters, $this->feedback);
	}


	/**
	 * @param array $blogs_that_need_servicing
	 * @return int
	 */
	private function getCountOfEventsThatNeedServicing(array $blogs_that_need_servicing): int
	{
		return array_reduce(
			$blogs_that_need_servicing,
			function ($count, $blog) {
				if (
					isset($blog['status'], $blog['data']['event_IDs'])
					&& $blog['status'] === BlogsServiced::STATUS_NEEDS_SERVICING
				) {
					$count += count($blog['data']['event_IDs']);
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
		$this->feedback[]    = "<strong>Servicing Blog $this->current_blog_id</strong>";
		$blog                = $this->blogs_serviced->findBlog($this->current_blog_id);
		$previously_serviced = $blog['data']['serviced'] ?? [];
		$needs_servicing     = $blog['data']['event_IDs'] ?? [];
		$event_IDs           = array_diff($needs_servicing, $previously_serviced);
		$serviced            = $this->processBlog($event_IDs);
		$this->processResults($job_parameters, $event_IDs, $serviced);
	}


	/**
	 * @param array $event_IDs
	 * @return array
	 */
	private function processBlog(array $event_IDs): array
	{
		switch_to_blog($this->current_blog_id);
		$serviced = [];
		if (! empty($event_IDs)) {
			foreach ($event_IDs as $event_ID) {
				$updated          = $this->updateEventWithDetachedVenue($event_ID);
				$this->$this->updateText(" ⤑ event $event_ID " . ($updated ? 'updated' : 'NOT updated'));
				if ($updated) {
					$serviced[] = $event_ID;
				}
			}
		}
		restore_current_blog();
		return $serviced;
	}


	/**
	 * @param JobParameters $job_parameters
	 * @param array         $event_IDs
	 * @param array         $serviced
	 */
	private function processResults(JobParameters $job_parameters, array $event_IDs, array $serviced)
	{
		$this->blogs_serviced->addBlogData($this->current_blog_id, ['serviced' => $serviced]);
		$events_serviced = count($serviced);
		if ($events_serviced) {
			$feedback = $events_serviced === 1
				? " ☆ $events_serviced event serviced"
				: " ☆ $events_serviced events serviced";
			$this->updateText($feedback);
			if ($events_serviced >= count($event_IDs)) {
				$this->blogs_serviced->serviceCompleted($this->current_blog_id);
				$blogs_that_need_servicing = $job_parameters->extra_datum(self::DATA_KEY_BLOGS_NEEDING_SERVICING, []);
				unset($blogs_that_need_servicing[ $this->current_blog_id ]);
				$job_parameters->add_extra_data(self::DATA_KEY_BLOGS_NEEDING_SERVICING, $blogs_that_need_servicing);
			} else {
				$job_parameters->add_extra_data('error', "Failed to update all events for blog $this->current_blog_id");
			}
		}
		$this->processed += $events_serviced;
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


	/**
	 * @param $event_ID
	 * @return bool|int
	 */
	private function updateEventWithDetachedVenue($event_ID)
	{
		global $wpdb;
		$query = <<<QRY
UPDATE {$wpdb->prefix}esp_event_meta AS em
JOIN {$wpdb->prefix}esp_event_venue AS ev
    ON (em.EVT_ID = ev.EVT_ID)
SET em.VNU_ID = ev.VNU_ID
WHERE ev.VNU_ID != 0
    AND em.EVT_ID = %d;
QRY;
		return $wpdb->query($wpdb->prepare($query, $event_ID));
	}
}
