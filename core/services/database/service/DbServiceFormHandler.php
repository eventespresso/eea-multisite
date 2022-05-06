<?php

namespace EventSmart\Multisite\core\services\database\service;

use EE_Admin_Page;
use EE_Error;
use EE_Form_Section_Proper;
use EE_Registry;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidFormSubmissionException;
use EventEspresso\core\libraries\form_sections\form_handlers\FormHandler;
use InvalidArgumentException;
use LogicException;

class DbServiceFormHandler extends FormHandler
{
    public const FORM_ACTION = 'run_db_service_jobs';

    /**
     * @var string
     */
    private $admin_page_url = '';


    /**
     * Form constructor
     *
     * @param EE_Registry $registry
     * @param string      $admin_page_url
     */
    public function __construct(EE_Registry $registry, string $admin_page_url)
    {
        $this->admin_page_url = $admin_page_url;
        parent::__construct(
            esc_html__('DB Service & Repair Tools', 'event_espresso'),
            esc_html__('DB Service & Repair Tools', 'event_espresso'),
            DbServiceForm::FORM_SLUG,
            EE_Admin_Page::add_query_args_and_nonce(
                [
                    'action'        => DbServiceFormHandler::FORM_ACTION,
                    'return_action' => DbServiceForm::FORM_SLUG,
                ],
                $this->admin_page_url
            ),
            FormHandler::ADD_FORM_TAGS_ONLY,
            $registry
        );
    }


    /**
     * creates and returns the actual form
     *
     * @return EE_Form_Section_Proper
     * @throws EE_Error
     */
    public function generate()
    {
        return new DbServiceForm(AvailableDbServices::options());
    }


    /**
     * handles processing the form submission
     * returns true or false depending on whether the form was processed successfully or not
     *
     * @param array $submitted_form_data
     * @return bool
     * @throws InvalidFormSubmissionException
     * @throws EE_Error
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     */
    public function process($submitted_form_data = []): bool
    {
        if (empty($submitted_form_data[ DbServiceForm::FORM_SLUG ]['available_DB_services'])) {
            $this->form()->add_validation_error(
                esc_html__(
                    'Please select one of the available DB services to continue.',
                    'event_espresso'
                )
            );
        }

        $valid_data = parent::process($submitted_form_data);
        if (empty($valid_data)) {
            return false;
        }

        return DbServiceJobManager::run(
            $valid_data['available_DB_services'] ?? '',
            [
                'admin_page_url'   => $this->admin_page_url,
                'auto_run_service' => $valid_data['auto_run_service'] === 'ON',
                'ignore_errors'    => $valid_data['ignore_errors'] === 'ON',
            ]
        );
    }
}
