<?php
if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}
/**
 * Event Espresso
 * Event Registration and Ticketing Management Plugin for WordPress
 * @ package            Event Espresso
 * @ author                Event Espresso
 * @ copyright        (c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license            http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link                    http://www.eventespresso.com
 * @ version            $VID:$
 * ------------------------------------------------------------------------
 */



/**
 * Class EE_Multisite_Config
 * Description
 *
 * @package               Event Espresso
 * @subpackage            core
 * @author                Brent Christensen
 * @since                 $VID:$
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