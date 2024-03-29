<?php

/**
 * Class  EED_Multisite
 *
 * @package               Event Espresso
 * @subpackage            espresso-multisite
 * @author                Mike Nelson
 */
class EED_Multisite_Site_List_Table extends EED_Module
{
    /**
     *    set_hooks - for hooking into EE Core, other modules, etc
     *
     * @access    public
     * @return    void
     */
    public static function set_hooks()
    {
    }


    /**
     *    set_hooks_admin - for hooking into EE Admin Core, other modules, etc
     *
     * @access    public
     * @return    void
     */
    public static function set_hooks_admin()
    {
        //      echo "add columns";
        add_filter('wpmu_blogs_columns', ['EED_Multisite_Site_List_Table', 'columns']);
        add_action('manage_sites_custom_column', ['EED_Multisite_Site_List_Table', 'cell_content'], 10, 2);
        add_filter('manage_sites-network_sortable_columns', ['EED_Multisite_Site_List_Table', 'sortable_columns']);
        add_filter('query', ['EED_Multisite_Site_List_Table', 'join_to_blog_meta_table']);
    }


    public static function columns($columns)
    {
        $columns['ee_status'] = __('Event Espresso Status', 'event_espresso');
        return $columns;
    }


    public static function cell_content($column_name, $blog_id)
    {
        if (EE_Maintenance_Mode::instance()->models_can_query()) {
            if ($column_name == 'ee_status') {
                $blog = EEM_Blog::instance()->get_one_by_ID($blog_id);
                if ($blog instanceof EE_Blog) {
                    echo $blog->pretty_status();
                }
            }
        } else {
            echo __('Unsure', 'event_espresso');
        }
    }


    public static function sortable_columns($sortable_columns)
    {
        // we tell it to sort on  a column that doesn't yet exist... but we'll change the
        // database query to join to the esp_blog_meta table where it DOES exist
        $sortable_columns['ee_status'] = 'STS_ID';
        return $sortable_columns;
    }


    public static function join_to_blog_meta_table($query)
    {
        global $wpdb;
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            //      var_dump( $screen) ;
            $from_blogs_sql = "FROM {$wpdb->blogs}";
            if (isset($screen->base) && $screen->base == 'sites-network' && strpos($query, $from_blogs_sql) !== false) {
                $from_blogs_join_blog_meta_sql = $from_blogs_sql
                                                 . " LEFT JOIN {$wpdb->base_prefix}esp_blog_meta ON {$wpdb->blogs}.blog_id = {$wpdb->base_prefix}esp_blog_meta.blog_id_fk";
                $query                         = str_replace($from_blogs_sql, $from_blogs_join_blog_meta_sql, $query);
                // sanitize incoming request vars
                $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : '';
                if (strpos($query, 'ORDER BY') === false && $orderby == 'STS_ID') {
                    $limit_sql_only         = ' LIMIT';
                    $order                  =
                        isset($_REQUEST['order']) && strtolower($_REQUEST['order']) == 'asc' ? 'asc' : 'desc';
                    $order_by_and_limit_sql =
                        "ORDER BY FIELD( STS_ID, 'BRK','BOD','BUN','BCM','BUD' ) {$order}" . $limit_sql_only;
                    $query                  = str_replace($limit_sql_only, $order_by_and_limit_sql, $query);
                }
                //              echo "new query: $query<br>";
            }
        }
        return $query;
    }


    /**
     *    run - initial module setup
     *
     * @access    public
     * @param WP $WP
     * @return    void
     */
    public function run($WP)
    {
    }


    /**
     *        @ override magic methods
     *        @ return void
     */
    public function __set($a, $b)
    {
        return false;
    }


    public function __get($a)
    {
        return false;
    }


    public function __isset($a)
    {
        return false;
    }


    public function __unset($a)
    {
        return false;
    }


    public function __clone()
    {
        return false;
    }


    public function __wakeup()
    {
        return false;
    }


    public function __destruct()
    {
        return false;
    }
}

// End of file EED_Multisite.module.php
// Location: /wp-content/plugins/espresso-multisite/EED_Multisite.module.php
