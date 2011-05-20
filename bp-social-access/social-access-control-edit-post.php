<?php


//TODO: Internationalize

Class BP_Post_Access_Control_Edit_Post Extends BP_Post_Access_Control {

	/*
	 * 
	 * Calls edit_post function, adds Meta Box into post.php and post-new.php
	 * 
	 */
	
	function edit_post_box(){
		
		add_meta_box( 	'bp_post_access_control_edit_post', 
						__( 'Sichtbarkeit', 'bp_learning_diary' ), 
	                	array($bp_post_access_control_edit_post = new BP_Post_Access_Control_Edit_Post,'edit_post'), 
	                	'post', 
	                	'side'
	                );

		add_meta_box( 	'bp_post_access_control_edit_post', 
						__( 'Sichtbarkeit', 'bp_learning_diary' ), 
	                	array($bp_post_access_control_edit_post = new BP_Post_Access_Control_Edit_Post,'edit_post'), 
	                	'page', 
	                	'side'
	                );

	}
	
	/*
	 * 
	 * Includes jQuery in head of post.php and post-new.php
	 * 
	 */
	
	function social_access_show_groups(){
		wp_enqueue_script( 
			"bp_post_access_control", 
			(WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . "/bp-social-access/js/bp-socialaccess.js"), array( 'jquery' ) );	
	}
	
	/*
	 * 
	 * edit form for post.php and post-new.php
	 * 
	 */
	
	function edit_post() {
		global $post;
		global $current_user;
		
		$allusers = array();
		$alluserids = array();
		(int) $i=0;
		$groups = array();
		$user_access_meta = "";	
		$group_access_meta = "";
		$users_of_blog = array();
		$users_of_blog_ids = array();
		
		$mapto_group_meta = "";
		
		$users_of_blog = get_users_of_blog();
		foreach($users_of_blog as $the_blog_user){
			$users_of_blog_ids[]=$the_blog_user->user_id;
		}
		
		
		?>
		
		<div class="misc-pub-section">
		
		<?php 
		$groups = groups_get_user_groups($current_user->ID,0,100);
		
		
		if($groups){
			$user_access_meta = get_post_meta($post->ID, '_bpac_visible_for',true);
			$group_access_meta = get_post_meta($post->ID, '_bpac_users_of_group_have_access');
			$mapto_groups_meta = get_post_meta($post->ID, '_bp_ld_mapto_groups');
			?>
			<label for="bpsa-visible-for"><?php _e("Sichtbar f&uuml;r:","bp_learning_diary")?></label>
			<select name='bpsa-visible-for' id='bpsa-visible-for'>
				<option value='all' <?php if ($user_access_meta=='all'){ echo " selected='selected'"; }?>><?php _e('vollständig öffentlich', 'bp_learning_diary') ?></option>
				<?php 
				if(get_blog_option(1,'BP_Post_Access_Control_loggedin_users_option_for_visibility')){ 
				//Show option to choose loggedin users only if super admin has activated the option (only superadmin can edit this option)
				?>
					<option value='loggedin_users' <?php if ($user_access_meta=='loggedin_users') { echo " selected='selected'"; } ?> > <?php _e('alle angemeldeten Nutzende','bp_learning_diary') ?></option>
				<?php 
				} 
				?>
				<option value='specific_groups' <?php if ($user_access_meta=='specific_groups') { echo " selected='selected'"; } ?> > <?php _e('spezifische Gruppen','bp_learning_diary') ?></option>
				<option value='specific_users' <?php if ($user_access_meta=='specific_users'){ echo " selected='selected'"; } ?> > <?php _e('spezifische Nutzende','bp_learning_diary') ?></option>
			</select>		
		<?php } ?>
		
		</div>
		<div>
			

		<div id="bpsa-groups-of-user" class="hidden">
		<br>
		<table  cellspacing="0" class="widefat">
		<?php 
		
		foreach ($groups['groups'] as $the_group){
			$i++;
			$group = new BP_Groups_Group( $the_group );
			$group_name = $group->name;
			?>
			<tr class='bpsa-groups-of-users-tr'>
			<td align="center"  class='bpsa-groups-of-users-td'>
				<input 	class='bpsa-groups-of-users-checkbox' 
						type="checkbox" 
						name="bpsa-user-post-access-setting[<?php echo $the_group ?>] " 
						value="allow_<?php echo $the_group ?>" <?php if (in_array($the_group,$group_access_meta)) { echo " checked"; }?>
				/>
			</td>
			<td>
			<div 	id='bpsa-show-members-of-group-<?php echo $i ?>' 
					class='bpsa-show-members-of-group'><strong><?php echo $group_name?></strong>
				<span> (<a id='bpsa-groups-add-toggle-<?php echo $i ?>' href='#bpsa-groups-add' class='hide-if-no-js bpsa-groups-adder' tabindex='2'>
						<?php _e('Nutzende anzeigen', 'bp_learning_diary') ?></a>)
				</span>
			<div id="bpsa-users-of-group-table">
			<table cellspacing="0" id="bpsa-groups-add-<?php echo $i ?>" class="hidden">
			<?php 
	
			$group_members = BP_Groups_Member::get_all_for_group( $the_group, 0, 100, false, true );
			
			//prepare/unset for sorting
			$user_ids = array();
			$display_names = array();
			$user_emails = array();
			
			foreach($group_members['members'] as $member) {
				$user_ids[] = $member->user_id;
				$display_names[] = $member->display_name;
				$user_emails[] = $member->user_email;
			}
			
			//sorting according to display_names (asc)
			array_multisort($display_names, $user_ids, $user_emails);
			
			$all_user_ids = array();
			
			foreach ($user_ids as $key => $user_id){
				if( !in_array($user_id, $all_user_ids) ){
					$all_user_ids[] = $user_id;
					$all_display_names[] = $display_names[$key];
					$all_user_emails[] = $user_emails[$key];
				}
				?>
											
				<tr>
				<td align="center">
					<?php 
						if (in_array($user_id, $users_of_blog_ids))
							$class="allow_me";
						else
							$class="allow_$the_group";
					?>
					<input class="<?php echo $class ?> bpsa-user-post-access-setting-specific-groups-checkbox" type="checkbox" name="bpsa-user-post-access-setting-specific-groups[<?php echo $user_id ?>]" value="allow" 
						<?php if (in_array($user_id, $users_of_blog_ids)) echo "disabled='disabled'";
						$per_post_setting = $this->get_user_post_setting($user_id, $post->ID);
						//Check if user has access to the post ($per_post_setting)
						//Check if current post has any access settings and group is selected-> if not: nobody will be selected (get_post_meta())
						if ($per_post_setting=='allow' && get_post_meta($post->ID, '_bpac_visible_for') && in_array($the_group,$group_access_meta))
							echo " checked";
						?>
					/>
				</td>
				<td <?php if (in_array($user_id, $users_of_blog_ids)) echo " style='color:gray' "; ?>
					class="first b b-posts"
					title="<?php echo $display_names[$key]?>&nbsp;&lt;<?php echo $user_emails[$key] ?>&gt;" >
					<?php echo $display_names[$key]?><br /><font style='color:gray'>
					<?php echo "&lt;" . $user_emails[$key] . "&gt;"; 
					if (in_array($user_id, $users_of_blog_ids)) echo "<br /><i>" . __('Blog-Besitzer/in', 'bp_learning_diary') . "</i>"; ?>
					</font>
				</td>
				</tr>
	  			<?php 
			} //end foreach ?>
	
			</table>
			</div> <!-- id="bpsa-users-of-group-table" -->
			</div>
			</td>
			</tr>
  			<?php 
		} // end foreach ?>
		
		</table>
		
		</div>
		
		<div id="bpsa-all-users" class="hidden">
		<br>
		<table class='widefat'>
		
  		<?php 

		//sort users catched above
		array_multisort($all_display_names, $all_user_ids, $all_user_emails);

		foreach ($all_user_ids as $key => $user_id) {
			$per_post_setting = $this->get_user_post_setting($user_id, $post->ID);
			?>
			<tr>
			<td align="center" >
				<input type="checkbox" name="bpsa-user-post-access-setting-specific-users[<?php echo $user_id ?>]" value="allow"
					<?php if (in_array($user_id, $users_of_blog_ids)) echo "disabled='true' ";
					//Check if user has access to the post ($per_post_setting)
					//Check if current post has any access settings -> if not: nobody will be selected
					if ($per_post_setting=='allow' && get_post_meta($post->ID, '_bpac_visible_for')) echo " checked";
					?>
				/>
			</td>
			
			<td <?php if (in_array($user_id, $users_of_blog_ids)) echo " style='color:gray' "; ?>
				title="<?php echo $all_display_names[$key]?>&nbsp;&lt;<?php echo $all_user_emails[$key] ?>&gt;" >
				<?php echo $all_display_names[$key] . "<br /><font style='color:gray'>" . "&lt;" . $all_user_emails[$key] . "&gt;";
				if (in_array($user_id, $users_of_blog_ids)) echo "<br /><i>" . __('Blog-Besitzer/in', 'bp_learning_diary') . "</i>"; ?>
				</font>
			</td>
			</tr>
			<?php 
		} // end foreach ?>
		
		</table>
		</div>

		<div class="misc-pub-section" >
			<br>
			<?php _e('Folgenden Gruppen zuordnen:', 'bp_learning_diary')?>
		</div>
		
		<div id="bpsa-groups-of-user-mapto" >
		
		
		<table  cellspacing="0" class="widefat">
		<?php 
		
		//show (all) groups of user to map post to
		foreach ($groups['groups'] as $the_group){
	 
			$group = new BP_Groups_Group( $the_group );
			
			$group_name = $group->name;
			
			?>
			<tr class='bpsa-groups-of-users-tr'>
			<td align="center" width='8' class='bpsa-groups-of-users-td' >
				<input 	class='bpsa-groups-of-users-checkbox' 
						type="checkbox" 
						name="bp_ld_mapto_groups[<?php echo $the_group ?>] " 
						value="allow_<?php echo $the_group ?>" 
						<?php if (in_array($the_group,$mapto_groups_meta)) echo " checked='checked'"; ?>		
				/>
			</td>
			<td>
				<div >
					<strong><?php echo $group_name?></strong>
				</div>
			</td>
			</tr>
		
		<?php }//end foreach group ?>
		
		</table>
		</div>
		
		
		</div>
		<?php
} //end function
	
	/*
	 * 
	 * save/update post access settings
	 * 
	 */
	
	function save_post($postid) {
		
		$user_post_access_setting = array();
		$bpsa_visible_for_groups = array();
		$users = array();
		$groups = array();
		
		$bpsa_visible_for = $_POST["bpsa-visible-for"];
		$bpsa_visible_for_groups = $_POST["bpsa-user-post-access-setting"];
		$bp_ld_mapto_groups = $_POST["bp_ld_mapto_groups"];
		
		if(!isset($_POST["bpsa-visible-for"])){
			return false;
		}else{
			switch ($bpsa_visible_for){
			case "all":
				$user_post_access_setting = $_POST["bpsa-visible-for"];
				break;
			case "loggedin_users":
				$user_post_access_setting = $_POST["bpsa-visible-for"];
				break;
			case "specific_groups":
				$user_post_access_setting = $_POST["bpsa-user-post-access-setting-specific-groups"];
				break;
			case "specific_users":
				$user_post_access_setting = $_POST["bpsa-user-post-access-setting-specific-users"];
				break;
			}
		}
		$users = array_keys($user_post_access_setting);
		
		delete_post_meta($postid, '_bpac_user_has_access');
		delete_post_meta($postid, '_bpac_visible_for');
		delete_post_meta($postid, '_bpac_users_of_group_have_access');
		delete_post_meta($postid, '_bp_ld_mapto_groups');
		
		if($bpsa_visible_for){
			add_post_meta($postid, '_bpac_visible_for', $bpsa_visible_for);
		}
		
		if ($bpsa_visible_for == "specific_groups")
			$groups= array_keys($bpsa_visible_for_groups);
		
		if($bpsa_visible_for != "specific_groups") {
			$mapto_groups = array_keys($bp_ld_mapto_groups);
		}
			
		if($mapto_groups){
			foreach ($mapto_groups as $the_group_id) {
				add_post_meta($postid, '_bp_ld_mapto_groups', $the_group_id);			
			}
		}
		
		if($groups){
			foreach ($groups as $the_group_id) {
				add_post_meta($postid, '_bpac_users_of_group_have_access', $the_group_id);
				add_post_meta($postid, '_bp_ld_mapto_groups', $the_group_id);			
			}
		}
		
		if($users){
			foreach ($users as $userid) {
				add_post_meta($postid, '_bpac_user_has_access', $userid);
			}
		}
		
		
		//TODO: Error Catching
		//Error Catching if
		// A: no users selected
		// B: no groups selected
	}
}

$bp_post_access_control_edit_post = new BP_Post_Access_Control_Edit_Post;

add_action("admin_print_scripts-post.php", array($bp_post_access_control_edit_post,'social_access_show_groups'), 10000);
add_action("admin_print_scripts-post-new.php", array($bp_post_access_control_edit_post,'social_access_show_groups'), 10000);
add_action('wp_insert_post', array($bp_post_access_control_edit_post,'save_post'), 10000);
add_action('admin_menu', array($bp_post_access_control_edit_post,'edit_post_box'), 10000);
?>