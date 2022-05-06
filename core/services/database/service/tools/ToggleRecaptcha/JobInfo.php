<?php

namespace EventSmart\Multisite\core\services\database\service\tools\ToggleRecaptcha;

use EventSmart\Multisite\core\services\database\service\DbServiceJobInfo;

/**
 * Class JobInfo
 * Specific Meta information about the job, like its name, description, product affected, product version, etc
 * This is NOT the actual data needed to perform the batch jobs, just info about the job.
 *
 * @author  Brent Christensen
 * @package EventSmart\Multisite\core\services\database\service\tools\EventVenueMigration
 */
class JobInfo extends DbServiceJobInfo
{
    /**
     * constant used for saving job progress information in the database
     */
    private const OPTION_NAME = 'blogs_using_recaptcha';


    public function description(): string
    {
        return 'Toggles Recaptcha ON or OFF for all blogs';
    }


    public function name(): string
    {
        return 'Toggle Recaptcha';
    }


    public function dbOptionName(): string
    {
        return JobInfo::OPTION_NAME;
    }


    public function product(): string
    {
        return 'Event Smart';
    }


    public function version(): string
    {
        return 'v4.12';
    }
}
