<?php

use EventEspresso\core\services\database\TableManager;
use EventEspresso\core\services\loaders\LoaderFactory;
use EventSmart\Multisite\core\services\database\service\DbServiceForm;
use EventSmart\Multisite\core\services\database\service\DbServiceFormHandler;

/**
 * Multisite_Admin_Page
 * This contains the logic for setting up the Multisite Addon Admin related pages. Any methods without PHP doc
 * comments have inline docs with parent class.
 *
 * @package               Multisite_Admin_Page (multisite addon)
 * @subpackage            admin/Multisite_Admin_Page.core.php
 * @author                Darren Ethier, Brent Christensen
 */
class Multisite_Admin_Page extends EE_Admin_Page
{
    /**
     * @var EE_Form_Section_Proper|null
     */
    protected $_multisite_queryer_form = null;


    protected function _init_page_props()
    {
        $this->page_slug        = MULTISITE_PG_SLUG;
        $this->page_label       = MULTISITE_LABEL;
        $this->_admin_base_url  = EE_MULTISITE_ADMIN_URL;
        $this->_admin_base_path = EE_MULTISITE_ADMIN;
    }


    protected function _ajax_hooks()
    {
        add_action('wp_ajax_multisite_migration_error', [$this, 'migration_error']);
        add_action('wp_ajax_delete_sites_range', [$this, 'delete_sites_range']);
    }


    protected function _define_page_props()
    {
        $this->_admin_page_title = MULTISITE_LABEL;
        $this->_labels           = [
            'publishbox' => esc_html__('Update Settings', 'event_espresso'),
        ];
    }


    protected function _set_page_routes()
    {
        $this->_page_routes = [
            'default'                         => [$this, '_migration_page'],
            'settings'                        => [$this, '_basic_settings'],
            'update_settings'                 => [
                'func'     => [$this, '_update_settings'],
                'noheader' => true,
            ],
            'force_reassess'                  => [
                'func'     => [$this, '_force_reassess'],
                'noheader' => true,
            ],
            'site_management'                 => [$this, '_site_management'],
            'update_site_management_settings' => [
                'func'     => [$this, '_update_site_management_settings'],
                'noheader' => true,
            ],
            'delete_sites_range'              => [
                'func'     => [$this, '_delete_sites_range'],
                'noheader' => true,
            ],
            'usage'                           => [$this, '_usage'],
            'cleanup_partially_deleted_sites' => [$this, 'cleanup_partially_deleted_sites'],
            'multisite_queryer'               => [$this, '_multisite_queryer'],
            'run_multisite_query'             => [
                'func'               => [$this, '_run_multisite_query'],
                'noheader'           => true,
                'headers_sent_route' => 'multisite_queryer',
                'capability'         => 'manage_options',
            ],
            DbServiceForm::FORM_SLUG          => [$this, 'databaseServiceAndRepair'],
            DbServiceFormHandler::FORM_ACTION => [
                'func'               => [$this, 'processDatabaseServiceJobs'],
                'noheader'           => true,
                'headers_sent_route' => DbServiceForm::FORM_SLUG,
                'capability'         => 'manage_options',
            ],
        ];
    }


    protected function _set_page_config()
    {
        $this->_page_config = [
            'default'                         => [
                'nav'           => [
                    'label' => esc_html__('Settings', 'event_espresso'),
                    'order' => 10,
                ],
                'metaboxes'     => array_merge($this->_default_espresso_metaboxes),
                'require_nonce' => false,
            ],
            'site_management'                 => [
                'nav'           => [
                    'label' => esc_html__('Site Management', 'event_espresso'),
                    'order' => 20,
                ],
                'require_nonce' => false,
            ],
            'multisite_queryer'               => [
                'nav'           => [
                    'label' => esc_html__('Queryer', 'event_espresso'),
                    'order' => 30,
                ],
                'require_nonce' => false,
            ],
            DbServiceForm::FORM_SLUG => [
                'nav'           => [
                    'label' => esc_html__('DB Service & Repair', 'event_espresso'),
                    'order' => 40,
                ],
                'require_nonce' => false,
            ],
            'cleanup_partially_deleted_sites' => [
                'require_nonce' => false,
            ],
        ];
        if (EE_Maintenance_Mode::instance()->models_can_query()) {
            $this->_page_config['default']['metaboxes'][]         = '_publish_post_box';
            $this->_page_config['site_management']['metaboxes'][] = '_publish_post_box';
        }
    }


