<?php
if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}



/**
 * EE_Site
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 */
class EE_Site extends EE_Soft_Delete_Base_Class
{

    /**
     * @param array $props_n_values
     * @return EE_Site
     */
    public static function new_instance($props_n_values = array())
    {
        $has_object = parent::_check_for_object($props_n_values, __CLASS__);
        return $has_object ? $has_object : new self($props_n_values);
    }



    /**
     * @param array $props_n_values
     * @return EE_Site
     */
    public static function new_instance_from_db($props_n_values = array())
    {
        return new self($props_n_values, true);
    }



    /**
     * Gets domain
     *
     * @return string
     */
    function domain()
    {
        return $this->get('domain');
    }



    /**
     * Sets domain
     *
     * @param string $domain
     * @return boolean
     */
    function set_domain($domain)
    {
        return $this->set('domain', $domain);
    }



    /**
     * Gets path
     *
     * @return string
     */
    function path()
    {
        return $this->get('path');
    }



    /**
     * Sets path
     *
     * @param string $path
     * @return boolean
     */
    function set_path($path)
    {
        return $this->set('path', $path);
    }
}

// End of file EE_Site.class.php
