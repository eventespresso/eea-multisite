<?php
if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}



/**
 * EE_Blogs. The "blog" being each individual "blog" (UI "site"). NOT the network.
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 */
class EEM_Blog extends EEM_Soft_Delete_Base
{

    /**
     * The blog is borked. Probably because a migration script died on it.
     * We can't migrate it, but we don't want to claim it's 'up-to-date' either
     */
    const status_borked = 'BRK';

    /**
     * This blog is definetely out of date and should be migrated
     */
    const status_out_of_date = 'BOD';

    /**
     * The blog might be out of date. EE core or an addon has been upgraded
     * and we havent checked if it needs to be migrated
     */
    const status_unsure = 'BUN';

    /**
     * this blog is currently being migrated by the EE multisite addon
     * (others may be getting migrated without the EE multisite addon)
     */
    const status_migrating = 'BCM';

    /**
     * The blog has been updated and EE core and its addons havent been updated since
     */
    const status_up_to_date = 'BUD';


    /**
     * private instance of the EEM_Answer object
     *
     * @type EEM_Blog
     */
    protected static $_instance = null;



    /**
     *    constructor
     */
    protected function __construct($timezone)
    {
        $this->singular_item = __('Blog', 'event_espresso');
        $this->plural_item = __('Blogs', 'event_espresso');
        $this->_tables = array(
            'Blog'      => new EE_Primary_Table('blogs', 'blog_id', true),
            'Blog_Meta' => new EE_Secondary_Table('esp_blog_meta', 'BLM_ID', 'blog_id_fk', null, true),
        );
        $this->_fields = array(
            'Blog'      => array(
                'blog_id'      => new EE_Primary_Key_Int_Field('blog_id', __('Blog ID', 'event_espresso')),
                'site_id'      => new EE_Foreign_Key_Int_Field('site_id', __('Site ID', 'event_espresso'), false, 0, 'Site'),
                'domain'       => new EE_Plain_Text_Field('domain', __('Domain', 'event_espresso'), false),
                'registered'   => new EE_Datetime_Field('registered', __('Registered', 'event_espresso'), false, current_time('timestamp')),
                'last_updated' => new EE_Datetime_Field('last_updated', __('Last Updated', 'event_espresso'), false, current_time('timestamp')),
                'public'       => new EE_Boolean_Field('public', __('Public?', 'event_espresso'), false, true),
                'archived'     => new EE_Boolean_Field('archived', __('Archived', 'event_espresso'), false, false),
                'mature'       => new EE_Boolean_Field('mature', __('Mature', 'event_espresso'), false, false),
                'spam'         => new EE_Boolean_Field('spam', __('Spam?', 'event_espresso'), false, false),
                'deleted'      => new EE_Trashed_Flag_Field('deleted', __('Deleted?', 'event_espresso'), false, false),
                'lang_id'      => new EE_Integer_Field('lang_id', __('Language ID', 'event_espresso'), false, 0),
            ),
            'Blog_Meta' => array(
                'BLM_ID'             => new EE_DB_Only_Int_Field('BLM_ID', __('Blog Meta ID', 'event_espresso'), false, 0),
                'blog_id_fk'         => new EE_DB_Only_Int_Field('blog_id_fk', __('Blog ID', 'event_espresso'), false, 0),
                'STS_ID'             => new EE_Foreign_Key_String_Field('STS_ID', __('Status', 'event_espresso'), false, self::status_unsure, 'Status'),
                'BLG_last_requested' => new EE_Datetime_Field('BLG_last_requested', __('Last Request for this Blog', 'event_espresso'), false, EE_Datetime_Field::now),
                'BLG_last_admin_visit' => new EE_Datetime_Field('BLG_last_admin_visit', __('Last Request for this Blog by an Admin', 'event_espresso'), false, EE_Datetime_Field::now),
            ),
        );
        $this->_model_relations = array(
            'Site' => new EE_Belongs_To_Relation()
        );
        parent::__construct();
    }



    /**
     * Counts all the blogs which MIGHT need to be mgirated
     *
     * @param array $query_params @see EEM_Base::get_all()
     * @return int
     */
    public function count_blogs_maybe_needing_migration($query_params = array())
    {
        return $this->count($this->_add_where_query_params_for_maybe_needs_migrating($query_params));
    }



