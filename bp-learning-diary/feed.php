<?php

	$root = dirname(dirname(dirname(dirname(__FILE__))));

	require_once($root.'/wp-load.php');
	
	if(!$_GET['group']) return false;
	
	global $current_user;

	include_once("learning-diary-tasks-group-activity.php");
	
	$get_feed_ug = array('group_id' => $_GET['group'], 'user_id' =>  $_GET['user']);
	
	$ld_group_activity = new LearningDiaryTasksGroupActivity($get_feed_ug);

	//make sure that have_posts() do what we expect
	$current_user->ID = $ld_group_activity->show_user_id();
	
	if($ld_group_activity->allowed($_GET['key']) || $current_user->ID == 0) {		
		
		$activity = $ld_group_activity->get_group_activity();

		$all_posts_dates = $activity['posts_dates'];
		$all_blogs_ids = $activity['blogs_ids'];
		$all_posts_ids = $activity['posts_ids'];
	}
	
	//formating according to http://feedvalidator.org
	header ("Content-type: application/rss+xml");

	echo ("<?xml version=\"1.0\" encoding=\"".get_bloginfo('charset')."\"?>\n");
	?>
	<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
	<title><?php echo "lerntagebuch.ch"?> </title>
	<description><?php bloginfo('name');?></description>
	<link><?php echo "http://gruppe_xy.ch"?></link>
	<language><?php bloginfo('language');?></language>

	<?php
	
	foreach($all_posts_ids as $key => $post_id){
		
		switch_to_blog($all_blogs_ids[$key]);

		$post = get_post($post_id);

		query_posts('p=' . $post_id); //query for have_posts()

		if ( have_posts() ) { 

			while (have_posts()) { 
				the_post(); ?>
		
				<item>
					<title><?php the_title()?></title>
					<link><?php the_permalink() ?></link>
					<description><?php echo strip_tags(get_the_content()) ?></description>					
					<pubDate><?php the_time('r'); ?></pubDate>
					<guid><?php the_permalink() ?></guid>
					<comments><?php comments_link(); ?></comments>
					<author><?php the_author_meta('user_email'); echo " ("; the_author_meta('display_name'); echo ")"; ?></author>
				</item>
	
				<?php
			}
		}
	}
	wp_reset_query();

	restore_current_blog();
	?>
	</channel>
	</rss>
	
