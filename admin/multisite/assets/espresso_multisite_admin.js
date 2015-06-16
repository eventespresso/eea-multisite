/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


//js for showing/hiding page elements



function EE_Multisite_DMS_Driver(){
	this.sites_total = 1;
	this.sites_migrated = 0;
	this.current_blog_name;
	this.migration_scripts_div_content;
	this.current_dms_records_migrated = 0;
	this.current_dms_records_to_migrate = 1;
	this.assessment_pg = new EE_Progress_Bar(jQuery('#sites-needing-migration-progress-bar'));
	this.sites_pg = new EE_Progress_Bar(jQuery('#sites-migrated-progress-bar'));
	this.current_dms_pg = new EE_Progress_Bar(jQuery('#current-migration-progress-bar'));
	//sends off an ajax request and handles the response
	this.step = function(){
		this.do_ajax({
			action : 'multisite_migration_step',
			page : 'espresso_multisite'
		}, this.handle_multisite_migration_step_response );
	};
	//performs the ajax request, and if successful, calls setup.callback;
	//on failure with HTML response, calls report_general_migration_error with the content and loads that content to the screen
	this.do_ajax = function(data, success_callback) {

		data.ee_admin_ajax = true;

		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,
			success: success_callback,
			error: driver.display_error
		});
		return false;
	};

	this.display_error = function( xhr, status, error ){
		alert( ee_i18n_text.ajax_error + error );
		driver.report_general_migration_error( error );

	}
	this.handle_multisite_migration_step_response = function(response, status, xhr){
		var ct = xhr.getResponseHeader("content-type") || "";
		if (ct.indexOf('json') > -1 && response.data) {
			var blogs_total = response.data.blogs_total;
			var blogs_needing_migration = response.data.blogs_needing_migration;
			var current_blog_name = response.data.current_blog_name;
			var current_blog_script_names = response.data.current_blog_script_names;
			var current_dms = response.data.current_dms;
			driver.sites_pg.update_progress_to( blogs_total - blogs_needing_migration, blogs_total );
			jQuery('#current-blog-title').html(current_blog_name);
			var script_names_html = '';
			var i;
			for (i=0;i<current_blog_script_names.length;i++){
				script_names_html += "<li>"+current_blog_script_names[i]+"</li>";
			}
			jQuery('#migration-scripts').html(script_names_html);
			driver.current_dms_pg.update_progress_to( current_dms.records_migrated, current_dms.records_to_migrate );
			jQuery('#current-blog-current-script').html( current_dms.script );
			jQuery('#progress-text').prepend( response.data.message);
			//are we all done then?
			if( blogs_needing_migration == 0 ){
				jQuery('#sites-migrated-progress-bar-header').html( ee_i18n_text.all_done );
				alert(ee_i18n_text.all_done);
			}else{
				driver.step();
			}
		}else{
			alert( ee_i18n_text.ajax_error );
			driver.report_general_migration_error(response);
		}

	}


	this.assessment_step = function(){
		this.do_ajax({
			action : 'multisite_migration_assessment_step',
			page : 'espresso_multisite'
		}, this.handle_multisite_migration_assessment_step_response
		);
	}
	this.handle_multisite_migration_assessment_step_response = function(response, status, xhr){
		if( response.data ){
			var total = response.data.total_blogs;
			var utd = response.data.up_to_date_blogs;
			var ofd = response.data.out_of_date_blogs;
			var b = response.data.borked_blogs;
			var u = response.data.unknown_status_blogs;
			jQuery('#migration-assessment-total').html( total );
			jQuery('#migration-assessment-up-to-date').html( utd );
			jQuery('#migration-asssment-out-of-date').html( ofd );
			jQuery('#migration-assessment-borked').html( b );
			driver.assessment_pg.update_progress_to( total - u, total );
			if( total == utd ){//no need to migrate anything
				jQuery('#main-title').html(ee_i18n_text.done_assessment);
				jQuery('#sub-title').html(ee_i18n_text.no_migrations_required);
			}else if( u == 0){//at least we are done assessing
				jQuery('#main-title').html(ee_i18n_text.done_assessment);
				jQuery('#sub-title').html(ee_i18n_text.network_needs_migration);
				jQuery('#begin-multisite-migration').show();
			}else{
				//still not done assessing
				driver.assessment_step();
			}
		}else{
			alert( ee_i18n_text.ajax_error );
			driver.report_general_migration_error( response );
		}

	}
	//sends an ajax message to the backend for logging
	this.report_general_migration_error = function(message){
		var data = {
			action: 'multisite_migration_error',
			page: 'espresso_multisite',
			message:message
		};
		this.do_ajax(data);
		alert( "reported general migration error");
	}
}

