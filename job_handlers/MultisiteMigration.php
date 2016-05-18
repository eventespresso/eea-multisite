<?php

namespace EventEspressoBatchRequest\JobHandlers;
use EventEspressoBatchRequest\JobHandlerBaseClasses\JobHandler;
use EventEspressoBatchRequest\Helpers\BatchRequestException;
use EventEspressoBatchRequest\Helpers\JobParameters;
use EventEspressoBatchRequest\Helpers\JobStepResponse;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}


class MultisiteMigration extends JobHandler {
	public function create_job( JobParameters $job_parameters ) {
		$job_parameters->set_job_size( \EEM_Blog::instance()->count() );
		return new JobStepResponse( $job_parameters, __( 'Assessment (and possible migrations) started', 'event_espresso' ));
	}
	public function continue_job(JobParameters $job_parameters, $step_size = 50) {
		$assess_step_size_value = defined( 'EE_MULTISITE_ASSESS_STEP_SIZE_VALUE' ) ? EE_MULTISITE_ASSESS_STEP_SIZE_VALUE : 100;
		$steps_taken = 0;
		$response_messages = array();
		$blogs_assessed_and_migrated = 0;
		do {
			if( \EEM_Blog::instance()->count_blogs_needing_migration() ) {
				$migration_step_response = \EE_Multisite_Migration_Manager::instance()->migration_step( $step_size );
				$steps_taken += $migration_step_response[ 'num_migrated' ];
				$response_messages[] = sprintf(
					__( 'Migrated %1$s records from %2$s during migration step %3$s.', 'event_espresso'),
					$migration_step_response[ 'num_migrated' ],
					$migration_step_response[ 'current_blog_name' ],
					$migration_step_response[ 'current_dms'][ 'script' ]
				);
				if( isset( $migration_step_response[ 'current_dms' ][ 'status' ] )
					&& $migration_step_response[ 'current_dms' ][ 'status' ] !== \EE_Data_Migration_Manager::status_continue) {
					//ok so we've finished the last migration script of that site.
					//we should count that as an assessment step (the wrap-up from
					//a migration is quite expensive but isn't a "step" per say)
					$blogs_assessed_and_migrated++;
					$steps_taken += $assess_step_size_value;
				}
			} else {
				//no blogs need to be migrated right now.
				//Maybe we need to assess some more?
				$blogs_needing_assessment = \EEM_Blog::instance()->get_all_blogs_maybe_needing_migration( array( 'limit' => 1 ) );
				if( empty( $blogs_needing_assessment ) ) {
					//so none need migrating. And none need assessment. We're done right?
					$job_parameters->set_status( JobParameters::status_complete );
				} else {
					//so we found a blog that needs to be assessed. so assess it
					$blog = array_shift( $blogs_needing_assessment );
					$needs_migrating = $this->site_need_migration( $blog );
					if( $needs_migrating ) {
						$response_messages[] = sprintf( __( 'Assessed %1$s. It needs to be migrateted...', 'event_espresso' ), $blog->name() );
					} else { 
						$response_messages[] = sprintf( __( 'Assessed %1$s. No migrations required.', 'event_espresso'), $blog->name() );
						$blogs_assessed_and_migrated++;
					}
				}
				$steps_taken += $assess_step_size_value;
			}
		} while ( $steps_taken < $step_size
			&& $job_parameters->status() === JobParameters::status_continue );
		$job_parameters->set_units_processed( \EEM_Blog::instance()->count_blogs_up_to_date() );
		return new JobStepResponse( 
			$job_parameters, 
			implode( 
				apply_filters( 'FHEE__MultisiteMigration__continue_job_response_glue', ',' ),
				$response_messages 
			) 
		);
	}
	/**
	 * 
	 * @param type $blog
	 * @return boolean
	 */
	protected function site_need_migration( $blog ) {
		\EED_Multisite::do_full_system_reset();
		\switch_to_blog( $blog->ID() );
		$needs_migrating = \EE_Maintenance_Mode::instance()->set_maintenance_mode_if_db_old();
		if ( $needs_migrating ) {
			$blog->set_STS_ID( \EEM_Blog::status_out_of_date );
			$needs_migrating = true;
		} else {
			$blog->set_STS_ID( \EEM_Blog::status_up_to_date );
			$needs_migrating = false;
		}
		\restore_current_blog();
		$blog->save();
		return $needs_migrating;
	}
	
	public function cleanup_job(JobParameters $job_parameters) {
		return new JobStepResponse( $job_parameters, __( 'All done multisite migration and assessment', 'event_espresso' ) );
	}
}

