<?php

use EventEspresso\core\services\database\TableAnalysis;
use EventEspresso\core\services\loaders\LoaderFactory;

/**
 * EE_Blogs. The "blog" being each individual "blog" (UI "site"). NOT the network.
 *
 * @package       Event Espresso
 * @subpackage    EventEspresso\Multisite
 * @author        Mike Nelson
 * @method get_one($query_params = []): EE_Blog
 */
class EEM_Blog extends EEM_Soft_Delete_Base
{
    /**
     * Blog status bRoKen
     * The blog is borked. Probably because a migration script died on it.
     * We can't migrate it, but we don't want to claim it's 'up-to-date' either
     */
    const status_borked = 'BRK';

    /**
     * Blog status Out of Date
     * This blog is definitely out of date and should be migrated
     */
    const status_out_of_date = 'BOD';

    /**
     * Blog status UNknown
     * The blog might be out of date. EE core or an addon has been upgraded
     * and we haven't checked if it needs to be migrated
     */
    const status_unsure = 'BUN';

    /**
     * Blog status Currently Migrating
     * this blog is currently being migrated by the EE multisite addon
     * (others may be getting migrated without the EE multisite addon)
     */
    const status_migrating = 'BCM';

    /**
     * Blog status UpDated
     * The blog has been updated and EE core and its addons haven't been updated since
     */
    const status_up_to_date = 'BUD';


    /**
     * private instance of the EEM_Blog object
     *
     * @var EEM_Blog|null
     */
    protected static $_instance = null;


    /**
     * @var TableAnalysis|null
     */
    private $table_analysis = null;


    /**
     * @param string|null $timezone
     * @throws EE_Error
     */
    protected function __construct(?string $timezone = '')
    {
        $this->singular_item    = esc_html__('Blog', 'event_espresso');
        $this->plural_item      = esc_html__('Blogs', 'event_espresso');
        $this->_tables          = [
            'Blog'      => new EE_Primary_Table('blogs', 'blog_id', true),
            'Blog_Meta' => new EE_Secondary_Table(
                'esp_blog_meta',
                'BLM_ID',
                'blog_id_fk',
                null,
                true
            ),
        ];
        $this->_fields          = [
            'Blog'      => [
                'blog_id'      => new EE_Primary_Key_Int_Field(
                    'blog_id',
                    esc_html__('Blog ID', 'event_espresso')
                ),
                'site_id'      => new EE_Foreign_Key_Int_Field(
                    'site_id',
                    esc_html__('Site ID', 'event_espresso'),
                    false,
                    0,
                    'Site'
                ),
                'domain'       => new EE_Plain_Text_Field(
                    'domain',
                    esc_html__('Domain', 'event_espresso'),
                    false
                ),
                'registered'   => new EE_Datetime_Field(
                    'registered',
                    esc_html__('Registered', 'event_espresso'),
                    false,
                    current_time('timestamp')
                ),
                'last_updated' => new EE_Datetime_Field(
                    'last_updated',
                    esc_html__('Last Updated', 'event_espresso'),
                    false,
                    current_time('timestamp')
                ),
                'public'       => new EE_Boolean_Field(
                    'public',
                    esc_html__('Public?', 'event_espresso'),
                    false,
                    true
                ),
                'archived'     => new EE_Boolean_Field(
                    'archived',
                    esc_html__('Archived', 'event_espresso'),
                    false,
                    false
                ),
                'mature'       => new EE_Boolean_Field(
                    'mature',
                    esc_html__('Mature', 'event_espresso'),
                    false,
                    false
                ),
                'spam'         => new EE_Boolean_Field(
                    'spam',
                    esc_html__('Spam?', 'event_espresso'),
                    false,
                    false
                ),
                'deleted'      => new EE_Trashed_Flag_Field(
                    'deleted',
                    esc_html__('Deleted?', 'event_espresso'),
                    false,
                    false
                ),
                'lang_id'      => new EE_Integer_Field(
                    'lang_id',
                    esc_html__('Language ID', 'event_espresso'),
                    false,
                    0
                ),
            ],
            'Blog_Meta' => [
                'BLM_ID'               => new EE_DB_Only_Int_Field(
                    'BLM_ID',
                    esc_html__('Blog Meta ID', 'event_espresso'),
                    false,
                    0
                ),
                'blog_id_fk'           => new EE_DB_Only_Int_Field(
                    'blog_id_fk',
                    esc_html__('Blog ID', 'event_espresso'),
                    false,
                    0
                ),
                'STS_ID'               => new EE_Foreign_Key_String_Field(
                    'STS_ID',
                    esc_html__('Status', 'event_espresso'),
                    false,
                    self::status_unsure,
                    'Status'
                ),
                'BLG_last_requested'   => new EE_Datetime_Field(
                    'BLG_last_requested',
                    esc_html__('Last Request for this Blog', 'event_espresso'),
                    false,
                    EE_Datetime_Field::now
                ),
                'BLG_last_admin_visit' => new EE_Datetime_Field(
                    'BLG_last_admin_visit',
                    esc_html__('Last Request for this Blog by an Admin', 'event_espresso'),
                    false,
                    EE_Datetime_Field::now
                ),
            ],
        ];
        $this->_model_relations = [
            'Site' => new EE_Belongs_To_Relation(),
        ];
        parent::__construct($timezone);
    }