    protected function _add_screen_options()
    {
    }


    protected function _add_screen_options_default()
    {
    }


    protected function _add_feature_pointers()
    {
    }


    public function load_scripts_styles()
    {
        wp_enqueue_script('ee_admin_js');
        wp_register_script(
            'espresso_multisite_admin',
            EE_MULTISITE_ADMIN_ASSETS_URL . 'espresso_multisite_admin.js',
            ['espresso_core', 'ee-dialog',],
            EE_MULTISITE_VERSION,
            true
        );
        wp_enqueue_script('espresso_multisite_admin');
        wp_localize_script('espresso_multisite_admin', 'ee_i18n_text', [
            'done_assessment'         => esc_html__('Assessment Complete', 'event_espresso'),
            'network_needs_migration' => esc_html__('Network requires migration', 'event_espresso'),
            'no_migrations_required'  => esc_html__('No migrations are required', 'event_espresso'),
            'ajax_error'              => esc_html__(
                'An error occurred communicating with the server. Please contact support. An email report should have been sent to your network admin',
                'event_espresso'
            ),
            'all_done'                => esc_html__('All done migrating network', 'event_espresso'),
            'all_done_deleting'       => esc_html__('All done deleting sites.', 'event_espresso'),
            'error_occurred'          => esc_html__('An error occurred', 'event_espresso'),
            'no_progress_assessing'   => esc_html__(
                'It appears we are not making any progress assessing the sites needing migration. Something is wrong',
                'event_espresso'
            ),
        ]);
        // steal the styles etc from the normal maintenance page
        wp_register_style(
            'espresso_multisite_migration',
            EE_MULTISITE_ADMIN_ASSETS_URL . 'espresso_multisite_admin.css',
            [],
            EE_MULTISITE_VERSION
        );
        wp_enqueue_style('espresso_multisite_migration');
    }


    public function admin_init()
    {
        EE_Registry::$i18n_js_strings['confirm_reset'] = esc_html__(
            'Are you sure you want to reset ALL your Event Espresso Multisite Information? This cannot be undone.',
            'event_espresso'
        );
    }


    public function admin_notices()
    {
    }


    public function admin_footer_scripts()
    {
    }


    /**
     * @throws EE_Error
     */
    protected function _migration_page()
    {
        if (EE_Maintenance_Mode::instance()->models_can_query()) {
            $this->_template_path = EE_MULTISITE_ADMIN_TEMPLATE_PATH . 'multisite_migration.template.php';

            $this->_template_args['reassess_url'] = EE_Admin_Page::add_query_args_and_nonce(
                ['action' => 'force_reassess'],
                EE_MULTISITE_ADMIN_URL
            );
            $this->_template_args['borked_sites_url'] = add_query_arg(
                ['orderby' => 'STS_ID'],
                network_admin_url('sites.php')
            );
            $this->_template_args['assess_and_migrate_url'] = EE_Admin_Page::add_query_args_and_nonce(
                [
                    'page'        => EED_Batch::PAGE_SLUG,
                    'batch' 	  => EED_Batch::batch_job,
                    'job_handler' => urlencode('EventEspressoBatchRequest\JobHandlers\MultisiteMigration'),
                    'return_url'  => urlencode("//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"),
                ],
                admin_url()
            );
            $this->_template_args['queryer_url'] = EE_Admin_Page::add_query_args_and_nonce(
                ['action' => 'multisite_queryer'],
                EE_MULTISITE_ADMIN_URL
            );
            $this->_set_add_edit_form_tags('update_settings');
            $this->_set_publish_post_box_vars(null, false, false, null, false);
            $this->_template_args['admin_page_content'] = EEH_Template::display_template(
                $this->_template_path,
                $this->_template_args,
                true
            );
        } else {
            $migration_page = get_admin_url(get_current_blog_id(), 'admin.php?page=espresso_maintenance_settings');
            $this->_template_args['admin_page_content'] = EEH_Template::display_template(
                EE_MULTISITE_ADMIN_TEMPLATE_PATH . 'multisite_migration_in_mm.template.php',
                ['migration_page_url' => $migration_page],
                true
            );
            //          $this->_template_args['whatever'] ='';
            //          $this->_set_add_edit_form_tags( 'update_settings' );
            //          $this->_set_publish_post_box_vars( NULL, FALSE, FALSE, NULL, FALSE );
            //
            //      $this->_template_args[ 'reassess_url' ] = EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'force_reassess' ), EE_MULTISITE_ADMIN_URL );
        }
        $this->display_admin_page_with_sidebar();
    }


