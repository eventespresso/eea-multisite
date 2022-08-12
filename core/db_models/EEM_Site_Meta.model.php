<?php

/**
 * EE_Site_Meta. THe DB site actually being the NETWORK
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 */
class EEM_Site_Meta extends EEM_Soft_Delete_Base
{
    /**
     * @type EEM_Site_Meta
     */
    protected static $_instance = null;


    /**
     *    constructor
     */
    protected function __construct($timezone = null)
    {
        $this->singular_item    = esc_html__('Site Meta', 'event_espresso');
        $this->plural_item      = esc_html__('Site Metas', 'event_espresso');
        $this->_tables          = [
            'Site_Meta' => new EE_Primary_Table('sitemeta', 'blog_id', true),
        ];
        $this->_fields          = [
            'Site_Meta' => [
                'meta_id'    => new EE_Primary_Key_Int_Field(
                    'meta_id',
                    esc_html__('Site Meta ID', 'event_espresso')
                ),
                'site_id'    => new EE_Foreign_Key_Int_Field(
                    'site_id',
                    esc_html__('Site ID', 'event_espresso'),
                    false,
                    1,
                    'Site'
                ),
                'meta_key'   => new EE_Plain_Text_Field(
                    'meta_key',
                    esc_html__('Meta Key', 'event_espresso'),
                    false,
                    ''
                ),
                'meta_value' => new EE_Maybe_Serialized_Text_Field(
                    'meta_value',
                    esc_html__('Value', 'event_espresso'),
                    true
                ),
            ],
        ];
        $this->_model_relations = [
            'Site' => new EE_Belongs_To_Relation(),
        ];
        parent::__construct();
    }
}

// End of file EE_Site_meta.model.php
