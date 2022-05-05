<?php

namespace EventSmart\Multisite\core\services\database\service;

use EE_Admin_Page;
use EE_Error;
use EED_Batch;
use EEH_URL;
use Exception;

class DbServiceJobManager
{
	public static function run(string $selected_service, array $valid_form_data): bool
	{
		try {
			$job_assessment = AvailableDbServices::assessment($selected_service);
			$job_data       = AvailableDbServices::data($selected_service, $valid_form_data);
			$job_handler    = AvailableDbServices::handler($selected_service);
			$job_info       = AvailableDbServices::info($selected_service);

			// get array of parameters utilized by both job phases
			$base_parameters = $job_info->prepForRequest();

			// build array of parameters for the actual service job
			$job_parameters = $base_parameters
							  + ['job_handler' => $job_handler]
							  + $job_data->prepForServiceRequest();

			// is an assessment phase required?
			if ($job_data->hasAssessmentPhase()) {
				// we'll be redirecting to the actual service job AFTER the assessment phase is completed,
				// so convert the service job parameters into a URL and add it to the assessment parameters
				$service_job_url = EE_Admin_Page::add_query_args_and_nonce($job_parameters, admin_url());
				// overwrite the $job_parameters with the new ones for the assessment phase
				$job_parameters  = $base_parameters
								   + [
									   'job_assessment'  => $job_assessment,
									   'service_job_url' => $service_job_url,
								   ]
								   + $job_data->prepForAssessmentRequest();
			}

			// url encode everything and redirect
			EEH_URL::safeRedirectAndExit(
				EE_Admin_Page::add_query_args_and_nonce(
					array_map('urlencode', $job_parameters),
					admin_url()
				)
			);
		} catch (Exception $exception) {
			EE_Error::add_error($exception->getMessage(), __FILE__, __FUNCTION__, __LINE__);
		}
		return false;
	}
}
