<?php

if (!class_exists("BP_Post_Access_Control_Options")) {

	class BP_Post_Access_Control_Options {
	
		public function manage_control_options() {
			$this->handle_action();
			$this->print_html();
		}
	
		private function print_html() {
			$page_uri = get_bloginfo( 'url' ) . "/wp-admin/options-general.php?page=" . plugin_basename(__FILE__);
			$checked = "";
			
			if(get_option('BP_Post_Access_Control_show_title_in_feeds'))
				$checked = "checked='checked'";
			
			?>
			<div class='wrap'>
				<?php screen_icon(); ?>
				<h2><?php _e('Einstellungen › Sichtbarkeitsoptionen', 'bp_learning_diary'); ?></h2>
				<form name='social_access_control' method='post' action='<?php echo $page_uri; ?>'>
			
					<h3><?php _e('Gesch&uuml;tzte Eintr&auml;ge in RSS-Feeds', 'bp_learning_diary'); ?></h3>
					<p><?php _e(
						'Gesch&uuml;tzte Eintr&auml;ge sind in RSS-Feeds nicht sichtbar.<br>' 
						. 'Damit ein Abonnent deines RSS-Feeds dennoch weiss, wann sich etwas auf deinem Weblog getan hat, '
						. 'kannst du den Titel von gesch&uuml;tzten Eintr&auml;gen anzeigen lassen.'
						, 'bp_learning_diary'
					);
					?>				
					<p><input name='BP_Post_Access_Control_show_title_in_feeds' type='checkbox' <?php echo $checked; ?> />
						 <?php _e('Zeige den Titel von gesch&uuml;tzten Eintr&auml;gen im RSS-Feed.', 'bp_learning_diary'); ?>
					</p>
					
			<!--
				<?php /*
					if(is_super_admin()){	
						if(get_option('BP_Post_Access_Control_loggedin_users_option_for_visibility'))
							$checked = "checked='checked'";
						else
							$checked = '';
					
					*/?>
					
					<p><input name='BP_Post_Access_Control_loggedin_users_option_for_visibility' type='checkbox' <?php //echo $checked; ?> />
						 <?php //_e('Allow visibility setting: logged in users only'); ?>
					</p>
					
					<?php 
				//	} //end if is_super_admin()
					?>
			-->		
					<p class="submit">
						<input type="submit" name="submit" value="<?php  _e('Update'); ?>" /> 
					</p>
				
				</form>
			</div>
			<?php 
		
		}
	
	// --------------------------------------------------------------------
	
		private function handle_action() {
			global $_POST;

			if ($_POST['submit'] == __('Reset All Options')) {
				delete_option('BP_Post_Access_Control_show_title_in_feeds',false);
			}

			if (strpos($_POST['submit'], __('Update')) !== false) {
		
				if ($_POST['BP_Post_Access_Control_show_title_in_feeds'] == 'on')
					update_option('BP_Post_Access_Control_show_title_in_feeds', true);
				else
					update_option('BP_Post_Access_Control_show_title_in_feeds', false);
					
					
				if ($_POST['BP_Post_Access_Control_loggedin_users_option_for_visibility'] == 'on')
					update_option('BP_Post_Access_Control_loggedin_users_option_for_visibility', true);
				else
					update_option('BP_Post_Access_Control_loggedin_users_option_for_visibility', false);
			
				return;
			}
		}
	
	}

}

// --------------------------------------------------------------------

add_action('admin_menu','bp_post_access_control_setup_options_page');
	
function  bp_post_access_control_setup_options_page() {
			if (function_exists('add_options_page'))
				add_options_page(__('Sichtbarkeitsoptionen', 'bp_learning_diary'), __('Sichtbarkeitsoptionen', 'bp_learning_diary'), 9, __FILE__,
					array($bp_post_access_control_options = new BP_Post_Access_Control_Options,'manage_control_options'));
}

?>