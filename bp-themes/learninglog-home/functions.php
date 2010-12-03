<?php


/* Add a admin link after the avatar */
function bp_lerntagebuch_admin_link() {
	?>
	
	<div id="admin-link">
		<a class="button buttonblue" href="<?php echo get_active_blog_for_user(get_current_user_id())->siteurl; ?>/wp-admin">
			<?php _e("Administration") ?>
		</a>
	</div>

	<?php
}

if(is_user_logged_in()) {
	add_action( 'bp_after_sidebar_me', 'bp_lerntagebuch_admin_link' );
}

/*
 *  Check if ip-Adress is from Switzerland	
 */
/*	
function bp_lerntagebuch_check_country(){
	//Check if ip-to-country Plugin is activated:	
	if (class_exists('PepakIpToCountry')){
		//Get Country Code
		$country = PepakIpToCountry::IP_to_Country_XX( getRealIpAddr() );

		if ( ( $country != "CH" ) && ( $country != "LI" ) ) {
			?>
			<h1>Herkunfts-Check:</h1>
			<p>Sehr geehrte/r Nutzer/in von &laquo;lerntagebuch.ch&raquo;<br><br>
			  Aufgrund Ihrer IP-Adresse haben wir festgestellt, 
			  dass Sie sich nicht in der Schweiz befinden. Unser Service ist nur f&uuml;r Schweizer Institutionen des &ouml;ffentlichen Bildungswesens frei zug&auml;nglich.<br /><br />
			  Wir bitten Sie um Verst&auml;ndnis!<br /><br />
			  Falls unsere &Uuml;berpr&uuml;fung fehlerhaft war, bitten wir Sie uns eine E-Mail mit den folgenden Angaben an die E-Mail Adresse andrea.cantieni@phz.ch zu senden:<br />
			  Name, Vorname, gew&uuml;nschter Benutzername f&uuml;r &laquo;lerntagebuch.ch&raquo;, Name und Adresse ihrer Bildungsinstitution.<br /><br />
			  Wir er&ouml;ffnen Ihnen ein Konto auf &laquo;lerntagebuch.ch&raquo; und kontaktieren Sie per E-Mail.
			<br><br></p>
			<?php 
			echo "</div></div>";
			locate_template( array( 'sidebar.php' ), true );
			get_footer();
			die();
		}
	}
}

add_action( 'bp_before_register_page' , 'bp_lerntagebuch_check_country' );


function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
*/

/*
 * Add left sided sidebar
 */

if ( function_exists('dynamic_sidebar') )
    register_sidebar(array(
        'name' => 'SidebarLeft',
        'before_widget' => '<div class="sidebar-left">',
        'after_widget' => '</div>',
        'before_title' => '',
        'after_title' => '',
    ));
	
    
/*
 * Add link to Blog for display_name in buddypress/groups/<the_group>/members
 */    
    
add_filter( 'bp_get_group_member_link', 'add_primary_blog_to_bp_get_group_member_link',10,1 );


function add_primary_blog_to_bp_get_group_member_link($group_member_link){
	global $members_template;
	
	
	$user_id = $members_template->member->user_id;
	
	$primary_blog_id = get_user_meta($user_id, 'primary_blog',true);
	
	if($primary_blog_id)
		$primary_blog_domain = get_blogaddress_by_id($primary_blog_id);
	
	//if user has blog: return blog link
	if($primary_blog_domain)
		echo "<a href='$primary_blog_domain'>".$members_template->member->display_name."</a>"; //if user has blog: return blog link
	else
		echo $group_member_link; //if user doesn't have a blog: return member link
	//if user doesn't have a blog: return member link
	
}

/*
 * Add link to Blog for Avatar in buddypress/groups/<the_group>/members
 */    

add_filter( 'bp_get_group_member_domain', 'add_primary_blog_to_bp_group_member_domain',10,1 );

function add_primary_blog_to_bp_group_member_domain($groub_member_domain){
	
	global $members_template;
	
	$user_id = $members_template->member->user_id;
	
	$primary_blog_id = get_user_meta($user_id, 'primary_blog',true);
	
	if($primary_blog_id)
		$primary_blog_domain = get_blogaddress_by_id($primary_blog_id);
	
	if($primary_blog_domain)
		echo $primary_blog_domain; //if user has blog: return blog link
	else
		echo $groub_member_domain; //if user doesn't have a blog: return member link
}
?>