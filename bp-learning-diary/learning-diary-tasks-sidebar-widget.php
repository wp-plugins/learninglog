<?php
 

class Learning_Diary_Tasks_Widget extends WP_Widget {
	
	/**
	 * Widget setup.
	 */
	
	function Learning_Diary_Tasks_Widget() {
		/* Widget settings. */
		$widget_ops = array( 
			'classname' => 'learning_diary_tasks', 
			'description' => __('Zeigt die neusten Aufgaben f&uuml;r Lernende.', 'bp_learning_diary') 
		);

		/* Widget control settings. */
		$control_ops = array( 'width' => 200, 'height' => 350, 'id_base' => 'learning-diary-tasks-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'learning-diary-tasks-widget', __('Lerntagebuchaufgaben', 'bp_learning_diary'), $widget_ops, $control_ops );
	
	}

	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		global $current_user;
		extract( $args );
		//Check if user is Admin of Blog -> Show Current Tasks only if he is admin
		if(current_user_can('manage_options')){
			$learningdiarytasks = new LearningDiaryTasks;
			$tasks = $learningdiarytasks->get_recent_tasks($current_user->ID);
			if( $tasks["tasks"] ) {
				
				/* Our variables from the widget settings. */
				$title = apply_filters('widget_title', $instance['title'] );
				
				if(!$title) 
					$title = __('Lerntagebuchaufgaben', 'bp_learning_diary');
				
		
				/* Before widget (defined by themes). */
				echo $before_widget;
		
				/* Display the widget title if one was input (before and after defined by themes). */
				if ( $title )
					echo $before_title . $title . $after_title;
					
				/* Get Learning Diary Tasks */
				
				
				?>
				
				<div id="the-task-list">
					<table id="learning_diary_tasks_table">	
						
				<?php 
					$has_new_tasks=false;
					foreach ( $tasks["tasks"]  as $task ){
						if(!$task->post_id){
							//do once:
							if($has_new_tasks==false){
								?>
								<tr>
									<td class="td-task-title"></td>
									<td class="task-date"><strong><?php _e('Abzugeben bis', 'bp_learning_diary')?></strong>
									</td>
								</tr>
								<?php 
								$has_new_tasks=true;
							}
							
							$userdata = get_userdata($task->post_author);
							
						?>
							<tr  id="task-<?php echo $task->ID; ?>">
							
								<td class="td-task-title">
									<h6><?php echo $task->post_title ?></h6>
									<span>
										<?php printf(__('(von %s)', 'bp_learning_diary'), $userdata->display_name)?>
									</span>
									
								</td>
								<td class="task-date">
									<span><?php 
									if($task->review_date != '0000-00-00 00:00:00') {
										$datetime = strtotime($task->review_date);
										$dateformat=get_option('date_format');
										echo date_i18n( $dateformat, $datetime );
										$timeformat=get_option('time_format');
										echo date_i18n($timeformat, $datetime);
									}else{
										_e('offen', 'bp_learning_diary');
									}
									?></span>
									<div id="widget_task_edit">
								  	<?php if($task->post_id){ ?>
											<a href="<?php echo get_bloginfo( 'url' ); ?>/wp-admin/post.php?action=edit&post=<?php echo $task->post_id ?>" class="button"><?php _e("Edit") ?></a>	
								  	<?php }else{ ?>
											<form name="post" action="<?php echo get_bloginfo( 'url' ); ?>/wp-admin/admin.php?page=learning_tasks&action=createpost" method="post" class="quick-press">
												<input name="task_id" type="hidden" value="<?php echo $task->ID ?>" />
												<input name="post_title" type="hidden" value="<?php echo $task->post_title ?>" />
												<input name="post_content" type="hidden" value="<?php echo $task->post_content ?>" />
												<input type="submit" class="button" value="<?php _e('Beantworten', 'bp_learning_diary')?>">
											</form>
								  	<?php } //endif ?>
									</div>
								
									
								</td>
							</tr>
				<?php 		
						} 
					}
					if(!$has_new_tasks) {
						?>
							<tr><td><p><strong><?php _e("Keine neuen Aufgaben!", 'bp_learning_diary') ?></strong></p></td></tr>
						<?php 	
					}	
				?>			
							</table>
						
				
				<?php
						if ( current_user_can('edit_posts') ) { ?>
							<div id="show-all-tasks"><p><a href="<?php echo get_bloginfo( 'url' ); ?>/wp-admin/admin.php?page=<?php echo LearningDiaryTasks::LEARNING_DIARY_TASKS_LEARNING_TASKS; ?>" class="button"><?php _e('View all'); ?></a></p></div>
							</div>
				<?php	}
				
				
				
					
		
				/* After widget (defined by themes). */
				echo $after_widget;
			}
		}
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['name'] = strip_tags( $new_instance['name'] );

		/* No need to strip tags for sex and show_sex. */
		$instance['sex'] = $new_instance['sex'];
		$instance['show_sex'] = $new_instance['show_sex'];

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 
			'title' => __('Meine Lerntagebuchaufgaben', 'bp_learning_diary'), 
			//'name' => __('John Doe', 'example'), 'sex' => 'male', 'show_sex' => true 
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php _e('Title:'); ?>
			</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" 
				name="<?php echo $this->get_field_name( 'title' ); ?>" 
				value="<?php echo $instance['title']; ?>" 
			/>
		</p>

	<?php
	}
	
}


?>