<?php
class LearningDiarySetup
{
	public $modules;
	
	function __construct() {
		$this->modules = array(
			'simplicity' => array(
				'name' => 'simplicity',
				'html_name' => 'simplicity',
				'title' => 'Simplicity',
				'path' => dirname(__FILE__) . '/../simplicity/simplicity.php',
				'desc' => __('Blendet einige selten benutzte Menus und Untermenus aus.', 'bp_learning_diary')
			),
			'admin_css' => array(
				'name' => 'admin_css',
				'html_name' => 'admin-css',
				'title' => 'Admin CSS',
				'path' => dirname(__FILE__) . '/../bp-learning-diary-admin-css/bp-learning-diary-admin-css.php',
				'desc' => __('Passt den  Administrationsbereich am Design des Lerntagebuchs-Thema an.', 'bp_learning_diary')
			),
			'bulk_import' => array(
				'name' => 'bulk_import',
				'html_name' => 'bulk-import',
				'title' => 'Bulk Import',
				'path' => dirname(__FILE__) . '/../bp-bulk-import-blogs/bp-bulk-import-blogs.php',
				'desc' => __('Erlaubt das Registrieren mehrerer Lernende gleichzeitig.', 'bp_learning_diary')
			),
			'social_access' => array(
				'name' => 'social_access',
				'html_name' => 'social-access',
				'title' => 'Social Access',
				'path' => dirname(__FILE__) . '/../bp-social-access/social-access-control.php',
				'desc' => __('Ermöglicht Sichtbarkeitseinstellungen für Artikel und Seiten', 'bp_learning_diary')
			),
			
		);
		
		ksort($this->modules);
	}
			
	public function learning_diary_setup_page() {			
		$checked = array();
		
		$modules = $this->modules;
		
		foreach($modules as $module){
			$checked[$module['name']]['on'] = (get_site_option($module['name'] . '_options')=='on' ? " checked" : "");
			$checked[$module['name']]['off'] = (get_site_option($module['name'] . '_options')=='on' ? "" : " checked");
			
			if($_POST[$module['html_name']] == "on"){
				include_once $module['path'];
				
				if($module['name']=='simplicity'){
					set_simplicity_options();
				}
				//add_option($module['name'] . '_options', 'on');
				//update_option($module['name'] . '_options', 'on');
				update_site_option($module['name'] . '_options', 'on');
				
				$checked[$module['name']]['on'] = " checked";
				$checked[$module['name']]['off'] = "";
				
			}

			if($_POST[$module['html_name']] == "off"){
				if($module['name']=='simplicity'){
					if(function_exists('unset_simplicity_options')) {
						unset_simplicity_options();
					}
				}
					
				//delete_option($module['name'] . '_options');
				update_site_option($module['name'] . '_options', 'off');
				
				$checked[$module['name']]['off'] = " checked";
				$checked[$module['name']]['on'] = "";
				
			}
			
		}
						
		?>
		
		<div class="wrap">
	    
		<h2><?php _e("Lerntagebuch Komponenten-Installation", 'bp_learning_diary') ?></h2>
		<?php if($_GET['updated']=='true'):?>
			<div id="message" class="updated fade below-h2">
				<p><?php _e('Settings Saved', 'buddypress')?></p>
			</div>
		<?php endif?>
		<form method="post">
			<p>
				<?php _e('Standardmässig sind alle Komponenten aktiviert. Hier kannst du einzelne Komponete für alle Blogs deaktivieren.', 'bp_learning_diary')?>
			</p>
			<table class="form-table", style="width: 90%">
			<tbody>
			<?php foreach($modules as $module) :?>
			<tr>
				<td>
					<h3><?php echo $module['title']?></h3>
					<p><?php echo $module['desc']?></p>
				</td>
				<td>
					<input type="radio" name="<?php echo $module['html_name']?>" value="on" <?php echo $checked[$module['name']]['on']?>/>
						<?php _e( 'Enabled', 'buddypress')?>&nbsp;
					<input type="radio" name="<?php echo $module['html_name']?>" value="off" <?php echo $checked[$module['name']]['off']?>/>
						<?php _e( 'Disabled', 'buddypress')?>
				</td>
			</tr>
			<?php endforeach;?>
			</tbody>
			</table>
			<p class="submit">
				<input class="button-primary" type="submit" value="<?php _e('Save Settings', 'buddypress')?>">
			</p>
		</form>
		</div>
		<?php
		
		//apply changes immediately
		if($_POST) {
			header("Location: admin.php?page=learning-diary-tasks-init.php&updated=true");
		}
	}

	public function load_enabled_modules($all=false) {
		
		$modules = $this->modules;
		
		foreach ($modules as $module){
			if ($all || get_site_option($module['name'] . '_options')=='on') {
				include_once $module['path'];
			}
			if($all){
				update_site_option($module['name'] . '_options', 'on');
			}
		}
		
		//deactivate admin css on install
		if($all){
			update_site_option('admin_css_options', 'off');
		}
		//var_dump(get_included_files());die();
	
	}
	


}