<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Multisite_Migration_Manager
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Multisite_Migration_Manager {

	/**
	 * 	@var EE_Multisite_Migration_Manager $_instance
	 * 	@access 	private
	 */
	private static $_instance = NULL;

	/**
	 * @singleton method used to instantiate class object
	 * @access public
	 * @return EE_Multisite_Migration_Manager
	 */
	public static function instance() {
		// check if class object is instantiated
		if ( self::$_instance === NULL or !is_object( self::$_instance ) or !( self::$_instance instanceof EE_Multisite_Migration_Manager ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}



	/**
	 * resets the singleton to its brand-new state (but does NOT delete old references to the old singleton. Meaning,
	 * all new usages of the singleton shoul dbe made with CLassname::instance()) and returns it
	 * @return EE_Data_Migration_Manager
	 */
	public static function reset() {
		self::$_instance = NULL;
		return self::instance();
	}



	public function __construct() {
		EE_Registry::instance()->load_core( 'Data_Migration_Manager' );
	}



	/**
	 * Migrates $records_to_migrate records from the currently-migrating blog
	 * on its currently-migrating script. When done the current migration script on
	 * the current blog, returns the number of records migrated so far.
	 * @param int $records_to_migrate
	 * @return array {
	 * 	@type string $current_blog_name,
	 * 	@type string[] $current_blog_script_names,
	 * 	@type array $current_dms {
	 * 		@type int $records_to_migrate from the current migration script,
	 * 		@type int $records_migrated from the current migration script,
	 * 		@type string $status one of EE_Data_Migration_Manager::status_*,
	 * 		@type string $script verbose name of the current DMS,
	 * 	}
	 * 	@type string $message string describing what was done during this step
	 * }
	 */
	public function migration_step( $records_to_migrate ) {
		$num_migrated = 0;
		$multisite_migration_message = '';
		$current_script_names = array( );
		while ( $num_migrated < $records_to_migrate && $blog_to_migrate = EEM_Blog::instance()->get_migrating_blog_or_most_recently_requested() ) {
			EED_Multisite::switch_to_blog( $blog_to_migrate->ID() );
			do {
				$results = EE_Data_Migration_Manager::instance()->migration_step( $records_to_migrate - $num_migrated );
				$num_migrated += $results[ 'records_migrated' ];
				$multisite_migration_message .= "<br>" . $results[ 'message' ];
				switch ( $results[ 'status' ] ) {
					case EE_Data_Migration_Manager::status_completed:
					case EE_Data_Migration_Manager::status_continue:
						$status_indicates_continue = TRUE;
						break;
					case EE_Data_Migration_Manager::status_no_more_migration_scripts:
					case EE_Data_Migration_Manager::status_fatal_error:
					default:
						$status_indicates_continue = FALSE;
				}
			} while ( $num_migrated < $records_to_migrate &&
			$status_indicates_continue );

			//if we're done this migration step, grab the remaining scripts for this blog
			//before we switch back to the network admin
			if ( $num_migrated >= $records_to_migrate ) {
				$current_script_names = $this->_get_applicable_dms_names();
			}
			EED_Multisite::restore_current_blog();

			//if appropriate, update this blog's status
			if ( $results[ 'status' ] == EE_Data_Migration_Manager::status_fatal_error ) {
				$blog_to_migrate->set_STS_ID( EEM_Blog::status_borked );
				$multisite_migration_message .= "<br>" . sprintf( __( 'Skipping migration of %s', 'event_espresso' ), $blog_to_migrate->name() );
			} elseif ( $results[ 'status' ] == EE_Data_Migration_Manager::status_no_more_migration_scripts ) {
				$blog_to_migrate->set_STS_ID( EEM_Blog::status_up_to_date );
				$multisite_migration_message .= '<br>' . sprintf( __( 'Finished migrating %s', 'event_espresso' ), $blog_to_migrate->name() );
			} else {
				$blog_to_migrate->set_STS_ID( EEM_Blog::status_migrating );
			}
			$blog_to_migrate->save();
		}
		if ( $blog_to_migrate ) {
			return array(
				'current_blog_name' => $blog_to_migrate->name(),
				'current_blog_script_names' => $current_script_names,
				'current_dms' => $results,
				'message' => $multisite_migration_message
			);
		} else {
			//theoreticlly we could receive another request like this when there are no
			//more blogs that need to be migrated
			return array(
				'current_blog_name' => '',
				'current_blog_script_names' => array( ),
				'current_dms' => array(
					'records_to_migrate' => 1,
					'records_migrated' => 1,
					'status' => EE_Data_Migration_Manager::status_no_more_migration_scripts,
					'script' => __( "Data Migration Completed Successfully", "event_espresso" ),
				),
				'message' => __( 'All blogs up-to-date', 'event_espresso' )
			);
		}
	}



	/**
	 * Gets the pretty names for all the data migration scripts needing to run
	 * on the current blog
	 * @return string[]
	 */
	protected function _get_applicable_dms_names() {
		$scripts = EE_Data_Migration_Manager::instance()->check_for_applicable_data_migration_scripts();
		$script_names = array( );
		foreach ( $scripts as $script ) {
			$script_names[ ] = $script->pretty_name();
		}
		return $script_names;
	}



	/**
	 * Assesses $num_to_assess blogs and finds whether they need ot be migrated or not,
	 * and updates their status. Returns the number that were found to need migrating
	 * (NOT the total number needing migrating. For that, use EEM_Blog::count_blogs_needing_migration())
	 * @param int $num_to_assess
	 * @return int number of blogs needing to be migrated, amongst those inspected
	 */
	public function assess_sites_needing_migration( $num_to_assess = 10 ) {
		$blogs = EEM_Blog::instance()->get_all_blogs_maybe_needing_migration( array( 'limit' => $num_to_assess ) );
		$blogs_needing_to_migrate = 0;
		foreach ( $blogs as $blog ) {
			//switch to that blog and assess whether or not it needs to be migrated
			EED_Multisite::switch_to_blog( $blog->ID() );
			$needs_migrating = EE_Maintenance_Mode::instance()->set_maintenance_mode_if_db_old();
			restore_current_blog();
			if ( $needs_migrating ) {
				$blog->set_STS_ID( EEM_Blog::status_out_of_date );
				$blogs_needing_to_migrate++;
			} else {
				$blog->set_STS_ID( EEM_Blog::status_up_to_date );
			}
			$blog->save();
		}
		EED_Multisite::restore_current_blog();
		return $blogs_needing_to_migrate;
	}



}

// End of file EE_Multisite_Migration_Manager.php