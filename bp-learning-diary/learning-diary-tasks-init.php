<?php

/*
 * Include Core Class
 */

include_once ("learning-diary-tasks-core.php");

include_once 'learning-diary-tasks-setup.php';

$ld_setup = new LearningDiarySetup;

$ld_setup->load_enabled_modules();

/*
 * Add Dashboard Widgets
 */

include ("learning-diary-tasks-dashboard-widgets.php");

function learning_diary_tasks_add_dashboard_widgets() {
	global $current_user;
	
	$learningdiarydashboard = new LearningDiaryTasksDashboardWidget ();
	
	//add dashboard widget for first steps
	if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true )) {
		wp_add_dashboard_widget ( 
			'show_teacher_dashboard_first_steps', 
			__( "Erste Schritte f&uuml;r Lehrende", 'bp_learning_diary' ), 
			array (&$learningdiarydashboard, 'show_teacher_dashboard_first_steps' )
		);
	}
	//add dashboard widget for creating a task (for teachers only)
	

	//add dashboard widget for fast access to important functions
	if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true )) {
		wp_add_dashboard_widget ( 
			'show_teacher_dashboard_functions', 
			__( "Schnellzugriff auf Funktionen f&uuml;r Lehrende", 'bp_learning_diary' ), 
			array (&$learningdiarydashboard, 'show_teacher_dashboard_functions' ) 
		);
	}
	
	//add dashboard widget for outstanding membership requests
	if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true )) {
		wp_add_dashboard_widget ( 
			'learning_diary_tasks_dashboard_membership_requests', 
			__( "Mitgliedschaftsanfragen zu meinen Gruppen", 'bp_learning_diary' ), 
			array (&$learningdiarydashboard, 'show_membership_requests' ) 
		);
	}
	
	//add dashboard widget for help and tutorials
	if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true )) {
		wp_add_dashboard_widget ( 
			'show_teacher_dashboard_tutorials', 
			__( "Tutorials und FAQ", 'bp_learning_diary' ), 
			array (&$learningdiarydashboard, 'show_teacher_dashboard_tutorials' ) 
		);
	}
	
	//add dashboard widget for checking current tasks (for students only)
	if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_student', true )) {
		wp_add_dashboard_widget ( 
			'learning_diary_tasks_dashboard_widget_get_tasks', 
			__( "Neue Aufgaben", 'bp_learning_diary' ), 
			array (&$learningdiarydashboard, 'get_recent_tasks_for_user' ) 
		);
	}
	
	//add css for dashboard
	add_action ( "admin_print_styles-index.php", array (&$learningdiarydashboard, 'add_style' ) );
	
	//Add js for menu page
	add_action ( "admin_print_scripts-index.php", array (&$learningdiarydashboard, "add_jquery" ) );

} //end function learning_diary_tasks_add_dashboard_widgets()

add_action ( 'wp_dashboard_setup', 'learning_diary_tasks_add_dashboard_widgets' );

/*
 * Add the main admin menu page
 */

include ("learning-diary-tasks-edit.php");