    /**
     * @throws EE_Error
     */
    protected function _basic_settings()
    {
        $this->_settings_page('multisite_basic_settings.template.php');
    }


    /**
     * @throws EE_Error
     */
    protected function _multisite_queryer()
    {
        $form_action = EE_Admin_Page::add_query_args_and_nonce(
            [
                'action' => 'run_multisite_query',
                'return_action' => $this->_req_action,
            ],
            EE_MULTISITE_ADMIN_URL
        );

        $form = $this->_get_multisite_queryer_form();
        $this->_template_args['admin_page_content'] = $form->form_open($form_action, 'post');
        $this->_template_args['admin_page_content'] .= $form->get_html_and_js();
        $this->_template_args['admin_page_content'] .= $form->form_close();
        $this->display_admin_page_with_sidebar();
    }


    /**
     * Handles multisite queryer form submission.
     * If invalid, it re-renders the form by allowing _multisite_queryer method to handle it
     *
     * @throws EE_Error
     */
    protected function _run_multisite_query()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $form = $this->_get_multisite_queryer_form();
            $form->receive_form_submission($this->_req_data);
            if ($form->is_valid()) {
                // redirect
                wp_redirect(
                    EE_Admin_Page::add_query_args_and_nonce(
                        [
                            'page'          => EED_Batch::PAGE_SLUG,
                            'batch'         => 'file',
                            'label'         => $form->get_input_value('label'),
                            'wpdb_method'   => $form->get_input_value('wpdb_method'),
                            'sql_query'     => urlencode($form->get_input_value('sql_query')),
                            'stop_on_error' => urlencode($form->get_input_value('stop_on_error')),
                            'job_handler'   => urlencode('EventEspressoBatchRequest\JobHandlers\MultisiteQueryer'),
                            'return_url'    => urlencode("//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"),
                        ],
                        admin_url()
                    )
                );
                exit;
            }
        }
    }


    /**
     * @return EE_Form_Section_Proper
     */
    protected function _get_multisite_queryer_form()
    {
        if (! $this->_multisite_queryer_form instanceof EE_Form_Section_Proper) {
            $this->_multisite_queryer_form = new EE_Multisite_Queryer_Form();
        }
        return $this->_multisite_queryer_form;
    }


    /**
     * _settings_page
     *
     * @param $template
     * @throws EE_Error
     */
    protected function _settings_page($template)
    {
        $this->_template_args['multisite_config'] = EE_Config::instance()->get_config(
            'addons',
            'EED_Espresso_Multisite',
            'EE_Multisite_Config'
        );
        add_filter('FHEE__EEH_Form_Fields__label_html', '__return_empty_string');
        $this->_template_args['yes_no_values'] = [
            EE_Question_Option::new_instance(
                [
                    'QSO_value' => 0,
                    'QSO_desc' => esc_html__('No', 'event_espresso')
                ]
            ),
            EE_Question_Option::new_instance(
                [
                    'QSO_value' => 1,
                    'QSO_desc' => esc_html__('Yes', 'event_espresso')
                ]
            ),
        ];
        $this->_template_args['return_action'] = $this->_req_action;
        $this->_template_args['reset_url']     = EE_Admin_Page::add_query_args_and_nonce(
            [
                'action' => 'reset_settings',
                'return_action' => $this->_req_action
            ],
            EE_MULTISITE_ADMIN_URL
        );
        $this->_set_add_edit_form_tags('update_settings');
        $this->_set_publish_post_box_vars(null, false, false, null, false);
        $this->_template_args['admin_page_content'] = EEH_Template::display_template(
            EE_MULTISITE_ADMIN_TEMPLATE_PATH . $template,
            $this->_template_args,
            true
        );
        $this->display_admin_page_with_sidebar();
    }


    /**
     * @throws EE_Error
     */
    protected function _usage()
    {
        $this->_template_args['admin_page_content'] = EEH_Template::display_template(
            EE_MULTISITE_ADMIN_TEMPLATE_PATH . 'multisite_usage_info.template.php',
            [],
            true
        );
        $this->display_admin_page_with_no_sidebar();
    }


    /**
     * @throws EE_Error
     */
    protected function _update_settings()
    {
        if (isset($_POST['reset_multisite']) && $_POST['reset_multisite'] == '1') {
            $config = new EE_Multisite_Config();
            $count  = 1;
        } else {
            $config = EE_Config::instance()->get_config('addons', 'EED_Espresso_Multisite', 'EE_Multisite_Config');
            $count  = 0;
            // otherwise, we assume you want to allow full HTML
            foreach ($this->_req_data['multisite'] as $top_level_key => $top_level_value) {
                if (is_array($top_level_value)) {
                    foreach ($top_level_value as $second_level_key => $second_level_value) {
                        if (
                            EEH_Class_Tools::has_property($config, $top_level_key)
                            && EEH_Class_Tools::has_property($config->$top_level_key, $second_level_key)
                            && $second_level_value != $config->$top_level_key->$second_level_key
                        ) {
                            $config->$top_level_key->$second_level_key = $this->_sanitize_config_input(
                                $top_level_key,
                                $second_level_key,
                                $second_level_value
                            );
                            $count++;
                        }
                    }
                } else {
                    if (
                        EEH_Class_Tools::has_property($config, $top_level_key)
                        && $top_level_value != $config->$top_level_key
                    ) {
                        $config->$top_level_key = $this->_sanitize_config_input($top_level_key, null, $top_level_value);
                        $count++;
                    }
                }
            }
        }
        EE_Config::instance()->update_config('addons', 'EED_Espresso_Multisite', $config);
        $this->_redirect_after_action($count, 'Settings', 'updated', ['action' => $this->_req_data['return_action']]);
    }



    /**
     * resets the multisite data and redirects to where they came from
     */
    //  protected function _reset_settings(){
    //      EE_Config::instance()->addons['multisite'] = new EE_Multisite_Config();
    //      EE_Config::instance()->update_espresso_config();
    //      $this->_redirect_after_action(1, 'Settings', 'reset', array('action' => $this->_req_data['return_action']));
    //  }
    private function _sanitize_config_input($top_level_key, $second_level_key, $value)
    {
        $sanitization_methods = [
            'display' => [
                'enable_multisite' => 'bool',
                //              'multisite_height'=>'int',
                //              'enable_multisite_filters'=>'bool',
                //              'enable_category_legend'=>'bool',
                //              'use_pickers'=>'bool',
                //              'event_background'=>'plaintext',
                //              'event_text_color'=>'plaintext',
                //              'enable_cat_classes'=>'bool',
                //              'disable_categories'=>'bool',
                //              'show_attendee_limit'=>'bool',
            ],
        ];
        $sanitization_method  = null;
        if (
            isset($sanitization_methods[ $top_level_key ]) && $second_level_key === null
            && ! is_array($sanitization_methods[ $top_level_key ])
        ) {
            $sanitization_method = $sanitization_methods[ $top_level_key ];
        } elseif (
            is_array($sanitization_methods[ $top_level_key ])
            && isset($sanitization_methods[ $top_level_key ][ $second_level_key ])
        ) {
            $sanitization_method = $sanitization_methods[ $top_level_key ][ $second_level_key ];
        }
        //      echo "$top_level_key [$second_level_key] with value $value will be sanitized as a $sanitization_method<br>";
        switch ($sanitization_method) {
            case 'bool':
                return (boolean) intval($value);
            case 'plaintext':
                return wp_strip_all_tags($value);
            case 'int':
                return intval($value);
            case 'html':
                return $value;
            default:
                $input_name = $second_level_key == null
                    ? $top_level_key
                    : "$top_level_key[$second_level_key]";
                EE_Error::add_error(
                    sprintf(
                        esc_html__(
                            "Could not sanitize input '%s' because it has no entry in our sanitization methods array",
                            "event_espresso"
                        ),
                        $input_name
                    )
                );
                return null;
        }
    }


    /**
     * forces EE to re-assess which sites are up-to-date, and which need migration
     *
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function _force_reassess()
    {
        $count = EEM_Blog::instance()->mark_all_blogs_migration_status_as_unsure();
        $this->_redirect_after_action($count, 'Blogs', 'migration status updated', ['action' => 'default']);
    }


    /**
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function migration_error()
    {
        // our last ajax response didn't send proper JSON
        // probably because of a fatal error or something
        // so update the last blog as borked
        // and ask its data migration manager to log the error
        $blog_migrating = EEM_Blog::instance()->get_migrating_blog_or_most_recently_requested();
        if ($blog_migrating instanceof EE_Blog) {
            $blog_migrating->set_STS_ID(EEM_Blog::status_borked);
            $blog_migrating->save();
            EED_Multisite::do_full_reset();
            switch_to_blog($blog_migrating->ID());
            EE_Data_Migration_Manager::instance()->add_error_to_migrations_ran($this->_req_data['message']);
            global $wpdb;
            $last_migration_script_option = $wpdb->get_row(
                "SELECT * FROM "
                . $wpdb->options
                . " WHERE option_name like '"
                . EE_Data_Migration_Manager::data_migration_script_option_prefix
                . "%' ORDER BY option_id DESC LIMIT 1",
                ARRAY_A
            );
            $blog_name                    = $blog_migrating->name();
            $blog_id                      = $blog_migrating->ID();
            restore_current_blog();
        } else {
            $blog_name                    = esc_html__('Unknown', 'event_espresso');
            $blog_id                      = esc_html__('Unknown', 'event_espresso');
            $last_migration_script_option = [];
        }
        wp_mail(
            get_site_option('admin_email'),
            sprintf(
                esc_html__('General error running multisite migration. Last ran blog was: %s', 'event_espresso'),
                $blog_name
            ),
            sprintf(
                esc_html__(
                    'Did not receive proper JSON response while running multisite migration. This was the response: \'%1$s\' while migrating blog %2$s (ID %3$d). The last ran migration script had data: %4$s',
                    'event_espresso'
                ),
                $this->_req_data['message'],
                $blog_name,
                $blog_id,
                print_r($last_migration_script_option, true)
            )
        );
    }


    /**
     * Callback for the site management page.
     *
     * @throws EE_Error
     * @throws EE_Error
     * @throws EE_Error
     * @throws EE_Error
     */
    protected function _site_management()
    {
        $this->_template_args['admin_page_content'] = $this->_site_management_delete_form()->get_html_and_js();
        $this->_set_add_edit_form_tags('update_site_management_settings');
        $this->_set_publish_post_box_vars(null, false, false, null, false);
        $this->display_admin_page_with_sidebar();
    }


    /**
     * Returns the main form for deleting sites.
     *
     * @return EE_Form_Section_Proper
     * @throws EE_Error
     */
    protected function _site_management_delete_form(): EE_Form_Section_Proper
    {
        $delete_url = add_query_arg(
            ['action' => 'delete_sites_range'],
            EE_MULTISITE_ADMIN_URL
        );
        return new EE_Form_Section_Proper(
            [
                'name'            => 'ee_multisite_site_management_form',
                'html_id'         => 'ee_multisite_site_management_form',
                'layout_strategy' => new EE_Div_Per_Section_Layout(),
                'subsections'     => [
                    'delete_sites_hdr'      => new EE_Form_Section_HTML(
                        EEH_HTML::h3(esc_html__('Prune Old Sites', 'event_espresso'))
                    ),
                    'delete_sites_message'  => new EE_Form_Section_HTML(
                        EEH_HTML::p(
                            esc_html__(
                                'Make sure you save any changes made here before clicking the prune sites button.',
                                'event_espresso'
                            )
                        )
                    ),
                    'delete_sites_settings' => $this->_delete_settings_form_settings(),
                    'delete_sites_button'   => new EE_Form_Section_HTML(
                        '<a id="ee-prune-sites-button" href="' . $delete_url . '" class="button button-secondary">'
                        . esc_html__('Prune Sites', 'event_espresso')
                        . '</a>'
                    ),
                    'delete_sites_js_pane'  => new EE_Form_Section_HTML_From_Template(
                        EE_MULTISITE_ADMIN_TEMPLATE_PATH . 'multisite-site-delete-pane.template.php'
                    ),
                ],
            ]
        );
    }


    /**
     * Returns the specific settings field that are assembled as a part of the main delete sites form.
     *
     * @return EE_Form_Section_Proper
     * @throws EE_Error
     */
    protected function _delete_settings_form_settings(): EE_Form_Section_Proper
    {
        $addons_config = EE_Registry::instance()->CFG->addons;
        return new EE_Form_Section_Proper(
            [
                'name'            => 'ee_multisite_site_management_settings',
                'html_id'         => 'ee_multisite_site_management_settings',
                'layout_strategy' => new EE_Div_Per_Section_Layout(),
                'subsections'     => [
                    'delete_threshold'             => new EE_Text_Input(
                        [
                            'html_label_text'         => esc_html__(
                                'Delete Sites that have not been updated in the last given number of days',
                                'event_espresso'
                            ),
                            'html_help_text'          => '',
                            'default'                 => $addons_config->ee_multisite->delete_site_threshold ?? 30,
                            'display_html_label_text' => false,
                        ]
                    ),
                    'delete_excludes'              => new EE_Text_Input(
                        [
                            'html_label_text' => esc_html__('Excluded sites', 'event_espresso'),
                            'html_help_text'  => esc_html__(
                                'Enter a comma delimited list of site_ids to exclude from the delete query',
                                'event_espresso'
                            ),
                            'default'         => isset($addons_config->ee_multisite->delete_site_excludes)
                                ? implode(',', $addons_config->ee_multisite->delete_site_excludes)
                                : 1,
                        ]
                    ),
                    'delete_non_super_admin_users' => new EE_Yes_No_Input(
                        [
                            'html_label_text' => esc_html__(
                                'Delete non super admin users that belong to the deleted sites?',
                                'event_espresso'
                            ),
                            'default'         => $addons_config->ee_multisite->delete_non_super_admin_users ?? false,
                        ]
                    ),
                ],
            ]
        );
    }


    /**
     * callback for update_site_management_settings action.
     *
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function _update_site_management_settings()
    {
        $config = EE_Registry::instance()->CFG->addons->ee_multisite;
        try {
            $form = $this->_site_management_delete_form();
            if ($form->was_submitted()) {
                // capture form data
                $form->receive_form_submission();
                // validate_form_data
                if ($form->is_valid()) {
                    $valid_data                           = $form->valid_data();
                    $config->delete_site_threshold        = $valid_data['delete_sites_settings']['delete_threshold'];
                    $config->delete_site_excludes         =
                        explode(',', $valid_data['delete_sites_settings']['delete_excludes']);
                    $config->delete_non_super_admin_users =
                        $valid_data['delete_sites_settings']['delete_non_super_admin_users'];
                } else {
                    if ($form->submission_error_message() != '') {
                        EE_Error::add_error($form->submission_error_message(), __FILE__, __FUNCTION__, __LINE__);
                    }
                }
            }
        } catch (EE_Error $e) {
            $e->get_error();
        }
        if (EE_Registry::instance()->CFG->update_config('addons', 'ee_multisite', $config)) {
            EE_Error::add_success(esc_html__('Settings updated.', 'event_espresso'));
        }
        $this->redirect_after_action(false, '', '', ['action' => 'site_management'], true);
    }


    /**
     * public wrapper for _delete_sites_range that is used as the callback on ajax requests.
     *
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function delete_sites_range()
    {
        $this->_delete_sites_range();
    }


    /**
     * callback for the delete_sites_range action.
     * This handles deleting sites matching the current config (in batches).
     *
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function _delete_sites_range()
    {
        // let's only allow activity via ajax
        if (! defined('DOING_AJAX') || ! DOING_AJAX) {
            EE_Error::add_error(
                esc_html__('Deleting sites is only allowable via ajax currently', 'event_espresso'),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
            $this->redirect_after_action(false, '', '', ['action' => 'site_management'], true);
        }
        $response = [];
        // set the number of sites deleted in each batch
        $batch_size = defined('EE_DELETE_SITES_BATCH_SIZE') ? (int) EE_DELETE_SITES_BATCH_SIZE : 20;
        // instructions for deletes
        $delete_site_threshold = EE_Registry::instance()->CFG->addons->ee_multisite->delete_site_threshold ?? 30;

        $range = "-$delete_site_threshold days";
        // use appropriate call for current_time() depending on what version of EE core is active.
        $current_time = method_exists(EEM_Blog::instance(), 'current_time_for_query')
            ? time()
            : current_time('timestamp');
        $delete_where_conditions = [
            'last_updated' => ['<', strtotime($range, $current_time)],
            'blog_id'      => ['NOT_IN', EED_Multisite_Auto_Site_Cleanup::get_protected_blogs()],
        ];
        $sites_to_delete_total   = EEM_Blog::instance()->count(
            [
                $delete_where_conditions,
                'default_where_conditions' => 'none'
            ]
        );
        // get the original count of blogs to delete.  If that's empty then this is the initial request.
        if (! $this->_req_data['total_sites_to_be_deleted']) {
            $response['total_sites_to_be_deleted'] = $sites_to_delete_total;
        }
        $response['sites_deleted']    = $sites_to_delete_total
            ? $this->_delete_sites($delete_where_conditions, $batch_size)
            : 0;
        $this->_template_args['data'] = $response;
        $this->_return_json();
    }


    /**
     * Takes care of deleting a batch of sites matching the given where conditions.
     *
     * @param array $delete_where_conditions The where conditions for the blogs that get deleted
     * @param int   $batch_size              The limit of blogs to be deleted in one batch.
     * @return int   The total of blogs deleted.
     * @throws EE_Error
     * @throws ReflectionException
     */
    protected function _delete_sites(array $delete_where_conditions, int $batch_size): int
    {
        // First the blog IDs are retrieved rather than doing a direct delete through the model.
        // This is because the blogs are deleted via the WP core methods
        // so that any related data is also deleted.
        $total_deleted      = 0;
        $blog_ids_to_delete = EEM_Blog::instance()->get_col(
            [$delete_where_conditions, 'limit' => $batch_size, 'default_where_conditions' => 'none'],
            'blog_id'
        );
        // loop through the blog_ids and let's get deleting!
        foreach ($blog_ids_to_delete as $blog_id) {
            wpmu_delete_blog($blog_id, true);
            // since WordPress doesn't return any info on the success of the deleted blog, let's verify it was deleted
            if (! EEM_Blog::instance()->exists_by_ID($blog_id)) {
                // deleted!
                $total_deleted++;
            }
        }
        // all done, return the total blogs deleted.
        return $total_deleted;
    }


    /**
     * For deleting tables for blogs that were deleted except for the EE tables
     *
     * @return void
     * @global wpdb $wpdb
     */
    function cleanup_partially_deleted_sites()
    {
        //      return;
        $deleted_sites = get_option('pruner_cleanup', false);
        global $wpdb;
        if ($deleted_sites === false) {
            // get the highest blog id
            $max = max($wpdb->get_var('SELECT max(blog_id) FROM ' . $wpdb->blogs), 5);
            // create an array with all ids up to that number
            $all_possible_blog_ids = range(1, $max);
            // select all blog ids
            $existing_blog_ids = $wpdb->get_col('SELECT blog_id FROM ' . $wpdb->blogs);
            // remove all existing blog IDs from all blog IDs.
            $deleted_sites = array_values(array_diff($all_possible_blog_ids, $existing_blog_ids));
            // save the result
            update_option('pruner_cleanup', $deleted_sites);
        }
        //      echo "deleted sites:";var_dump($deleted_sites);die;
        $offset = get_option('pruner_cleanup_index', 0);
        /** @var TableManager $table_manager */
        $table_manager = LoaderFactory::getShared(TableManager::class);
        for ($i = $offset; $i < $offset + 10; $i++) {
            if (! isset($deleted_sites[ $i ])) {
                delete_option('pruner_cleanup');
                delete_option('pruner_cleanup_offset');
                delete_option('pruner_cleanup_index');
                return;
            }
            $blog_id_to_cleanup = $deleted_sites[ $i ];
            // remember underscores are WILDCARDS in SQL like queries! so escape them
            $this_blog_prefix = str_replace('_', '\_', $wpdb->base_prefix . $blog_id_to_cleanup . '_');
            $sql              = 'SHOW TABLES LIKE "' . $this_blog_prefix . '%";';
            echo $sql;
            $table_names = $wpdb->get_col($sql);
            var_dump($table_names);
            foreach ($table_names as $table_name) {
                $success = $table_manager->dropTable($table_name);
                echo "<br/>delete table $table_name. success? " . $success;
            }
        }
        if (! isset($deleted_sites[ $i ])) {
            echo "<hr>We seem to be all done";
            delete_option('pruner_cleanup');
            delete_option('pruner_cleanup_offset');
            delete_option('pruner_cleanup_index');
        } else {
            echo "<hr>Next up:" . $deleted_sites[ $i ];
            update_option('pruner_cleanup_index', $i);
            echo '<a href="">Proceed</a><script>location.reload();</script>';
        }
    }


    private function dbRepairFormHandler(): DbServiceFormHandler
    {
        return new DbServiceFormHandler(EE_Registry::instance(), $this->_admin_base_url);
    }


    /**
     * @throws EE_Error
     */
    protected function databaseServiceAndRepair()
    {
        $this->_template_args['admin_page_content'] = EEH_HTML::div(
            $this->dbRepairFormHandler()->display(),
            '',
            'padding'
        );
        $this->display_admin_page_with_sidebar();
    }


    /**
     * Handles dbRepairForm submission.
     * If invalid, it re-renders the form by calling databaseRepairJob()
     *
     * @throws EE_Error
     */
    protected function processDatabaseServiceJobs()
    {
        $this->dbRepairFormHandler()->process($this->request->requestParams());
    }
}
// End of file Multisite_Admin_Page.core.php
// Location: /wp-content/plugins/espresso-multisite/admin/multisite/Multisite_Admin_Page.core.php
