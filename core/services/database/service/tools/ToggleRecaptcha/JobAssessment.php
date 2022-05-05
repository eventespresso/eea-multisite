<?php

namespace EventSmart\Multisite\core\services\database\service\tools\ToggleRecaptcha;

use EE_Config;
use EED_Multisite;
use EEH_Activation;
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
		$this->setBatchSizeCap(50);
		$this->setJobSize(count($this->blog_ids));
		return new JobStepResponse($job_parameters, $this->feedback);
	}


	/**
	 * @param JobParameters $job_parameters
	 * @throws Exception
	 */
	protected function executeJob(JobParameters $job_parameters)
	{
		$this->updateText("<strong>Blog $this->current_blog_id Assessment</strong>");
		$this->blogs_serviced->findBlog($this->current_blog_id);
		$uses_captcha = $this->processBlog();
		$this->processResults($uses_captcha);
	}


	/**
	 * @return bool
	 */
	private function processBlog(): bool
	{
		EED_Multisite::perform_full_reset();
		switch_to_blog($this->current_blog_id);
		EEH_Activation::verify_default_pages_exist();
		$turned_off_by_garth = $this->turnedOffByBigG();
		$uses_captcha = in_array($this->current_blog_id, $turned_off_by_garth) || $this->checkRecaptchaStatusForBlog();
		restore_current_blog();
		return $uses_captcha;
	}


	/**
	 * @param bool $uses_captcha
	 */
	private function processResults(bool $uses_captcha)
	{
		if ($uses_captcha) {
			$this->blogs_serviced->requiresServicing($this->current_blog_id);
			$this->updateText(' ⦿ blog uses reCaptcha');
		} else {
			$this->blogs_serviced->servicingNotNeeded($this->current_blog_id);
			$this->updateText(' ⨯ blog does not use reCaptcha');
		}
		$this->blogs_serviced->addBlogData($this->current_blog_id, ['uses_captcha' => $uses_captcha]);
		$this->processed++;
	}


	/**
	 * @param JobParameters $job_parameters
	 * @return JobStepResponse
	 */
	public function advance_job(JobParameters $job_parameters): JobStepResponse
	{
		$this->blogs_serviced = new BlogsServiced($this->db_option_name);
		$blogs = 0;
		$blog_data = $this->blogs_serviced->getAll();
		foreach ($blog_data as $blog) {
			$uses_captcha = $blog['data']['uses_captcha'] ?? false;
			$blogs += $uses_captcha ? 1 : 0;
		}
		$this->updateTextHeader(__('Service Assessment Completed', 'event_espresso'));

		$start_button = "<a class='button button--primary' href='$this->service_job_url'>begin update process</a>";
		switch ($blogs) {
			case 0;
				$this->updateText("There are $blogs blogs currently using reCaptcha.");
				$this->updateText("<a class='button button--primary' href='$this->return_url'>Finish Job</a>");
				// $job_parameters->deleteJobRecord();
				break;
			case 1;
				$this->updateText(
					$this->infoWrapper(
						"There is $blogs blog currently using reCaptcha."
					)
				);
				$this->updateText($start_button);
				// $job_parameters->dontDeleteJobRecord();
				break;
			case 2;
			default:
				$this->updateText(
					$this->infoWrapper(
						"There are $blogs blogs currently using reCaptcha."
					)
				);
				$this->updateText($start_button);
				break;
		}
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
		$job_parameters->dontDeleteJobRecord();
		return new JobStepResponse($job_parameters, $this->feedback);
	}



	/**
	 * @return bool
	 */
	private function checkRecaptchaStatusForBlog(): bool
	{
		$config = EE_Config::instance();
		return $config->registration->use_captcha;
	}


	/**
	 * @return array
	 */
	private function turnedOffByBigG(): array
	{
		return [
			21065,
			14963,
			4608,
			12828,
			20992,
			36756,
			18024,
			4481,
			29676,
			30733,
			23811,
			29840,
			36578,
			5385,
			27230,
			20058,
			36729,
			30812,
			32303,
			3638,
			3674,
			32924,
			29076,
			13316,
			36788,
			61,
			22888,
			13086,
			24894,
			3390,
			33398,
			31580,
			28853,
			6108,
			36664,
			22655,
			33615,
			12694,
			30597,
			951,
			32229,
			28801,
			10658,
			36604,
			24232,
			8021,
			34426,
			31950,
			22939,
			25206,
			27893,
			23404,
			26694,
			28728,
			29889,
			36705,
			32691,
			36700,
			36777,
			2532,
			10813,
			32407,
			29897,
			17940,
			1040,
			34108,
			37535,
			30769,
			6177,
			29773,
			23507,
			35976,
			37621,
			35940,
			32917,
			36798,
			25087,
			16062,
			28716,
			29711,
			22043,
			19678
		];
	}
}
