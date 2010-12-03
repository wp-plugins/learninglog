<?php
/*
 * Plugin Name: Learninglog
 * Plugin URI:  http://learninglog.org
 * Description: Learninglog offers teachers and learners advanced functions to use Wordpress as a learning tool.
 * Version:     2.1
 * Requires at least: WordPress 3.0 with Multisite / BuddyPress 1.2.4.1
 * Author:      Thomas Moser, Andrea Cantieni
 * Text Domain: bp_learning_diary
 * Site Wide Only: true
*/

/* 
 * Wordpress Version check
 */
define('LEARNING_DIARY_TASKS_PLUGIN_URL', basename(dirname(__FILE__)));

global $wp_version;

$exit_msg='Learning Diary Tasks requires Wordpress 3.0 or newer';

if ( version_compare( $wp_version,"3.0","<" ) ){
	exit ( $exit_msg );
}

/* 
 * Check if multisite is activated 
 */

if(is_multisite()){
	# Check if buddypress is activated
	add_action( 'bp_init', 'learning_diary_tasks_init' );
}

function learning_diary_tasks_init() {
	load_plugin_textdomain('bp_learning_diary', false, LEARNING_DIARY_TASKS_PLUGIN_URL . '/languages');
    require( dirname( __FILE__ ) . '/bp-learning-diary/learning-diary-tasks-init.php' );
}

/* Register BuddyPress themes contained within the bp-theme folder */
if ( function_exists( 'register_theme_directory') )
	register_theme_directory( dirname( __FILE__ ) . '/bp-themes' );


/*
 * Install scripts
 */

include_once(dirname( __FILE__ ) . "/bp-learning-diary/learning-diary-tasks-core.php");

include_once(dirname( __FILE__ ) . "/bp-learning-diary/learning-diary-tasks-install.php");
//var_dump(get_included_files());die();

//@todo: uncomment the lines bellow and check the errors
#Prepare Database
register_activation_hook(__FILE__, array($ldi = new LearningDiaryTasksInstall,'install'));

#Delete Database
//register_deactivation_hook(__FILE__, array($ldui = new LearningDiaryTasksInstall,'uninstall'));