function learning_diary_tasks_add_admin_menu_tasks() {
	global $current_user;
	global $menu;
	
	/*
		 * Change admin menu for teachers
		 */
	//move menu "Posts" to Position 8
	

	if (! is_super_admin ()) {
		//change position of "Artikel" and "Mediathak"
		$menu [14] = $menu [10];
		$menu [13] = $menu [5];
		
		$menu [10] = ""; //remove "Mediathek" at position 10
		$menu [5] = ""; //remove "Artikel" at position 5
		

		if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true )) {
			//change position of dashboard
			$menu [0] = $menu [2]; //change dashboard position to 0
			

			//add separators
			$menu [1] = $menu [4]; //change sparater position to 1
			$menu [3] = array ("", "read", "separator10", "", "wp-menu-separator-a" ); //add separator 1
			$menu [8] = array ("", "read", "separator20", "", "wp-menu-separator-b" ); //add separator 2
			$menu [11] = array ("", "read", "separator30", "", "wp-menu-separator-c" ); //add separator 3
			

			//add subtitles
			$menu [10] = array (__("Mein Lerntagebuch", 'bp_learning_diary'), "read", "#", "", "wp-menu-title", "wp-menu-separator-title", "div" );
			$menu [2] = array (__("Funktionen für Lehrende", 'bp_learning_diary'), "read", "#", "", "wp-menu-title", "wp-menu-separator-title", "div" );
		

		}
	}
	
	$learningdiaryedit = new LearningDiaryTasksEdit();
	$learningdiarysetup = new LearningDiarySetup();
	
	if (! is_super_admin ()) {
		if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true )) {
			$page = add_menu_page ( 
				__( "Aufgaben verwalten", 'bp_learning_diary' ), 
				__( "Aufgaben verwalten", 'bp_learning_diary' ), 
				2, 
				basename ( __FILE__ ), 
				array (&$learningdiaryedit, 'learning_diary_tasks_edit_page' ), 
				"", 
				4
			);
		}
	} else {
		$page = add_menu_page ( 
			__( "Lerntagebuchadmin", 'bp_learning_diary' ), 
			__( "Lerntagebuchadmin", 'bp_learning_diary' ), 
			2, 
			basename ( __FILE__ ), 
			//array (&$learningdiaryedit, 'learning_diary_tasks_edit_page' ), 
			array (&$learningdiarysetup, 'learning_diary_setup_page' ), 
			"" 
		);
	}
	
	add_action ( "admin_print_styles", "learning_diary_tasks_add_general_style" );
	
	//menu for student
	if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_student', true )) {
		$title = __( "Aufgaben", 'bp_learning_diary' );
		$tasks = LearningDiaryTasks::get_recent_tasks ( $current_user->ID );
		
		if ($tasks ["count_new_posts"]) {
			$title .= ' <span class="update-plugins count-2"><span class="plugin-count">' . $tasks ["count_new_posts"] . '</span></span>';
		}
		
		$page = add_menu_page ( 
			__( "Aufgaben", 'bp_learning_diary' ), 
			$title, 
			2, 
			'learning_tasks', 
			array (&$learningdiaryedit, 'learning_diary_tasks_edit_page_student' ), 
			"", 
			12 
		);
		
		//Add js for menu page
		add_action ( "admin_print_scripts-$page", array (&$learningdiaryedit, "add_jquery" ) );
	
		//Add css for menu page
		add_action ( "admin_print_styles-$page", array (&$learningdiaryedit, "add_style" ) );
	
	}
	
	//menu for teachers
	if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true )) {
			
		$page = add_submenu_page ( 
			'learning-diary-tasks-init.php', 
			__( "Aufgaben verwalten", 'bp_learning_diary' ), 
			__( "Aufgaben verwalten", 'bp_learning_diary' ), 
			2, 
			basename ( __FILE__ ), 
			array (&$learningdiaryedit, 'learning_diary_tasks_edit_page' ) 
		);
	
		//Add js for menu page
		add_action ( "admin_print_scripts-$page", array (&$learningdiaryedit, "add_jquery" ) );
	
		//Add css for menu page
		add_action ( "admin_print_styles-$page", array (&$learningdiaryedit, "add_style" ) );
		
		$page = add_submenu_page ( 
			'learning-diary-tasks-init.php', 
			__( "Neue Aufgabe erstellen", 'bp_learning_diary' ), 
			__( "Neue Aufgabe erstellen", 'bp_learning_diary' ), 
			2, 
			"add-new-task", 
			array (&$learningdiaryedit, 'add_new_task' ) 
		);
		
		//Add js for menu page
		add_action ( "admin_print_scripts-$page", array (&$learningdiaryedit, "add_jquery" ) );
				
		//Add css for menu page
		add_action ( "admin_print_styles-$page", array (&$learningdiaryedit, "add_style" ) );
	}
}

function learning_diary_tasks_add_general_style() {
//	$myStyleUrl = WP_PLUGIN_URL . '/' . basename ( dirname ( __FILE__ ) ) . '/css/general-admin.css';
	$myStyleUrl = WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary/css/general-admin.css';	
	wp_register_style ( 'learning_diary_tasks_general_admin_style', $myStyleUrl );
	wp_enqueue_style ( 'learning_diary_tasks_general_admin_style' );
}

add_action ( 'admin_menu', 'learning_diary_tasks_add_admin_menu_tasks', 10 );

/*
 * Add menu item for creating and administrating groups
 */

function learning_diary_tasks_add_admin_menu_groups() {
	if (! is_super_admin ()) {
		add_menu_page ( 
			__( "Gruppen verwalten", 'bp_learning_diary' ), 
			__( "Gruppen verwalten", 'bp_learning_diary' ), 
			2, 
			"redirect", 
			'learning_diary_tasks_add_admin_menu_groups', 
			"", 
			5 
		);
	}
}

global $current_user;

if (get_user_meta ( $current_user->ID, 'learning_diary_tasks_teacher', true ))
	add_action ( 'admin_menu', 'learning_diary_tasks_add_admin_menu_groups', 10 );

function learning_diary_tasks_add_admin_menu_groups_redirect() {
	if ($_GET ["page"] == "redirect") {
		$location = "http://" . get_blog_details ( 1 )->domain . get_blog_details ( 1 )->path . "groups/";
		status_header ( 301 );
		header ( "Location: $location", true, 301 );
	}
}

add_action ( 'load_textdomain', 'learning_diary_tasks_add_admin_menu_groups_redirect' );

/*
 * Add show group activity functionality
 */

