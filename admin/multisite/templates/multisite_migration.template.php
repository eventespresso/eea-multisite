<div id="assessment-pane">
	<h1 id="main-title"><?php _e( 'Assessing what sites need Migrating...', 'event_espresso' )?></h1>
	<h2 id='sub-title'> <?php _e( 'Once done assessing, the option will to migrate the network\'s data, if necessary', 'event_espresso' );?></h2>
	<div id="sites-needing-migration-progress-bar" class="progress-bar">
	<figure>
		<div class="bar" style="background:#2EA2CC;"></div>
		<div class="percent"></div>
	</figure>
	<table id="migration-assessment" class="form-table">
		<tr><th><?php _e( 'Total:', 'event_espresso' )?></th><td id="migration-assessment-total">1</td></tr>
		<tr><th><?php _e( 'Up-to-date:', 'event_espresso' );?></th><td id="migration-assessment-up-to-date">0</td></tr>
		<tr><th><?php _e( 'Out-of-date:', 'event_espresso' );?></th><td id="migration-asssment-out-of-date">0</td></tr>
		<tr><th><?php _e( 'Broken', 'event_espresso' );?></th><td id="migration-assessment-borked">0</td></tr>
	</table>
		<a id="begin-multisite-migration" class="button button-primary" style='display:none'><?php _e( 'Migrate Network', 'event_espresso' );?></a>
</div>
</div>
<div id="migration-pane" style="display:none">
	<h2><?php _e( 'Sites Migrated', 'event_espresso' );?></h2>
	<div id="sites-migrated-progress-bar" class="progress-bar">
		<figure>
			<div class="bar" style="background:#2EA2CC;"></div>
			<div class="percent"></div>
		</figure>
	</div>
	<h2 id="current-blog-title"><?php _e( 'Current Site: Garths dental visit', 'event_espresso' );?></h2>
	<div id="migration-scripts">
		<ol>
			<li>Core 4.3 DMS</li>
			<li>Calendar 3.1 DMS</li>
		</ol>
	</div>
	<h3 id="current-blog-current-script"><?php __( 'Loading Data Migration Script', 'event_espresso' )?></h3>
	<div id="current-migration-progress-bar" class="progress-bar">
		<figure>
			<div class="bar" style="background:#2EA2CC;"></div>
			<div class="percent"></div>
		</figure>
	</div>
	<div id="progress-text">
		234 records migrated<br/>
		342 records migrated...
	</div>
</div>
