<?php
if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}



/**
 * EE_DMS_Core_9_9_10
 * Which requires that Core 9.9.9 DMS be done first before running
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 */
class EE_DMS_Core_9_9_10 extends EE_Data_Migration_Script_Base
{

    public function __construct()
    {
        $this->_pretty_name = __('Multisite Mock Migration Two', 'event_espresso');
        $this->_migration_stages = array(
            new EE_DMS_9_9_10_first(),
            new EE_DMS_9_9_10_second(),
        );
        parent::__construct();
    }



    /**
     * This one just always needs to migrate
     *
     * @param type $version_string
     * @return boolean
     */
    public function can_migrate_from_version($version_string)
    {
        $core_9_9_9_dms = EE_Data_Migration_Manager::instance()->get_migration_ran('9.9.9', 'Core');
        if ($core_9_9_9_dms instanceof EE_Data_Migration_Script_Base && $core_9_9_9_dms->get_status() == EE_Data_Migration_Manager::status_completed) {
            return true;
        } else {
            return false;
        }
    }



    public function schema_changes_after_migration()
    {
    }



    public function schema_changes_before_migration()
    {
    }



}



class EE_DMS_9_9_10_first extends EE_Data_Migration_Script_Stage
{

    public function __construct()
    {
        $this->_pretty_name = __('First Stage', 'event_espresso');
        parent::__construct();
    }



    protected function _count_records_to_migrate()
    {
        return 111;
    }



    protected function _migration_step($num_items_to_migrate = 50)
    {
        $records_remaining_to_migrate = $this->count_records_to_migrate() - $this->count_records_migrated();
        $num_items_to_migrate = min(array($num_items_to_migrate, $records_remaining_to_migrate));
        if ($this->count_records_migrated() + $num_items_to_migrate >= $this->count_records_to_migrate()) {
            $this->set_completed();
        }
        return $num_items_to_migrate;
    }



}



class EE_DMS_9_9_10_second extends EE_Data_Migration_Script_Stage
{

    public function __construct()
    {
        $this->_pretty_name = __('Second Stage', 'event_espresso');
        parent::__construct();
    }



    protected function _count_records_to_migrate()
    {
        return 222;
    }



    protected function _migration_step($num_items_to_migrate = 50)
    {
        $records_remaining_to_migrate = $this->count_records_to_migrate() - $this->count_records_migrated();
        $num_items_to_migrate = min(array($num_items_to_migrate, $records_remaining_to_migrate));
        if ($this->count_records_migrated() + $num_items_to_migrate >= $this->count_records_to_migrate()) {
            $this->set_completed();
        }
        $this->add_error('Some error occured. JK!');
        return $num_items_to_migrate;
    }



}

// End of file EE_DMS_Core_9_9_9.dms.php