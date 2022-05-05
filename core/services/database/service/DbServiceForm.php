<?php

namespace EventSmart\Multisite\core\services\database\service;

use EE_Admin_Two_Column_Layout;
use EE_Form_Section_HTML;
use EE_Form_Section_Proper;
use EE_Select_Input;
use EE_Submit_Input;
use EE_Switch_Input;
use EEH_HTML;

class DbServiceForm extends EE_Form_Section_Proper
{
	public const FORM_SLUG   = 'db_service_repair';


	public function __construct(array $available_services)
	{
		parent::__construct(
			[
				'name'            => DbServiceForm::FORM_SLUG,
				'layout_strategy' => new EE_Admin_Two_Column_Layout(),
				'subsections'     => [
					'header'              => new EE_Form_Section_HTML(
						EEH_HTML::h3(__('DB Service & Repair Tools', 'event_espresso'))
					),
					'explanation'         => new EE_Form_Section_HTML(
						EEH_HTML::h4(
							esc_html__(
								'Will run the selected service tool on every site in the network',
								'event_espresso'
							),
							'',
							'ee-status-outline ee-status-bg--info'
						)
					),
					'available_DB_services'     => new EE_Select_Input(
						$available_services,
						[
							'html_class' => 'ee-input-width--big',
							'required' => true,
							'validation_error_message' => esc_html__(
								'Please select one of the available DB services to continue.',
								'event_espresso'
							),
						]
					),
					'auto_run_service' => new EE_Switch_Input(
						[ 'default' => EE_Switch_Input::OPTION_OFF ],
						[
							EE_Switch_Input::OPTION_OFF  => esc_html__(
								'run the service assessment then prompt before making any changes',
								'event_espresso'
							),
							EE_Switch_Input::OPTION_ON => esc_html__(
								'run the service assessment and then automatically apply changes',
								'event_espresso'
							),
						]
					),
					'ignore_errors'       => new EE_Switch_Input(
						['default' => EE_Switch_Input::OPTION_OFF],
						[
							EE_Switch_Input::OPTION_OFF  => esc_html__(
								'stop all service jobs if an error is encountered',
								'event_espresso'
							),
							EE_Switch_Input::OPTION_ON => esc_html__(
								'skip over errors and attempt to continue processing service jobs',
								'event_espresso'
							),
						]
					),
					''                    => new EE_Submit_Input(
						['default' => esc_html__('Run Selected Service', 'event_espresso')]
					),
				],
			]
		);
	}

}
