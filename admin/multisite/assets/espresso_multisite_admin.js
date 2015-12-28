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
    deleteDriver = new EE_Multisite_Delete_Sites();
    jQuery('#ee-prune-sites-button').click( function(e) {
        e.preventDefault();
        jQuery(this).hide();
        jQuery( '#ee-delete-progress-pane').toggle('slow');
        deleteDriver.step();
    });
});