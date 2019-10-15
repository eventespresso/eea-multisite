<?php
/**
 * Class EE_Multisite_Queryer_Form
 * Description here
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 *
 */
if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}



class EE_Multisite_Queryer_Form extends EE_Form_Section_Proper
{

    public function __construct($options_array = array())
    {
        $options_array = array_replace_recursive(
            array(
                'name'            => 'multisite_queryer',
                'layout_strategy' => new EE_Admin_Two_Column_Layout(),
                'subsections'     => array(
                    'header'        => new EE_Form_Section_HTML(EEH_HTML::h1(__('Multisite Queryer', 'event_espresso'))),
                    'label'         => new EE_Text_Input(
                        array(
                            'required' => true,
                        )
                    ),
                    'explanation'   => new EE_Form_Section_HTML(EEH_HTML::p(__('Will execute a query on every site in the network and generate a CSV file of the results', 'event_espresso'))),
                    'wpdb_method'   => new EE_Radio_Button_Input(
                        array(
                            'get_results' => __('get_results (for selects)', 'event_espresso'),
                            //                              'query' => __( 'query (for inserts, updates, or deletes)', 'event_espresso' )
                        ),
                        array(
                            'default' => 'get_results',
                        )
                    ),
                    'sql_query'     => new EE_Text_Area_Input(
                        array(
                            'html_help_text' => __(
                                'Only SELECT queries allowed (for now). Use the string "{$wpdb->prefix}", "{$wpdb->base_prefix}", "{$wpdb->siteid}", and "{$wpdb->blogid}" as you would normally. These strings will be replaced appropriately when querying each blog.',
                                'event_espresso'
                            ),
                            'required'       => true,
                            'default'        => 'SELECT',
                        )
                    ),
                    'stop_on_error' => new EE_Yes_No_Input(
                        array(
                            'html_help_text' => __('Whether to stop everything on exception, or just skip', 'event_espresso'),
                        )
                    ),
                    'submit'        => new EE_Submit_Input(
                        array(
                            'default' => __('Run Query', 'event_espresso'),
                        )
                    ),
                ),
            ),
            $options_array
        );
        parent::__construct($options_array);
    }



    /**
     * Called by EE_FOrm_Section_Proper::_validate automatically, and
     * does extra work to validate the query to make sure we're not modifying anything.
     * Modifying queries are dangerous
     *
     * @param EE_Form_Input_Base $input
     */
    public function _validate_sql_query($input)
    {
        if ($input instanceof EE_Form_Input_Base) {
            $value = $input->normalized_value();
            if (strpos(
                $value,
                'UPDATE'
            ) === 0
                || strpos($value, 'INSERT') === 0
                || strpos($value, 'DELETE') === 0
                || strpos($value, 'DROP') === 0
            ) {
                $input->add_validation_error(__('Only SELECT queries allowed'), 'query_not_allowed');
            }
        }
    }
}
