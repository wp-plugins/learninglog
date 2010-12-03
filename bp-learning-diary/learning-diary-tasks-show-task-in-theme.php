<?php

class LearningDiaryTasksShowTaskInTheme extends LearningDiaryTasks {
	
	/**
	 * Constructor.
	 */
	
	function __construct() {
		//Show task in index.php
		//add_action( 'bp_before_blog_post', array( $this , 'show_task' ) );
		//Show task in single.php
		add_action( 'bp_before_blog_single_post', array( $this , 'show_task' ) );
	}
	
	/*
	 * Show Task before a Post (evtl. in learning-diary-task-plugin verschieben)
	 */
	
	public function show_task() {
		global $post;
		global $wpdb;
		
		//get Task ID
		$task_id = get_post_meta($post->ID,"_task_id",true);

		if($task_id){
			$task = $wpdb->get_results( "SELECT * FROM ".$wpdb->base_prefix.self::TASK_TABLE_NAME." WHERE ID=$task_id" );
			$owner_of_task_id = $task[0]->post_author;
			$user = get_userdata($owner_of_task_id);
			if($user){				
				?>
				
				<div class="post ">
				<div class="post-content">
				<h3>
					<?php printf(__('Aufgabe <small>(von %s)</small>', 'bp_learning_diary'), $user->display_name) ?>
				</h3>
				<h5>
					<?php echo apply_filters('the_title', $task[0]->post_title); ?>
				</h5>
				<?php echo apply_filters('the_content', htmlspecialchars_decode(html_entity_decode(stripslashes($task[0]->post_content)))); ?>
				</div>
				</div>
				
				<?php 
			}
		}
	}
}
	
?>