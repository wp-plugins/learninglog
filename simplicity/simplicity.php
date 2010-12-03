<?php
/*
 * Simplifies Wordpress (Removes BuddyPress Admin Bar and Dashboard Items as well as some Menus and Submenus)
 * by Thomas Moser
 */

#INITIATE Simplicity
add_action('init', 'simplicity_init');
//add_action('wp_loaded', 'simplicity_init');

#ACTIVATE Options Page
add_action('admin_menu', 'simplicity_admin_menu');

#DISABLE Menus and Submenus if Simplicity is activated
add_action('admin_menu', 'simplicity_disable_menus', 100);

function simplicity_admin_menu(){
	add_options_page('Simplicity', __('Funktionsumfang', 'bp_learning_diary'), 8, basename(__FILE__), 'simplicity_options_page');
}

#REMOVE Dashboard Widgets
add_action('wp_dashboard_setup', 'simplicity_disable_dashboard_widgets' );

#REMOVE Buddy Press Adminbar
define('BP_DISABLE_ADMIN_BAR', true);	

#PREPARE Database while activating and deactivating the plugin
//register_activation_hook(__FILE__, 'set_simplicity_options');
//register_deactivation_hook(__FILE__, 'unset_simplicity_options');

$simplicity_menu = null;
$simplicity_submenu = null;

function simplicity_init(){
	// save default menu and submenu, otherwise it will be permanently hidden
	// and we can't display it in our options
	global $simplicity_menu, $simplicity_submenu, $menu, $submenu;
	$simplicity_menu = $menu;
	$simplicity_submenu = $submenu;	
	
	#Check if form was submitted
	if (isset($_POST['simplicity_state_submit'])){
		simplicity_save_options();
	}
}

function simplicity_get_options() {
	$options = get_option('simplicity_options');
	if ($options && is_array($options) && !empty($options))
		return $options; 

	return simplicity_default_options();
}

/* 
 * 
 * Disable predefined menus and submenus
 * 
 */

function simplicity_disable_menus() {

	global $menu, $submenu;

	$options = simplicity_get_options();

	if ($options['simplicity_state']==2){
		$disabled_menu_items = array(
			"link-manager.php",
			"edit-pages.php",
			"plugins.php",
			"users.php"
		);
		$disabled_submenu_items = array(
			"index.php",
		//dashboard
			"my-sites.php",
		//settings
			"options-general.php",
			"options-writing.php",
			"options-reading.php",
			"options-discussion.php",
			"options-media.php",
			"options-privacy.php",
			"options-permalink.php",
		
		//design
			"themes.php",
			"nav-menus.php",
			"widgets.php",
		//tools
            "tools.php",
            "export.php"
        );
 
	}else{
		$disabled_menu_items = array();
		$disabled_submenu_items = array("index.php", "my-sites.php", "nav-menus.php");//, "themes.php"); //disable useless dashboard submenus forever
	}
	
	foreach($menu as $index => $menu_item)	{
		if (in_array($menu_item[2], $disabled_menu_items)){
			unset($menu[$index]);
		}
		if (!empty($submenu[$menu_item[2]])){
			foreach ($submenu[$menu_item[2]] as $subindex => $subitem){
				if (in_array($subitem[2], $disabled_submenu_items)){
					unset($submenu[$menu_item[2]][$subindex]);
				}
			}
		}
	}	
}


/* 
 * 
 * Simplify Dashboard 
 * 
 */

function simplicity_disable_dashboard_widgets() {
	
	// Globalize the metaboxes array, this holds all the widgets for wp-admin
	global $wp_meta_boxes;

	// Remove the quickpress widget
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);

	// Remove the incomming links widget
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);	
	
	// Remove the plugins widget
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);	
	
	// Remove the dashbard primary links widget
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);	
	
	// Remove the dashbard secondary links widget
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
	
	// Remove the dashbard right now widget	
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
	
	// Remove the dashbard recent comments widget
	//unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
	
	// Remove the dashbard recent drafts widget
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);

} 

/*
 * 
 * Simplicity options page
 * 
 */

function simplicity_options_page(){
	
	#Get user option
	$options = simplicity_get_options();
	$checked = $options['simplicity_state'] == '1' ? 'checked="checked"' : '';
	?>
	
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Einstellungen › Funktionsumfang', 'bp_learning_diary'); ?></h2>
	<h3><?php _e('Funktionsumfang erweitern', 'bp_learning_diary')?></h3>
	<p><?php _e('Auf dieser Seite kannst du selten benötigte Funktionen ein- oder ausblenden.', 'bp_learning_diary')?></p>
	
	<form id="update_simplicity_options" name="update_simplicity_options" action="options-general.php?page=simplicity.php" method="POST">
		
		<input type="checkbox" name="simplicity_state" <?php echo $checked ?>> <?php _e('Alle Funktionen anzeigen.', 'bp_learning_diary')?>
		<p>
			<input type="submit" name="simplicity_state_submit" id="simplicity_state_submit" value="<?php _e("Save Changes") ?>" class="button-primary" />
		</p>

	</form>
	</div> 
	<?php 
}

function simplicity_save_options() {
	$simplicity_state = $_POST['simplicity_state'] == 'on' ? '1' : '2';

	simplicity_update_option('simplicity_state',$simplicity_state);
}

function simplicity_update_option($name, $value) {
	$options = simplicity_get_options();
	$options[$name] = $value;
	simplicity_update_options($options);
}

function simplicity_update_options($options) {
	update_option('simplicity_options', $options);
}

/*
 * 
 * Activating and Deactivating the Plugin  
 * 
 */

#insert data while installing
function set_simplicity_options() {
	add_option('simplicity_options');
	update_option('simplicity_options', simplicity_default_options());
}

#delete data while uninstalling
function unset_simplicity_options() {
	delete_option('simplicity_options');
}

#set default options
function simplicity_default_options() {
	$options = array(
		# 2 means activated
		# 1 means deactivated
		'simplicity_state' => 2,
	);
	return $options;
}




?>