<?php 

/*
 * Shows meta box(es) (on the right sidebar) in post-new.php and post-edit.php if the post is from a task
 */

Class LearningDiaryTasksPostEdit Extends LearningDiaryTasks {
	
	public function construct(){
		global $post;		
		global $wpdb;
		
		#Show Meta Box only if post is part of a task
		if(get_post_meta($post->ID,"_task_id",true)){
			//get title
			$task_id = get_post_meta($post->ID,"_task_id",true);
			$task = $wpdb->get_results( "SELECT * FROM ".$wpdb->base_prefix.self::TASK_TABLE_NAME." WHERE ID=$task_id" );
			$task_title = $task[0]->post_title;
			
			$owner_of_task_id = $task[0]->post_author;
			$user = get_userdata($owner_of_task_id);
			
			//show metabox
			add_meta_box(
				'current_task', 
				sprintf(__('Aufgabe (von %1$s): %2$s', 'bp_learning_diary'), $user->display_name, $task_title),
				array('LearningDiaryTasksPostEdit','edit_post'), 
				'post', 
				'side'
	        );
		}
		
		//create custom answer format meta data entry in db if task has such data
		$task_id = get_post_meta($post->ID, "_task_id", true);
	
		$query = "SELECT meta_value FROM " 
				. $wpdb->base_prefix . self::META_TASK_TABLE_NAME 
				. " WHERE task_id=$task_id AND meta_key='_ld_answer_format'";
				
		if( $task_id AND !get_post_meta($post->ID, '_ld_answer_format', true)
					 AND $answer_value = $wpdb->get_var($query) )
		{
			add_post_meta($post->ID, '_ld_answer_format', maybe_unserialize($answer_value)); //add_post_meta does serialize internal	
		}
		
		//show custom answer format meta box if post has such data
		if( $answer_value = maybe_unserialize(get_post_meta($post->ID, '_ld_answer_format', true)) ){
			
			add_meta_box(
				'AnswerFormatMetaBox', 
				__('Kurzfrage:', 'bp_learning_diary') . ' ' . $answer_value['ques'],
				array('LearningDiaryTasksPostEdit', 'show_answer_format_meta_box'), 
				'post', 
				'normal',
				'high'
			);
		}
	}
	
	/*
	 * add task jquery in post.php
	 */
	
	public function add_jquery(){
		//load js for positioning the metabox on the Top of the Post
		wp_enqueue_script(
			"learning_diary_tasks", 
			( WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . "/bp-learning-diary/js/bp-learning-diary-edit-post.js"), 
			array( 'jquery' ) 
		);

	}
	
	/*
	 * add task css in post.php
	 */
	
	public function add_style() {
		//$myStyleUrl = WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) ) . '/css/admin.css';	
		$myStyleUrl = WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/css/admin.css';	
		wp_register_style('learning_diary_tasks_admin_style', $myStyleUrl);
		wp_enqueue_style( 'learning_diary_tasks_admin_style');
	}

	/*
	 * add task metabox at the top of post.php
	 */
	
	public function edit_post() {
		global $post;
		global $wpdb;
		
		$task_id = get_post_meta($post->ID,"_task_id",true);
		$task = $wpdb->get_results( "SELECT * FROM ".$wpdb->base_prefix.self::TASK_TABLE_NAME." WHERE ID=$task_id" );
		$owner_of_task_id = $task[0]->post_author;
		$user = get_userdata($owner_of_task_id);
		?>
		<div class="post">
			<p>
				<?php echo apply_filters('the_content', htmlspecialchars_decode(html_entity_decode(stripslashes($task[0]->post_content)))); ?>
			</p>
		</div>
		
		<?php 
	}
	
	/*
	 * show custom answer format in a meta box
	 */
	
	public function show_answer_format_meta_box()
	{
		global $post;

		include_once('learning-diary-tasks-answer-format.php');
				
		$answer_format_db = maybe_unserialize(get_post_meta($post->ID, '_ld_answer_format'));
		
		$AnswerFormat = new LearningDiaryTaskAnswerFormat($answer_format_db[0]);
		
		$AnswerFormat->show();

	}
	
	/*
	 * save custom answer format VALUE to database
	 * custom answer format question and format can't be changed by student
	 */
	
	public function save_answer_format()
	{
		include_once("learning-diary-tasks-answer-format.php");		
		
		global $post;

		$answer_format_db = maybe_unserialize(get_post_meta($post->ID, '_ld_answer_format'));
		
		if ($format = $_POST['answer_format_format']) {
			
			$value = is_array($_POST[$format]) ? $_POST[$format] : array($_POST[$format]);
			
			$answer_format_db[0]['value'] = $value;
			
			update_post_meta($post->ID, '_ld_answer_format', $answer_format_db[0]); //update_post_meta does serialize internal
		}
		
	}
	
	/*
	 * display custom answer format in single post view
	 */
	
	public function display_answer_format_in_single_post()
	{
		include_once('learning-diary-tasks-answer-format.php');
		
		global $post;
		
		$answer_format_db = maybe_unserialize(get_post_meta($post->ID, '_ld_answer_format'));
		
		$AnswerFormat = new LearningDiaryTaskAnswerFormat($answer_format_db[0]);
		?>
		<?php
		if($AnswerFormat->ques):?>
			<div><?php echo '<b><i>' . __('Kurzfrage:', 'bp_learning_diary') .  '</i>' . ' ' . $AnswerFormat->ques . '</b>'?></div>
			<?php
			$AnswerFormat->show(true, true); //show disabled (readonly) answer format
		endif;
	}
	
	/*
	 * Change post_id, blog_id and task_status in USER_TASK_TABLE_NAME while updating or editing a post
	 */
	
	public function update_post($post_id,$post){
		global $wpdb;
		global $current_blog;
			
		$user_id = $post->post_author;
		$post_id = $post->ID; 
		$task_status = $post->post_status; 
		$task_id = false;
		
		$task_id = get_post_meta($post->ID,"_task_id",true);
					
		if ($task_id) {
			//update the USER_TASK_TABLENAME, insert post_id and task_status
			$data = array(
				"post_id" => $post_id,
				"task_status" => $task_status,
				"blog_id" => $current_blog->blog_id 
			);
				
			$wpdb->update(
				$wpdb->base_prefix . self::USER_TASK_TABLE_NAME, 
				$data,
				array( "task_id" => $task_id, "user_id" => $user_id)
			);
			
		}
		
		$count_users = $wpdb->get_var("SELECT count(*) FROM " . $wpdb->base_prefix.self::USER_TASK_TABLE_NAME . " WHERE task_id=" . $task_id);
		$count_answers = $wpdb->get_var("SELECT count(*) FROM " . $wpdb->base_prefix.self::USER_TASK_TABLE_NAME . " WHERE task_id=" . $task_id . " AND (task_status='publish' OR task_status='trash')");
		
		if($count_users == $count_answers && $count_users>0){
			$wpdb->update(
				$wpdb->base_prefix . self::TASK_TABLE_NAME, 
				array( "task_status" => "solved" ),
				array( "ID" => $task_id)
			);
		}
			
	}
	
	/*
	 * Remove data from USER_TASK_TABLENAME when deleting a post that is part of a task 
	 */
	
	public function delete_post(){
		global $post;
		global $current_blog;
		global $wpdb;
		
		$user_id = $post->post_author;
		$post_id = $post->ID; 
		$task_status = 	"deleted";  

		$task_id = get_post_meta($post->ID,"_task_id",true);
		
		if ($task_id) {
			//update the USER_TASK_TABLENAME, insert post_id and task_status
			$data = array(
				"post_id" => $post_id,
				"task_status" => $task_status,
				"blog_id" => $current_blog->blog_id 
			);
				
			$wpdb->update(
				$wpdb->base_prefix . self::USER_TASK_TABLE_NAME, 
				$data,
				array( "task_id" => $task_id, "user_id" => $user_id)
			);			
		}		
	}
}
?>