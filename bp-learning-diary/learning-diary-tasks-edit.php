<?php

/*
 * Main Page for editig and solving tasks
 */

class LearningDiaryTasksEdit extends LearningDiaryTasks {
	
	function __construct() {
		add_filter ( 'attachment_fields_to_save', array ($this, 'reset_attachement_id' ), 2 );
	}
	
	public function reset_attachement_id($post, $attachment) {
		if ($post ["post_parent"] == 1)
			$post ["post_parent"] = 0;
		
		return $post;
	}
	
	/*
	 * Add CSS
	 */
	
	public function add_style() {
	//	$myStyleUrl = WP_PLUGIN_URL . '/' . basename ( dirname ( __FILE__ ) ) . '/css/admin.css';
		$myStyleUrl = WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/css/admin.css';
		wp_register_style ( 'learning_diary_tasks_admin_style', $myStyleUrl );
		wp_enqueue_style ( 'learning_diary_tasks_admin_style' );
		// FOR RTE
		wp_enqueue_style ( 'thickbox' );
	
	}
	
	/*
	 * Add JS
	 */
	
	public function add_jquery() {
		wp_enqueue_script ( 
			"learning_diary_tasks", 
			//(WP_PLUGIN_URL . "/" . basename ( dirname ( __FILE__ ) ) . "/js/bp-learning-diary.js"), 
			(WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/js/bp-learning-diary.js'),
			array ('jquery' ) 
		);

		//localization of strings in javascript
		wp_localize_script( 
			'learning_diary_tasks', 
			'de_strings', //name of the js object that is created
			array(
				'shortq' => __('Kurzfrage hier eingeben', 'bp_learning_diary'),
			)
		);
		//for RTE
		wp_enqueue_script ( 'editor' );
		wp_enqueue_script ( 'thickbox' );
		wp_enqueue_script ( 'media-upload' );
		add_action ( 'admin_head', 'wp_tiny_mce' );
	
	}
	
	/*
	 * handle new task or edit task data
	 */
	
	private function handle_new_task() {
		global $wpdb;
		global $current_user;
		global $_POST;
		
		//CHECK FOR ERRORS
		$the_title = esc_html ( $_POST ["post_title"] );
		$the_content = wp_kses ( $_POST ["content"], $allowed_tags );
		
		//Check nonce field	
		if (! wp_verify_nonce ( $_POST ["_wpnonce"], 'add-learning-diary-entry' ))
			die ( "Nonce" );
		
		//for task-new and task-edit only
		if (($_POST ["action"] == "task-new" || $_POST ["action"] == "task-edit") && $_POST ["save_or_publish"] == "save")
			$_POST ["action"] = "post-quickpress-save";
		else if (($_POST ["action"] == "task-new" || $_POST ["action"] == "task-edit") && $_POST ["save_or_publish"] == "publish")
			$_POST ["action"] = "post-quickpress-publish";
		
		if ($_POST ["action"] == "post-quickpress-save") {
			$the_status = "drafts";
		} else if ($_POST ["action"] == "post-quickpress-publish") {
			$the_status = "publish";
		} else {
			//TODO: Error Catching
			print_r ( "failed" );
			break;
		}
		
		//sanitize data
		$the_title = esc_attr ( $_POST ["post_title"] );
		define ( 'CUSTOM_TAGS', false );
		$the_content = htmlspecialchars ( ($_POST ["content"]) );
		
		//	$visible_for = $_POST["learning-diary-tasks-visible-for"];
		

		//checks if user has chosen specific groups or specific users
		//	if( $visible_for == "specific_groups" ){
		$the_groups = $_POST ["learning-diary-tasks-user-post-access-setting"];
		//		if(!empty($_POST["learning-diary-tasks-user-post-access-setting"]))
		//			$the_users = $_POST["learning-diary-tasks-user-post-access-setting-specific-groups"];
		//	} else if( $visible_for == "specific_users" ){
		$the_users = $_POST ["learning-diary-tasks-user-post-access-setting-specific-users"];
		//	} else {
		//		$the_users = false;
		//	}
		if (! $the_users)
			$the_users = false;
		
		$visibility_of_solution = $_POST ["visibility_of_solution"];
		
		if ($_POST ["learning_diary_task_review_date_input"] == "specific")
			$the_review_mysqltime = $this->get_date ( $_POST, 1 );
		else
			$the_review_mysqltime = "0000-00-00 00:00:00";
		
		if ($_POST ["learning_diary_task_publish_immediately_input"] == "specific")
			$the_publish_mysqltime = $this->get_date ( $_POST, 0 );
		else
			$the_publish_mysqltime = date ( "Y-m-d H:i:s" );
			
		/*
		 * Check for Errors
		 */
		
		$errors = array ();
		
		if (empty ( $_POST ["post_title"] )) {
			$errors ["post_title"] = __ ( "This is a required field", "buddypress" );
		}
		
		if (empty ( $_POST ['content'] )) {
			$errors ["content"] = __ ( "This is a required field", "buddypress" );
		}
		
		if (! $the_users && $the_status == "publish") {
			//checked only if user publishes the task
			$errors ["choose_users"] = __( "Lernende w&auml;hlen", 'bp_learning_diary' );
		}
		
		if (strtotime ( $the_publish_mysqltime ) > strtotime ( $the_review_mysqltime ) && $the_review_mysqltime != "0000-00-00 00:00:00") {
			$errors ["review_date_to_late"] = __( "Abgabedatum muss nach dem Sichtbarkeitsdatum liegen", 'bp_learning_diary' );
		}
		
		if (! $visibility_of_solution && $the_status == "publish") {
			//checked only if user publishes the task
			$errors ["visibility_of_solution"] = __( "Sichtbarkeit w&auml;hlen", 'bp_learning_diary' );
		}
		
		if ($_POST ['answer_format_select'] != 'text' && ! empty ( $_POST ['answer_format_select'] ) && (empty ( $_POST ['answer_format_ques'] ) || 'Kurzfrage hier eingeben' == $_POST ['answer_format_ques'])) {
			$errors ["answer_format_ques"] = __( "Kurzfrage muss eingegeben werden", 'bp_learning_diary' );
		}
		
		/*
		 * Return Errors
		 */
		
		if ($errors) {
			$_POST ["publish_date"] = $the_publish_mysqltime;
			$_POST ["review_date"] = $the_review_mysqltime;
			return $errors;
		}
		
		/*
		 * Handle Data
		 */
		
		$data = array (
			"post_author" => $current_user->ID, 
			"post_content" => $the_content, 
			"post_title" => $the_title, 
			"task_status" => $the_status, 
			"review_date" => $the_review_mysqltime, 
			"publish_date" => $the_publish_mysqltime, 
			"post_date" => date ( "Y-m-d H:i:s" ), 
			"post_modified" => date ( "Y-m-d H:i:s" ), 
			"visibility_of_solution" => $visibility_of_solution 
		);
		
		if ($_POST ["task_id"]) {
			
			//$task_id = intval ($_POST["task_id"]);
			

			/*
			 * Update the task
			 */
			
			$task_id = intval ( $_POST ["task_id"] );
			
			$result = $wpdb->update ( $wpdb->base_prefix . self::TASK_TABLE_NAME, $data, array ('ID' => $task_id ) );
			
			if ($result) {
				
				/*
				 * Check if users exist, that allready edited the task
				 */
				
				$additional_sql = "";
				
				$user_allready_answered = $wpdb->get_row ( "SELECT user_id FROM `" . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . "` WHERE task_id = $task_id AND task_status<>''", ARRAY_N );
				
				foreach ( $user_allready_answered as $the_user_id ) {
					$additional_sql .= " AND user_id<>$the_user_id";
				}
				
				/*
				 * Delete Data from Users that did not answer yet
				 */
				
				$wpdb->query ( "DELETE FROM `" . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . "` WHERE task_id = $task_id $additional_sql" );
				
				/*
				 * Delete Groups in USER_TASK_TABLE_NAME
				 */
				
				$wpdb->query ( "DELETE FROM `" . $wpdb->base_prefix . self::META_TASK_TABLE_NAME . "` WHERE task_id = $task_id AND meta_key='group'" );
			
			} else {
				//TODO: Catch Error
				//die ( "Konnte nicht aus " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " oder " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " gel&ouml;scht werden" );
				die( sprintf(
					__('Konnte nicht aus %1$s oder %2$s gel&ouml;scht werden', 'bp_learning_diary'), 
					$wpdb->base_prefix . self::USER_TASK_TABLE_NAME, $wpdb->base_prefix . self::USER_TASK_TABLE_NAME   
					));
			}
			
			if ($result && is_array ( $the_users )) {
				
				foreach ( $the_users as $the_user => $allow ) {
					
					//Check if user allready answered the task
					//If he did: continue without inserting new row
					

					if (in_array ( $the_user, $user_allready_answered ))
						continue;
					
					$data = array ("task_id" => $task_id, "user_id" => $the_user );
					
					$wpdb->insert ( $wpdb->base_prefix . self::USER_TASK_TABLE_NAME, $data );
				
				}
			}
			
			/*
			 * 
			 * Check if $count_answers == $count_users
			 * 
			 * if true: mark task as "solved"
			 * 
			 */
			
			$count_answers = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " WHERE task_id=" . $task_id . " AND (task_status='publish' OR task_status='trash')" );
			$count_users = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " WHERE task_id=$task_id " );
			
			if ($count_answers == $count_users && $count_users > 0)
				$wpdb->update ( $wpdb->base_prefix . self::TASK_TABLE_NAME, array ('task_status' => 'solved' ), array ('ID' => $task_id ) );
			
		//	if($_POST["learning-diary-tasks-visible-for"]=="specific_groups"){
			

			/*
				 * Insert Group Id into META_TASK_TABLE_NAME
				 */
			
			foreach ( $_POST ["learning-diary-tasks-user-post-access-setting"] as $the_group_id => $the_group ) {
				$data = array ("task_id" => $task_id, "meta_key" => "group", "meta_value" => $the_group_id );
				$wpdb->insert ( $wpdb->base_prefix . self::META_TASK_TABLE_NAME, $data );
			}
		
		//	}
		

		} else {
			
			//insert new Task
			

			global $current_user;
			
			get_currentuserinfo ();
			
			$result = $wpdb->insert ( $wpdb->base_prefix . self::TASK_TABLE_NAME, $data );
			
			if ($result) {
				$task_id = $wpdb->insert_id;
				$_REQUEST ['id'] = $task_id;
			}
			
			if ($result && is_array ( $the_users )) {
				
				$blog_details = get_blog_details ( 1 );
				
				foreach ( $the_users as $the_user => $allow ) {
					$data = array ("task_id" => $task_id, "user_id" => $the_user );
					$wpdb->insert ( $wpdb->base_prefix . self::USER_TASK_TABLE_NAME, $data );
					
					//send email notification to users who set this option
					$send_email = get_user_meta ( $the_user, 'ld_send_email_on_new_task', true );
					
					if ($send_email) {
						
						$userdata = get_userdata ( $the_user );
						$to = $userdata->user_email;
						$subject = __("Neue Aufgabe auf lerntagebuch.ch", 'bp_learning_diary');
						//$message = "Hallo " . $userdata->display_name . "\n\n" . "Du hast eine neue Aufgabe erhalten. \n\n" . "Um die Aufgabe zu beantworten musst du dich anmelden unter \n\n" . $blog_details->siteurl . "\n\n" . "Viel Erfolg! \n\n" . $current_user->display_name;
						$message = sprintf(
							__('Hallo %1$s \n\n'
								. 'Du hast eine neue Aufgabe erhalten. \n\n' 
								. 'Um die Aufgabe zu beantworten musst du dich anmelden unter \n\n'
								. '%2$s \n\n'
								. 'Viel Erfolg! \n\n'
								. '%3$s', 
								'bp_learning_diary'
							),
							$userdata->display_name,
							$blog_details->siteurl,
							$current_user->display_name
						);
						wp_mail ( $to, $subject, $message );
					}
				
				}
			}
			
			//save group
			//	if($_POST["learning-diary-tasks-visible-for"]=="specific_groups"){
			foreach ( $_POST ["learning-diary-tasks-user-post-access-setting"] as $the_group_id => $the_group ) {
				$data = array ("task_id" => $task_id, "meta_key" => "group", "meta_value" => $the_group_id );
				$wpdb->insert ( $wpdb->base_prefix . self::META_TASK_TABLE_NAME, $data );
			}
		
		//	}
		}
		//TODO: catch error 
		

