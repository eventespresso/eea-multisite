<?php

namespace EventSmart\Multisite\core\services\database\service\tools\EventVenueMigration;

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
	private const OPTION_NAME = 'sites_repaired_event_venue_migration_v4_12';


	public function description(): string
	{
		return 'Fixes missing Event Venues caused by timeouts during migration';
	}


	public function name(): string
	{
		return 'Event Venue Migration';
	}


	public function dbOptionName(): string
	{
		return JobInfo::OPTION_NAME;
	}


	public function product(): string
	{
		return 'Event Espresso Core';
	}


	public function version(): string
	{
		return 'v4.12';
	}
}
