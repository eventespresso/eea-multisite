<?php

namespace EventEspressoMultisite\core\services\commands;

use EE_Registry;
use EE_Secondary_Table;
use EEH_Activation;
use EEM_Base;
use EEM_Blog;
use EventEspresso\core\services\commands\CommandHandler;
use EventEspressoMultisite\core\services\commands\DeleteBlogCommand;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed');



/**
 * Class DeleteBlogCommandHandler
 * Description
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          $VID:$
 */
class DeleteBlogCommandHandler extends CommandHandler
{

    public function __construct(

    )
    {
    }



    /**
     * @param  CommandInterface $command
     * @return mixed
     */
    public function handle(DeleteBlogCommand $command)
    {
        $blog_id = $command ->blog_id();
        $users = array();
        //first get the current users on the blog so we can loop through and delete them after (but only if users
        //are to be deleted!
        if ($command->delete_users()) {
            $users = get_users( array( 'blog_id' => $blog_id, 'fields' => 'ids' ) );
        }

        EEM_Base::set_model_query_blog_id( $blog_id );
        EEH_Activation::drop_espresso_tables();
        //reset the model's internal blog id
        EEM_Base::set_model_query_blog_id();
        //now delete core blog tables/data
        wpmu_delete_blog( $blog_id, true );

        //since WordPress doesn't return any info on the success of the deleted blog, let's verify it was deleted
        if ( ! EEM_Blog::instance()->exists_by_ID( $blog_id ) ) {

            //clean up blog_meta table
            $tables = EEM_Blog::instance()->get_tables();
            if ( isset( $tables['Blog_Meta'] ) && $tables['Blog_Meta'] instanceof EE_Secondary_Table ) {
                //the main blog entry is already deleted, let's clean up the entry in the secondary table
                global $wpdb;
                $wpdb->delete( $tables['Blog_Meta']->get_table_name(), array( 'blog_id_fk' => $blog_id ) );

                //delete all non super_admin users that were attached to that blog if configured to drop them
                if ( $command->delete_users() && $users ) {
                    foreach ( $users as $user_id ) {
                        if ( is_super_admin( $user_id ) ) {
                            continue;
                        }
                        wpmu_delete_user( $user_id );
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

}
// End of file DeleteBlogCommandHandler.php
// Location: EventEspressoMultisite\core\services\commands/DeleteBlogCommandHandler.php