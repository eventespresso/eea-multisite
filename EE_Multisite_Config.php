<?php

/**
 * Class EE_Multisite_Config
 * Description
 *
 * @package               Event Espresso
 * @subpackage            core
 * @author                Brent Christensen
 */
class EE_Multisite_Config extends EE_Config_Base
{
    public $delete_site_threshold;

    public $delete_site_excludes;

    public $delete_non_super_admin_users;


    public function __construct()
    {
        $this->delete_site_threshold = 30;
        $this->delete_site_excludes = array(1);
        $this->delete_non_super_admin_users = false;
    }
}
// End of file EE_Multisite_Config.php
// Location: /wp-content/plugins/espresso-multisite/EE_Multisite_Config.php