function EE_Multisite_Delete_Sites() {
    this.total_sites_to_be_deleted = 0;
    this.sites_deleted = 0;
    this.sites_progress = new EE_Progress_Bar( jQuery( '#sites-deleted-progress-bar' ) );
    this.step = function() {
        this.do_ajax({
            action: 'delete_sites_range',
            page : 'espresso_multisite',
            total_sites_to_be_deleted : deleteDriver.total_sites_to_be_deleted
        }, this.handle_multisite_delete_step_response );
    }

    //performs ajax request, and if successful, calls the success callback
    this.do_ajax = function( data, success_callback ) {
        data.ee_admin_ajax = true;

        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
            success: success_callback,
            error: deleteDriver.display_error
        });
        return false;
    }


    this.display_error = function( xhr, status, error ) {
        alert( ee_i18n_text.ajax_error + error );
    }

    this.handle_multisite_delete_step_response = function( response, status, xhr ) {
        var ct = xhr.getResponseHeader("content-type") || "";
        if (ct.indexOf('json') > -1 && response.data) {
            if ( response.data.total_sites_to_be_deleted ) {
                deleteDriver.total_sites_to_be_deleted = response.data.total_sites_to_be_deleted;
            }
            deleteDriver.sites_deleted = deleteDriver.sites_deleted + response.data.sites_deleted;
            deleteDriver.sites_progress.update_progress_to( deleteDriver.sites_deleted, deleteDriver.total_sites_to_be_deleted );
            jQuery('#progress-text').prepend( response.data.message);
            //are we all done then?
            if( deleteDriver.sites_deleted == deleteDriver.total_sites_to_be_deleted ){
                jQuery('#sites-migrated-progress-bar-header').html( ee_i18n_text.all_done_deleting );
                alert(ee_i18n_text.all_done_deleting);
            }else{
                deleteDriver.step();
            }
        }else{
            alert( ee_i18n_text.ajax_error );
        }
    }
}

//for converting a div like
//<div id="current-migration-progress-bar" class="progress-bar">
//	<figure>
//		<div class="bar" style="background:#2EA2CC;"></div>
//		<div class="percent"></div>
//	</figure>
//</div>
//into a progress bar that can be updated dynamically, probably in response to ajax requests
function EE_Progress_Bar( containing_div ){
	this.containing_div = containing_div;
	this.items_total = 1;
	this.items_complete = 0;
	this.update_progress_to = function( items_complete, items_total ){
		items_complete = parseInt( items_complete );
		items_total = parseInt( items_total );
		var percent_complete = items_complete / items_total;
		var bar_size = jQuery('figure', this.containing_div).innerWidth();
		var new_bar_size = percent_complete * parseFloat( bar_size );
		jQuery('.bar',this.containing_div).width( new_bar_size );
		percent_complete = Math.floor( percent_complete * 100 ) + '% ('+items_complete+'/'+items_total+')';
		jQuery('.percent', this.containing_div).text(percent_complete);
	}
}

jQuery(function() {
	//	alert("jquery a go");
	driver = new EE_Multisite_DMS_Driver();
	driver.assessment_step();
	jQuery('#begin-multisite-migration').click(function(){
		jQuery('#migration-pane').toggle('slow');
		jQuery('#assessment-pane').toggle('slow');
		driver.step();
	});
});

jQuery(function() {
    deleteDriver = new EE_Multisite_Delete_Sites();
    jQuery('#ee-prune-sites-button').click( function(e) {
        e.preventDefault();
        jQuery(this).hide();
        jQuery( '#ee-delete-progress-pane').toggle('slow');
        deleteDriver.step();
    });
});