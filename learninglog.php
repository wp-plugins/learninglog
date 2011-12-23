<?php
/*
 * Plugin Name: Learninglog
 * Plugin URI:  http://learninglog.org
 * Description: Learninglog offers teachers and learners advanced functions to use Wordpress as a learning tool.
 * Version:     2.1.3
 * Requires at least: 3.0
 * Author:      Tom, andrea.cantieni
 * Text Domain: bp_learning_diary
 * Site Wide Only: true
*/

/* Define Plugin URL */
define('LEARNING_DIARY_TASKS_PLUGIN_URL', basename(dirname(__FILE__)));

/* Wordpress Version check */
global $wp_version;

$exit_msg='Learninglog requires WordPress 3.0 or newer.';

if ( version_compare( $wp_version,"3.0","<" ) ) {
	exit ( $exit_msg );
}

/* Check if multisite is activated */
$exit_msg='Learninglog requires WordPress with multisite enabled.';

if( !is_multisite() ) {
	exit ( $exit_msg );
}

/* Check if buddypress is activated */
function learninglog_install_buddypress_notice() {
	echo '<div id="message" class="error fade"><p style="line-height: 150%">';
	_e('<strong>Learninglog</strong></a> requires the BuddyPress plugin to work. Please <a href="http://buddypress.org/download">install BuddyPress</a> first, or <a href="plugins.php">deactivate Learninglog</a>.');
	echo '</p></div>';
}

if ( !function_exists( 'bp_core_install' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	if ( is_plugin_active( 'buddypress/bp-loader.php' ) ) {
		require_once ( WP_PLUGIN_DIR . '/buddypress/bp-loader.php' );
	} else {
		add_action( 'admin_notices', 'learninglog_install_buddypress_notice' );
		return;
	}
}

function learning_diary_tasks_init() {
	load_plugin_textdomain('bp_learning_diary', false, LEARNING_DIARY_TASKS_PLUGIN_URL . '/languages');
    require( dirname( __FILE__ ) . '/bp-learning-diary/learning-diary-tasks-init.php' );
}

add_action( 'bp_init', 'learning_diary_tasks_init' );

/* Register learninglog themes contained within the bp-themes folder */
if ( function_exists( 'register_theme_directory') ) {
	register_theme_directory( dirname( __FILE__ ) . '/bp-themes' );
}

/* include install scripts */
include_once(dirname( __FILE__ ) . "/bp-learning-diary/learning-diary-tasks-core.php");

include_once(dirname( __FILE__ ) . "/bp-learning-diary/learning-diary-tasks-install.php");

/* Prepare Database */
register_activation_hook(__FILE__, array($ldi = new LearningDiaryTasksInstall,'install'));

/* Delete Database */
//register_deactivation_hook(__FILE__, array($ldui = new LearningDiaryTasksInstall,'uninstall'));
