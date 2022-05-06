<?php

namespace EventSmart\Multisite\core\services\database\service\tools\EventVenueMigration;

use EventSmart\Multisite\core\services\database\service\DbServiceJobData;

/**
 * Class JobData
 * The actual data needed to perform the batch jobs.
 *
 * @author  Brent Christensen
 * @package EventSmart\Multisite
 * @since   $VID:$
 */
class JobData extends DbServiceJobData
{
    /**
     * IMPORTANT !!!
     * This method needs to be overridden and return true
     * if a job requires an initial assessment phase,
     * otherwise the batch job will jump directly to the service job
     *
     * @return bool
     */
    public function hasAssessmentPhase(): bool
    {
        return true;
    }


    public function assessmentStartNotice(): string
    {
        return __('Beginning Service Assessment.', 'event_espresso');
    }


    public function jobStartNotice(): string
    {
        return __('Service in Progress...', 'event_espresso');
    }
}
