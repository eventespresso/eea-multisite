<?php

/**
 * Multisite_Admin_Page_Init class
 * This is the init for the Multisite Addon Admin Pages.  See EE_Admin_Page_Init for method inline docs.
 *
 * @package               Event Espresso (multisite addon)
 * @subpackage            admin/Multisite_Admin_Page_Init.core.php
 * @author                Darren Ethier
 */
class Multisite_Admin_Page_Init extends EE_Admin_Page_Init
{
    public function __construct()
    {
        do_action('AHEE_log', __FILE__, __FUNCTION__, '');
        define('MULTISITE_PG_SLUG', 'espresso_multisite');
        define('MULTISITE_LABEL', __('EE Multisite', 'event_espresso'));
        define('EE_MULTISITE_ADMIN_URL', network_admin_url('admin.php?page=' . MULTISITE_PG_SLUG));
        define('EE_MULTISITE_ADMIN_ASSETS_PATH', EE_MULTISITE_ADMIN . 'assets' . DS);
        define('EE_MULTISITE_ADMIN_ASSETS_URL', EE_MULTISITE_URL . 'admin' . DS . 'multisite' . DS . 'assets' . DS);
        define('EE_MULTISITE_ADMIN_TEMPLATE_PATH', EE_MULTISITE_ADMIN . 'templates' . DS);
        define(
            'EE_MULTISITE_ADMIN_TEMPLATE_URL',
            EE_MULTISITE_URL . 'admin' . DS . 'multisite' . DS . 'templates' . DS
        );
        parent::__construct();
        $this->_folder_path = EE_MULTISITE_ADMIN;
    }


    protected function _set_init_properties()
    {
        $this->label = MULTISITE_LABEL;
    }


    /**
     * @throws EE_Error
     */
    protected function _set_menu_map()
    {
        $this->_menu_map = new EE_Admin_Page_Main_Menu(
            [
                'menu_group'              => 'main',
                'menu_order'              => 25,
                'show_on_menu'            => EE_Admin_Page_Menu_Map::NETWORK_ADMIN_ONLY,
                'parent_slug'             => MULTISITE_PG_SLUG,
                'menu_slug'               => MULTISITE_PG_SLUG,
                'menu_label'              => MULTISITE_LABEL,
                'capability'              => 'administrator',
                'maintenance_mode_parent' => MULTISITE_PG_SLUG,
                'admin_init_page'         => $this,
            ]
        );
    }
}
// End of file Multisite_Admin_Page_Init.core.php
// Location: /wp-content/plugins/espresso-multisite/admin/multisite/Multisite_Admin_Page_Init.core.php