    private function tableAnalysis(): TableAnalysis
    {
        if (! $this->table_analysis instanceof TableAnalysis) {
            $this->table_analysis = LoaderFactory::getLoader()->getShared(TableAnalysis::class);
        }
        return $this->table_analysis;
    }


    /**
     * Counts all the blogs which MIGHT need to be migrated
     *
     * @param array $query_params @see EEM_Base::get_all()
     * @return int
     * @throws EE_Error
     */
    public function count_blogs_maybe_needing_migration(array $query_params = []): int
    {
        return $this->count($this->_add_where_query_params_for_maybe_needs_migrating($query_params));
    }


    /**
     * Gets all blogs that might need to be migrated
     *
     * @param array $query_params @see EEM_Base::get_all()
     * @return EE_Blog[]
     * @throws EE_Error
     */
    public function get_all_blogs_maybe_needing_migration(array $query_params = []): array
    {
        return $this->get_all($this->_add_where_query_params_for_maybe_needs_migrating($query_params));
    }


    /**
     * Adds teh where conditions to get all the blogs that might need to be migrated (unsure)
     *
     * @param array $query_params @see EEM_base::get_all()
     * @return array @see EEM_Base::get_all()
     */
    private function _add_where_query_params_for_maybe_needs_migrating(array $query_params = []): array
    {
        return array_merge_recursive(
            [
                // where params
                [
                    'OR*maybe_needs_migrating' => [
                        'STS_ID*unsure' => self::status_unsure,
                        'STS_ID*null'   => ['IS NULL'],
                    ],
                ],
                'order_by' => ['BLG_last_requested' => 'DESC'],
            ],
            $query_params
        );
    }


    /**
     * Counts all blogs which DEFINITELY DO need to be migrated
     *
     * @return int
     * @throws EE_Error
     */
    public function count_blogs_needing_migration(): int
    {
        return $this->count(
            [
                // where params
                [
                    'OR' => [
                        'STS_ID*outdated'    => self::status_out_of_date,
                        'STS_ID*in_progress' => self::status_migrating,
                    ],
                ],
            ]
        );
    }


    /**
     * @throws EE_Error
     */
    public function count_blogs_up_to_date(): int
    {
        return $this->count(
            [
                // where params
                ['STS_ID' => self::status_up_to_date],
            ]
        );
    }


    /**
     * Counts all blogs which DEFINITELY DO need to be migrated
     *
     * @return int
     * @throws EE_Error
     */
    public function count_borked_blogs(): int
    {
        return $this->count(
            [
                // where params
                ['STS_ID' => self::status_borked],
            ]
        );
    }


    /**
     * Gets all the blogs which are broken, probably from a failed migration
     *
     * @param array $query_params @see EEM_Base::get_all()
     * @return EE_Blog[]
     * @throws EE_Error
     */
    public function get_all_borked_blogs(array $query_params = []): array
    {
        return $this->get_all(
            array_merge_recursive(
                [
                    // where params
                    ['STS_ID' => self::status_borked],
                ],
                $query_params
            )
        );
    }


