<div id="assessment-pane">
	<h1 id="main-title"><?php _e( 'Assessing what sites need Migrating...', 'event_espresso' ) ?></h1>
	<h2 id='sub-title'> <?php _e( 'Once done assessing, the option to migrate the network\'s data will appear, if necessary', 'event_espresso' ); ?></h2>
	<div id="sites-needing-migration-progress-bar" class="progress-bar">
		<figure>
			<div class="bar" style="background:#2EA2CC;"></div>
			<div class="percent"></div>
		</figure>
		<table id="migration-assessment" class="form-table">
			<tr><th><?php _e( 'Total:', 'event_espresso' ) ?></th><td id="migration-assessment-total">1</td></tr>
			<tr><th><?php _e( 'Up-to-date:', 'event_espresso' ); ?></th><td id="migration-assessment-up-to-date">0</td></tr>
			<tr><th><?php _e( 'Out-of-date:', 'event_espresso' ); ?></th><td id="migration-asssment-out-of-date">0</td></tr>
			<tr><th><?php _e( 'Broken', 'event_espresso' ); ?></th><td id="migration-assessment-borked">0</td></tr>
		</table>
		<a id="begin-multisite-migration" class="button button-primary" style='display:none'><?php _e( 'Migrate Network', 'event_espresso' ); ?></a>
		<p><?php _e( 'Think there are other sites that need migrating? You may want to force a reassessment of which blogs need to be migrated', 'event_espresso' ) ?></p>
		<a id="begin-multisite-migration" href="<?php echo $reassess_url ?>" class="button button-secondary"><?php _e( 'Reassess Network Migration Needs', 'event_espresso' ); ?></a>
	</div>
</div>
<div id="migration-pane" style="display:none">
	<h2 id="sites-migrated-progress-bar-header"><?php _e( 'Sites Migrated', 'event_espresso' ); ?></h2>
	<div id="sites-migrated-progress-bar" class="progress-bar">
		<figure>
			<div class="bar" style="background:#2EA2CC;"></div>
			<div class="percent"></div>
		</figure>
	</div>
	<br style='clear:both'>
	<h2 id="current-blog-title"></h2>
	<div id="migration-scripts">
		<ol><!-- content added dynamically by javascript --></ol>
	</div>
	<div id="current-migration-progress-bar" class="progress-bar">
		<figure>
			<div class="bar" style="background:#2EA2CC;"></div>
			<div class="percent"></div>
		</figure>
	</div>
	<br style='clear:both'>
	<h3 id="current-blog-current-script"><?php __( 'Loading Data Migration Script', 'event_espresso' ) ?></h3>
	<div id="progress-text" style='height:400px;overflow-y:scroll'>
		<!-- content added dynamically by javascript -->
	</div>
</div>

<!-- modal dialog for displaying ajax errors. But so far haven't got it working -->
<h2 id="admin-modal-dialog-apply-payment-h2" class="admin-modal-dialog-h2 hdr-has-icon" style="display:none;">
	<div class="ee-icon ee-icon-cash-add float-left"></div>
</h2>