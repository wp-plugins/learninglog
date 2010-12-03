<?php

/*
 * This project is based on Social Access Control project version 1.1 and the Category Access project version 0.8.2
 * Restricts the access permissions of posts, based on Users found in specific BP-Groups. This plugin gives you the ability to permit only specific registered users to read certain posts.
 * by Thomas Moser & Andrea Cantieni

*/

/*
 * TODO: Version Check
 * 
 * INSERT:
 * 
 
global $wp_version;

$exit_msg='Simplicity requires Wordpress 2.7.1 or newer';
if (version_compare($wp_version,"2.7.1","<")){
	exit ($exit_msg);
}

 */

/*
 * TODO: Check if Buddypress is installed
 * 
 * INSERT:
 * 
// Only load the BuddyPress plugin functions if BuddyPress is loaded and initialized. 
function my_plugin_init() {
	require( dirname( __FILE__ ) . '/my-plugin-bp-functions.php' );
}
add_action( 'bp_init', 'my_plugin_init' );
 */
//session_start();


if(is_admin()){
	include_once('social-access-control-options.php');
	include_once('social-access-control-edit-post.php');
}
// --------------------------------------------------------------------

global $social_access_control_default_private_message;

$social_access_control_default_private_message =
	__('Sorry, you do not have sufficient privileges to view this post.', 'social-access-control');

$social_access_control_filtered_protected_post = false;

$debug_social_access_control = true;

class BP_Post_Access_Control {
/*	
	function __construct($post_id){
		print_r($post_id);
	}
*/
	protected function post_should_be_hidden_to_user($postid, $user = 0) {

		if(!$user){
			global $current_user;
			$user = $current_user;
		}
		
		// TODO: is this needed?
		if (is_null($postid))
			return true;
	
		$post = get_post($postid);
	
		//Post Access Control is only for posts but not for pages // always return false
		//if ($post->post_status == 'static' || $post->post_type == 'page')
		if ($post->post_status == 'static') //added/changed by andrea @ ims to handle pages as posts
			return false;
	
		if (!isset($postid))
			return true;
	
		// Added by Justin at Multinc
		// for per post setting checking
		$per_post_setting = $this->get_user_post_setting($user->ID, $postid);
		
		if ($per_post_setting=='deny')
			return true;
			
		if ($per_post_setting=='allow')
			return false;
			
	}
	
	// --------------------------------------------------------------------
	
	public function filter_title($text, $post_to_check=null)
	{
		global $post;
		$post_id = $post_to_check->ID;
		if (is_null($post_id))
			$post_id = $post->ID;
	
		/* TODO: DELETE
		 * 
		 * FOR DEBUGGING ONLY
		 * 
		 * 
		 
		
		$_bpac_user_has_access = get_post_meta($post->ID, '_bpac_user_has_access');
		$_bpac_visible_for = get_post_meta($post->ID, '_bpac_visible_for', true);
		$_bpac_users_of_group_have_access = get_post_meta($post->ID, '_bpac_users_of_group_have_access');
		
		
		
		
		 
		 $debugusersall = "";
		
		foreach($_bpac_user_has_access as $debugusers){
			$debugusersall .= $debugusers.";";
		}
		$fordebuggingonly = $_bpac_visible_for." || ".$debugusersall;
		
		
		if (is_feed() || !$this->post_should_be_hidden_to_user($post_id))
			return $text." || ".$fordebuggingonly;
			
		/*
		 * 
		 * 
		 * 
		 */
	
			
		if (is_feed() || !$this->post_should_be_hidden_to_user($post_id))
			return $text;
			
		$filtered_title = $this->get_private_message();
	
		if (get_option('Social_Access_Control_post_policy') == 'show title')
			$filtered_title = $text;
	
		return "<div class='social_access_control_protected_title'>" . 
			"$padlock_prefix$filtered_title</div>";
	}
	
	// --------------------------------------------------------------------
	
