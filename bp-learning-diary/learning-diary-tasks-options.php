<?php

/*
 * Option Page for Learning Diary Tasks
 * - Add/Remove capability to create Tasks
 * - Add/Remove capability to solve Tasks
 */

Class LearningDiaryTasksOptions Extends LearningDiaryTasks {
	
	public function add_js()
	{
		wp_enqueue_script(
			"learning_diary_tasks_options", 
			//WP_PLUGIN_URL . "/" . basename(dirname( __FILE__ )) . "/js/bp-learning-diary-options.js", 
			WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/js/bp-learning-diary-options.js',
			array( 'jquery' ) 
		);
		
	}
	
	
	public function get_setup_options_page(){
		
		//$this->handle_action()
		$this->print_html( $this->handle_action() );
	}
	
	
	// --------------------------------------------------------------------
	
	private function print_html($errors) {
		global $current_user;
		
		//$page_uri = get_bloginfo( 'url' ) . "/wp-admin/options-general.php?page=" . self::LEARNING_DIARY_TASKS_OPTION_PLUGIN_BASENAME;
		$page_uri = "options-general.php?page=" . self::LEARNING_DIARY_TASKS_OPTION_PLUGIN_BASENAME;
		$checked_student = "";
		$checked_teacher = "";
		
		$checked_send_email = "";
		$disabled_send_email ="";
		$color_send_email = "#333";
		
		if(!$errors) {
			if(get_usermeta($current_user->ID,'learning_diary_tasks_student'))
				$checked_student = 'checked';
			else {
				$disabled_send_email =' disabled';
				$color_send_email = "#777";
			}
			
			if(get_usermeta($current_user->ID,'learning_diary_tasks_teacher'))
				$checked_teacher = 'checked';

			if(get_usermeta($current_user->ID,'ld_send_email_on_new_task'))
				$checked_send_email = 'checked';
		
		}else{
			if ($_POST['learning_diary_tasks_student'] == 'on')
				$checked_student = 'checked';
			else {
				$disabled_send_email =' disabled';
				$color_send_email = "#777";
			}
				
			if ($_POST['learning_diary_tasks_teacher'] == 'on')
				$checked_teacher = 'checked';

			if ($_POST['ld_send_email_on_new_task'] == 'on')
				$checked_send_email = 'checked';
			?>
			<div class="error"><?php _e('Du must mindestens eine Rolle (Lernende/r oder Lehrende/r) ausw&auml;hlen.', 'bp_learning_diary');?></div>
		<?php
		}//end if(!$errors) ... else ...
			
		if (strpos($_POST['submit'], __('Save Changes')) !== false AND !$errors) {	
		?>
		
			<div id="message" class="updated">
				<?php _e('Deine Angaben wurden gespeichert.', 'bp_learning_diary');?>
			</div>
		
		<?php 
		}
		
		?>
		<div class='wrap'>
			<?php screen_icon(); ?>
			<h2><?php _e('Einstellungen › Lerntagebuchoptionen', 'bp_learning_diary'); ?></h2>
			
			<form name='social_access_control' method='post' action='<?php echo $page_uri; ?>'>
		
				<h3><?php _e('Rollen / Einstellung zu den Aufgaben', 'bp_learning_diary'); ?></h3>
			
				<p><input name='learning_diary_tasks_student' id='learning_diary_tasks_student' type='checkbox' <?php echo $checked_student; ?> />
					 <?php _e('Ich bin <b>Lernende/r</b>. (Ich m&ouml;chte Aufgaben erhalten.)', 'bp_learning_diary'); ?>
				</p>
				<p><input name='learning_diary_tasks_teacher' type='checkbox' <?php echo $checked_teacher; ?> />
					 <?php _e('Ich bin <b>Lehrende/r</b>. (Ich m&ouml;chte Aufgaben stellen. Um Aufgaben stellen zu k&ouml;nnen, musst du von mindestens einer Gruppe Moderator oder Administrator sein.)', 'bp_learning_diary'); ?>
				</p>
				<br>
				
				<h3><?php _e('Benachrichtigungen', 'bp_learning_diary'); ?></h3>
			
				<p style="color:<?php echo $color_send_email?>"><input name='ld_send_email_on_new_task' id="ld_send_email_on_new_task" type='checkbox' <?php echo $checked_send_email . $disabled_send_email; ?> />
					 <?php _e('Ich m&ouml;chte beim Erhalt neuer Aufgaben per E-Mail benachrichtigt werden.', 'bp_learning_diary'); ?>
				</p>
			
				<p class="submit">
				<!--	<input type="submit" name="submit" value="<?php  _e('Update'); ?>" /> -->
				<input type="submit" name="submit" 	value="<?php _e('Save Changes') ?>" class="button-primary"/>
				</p>
			
			</form>
		</div>
		<?php 	
	
	}

	// --------------------------------------------------------------------

	private function handle_action() {
		global $current_user;
	
		if (strpos($_POST['submit'], __('Save Changes')) !== false) {
			
			if($_POST['learning_diary_tasks_student'] != 'on' AND $_POST['learning_diary_tasks_teacher'] != 'on') {
				return true;
			}	
					
			if ($_POST['learning_diary_tasks_student'] == 'on')
				update_usermeta($current_user->ID,'learning_diary_tasks_student', 1);
			else
				update_usermeta($current_user->ID,'learning_diary_tasks_student', 0);
				
			if ($_POST['learning_diary_tasks_teacher'] == 'on')
				update_usermeta($current_user->ID,'learning_diary_tasks_teacher', 1);
			else
				update_usermeta($current_user->ID,'learning_diary_tasks_teacher', 0);
			
			
			
			if ($_POST['ld_send_email_on_new_task'] == 'on')
				update_user_meta($current_user->ID,'ld_send_email_on_new_task', 1);
			else
				update_user_meta($current_user->ID,'ld_send_email_on_new_task', 0);
		
			return false;
		}
	}

}
