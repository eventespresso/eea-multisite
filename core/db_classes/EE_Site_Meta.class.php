<?php

/**
 * EE_Blog
 *
 * @package     Event Espresso
 * @subpackage
 * @author      Mike Nelson
 */
class EE_Site_Meta extends EE_Soft_Delete_Base_Class
{
    /**
     * @param array $props_n_values
     * @return EE_Site_Meta
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
     * @return EE_Site_Meta
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function new_instance_from_db($props_n_values = [])
    {
        return new self($props_n_values, true);
    }


    /**
     * Gets site_id
     *
     * @return int
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function site_id()
    {
        return $this->get('site_id');
    }


    /**
     * Sets site_id
     *
     * @param int $site_id
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function set_site_id($site_id)
    {
        $this->set('site_id', $site_id);
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
     * Gets registered
     *
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function registered()
    {
        return $this->get('registered');
    }


    /**
     * Sets registered
     *
     * @param string $registered
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function set_registered($registered)
    {
        $this->set('registered', $registered);
    }
}
// End of file EE_Blog.class.php