	private function get_private_message() {
		$message = get_option("Social_Access_Control_private_message");
	
		if (is_null($message)) {
			global $social_access_control_default_private_message;
			$message = $social_access_control_default_private_message;
		}
	
		return $message;
	}
	
		
	// --------------------------------------------------------------------
	
	
	public function filter_posts($sql)
	{
		global $current_user;
			
		if (is_feed() && get_option('BP_Post_Access_Control_show_title_in_feeds') ||
				strpos($sql, 'post_status = "static"') !== false ||
				strpos($sql, 'post_type = \'page\'') !== false)
			return $sql;
	
		if (!is_feed() && (
				get_option('Social_Access_Control_post_policy') == 'show title' ||
				get_option('Social_Access_Control_post_policy') == 'show message' ||
				// For backwards compatibility
				get_option('Social_Access_Control_show_private_message') ))
			return $sql;
	
		// Added by Justin
		// to filter out posts that user don't have permission to read
		$visible_posts = $this->get_posts_visible_to_user($current_user);
	    $sql = $sql." AND ID IN (".implode(",", $visible_posts).")";
		return $sql;
		
		// we don't need the rest
	
	}
	
	// --------------------------------------------------------------------
	
	public function check_redirect() {
		global $social_access_control_filtered_protected_post;
	
		if ( $social_access_control_filtered_protected_post )
	    auth_redirect();
	}
	
	// --------------------------------------------------------------------
	
	public function filter_content($text, $post_to_check=0)
	{
		
		global $post, $current_user;
		
		
		$post_id = $post_to_check;
		if (!$post_id)
			$post_id = $post->ID;

		
		//if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') == true)
			//return $text;
	
		if ($this->post_should_be_hidden_to_user($post_id)) {
			if (get_option('Social_Access_Control_post_policy') == 'show title')
				$text = "<div class='social_access_control_protected_post'>" .
					$this->get_private_message() . "</div>";
			else
				$text = '';
		}
	
		return $text;
	}
	
	/*
	 * Used by add_filter: comment_author, comment_email, comment_excerpt, comment_text, comment_url, posts_where
	 */
	
	public function hide_text($text)
	{
		//Hide Text if not in wp-admin
		if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') == true)
			return $text;
		
		global $post;
	
		if ($this->post_should_be_hidden_to_user($post->ID))
			$text = '';
	
		return $text;
	}
	
	// --------------------------------------------------------------------
	
	// Added by Tom @ ims: just get real posts but no revisions (added post_status<>'inherit')!
	protected function get_posts_visible_to_user($user) {
		global $wpdb;
		
		$sql = "SELECT ID FROM $wpdb->posts WHERE post_status<>'inherit'";
		$posts = $wpdb->get_col($sql);
		foreach ($posts as $key => $value) {
			if ($this->post_should_be_hidden_to_user((int)$value, $user))
				unset($posts[$key]);
		}
		return $posts;
	}
	
	// Added by Andrea @ ims
	public function get_posts_maped_to_group($group_id)
	{
		global $wpdb;

		$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_bp_ld_mapto_groups' AND meta_value = $group_id";

		return $wpdb->get_col($query);

	}
	
	/*
	 * get per post setting for the current user 
	 * returns allow or deny
	 */
	
	public function get_user_post_setting($userid, $postid) {
		global $current_user;
		
		//users who have access to the post
		$_bpac_users_have_access = get_post_meta($postid, '_bpac_user_has_access'); //_accessible_users
		//visible for all, logged in users, a specific group or specific users
		$_bpac_visible_for = get_post_meta($postid, '_bpac_visible_for'); //_bpsa_visible_for
		//which groups have access to the post
		$_bpac_users_of_group_have_access = get_post_meta($postid, '_bpac_users_of_group_have_access'); //_bpsa_visible_for_groups
				
		if(!$_bpac_visible_for){
			//if there is no user setting (user has not set any access control for post)
			return "allow";
		} else if ($_bpac_visible_for[0]=="all"){
			//if post is visible for everybody (option: all)
			return "allow";
		} else if ($_bpac_visible_for[0]=="loggedin_users" AND $current_user->ID){
			//if post is visible for logged in users (option: loggedin)
			return "allow";
		} else if ($_bpac_users_have_access && in_array((string)$userid, $_bpac_users_have_access)){
			//if current user is in list
			return "allow";
		} else if ($current_user->ID==1){ //added by andrea @ ims
			//if current user is THE sidewide admin
			return "allow";
		}else{
			$blog_users = get_users_of_blog();
			foreach($blog_users as $the_blog_user){
				if($the_blog_user->user_id==$userid){
					//if current user is part of the blog
					return "allow";
				}
			}
			return "deny";
		}
	}
	
