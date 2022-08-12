<?php

namespace EventSmart\Multisite\core\services\database\service;

use EventEspresso\core\services\loaders\LoaderFactory;

/**
 * Class AvailableDbServices
 * Service for loading other database services
 *
 * @author  Brent Christensen
 * @package EventSmart\Multisite
 * @since   $VID:$
 */
class AvailableDbServices
{
    /**
     * @return array
     */
    public static function options(): array
    {
        $services         = [];
        $tools_directory  = __DIR__ . '/tools/';
        $tool_directories = glob("$tools_directory*", GLOB_ONLYDIR);
        if ($tool_directories) {
            $services = AvailableDbServices::optionsArray($tool_directories);
        }
        return $services;
    }


    /**
     * @param array $services
     * @return array
     */
    private static function optionsArray(array $services): array
    {
        $service_options = [];
        foreach ($services as $service) {
            // just grab job folder name from full path
            $service_name = basename($service);
            $service_info = AvailableDbServices::info($service_name);
            // add service job option to array
            $service_options[ $service_name ] = $service_info->optionValue();
        }
        asort($service_options);
        return ['' => esc_html__('~ select a service to proceed ~', 'event_espresso')] + $service_options;
    }


    /**
     * @param string $job_name
     * @return string
     */
    public static function assessment(string $job_name): string
    {
        return AvailableDbServices::jobObject($job_name, 'JobAssessment', false);
    }


    /**
     * @param string $job_name
     * @return DbServiceJobInfo
     */
    public static function info(string $job_name): DbServiceJobInfo
    {
        return AvailableDbServices::jobObject($job_name, 'JobInfo');
    }


    /**
     * @param string $job_name
     * @return string
     */
    public static function handler(string $job_name): string
    {
        return AvailableDbServices::jobObject($job_name, 'JobHandler', false);
    }


    /**
     * @param string $job_name
     * @param array  $arguments
     * @return DbServiceJobData
     */
    public static function data(string $job_name, array $arguments): DbServiceJobData
    {
        return AvailableDbServices::jobObject($job_name, 'JobData', true, $arguments);
    }


    /**
     * @param string $job_name
     * @param string $job_class
     * @param bool   $object
     * @param array  $arguments
     * @return mixed
     */
    private static function jobObject(string $job_name, string $job_class, bool $object = true, array $arguments = [])
    {
        $job_fqcn = "EventSmart\\Multisite\\core\\services\\database\\service\\tools\\$job_name\\$job_class";
        return $object ? LoaderFactory::getShared($job_fqcn, $arguments) : $job_fqcn;
    }
}
