<?php 

/*
 * Dashboard widgets for showing current tasks and tutorials
 */

Class LearningDiaryTasksDashboardWidget Extends LearningDiaryTasks { 
	
	public function add_style() {
		//$myStyleUrl = WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) ) . '/css/dashboard.css';
		$myStyleUrl = WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/css/dashboard.css';	
		wp_register_style('learning_diary_tasks_dashboard_style', $myStyleUrl);
		wp_enqueue_style( 'learning_diary_tasks_dashboard_style');
		
		//$myStyleUrl = WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) ) . '/css/colorbox.css';
		$myStyleUrl = WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/css/colorbox.css';	
		wp_register_style('learning_diary_tasks_dashboard_colorbox_style', $myStyleUrl);
		wp_enqueue_style( 'learning_diary_tasks_dashboard_colorbox_style');
	}
	
	/*
	 * Add Colorbox Lightbox to Dashboard for video tutorials
	 */
	
	public function add_jquery(){
		wp_enqueue_script( 
			"learning_diary_tasks_dashboard_popupwindow", 
			( WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . "/bp-learning-diary/js/jquery.popupwindow.js"), 
			array( 'jquery' ) 
		);
	    
		//add_action( 'admin_head' );
		
		wp_enqueue_script( 
			"learning_diary_tasks", 
			( WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . "/bp-learning-diary/js/bp-learning-diary-dashboard.js"), 
			array( 'jquery' ) 
		);
	    
		//add_action( 'admin_head' );

	}
	
	public function get_recent_tasks_for_user() {
		global $current_user;
	
		// Select published tasks only
		$tasks = $this->get_recent_tasks($current_user->ID);
	
		if ( $tasks["count_open_posts"] ) { 
	?>
			<div id="the-task-list">
			<table id="recent-tasks-table" width="100%" cellpadding="3" cellspacing="3" class="widefat roundedcorners">
				<thead>
					<tr class="alt">
						<th scope="col" class="th_title"><?php _e('Title')?></th>
						<th scope="col" class="th_author"><?php _e('Author')?></th>
						<th scope="col" class="th_publish_date"><?php _e('Date')?></th>
						<th scope="col" class="th_review_date"><?php _e('Abzugeben bis', 'bp_learning_diary')?></th>
						<th scope="col" class="th_answer"><?php _e('Beantworten', 'bp_learning_diary')?></th>
					</tr>
				</thead>
				<tbody>
	
	<?php
	
			foreach ( $tasks["tasks"] as $task ){
				$userdata = get_userdata($task->post_author);
				
	?>
	
			<tr id="task-<?php echo $task->ID; ?>" class="byuser comment-author-admin comment-item">
				<td class="td-task-title"><span><?php echo $task->post_title ?></span></td>
				<td class="td-task-action"><?php _e('von', 'bp_learning_diary')?> <?php echo $userdata->display_name ?></td>
				<td class="td-task-action"><?php echo date_i18n( get_option('date_format'), strtotime($task->publish_date) ); ?></td>
			<?php if($task->review_date == '0000-00-00 00:00:00'){ ?>
				<td class="td-task-date"><?php _e('Offen', 'bp_learning_diary')?></td>
			<?php }else{ ?>
				<td class="td-task-date"><?php echo date_i18n( get_option('date_format')." ".get_option('time_format'), strtotime($task->review_date) ); ?></td>
			<?php } ?>
				<td class="td-task-answer">
					<div>
			  	<?php if($task->post_id){ ?>
						<a href="<?php echo get_bloginfo( 'url' ); ?>/wp-admin/post.php?action=edit&post=<?php echo $task->post_id ?>" class="button">
							<?php _e('Bearbeiten', 'bp_learning_diary')?>
						</a>	
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

	?>			
	
					</tbody>
				
				</table>
			</div>
	
				<p class="textright"><a href="admin.php?page=learning_tasks" class="button"><?php _e('View all'); ?></a></p>
	<?php	
	
		} else {
	?>
	
		<p><?php _e( 'Es sind keine neuen Aufgaben zu bearbeiten.', 'bp_learning_diary' ); ?></p>		
	
	<?php

		}
		
	}
	
	public function show_teacher_dashboard_tutorials(){
		
		?>
		
		<ul>
			<li>
				<?php // VIDEO 1 ?>
				<a href="<?php echo WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/videos/tutorial1.html' ?>" class="popupwindow" rel="windowCenter">
					<?php _e('Die ersten Schritte bei «lerntagebuch.ch» (Video)', 'bp_learning_diary')?>
				</a>
			</li>
			<li>
				<?php // VIDEO 1 ?>
				<a href="<?php echo WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/videos/tutorial2.html' ?>" class="popupwindow" rel="windowCenter">
					<?php _e('Fragen erfassen und Antworten beobachten (Video)', 'bp_learning_diary')?>
				</a>
			</li>
			<li>
				<?php // VIDEO 1 ?>
				<a href="<?php echo WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/videos/tutorial2.html' ?>" class="popupwindow" rel="windowCenter">
					<?php _e('Gruppe erstellen oder einer Gruppe beitreten (Video)', 'bp_learning_diary')?>
				</a>
			</li>
			<li>&nbsp;
			</li>
			
			<li>
				<?php printf(
					__('Weitere Informationen findest du unter %1$s FAQ%2$s.', 'bp_learning_diary'),
					//'<a href="' . get_current_site(1)->url . get_current_site(1)->path . 'faq">',
					'<a href="http://wordpress.org/extend/plugins/learninglog/faq/">',
					'</a>'
				)?>
			</li>
		</ul>

		<?php 
	
	}
	
	/*
	 * Dashboard Widget for Teachers. Shows most important functions.
	 */
	
	public function show_teacher_dashboard_functions() {
		global $current_user;
		get_currentuserinfo();
		$blog_details = get_blog_details(1);
		?>
		<div class="learning_diary_task_dashboard_shortcuts" >
			<ul>
				<li class="learning_diary_task_dashboard_shortcuts_title"><?php _e('Aufgaben', 'bp_learning_diary')?></li>
				<li><a href="admin.php?page=learning-diary-tasks-init.php"><?php _e('Aufgaben verwalten', 'bp_learning_diary')?></a></li>
				<li><a href="admin.php?page=add-new-task"><?php _e('Neue Aufgabe erstellen', 'bp_learning_diary')?></a></li>
			</ul>
		</div>
		
		<div class="learning_diary_task_dashboard_shortcuts" >
			<ul>
				<li class="learning_diary_task_dashboard_shortcuts_title"><?php _e('Gruppen', 'bp_learning_diary')?></li>
				<li><a href="<?php echo $blog_details->siteurl?>/groups/create/"><?php _e('Neue Gruppe erstellen', 'bp_learning_diary')?></a></li>
				<li><a href="<?php echo $current_user->user_url?>groups/my-groups/"><?php _e('Lernende in Gruppen organisieren', 'bp_learning_diary')?></a></li>
			</ul>
		</div>
		<div  class="learning_diary_task_dashboard_shortcuts">
			<ul>
				<li class="learning_diary_task_dashboard_shortcuts_title"><?php _e('Lernende registrieren', 'bp_learning_diary')?></li>
				<li><a href="admin.php?page=import-user"><?php _e('Eine/n Lernende/n registrieren', 'bp_learning_diary')?></a> </li>
				<li><a href="admin.php?page=bulk-user-import"><?php _e('Mehrere Lernende registrieren', 'bp_learning_diary')?></a></li>
			</ul>
		</div>
		<div style="clear:left" ></div>
		<?php 
	}
	
	public function show_membership_requests()
	{
		global $wpdb, $current_user;
		
		get_currentuserinfo();
		
		$blog_details = get_blog_details(1);
		
		$tbl = $wpdb->base_prefix . "bp_groups_members";
		
		$subq = "SELECT group_id FROM " .  $tbl . " WHERE user_id=" . $current_user->ID . " AND is_admin=1";
		
		$query = "SELECT * FROM " . $tbl . " WHERE group_id IN (" . $subq . ") AND is_confirmed=0";
		
		$requests = $wpdb->get_results($query);

		$groups = array(); //array('group_id' => #requests)
		
		foreach ($requests as $request){	
			++$groups[$request->group_id]; //count outstanding requests for each group
		}
		
		if(!$groups) echo "<p>" . __('Es gibt keine Anfragen.', 'bp_learning_diary') . "</p>";
		
		//echo "<table>";
		
		foreach($groups as $group_id => $count){
			$tbl =  $wpdb->base_prefix . "bp_groups";
	
			$query = "SELECT * FROM " . $tbl . " WHERE id=" . $group_id;
	
			$group = $wpdb->get_row($query);
			$url_gr = $blog_details->siteurl . "/groups/" . $group->slug;
			$url_ms = $blog_details->siteurl . "/groups/" . $group->slug . "/admin/membership-requests";
			?>
			<p><?php 
				printf(__ngettext(
					'Du hast %2$s eine neue Mitgliedschaftsanfrage%3$s zur Gruppe %4$s.',
					'Du hast %2$s %1$s neue Mitgliedschaftsanfragen%3$s zur Gruppe %4$s.',
					$count,
					'bp_learning_diary'
					),
					$count,
					'<a href="' . $url_ms . '">',
					'</a>',
					'<a href="' .  $url_gr . '">' . $group->name . '</a>'
				)
				?>
			</p>
			<?php
		}

		//echo "</table>";
		
	}

	/*
	 * Dashboard Widget for Teachers - First Steps.
	 */
	
	public function show_teacher_dashboard_first_steps() {
		global $current_user;
		get_currentuserinfo();
		$blog_details = get_blog_details(1);
		$blog_url = get_bloginfo(url);
		?>
		<div class="learning_diary_task_dashboard_shortcuts" >
			<p><?php _e('Um Aufgaben stellen zu können, müssen die Lernenden in Gruppen organisiert sein. Dazu ist folgendes Vorgehen notwendig:', 'bp_learning_diary')?></p>
			<ol>
			<li><a href="<?php echo $blog_details->siteurl?>/groups/create/"><?php _e('Neue Gruppe erstellen', 'bp_learning_diary')?></a> 
				<?php _e('or', 'buddypress')?>  <a href="<?php echo $blog_details->siteurl?>/groups/"><?php _e('einer Gruppe beitreten', 'bp_learning_diary')?></a></li>
			<li><a href="<?php echo $blog_url?>/wp-admin/admin.php?page=import-user"><?php _e('Eine/n Lernende/n registrieren', 'bp_learning_diary')?></a> 
				<?php _e('or', 'buddypress')?> <a href="<?php echo $blog_url?>/wp-admin/admin.php?page=bulk-user-import"><?php _e('mehrere Lernende registrieren', 'bp_learning_diary')?></a></li>
			<li><a href="<?php echo $blog_url?>/wp-admin/admin.php?page=add-new-task"><?php _e('Neue Aufgabe erstellen', 'bp_learning_diary')?></a></li>
			<ol>
		</div>
		<?php
	
	}

	
}
?>