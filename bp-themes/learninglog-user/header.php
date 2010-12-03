<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

	<head profile="http://gmpg.org/xfn/11">

		<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

		<title><?php bp_page_title() ?></title>

		<?php do_action( 'bp_head' ) ?>

		<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" /> <!-- leave this for stats -->

		<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />

		<?php if ( function_exists( 'bp_sitewide_activity_feed_link' ) ) : ?>
			<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> | <?php _e('Site Wide Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_sitewide_activity_feed_link() ?>" />
		<?php endif; ?>

		<?php if ( function_exists( 'bp_member_activity_feed_link' ) && bp_is_member() ) : ?>
			<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> | <?php bp_displayed_user_fullname() ?> | <?php _e( 'Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_member_activity_feed_link() ?>" />
		<?php endif; ?>

		<?php if ( function_exists( 'bp_group_activity_feed_link' ) && bp_is_group() ) : ?>
			<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> | <?php bp_current_group_name() ?> | <?php _e( 'Group Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_group_activity_feed_link() ?>" />
		<?php endif; ?>

		<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> <?php _e( 'Blog Posts RSS Feed', 'buddypress' ) ?>" href="<?php bloginfo('rss2_url'); ?>" />
		<link rel="alternate" type="application/atom+xml" title="<?php bloginfo('name'); ?> <?php _e( 'Blog Posts Atom Feed', 'buddypress' ) ?>" href="<?php bloginfo('atom_url'); ?>" />

		<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

		<?php wp_head(); ?>

	</head>

	<body <?php body_class() ?> id="bp-default" >
	
		<?php do_action( 'bp_before_header' ) ?>
		
		<div id="header">
		
			<h1 id="logo"><a href="<?php echo site_url() ?>" title="<?php _e( 'Home', 'buddypress' ) ?>"><?php //bp_site_name() ?></a></h1>

			<ul id="nav">
				<?php $base_site_url = get_blog_details( 1 )->siteurl; ?>
				<li<?php if ( bp_is_page( 'home' ) && get_current_site()->site_name==get_bloginfo( 'id' )) : ?> class="selected"<?php endif; ?>>
					<a href="<?php echo $base_site_url ?>" title="<?php _e( 'Home', 'buddypress' ) ?>"><?php _e( 'Home', 'buddypress' ) ?></a>
				</li>
				
				<?php if( is_user_logged_in() ) { ?>
				
					<li<?php if ( (get_active_blog_for_user(get_current_user_id())->siteurl) == get_bloginfo( 'url' )) : ?> class="selected"<?php endif; ?>>
						<a href="<?php echo (get_active_blog_for_user(get_current_user_id())->siteurl) ?>" 
							title="<?php _e( 'Mein Lerntagebuch', 'bp_learning_diary' ) ?>"
							>
							<?php _e( 'Mein Lerntagebuch', 'bp_learning_diary' ) ?>
						</a>
					</li>
					
					<li<?php if ( is_admin() ) : ?> class="selected"<?php endif; ?>>
						<a href="<?php echo $base_site_url ?>/wp-admin/" title="<?php _e( 'Administration', 'buddypress' ) ?>"><?php _e( 'Administration', 'buddypress' ) ?></a>
					</li>
				
				<?php } ?>

				<?php if ( bp_is_active( 'groups' ) ) : ?>
					<li<?php if ( bp_is_page( BP_GROUPS_SLUG ) || bp_is_group() ) : ?> class="selected"<?php endif; ?>>
						<a href="<?php echo $base_site_url ?>/<?php echo BP_GROUPS_SLUG ?>/" title="<?php _e( 'Groups', 'buddypress' ) ?>"><?php _e( 'Groups', 'buddypress' ) ?></a>
					</li>

				<?php endif; ?>

				<?php // wp_list_pages( 'title_li=&depth=1&exclude=' . bp_dtheme_page_on_front() ); ?>

				<?php do_action( 'bp_nav_items' ); ?>
			</ul><!-- #nav -->

			<?php do_action( 'bp_header' ) ?>

		</div><!-- #header -->

		<?php do_action( 'bp_after_header' ) ?>
		<?php do_action( 'bp_before_container' ) ?>

		<div id="container">