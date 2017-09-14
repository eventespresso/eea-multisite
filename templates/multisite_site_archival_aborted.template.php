<head><?php

wp_enqueue_style( 'login' );
do_action( 'login_enqueue_scripts' );

?>
</head>
<body>
<?php do_action( 'login_head' ); ?>
<div style="width:100%; padding:5%">
<img style ='width:95%' src="<?php echo( get_header_image() ); ?>" alt="<?php echo( get_bloginfo( 'title' ) ); ?>" />
</div>
<div id="login">

<h1><?php esc_html_e('Site Archival Cancelled', 'event_espresso');?></h1>
<p><?php esc_html_e('Thanks for logging in again, it\'s been a while.', 'event_espresso');?></p>
<p><?php esc_html_e('You probably saw the email we sent you notifying your site would be deleted if you didn\'t log in. Well, you logged in, and so we won\'t be deleting your site.', 'event_espresso');?></p>
<p><?php esc_html_e('Please, remember to log in every two years, otherwise we\'ll take the hint and realize you don\'t really want the site and will delete it. (But we\'ll again notify you beforehand, like we did this time.)', 'event_espresso');?></p>
<a href="<?php echo admin_url();?>" class="button-primary"><?php esc_html_e('Ok, got it. Take me to my site.', 'event_espresso');?></a>
</div>

<?php do_action( 'login_footer' ); ?>
<div class="clear"></div>
</body>
