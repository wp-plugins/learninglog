<?php 

/*
 * For Creating and Deleting the Table while installing
 */

Class LearningDiaryTasksInstall extends LearningDiaryTasks {
	function __construct(){
		
	}
	public function install() {
		
		/*
		 * Enable all modules by default
		 */
		include_once 'learning-diary-tasks-setup.php';
		
		$ld_setup = new LearningDiarySetup;
		
		$ld_setup->load_enabled_modules(true);
		
		/*
		 * Create Tables
		 */
		
		//print_r("INSTALL");
		
		global $wpdb;
	    $table = $wpdb->base_prefix.self::TASK_TABLE_NAME;
	    $structure = "CREATE TABLE IF NOT EXISTS `$table` (
		  `ID` bigint(20) unsigned NOT NULL auto_increment,
		  `post_author` bigint(20) unsigned NOT NULL default '0',
		  `post_date` datetime NOT NULL default '0000-00-00 00:00:00',
		  `post_date_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
		  `post_content` longtext NOT NULL,
		  `post_title` text NOT NULL,
		  `post_excerpt` text NOT NULL,
		  `task_status` varchar(20) NOT NULL default 'publish',
		  `comment_status` varchar(20) NOT NULL default 'open',
		  `post_name` varchar(200) NOT NULL default '',
		  `post_modified` datetime NOT NULL default '0000-00-00 00:00:00',
		  `post_modified_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
		  `publish_date` datetime NOT NULL default '0000-00-00 00:00:00',
		  `review_date` datetime NOT NULL default '0000-00-00 00:00:00',
		  `visibility_of_solution` tinytext NOT NULL,
		  `post_type` varchar(20) NOT NULL default 'post',
		  PRIMARY KEY  (`ID`),
		  KEY `post_name` (`post_name`),
		  KEY `type_status_date` (`post_type`,`task_status`,`post_date`,`ID`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
	    
	    $wpdb->query($structure);
	 	
	    $table = $wpdb->base_prefix.self::META_TASK_TABLE_NAME;
	    $structure = "CREATE TABLE IF NOT EXISTS `$table` (
		  `umeta_id` bigint(20) unsigned NOT NULL auto_increment,
		  `umeta_parent_id` bigint(20) NOT NULL default '0',
		  `task_id` bigint(20) unsigned NOT NULL default '0',
		  `meta_key` varchar(255) default NULL,
		  `meta_value` text NOT NULL,
		  PRIMARY KEY  (`umeta_id`),
		  KEY `task_id` (`task_id`),
		  KEY `meta_key` (`meta_key`)
		)  ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
	    
	    $wpdb->query($structure);

	    $table = $wpdb->base_prefix.self::USER_TASK_TABLE_NAME;
	    $structure = "CREATE TABLE IF NOT EXISTS `$table` (
		  `umeta_id` bigint(20) unsigned NOT NULL auto_increment,
		  `task_id` bigint(20) unsigned NOT NULL default '0',
		  `user_id` int(11) NOT NULL,
		  `blog_id` int(11) NOT NULL default '0',
		  `post_id` int(11) NOT NULL default '0',
		  `task_status` tinytext NOT NULL,
		  PRIMARY KEY  (`umeta_id`),
		  KEY `task_id` (`task_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
	    
	    $wpdb->query($structure);
		
	}
		
	/*
	 * drop tables 
	 */
	
	public function uninstall() {
		/*
		 * TODO: Before Dropping table: Prevent Accidental Deactivation
		 */
		
		/*global $wpdb;
		//DELETE TASK_TABLE
	    $table = "".$wpdb->base_prefix.self::TASK_TABLE_NAME;
	    $sql = "DROP TABLE $table";
	    $wpdb->query($sql);
		//DELETE META_TABLE
	    $table = "".$wpdb->base_prefix.self::META_TASK_TABLE_NAME;
	    $sql = "DROP TABLE $table";
	    $wpdb->query($sql);
	    //DELETE USER_TABLE
	    $table = "".$wpdb->base_prefix.self::USER_TASK_TABLE_NAME;
	    $sql = "DROP TABLE $table";
	    $wpdb->query($sql);*/
	}
}

?>