include_once ("learning-diary-tasks-group-activity.php");

$ld_group_actvity = new LearningDiaryTasksGroupActivity ();

add_action ( 'bp_before_group_body', array (&$ld_group_actvity, 'show_group_activity' ), 10 );

//reorder member list
add_action ( 'bp_before_group_members_list', array (&$ld_group_actvity, 'reorder_members' ), 10 );

/*
 * Prepare database during the installation of the plugin
 
	
	include("learning_diary_tasks_install.php");
	#Prepare Database
	register_activation_hook(__FILE__, array($ldi = new LearningDiaryTasksInstall,'install'));
	#Delete Database
	register_deactivation_hook(__FILE__, array($ldui = new LearningDiaryTasksInstall,'uninstall'));
*/
/*
 * Setup options page
 */

include ("learning-diary-tasks-options.php");

function setup_options_page() {
	$page = add_options_page ( 
		__( 'Lerntagebuchoptionen', 'bp_learning_diary' ), 
		__( 'Lerntagebuchoptionen', 'bp_learning_diary' ), 
		9, 
		//__FILE__,
		'learning-diary-tasks-options.php',
		array ($ldto = new LearningDiaryTasksOptions (), 'get_setup_options_page' ) 
	);
	
	//Add js for options page
	add_action ( "admin_print_scripts-$page", array (&$ldto, "add_js" ) );

}

add_action ( 'admin_menu', 'setup_options_page' );

/*
 * Show widget in post-new.php and post-edit.php
 */

include ("learning-diary-tasks-post-edit.php");

#Add
//add_action('wp_insert_post', array('LearningDiaryTasksPostEdit','save_post'), 10000);


#Add Post Box in Post Section
add_action ( 'admin_head-post.php', array ('LearningDiaryTasksPostEdit', 'construct' ), 10000 );

add_action ( 'delete_post', array ('LearningDiaryTasksPostEdit', 'delete_post' ) );

add_action ( 'edit_post', array ('LearningDiaryTasksPostEdit', 'update_post' ), 10000, 2 );

#Add js for positioning of the edit post metabox in post.php
add_action ( "admin_print_scripts-post.php", array ('LearningDiaryTasksPostEdit', "add_jquery" ) );

#Add js for positioning of the edit post metabox in post.php
add_action ( "admin_print_styles-post.php", array ('LearningDiaryTasksPostEdit', "add_style" ) );

#Add js for positioning of the edit post-new metabox in post.php
add_action ( "admin_print_styles-post-new.php", array ('LearningDiaryTasksPostEdit', "add_style" ) );

/*
 * Catch/save custom answer format data on post saving
 */
add_action ( 'save_post', array ('LearningDiaryTasksPostEdit', 'save_answer_format' ) );

/*
 * Display custom response format in single post view
 */
// do_action('ld_display_answer_format') must be present in single.php of the used theme
add_action ( 'ld_display_answer_format', array ('LearningDiaryTasksPostEdit', 'display_answer_format_in_single_post' ) );

/*
 * AJAX response action (first argument must start with 'wp_ajax_') (http://codex.wordpress.org/AJAX_in_Plugins)
 */
include_once ("learning-diary-tasks-ajax.php");

add_action ( 'wp_ajax_answer_format_ajax', 'response_answer_format' );
add_action ( 'wp_ajax_select_users_ajax', 'response_users_to_select' );
add_action ( 'wp_ajax_highlight_users_ajax', 'response_users_to_highlight' );
add_action ( 'wp_ajax_highlight_groups_ajax', 'response_groups_to_highlight' );

/**
 * Setup default data while registration or deletion of a new blog / user
 */

include ("learning-diary-tasks-register-blog.php");

// Create New Object
$learningdiarytasksregister = new LearningDiaryTasksRegister ();

/*
 * Register sidebar widget for user template
 */

include ("learning-diary-tasks-sidebar-widget.php");

/**
 * Register our widget.
 * 'Example_Widget' is the widget class used below.
 */

function learning_diary_tasks_load_widgets() {
	register_widget ( 'Learning_Diary_Tasks_Widget' );
}

/*
 * Add function to widgets_init that'll load our widget.
 */

add_action ( 'widgets_init', 'learning_diary_tasks_load_widgets' );

include ("learning-diary-tasks-show-task-in-theme.php");

$learningdiarytasksshowtasksintheme = new LearningDiaryTasksShowTaskInTheme ();

/*
 * remove menu item in bp profile added by invite-anyone plugin
 */

function ld_remove_menu_item_send_invite() {
	remove_action ( 'admin_menu', 'invite_anyone_setup_nav', 2 );
	remove_action ( 'wp', 'invite_anyone_setup_nav', 2 );
}

add_action ( 'plugins_loaded', 'ld_remove_menu_item_send_invite', 50 );