    /**
     * Gets all blogs that might need to be migrated
     *
     * @param array $query_params @see EEM_Base::get_all()
     * @return EE_Blog[]
     */
    public function get_all_blogs_maybe_needing_migration($query_params = array())
    {
        return $this->get_all($this->_add_where_query_params_for_maybe_needs_migrating($query_params));
    }



    /**
     * Adds teh where conditions to get all the blogs that might need to be migrated (unsure)
     *
     * @param array $query_params @see EEM_base::get_all()
     * @return array @see EEM_Base::get_all()
     */
    private function _add_where_query_params_for_maybe_needs_migrating($query_params = array())
    {
        $query_params[0]['OR*maybe_needs_migrating'] = array(
            'STS_ID*unsure' => self::status_unsure,
            'STS_ID*null'   => array('IS NULL'),
        );
        $query_params['order_by'] = array('BLG_last_requested' => 'DESC');
        return $query_params;
    }



    /**
     * Counts all blogs which DEFINETELY DO need to be migrated
     *
     * @return int
     */
    public function count_blogs_needing_migration()
    {
        return $this->count(array(
            array(
                'OR' => array(
                    'STS_ID*outdated'    => self::status_out_of_date,
                    'STS_ID*in_progress' => self::status_migrating,
                ),
            ),
        ));
    }



    public function count_blogs_up_to_date()
    {
        return $this->count(array(
            array(
                'STS_ID' => self::status_up_to_date,
            ),
        ));
    }



    /**
     * Counts all blogs which DEFINETELY DO need to be migrated
     *
     * @return int
     */
    public function count_borked_blogs()
    {
        return $this->count(array(
            array(
                'STS_ID' => self::status_borked,
            ),
        ));
    }



    /**
     * Gets all the blogs which are broken, probably from a failed migration
     *
     * @param array $query_params @see EEM_Base::get_all()
     * @return EE_Blog[]
     */
    public function get_all_borked_blogs($query_params = array())
    {
        $query_params[0]['STS_ID'] = self::status_borked;
        return $this->get_all($query_params);
    }



    /**
     * Gets the blog which is marked as currently updating, or
     *
     * @return EE_Blog
     */
    public function get_migrating_blog_or_most_recently_requested()
    {
        $currently_migrating = $this->get_one(array(
            array(
                'STS_ID' => self::status_migrating,
            ),
        ));
        if ($currently_migrating) {
            return $currently_migrating;
        } else {
            return $this->get_one(array(
                array(
                    'STS_ID' => self::status_out_of_date,
                ),
                'order_by' => array(
                    'BLG_last_requested' => 'DESC',
                ),
            ));
        }
    }



    /**
     * Updates all the blogs' status to indicate we're unsure as to whether
     * or not they need to be migrated. This should probably be done
     * anytime a new version of EE is installed or an addon is activated or upgraded.
     * This method needs to be runnable while in maintenance mode, so it can't use the normal model methods
     * (because they throw an exception if called in maintenance mode, because the table might not exist.
     * This method has special logic for when the table doesn't yet exist)
     *
     * @return int how many blog were marked as unsure
     */
    public function mark_all_blogs_migration_status_as_unsure()
    {
        EE_Registry::instance()->load_helper('Activation');
        if (EEH_Activation::table_exists($this->second_table())) {
            global $wpdb;
            $query = 'UPDATE ' . $this->second_table() . ' SET STS_ID = "' . self::status_unsure . '" WHERE 1=1';
            $rows_affected = $wpdb->query($query);
            return $rows_affected;
        } else {
            // don't do anything. the table doesn't exist, and its default value for STS_ID is self::status_unsure anyways
            // so there is nothing to do to mark it as status unsure
            return 0;
        }
    }



