<?php

namespace EventEspressoBatchRequest\JobHandlers;
use EventEspressoBatchRequest\JobHandlerBaseClasses\JobHandlerFile;
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


class MultisiteQueryer extends JobHandlerFile {
	public function create_job( JobParameters $job_parameters ) {
		if( ! \EE_Capabilities::instance()->current_user_can( 'manage_options', 'generating_report' ) ) {
			throw new BatchRequestException(
				__( 'You do not have permission to do big queries like this. You need "manage_options"', 'event_espresso')
			);
		}
		$job_parameters->set_job_size( \EEM_Blog::instance()->count() );
		$filepath = $this->create_file_from_job_with_name(
			$job_parameters->job_id(),
			$job_parameters->request_datum( 
				'label', 
				__( 'Query', 'event_espresso' )  
			) . '.csv'
		);
		$job_parameters->add_extra_data( 'filepath', $filepath );
		$this->_query_and_write_to_file( $job_parameters, 1, true );
		return new JobStepResponse( $job_parameters, __( 'Counted blogs and wrote header row', 'event_espresso' ));
	}
	
	protected function _query_and_write_to_file( JobParameters $job_parameters, $num_to_do = 1, $write_headers = false ) {
		
		$wpdb_method = $job_parameters->request_datum( 'wpdb_method', 'get_results' );
		$sql_query = $job_parameters->request_datum( 'sql_query' );
		//get next blog
		$blogs = \EEM_Blog::instance()->get_all_wpdb_results( 
			array( 
				'limit' => array( 
					$job_parameters->units_processed(), 
					$num_to_do 
				) 
			), 
			ARRAY_A,
			'blog_id, concat(domain, path) as blog'
		);
		$rows_generated_this_step = array();
		foreach( $blogs as $blog ) {
			$rows = $this->_query_blog( $blog['blog_id'], $wpdb_method, $sql_query, $job_parameters->request_datum( 'stop_on_error' ) );
			foreach( $rows as $row ) {
				$row = array_merge(
					$blog,
					$row
				);
				$rows_generated_this_step[] = $row;
			}
		}
		
		\EE_Registry::instance()->load_helper( 'Export' );
		\EEH_Export::write_data_array_to_csv( $job_parameters->extra_datum( 'filepath' ), $rows_generated_this_step, $job_parameters->extra_datum( 'need_to_write_headers', $write_headers ) );
		$units_processed = count( $blogs );
		//if we just wrote the headers, we don't need to do it anymore now do we?
		if( $units_processed > 0 ) {
			$job_parameters->add_extra_data( 'write_headers', false );
		}
		$job_parameters->mark_processed( $units_processed );
		return $units_processed;
	}
	
	/**
	 * Does the requested query on the requested blog and returns an array of results
	 * @global type $wpdb
	 * @param int $blog_id
	 * @param string $wpdb_method the method on WPDB to call
	 * @param string $sql_query the SQL, but with no wpdb prefixes. Instead use the extract string "{$wpdb->prefix}"
	 * @param boolean $stop_on_error
	 * @return array for writing to a CSV file
	 */
	protected function _query_blog( $blog_id, $wpdb_method, $sql_query, $stop_on_error = false ) {
		global $wpdb;
		\switch_to_blog( $blog_id );
		$parsed_query = str_replace( 
			array(
				'{$wpdb->prefix}',
				'{$wpdb->base_prefix}'
			), 
			array(
				$wpdb->prefix,
				$wpdb->base_prefix
			), 
			stripslashes( $sql_query )
		);
		$args = array( $parsed_query );
		if( $wpdb_method == 'get_results' ) {
			$args[] = ARRAY_A;
		}
		$wpdb->last_error = null;
		$results = call_user_func_array( 
			array( 
				$wpdb, 
				$wpdb_method ),
			$args 
		);
		restore_current_blog();
		//if there's an error just blow up
		if ( ( $results === false || $results === null || ! empty( $wpdb->last_error ) ) ) {
			if( $stop_on_error ) {
				throw new \EE_Error( 
					sprintf( 
						__( 'WPDB Error: "%1$s" while running query "%2$s"', 'event_espresso'), 
						$wpdb->last_error, 
						$parsed_query 
					) 
				);
			} else {
				$results = array( array( $wpdb->last_error ) );
			}
		}
		
		if( ! is_array( $results ) ) {
			$results = array( $results );
		}
		return $results;
	}
	
	public function continue_job(JobParameters $job_parameters, $batch_size = 50) {
		$units_processed = $this->_query_and_write_to_file( $job_parameters, $batch_size );
		$extra_response_data = array(
			'file_url' => ''
		);
		if( $units_processed < $batch_size ) {
			$job_parameters->set_status( JobParameters::status_complete );
			$extra_response_data[ 'file_url' ] = $this->get_url_to_file( $job_parameters->extra_datum( 'filepath' ) );
		}
		return new JobStepResponse(
				$job_parameters,
				sprintf(
					__( 'Wrote rows for %1$d blogs...', 'event_espresso' ),
					count( $units_processed ) ),
				$extra_response_data );
	}
	
	/**
	 * Performs any clean-up logic when we know the job is completed.
	 * In this case, we delete the temporary file
	 * @param JobParameters $job_parameters
	 * @return boolean
	 */
	public function cleanup_job( JobParameters $job_parameters ){
		$this->_file_helper->delete(
			\EEH_File::remove_filename_from_filepath( $job_parameters->extra_datum( 'filepath' ) ),
			true,
			'd'
		);
		return new JobStepResponse( $job_parameters, __( 'Cleaned up temporary file', 'event_espresso' ) );
	}
}