		if (! $result) {
			print_r ( "failed to update or to insert a task" );
		}
		
		/*
		 * insert/update custom answer format (NULL if text only)
		 */
		
		if ($task_id and ! $count_answers) {
			include_once ('learning-diary-tasks-answer-format.php');
			
			$answer_format_ques = wp_kses ( $_POST ['answer_format_ques'] );
			$answer_format_format = $_POST ['answer_format_format'];
			$answer_format_value = $_POST [$answer_format_format];
			
			//var_dump($answer_format_value); die();
			

			$AnswerFormat = new LearningDiaryTaskAnswerFormat ( array ('ques' => $answer_format_ques, 'format' => $answer_format_format, 'value' => $answer_format_value ) );
			
			//var_dump($AnswerFormat);die();
			

			$AnswerFormat->update_task_meta ( $task_id, self::META_TASK_TABLE_NAME );
		
		}
		
		return;
	
	} //end handle_new_task()
	

	private function move_task($id = 0) {
		global $wpdb;
		
		//sanitize data					
		$wpdb->update ( $wpdb->base_prefix . self::TASK_TABLE_NAME, array ('task_status' => 'solved' ), array ('id' => $id ) );
	}
	
	private function trash_task($id = 0) {
		global $wpdb;
		global $current_user;
		
		//Check nonce field	
		if (! wp_verify_nonce ( $_GET ["_wpnonce"], 'bp_deletenonce' ))
			die ( "Nonce" );
		
		//sanitize data					
		$wpdb->update ( $wpdb->base_prefix . self::TASK_TABLE_NAME, array ('task_status' => 'trash' ), array ('id' => $id, 'post_author' => $current_user->ID ) );
	}
	
	private function restore_task($id = 0) {
		global $wpdb;
		global $current_user;
		
		if (! wp_verify_nonce ( $_GET ["_wpnonce"], 'bp_deletenonce' ))
			die ( "Nonce" );
		
		//sanitize data
		

		$wpdb->update ( $wpdb->base_prefix . self::TASK_TABLE_NAME, array ('task_status' => 'publish' ), array ('id' => $id, 'post_author' => $current_user->ID ) );
	}
	
	private function delete_task_permanently($id = 0) {
		
		//TODO: Check if there are users who have answered the task
		

		global $wpdb;
		global $current_user;
		
		if (! wp_verify_nonce ( $_GET ["_wpnonce"], 'bp_deletenonce' ))
			die ( "Nonce" );
		
		//sanitize data
		

		//TODO: Also Delete Data from Table ".$wpdb->base_prefix."learning_diary_tasks_meta
		$result = $wpdb->query ( "
			DELETE FROM  `" . $wpdb->base_prefix . self::TASK_TABLE_NAME . "`  WHERE ID = $id AND post_author = $current_user->ID AND task_status = 'trash'" );
		
		if ($result) {
			$result = $wpdb->query ( "
				DELETE FROM  `" . $wpdb->base_prefix . self::META_TASK_TABLE_NAME . "` WHERE task_id = $id" );
			$result = $wpdb->query ( "
				DELETE FROM  `" . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . "` WHERE task_id = $id" );
		} else {
			//die ( "Konnte nicht aus " . $wpdb->base_prefix . self::META_TASK_TABLE_NAME . " oder " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " gel�scht werden" );
			die ( sprintf(
				__("Konnte nicht aus %1$s oder %2$s gel&ouml;scht werden", 'bp_learning_diary'),
				$wpdb->base_prefix . self::META_TASK_TABLE_NAME,
				$wpdb->base_prefix . self::USER_TASK_TABLE_NAME
			));
		}
	
	}
	
	private function create_post_from_task() {
		
		global $current_blog;
		global $current_user;
		global $wpdb;
		global $_POST;
		
		//if post is created there will be an entry in the postmeta table of the specific blog
		$my_post = array ();
		
		$task_id = $_POST ["task_id"];
		
		if ($task_id)
			$task_owner = $this->get_task_owner ( $task_id );
		else
			return false;
		
		$task = $this->get_task ( $task_id, $task_owner );
		
		$my_post ["post_title"] = $task->post_title;
		$my_post ["post_content"] = " ";
		
		//insert Post
		$post_id = wp_insert_post ( $my_post );
		
		switch ($task->visibility_of_solution) {
			//everybody has access
			case "all" :
				add_post_meta ( $post_id, '_bpac_visible_for', 'all' );
				//map task to specific group, if any
				foreach ( $task->meta as $meta ) {
					//Check if task was sent to one or more groups
					if ($meta->meta_key == "group") {
						//add groups into table post_meta
						add_post_meta ( $post_id, '_bp_ld_mapto_groups', $meta->meta_value );
					}
				}
				
				break;
			
			case "loggedin_users" :
				//all logged in users will have access
				add_post_meta ( $post_id, '_bpac_visible_for', 'loggedin_users' );
				break;
			
			//only students, who got the same task have access
			case "many" :
				//add Author of Task
				add_post_meta ( $post_id, '_bpac_user_has_access', $task->post_author );
				//add _bpac_visible_for
				add_post_meta ( $post_id, '_bpac_visible_for', 'specific_users' );
				
				foreach ( $task->meta as $meta ) {
					//Check if task was sent to one or more groups
					if ($meta->meta_key == "group") {
						//add groups into table post_meta
						add_post_meta ( $post_id, '_bpac_users_of_group_have_access', $meta->meta_value );
						update_post_meta ( $post_id, '_bpac_visible_for', 'specific_groups' );
						//add groups into table post_meta
						add_post_meta ( $post_id, '_bp_ld_mapto_groups', $meta->meta_value );
					}
				}
				
				foreach ( $task->users as $user ) {
					//add users for getting access
					add_post_meta ( $post_id, '_bpac_user_has_access', $user->user_id );
				}
				
				break;
			
			//only  teacher and $current_user has access
			case "two" :
				add_post_meta ( $post_id, '_bpac_visible_for', 'specific_users' );
				add_post_meta ( $post_id, '_bpac_user_has_access', $task->post_author );
				//get sender and $current_user
				

				//map task to specific group, if any
				foreach ( $task->meta as $meta ) {
					//Check if task was sent to one or more groups
					if ($meta->meta_key == "group") {
						//add groups into table post_meta
						add_post_meta ( $post_id, '_bp_ld_mapto_groups', $meta->meta_value );
					}
				}
				break;
		}
		
		$blog_id = $current_blog->blog_id;
		
		//die if post id or blog id is false
		//TODO: Error Catching
		if (! $post_id || ! $blog_id)
			die ( __("Blog ID oder Post ID fehlt", 'bp_learning_diary') );
		
		update_post_meta ( $post_id, '_task_id', $task_id );
		
		/*
		 * add tags=group's names to post
		 */
		
		//get group ids added above to post meta (if any)
		$mapto_ids = get_post_meta ( $post_id, '_bp_ld_mapto_groups' );
		
		foreach ( $mapto_ids as $group_id ) {
			$group = new BP_Groups_Group ( $group_id );
			//set tag only for groups the user belongs to (for security reasons)
			if (bp_group_is_member ( $group )) {
				wp_set_post_tags ( $post_id, $group->name, true );
			}
		}
		
		$url = get_option ( 'siteurl' ) . "/wp-admin/post.php?action=edit&post=$post_id";
		
		//update blog_id and post_id in User Task Table Name
		$wpdb->update ( $wpdb->base_prefix . self::USER_TASK_TABLE_NAME, array ('blog_id' => $blog_id, 'post_id' => $post_id, 'task_status' => "draft" ), array ('task_id' => $task_id, 'user_id' => $current_user->ID ) );
		
		//REDIRECT TO EDIT PAGE
		if (! headers_sent ()) {
			wp_redirect ( $url, 302 );
			exit ();
		} else {
			?>
			<script type="text/javascript">
       			window.location.href="<?php echo $url?>";
        	</script>
			<noscript>
				<meta http-equiv="refresh" content="0;url='<?php echo $url?>'" />
			</noscript>
			<?php
			exit ();
		}
		
		return;
	}
	
	/*
	 * Show message if user has no group membership or group has no students
	 */
	
	private function show_no_group_membership_message($group = false) { ?>
		<p><?php _e('Um Aufgaben erstellen und bearbeiten zu k&ouml;nnen, musst du mindestens einer Gruppe angeh&ouml;ren.
			In dieser Gruppe m&uuml;ssen zudem Lernende enthalten sein.', 'bp_learning_diary'); ?>
		<ul>
			<?php if (! $group) : ?>
				<li><?php printf(
					__('Eine %1$s neue Gruppe erstellen%2$s oder einer %3$s Gruppe beitreten%4$s', 'bp_learning_diary'),
					'<a href="' . get_current_site(1)->url . get_current_site(1)->path . 'groups/create">',
					'</a>',
					'<a href="' . get_current_site(1)->url . get_current_site(1)->path . 'groups/">',
					'</a>.'
				);?>
				</li>
			<?php else : ?>
				<li><?php printf(
					__('%1$s Eine/n Lernende/n registrieren%2$s oder %3$s mehrere Lernende registrieren%4$s', 'bp_learning_diary'),
					'<a href="' . $current_site->url . $current_site->path . 'wp-admin/admin.php?page=import-user">',
					'</a>',
					'<a href="' . $current_site->url . $current_site->path . 'wp-admin/admin.php?page=bulk-user-import">',
					'</a>.'
				);?>
				</li>
			<?php endif;?>
		</ul>
		</p>
	<?php
	}
	
	/*
	 * check if given groups has at least one member that is a student
	 */
	
	private function group_has_students($groups) {
		global $current_user;
		
		get_currentuserinfo ();
		
		$group_has_pupils = false;
		
		foreach ( $groups ['groups'] as $the_group ) {
			$group = new BP_Groups_Group ( $the_group );
			
			$group_members = BP_Groups_Member::get_all_for_group ( $the_group, 0, 100, false, true );
			
			foreach ( $group_members ["members"] as $the_group_member ) {
				if (get_usermeta ( $the_group_member->user_id, 'learning_diary_tasks_student' ) && $the_group_member->user_id != $current_user->ID) {
					return true;
				}
			}
		}
	}
	
	private function edit_task($errors) {
		global $wpdb;
		global $temp_ID;
		global $current_user;
		
		$task_id = false;
		$new_task = false;
		
		/*
		 * Check if user is part of a group
		 */
		
		$groups = groups_get_user_groups ( $current_user->ID, 0, 100 );
		
		if (count ( $groups ['groups'] ) == 0) { ?>
			<div class="wrap" id="editnewtask">
				<h2><?php _e('Neue Aufgabe erstellen', 'bp_learning_diary')?></h2>
				<?php $this->show_no_group_membership_message ()?>
			</div>
			<?php
			return;
		}
		
		/*
		 * Check if groups have student pupils
		 */
		
		$group_has_pupils = $this->group_has_students ( $groups );
		
		if (! $group_has_pupils) { ?>
			<div class="wrap" id="editnewtask">
				<h2><?php _e('Neue Aufgabe erstellen', 'bp_learning_diary')?></h2>
				<?php $this->show_no_group_membership_message ( true )?>
			</div>
			<?php
			return;
		}
		
		if ($_REQUEST ["id"]) {
			$task_id = $_REQUEST ["id"];
			$task = $wpdb->get_row ( "SELECT * FROM `" . $wpdb->base_prefix . self::TASK_TABLE_NAME . "` WHERE ID=$task_id" );
		} else {
			//task is a new task
			$new_task = true;
			$task_id = false;
		}
		
		/*
		 * create new $AnserFormat object and get custom answer format data from db (if any)
		 */
		
		include_once ('learning-diary-tasks-answer-format.php');
		
		$answer_format_and_value = LearningDiaryTaskAnswerFormat::get_task_meta ( $task_id, self::META_TASK_TABLE_NAME );
		
		$AnswerFormat = new LearningDiaryTaskAnswerFormat ( $answer_format_and_value );
		
		//keep data, if there is an error while creating/editing a task
		if ($_REQUEST ["action"] == "edittaskerror") {
			$task->publish_date = $_POST ["publish_date"];
			$task->review_date = $_POST ["review_date"];
			$task->visibility_of_solution = $_POST ["visibility_of_solution"];
			$task->post_title = $_POST ["post_title"];
			$task->post_content = $_POST ["content"];
			
			$AnswerFormat->ques = wp_kses ( $_POST ['answer_format_ques'] );
			$AnswerFormat->format = $_POST ['answer_format_format'];
			$AnswerFormat->value = is_array ( $_POST [$AnswerFormat->format] ) ? $_POST [$AnswerFormat->format] : array ($_POST [$AnswerFormat->format] );
		}
		
		/*
		 * create nonce
		 */
		
		$nonce = wp_create_nonce ( 'add-learning-diary-entry' );
		
		/*
		 * alert user before editing task that have been answered
		 */
		
		if ($task_id) {
			
			//$count_answers = $wpdb->get_var("SELECT count(*) FROM " . $wpdb->base_prefix.self::USER_TASK_TABLE_NAME . " WHERE task_id=" . $task_id . " AND (task_status='publish' OR task_status='trash')");
			$count_answers = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " WHERE task_id=" . $task_id . " AND (task_status='publish' OR task_status='trash' OR task_status='solved')" );
			
			if ($count_answers) {
				$errors ["has_answers"] = sprintf ( __ngettext( 
					'Diese Aufgabe wurde bereits durch einen Lernenden beantwortet', 
					'Diese Aufgabe wurde bereits durch %1$s Lernende beantwortet.', 
					$count_answers,
					'bp_learning_diary' 
					), 
					$count_answers 
				); 

				$errors ["has_answers"] .= '<br/><br/>' . __( 
					'Bei &Auml;nderungen wird dies von jenen Lernenden nicht wahrgenommen, die die Aufgabe bereits beantwortet haben.',
					'bp_learning_diary'
				);
				
				$errors ["has_answers"] .= '<br/><br/>' . __(
					'Das Antwortformat kann nicht mehr ge&auml;ndert werden, falls deine Frage bereits von Lernenden beantwortet wurde.',
					'bp_learning_diary'
				);
			}
			
			$count_users = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " WHERE task_id=$task_id " );
			
			//	if($task->task_status=="solved" || ($count_answers==$count_users && $count_users>0) )
			if (($count_answers == $count_users && $count_users > 0))
				return;
		}
		
		?>
		<div class="wrap" id="editnewtask">
			<?php if ($task_id) { ?>
				<h2><?php _e('Aufgabe bearbeiten', 'bp_learning_diary')?> 
					<a href="admin.php?page=<?php echo self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME;?>&task_status=all" class="button add-new-h2">
						<?php _e ( "&laquo; Back" ); ?>
					</a>
				</h2>
			<?php } else { ?>
				<h2><?php _e('Neue Aufgabe erstellen', 'bp_learning_diary')?> 
					<a href="admin.php?page=<?php echo self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME; ?>&task_status=all" class="button add-new-h2">
					<?php _e ( "&laquo; Back" ); ?>
					</a>
				</h2>
			<?php } ?>
			
			<?php
			if ((! $errors || $errors ["has_answers"]) && $_REQUEST ["save_or_publish"]) :
			?>
				<div id="message" class="updated below-h2">
				<?php
				if ($_REQUEST ["save_or_publish"] == 'save') :
				?>
					<p><?php _e('Die Aufgabe wurde als Entwurf gespeichert.', 'bp_learning_diary')?></p>
				<?php endif;
			?>
				<?php
				if ($_REQUEST ["save_or_publish"] == 'publish') :
				?>
					<p><?php _e('Die Aufgabe wurde publiziert.', 'bp_learning_diary')?></p>
				
			<?php endif;
			?>
				</div>
			
		<?php endif;
		?>
		
			
		<form name="post"
			action="admin.php?page=<?php echo self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME?>"
			method="post" class="quick-press">

		<div id="poststuff" class="metabox-holder has-right-sidebar">
			
			<?php $this->display_errors ( $errors, "has_answers" ); ?>
			
			<div id="side-info-column" class="inner-sidebar">
			<div id="side-sortables">
			<?php $this->display_errors ( $errors, "publish_date" ); ?>
			<div id="visiblefrom" class="postbox ">
			<h3 class="hndle"><label for="date-input"><?php _e( 'Sichtbar ab', 'bp_learning_diary' )?></label></h3>
			<div class="inside">
			<div id="learning_diary_task_publish_immediately"
				class="<?php if (strtotime ( $task->publish_date ) > (time ())) echo 'hidden'; ?>"
				>
				<?php _e('Sofort', 'bp_learning_diary')?> (<a href="#"><?php _e ( "Edit" )?></a>)
			</div>

			<div id="learning_diary_task_publish_date"
				class="<?php if (strtotime ( $task->publish_date ) <= (time ()) || ($new_task && ! $errors)) echo 'hidden';?>"
				>
				<?php $this->learning_diary_tasks_date_time ( 1, 3, 1, $task->publish_date, "<br />" );?>
				<input type="hidden"
						name="learning_diary_task_publish_immediately_input"
						id="learning_diary_task_publish_immediately_input"
						value="<?php if (strtotime ( $task->publish_date ) < time ()) {
								echo 'none';
							} else {
								echo 'specific';
							} ?>" 
				/>
			<br />
			<a href="#" id="learning_diary_task_publish_immediately_toggle"><?php _e( "&auml;ndern zu sofort", 'bp_learning_diary' )?></a>
			</div>
			</div>
			</div>
			<?php $this->display_errors ( $errors, "review_date" );?>
			<?php $this->display_errors ( $errors, "review_date_to_late" ); ?>
			<div id="reviewdate" class="postbox ">
				<h3 class="hndle"><label for="date-input"><?php _e( 'Abzugeben bis', 'bp_learning_diary' )?></label></h3>
				<div class="inside">
				<div id="learning_diary_task_review_no_date"
					<?php if ($task->review_date != '0000-00-00 00:00:00' && ! $new_task) echo 'class="hidden"';?>
					>
					<?php _e('Offen', 'bp_learning_diary')?> (<a href="#"><?php _e ( "Edit" )?></a>)
				</div>
				<div id="learning_diary_task_review_date"
					<?php if ($task->review_date == '0000-00-00 00:00:00' || $new_task) echo 'class="hidden"';?>>
					<?php $this->learning_diary_tasks_date_time ( 1, 4, 1, $task->review_date, "<br />" );?>
					<input type="hidden"
						name="learning_diary_task_review_date_input"
						id="learning_diary_task_review_date_input"
						value="<?php if ($task->review_date == '0000-00-00 00:00:00' || $new_task) {
									echo 'none';
								} else {
									echo 'specific';
								}?>" 
					/>
					<br />
					<a href="#" id="learning_diary_task_review_date_toggle"><?php _e( "&auml;ndern zu Offen", 'bp_learning_diary' )?></a>
				</div>
				</div>
			</div>
						
			<?php $this->display_errors ( $errors, "visibility_of_solution" );?>
						
			<div id="visiblefor" class="postbox ">
				<h3><label for="date-input"><?php _e( 'Sichtbarkeit der Antworten', 'bp_learning_diary' )?></label></h3>
				<div class="inside">
				<p><?php _e('Kann von den antwortenden Lernenden ge&auml;ndert werden.', 'bp_learning_diary')?></p>
				<hr>
				<ul>
					<li>
					<input type="radio" name="visibility_of_solution"
							id="visibility_of_solution" value="two"
							<?php if ($task->visibility_of_solution == "two") echo "checked";?> 
					/>
					<div class="label">
						<label for="visibility_of_solution">
							<?php _e(
								'Antworten auf diese Aufgabe sind nur f&uuml;r mich und <strong>den antwortenden Lernenden</strong> sichtbar.', 
								'bp_learning_diary'
							)?>
						</label>
					</div>
					</li>
					<li><input type="radio" name="visibility_of_solution"
								id="visibility_of_solution" value="many"
								<?php if ($task->visibility_of_solution == "many") echo "checked";?> 
						/>
						<div class="label">
							<label for="visibility_of_solution">
								<?php _e(
									'Antworten auf diese Aufgabe sind f&uuml;r mich und <strong>alle antwortenden Lernenden</strong> sichtbar.',
									'bp_learning_diary'
								)?>
							</label>
						</div>
					</li>
					<?php
					if (get_blog_option ( 1, 'BP_Post_Access_Control_loggedin_users_option_for_visibility' )) {
					//Show option to choose loggedin users only if super admin has activated the option (only superadmin can edit this option)
					?>
					<li><input type="radio" name="visibility_of_solution"
								id="visibility_of_solution" value="loggedin_users"
								<?php if ($task->visibility_of_solution == "loggedin_users") echo "checked";?> 
						/>
					<div class="label">
						<label for="visibility_of_solution">
							<?php _e(
								'Antworten auf diese Aufgabe sind f&uuml;r <strong>alle angemeldeten Nutzer</strong> sichtbar.',
									'bp_learning_diary'
							)?>
						</label>
					</div>
					</li>
					<?php
					}
					?>
					<li><input type="radio" name="visibility_of_solution"
								id="visibility_of_solution" value="all"
								<?php if ($task->visibility_of_solution == "all") echo "checked";?> 
						/>
					<div class="label">
						<label for="visibility_of_solution">
							<?php _e('Antworten sind <strong>vollst&auml;ndig &ouml;ffentlich</strong>.', 'bp_learning_diary')?>
						</label>
					</div>
					</li>
				</ul>
				</div>
			</div>
			<?php $this->display_errors ( $errors, "choose_users" );?>
			<div id="chooseuser" class="postbox ">
			<h3><label for="date-input"><?php _e( 'Sichtbarkeit der Aufgabe', 'bp_learning_diary' )?>
				<img class="hidden" id="ajax-loader-img" src="<?php echo BP_PLUGIN_URL . '/bp-themes/bp-default/_inc/images/ajax-loader.gif';?>">
				</label>
			</h3>

			<div class="inside">

			<div class="textarea-wrap bp-learning-diary-wrap">
			<?php
		
			$post_data = array ();
			$post_data ["sent_to"] = $_POST ["learning-diary-tasks-visible-for"];
			$post_data ["checkedgroups"] = $_POST ["learning-diary-tasks-user-post-access-setting"];
			$post_data ["checkedusers"] ["specific_groups"] = $_POST ["learning-diary-tasks-user-post-access-setting-specific-groups"];
			$post_data ["checkedusers"] ["specific_users"] = $_POST ["learning-diary-tasks-user-post-access-setting-specific-users"];
		
			/*
  			* Check if form has been sent
  			*/
		
			//if(isset($_POST["learning-diary-tasks-visible-for"])){
			if (isset ( $_POST ["save_or_publish"] )) {
				//tell function that form has been sent
				$this->choose_users_for_task ( 0, $post_data );
			} else {
				//tell function that it should get data from database and not from the form
				$this->choose_users_for_task ( $task->ID, $post_data );
			}
			?>
		</div>
	</div>
	</div>
	</div>
	</div>
	</div>

	<div id="post-body" class="metabox-holder has-right-sidebar">
	<div id="post-body-content">
		<?php $this->display_errors ( $errors, "post_title" ); ?>
		<div id="namediv" class="stuffbox">
			<h3><label for="content"><?php _e( 'Titel der Aufgabe', 'bp_learning_diary' )?></label></h3>
			<div class="inside">
			<div class="input-text-wrap"><input type="text" name="post_title"
				id="title" tabindex="1" autocomplete="off"
				value="<?php echo esc_attr ( $task->post_title );?>" />
			</div>
			</div>
		</div>
		<?php $this->display_errors ( $errors, "content" ); ?>
		<div id="namediv" class="stuffbox">
		<h3><label for="content"><?php _e('Beschreibung der Aufgabe', 'bp_learning_diary' )?></label></h3>
		<div>
		<div class="textarea-wrap">
		<div id="poststuff" class="postarea">
		<?php $temp_ID = 1;?>
		<?php
		the_editor ( htmlspecialchars_decode ( html_entity_decode ( stripslashes ( $task->post_content ) ) ), 'content', 'post_title', 1, 2 );
		?>
		</div>
		</div>
		</div>
		</div>

<!-- pseudo meta box for custom answer format -->
		<?php $this->display_errors ( $errors, "answer_format_ques" );?>
		<div class="stuffbox">
		<h3><?php _e('Antwortformat', 'bp_learning_diary')?></h3>
		<div class="inside" id="answer_format_div">
		<select name="answer_format_select" id="answer_format_select" size="1"
			<?php echo $count_answers ? 'disabled="disabled"' : ""?>
			>
			<?php echo $AnswerFormat->show_dropdown_list ();?>
		</select>
		<div id="answer_format_preselect" name="answer_format_preselect">
			<?php echo $AnswerFormat->show ( true, false, $count_answers );?>	
		</div>
		</div>
		</div>

		<p class="submit">
		<?php if ($task_id) { ?>
			<input type="hidden" name="task_id" id="save_task_id" value="<?php echo $task_id;?>" />
		<?php } ?>
		<?php if ($task_id) { ?>
			<input type="hidden" name="action" id="quickpost-action" value="task-edit" />
		<?php } else { ?>
			<input type="hidden" name="action" id="quickpost-action" value="task-new" />
		<?php } ?>
		
		<?php if ($task_id) { ?>
			<input type="hidden" name="id" id="id" value="<?php echo $task_id;?>" />
		<?php } ?>
		
		<input type="hidden" name="save_or_publish" id="save_or_publish" value="save" />
		<?php wp_nonce_field ( 'add-learning-diary-entry' ); ?>
		<?php
		/*
		 * Hide "Save as Draft" if users exist, that allready answered the task. 
		*/
		if (! $wpdb->get_var ( "SELECT user_id FROM `" . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . "` WHERE task_id = $task_id AND task_status<>''" )) {
			?>
			<input type="submit" name="save" id="save-post" class="button" tabindex="9" value="<?php esc_attr_e ( 'Save as Draft' );?>" />  
		<?php } ?>
		<?php if (current_user_can ( 'publish_posts' )) { ?>
			<input type="submit" name="publish" id="publish" accesskey="p" tabindex="10" class="button-primary"
				value="<?php esc_attr_e ( 'Publish' );?>" 
			/>
		<?php } else { ?>
			<input type="submit" name="publish" id="publish" accesskey="p" tabindex="10" class="button-primary"
				value="<?php esc_attr_e ( 'Submit for Review' );?>" 
			/>
		<?php } ?>
		
		<br class="clear" />
		</p>
		</div>
		</div>

		</form>
		</div>

	<?php } //end function edit_task()
	
	public function add_new_task() {
		$_REQUEST ["action"] = "newtask";
		$this->learning_diary_tasks_edit_page ();
	}
	
	public function learning_diary_tasks_edit_page() {
		
		global $wpdb;
		global $current_user;
		
		$errors = array ();
		
		switch ($_REQUEST ["action"]) {
			
			/*
		 * Action: Publish or Save Learning Diary
		 */
			
			case 'post-quickpress-save' :
			case 'post-quickpress-publish' :
			case 'task-new' :
			case 'task-edit' :
				$errors = $this->handle_new_task ();
				
				if ($errors)
					$_REQUEST ["action"] = "edittaskerror";
				
				break;
			
			/*
		 * Action: Move Learning Diary Task to Solved
		 */
			
			case "movetask" :
				$id = absint ( $_GET ["id"] );
				$this->move_task ( $id );
				break;
			
			/*
		 * Action: Trash Learning Diary Task
		 */
			
			case "trash" :
				$id = absint ( $_GET ["id"] );
				$this->trash_task ( $id );
				break;
			
			/*
		 * Action: Restore Learning Diary Task 
		 */
			
			case "restore" :
				$id = absint ( $_GET ["id"] );
				$this->restore_task ( $id );
				break;
			
			/*
		 * Action: Delete Learning Diary Task Permanently
		 */
			
			case "deletepermanently" :
				$id = absint ( $_GET ["id"] );
				$this->delete_task_permanently ( $id );
				break;
		
		}
		
		switch ($_REQUEST ["action"]) {
			
			/*
		 * Action: Show Answers for a Task
		 */
			
			case "showanswers" :
				
				$task_id = $_GET ["id"];
				
				if ($task_id)
					$this->show_answers ( $task_id );
				
				break;
			
			/*
		 * Action: Edit Learning Diary Task
		 */
			case "task-edit" :
				$this->edit_task ( $errors );
				break;
			case "task-new" :
				$this->edit_task ( $errors );
				break;
			case "newtask" :
			case "edittask" :
			case "edittaskerror" :
				
				$this->edit_task ( $errors );
				
				break;
			/*
		 * DEFAULT: Show Tasks Overview 
		 */
			
			default :
				
				?>

				<div class="wrap" style="position: relative;">
		
				<?php
				
				if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true ))
					$this->get_tasks_of_teacher ();
				
				?>
		
				</div>

				<?php
		}
	}
	
	public function learning_diary_tasks_edit_page_student() {
		
		global $wpdb;
		global $current_user;
		
		$errors = array ();
		
		switch ($_REQUEST ["action"]) {
			
			/*
		 * Action: Create Post From Task
		 */
			
			case "createpost" :
				$this->create_post_from_task ();
				break;
			
			default :
				
				?>

				<div class="wrap" style="position: relative;">
		
				<?php
				
				if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_student', true ))
					$this->get_recent_tasks_for_user ();
				
				?>
		
				</div>

	<?php
		}
	}
	
	private function get_tasks_of_teacher() {
		global $wpdb;
		global $current_user;
		
		$current_site = get_current_site ( 1 );
		
		/*
		 * Pagination
		 */
		
		if ($_GET ["apage"])
			$current = intval ( $_GET ["apage"] );
		else
			$current = 1;
		
		$tasks_per_page = 10;
		$limit = $tasks_per_page;
		$start = $current * $tasks_per_page - $tasks_per_page;
		
		/*
		 * Query
		 */
		
		$show_tasks_of_group = $_GET ["show_tasks_of_group"];
		
		//Check if the user wants to see tasks of a specific group
		if ($show_tasks_of_group) {
			$query = "SELECT * FROM ";
			$query_talbe = $wpdb->base_prefix . self::TASK_TABLE_NAME . ", " . $wpdb->base_prefix . self::META_TASK_TABLE_NAME . " 
				   WHERE ID=task_id AND meta_key='group' 
				   AND meta_value=$show_tasks_of_group 
				   AND post_author=$current_user->ID ";
			$query = $query . $query_talbe;
		} else {
			$query = "SELECT * FROM ";
			$query_talbe = $wpdb->base_prefix . self::TASK_TABLE_NAME . "
					 WHERE post_author=$current_user->ID ";
			$query = $query . $query_talbe;
		}
		
		if (! $_GET ['task_status'])
			$_GET ['task_status'] = "publish";
		
		if ($_GET ['task_status'] && $_GET ['task_status'] != "all")
			$query .= "AND task_status='" . $_GET ['task_status'] . "' ";
		
		if ($_GET ['task_status'] == "all")
			$query .= "AND task_status <> 'trash' ";
		
		//Group By
		if ($show_tasks_of_group)
			$query .= "GROUP BY ID ";
		
		//Sorting
		if (isset ( $_GET ['sortby'] ) == false) {
			$query .= ' ORDER BY publish_date ';
		} else if ($_GET ['sortby'] == 'title') {
			$query .= ' ORDER BY post_title ';
		} else if ($_GET ['sortby'] == 'publish_date') {
			$query .= ' ORDER BY publish_date ';
		} else if ($_GET ['sortby'] == 'review_date') {
			$query .= ' ORDER BY review_date ';
		}
		
		$query .= ($_GET ['order'] == 'DESC') ? 'ASC' : 'DESC';
		
		$query .= " LIMIT " . $start . ", " . $limit;
		
		/*
		 * Get learning diary task list
		 */
		
		$learning_diary_list = $wpdb->get_results ( $query, ARRAY_A );
		
		/*
		 * Count Entries
		 */
		
		$count_entries = array ("all" => 0, "publish" => 0, "open" => 0, "solved" => 0, "drafts" => 0, "trash" => 0, "total" => 0 );
		
		$count_entries ["publish"] = $wpdb->get_var ( "SELECT count(DISTINCT ID) FROM $query_talbe AND task_status='publish'" );
		$count_entries ["trash"] = $wpdb->get_var ( "SELECT count(DISTINCT ID) FROM $query_talbe AND task_status='trash'" );
		$count_entries ["drafts"] = $wpdb->get_var ( "SELECT count(DISTINCT ID) FROM $query_talbe AND task_status='drafts'" );
		$count_entries ["solved"] = $wpdb->get_var ( "SELECT count(DISTINCT ID) FROM $query_talbe AND task_status='solved'" );
		$count_entries ["total"] = $wpdb->get_var ( "SELECT count(DISTINCT ID) FROM $query_talbe" );
		$count_entries ["all"] = $count_entries ["total"] - $count_entries ["trash"];
		
		$class = empty ( $class ) && empty ( $_GET ['task_status'] ) ? ' class="current"' : '';
		
		$entries = array(
			"all" => __( "All" ), 
			"publish" => __( "Ungelöst", 'bp_learning_diary' ), 
			"solved" => __( "Gelöst", 'bp_learning_diary' ), 
			"drafts" => __( "Drafts" ), 
			"trash" => __ ( "Trash" ) 
		);
		
		if (! $_GET ['task_status'])
			$task_status = "all";
		else
			$task_status = $_GET ['task_status'];
			
		/*
		 * Pagination
		 */
		
		$count_tasks = $count_entries [$task_status];
		
		$total = (ceil ( $count_tasks / $tasks_per_page ));
		
		$page_links = paginate_links ( array(
			'base' => add_query_arg ( array('apage' => '%#%', 'action' => '', 'id' => '' ) ), 
			'format' => '', 
			'prev_text' => __ ( '&laquo;' ), 
			'next_text' => __ ( '&raquo;' ), 
			'total' => $total, 
			'current' => $current 
		));
		
		/*
		 * List task entries
		 */
		
		$groups = groups_get_user_groups ( $current_user->ID, 0, 100 );
		
		//NEW: $group and $lernender in group
		//EDIT: $group 
		

		/*
		 * Base URL
		 */
		$base_url = "admin.php?page=" . self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME . "&";
		(! $_GET ["task_status"]) ? $task_status_url = "" : $task_status_url = "task_status=" . $_GET ["task_status"] . "&";
		(! $_GET ["show_tasks_of_group"]) ? $show_tasks_of_group_url = "" : $show_tasks_of_group_url = "show_tasks_of_group=" . $_GET ["show_tasks_of_group"] . "&";
		
		?>
		
		<?php if ($groups ["total"]) { ?>
			<div id="show_tasks_of_group_div">
				<label for="show_tasks_of_group">
					<?php _e('Nur Aufgaben zeigen f&uuml;r Gruppe:', 'bp_learning_diary')?>
				</label>
				<select id="show_tasks_of_group" name="show_tasks_of_group">
					<option value="0"><?php _e('Alle zeigen', 'bp_learning_diary')?></option>
					<?php 
					foreach ( $groups ["groups"] as $the_group_id ) {
						$group = new BP_Groups_Group ( $the_group_id );
				
						$group_has_members = $this->group_has_students ( $groups );
				
						$selected = "";
				
						if ($group->id == $_GET ["show_tasks_of_group"])
							$selected = "selected";
						?>
						<option value="<?php echo $group->id;?>" 
							<?php echo $selected?>
							>
							<?php echo $group->name;?>
						</option>
					<?php } ?>
				</select>
			</div>
		<?php } ?>

		<h2><?php _e( 'Aufgaben verwalten', 'bp_learning_diary' )?>
			<?php if ($groups ["total"] && $group_has_members) { ?>
				<a href="admin.php?page=<?php echo self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME?>&action=newtask"
					class="button add-new-h2"><?php _e( 'Hinzufügen', 'bp_learning_diary' )?></a>
			<?php } ?>	
		</h2>

		<?php
		
		if ($groups ["total"] && $group_has_members) {
			if ($show_tasks_of_group && ! $count_entries ["all"]) {
				?>
				<p><?php _e('F&uuml;r diese Gruppe existieren keine Aufgaben. Erstelle mit &laquo;Hinzuf&uuml;gen&raquo; eine Aufgabe.', 'bp_learning_diary')?></p>
				<?php
			} else if (! $count_entries ["total"]) {
				?>
				<p><?php _e('Du hast noch keine Aufgaben erstellt. Erstelle mit &laquo;Hinzuf&uuml;gen&raquo; deine erste Aufgabe.', 'bp_learning_diary')?></p>
			<?php
			}
		} else {
			$this->show_no_group_membership_message ( count ( $groups ["groups"] ) );
		}
		
		?>

		<div class="tablenav">
		<ul class="subsubsub">
		<?php
		$learning_diary_temp_list = array ();
		
		foreach ( $learning_diary_list as $the_learning_diary_entry ) {
			
			//count users who received the task
			$count_users = 0;
			$count_users = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " WHERE task_id=" . $the_learning_diary_entry ['ID'] );
			$the_learning_diary_entry ["count_users"] = $count_users;
			
			//count users who answered the task
			$count_answers = 0;
			$count_answers = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " WHERE task_id=" . $the_learning_diary_entry ['ID'] . " AND (task_status='publish' OR task_status='trash')" );
			$the_learning_diary_entry ["count_answers"] = $count_answers;
			
			$learning_diary_temp_list [] = $the_learning_diary_entry;
		}
		
		$learning_diary_list = $learning_diary_temp_list;
		
		foreach ( $entries as $status => $name ) {
			$class = '';
			
			//	if ($count_entries[$status]==0)
			//		continue;

			if (isset ( $_GET ['task_status'] ) && $status == $_GET ['task_status']) {
				$class = ' class="current"';
			}
			
			$task_status_url = "task_status=$status&";
			
			$url = "$base_url$task_status_url$show_tasks_of_group_url";
			
			$status_links [] = "<li><a href='$url'$class>$name <span class='count'>($count_entries[$status])</span></a>";
		}
		
		if (is_array ( $status_links ) && $count_entries ["total"]) {
			echo implode ( " |</li>\n", $status_links ) . '</li>';
		}
		unset ( $status_links );
		?>
		</ul>
					
		<?php if ($page_links) { ?>
			<div class="tablenav-pages">
			<?php $page_links_text = sprintf ( 
				'<span class="displaying-num">' 
					. __ ( 'Displaying %s&#8211;%s of %s' ) 
					. '</span>%s', number_format_i18n ( ($current - 1) * $tasks_per_page + 1 ), 
				number_format_i18n ( min ( $current * $tasks_per_page, $count_tasks ) ), 
				number_format_i18n ( $count_tasks ), 
				$page_links 
			);
			echo $page_links_text;
			?>
			</div>
		<?php } ?>
						
		</div>
		<?php
		
		if ($count_entries ["total"]) {
			?>

		<form id="form-blog-list" action="wpmu-edit.php?action=allblogs" method="post">
			<br class="clear" />
		
			<?php
			
			// define the columns to display, the syntax is 'internal name' => 'display name'
			
			$posts_columns = array (
				//'id'           		=> __('ID'),
				'title' => __ ( 'Title' ), 
				'text' => __ ( 'Text' ), 
				//'users'				=> __('Users'),
				'answers' => __( 'Antworten', 'bp_learning_diary' ), 
				'publish_date' => __( 'Sichtbar ab', 'bp_learning_diary' ), 
				'review_date' => __( 'Abzugeben bis', 'bp_learning_diary' ) 
			)//'post_modified'   	=> __('Last Updated')
			;
			
			$deletenonce = wp_create_nonce ( 'bp_deletenonce' );
			
			?>
				
			<table width="100%" cellpadding="3" cellspacing="3" class="widefat" id="learning_diary_tasks_table">
			<thead>
			<tr>
			<!--  <th scope="col" class="check-column"></th> -->
			<?php
			
			foreach ( $posts_columns as $column_id => $column_display_name ) {
				
				$sortby_url = "sortby=$column_id&";
				if ($_GET ['sortby'] == $column_id)
					$order_url = $_GET ['order'] == 'DESC' ? 'order=ASC&amp;' : 'order=DESC&';
				if (isset ( $_GET ['task_status'] ))
					$task_status_url = "task_status=$task_status&";
				
				$url = "$base_url$task_status_url$show_tasks_of_group_url$sortby_url$order_url";
				
				$column_link = "<a href='" . $url . "'> $column_display_name </a>";
				$col_url = ($column_id == 'text' || $column_id == 'answers') ? $column_display_name : $column_link;
				?>
						
				<th scope="col" class="th_<?php echo $column_id?>">
					<?php echo $col_url?>
				</th>
				<?php
				}
				?>
			</tr>
			</thead>
			
			<tbody id="the-list">
			<?php
			
			$bgcolor = $class = '';
			$status_list = array ("publish" => "#fff", "drafts" => "#f2f2f2" );
			
			if (! $learning_diary_list)
				echo "<tr><td colspan=2>" . __('Keine Aufgaben in dieser Kategorie.', 'bp_learning_diary') . "</td></tr>";
			
			foreach ( $learning_diary_list as $the_learning_diary ) {
				
				$class = ('alternate' == $class) ? '' : 'alternate';
				$bgcolour = '';
				reset ( $status_list );
				
				$bgcolour = "";
				
				if ($the_learning_diary ["task_status"] == "publish" || $the_learning_diary ["task_status"] == "solved") {
					$bgcolour = "style='background:" . $status_list ["publish"] . "'";
				} else if ($_GET ['task_status'] != "drafts") {
					$bgcolour = "style='background:" . $status_list ["drafts"] . "'";
				}
				
				echo "<tr $bgcolour class='$class'>";
				
				foreach ( $posts_columns as $column_name => $column_display_name ) {
					switch ($column_name) {
						case 'id' :
							?>
							<th scope="row" class="check-column">
								<input type='checkbox' 
										id='blog_<?php echo $the_learning_diary ["ID"]?>' 
										name='allblogs[]'
										value='<?php echo $the_learning_diary ["ID"]?>' />
							</th>
							<th scope="row">
								<?php echo $the_learning_diary ["ID"]?>
								<br><br>
							</th>
								<?php
								break;
						
						case 'title' :
							?>
							<td valign="top">
							<?php
							//title should be shown with link if task is not solved entirely
							if (! ($the_learning_diary ["count_users"] == $the_learning_diary ["count_answers"] && $the_learning_diary ["count_users"] > 0)) {
								?>
								<a href="admin.php?page=<?php echo self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME?>&action=edittask&amp;id=<?php echo $the_learning_diary ["ID"]?>"
									class="edit">
									<?php echo $the_learning_diary ["post_title"];?>
								</a> 
								<?php
							} else {
								?>
								<?php echo $the_learning_diary ["post_title"];?>
								<?php
							} // end if ?>
									
							<?php
							if ($the_learning_diary ["task_status"] == "drafts" && $_GET ["task_status"] != "drafts") {
								?>	
								<i>(<?php _e ( "Draft" )?>)</i>
								<?php
							}
							?>
							<br />
							<?php
							$controlActions = array ();
							
							if ($the_learning_diary ['task_status'] != "trash") {
								if (! ($the_learning_diary ["count_users"] == $the_learning_diary ["count_answers"] && $the_learning_diary ["count_users"] > 0))
									$controlActions [] = '<a href="admin.php?page=' . self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME . '&action=edittask&amp;id=' . $the_learning_diary ['ID'] . '" class="edit">' . __ ( 'Edit' ) . '</a>';
								
								if ($the_learning_diary ['task_status'] == "publish") {
									$controlActions [] = '<a href="admin.php?page=' . self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME . '&action=movetask&amp;id=' . $the_learning_diary ['ID'] . '" class="edit">' . __( 'Gel&ouml;st', 'bp_learning_diary' ) . '</a>';
								}
								
								$controlActions [] = '<span class="trash"><a href="admin.php?page=' . self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME . '&amp;task_status=' . $task_status . '&amp;action=trash&amp;id=' . $the_learning_diary ['ID'] . '&_wpnonce=' . $deletenonce . '">' . __ ( "Trash" ) . '</a></span>';
							} else {
								$controlActions [] = '<span><a href="admin.php?page=' . self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME . '&amp;action=restore&amp;id=' . $the_learning_diary ['ID'] . '&_wpnonce=' . $deletenonce . '">' . __ ( "Restore" ) . '</a></span>';
								$controlActions [] = '<span class="trash"><a href="admin.php?page=' . self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME . '&amp;task_status=trash&amp;action=deletepermanently&amp;id=' . $the_learning_diary ['ID'] . '&_wpnonce=' . $deletenonce . '">' . __ ( "Delete Permanently" ) . '</a></span>';
							}
							?>
										
							<?php if (count ( $controlActions )) : ?>
								<div class="row-actions">
									<?php echo implode ( ' | ', $controlActions );?>
								</div>
										
							<?php endif;?>
							</td>
							<?php
							break;
						
						case 'text' :
							?>
							<td valign="top">
							<p>
							<?php
							$result = substr ( strip_tags ( htmlspecialchars_decode ( html_entity_decode ( stripslashes ( $the_learning_diary ["post_content"] ) ) ) ), 0, strpos ( htmlspecialchars_decode ( html_entity_decode ( stripslashes ( strip_tags ( $the_learning_diary ["post_content"] ) ) ) ), ' ', 120 ) - 1 );
							echo $result;
							?>
							</p>
							</td>
							<?php
							break;

						case 'answers' :
							?>
							<td valign="top">
							<?php
							
							if ($the_learning_diary ["count_users"]) {
								echo $the_learning_diary ['count_answers'] . ' ' . __('of') . ' ' . $the_learning_diary ['count_users'] . '<br/>';
								
								echo ' <a href="admin.php?page=' 
									. self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME 
									. '&amp;action=showanswers&amp;id=' 
									. $the_learning_diary ['ID'] 
									. '">'
									. __('Antworten zeigen', 'bp_learning_diary')
									. '</a>';
							}
							?>
										
							</td>
							<?php
							break;
						
						case 'users' :
							?>
							<td valign="top">
							<?php
							//TODO: Evtl. Gruppe anstatt user_id anzeigen
							$userids = $wpdb->get_results ( "SELECT user_id FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " WHERE task_id = $the_learning_diary[ID]" );
							foreach ( $userids as $the_user_id ) {
								echo "<p>$the_user_id->user_id</p>";
							}
							?>			
							</td>
							<?php
							break;
						
						case 'publish_date' :
							?>
							<td valign="top">
							<?php
							if ($the_learning_diary ["task_status"] == "drafts" || $the_learning_diary ["task_status"] == "trash") {
								?>	
								<i>-</i>
								<?php
							} else {
								?>
								<?php
								echo ($the_learning_diary ['publish_date'] == '0000-00-00 00:00:00') ? __( "Sofort", 'bp_learning_diary' ) : date_i18n ( get_option ( 'date_format' ) . " " . get_option ( 'time_format' ), strtotime ( $the_learning_diary ['publish_date'] ) );
								?>
								<?php
							}
							?>
							</td>
							<?php
							break;
						
						case 'review_date' :
							?>
							<td valign="top">
							<?php
							if ($the_learning_diary ["task_status"] == "drafts" || $the_learning_diary ["task_status"] == "trash") {
								?>	
								<i>-</i>
								<?php
							} else {
								?>
								<?php
								echo ($the_learning_diary ['review_date'] == '0000-00-00 00:00:00') ? __( "Offen", 'bp_learning_diary' ) : date_i18n ( get_option ( 'date_format' ) . " " . get_option ( 'time_format' ), strtotime ( $the_learning_diary ['review_date'] ) );
								?>
								<?php
							}
							?>
							</td>
							<?php
							break;
						
						case 'post_modified' :
							?>
							<td valign="top">
							<?php
							echo mysql2date ( __ ( 'Y-m-d \<\b\r \/\> g:i:s a' ), $the_learning_diary ['post_modified'] );
							?>
							</td>
							<?php
							break;
					
					} //end switch($column_name)
				} //end foreach($posts_columns ...
				?>
						</tr>
					<?php
			} //end foreach ($learning_diary_list ...
		

		} // end if ($blogs)
		

		?>

			</tbody>

	</table>
	</form>
	
	<div class="tablenav">
			
		<?php
		
		/*
			 * Print pagination
			 */
		
		if ($page_links) {
			?>
			<div class="tablenav-pages">
			<?php
			$page_links_text = sprintf ( '<span class="displaying-num">' . __ ( 'Displaying %s&#8211;%s of %s' ) . '</span>%s', number_format_i18n ( ($current - 1) * $tasks_per_page + 1 ), number_format_i18n ( min ( $current * $tasks_per_page, $count_tasks ) ), number_format_i18n ( $count_tasks ), $page_links );
			echo $page_links_text;
			?>
			</div>
			
			<?php
		}
		?>
					
	</div>

	<?php
	
	}
	
	private function show_answers($task_id) {
		global $wpdb;
		
		/*
		 * Pagination
		 */
		
		if ($_GET ["apage"])
			$current = intval ( $_GET ["apage"] );
		else
			$current = 1;
		
		$tasks_per_page = 20;
		$limit = $tasks_per_page;
		$start = $current * $tasks_per_page - $tasks_per_page;
		
		/*
		 * get task and answers
		 */
		
		$task = $this->get_task ( $task_id );
		
		if (! $task)
			return false;
		
		$answers = $this->get_task_answers ( $task_id, "", $start, $limit );
		
		/*
		 * Pagination
		 */
		
		$count_tasks = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " WHERE task_id=$task_id" );
		
		$total = (ceil ( $count_tasks / $tasks_per_page ));
		
		$page_links = paginate_links ( array ('base' => add_query_arg ( 'apage', '%#%' ), 'format' => '', 'prev_text' => __ ( '&laquo;' ), 'next_text' => __ ( '&raquo;' ), 'total' => $total, 'current' => $current ) );
		
		?>
		<div class="wrap" id="editnewtask">
				
		<?php
		$posts_columns = array ('author' => __ ( 'Author' ), 'text' => __ ( 'Content' ), 'comments' => __ ( 'Comments' ), 'post_modified' => __ ( 'Last Updated' ) );
		?>
		
		<h2>
			<?php printf(
				__('Antworten zur Aufgabe &laquo;%s&raquo;', 'bp_learning_diary'),
				$task->post_title
			)?>
			<a href="admin.php?page=<?php echo self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME;?>"
				class="button add-new-h2"><?php _e ( "&laquo; Back" );?>
			</a>
		</h2>

		<div class="tablenav">
			
		<?php
		
		/*
		 * Print Pagination 
		 */
		
		if ($page_links) {
			?>
			<div class="tablenav-pages"><?php
			$page_links_text = sprintf ( '<span class="displaying-num">' . __ ( 'Displaying %s&#8211;%s of %s' ) . '</span>%s', number_format_i18n ( ($current - 1) * $tasks_per_page + 1 ), number_format_i18n ( min ( $current * $tasks_per_page, $count_tasks ) ), number_format_i18n ( $count_tasks ), $page_links );
			echo $page_links_text;
			?>
			</div>
			<?php
		}
		?>
					
		</div>

	<table width="100%" cellpadding="3" cellspacing="3" class="widefat"
	id="learning_diary_answers_table">
	<thead>
		<tr>
		<?php
		foreach ( $posts_columns as $column_id => $column_display_name ) {
			if ($_GET ["order"] == "DESC")
				$order = "ASC";
			else
				$order = "DESC";
			
			if ($column_id == "author" || $column_id == "post_modified") {
				//insert sorting link
				$a_url = "admin.php?page=learning-diary-tasks-init.php&action=showanswers&id=$_GET[id]&order=$order&sort_by=$column_id";
				$col_url = "<a href='$a_url'>$column_display_name</a>";
			} else {
				//no sorting link
				$col_url = $column_display_name;
			}
			$order = "";
			?>
			<th scope="col" class="th_<?php echo $column_id?>"><?php echo $col_url?></th>
			<?php
		}
		?>
		</tr>
	</thead>

	<tbody id="the-list">
				
		<?php
		
		if ($answers) {
			
			/*
			 * Sorting:
			 */
			
			//prepare for array_multisort function
			
			foreach ( $answers as $the_answer ) {
				
				//prepare array for author
				$display_names [] = get_userdata ( $the_answer->post_author )->display_name;
				
				//prepare array for post_modified
				if ($the_answer->post_modified)
					$post_modified [] = $the_answer->post_modified;
				else
					$post_modified [] = 0;
			
			}
			
			//get _GET data

			if ($_GET ["sort_by"])
				$sort_by = $_GET ["sort_by"];
			if ($_GET ["order"])
				$order = $_GET ["order"];
			
			//perform array_multisort function based on user choice
			
			if ($sort_by == "author") {
				
				if ($order == "ASC") {
					//SORT: author ASC	
					array_multisort ( $display_names, SORT_ASC, $answers );
				} else {
					//SORT: author DESC
					array_multisort ( $display_names, SORT_DESC, $answers );
				}
			
			} else {
				
				if ($order == "ASC") {
					//SORT: post_modified ASC
					array_multisort ( $post_modified, SORT_ASC, $answers );
				} else {
					//SORT: post_modified DESC
					array_multisort ( $post_modified, SORT_DESC, $answers );
				}
			
			}
			
			/*
			 * End sorting
			 */
			
			$bgcolor = $class = '';
			$status_list = array ("publish" => "#fff", "has-comments" => "#fff", "not-answered" => "#f2f2f2", "trash" => "#fff" );
			foreach ( $answers as $the_answer ) {
				
				//Check if $the_learning_diary is a trashed entry
				if ($the_answer->task_status == "trash" && $_GET ["task_status"] != "trash")
					continue;
				
				//Check if $the_learning_diary is 
				if (isset ( $_GET ['task_status'] ) && $the_answer->task_status != $_GET ['task_status'])
					continue;
				
				$class = ('alternate' == $class) ? '' : 'alternate';
				$bgcolour = '';
				reset ( $status_list );
				
				$bgcolour = "";
				
				if ($the_answer->post_status == "publish") {
					$bgcolour = "style='background:" . $status_list ["publish"] . "'";
				} else if ($the_answer->post_status == "trash") {
					$bgcolour = "style='background:" . $status_list ["trash"] . "'";
				} else if ($the_answer->comment_count > 0) {
					$bgcolour = "style='background:" . $status_list ["has-comments"] . "'";
				} else {
					$bgcolour = "style='background:" . $status_list ["not-answered"] . "'";
				}
				
				?> <tr <?php echo $bgcolour?> class='<?php echo $class?>'> <?php
				
				foreach ( $posts_columns as $column_name => $column_display_name ) {
					switch ($column_name) {
						case 'id' :
							?>
							<td valign="top">
							<input type='checkbox' 
								id='blog_<?php echo $the_answer->ID?>' 
								name='allblogs[]' 
								value='<?php echo $the_answer->ID?>' />
							</td>
			
							<td valign="top">
								<?php echo $the_answer->ID;?>
							</td>
							<?php
							break;
						
						case 'author' :
							?>		
							<td valign="top">
								<?php
								$user = get_userdata ( $the_answer->post_author );
								echo $user->display_name;
								?>
							</td>
							<?php
							break;
						
						case 'title' :
							?>
							<td valign="top">
								<a href="admin.php?page=<?php echo self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME?>&action=edittask&amp;id=<?php echo $the_answer->ID?>"
									class="edit">
									<?php echo $the_answer->post_title;?>
								</a>
							</td>
							<?php
							break;
						
						case 'text' :
							?>
							<td valign="top">
							<?php
							if ($the_answer->post_status == "publish") {
								
								switch_to_blog ( $the_answer->user->blog_id );
								
								/*
								 * Check if user has access to the blog content
								 */
								
								$post_access_control = new BP_Post_Access_Control ();
								
								echo $post_access_control->filter_content ( $the_answer->post_content, $the_answer->ID );
								
								restore_current_blog ();
								
								$controlActions = '<a href="' . $the_answer->guid . '" class="edit">' . __( 'Beitrag ansehen und kommentieren', 'bp_learning_diary' ) . '</a>';
								?>
								<br />
								<div class="row-actions">
								<?php
								echo $controlActions;
								?>
								</div>
								<?php
							
							} else if ($the_answer->user->task_status == "trash") {
								_e('Nutzer hat Aufgabe in den Papierkorb verschoben.', 'bp_learning_diary');
							} else if ($the_answer->user->task_status == "deleted") {
								_e('Nutzer hat seine Antwort endg&uuml;ltig gel&ouml;scht.', 'bp_learning_diary');
							} else {
								_e('Noch nicht geantwortet.', 'bp_learning_diary');
							}
							
							?>
							</td>
							<?php
							break;
						
						case 'comments' :
							?>
							<td valign="top">
								<p><?php echo $the_answer->comment_count; ?></p>
							</td>
							<?php
							break;
						
						case 'post_modified' :
							?>
							<td valign="top">
							<?php
							if ($the_answer->ID)
								echo date_i18n ( get_option ( 'date_format' ) . " " . get_option ( 'time_format' ), strtotime ( $the_answer->post_modified ) );
							?>
							</td>
							<?php
							break;
					
						}
					}
					?>
					</tr>
					<?php
			}
		} else {
			?>
			<tr style='background-color: <?php echo $bgcolor; ?>'>
				<td colspan="8"><?php _e ( 'No blogs found.' )?></td>
			</tr> 
			<?php
		} // end if ($blogs)	
		?>
		</tbody>
	</table>
	<div class="tablenav">
			
		<?php
		
		/*
		 * Print Pagination 
		 */
		
		if ($page_links) {
			?>
			<div class="tablenav-pages"><?php
			$page_links_text = sprintf ( '<span class="displaying-num">' . __ ( 'Displaying %s&#8211;%s of %s' ) . '</span>%s', number_format_i18n ( ($current - 1) * $tasks_per_page + 1 ), number_format_i18n ( min ( $current * $tasks_per_page, $count_tasks ) ), number_format_i18n ( $count_tasks ), $page_links );
			echo $page_links_text;
			?>
			</div>
			<?php
		}
		?>
					
	</div>
	<br />
	</div>

	<?php
	
	}
	
	private function display_errors($errors, $title) {
		if ($errors [$title]) {
			?>
		<div class="task-error">
			<h3><?php echo $errors [$title];?></h3>
		</div>
		<?php
		}
	}
	
	private function get_recent_tasks_for_user() {
		global $current_user;
		
		$show_tasks_of_group = $_GET ["show_tasks_of_group"];
		
		/*
		 * Pagination 
		 */
		if ($_GET ["paged"])
			$current = intval ( $_GET ["paged"] );
		else
			$current = 1;
		
		$tasks_per_page = 10;
		$limit = $tasks_per_page;
		$start = $current * $tasks_per_page - $tasks_per_page;
		
		/*
		 * Order By
		 */
		
		// Select published tasks only
		$orderby = ($_GET ["user_tasks_orderby"]) ? $_GET ["user_tasks_orderby"] : 'publish_date';
		
		$asc_or_desc = ($_GET ["user_tasks_order"]) ? $_GET ["user_tasks_order"] : 'ASC';
		
		if ($asc_or_desc == "ASC")
			$asc_or_desc = "DESC";
		else
			$asc_or_desc = "ASC";
		
		$order = "user_tasks_order=$asc_or_desc&amp;";
		
		/*
		 * Get tasks
		 */
		
		$tasks = $this->get_recent_tasks ( $current_user->ID, $_GET ["post_status"], $orderby, $asc_or_desc, $start, $limit, $show_tasks_of_group );
		
		/*
		 * Pagination
		 */
		
		$count_tasks = $tasks ["count_open_posts"];
		if ($_GET ["post_status"] == "publish"){
			$count_tasks = $tasks ["count_solved_posts"];
		}else if ($_GET ["post_status"] == "trash"){
			$count_tasks = $tasks ["count_trash_posts"];
		}
		$total_count_tasks = $tasks ["count_open_posts"] + $tasks ["count_solved_posts"] + $tasks ["count_trash_posts"];
		
		$total = (ceil ( $count_tasks / $tasks_per_page ));
		
		$page_links = paginate_links ( array ('base' => add_query_arg ( array ('paged' => '%#%', 'action' => '', 'id' => '' ) ), 'format' => '', 'prev_text' => __ ( '&laquo;' ), 'next_text' => __ ( '&raquo;' ), 'total' => $total, 'current' => $current ) );
		
		/*
		 * Print tasks in table
		 */
		
		?>
	
	
	
	<?php
		/*
		 * List task entries
		 */
		
		$groups = groups_get_user_groups ( $current_user->ID, 0, 100 );
		
		//NEW: $group and $lernender in group
		//EDIT: $group 
		

		/*
		 * Base URL
		 */
		//	$base_url = "admin.php?page=".self::LEARNING_DIARY_TASKS_PLUGIN_BASENAME."&";
		//	(!$_GET["post_status"]) ? $post_status_url = "": $task_status_url = "task_status=".$_GET["task_status"]."&";
		//	(!$_GET["show_tasks_of_group"]) ? $show_tasks_of_group_url = "": $show_tasks_of_group_url = "show_tasks_of_group=".$_GET["show_tasks_of_group"]."&";
		?>
	
	<?php //if( $groups["total"] ){  		?>
	<div id="show_tasks_of_group_div">
	<label for="show_tasks_of_group"><?php _e('Nur Aufgaben zeigen f&uuml;r Gruppe:', 'bp_learning_diary')?></label>
	<select id="show_tasks_of_group" name="show_tasks_of_group">
		<option value="0"><?php _e('Alle zeigen', 'bp_learning_diary')?></option>
		<?php
		
		foreach ( $groups ["groups"] as $the_group_id ) {
			$group = new BP_Groups_Group ( $the_group_id );
			
			$group_has_members = $this->group_has_students ( $groups );
			
			$selected = "";
			
			if ($group->id == $_GET ["show_tasks_of_group"])
				$selected = "selected";
			?>
			<option value="<?php echo $group->id;?>" <?php echo $selected?>>
				<?php echo $group->name;?>
			</option>
		<?php
		}
		?>
	</select>
	</div>
	<?php //} 		?>

	<h2><?php _e( 'Meine Aufgaben', 'bp_learning_diary' )?></h2>
	<?php
		
	if ($total_count_tasks) {
			
		$pagename = "learning_tasks";
			
		?>

		<div id="the-task-list">

		<div class="tablenav">
		<ul class="subsubsub">
			<?php //learning_tasks
			$switch_link = "admin.php?page=$pagename&post_status=";
			?>
			<li><a href="<?php echo $switch_link . "&show_tasks_of_group=$show_tasks_of_group"?>"
			<?php
			if ($_GET ["post_status"] == "") {
				echo "class='current'";
			}
			?>>
			<?php _e( "Offene Aufgaben und Entw&uuml;rfe", 'bp_learning_diary' )?> (<?php echo $tasks ["count_open_posts"];?>)
			</a></li>
			
			<?php
			if ($tasks ["count_solved_posts"]) {
				?>
				<li>| <a href="<?php echo $switch_link . "publish&show_tasks_of_group=$show_tasks_of_group";?>"
				<?php
				if ($_GET ["post_status"] == "publish") {
					echo "class='current'";
				}
				?>>
				<?php _e( "Gel&ouml;ste Aufgaben", 'bp_learning_diary' )?> (<?php echo $tasks ["count_solved_posts"];?>)
				</a></li>
				<?php
			}
			?>
			<?php
			if ($tasks ["count_trash_posts"]) {
				?>
				<li>| <a href="<?php echo $switch_link . "trash&show_tasks_of_group=$show_tasks_of_group";?>"
				<?php
				if ($_GET ["post_status"] == "trash") {
					echo "class='current'";
				}
				?>>
				<?php _e ( "Trash" );?> (<?php echo $tasks ["count_trash_posts"];?>)
				</a></li>
				<?php
			}
			?>
			</ul>
			<?php
			if ($page_links) {
				?>
				<div class="tablenav-pages"><?php
				$page_links_text = sprintf ( '<span class="displaying-num">' . __ ( 'Displaying %s&#8211;%s of %s' ) . '</span>%s', number_format_i18n ( ($current - 1) * $tasks_per_page + 1 ), number_format_i18n ( min ( $current * $tasks_per_page, $count_tasks ) ), number_format_i18n ( $count_tasks ), $page_links );
				echo $page_links_text;
				?>
				</div>
				<?php
			}
			?>
			</div>
			<table class="widefat" id="learning_diary_tasks_table" width="100%"
					cellpadding="3" cellspacing="3">
			<thead>
			<tr class="alt">
			<th scope="col" class="th_title"><?php _e('Title')?></th>
			<th scope="col" class="th_author">
				<a href="admin.php?page=<?php echo $pagename?>&amp;user_tasks_orderby=post_author&amp;<?php echo ($orderby == "post_author") ? $order : "";?>
					apage=1&amp;post_status=<?php echo $_GET ["post_status"]?>&amp;show_tasks_of_group=<?php
					echo $show_tasks_of_group?>"><?php _e('Author')?>
				</a>
			</th>
			<th scope="col" class="th_publish_date">
				<a href="admin.php?page=<?php echo $pagename?>
					&amp;user_tasks_orderby=publish_date&amp;<?php echo ($orderby == "publish_date") ? $order : "";?>
					apage=1&amp;post_status=<?php
					echo $_GET ["post_status"]?>&amp;show_tasks_of_group=<?php echo $show_tasks_of_group?>"><?php _e('Date')?>
				</a>
			</th>
			<th scope="col" class="th_review_date">
				<a href="admin.php?page=<?php echo $pagename?>&amp;user_tasks_orderby=review_date
					&amp;<?php echo ($orderby == "review_date") ? $order : "";?>
					apage=1&amp;post_status=<?php echo $_GET ["post_status"]?>
					&amp;show_tasks_of_group=<?php echo $show_tasks_of_group?>"><?php _e('Abzugeben bis', 'bp_learning_diary')?> 
				</a>
			</th>
			<th scope="col" class="th_answer"><?php _e('Beantworten', 'bp_learning_diary')?></th>
		</tr>
	</thead>
	<tbody>
	
	<?php
			
	if (! $tasks ["tasks"])
		echo "<tr><td colspan=2>" . __('Keine Aufgaben in dieser Kategorie.', 'bp_learning_diary') . "</td></tr>";
		
	foreach ( $tasks ["tasks"] as $task ) {
				
		$userdata = get_userdata ( $task->post_author );
				
		?>
	
		<tr id="task-<?php echo $task->ID;?>"
			class="byuser comment-author-admin comment-item"
			<?php
				if (! $task->post_id) {
					?> style="background: #fee;" <?php
				}
				?>>

			<td><span><?php
				echo $task->post_title?></span></td>
			<td>von <?php
				echo $userdata->display_name?></td>
			<td><?php
				echo date_i18n ( get_option ( 'date_format' ), strtotime ( $task->publish_date ) );
				?></td>
			<?php
				if ($task->review_date == '0000-00-00 00:00:00') {
					?>
				<td><?php _e('Offen', 'bp_learning_diary')?></td>
			<?php
				} else {
					?>
				<td><?php
					echo date_i18n ( get_option ( 'date_format' ) . " " . get_option ( 'time_format' ), strtotime ( $task->review_date ) );
					?></td>
			<?php
				}
				?>
				<td>
			<div>
			  	<?php
				if ($task->post_id) {
					?>
						<a
				href="<?php
					echo get_bloginfo ( 'url' );
					?>/wp-admin/post.php?action=edit&post=<?php
					echo $task->post_id?>"
				class="button"><?php _e('Edit')?></a>	
			  	<?php
				} else {
					?>
			<form name="post"
				action="<?php
					echo get_bloginfo ( 'url' );
					?>/wp-admin/admin.php?page=<?php
					echo $pagename?>&action=createpost"
				method="post" class="quick-press"><input name="task_id"
				type="hidden" value="<?php
					echo $task->ID?>" /> <input
				name="post_title" type="hidden"
				value="<?php
					echo $task->post_title?>" /> <input
				name="post_content" type="hidden"
				value="<?php
					echo $task->post_content?>" /> <input type="submit"
				class="button" value="<?php _e('Beantworten', 'bp_learning_diary')?>"></form>
			  	<?php
				} //endif 				?>
					</div>
			</td>
		</tr>
			
	<?php
			
			}
			
			?>			
	</tbody>
	</table>
	<div class="tablenav">
		<?php if ($page_links) { ?>
			<div class="tablenav-pages"><?php
				$page_links_text = sprintf ( '<span class="displaying-num">' . __ ( 'Displaying %s&#8211;%s of %s' ) . '</span>%s', number_format_i18n ( ($current - 1) * $tasks_per_page + 1 ), number_format_i18n ( min ( $current * $tasks_per_page, $count_tasks ) ), number_format_i18n ( $count_tasks ), $page_links );
				echo $page_links_text;
				?></div>
				<?php
			}
			?>
			<?php //echo ($page_links); 			?>
			</div>
	<br />
	</div>

	<?php
		
		} else {
			
			?>
	<p><?php _e( 'Du hast noch keine zu l&ouml;senden Aufgaben erhalten.', 'bp_learning_diary' );?></p>

	<?php
		}
	}
}

?>