    /**
     * Marks the current blog's status in the esp_blog_meta table as being up-to-date.
     * Can be called from any blog and automatically does the blog switching magic.
     * If the main site is itself in mainteannce mode, then this is skipped. Oh well,
     * the blog will just be marked as unsure or out-of-date until the next multisite migration
     * manager assesses which blogs need to be migrated or this blog later noticies it has to nothing to migrate
     *
     * @param string $new_status the status you want to update the blog to
     */
    public function mark_current_blog_as($new_status = EEM_Blog::status_up_to_date)
    {
        $current_blog_id = get_current_blog_id();
        // needs to use WP's core switch_to_blog() instead of EED_Multisite::switch_to_blog()
        // instead ot avoid an infinite loop.
        EE_Registry::instance()->load_helper('Activation');
        if (EEH_Activation::table_exists($this->second_table())) {
            global $wpdb;
            // first check the current blog isn't ALREADY marked as up-to-date
            $verify_query = $wpdb->prepare('SELECT STS_ID FROM ' . $this->second_table() . ' WHERE blog_id_fk=%d LIMIT 1', $current_blog_id);
            $current_status = $wpdb->get_var($verify_query);
            if ($current_status !== $new_status) {
                $query = $wpdb->prepare('UPDATE ' . $this->second_table() . ' SET STS_ID = %s WHERE blog_id_fk = %d LIMIT 1', $new_status, $current_blog_id);
                $wpdb->query($query);
            }
        } else {
            // dang can't update it because the esp_blog_meta doesn't yet exist. Oh well, we'll just need to check it again
            // later when either the site's maintenance page is visited or the multisite migrator checks it.
            // not a HUGE deal though.
        }
    }



    /**
     * Updates the time the specified blog was last requested to right now
     *
     * @param int $blog_id
     * @return int number of rows updated
     */
    public function update_last_requested($blog_id)
    {
        global $wpdb;
        if (EEH_Activation::table_exists($this->second_table())) {
            return $wpdb->update(
                $this->second_table(),
                // update columns
                array(
                    'BLG_last_requested' => current_time('mysql', true),
                ),
                // where columns
                array(
                    'blog_id_fk' => $blog_id,
                ),
                // update format
                array(
                    '%s',
                ),
                // where format
                array(
                    '%d',
                )
            );
        } else {
            return 0;
        }
    }



    /**
     * Gets all blogs which have been visited by an event admin since the specified time,
     * who also have an entry for the specified extra meta key
     *
     * @param int|DateTime $threshold_time
     * @param string       $key_that_should_exist
     * @param string       $key_that_shouldnt_exist
     * @param array        $protected_blogs blog IDs that shouldn't be returned
     * @return EE_Blog[]
     */
    public function get_all_logged_into_since_time_with_extra_meta(
        $threshold_time,
        $key_that_should_exist,
        $key_that_shouldnt_exist,
        $protected_blogs = array()
    ) {
        global $wpdb;

        $query = 'SELECT blog_id FROM ' . $wpdb->base_prefix . 'blogs as b'
            . ' LEFT JOIN ' . $wpdb->base_prefix . 'esp_blog_meta AS bm'
            . ' ON b.blog_id = bm.blog_id_fk';
        if ($key_that_should_exist !== null) {
            $query .= ' LEFT JOIN ' . $wpdb->base_prefix . 'esp_extra_meta m1'
                      . ' ON b.blog_id = m1.OBJ_ID AND m1.EXM_type = "Blog" AND m1.EXM_key="'
                      . $key_that_should_exist . '"';
        }
        $query .= ' LEFT JOIN ' . $wpdb->base_prefix . 'esp_extra_meta m2'
                  . ' ON b.blog_id = m2.OBJ_ID AND m2.EXM_type = "Blog" AND m2.EXM_key="'
                  . $key_that_shouldnt_exist . '"'
                  . ' WHERE';
        if ($key_that_should_exist !== null) {
            $query .= ' m1.EXM_value IS NOT NULL AND';
        }
        $query .= ' m2.EXM_value IS NULL AND bm.BLG_last_admin_visit < "'
                  . date(EE_Datetime_Field::mysql_timestamp_format, $threshold_time)
                  . '"';
        if (! empty($protected_blogs)) {
            $query .= ' AND b.blog_id NOT IN (' . implode(',', $protected_blogs) . ')';
        }
        $blog_ids = $this->_do_wpdb_query('get_col', array($query));
        if (! is_array($blog_ids) || empty($blog_ids)) {
            return array();
        }
        return $this->get_all(
            array(
                array(
                    'blog_id' => array('IN', $blog_ids)
                ),
                'limit' => count($blog_ids)
            )
        );
    }
}

// End of file EE_Blogs.model.php
