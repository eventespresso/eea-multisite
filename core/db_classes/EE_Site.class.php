<?php

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
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function new_instance($props_n_values = [])
    {
        $has_object = parent::_check_for_object($props_n_values, __CLASS__);
        return $has_object ?: new self($props_n_values);
    }


    /**
     * @param array $props_n_values
     * @return EE_Site
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function new_instance_from_db($props_n_values = [])
    {
        return new self($props_n_values, true);
    }


    /**
     * Gets domain
     *
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function domain()
    {
        return $this->get('domain');
    }


    /**
     * Sets domain
     *
     * @param string $domain
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function set_domain($domain)
    {
        $this->set('domain', $domain);
    }


    /**
     * Gets path
     *
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function path()
    {
        return $this->get('path');
    }


    /**
     * Sets path
     *
     * @param string $path
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function set_path($path)
    {
        $this->set('path', $path);
    }
}
// End of file EE_Site.class.php
