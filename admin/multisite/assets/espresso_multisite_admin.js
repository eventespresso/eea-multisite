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
			page : 'espresso_multisite'}, this.handle_multisite_migration_step_response );
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
	}
	this.handle_multisite_migration_step_response = function(response, status, xhr){
		if( response.data ){
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
			jQuery('#progress-text').append( current_dms.message);
			//are we all done then?
			if( blogs_needing_migration == 0 ){
				alert(ee_i18n_text.all_done);
			}else{
				driver.step();
			}
		}else{
			alert( ee_i18n_text.ajax_error );
		}
	}


	this.assessment_step = function(){
		this.do_ajax({
			action : 'multisite_migration_assessment_step',
			page : 'espresso_multisite'}, this.handle_multisite_migration_assessment_step_response
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



var Maintenance_helper = {
	begin_migration: function(){
		BG.update_progress_to(0, 1);
		var kickoff_button = jQuery('#start-migration');
		kickoff_button.attr('disabled',true);
		kickoff_button.text(ee_maintenance.migrating);
		Maintenance_helper.continue_migration();
	},
	/**
	 *Used to start and continue the data migration ajax-calling loop. Called by begin_migration to kick-start the process,
	 *and from update_progress in order to continue
	 **/
	continue_migration: function(){
		var data = {
			action: 'migration_step',
			page: 'espresso_maintenance_settings'
		};
		Maintenance_helper.do_ajax(data,{'where':'#migration-messages', 'what':'prepend','callback':Maintenance_helper.update_progress});

	},
	/**
	 * @param ajax_response shoudl eb an object with attributes error, success, notices,content, and data
		data should be an object with attributes  like {records_to_migrate: 1, records_migrated: 1, status: "no_more_migration_scripts", script: null, message: "Data Migration Completed Successfully"…}
	 */
	update_progress: function(ajax_response){
		if(typeof(ajax_response) === 'undefined'){
			migration_data = {
				records_to_migrate:1,
				records_migrated:0,
				status:'Fatal Error',
				script:'Unknown',
				message:'AJAX was not returned'
			};
		}else{
			migration_data = ajax_response.data;
		}
		//update the bar graph
		BG.update_progress_to(migration_data.records_migrated, migration_data.records_to_migrate);

		//update the main title of what we're doing
		Maintenance_helper.display_content(migration_data.script, '#main-message', 'clear');
		//update the descriptive text
		Maintenance_helper.display_content(migration_data.message+'<br>', '#migration-messages', 'prepend');
		if(migration_data.status === ee_maintenance.status_completed ||
			migration_data.status === ee_maintenance.status_no_more_migration_scripts){
			Maintenance_helper.finish( migration_data.records_migrated, migration_data.records_to_migrate );
		}else if(migration_data.status === ee_maintenance.status_fatal_error){
			Maintenance_helper.finish( migration_data.records_migrated, migration_data.records_to_migrate );
		}else{
			Maintenance_helper.continue_migration();
		}
	},
	//handles what to do once we're done the current migration script
	finish: function( records_migrated, records_to_migrate ){
		//change button
		//show after-migration options
		var kickoff_button = jQuery('#start-migration');
		kickoff_button.attr('disabled',false);
		kickoff_button.text(ee_maintenance.next);
		kickoff_button.unbind('click');
		kickoff_button.click(function(){
			document.location.href = document.location.href + '&continue_migration=true';
		});
		BG.update_progress_to( records_migrated, records_to_migrate );
		jQuery( '#progress-responsive__percent' ).css({ 'color' : '#fff' });
		alert(ee_maintenance.click_next_when_ready);
	},
	//performs the ajax request, and if successful, calls setup.callback;
	//on failure with HTML response, calls report_general_migration_error with the content and loads that content to the screen
	do_ajax: function(data, setup) {

			if ( typeof(setup) === 'undefined' ) {
				setup = {
					where: '#migration-messages',
					what: 'clear',
					callback: undefined
				};
			}

			data.ee_admin_ajax = true;

			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: data,
				success: function(response, status, xhr) {
//					alert('response:'+response);
					var ct = xhr.getResponseHeader("content-type") || "";
					if (ct.indexOf('html') > -1) {
						Maintenance_helper.display_content(response,setup.where,setup.what);
						if( typeof(setup.dont_report) === 'undefined'){
							Maintenance_helper.report_general_migration_error(response);
							Maintenance_helper.display_content(ee_maintenance.fatal_error, '#main-message', 'clear');
							Maintenance_helper.finish();
						}
					}

					if (ct.indexOf('json') > -1 ) {
						var resp = response,
						wht = typeof(resp.data.what) === 'undefined' ? setup.what : resp.data.what,
						whr = typeof(resp.data.where) === 'undefined' ? setup.where : resp.data.where,
						display_content = resp.error ? resp.error : resp.content;

						Maintenance_helper.display_notices(resp.notices);
						Maintenance_helper.display_content(display_content, whr, wht);
						//call the callback that was passed in
						if (typeof(setup.callback) !== 'undefined'){
							setup.callback(response);
						}
					}
				}
			});
			return false;
		},
	//sends an ajax message to the backend for logging
	report_general_migration_error: function(message){
		var data = {
			action: 'add_error_to_migrations_ran',
			page: 'espresso_maintenance_settings',
			message:message
		};
		Maintenance_helper.do_ajax(data,{'where':'#migration-messages', 'what':'prepend','dont_report':true});
	},

//we actually want to display notices in the same place as all normal ajax messages appear
	display_notices: function(content) {
		jQuery('#migration-messages').prepend(content);
//		jQuery('#ajax-notices-container').prepend(content);
	},

	display_content: function(content, where, what) {
		if ( typeof(where) === 'undefined' || typeof(what) === 'undefined' ) {
			console.log('content is not displayed because we need where or what');
			return false;
		}
		if ( what == 'clear' ) {
			jQuery(where).html(content);
		} else if ( what == 'append' ) {
			jQuery(where).append(content);
		} else if ( what == 'prepend' ) {
			jQuery(where).prepend(content);
		}
	}
};

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