	// filter archives from protected posts
	public function filter_getarchives_where($sql) {
		global $current_user;
		
		if (get_option('Social_Access_Control_post_policy') != 'hide')
			return $sql;
				
		$visible_posts = $this->get_posts_visible_to_user($current_user);
	    $sql = $sql." AND ID IN (".implode(",", $visible_posts).")";
		return $sql;
	}
	
	//added by andrea @Êims 
	//exclude hidden pages from the default sidebar widget
	public function exclude_page_from_sidebar_widget($args) {
		global $current_user;
		
		$visible = $this->get_posts_visible_to_user($current_user);
		
		$allpages = get_pages(); //get pages from current blog (not from current user's blog)
		
		foreach($allpages as $page) {
			if ( !in_array($page->ID, $visible) ){
				$args['exclude'] = $page->ID;
			}
		}
		return $args;
	}
}

Class BP_User_Access_Control_Install { #insert data while installing

	public function set_options() {
		//TODO: Define Default Options
		// 1: Bezï¿½glich Feeds
		// 2: 
		//add_option('simplicity_options');
		//update_option('simplicity_options', default_options());
	}
	
	#delete data while uninstalling
	public function unset_options() {
		delete_option('BP_Post_Access_Control_private_message',false);
		delete_option('BP_Post_Access_Control_post_policy',false);
		delete_option('BP_Post_Access_Control_show_title_in_feeds',false);	}
	
	private function default_options(){
		
	}
}
// --------------------------------------------------------------------

//register_activation_hook(__FILE__, array('BP_User_Access_Control_Install','set_options'));
//register_deactivation_hook(__FILE__, array('BP_User_Access_Control_Install','unset_options'));

// We'll use a very low priority so that our plugin will run after everyone
// else's. That way we won't interfere with other plugins.

$bp_post_access_control = new BP_Post_Access_Control;

add_filter('comment_author',
	array($bp_post_access_control,'hide_text'), 10000);
add_filter('comment_email',
	array($bp_post_access_control,'hide_text'), 10000);
add_filter('comment_excerpt',
	array($bp_post_access_control,'hide_text'), 10000);
add_filter('comment_text',
	array($bp_post_access_control,'hide_text'), 10000);
add_filter('comment_url',
	array($bp_post_access_control,'hide_text'), 10000);
add_filter('posts_where',
	array($bp_post_access_control,'filter_posts'), 10000);
add_filter('single_post_title',
	array($bp_post_access_control,'filter_title'), 10000, 2);
add_filter('the_content',
	array($bp_post_access_control,'filter_content'), 10000);
add_filter('the_excerpt',
	array($bp_post_access_control,'hide_text'), 10000);
add_filter('the_title',
	array($bp_post_access_control,'filter_title'), 10000, 2);
add_filter('the_title_rss',
	array($bp_post_access_control,'filter_title'), 10000, 2);
add_action('template_redirect',
	array($bp_post_access_control,'check_redirect'), 10000);
add_filter('getarchives_where',	
	array($bp_post_access_control,'filter_getarchives_where'), 10000);
//added by andrea @ ims
//hook is located in wp-includes/default-widget.php on line 32
add_filter('widget_pages_args', 
	array($bp_post_access_control,'exclude_page_from_sidebar_widget'), 10000);
?>