    /**
     * Gets the blog which is marked as currently updating, or
     *
     * @return EE_Blog|null
     * @throws EE_Error
     */
    public function get_migrating_blog_or_most_recently_requested(): ?EE_Blog
    {
        $currently_migrating = $this->get_one([['STS_ID' => self::status_migrating]]);
        if ($currently_migrating) {
            return $currently_migrating;
        }
        return $this->get_one(
            [
                // where params
                ['STS_ID' => self::status_out_of_date],
                'order_by' => ['BLG_last_requested' => 'DESC'],
            ]
        );
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
    public function mark_all_blogs_migration_status_as_unsure(): int
    {
        if ($this->tableAnalysis()->tableExists($this->second_table())) {
            global $wpdb;
            $query = 'UPDATE ' . $this->second_table() . ' SET STS_ID = "' . self::status_unsure . '" WHERE 1=1';
            return $wpdb->query($query);
        }
        // don't do anything. the table doesn't exist, and its default value for STS_ID is self::status_unsure anyways
        // so there is nothing to do to mark it as status unsure
        return 0;
    }


    /**
     * Marks the current blog's status in the esp_blog_meta table as being up-to-date.
     * Can be called from any blog and automatically does the blog switching magic.
     * If the main site is itself in maintenance mode, then this is skipped. Oh well,
     * the blog will just be marked as unsure or out-of-date until the next multisite migration
     * manager assesses which blogs need to be migrated or this blog later notices it has to nothing to migrate
     *
     * @param string $new_status the status you want to update the blog to
     */
    public function mark_current_blog_as(string $new_status = EEM_Blog::status_up_to_date)
    {
        $current_blog_id = get_current_blog_id();
        // needs to use WP's core switch_to_blog() instead of EED_Multisite::switch_to_blog()
        // instead ot avoid an infinite loop.
        if ($this->tableAnalysis()->tableExists($this->second_table())) {
            global $wpdb;
            // first check the current blog isn't ALREADY marked as up-to-date
            $verify_query   = $wpdb->prepare(
                'SELECT STS_ID FROM ' . $this->second_table() . ' WHERE blog_id_fk=%d LIMIT 1',
                $current_blog_id
            );
            $current_status = $wpdb->get_var($verify_query);
            if ($current_status !== $new_status) {
                $query = $wpdb->prepare(
                    'UPDATE ' . $this->second_table() . ' SET STS_ID = %s WHERE blog_id_fk = %d LIMIT 1',
                    $new_status,
                    $current_blog_id
                );
                $wpdb->query($query);
            }
        }
        // dang can't update it because the esp_blog_meta doesn't yet exist. Oh well, we'll just need to check it again
        // later when either the site's maintenance page is visited or the multisite migrator checks it.
        // not a HUGE deal though.
    }


    /**
     * Updates the time the specified blog was last requested to right now
     *
     * @param int $blog_id
     * @return int number of rows updated
     */
    public function update_last_requested(int $blog_id): int
    {
        global $wpdb;
        if ($this->tableAnalysis()->tableExists($this->second_table())) {
            return $wpdb->update(
                $this->second_table(),
                // update columns
                [
                    'BLG_last_requested' => current_time('mysql', true),
                ],
                // where columns
                [
                    'blog_id_fk' => $blog_id,
                ],
                // update format
                [
                    '%s',
                ],
                // where format
                [
                    '%d',
                ]
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
     * @param string|null  $key_that_should_exist
     * @param string|null  $key_that_shouldnt_exist
     * @param array        $protected_blogs blog IDs that shouldn't be returned
     * @return EE_Blog[]
     * @throws EE_Error
     */
    public function get_all_logged_into_since_time_with_extra_meta(
        $threshold_time,
        ?string $key_that_should_exist,
        ?string $key_that_shouldnt_exist,
        array $protected_blogs = []
    ): array {
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

        $blog_ids = $this->_do_wpdb_query('get_col', [$query]);
        if (! is_array($blog_ids) || empty($blog_ids)) {
            return [];
        }

        return $this->get_all(
            [
                // where params
                ['blog_id' => ['IN', $blog_ids]],
                'limit' => count($blog_ids),
            ]
        );
    }
}

// End of file EE_Blogs.model.php
