<?php
if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}



/**
 * EEM_Blog_Test
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 */
class EEM_Blog_Test extends EE_Multisite_UnitTestCase
{

    public function test_get_all()
    {
        $this->assertEquals(1, EEM_Blog::instance()->count());
        //insert one using the normal WP way
        $this->factory->blog->create_many(2);
        $this->assertEquals(3, EEM_Blog::instance()->count());
        //insert one using the nomra l EE way
        $this->new_model_obj_with_dependencies('Blog');
        $this->assertEquals(4, EEM_Blog::instance()->count());
    }



    public function test_count_blogs_needing_migration()
    {
        //these two don't need to be migrated
        $this->factory->blog->create_many(2);
        $blog_needing_migration = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_out_of_date));
        $blog_maybe_needing_migration = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_unsure));
        $blog_up_to_date = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_up_to_date));
        $this->assertEquals(1, EEM_Blog::instance()->count_blogs_needing_migration());
    }



    public function test_count_blogs_maybe_needing_migration()
    {
        //these two new ones start off marked as up-to-date; the main blog starts of unsure
        $this->factory->blog->create_many(2);
        $blog_needing_migration = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_out_of_date));
        $blog_maybe_needing_migration = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_unsure));
        $blog_up_to_date = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_up_to_date));
        $this->assertEquals(2, EEM_Blog::instance()->count_blogs_maybe_needing_migration()); //main site and one with statu s'unsure'
        $this->assertEquals(2, count(EEM_Blog::instance()->get_all_blogs_maybe_needing_migration()));
    }



    public function test_count_blogs_up_to_date()
    {
        //the main site starts off unsure of whether it needs to be migrated or not. 
        //but these two new sites start off as up-to-date.
        $this->factory->blog->create_many(2);
        $this->assertEquals(2, EEM_Blog::instance()->count_blogs_up_to_date());
        $blog_needing_migration = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_out_of_date));
        $blog_maybe_needing_migration = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_unsure));
        $blog_up_to_date = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_up_to_date));
        $this->assertEquals(3, EEM_Blog::instance()->count_blogs_up_to_date()); //just the last created one is KNOWN to be up-to-date
    }



    public function test_get_migrating_blog_or_most_recently_requested()
    {
        //these two MIGHT need migrating, so MIGHT the main site
        $this->factory->blog->create_many(2);
        $blog_needing_migration_last_long_ago = $this->new_model_obj_with_dependencies('Blog',
            array('STS_ID' => EEM_Blog::status_out_of_date, 'BLG_last_requested' => current_time('timestamp') - 1000));
        $blog_needing_migration_last_requetsed_now = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_out_of_date, 'BLG_last_requested' => current_time('timestamp')));
        $blog_needing_migration_last_way_long_ago = $this->new_model_obj_with_dependencies('Blog',
            array('STS_ID' => EEM_Blog::status_out_of_date, 'BLG_last_requested' => current_time('timestamp') - 9000));
        $blog_migrating = $this->new_model_obj_with_dependencies('Blog', array('STS_ID' => EEM_Blog::status_migrating));
        $this->assertEquals($blog_migrating, EEM_Blog::instance()->get_migrating_blog_or_most_recently_requested());
        $blog_migrating->delete();
        $this->assertEquals($blog_needing_migration_last_requetsed_now, EEM_Blog::instance()->get_migrating_blog_or_most_recently_requested());
    }



    public function test_mark_all_blogs_migration_status_as_unsure()
    {
        $blog1 = $this->_create_a_blog_with_ee();
        $blog1->save(array('STS_ID' => EEM_Blog::status_up_to_date));
        $this->assertEquals(EEM_Blog::status_up_to_date, $blog1->STS_ID());
        $blog2 = $this->_create_a_blog_with_ee();
        $blog2->save(array('STS_ID' => EEM_Blog::status_out_of_date));
        $this->assertEquals(EEM_Blog::status_out_of_date, $blog2->STS_ID());
        EEM_Blog::reset()->mark_all_blogs_migration_status_as_unsure();
        $refreshed_blog1 = EEM_Blog::instance()->get_one_by_ID($blog1->ID());
        $refreshed_blog2 = EEM_Blog::instance()->get_one_by_ID($blog2->ID());
        $this->assertEquals(EEM_Blog::status_unsure, $refreshed_blog1->STS_ID());
        $this->assertEquals(EEM_Blog::status_unsure, $refreshed_blog2->STS_ID());
    }



    /**
     * @group current
     */
    public function test_get_all_logged_into_since_time_with_extra_meta()
    {
        //set the main blog so it would get archived, but it's protected so it shouldn't
        $main_blog = EEM_Blog::instance()->get_one_by_ID(1);
        $main_blog->set('domain','main');
        $main_blog->set('BLG_last_admin_visit', new \EventEspresso\core\domain\entities\DbSafeDateTime('-24 months'));

        //this blog was recently visited, so it shouldn't have any actions taken on it
        $blog_no_action = $this->new_model_obj_with_dependencies(
            'Blog',
            array(
                'domain' => 'no-action',
                'BLG_last_admin_visit' => new \EventEspresso\core\domain\entities\DbSafeDateTime('now')
            )
        );

        //this blog was visited a long time ago and should get the first warning
        $blog_first_warning = $this->new_model_obj_with_dependencies(
            'Blog',
            array(
                'domain' => 'give_me_first_warning',
                'BLG_last_admin_visit' => new \EventEspresso\core\domain\entities\DbSafeDateTime('-23 months')
            )
        );

        //this blog already got the first warning and should get the 2nd warning
        $blog_second_warning = $this->new_model_obj_with_dependencies(
            'Blog',
            array(
                'domain' => 'give_me_second_warning',
                'BLG_last_admin_visit' => new \EventEspresso\core\domain\entities\DbSafeDateTime('-24 months')
            )
        );
        $this->_pretend_did_actions_up_to_but_not_including('second_warning', $blog_second_warning);

        //this blog already got both warnings and so should get an email telling them their site will be deleted
        $blog_archive_me = $this->new_model_obj_with_dependencies(
            'Blog',
            array(
                'domain' => 'bluff-archive-me',
                'BLG_last_admin_visit' => new \EventEspresso\core\domain\entities\DbSafeDateTime('-26 months')
            )
        );
        $this->_pretend_did_actions_up_to_but_not_including('really_archive', $blog_archive_me);

        //this site has been warned and told their site is deleted, which was a bluff. But now they should really be deleted
        $blog_archived = $this->new_model_obj_with_dependencies(
            'Blog',
            array(
                'domain' => 'really-archive-me',
                'BLG_last_admin_visit' => new \EventEspresso\core\domain\entities\DbSafeDateTime('-28 months')
            )
        );
        $this->_pretend_did_actions_up_to_but_not_including('', $blog_archived);

        $cleanup_tasks_and_expected_matches = array(
            EED_Multisite_Auto_Site_Cleanup::FIRST_WARNING_LABEL      => array(
                'interval' => EED_Multisite_Auto_Site_Cleanup::FIRST_WARNING_WAIT_TIME,
                'expected' => array(
                    $blog_first_warning->ID() => $blog_first_warning
                )
            ),
            EED_Multisite_Auto_Site_Cleanup::SECOND_WARNING_LABEL     => array(
                'interval' => EED_Multisite_Auto_Site_Cleanup::SECOND_WARNING_WAIT_TIME,
                'expected' => array(
                    $blog_second_warning->ID() => $blog_second_warning
                )
            ),
            EED_Multisite_Auto_Site_Cleanup::ARCHIVE_SITE_BLUFF_LABEL => array(
                'interval' => EED_Multisite_Auto_Site_Cleanup::ARCHIVE_SITE_BLUFF_WAIT_TIME,
                'expected' => array()
            ),
            EED_Multisite_Auto_Site_Cleanup::ARCHIVE_SITE_REAL_LABEL  => array(
                'interval' => EED_Multisite_Auto_Site_Cleanup::ARCHIVE_SITE_REAL_WAIT_TIME,
                'expected' => array(
                    $blog_archive_me->ID() => $blog_archive_me
                )
            )
        );
        $previous_interval_label = null;
        foreach ($cleanup_tasks_and_expected_matches as $label => $more_info) {
            $threshold_time = strtotime('-' . $more_info['interval']);
            $blogs_matching_criteria = EEM_Blog::instance()->get_all_logged_into_since_time_with_extra_meta(
                $threshold_time,
                EED_Multisite_Auto_Site_Cleanup::get_action_record_extra_meta_name(
                    $previous_interval_label
                ),
                EED_Multisite_Auto_Site_Cleanup::get_action_record_extra_meta_name(
                    $label
                )
            );
            $this->assertEquals(
                $more_info['expected'],
                $blogs_matching_criteria,
                $label
            );
            $previous_interval_label = $label;
        }
    }



    /**
     * Adds extra metas which indicate all the previous cleanup tasks, except the one with label $action_label_name, were already done.
     * @param string $action_label_name. A key from EED_Multisite_Auto_Site_Cleanup::get_cleanup_tasks(). Otherwise, will pretend ALL actions were done
     * @param EE_Base_Class $blog
     * @return void
     */
    protected function _pretend_did_actions_up_to_but_not_including( $action_label_name, EE_Blog $blog)
    {
        $action_labels = array_keys(EED_Multisite_Auto_Site_Cleanup::get_cleanup_tasks());
        foreach( $action_labels as $an_action_label) {
            if( $action_label_name === $an_action_label) {
                break;
            }
            $blog->add_extra_meta(
                EED_Multisite_Auto_Site_Cleanup::get_action_record_extra_meta_name($an_action_label),
                '2017-01-01 00:00:00'
            );
        }
    }
    //test a blog matching the first criteria
    //test a blog matching the 2nd criteria but hasn't done the first criteria
    //test a blog has done the penultimate event and not yet meeting the criteria for the last event
    //test a blog has done the penultimate event and mathces the criteria for the last event



}

// End of file EEM_Blog_Test.php