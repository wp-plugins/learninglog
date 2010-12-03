<?php 


/*
 * 
 * Add default user data during blog registration
 * 
 */

Class LearningDiaryTasksRegister Extends LearningDiaryTasks {
	function __construct(){ //Constructor
	//Actions
		#Set usermeta while activating
		add_action( 'bp_core_activated_user' , array( $this , 'activate_usermeta' ) , 10 , 3 );
		#Validate user input
		add_action( 'bp_signup_validate', array( $this , 'register_errors' ) );
		#Show field in registration form
		add_action( 'bp_after_signup_profile_fields' , array( $this , 'add_fields' ),10 );
		#Set default usermeta options
		add_action( 'wpmu_new_blog', array( $this , 'update_usermeta' ) , 10 , 2 );
		#Check "Signup With Blog" by default and hide the checkbox 
		add_action( 'bp_after_signup_profile_fields', array( $this , 'always_add_blogdetails' ) , 10);
		#Check "Signup With Blog" by default and hide the checkbox 
		add_action( 'wpmu_delete_user', array( $this , 'delete_user' ) , 10, 1);
		#Add user to group when account is activated - group is specified by a teacher in bulk_import_blogs
		add_action( 'wpmu_activate_user', 'join_group', 12, 3 );
		#delete default first page permanently
		add_action( 'wpmu_activate_blog', array( $this, 'delete_page'), 10 , 5 );
		
		
	//Filters
		add_filter( 'bp_signup_usermeta' , array($this,'signup_user') , 10 , 1 );
		
	}

	/*
	 * Change Settings on post.php / post-new.php, the dashboard and the templates sidebar and the Date for the_time function
	 */
	
	public function update_usermeta($blog_id, $user_id){
		/*
		 * update blog option
		 */
		
		//add sidebar widget "pages" 
		update_blog_option($blog_id, "sidebars_widgets", array("sidebar-1" => array("pages","learning-diary-tasks-widget")));
		
		//add european date format
		update_blog_option($blog_id, "time_format", "d.m.Y H:i");
		
		/*
		 * update usermeta
		 */
		
		//Change the Screen layout on the Dashboard (only one column)
		update_user_meta( $user_id, 'screen_layout_dashboard', 1 );
		
		//set order of metaboxes in post-edit.php and post-new.php
		$metaboxorder_post = array ( 
			"side"	=> "submitdiv,bp_post_access_control_edit_post,current_task,tagsdiv-post_tag",
			"normal" => "categorydiv,postexcerpt,trackbacksdiv,postcustom,commentstatusdiv,slugdiv,authordiv,commentsdiv,revisionsdiv",
			"advanced" => ""
		); 
		update_user_meta( $user_id, 'wp_'.$blog_id.'_metaboxorder_post', $metaboxorder_post );
		
		//hide metaboxes to simplify editing and creating posts
		$metaboxhidden_post = array (
			"tagsdiv-post_tag",
			"postexcerpt",
			"trackbacksdiv",
			"postcustom",
			"commentstatusdiv",
			"slugdiv",
			"authordiv",
			"commentsdiv"
		);
		
		update_user_meta( $user_id, 'metaboxhidden_post', $metaboxhidden_post );
		
		$metaboxhidden_page = array (
			"postcustom",
			"slugdiv"
		);
		
		update_user_meta( $user_id, 'metaboxhidden_page', $metaboxhidden_page );			
		
		//hide some table cols in category and tag section
		$catcol = array(
			"description",
			"slug",
			"posts"
		);
		
		update_user_meta( $user_id, 'manageedit-categorycolumnshidden', $catcol );
	
		update_user_meta( $user_id, 'manageedit-post_tagcolumnshidden', $catcol );
		
		/*
		 * Close post boxes on dashboard
		 */
		
		$closedpostboxes = array (
			'show_teacher_dashboard_functions',
			'learning_diary_tasks_dashboard_membership_requests',
			'show_teacher_dashboard_tutorials',
			'dashboard_recent_comments'
		);
		
		update_user_meta( $user_id, 'closedpostboxes_dashboard', $closedpostboxes );
		
		/*
		 * Change order of post boxes on dashboard
		 */
		
		$metaboxorderdashboard = array (
			"normal" => "show_teacher_dashboard_first_steps,
				show_teacher_dashboard_functions,show_teacher_dashboard_tutorials,
				learning_diary_tasks_dashboard_widget_get_tasks,
				dashboard_recent_comments,
				learning_diary_tasks_dashboard_membership_requests",
			"side" => "",
			"column3" => "",
			"column4" => ""
		);

		update_user_meta( $user_id, 'meta-box-order_dashboard', $metaboxorderdashboard );
		
		
	}
	
	
	/*
	 * Join group while bulk importing users
	 */
	

	public function join_group($user_id, $key, $user = 0){
		global $wpdb, $bp;
		
		//join forum
		//$this->join_forum($user_id);
		
		if(!$user['meta']['join_groups'])
			return;	

		// get the groups
		$join_groups = $user['meta']['join_groups'];
		// get the user id
		$userid = $user['user_id'];
		
		
		foreach ($join_groups as $the_goup_id) {
			
			
			// see if the user is already in the group
			if ( !BP_Groups_Member::check_is_member( $userid, $the_goup_id ) ) {
				// make sure the user isn't banned from the group!
				if ( !groups_is_user_banned( $userid, $the_goup_id ) ) {
					// add the group already!
				      if ( groups_check_user_has_invite( $userid, $the_goup_id ) ) {
						groups_delete_invite( $userid, $the_goup_id );
					}
					  
					 $new_member = new BP_Groups_Member;
					 $new_member->group_id = $the_goup_id;
					 $new_member->inviter_id = 0;
					 $new_member->user_id = $userid;
					 $new_member->is_admin = 0;
					 $new_member->user_title = '';
					 $new_member->date_modified = gmdate('Y-m-d H:i:s');
					 $new_member->is_confirmed = 1;
					 
					if ( !$new_member->save() ) {
					 	return false;
					}
						
					// Should I add this to the activity stream?  left off for now
					 
					/* Modify group meta */
					groups_update_groupmeta( $the_goup_id, 'total_member_count', (int) groups_get_groupmeta( $the_goup_id, 'total_member_count') + 1 );
					groups_update_groupmeta( $the_goup_id, 'last_activity', gmdate('Y-m-d H:i:s') );
				}
			}
		}
	}
	
	/*
	 * "Signup With Blog" should always be checked. This hack (sorry for that ;-) will check it, without touching register.php in the buddypress template.
	 */
	
	public function always_add_blogdetails(){
		//check the checkbox
		$_POST['signup_with_blog'] = 1;
		//hide checkbox and text
		?> 
		<style type="text/css">
			#blog-details-section #signup_with_blog, #blog-details-section p {
				display:none;
			}
		</style>
		<?php 
	}
	
	/*
	 * Add fields on register screen:
	 *  1. Add User-Role checkboxes
	 *  2. Add AGB checkbox
	 */
	
	public function add_fields(){
		
		//HANDLING
		//$checked = array();
		$checked_student_or_teacher = $_POST['learning_diary_tasks_student_or_teacher'];
		if(!$checked_student_or_teacher)
			$checked_student_or_teacher = array();
			
		$checked_agb = $_POST['agb'];

		//HTML
		
		?>
		<div class="register-section" id="profile-details-section">
			<h4><?php _e('Lerntagebuchoptionen', 'bp_learning_diary')?></h4>
			<div class="editfield">
				<div class="checkbox">
					
					<span class="label"><?php _e( 'Role' ) ?> <?php _e( '(required)', 'buddypress' ) ?></span>
					<div><?php _e('(Kann sp&auml;ter ge&auml;ndert werden.)', 'bp_learning_diary')?></div>
					<?php do_action( 'bp_learning_diary_tasks_student_or_teacher_errors' ) ?>
					<label id="label_input_stud"><input type="checkbox" name="learning_diary_tasks_student_or_teacher[]" id="learning_diary_tasks_student" value="2" <?php if( in_array( 2 , $checked_student_or_teacher ) ) { echo "checked"; } ?>>
						<?php _e('Ich bin Lernende/r.', 'bp_learning_diary')?>
					</label>								
					<label id="label_input_teach"><input type="checkbox" name="learning_diary_tasks_student_or_teacher[]" id="learning_diary_tasks_teacher" value="1" <?php if( in_array( 1 , $checked_student_or_teacher ) ) { echo "checked"; } ?>>
						<?php _e('Ich bin Lehrende/r.', 'bp_learning_diary')?>
					</label>
				</div>
				<p class="description"></p>
			</div>
			<div class="editfield">
				<div class="checkbox">
					
					<span class="label"><?php _e( 'Nutzungsbestimmungen', 'bp_learning_diary' ) ?> <?php _e( '(required)', 'buddypress' ) ?></span>
					<?php do_action( 'bp_agb_errors' ) ?>
					<label id="label_input_agb"><input type="checkbox" name="agb" id="agb" value="accepted" <?php if( isset( $checked_agb ) ) { echo "checked"; } ?>>
						<?php printf(
							__('Ich bin mit den %1$s Nutzungsbestimmungen%2$s einverstanden.', 'bp_learning_diary'),
							'<a href="http://' . get_blog_details(1)->domain.get_blog_details(1)->path . 'nutzungsbestimmungen/" target=_blank>',
							'</a>'
						)?>
					</label>
				</div>
				<p class="description"></p>
			</div>
		</div>
		<?php 
		
	}
	
	public function activate_usermeta ( $user_id, $key, $user ) {
		//var_dump($user);die();
		update_user_meta( $user_id , 'learning_diary_tasks_student' , $user['meta']['learning_diary_tasks_student'] );
		update_user_meta( $user_id , 'learning_diary_tasks_teacher' , $user['meta']['learning_diary_tasks_teacher'] );
		update_user_meta( $user_id , 'ld_send_email_on_new_task' , $user['meta']['ld_send_email_on_new_task'] );		
		
		if ( $user['meta']['first_name'] ) {
			update_user_meta( $user_id , 'first_name' , $user['meta']['first_name'] );
			update_user_meta( $user_id , 'last_name' , $user['meta']['last_name'] );
			update_user_meta( $user_id , 'display_name', $user['meta']['first_name'] . " " . $user['meta']['last_name'] );
		}
		
		update_user_meta( $user_id , 'agb' , 'accepted' );
		
		if($_GET["batchimportusers"]=="true")
			xprofile_set_field_data(1,$user_id,$user['meta']['first_name']." ".$user['meta']['last_name']);
		//
		$this->join_group( $user_id, $key, $user );
		
		
		
	}
	
	public function register_errors(){
		global $bp;
		if(empty($_POST['learning_diary_tasks_student_or_teacher'])){
			$bp->signup->errors['learning_diary_tasks_student_or_teacher'] = __("This is a required field","buddypress");
		}
		if(empty($_POST['agb'])){
			$bp->signup->errors['agb'] = __("This is a required field","buddypress");
		}
	}
		
	public function signup_user( $usermeta ){
		
		$data = $_POST['learning_diary_tasks_student_or_teacher'];
		
		if($data){
			$usermeta['ld_send_email_on_new_task'] = 0;
			
			if( in_array( 1 , $data ) ) 
				$usermeta['learning_diary_tasks_teacher'] = 1;
			else
				$usermeta['learning_diary_tasks_teacher'] = 0;	
			
			if( in_array( 2 , $data ) ) {
				$usermeta['learning_diary_tasks_student'] = 1;
				$usermeta['ld_send_email_on_new_task'] = 1;
			}else{
				$usermeta['learning_diary_tasks_student'] = 0;
			}
			
			if($_POST['agb']=='accepted')
				$usermeta['agb'] = 'accepted';
		}
				
		return $usermeta;
	}
	
	/*
	 * join forum group
	 */
/*	public function join_forum($user_id)
	{
		global $wpdb;

		if( !$group_id = $wpdb->get_var("SELECT id FROM wp_forum_usergroups WHERE name='lerntagebuch.ch'") )
			$group_id = 1;

		$data = array('user_id' => $user_id, 'group' => $group_id);

		$wpdb->insert( 'wp_forum_usergroup2user', $data );
	}
*/	
	/*
	 * Sets tasks to "deleted" when a user is deleted
	 */	
	
	public function delete_user($user_id){
		
		global $wpdb;
				
		if ($user_id) {
			//update the USER_TASK_TABLENAME, change tasks of user to deleted
			$data = array(
				"task_status" => "deleted"
			);
				
			$wpdb->update(
				$wpdb->base_prefix . self::USER_TASK_TABLE_NAME, 
				$data,
				array( "user_id" => $user_id)
			);
		}
	}

	/*
	 * delete default first page
	 */
	
	public function delete_page($blog_id, $user_id, $password, $signuptitle, $meta){
		
		switch_to_blog($blog_id);
					
		wp_delete_post(2,true);
			
		restore_current_blog();
	
	}
}