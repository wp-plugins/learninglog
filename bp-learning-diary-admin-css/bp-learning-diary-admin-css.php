<?php
/*
 * Changes the Admin Template to fit lerntagebuch.ch
 * by Thomas Moser & Andrea Cantieni
 */

// Add CSS
add_action('admin_print_styles', 'learning_diary_admin_template_add_style');	

// Add alternative header
add_action('admin_notices','learning_diary_admin_template_print_header');
add_action('network_admin_notices','learning_diary_admin_template_print_header');

// Add sidebar loginout
add_action('adminmenu','learning_diary_admin_menu_header');

// Add sidebar loginout
add_action('admin_head','learning_diary_admin_template_print_style');


/* 
 * Add link (menu item) to the network admin section (new in wp 3.1) and back to the site admin section 
*/

global $wp_version;

if ( version_compare( $wp_version,"3.1",">=" ) && is_super_admin()) {
	add_action( 'network_admin_menu', 'learninglog_diary_network_admin_menu', 10);
	add_action( 'admin_menu', 'learninglog_diary_site_admin_menu', 10);
}

/*
 * Add Css Style Sheet in WP Admin Section
 */

function learning_diary_admin_template_add_style() {
	//$myStyleUrl = WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) ) . '/admin.css';
	$myStyleUrl = WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary-admin-css/admin.css';
	wp_register_style('learning_diary_admin_template_style', $myStyleUrl);
	wp_enqueue_style( 'learning_diary_admin_template_style');
}

/*
 * Add support for costum header image
 */

function learning_diary_admin_template_print_style() {
	if(!is_super_admin()){
	?>
		<style type="text/css">
			#admin_header { background-image: url(<?php header_image() ?>); }
			<?php if ( 'blank' == get_header_textcolor() ) { ?>
			#admin_header h1, #header #desc { display: none; }
			<?php } else { ?>
			#admin_header h1 a, #desc { color:#<?php header_textcolor() ?>; }
			<?php } ?>
		</style>
	<?php
	}
}

/*
 * Add Header in Admin Section
 */

function learning_diary_admin_template_print_header(){
	
	?>
	<div id="admin_header">
	
			<h1 id="logo"><a href="<?php echo site_url() ?>" title="<?php _e( 'Home', 'buddypress' ) ?>"><?php //bp_site_name() ?></a></h1>
	
			<ul id="nav">
				<?php $base_site_url = get_blog_details( 1 )->siteurl; ?>
				<li<?php if ( bp_is_page( 'home' ) && get_current_site()->site_name==get_bloginfo( 'id' )) : ?> class="selected"<?php endif; ?>>
					<a href="<?php echo $base_site_url ?>" title="<?php _e( 'Home' ) ?>"><?php _e( 'Home' ) ?></a>
				</li>
				
				<?php if( is_user_logged_in() ) { ?>
				
					<li<?php if ( (get_active_blog_for_user(get_current_user_id())->siteurl) == get_bloginfo( 'url' )  && !is_admin()) : ?> class="selected"<?php endif; ?>>
						<a href="<?php echo (get_active_blog_for_user(get_current_user_id())->siteurl) ?>" title="<?php _e( 'Mein Lerntagebuch', 'bp_learning_diary' ) ?>"><?php _e( 'Mein Lerntagebuch', 'bp_learning_diary' ) ?></a>
					</li>
				
					<li<?php if (is_admin()) : ?> class="selected"<?php endif; ?>>
						<a href="<?php echo get_active_blog_for_user(get_current_user_id())->siteurl ?>/wp-admin/" title="<?php _e( 'Administration', 'buddypress' ) ?>"><?php _e( 'Administration', 'buddypress' ) ?></a>
					</li>
				
				<?php } ?>

				<?php if ( bp_is_active( 'groups' ) ) : ?>
					<li<?php if ( bp_is_page( BP_GROUPS_SLUG ) || bp_is_group() ) : ?> class="selected"<?php endif; ?>>
						<a href="<?php echo $base_site_url ?>/<?php echo BP_GROUPS_SLUG ?>/" title="<?php _e( 'Groups', 'buddypress' ) ?>"><?php _e( 'Groups', 'buddypress' ) ?></a>
					</li>
				<?php endif; ?>
				
				<?php if ( bp_is_active( 'activity' ) && bp_core_is_multisite() ) : ?>
					<li<?php if ( bp_is_page( BP_BLOGS_SLUG ) ) : ?> class="selected"<?php endif; ?>>
						<a href="<?php echo $base_site_url ?>/<?php echo BP_BLOGS_SLUG ?>/" title="<?php _e( 'Members', 'buddypress' ) ?>"><?php _e( 'Members', 'buddypress' ) ?></a>
					</li>
				<?php endif; ?>

			</ul><!-- #nav -->
	
		</div><!-- #header -->
						
		<?php 


}

/*
 * show avatar and logout button at the bottom of the administration navigation
 */

