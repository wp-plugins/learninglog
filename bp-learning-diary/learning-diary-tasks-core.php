<?php
class LearningDiaryTasks {
	const TASK_TABLE_NAME = "learning_diary_tasks";
	const USER_TASK_TABLE_NAME = "learning_diary_tasks_users";
	const META_TASK_TABLE_NAME = "learning_diary_tasks_meta";
//	const LEARNING_DIARY_TASKS_OPTION_PLUGIN_BASENAME = "bp-learning-diary/learning-diary-tasks-init.php";
	const LEARNING_DIARY_TASKS_OPTION_PLUGIN_BASENAME = "learning-diary-tasks-options.php";
	const LEARNING_DIARY_TASKS_PLUGIN_BASENAME = "learning-diary-tasks-init.php";
	const LEARNING_DIARY_TASKS_LEARNING_TASKS = "learning_tasks";
	
	public function user_has_tasks() {
		global $wpdb;
		global $current_user;
	
		//TODO: Implement function that checks if current_user has tasks
	}
	
	/*
	 * check if teacher is member of a group with pupils
	 */
	protected function teacher_has_pupils() {
		global $current_user;
		
		//check if current_user is teacher
		if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true )) {
			//check if current_user is member of a group
			$groups = groups_get_user_groups ( $current_user->ID, 0, 100 );
			
			//check if group contains pupils
			foreach ( $groups ["groups"] as $the_group_id ) {
				$group = new BP_Groups_Group ( $the_group_id );
				
				if ($group->total_member_count > 1)
					
					// get group members
					$group_members = BP_Groups_Member::get_all_for_group ( $the_group_id, 0, 100, false, true );
				
		// check if user is student
				foreach ( $group_members ["members"] as $the_group_member ) {
					if (get_user_meta ( $the_group_member->user_id, 'learning_diary_tasks_student', true ))
						return true;
				}
			
			}
		
		}
		
		return false;
	
	}
	
	/**
	 * @returns date
	 */
	
	protected function get_date($data, $key = 0) {
		//sanitize user input data
		$mm = intval ( $data ["mm"] [$key] );
		$jj = intval ( $data ["jj"] [$key] );
		$aa = intval ( $data ["aa"] [$key] );
		$hh = intval ( $data ["hh"] [$key] );
		$mn = intval ( $data ["mn"] [$key] );
		$ss = intval ( $data ["ss"] [$key] );
		
		$date = mktime ( $hh, $mn, $ss, $mm, $jj, $aa );
		
		return date ( "Y-m-d H:i:s", $date );
	
	}
	
	/*
	 * returns the owner id of a task
	 */
	
	protected function get_task_owner($task_id) {
		global $wpdb;
		
		if ($task_id) {
			$task_owner = $wpdb->get_var ( "SELECT post_author FROM " . $wpdb->base_prefix . self::TASK_TABLE_NAME . " WHERE ID=$task_id" );
		}else{
			return false;
		}
		
		return $task_owner;
	}
	
	protected function get_task($task_id = 0, $task_owner = 0) {
		global $wpdb;
		global $current_user;
		
		if (! $task_owner) {
			$task_owner = $current_user->ID;
		}
		if (! $task_id) {
			return false;
		}else{
			$task = $wpdb->get_row ( "SELECT * FROM " . $wpdb->base_prefix . self::TASK_TABLE_NAME . " WHERE ID=$task_id AND post_author=$task_owner" );
		}
		if (! $task) {
			return false;
		}else{
			$task->users = $wpdb->get_results ( "SELECT * FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " WHERE task_id = $task_id" );
		}
		$task->meta = $wpdb->get_results ( "SELECT * FROM " . $wpdb->base_prefix . self::META_TASK_TABLE_NAME . " WHERE task_id = $task_id " );
		
		return $task;
	}
	
	/*
	 * get answer posts for a task
	 * 
	 * @returns array with title and content of the post affiliated to the task
	 */
	
	protected function get_task_answers($task_id, $user_task_status = "", $start = 0, $limit = 10) {
		
		global $wpdb;
		
		$users = $wpdb->get_results ( "SELECT * FROM " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . "  WHERE task_id=$task_id $user_task_status LIMIT $start, $limit" );
		
		$posts = array ();
		
		foreach ( $users as $the_user ) {
			$blog_id = $the_user->blog_id;
			$post_id = $the_user->post_id;
			//get post
			if (! $the_user->post_id == 0) {
				$post = NULL;
				$post = get_blog_post ( $blog_id, $post_id );
				//$post = $wpdb->get_results("SELECT * FROM " . $wpdb->base_prefix . $blog_id . "_posts WHERE ID = $post_id", ARRAY_A);
				$post->user = $the_user;
				$posts [] = $post;
			} else {
				$post = NULL;
				$post->ID = 0;
				$post->post_author = $the_user->user_id;
				$post->user = $the_user;
				$posts [] = $post;
			}
		}
		return $posts;
	}
	
	public function get_recent_tasks($userid, $user_task_status = "", $order_by = "review_date", $order = "ASC", $start = 0, $limit = 10, $group_id = 0) {
		global $wpdb;
		global $current_blog;
		
		$order_by_allowed = array ("review_date", "post_author", "publish_date" );
		
		if ($order == "DESC")
			$order = "DESC";
		else
			$order = "ASC";
		
		if (! in_array ( $order_by, $order_by_allowed ))
			$order_by = "review_date";
		
		if (! $userid)
			return false;
		
		if ($user_task_status == "")
			$user_task_status = "(u.task_status='draft' OR u.task_status='')";
		else
			$user_task_status = "u.task_status='$user_task_status'";
		
		$gr_id_sql = $group_id > 0 ? " AND m.meta_key = 'group' AND m.meta_value = $group_id " : " ";
		$gr_meta_sql = $group_id > 0 ? " LEFT JOIN " . $wpdb->base_prefix . self::META_TASK_TABLE_NAME . " m ON t.ID = m.task_id " : " ";
		
		// Select tasks that are published by the teacher AND already visible
		$tasks ["tasks"] = $wpdb->get_results ( "SELECT * FROM " . $wpdb->base_prefix . self::TASK_TABLE_NAME . " t 
			LEFT JOIN " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " u 
			ON t.ID = u.task_id" . $gr_meta_sql . "WHERE u.user_id=$userid AND UNIX_TIMESTAMP(t.publish_date) <= " . time() . " AND t.task_status <> 'trash' AND t.task_status <> 'drafts' AND $user_task_status " . $gr_id_sql . "ORDER BY t.$order_by $order 
			LIMIT $start, $limit" );
		
		$tasks ["count_new_posts"] = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::TASK_TABLE_NAME . " t
			LEFT JOIN " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " u 
			ON t.ID = u.task_id" . $gr_meta_sql . "WHERE u.user_id=$userid AND UNIX_TIMESTAMP(t.publish_date) <= " . time() . " AND t.task_status <> 'trash' AND t.task_status <> 'drafts' AND (u.task_status='')" . $gr_id_sql );
		
		$tasks ["count_open_posts"] = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::TASK_TABLE_NAME . " t 
			LEFT JOIN " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " u 
			ON t.ID = u.task_id" . $gr_meta_sql . "WHERE u.user_id=$userid AND UNIX_TIMESTAMP(t.publish_date) <= " . time() . " AND t.task_status <> 'trash' AND t.task_status <> 'drafts' AND (u.task_status='draft' OR u.task_status='')" . $gr_id_sql );
		
		$tasks ["count_solved_posts"] = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::TASK_TABLE_NAME . " t
			LEFT JOIN " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " u 
			ON t.ID = u.task_id" . $gr_meta_sql . "WHERE u.user_id=$userid AND UNIX_TIMESTAMP(t.publish_date) <= " . time() . " AND t.task_status <> 'trash' AND t.task_status <> 'drafts' AND u.task_status='publish'" . $gr_id_sql );
		
		$tasks ["count_trash_posts"] = $wpdb->get_var ( "SELECT count(*) FROM " . $wpdb->base_prefix . self::TASK_TABLE_NAME . " t 
			LEFT JOIN " . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . " u 
			ON t.ID = u.task_id" . $gr_meta_sql . "WHERE u.user_id=$userid AND UNIX_TIMESTAMP(t.publish_date) <= " . time() . " AND t.task_status <> 'trash' AND t.task_status <> 'drafts' AND u.task_status='trash'" . $gr_id_sql );
		
		return $tasks;
	
	}
	
	protected function learning_diary_tasks_date_time($edit = 1, $tab_index = 0, $multi = 0, $date = '0000-00-00 00:00:00', $separator = "@") {
		//TODO: insert date picker
		global $wp_locale;
		global $comment;
		
		if (! $date || $date == '0000-00-00 00:00:00')
			$date = date ( "Y-m-d H:i:s" );
		
		$tab_index_attribute = '';
		if (( int ) $tab_index > 0)
			$tab_index_attribute = " tabindex=\"$tab_index\"";
		
		$time_adj = time () + (get_option ( 'gmt_offset' ) * 3600);
		$post_date = $date;
		$jj = ($edit) ? mysql2date ( 'd', $post_date, false ) : gmdate ( 'd', $time_adj );
		$mm = ($edit) ? mysql2date ( 'm', $post_date, false ) : gmdate ( 'm', $time_adj );
		$aa = ($edit) ? mysql2date ( 'Y', $post_date, false ) : gmdate ( 'Y', $time_adj );
		$hh = ($edit) ? mysql2date ( 'H', $post_date, false ) : gmdate ( 'H', $time_adj );
		$mn = ($edit) ? mysql2date ( 'i', $post_date, false ) : gmdate ( 'i', $time_adj );
		$ss = ($edit) ? mysql2date ( 's', $post_date, false ) : gmdate ( 's', $time_adj );
		
		$cur_jj = gmdate ( 'd', $time_adj );
		$cur_mm = gmdate ( 'm', $time_adj );
		$cur_aa = gmdate ( 'Y', $time_adj );
		$cur_hh = gmdate ( 'H', $time_adj );
		$cur_mn = gmdate ( 'i', $time_adj );
		
		$month = "<select " . ($multi ? 'name="mm[]" ' : 'name="mm" ') . "id=\"mm\"$tab_index_attribute>\n";
		for($i = 1; $i < 13; $i = $i + 1) {
			$month .= "\t\t\t" . '<option value="' . zeroise ( $i, 2 ) . '"';
			if ($i == $mm)
				$month .= ' selected="selected"';
			$month .= '>' . $wp_locale->get_month_abbrev ( $wp_locale->get_month ( $i ) ) . "</option>\n";
		}
		$month .= '</select>';
		
		$day = '<input type="text" ' . ($multi ? 'name="jj[]" ' : 'name="jj" ') . 'id="jj" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		$year = '<input type="text" ' . ($multi ? 'name="aa[]" ' : 'name="aa" ') . 'id="aa" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" />';
		$hour = '<input type="text" ' . ($multi ? 'name="hh[]" ' : 'name="hh" ') . 'id="hh" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		$minute = '<input type="text" ' . ($multi ? 'name="mn[]" ' : 'name="mn" ') . 'id="mn" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		
		?>
		<div class="timestamp-wrap">
		<?php
		/* translators: 1: day input, 2: month input, 3: year input, 4: separator, 5: hour input, 6: minute input */
		printf ( __( '%1$s. %2$s %3$s %4$s %5$s : %6$s', 'bp_learning_diary' ), $day, $month, $year, $separator, $hour, $minute );
		?>
		</div>
		<input type="hidden" id="ss" '<?php ($multi ? print ('name="ss[]" ')  : print ('name="ss" ') )?>' value="<?php echo $ss?>" />

		<?php
		
		if ($multi)
			return;
		
		echo "\n\n";
		foreach ( array ('mm', 'jj', 'aa', 'hh', 'mn' ) as $timeunit ) {
			echo '<input type="hidden" id="hidden_' . $timeunit . '"  ' . ($multi ? 'name="hidden_' . $timeunit . '[]" ' : 'name="hidden_' . $timeunit . '" ') . ' name="hidden_' . $timeunit . '" value="' . $$timeunit . '" />' . "\n";
			$cur_timeunit = 'cur_' . $timeunit;
			echo '<input type="hidden" id="' . $cur_timeunit . '" name="' . $cur_timeunit . '" value="' . $$cur_timeunit . '" />' . "\n";
		}
	}
	
	/*
	 * 
	 * Check if User is Admin of a Blog
	 * 
	 * @global $wpdb
	 * @return true if user is admin of blog
	 * 
	 */
	
	protected function get_admin_users_for_blog($user_id, $blog_id) {
		global $wpdb;
		$key = "wp_" . $blog_id . "_capabilities";
		$cap = $wpdb->get_col ( $wpdb->prepare ( "SELECT meta_value from $wpdb->usermeta AS um WHERE um.meta_key ='" . $key . "' AND user_id=$user_id" ), 0 );
		if (strpos ( $cap [0], "administrator" ))
			return true;
		else
			return false;
	}
	
	/**
	 * Display recent learning diary tasks to solve
	 *
	 * @since unknown
	 */
	
	protected function choose_users_for_task($task_id = 0, $post_data = array()) {
		global $post;
		global $current_user;
		global $wpdb;
		
		$allusers = array ();
		$alluserids = array ();
		$groups = array ();
		$user_access_meta = "";
		$group_access_meta = "";
		$users_of_blog = array ();
		$users_of_blog_ids = array ();
		$checkedusers = array ();
		$checkedgroups = array ();
		$disabledusers = array ();
		$all_user_ids = array ();
		$all_display_names = array ();
		$all_user_emails = array ();
		
		$users_of_blog = get_users_of_blog ();
		
		//probaly deprecated for WP >= 3.0 (http://codex.wordpress.org/Installing_Multiple_Blogs)
		foreach ( $users_of_blog as $the_blog_user ) {
			$users_of_blog_ids [] = $the_blog_user->user_id;
		}
		
		//echo '<div class="misc-pub-section">';
		

		$groups = groups_get_user_groups ( $current_user->ID );
		
		$user_access_meta = "";
		
		if ($task_id) {
			//get users of the task
			$results_users = $wpdb->get_results ( "SELECT * FROM `" . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . "` WHERE task_id = $task_id " );
			
			if ($results_users) {
				//$sent_to = '';
				foreach ( $results_users as $the_result ) {
					$checkedusers [] = $the_result->user_id;
					if ($the_result->task_status == 'draft' || $the_result->task_status == 'publish') {
						$disabledusers [] = $the_result->user_id;
					}
				}
			
		//$sent_to = 'specific_users';
			} else {
				//$sent_to = 'nobody';
			}
			//get groups of the task
			$results_groups = $wpdb->get_results ( "SELECT * FROM `" . $wpdb->base_prefix . self::META_TASK_TABLE_NAME . "` WHERE task_id = $task_id AND meta_key = 'group'" );
			
			//if($results_groups){
			foreach ( $results_groups as $the_result ) {
				//if( $the_result->meta_key=="group" )
				$checkedgroups [] = $the_result->meta_value;
			}
		
		//$sent_to = 'specific_groups';
		//}
		

		} else if ($post_data) {
			//$sent_to = $post_data["sent_to"];
			//if($post_data["sent_to"]=="specific_users"){
			//	$checkedusers = array_keys($post_data["checkedusers"]["specific_users"]);
			//}else{
			if (isset ( $_POST ["task_id"] )) {
				$task_id = ( int ) $_POST ["task_id"];
				
				$results_users = $wpdb->get_results ( "SELECT * FROM `" . $wpdb->base_prefix . self::USER_TASK_TABLE_NAME . "` WHERE task_id = $task_id " );
				
				foreach ( $results_users as $the_result ) {
					if ($the_result->task_status == 'draft' || $the_result->task_status == 'publish') {
						$disabledusers [] = $the_result->user_id;
					}
				}
			}
			if($post_data ["checkedusers"] ["specific_users"]){
				$checkedusers = array_keys ( $post_data ["checkedusers"] ["specific_users"] );
			}
			//$checkedusers = array_keys($post_data["checkedusers"]["specific_groups"]);
			if($post_data ["checkedgroups"]) {
				$checkedgroups = array_keys ( $post_data ["checkedgroups"] );
			}
		//}
		

		}
		
		if ($groups) {
			?>
<!--		<select name='learning-diary-tasks-visible-for' id='learning-diary-tasks-visible-for' class=''>
				<option value='nobody' <?php //if ($sent_to=='nobody'){ echo " selected='selected'";} 			?>><?php // _e("Bitte w&auml;hlen"); 			?></option>
				<option value='specific_groups' <?php //if ($sent_to=='specific_groups'){ echo " selected='selected'";} 			?> ><?php // _e("folgende Gruppen") 			?></option>
				<option value='specific_users' <?php //if ($sent_to=='specific_users'){ echo " selected='selected'";} 			?> ><?php // _e("folgende Lernende") 			?></option>
			</select>
	-->
<?php
		}
		?>
<!--	</div> -->
		<div>

		<p><?php _e('Lernende aus folgenden Gruppen:', 'bp_learning_diary')?></p>

		<div id="learning-diary-tasks-groups-of-user"><br>
		<table cellspacing="0" class="widefat">
		
		<?php
		
		/*
		 * 
		 * Show Groups (and Users who are in one of the Groups AND who are Admin of a Blog(?) Group!)
		 * 
		 */
		
		$i = 0;
		
		foreach ( $groups ['groups'] as $the_group ) {
			$i ++;
			$group = new BP_Groups_Group ( $the_group );
			$group_name = $group->name;
			
			$group_members = BP_Groups_Member::get_all_for_group ( $the_group, false, false, false, true ); //members AND admins of the group
			

			$group_has_pupils = false; //has students
			

			foreach ( $group_members ["members"] as $the_group_member ) {
				if (get_user_meta ( $the_group_member->user_id, 'learning_diary_tasks_student', true )) {
					$group_has_pupils = true;
					break;
				}
			}
			
			if ($group_has_pupils) { ?> 
				<tr>
				<td align="center" width="8"
					class="learning-diary-tasks-groups-of-users-td">
					<input class="learning-diary-tasks-groups-of-users-checkbox" 
							type="checkbox"
							name="learning-diary-tasks-user-post-access-setting[<?php echo $the_group?>]"
							value="allow_<?php echo $the_group?>"
							<?php if (in_array ( $the_group, $checkedgroups )) echo " checked";?> 
					/>
				</td>

				<td>
				<div id="learning-diary-tasks-show-members-of-group-<?php echo $i?>"
					class="learning-diary-tasks-show-members-of-group"
					name="group_id_<?php echo $the_group?>">
				
				<strong><?php echo $group_name?></strong>

		<!--		<span> (
							<a 	id="learning-diary-tasks-groups-add-toggle-<?php //echo $i 				?>" 
								href="#learning-diary-tasks-groups-add"
								class="hide-if-no-js learning-diary-tasks-groups-adder" 
								tabindex="2">
								<?php //_e('Lernende anzeigen') 				?>
							</a>
							)
						</span>
						<div id="bpsa-users-of-group-table">
						<table cellspacing="0" id="learning-diary-tasks-groups-add-<?php //echo $i 				?>" class="hidden__">
		
		-->		<?php
				
				//get an array with ids of the group admins
				/*		$groupadmins = array();
				
				foreach ($group->admins as $the_admin_of_the_group){
					$groupadmins[] = $the_admin_of_the_group->user_id;
				}
		*/
				//prepare/unset for sorting
				$user_ids = array ();
				$display_names = array ();
				$user_emails = array ();
				
				foreach ( $group_members ['members'] as $member ) {
					$user_ids [] = $member->user_id;
					$display_names [] = $member->display_name;
					$user_emails [] = $member->user_email;
				}
				
				//sorting according to display_names (asc)
				array_multisort ( $display_names, $user_ids, $user_emails );
				
				//go through every user of the group and check if he is student
				foreach ( $user_ids as $key => $user_id ) {
					if (! in_array ( $user_id, $all_user_ids )) {
						if (get_user_meta ( $user_id, 'learning_diary_tasks_student', true )) {
							$all_user_ids [] = $user_id;
							$all_display_names [] = $display_names [$key];
							$all_user_emails [] = $user_emails [$key];
						
		//$allusers[] = $the_group_member;
						}
					}
				}
				
				//				$blogs_of_user = get_blogs_of_user($user_id);
				

				//User is admin of a blog?
				//				foreach($blogs_of_user as $the_blog_of_user){
				//					$is_admin_of_blog = $this->get_admin_users_for_blog($user_id, $the_blog_of_user->userblog_id);
				//					if($is_admin_of_blog)
				//						break;
				//				}		
				

				//				if ($is_admin_of_blog && $current_user->ID!=$user_id && get_usermeta($user_id, 'learning_diary_tasks_student')){
				?>
			<!--		<tr>
							<td align="center">
								<input 	class="allow_<?php // echo $the_group 				?> learning-diary-tasks-user-post-access-setting-specific-groups-checkbox" 
										type="checkbox" 
										name="learning-diary-tasks-user-post-access-setting-specific-groups[<?php //echo $user_id 				?>]" 
										value="allow" 
										<?php //if (in_array($user_id, $checkedusers)) echo " checked"; 				?>
								/>
							</td>
							<td class="first b b-posts"
								title="<?php //echo $display_names[$key]				?>&nbsp;&lt;<?php //echo $user_emails[$key] 				?>&gt;" >
								<?php //echo $display_names[$key];				?>
								<br>
								<font style="color:gray;">&lt;<?php //echo $user_emails[$key] 				?>&gt;
								<?php //if (in_array($user_id, $groupadmins)) echo "<br><i>Administrator/in dieser Gruppe</i>";				?>
								</font>
							</td>
						</tr>
				-->		<?php
				//}			
				//	}
				?>
		<!--				</table>
						</div>
			--></div>
		</td>
	</tr>
				<?php
			} // endif $group_has_pupils
		}
		
		?>
		
		</table>
		</div>

		<p><?php _e('Lernende:', 'bp_learning_diary')?></p>

		<div id="learning-diary-tasks-all-users"><br>
		<table class='widefat'>
		
		<?php
		
		/*
		 * 
		 * Show Users (Users who are in one of the Groups and who are Admin of the Blog)
		 * 
		 */
		
		//sort users catched above
		array_multisort ( $all_display_names, $all_user_ids, $all_user_emails );
		
		foreach ( $all_user_ids as $key => $user_id ) {
			$blogs_of_user = get_blogs_of_user ( $user_id );
			
			foreach ( $blogs_of_user as $the_blog_of_user ) {
				$is_admin_of_blog = $this->get_admin_users_for_blog ( $user_id, $the_blog_of_user->userblog_id );
				if ($is_admin_of_blog)
					break;
			}
			
			//	if ($is_admin_of_blog && $current_user->ID!=$user_id){ //Check if user is admin of a blog and if user isn't current user
			if ($is_admin_of_blog) { ?>
				<tr>
				<td align="center" width='8'>
					<input type="checkbox"
							name="learning-diary-tasks-user-post-access-setting-specific-users[<?php echo $user_id; ?>]"
							value="allow_<?php echo $user_id; ?>"
							class="learning-diary-tasks-users-checkbox"
							<?php if (in_array ( $user_id, $checkedusers )) echo " checked"; ?>
							<?php if (in_array ( $user_id, $disabledusers )) echo " disabled  "; ?> 
					/> 
					<?php if (in_array ( $user_id, $disabledusers )) : ?>
						<input type="hidden"
								name="learning-diary-tasks-user-post-access-setting-specific-users[<?php echo $user_id?>]"
								value="allow_<?php echo $user_id?>"
						/>
					<?php endif; ?>
				</td>
		<td <?php //if (in_array($user_id, $users_of_blog_ids)) echo " style='color:gray' "; ?>
			title="<?php echo $all_display_names [$key]?>&nbsp;&lt;<?php echo $all_user_emails [$key]?>&gt;"
			class="learning-diary-tasks-show-members-of-all-groups"
			name="user_id_<?php echo $user_id?>">
			
			<?php echo $all_display_names [$key]?>
			<br>
			<font style="color: gray">
				<?php echo "&lt;" . $all_user_emails [$key] . "&gt;"; ?>
				<?php if (in_array ( $user_id, $users_of_blog_ids )) echo "<br><i>" . __('Das bin ich.', 'bp_learning_diary') . "</i>"; ?>
			</font>
		</td>
		</tr>
		<?php
			}
		}
		?>
	</table>
	</div>
	</div>
	<?php
	}
}

?>