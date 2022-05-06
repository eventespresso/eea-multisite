<?php

namespace EventSmart\Multisite\core\services\database\service;

use EE_Admin_Page;

/**
 * Class DbServiceJobData
 *
 * @author  Brent Christensen
 * @package EventSmart\Multisite\core\services\database\service
 * @since   $VID:$
 */
abstract class DbServiceJobData
{
    /**
     * URL of admin screen where service tool is being run from
     */
    private $admin_page_url;

    /**
     * whether to automatically redirect to return URL after job completion
     * or pause to display user notice and display a link for trigger redirect to return URL
     */
    private $auto_redirect_on_complete = true;

    /**
     * whether to only run the service assessment without making any changes [true] or proceed with repairs [false]
     */
    private $auto_run_service = false;

    /**
     * whether to halt execution if an error occurs [true] or attempt to continue [false]
     */
    private $ignore_errors = false;

    /**
     * URL of admin screen to redirect to after service job has run
     */
    private $return_url = '';


    /**
     * @param array $arguments
     */
    public function __construct(array $arguments)
    {
        $this->admin_page_url = $arguments['admin_page_url'];
        $this->setReturnUrl($arguments['return_url'] ?? '');
        $this->setIgnoreErrors($arguments['ignore_errors'] ?? false);
        $this->setAutoRunService($arguments['auto_run_service'] ?? false);
        $this->setAutoRedirectOnComplete($arguments['auto_redirect_on_complete'] ?? true);
    }


    /**
     * IMPORTANT !!!
     * This method needs to be overridden and return true
     * if a job requires an initial assessment phase,
     * otherwise the batch job will jump directly to the service job
     *
     * @return bool
     */
    abstract public function hasAssessmentPhase(): bool;


    /**
     * can be overridden by child classes to provide any extra data
     * that might need to be gathered now before doing anything else
     *
     * @return array
     */
    public function data(): array
    {
        return [];
    }


    /**
     * @param bool $auto_redirect_on_complete
     */
    public function setAutoRedirectOnComplete(bool $auto_redirect_on_complete): void
    {
        $this->auto_redirect_on_complete = filter_var($auto_redirect_on_complete, FILTER_VALIDATE_BOOLEAN);
    }


    /**
     * @param bool|int|string $auto_run_service
     */
    public function setAutoRunService($auto_run_service): void
    {
        $this->auto_run_service = filter_var($auto_run_service, FILTER_VALIDATE_BOOLEAN);
    }


    /**
     * @param bool|int|string $ignore_errors
     */
    public function setIgnoreErrors($ignore_errors): void
    {
        $this->ignore_errors = filter_var($ignore_errors, FILTER_VALIDATE_BOOLEAN);
    }


    /**
     * @param string|null $return_url
     */
    public function setReturnUrl(?string $return_url): void
    {
        $this->return_url = $return_url
            ?: EE_Admin_Page::add_query_args_and_nonce(
                ['action' => DbServiceForm::FORM_SLUG],
                $this->admin_page_url
            );
    }


    /**
     * displayed at the start of a service assessment
     *
     * @return string
     */
    public function assessmentStartNotice(): string
    {
        return '';
    }


    /**
     * displayed at the start of a service job
     *
     * @return string
     */
    public function jobStartNotice(): string
    {
        return __('You will be redirected automatically when job is complete.', 'event_espresso');
    }


    /**
     * @return array
     */
    public function prepForAssessmentRequest(): array
    {
        return [
                   'admin_page_url'    => $this->admin_page_url,
                   'assessment_notice' => $this->assessmentStartNotice(),
                   'auto_run_service'  => $this->auto_run_service,
                   'ignore_errors'     => $this->ignore_errors,
                   'return_url'        => $this->return_url,
               ]
               + $this->data();
    }


    /**
     * @return array
     */
    public function prepForServiceRequest(): array
    {
        return [
                   'admin_page_url'            => $this->admin_page_url,
                   'auto_redirect_on_complete' => $this->auto_redirect_on_complete,
                   'job_start_notice'          => $this->jobStartNotice(),
                   'ignore_errors'             => $this->ignore_errors,
                   'return_url'                => $this->return_url,
               ]
               + $this->data();
    }
}
