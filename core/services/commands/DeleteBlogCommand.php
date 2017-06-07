<?php
namespace EventEspressoMultisite\core\services\commands;
defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed');
use EE_Blog;
use EventEspresso\core\services\commands\Command;


/**
 * Class DeleteBlogCommand
 * Command to delete a blog/"site" (entry in wp_blogs table) and all its corresponding
 * EE4 data
 *
 * @package        Event Espresso
 * @author         Mike Nelson
 * @since          $VID:$
 */
class DeleteBlogCommand extends Command
{


    /**
     * @var int
     */
    private $blog_id;

    /**
     * Whether or not we should delete the non-super-admins of the site
     * @var boolean
     */
    private $delete_users;


    /**
     * DeleteBlogCommand constructor.
     *
     * @param int     $blog_id
     * @param boolean $delete_users
     */
    public function __construct($blog_id, $delete_users = true)
    {
        $this->set_blog_id($blog_id);
        $this->set_delete_users($delete_users);
    }



    /**
     * Sets the blog ID and verifies its an int
     * @param int $blog_id
     * @throws \EE_Error
     * @return void
     */
    private function set_blog_id($blog_id)
    {
        if (! intval($blog_id)){
            throw new \EE_Error(
                sprintf(
                    esc_html__('Cannot delete blog because the ID provided, "%1$s" is invalid.', 'event_espresso'),
                    $blog_id
                )
            );
        }
        $this->blog_id = $blog_id;
    }



    /**
     * Sets whether or not we should delete the sites' users
     * @param boolean $delete_users
     * @return void
     */
    private function set_delete_users($delete_users)
    {
        $this->delete_users = filter_var($delete_users, FILTER_VALIDATE_BOOLEAN);
    }



    /**
     * Returns the specified blog's ID (primary key value in the wp_blogs table)
     * @return int
     */
    public function blog_id(){
        return $this->blog_id;
    }



    /**
     * Whether or not we should delete the blog's users
     * @return bool
     */
    public function delete_users(){
        return $this->delete_users;
    }


}
// End of file DeleteBlogCommand.php
// Location: ${NAMESPACE}/DeleteBlogCommand.php