<?php

/**
 * EEM_Site. The DB Site, of course, actually being the NETWORK
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 */
class EEM_Site extends EEM_Base
{
    /**
     * private instance of the EEM_Site object
     *
     * @var EEM_Site
     */
    protected static $_instance = null;


    /**
     * @param string $timezone
     * @throws EE_Error
     */
    protected function __construct($timezone = '')
    {
        $this->singular_item    = esc_html__('Site', 'event_espresso');
        $this->plural_item      = esc_html__('Sites', 'event_espresso');
        $this->_tables          = [
            'Sites' => new EE_Primary_Table('site', 'id', true),
        ];
        $this->_fields          = [
            'Sites' => [
                'id'     => new EE_Primary_Key_Int_Field(
                    'id',
                    esc_html__('Site ID', 'event_espresso')
                ),
                'domain' => new EE_Plain_Text_Field(
                    'domain',
                    esc_html__('Domain', 'event_espresso'),
                    false,
                    'localhost'
                ),
                'path'   => new EE_Plain_Text_Field(
                    'path',
                    esc_html__('Path', 'event_espresso'),
                    false,
                    '/'
                ),
            ],
        ];
        $this->_model_relations = [
            'Blog'      => new EE_Has_Many_Relation(),
            'Site_Meta' => new EE_Has_Many_Any_Relation(),
        ];
        parent::__construct($timezone);
    }
}

// End of file EEM_Site.model.php