function learning_diary_admin_menu_header(){
	?>
		<li>
		<div id="sidebar-me-top-border"><?php _e('My Profile', 'buddypress')?></div>
		<div id="sidebar-me">
			<a href="<?php echo bp_loggedin_user_domain() ?>">
				<?php bp_loggedin_user_avatar( 'type=thumb&width=40&height=40' ) ?>
			</a>
	
			<h4><?php bp_loggedinuser_link() ?></h4>
			<a class="button logout" href="<?php echo wp_logout_url( bp_get_root_domain() ) ?>"><?php _e( 'Log Out', 'buddypress' ) ?></a>
	
		</div>
		</li>
		<?php 	
}

/*
 *	Hide useless functions on custom header page
 */

function ld_hide_useless_functions_on_custom_header_page()
{
	wp_register_style('customHeaderStyle', WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary-admin-css/custom-header.css');

	wp_enqueue_style('customHeaderStyle');
}

add_action("admin_print_styles-appearance_page_custom-header", "ld_hide_useless_functions_on_custom_header_page");

/*
 * Add link (menu item) to the site admin section and in the network admin section (new in wp 3.1) 
 */

function learninglog_diary_network_admin_menu()
{
	global $menu;
	
	$super_admin_menu = array(__('Site Admin'), 'manage_network', '../ ', '', 'menu-top menu-top-first menu-icon-site', 'menu-site', 'div');
	$separator = array( '', 'read', 'separator1', '', 'wp-menu-separator' );
	
	array_unshift($menu, $separator);
	array_unshift($menu, $super_admin_menu);

}

/*
 * Add link (menu item) to the network admin section (new in wp 3.1) in the site admin section 
 */

function learninglog_diary_site_admin_menu()
{
	global $menu;
	
	//snipped from wp-admin/menu.php
	$plugin_update_count = $theme_update_count = $wordpress_update_count = 0;
	$update_plugins = get_site_transient( 'update_plugins' );
	if ( !empty($update_plugins->response) )
		$plugin_update_count = count( $update_plugins->response );
	$update_themes = get_site_transient( 'update_themes' );
	if ( !empty($update_themes->response) )
		$theme_update_count = count( $update_themes->response );
	$update_wordpress = get_core_updates( array('dismissed' => false) );
	if ( !empty($update_wordpress) && !in_array( $update_wordpress[0]->response, array('development', 'latest') ) )
		$wordpress_update_count = 1;

	$total_update_count = $plugin_update_count + $theme_update_count + $wordpress_update_count;
	
	if(!empty( $total_update_count)){
		$up_count_html = '<span class="update-plugins"><span class="update-count">' . $total_update_count . '</span></span>';
	}else{
		$up_count_html = '';
	}
	
	$site_admin_menu = array(__('Network Admin') . $up_count_html, 'manage_network', 'network/', '', 'menu-top menu-top-first menu-icon-site', 'menu-site', 'div');
	$separator = array( '', 'read', 'separator1', '', 'wp-menu-separator' );
	
	array_unshift($menu, $separator);
	array_unshift($menu, $site_admin_menu);

}

/*
 * Add a message to the registration page to make sure they do activate their accounts
 */

function ld_add_activation_message_to_registration_page()
{
	if ( 'completed-confirmation' == bp_get_current_signup_step() && bp_registration_needs_activation() )
		echo "<h4>" . __('Du musst dein Benutzerkonto jetzt noch aktivieren', 'bp_learning_diary') . "</h4>";
}

add_action('template_notices','ld_add_activation_message_to_registration_page');

/*
 * Style category and tag pages
 */

function ld_style_cat_tag_page()
{
	wp_register_style('styleCatTag', WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary-admin-css/edit-tags-php.css');

	wp_enqueue_style('styleCatTag');
	
}

add_action("admin_print_styles-edit-tags.php", 'ld_style_cat_tag_page');

/*
 * Style add link page (probably a wordpress bug)
 */

function ld_style_link_page()
{
	wp_register_style('styleLink', WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary-admin-css/link-add-php.css');

	wp_enqueue_style('styleLink');
	
}

add_action("admin_print_styles-link-add.php", 'ld_style_link_page');

/*
 * Disable / hide number of columns option on the options screen
 */

function ld_disable_screen_layout_columns($cols, $id, $scr)
{
	if($id == 'dashboard')
		return null;	
	else
		return $cols;
}
//apply_filters('screen_layout_columns', ...) is located in wp-admin/includes/template.php
add_filter('screen_layout_columns', 'ld_disable_screen_layout_columns', 10, 3);

function restore_admin_menu()
{
	wp_enqueue_script ( 
		"restore_admin_menu", 
		(WP_PLUGIN_URL . '/' . LEARNING_DIARY_TASKS_PLUGIN_URL . '/bp-learning-diary-admin-css/admin.js'),
		array ('jquery' ) 
	);
}

add_action('admin_init', 'restore_admin_menu');
