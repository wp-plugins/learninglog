<?php


/* Add a admin link after the avatar */
function bp_lerntagebuch_admin_link() {
	?>
	
	<div id="admin-link"><a class="button buttonblue" href="<?php echo get_active_blog_for_user(get_current_user_id())->siteurl; ?>/wp-admin"><?php _e("Administration") ?></a></div>

	<?php
}
if(is_user_logged_in())
	add_action( 'bp_after_sidebar_me', 'bp_lerntagebuch_admin_link' );


//disable current header functions
//define( 'BP_DTHEME_DISABLE_CUSTOM_HEADER', true );


/*
 * lerntagebuch user theme header function
 */
/*
function lerntagebuch_user_add_custom_header_support() {
	// Set the defaults for the custom header image (http://ryan.boren.me/2007/01/07/custom-image-header-api/) 
	define( 'HEADER_TEXTCOLOR', 'FFFFFF' );
	define( 'HEADER_IMAGE', get_bloginfo('stylesheet_directory').'/images/default-header.png' ); // %s is theme dir uri
	define( 'HEADER_IMAGE_WIDTH', 960 );
	define( 'HEADER_IMAGE_HEIGHT', 130 );
	
	function bp_dtheme_header_style() { ?>
		
		<style type="text/css">
			#header { background-image: url(<?php header_image() ?>); }
			<?php if ( 'blank' == get_header_textcolor() ) { ?>
			#header h1, #header #desc { display: none; }
			<?php } else { ?>
			#header h1 a, #desc { color:#<?php header_textcolor() ?>; }
			<?php } ?>
		</style>
	<?php
	}

	function bp_dtheme_admin_header_style() { ?>
		<style type="text/css">
			#headimg {
				position: relative;
				color: #fff;
				background: url(<?php header_image() ?>);
				-moz-border-radius-bottomleft: 6px;
				-webkit-border-bottom-left-radius: 6px;
				-moz-border-radius-bottomright: 6px;
				-webkit-border-bottom-right-radius: 6px;
				margin-bottom: 20px;
				height: 130px;
				padding-top: 25px;
			}

			#headimg h1{
				position: absolute;
				bottom: 15px;
				left: 15px;
				width: 44%;
				margin: 0;
				font-family: Arial, Tahoma, sans-serif;
			}
			#headimg h1 a{
				color:#<?php header_textcolor() ?>;
				text-decoration: none;
				border-bottom: none;
			}
			#headimg #desc{
				color:#<?php header_textcolor() ?>;
				font-size:1em;
				margin-top:-0.5em;
			}

			#desc {
				display: none;
			}

			<?php if ( 'blank' == get_header_textcolor() ) { ?>
			#headimg h1, #headimg #desc {
				display: none;
			}
			#headimg h1 a, #headimg #desc {
				color:#<?php echo HEADER_TEXTCOLOR ?>;
			}
			<?php } ?>
		</style>
	<?php
	}
	add_custom_image_header( 'bp_dtheme_header_style', 'bp_dtheme_admin_header_style' );

}

//add_action( 'init', 'lerntagebuch_user_add_custom_header_support' );
add_action( 'setup_theme', 'lerntagebuch_user_add_custom_header_support' );
*/

//Disable Admin Bar in WordPress 3.2
add_filter( 'show_admin_bar', '__return_false' );
//Disable Admin Bar in Buddypress
define('BP_DISABLE_ADMIN_BAR', true);

//Disable Admin Bar in WordPress 3.3
//snipped from http://wp.tutsplus.com/tutorials/how-to-disable-the-admin-bar-in-wordpress-3-3/
if (!function_exists('disableAdminBar')) {  

	function disableAdminBar(){

		remove_action( 'admin_footer', 'wp_admin_bar_render', 1000 ); // for the admin page
		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 ); // for the front end

		function remove_admin_bar_style_backend() {  // css override for the admin page  
			echo '<style>body.admin-bar #wpcontent, body.admin-bar #adminmenu { padding-top: 0px !important; }</style>';
		}

		add_filter('admin_head','remove_admin_bar_style_backend');

		function remove_admin_bar_style_frontend() { // css override for the frontend
			echo '<style type="text/css" media="screen">
				html { margin-top: 0px !important; }
				* html body { margin-top: 0px !important; }
			</style>';
		}

		add_filter('wp_head','remove_admin_bar_style_frontend', 99);

	}

}

add_action('init','disableAdminBar'); // New version

?>