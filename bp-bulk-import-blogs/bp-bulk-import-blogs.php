<?php
/*
 * Description: Bulk Import Members (Users) to your Wordpress MU + BuddyPress installation.
 * by Thomas Moser
 */

/* 
 * Check if multisite is activated 
 */

if(is_multisite()){
	# Check if buddypress is activated
	//add_action( 'bp_init', 'bulk_import_blogs_init' );
	//add_action( 'init', 'bulk_import_blogs_init' );
	add_action( 'wp_loaded', 'bulk_import_blogs_init' );
	
}

function bulk_import_blogs_init() {
    $usermassimport = new UserMassImport;
}

Class UserMassImport {
	function __construct () {
	
	#actions
		add_action( 'admin_menu', array( $this,'add_admin_menu' ), 11 );
		add_action( 'network_admin_menu', array( $this,'add_network_admin_menu' ), 11 );
	
		if( isset( $_GET[ "batchimportusers" ] ) ) {
			add_action( 'plugins_loaded', array( $this, 'remove_bp_core_filter_blog_welcome_email' ) , 200 );
		}
		
	}
	
	/*
	 * 
	 * 'bp_core_filter_blog_welcome_email' removes the password from the welcome mail. 
	 * This function removes the filter. So the welcome Mail is sent with the password.
	 * 
	 */
	
	public function remove_bp_core_filter_blog_welcome_email () {
		remove_filter( 'update_welcome_email', 'bp_core_filter_blog_welcome_email', 10 );		
	}
	
	/*
	 * Add admin menu according to users
	 */
	
	public function add_admin_menu() {
		global $current_user;
							
		if ( is_super_admin() ) {
			//add_menu_page(__("Importadministration", 'bp_learning_diary'), __("Importadministration", 'bp_learning_diary'), 2, "import-admin", array($this,'admin_validate') ,'');
				       
		} else if( get_usermeta( $current_user->ID,'learning_diary_tasks_teacher' ) ) {
			
			add_menu_page(__("Lernende registrieren", 'bp_learning_diary'), __("Lernende registrieren", 'bp_learning_diary'), 2, "import-user", array($this,'single_user_import') ,'',6);
						
			add_submenu_page('import-user', 'Single User Import', __("Eine/n Lernende/n registrieren", 'bp_learning_diary'), 3, "import-user", array($this,'single_user_import') );
			//if user is a teacher:			
			if ( get_usermeta($current_user->ID,'ask_for_access_to_user_import')=="Accepted" )
				//Access granted
			
				add_submenu_page( "import-user", 'Bulk Import Users', __("Mehrere Lernende registrieren", 'bp_learning_diary'), 3 , "bulk-user-import", array($this,'user_import') );

			else if ( get_usermeta($current_user->ID,'ask_for_access_to_user_import')=="Asked" )
				//Waiting for access
				add_submenu_page( 'import-user', 'Bulk Import Users', __("Mehrere Lernende registrieren", 'bp_learning_diary'), 3 , "bulk-user-import", array($this,'user_import_wait') );

			else
				//Form for asking for access
				add_submenu_page( 'import-user', 'Bulk Import Users', __("Mehrere Lernende registrieren", 'bp_learning_diary'), 3 , "bulk-user-import", array($this,'get_access_for_user_import') );

		}
	
	}
	
	/*
	 * Add network admin menu to network admin section of super admin
	 */ 
	
	public function add_network_admin_menu()
	{
		if ( is_super_admin() ) {
			add_menu_page(__("Importadministration", 'bp_learning_diary'), __("Importadministration", 'bp_learning_diary'), 2, "import-admin", array($this,'admin_validate') ,'');
		}
	}
	
	/*
	 * Sysadmin handle admin input
	 */
	
	
	private function admin_validate_handle($action){
		
		switch($action) {
			
			case "grant permission":
				if($_POST["user_id"]){
					
					$the_message = __(
						"Lieber Nutzer von «lerntagebuch.ch»\n\n"
						. "Deine Anfrage zum Massenimport wurde bearbeitet.\n\n"
						. "Du kannst nun Nutzer auf «lerntagebuch.ch» importieren.\n\n"
						. "Herzliche Grüsse\n"
						. "Dein «lerntagebuch.ch»-Team", 
						'bp_learning_diary'
					);
					
					$user_mail = get_userdata($_POST["user_id"])->user_email;
					
					wp_mail($user_mail, __('Zugang zum Massenimport genehmigt', 'bp_learning_diary'), $the_message);
			
					update_usermeta( intval ($_POST["user_id"]), 'ask_for_access_to_user_import', "Accepted" );
				}	
				break;
				
			case "deny permission":
				if($_POST["user_id"])
					update_usermeta( intval ($_POST["user_id"]), 'ask_for_access_to_user_import', "Denied" );
				break;
				
		}
		
		
	}
	
	/*
	 * Sysadmin show users that asked for permission and grant or deny
	 */
	
	public function admin_validate(){
		
		global $wpdb;
		
		if($_POST["accept"])
			$this->admin_validate_handle("grant permission");
		if($_POST["deny"])
			$this->admin_validate_handle("deny permission");
		
		$users =  $wpdb->get_results("SELECT user_id FROM $wpdb->usermeta WHERE meta_key='ask_for_access_to_user_import'",ARRAY_A);
		
		$actions = array ("Asked", "Accepted", "Denied");

		//show all users who asked for permission
		?>	
		<div class="wrap">
	    	<h2><?php _e("Mehrfachregistration validieren", 'bp_learning_diary') ?></h2>
	    	
	    	<?php foreach($actions as $the_action){ ?>
	    	<h3><?php echo $the_action; ?></h3>
	    	<table width="100%" cellpadding="3" cellspacing="3" class="widefat">
	    	<thead>
	    		<tr>
		    		<th><?php _e('Username') ?></th>
		    		<th><?php _e("Name"); ?></th>
		    		<th><?php _e("Name der Institution", 'bp_learning_diary'); ?></th>
		    		<th><?php _e("Address"); ?></th>
		    		<th></th>
		    		<th><?php _e("Rolle", 'bp_learning_diary'); ?></th>
		    		<th><?php _e("Bemerkungen", 'bp_learning_diary'); ?></th>
		    		<th></th>
		    		<th></th>
				</tr>
			</thead>
			
			<tbody>
				<?php foreach($users as $the_user) { 
						if(get_user_meta($the_user["user_id"], "ask_for_access_to_user_import", true)==$the_action) {
							$userdata = get_userdata($the_user["user_id"]);
							?>
							<tr>
								<td><?php echo $userdata->user_login; ?></td>
					    		<td><?php echo get_user_meta($the_user["user_id"], "institution_teacher_name", true);?></td>
					    		<td><?php echo get_user_meta($the_user["user_id"], "institution_name", true); ?></td>
					    		<td><?php echo get_user_meta($the_user["user_id"], "institution_adress_1", true); ?></td>
					    		<td><?php echo get_user_meta($the_user["user_id"], "institution_adress_2", true); ?></td>
					    		<td><?php echo get_user_meta($the_user["user_id"], "institution_role", true); ?></td>
					    		<td><?php echo get_user_meta($the_user["user_id"], "institution_bemerkungen", true); ?></td>
					    		<td>
						    		<form name="post" action="admin.php?page=import-admin" method="post">
						    			<input type="submit" name="accept" id="accept" value="<?php _e("Grant permission"); ?>" />
						    			<input type="hidden" name="user_id" id="user_id" value="<?php echo $the_user["user_id"]; ?>" />
						    		</form>
								</td>
								<td>
									<form name="post" action="admin.php?page=import-admin" method="post">
						    			<input type="submit" name="deny" id="deny" value="<?php _e("Deny permission"); ?>" />
						    			<input type="hidden" name="user_id" id="user_id" value="<?php echo $the_user["user_id"]; ?>" />
						    		</form>
								</td>
							</tr>
							<?php 
						} //end if 
					} //end foreach $users ?>	
			</tbody>
	    	</table>
	    	<?php 
	    	} //end foreach $actions ?>
		</div>
		<?php
	} //end function admin_validate()
	
	/*
	 * Prevent Mail to be sent in signup_notification while mass importing users
	 */
	
	public function prevent_signup_notification(){
		return false;
	}
	
	private function fieldParseFunction($text){
		return explode("\n", trim($text));
	}
	
	/*
	 * Show infomration if permission has not been granted yet
	 */
	
	public function user_import_wait(){
		?>
		<div class="wrap">
	    	<h3><?php _e("Die Anfrage wurde gesendet und wird innerhalb von 2 Wochen bearbeitet.", 'bp_learning_diary') ?></h3>
	    </div>
	    
		<?php 
	}
	
	/*
	 * Handle the user permission
	 */
	
	private function user_permission_handle() {
		global $current_user;
				
		//DATA VALIDATION
		
		$institution_teacher_name = esc_html ($_POST["institution_teacher_name"]);
		$institution_name = esc_html ($_POST["institution_name"]);
		$institution_adress_1 = esc_html ($_POST["institution_adress_1"]);
		$institution_adress_2 = esc_html ($_POST["institution_adress_2"]);
		$institution_role = esc_html ($_POST["institution_role"]);
		$institution_bemerkungen = esc_html ($_POST["institution_bemerkungen"]);
		
		
		if($institution_teacher_name=="" || $institution_name=="" || $institution_adress_1=="" || $institution_adress_2=="" || $institution_role=="" ){
			
			$error = __("Bitte alle Felder mit * ausf&uuml;llen.", 'bp_learning_diary');
			return $error;
			
		} else {
		
			//INSERT USER_META
			update_user_meta( $current_user->ID, 'ask_for_access_to_user_import', "Asked" );
			update_user_meta( $current_user->ID, 'institution_teacher_name', $institution_teacher_name );
			update_user_meta( $current_user->ID, 'institution_name', $institution_name );
			update_user_meta( $current_user->ID, 'institution_adress_1', $institution_adress_1 );
			update_user_meta( $current_user->ID, 'institution_adress_2', $institution_adress_2 );
			update_user_meta( $current_user->ID, 'institution_role', $institution_role );
			update_user_meta( $current_user->ID, 'institution_bemerkungen', $institution_bemerkungen );
			
			//$current_user->mail;

			$the_message = sprintf(__(
				"Angaben zum Nutzer: \n\n" 
				. "ID: %1$s  \n\n"
				. "Benutzername: %2$s \n\n"
				. "Mail: %3$s \n\n \n\n"
				. "Weitere Angaben:\n\n"
				, 'bp_learning_diary'),
				$current_user->ID,
				$current_user->user_nicename,
				$current_user->user_email
			)
			. $institution_teacher_name . "\n\n"
			. $institution_name . "\n\n"
			. $institution_adress_1 . "\n\n" 
			. $institution_adress_2 . "\n\n" 
			. $institution_role . "\n\n"
			. $institution_bemerkungen . "\n\n";
			
			$admin_mail = get_userdata(1)->user_email;
			
			wp_mail($admin_mail, __('Anfrage für Massenimport' ,'bp_learning_diary'), $the_message);
			
			$this->user_import_wait();
		
			return true;
			
		}
		
	}
	
	/*
	 * Ask for Permission for the mass import tool.
	 */
	
	public function get_access_for_user_import(){
		
		global $current_user;
				
		if($_POST["submit"]==__("Submit")) {
			$error = $this->user_permission_handle();
			if ( $error === true ){
				return;
			}
		}
		?>
		
		<div class="wrap">
	  	<div id="icon-users" class="icon32"><br /></div>
	    <h2 id="add-new-user"><?php _e("Zugang zur Mehrfachregistration beantragen", 'bp_learning_diary') ?></h2>
	    <p>
	    	<?php 
	
	    	_e("Hier kannst Du mehrere Nutzer gleichzeitig erfassen. Um diese Funktion zu nutzen, musst Du einen Zugang beantragen, indem Du untenstehendes Formular ausfüllst.", 'bp_learning_diary');
	    	
	    	?>
	    </p>
		<p><small><?php _e("Wir benötigen diese Angaben um einen Missbrauch dieser Funktion zu vermeiden. Nach der Prüfung Deiner Angaben wird diese Funktion für dich freigeschaltet.", 'bp_learning_diary'); ?></small></p>
	    
	    <?php if($error){ ?>
	    <div class="error"><p><?php echo $error; ?></p></div>
	    <?php } ?>
	    
	    <!--<form  name="post" action="admin.php?page=bp-user-import.php" method="post">-->
		<form  name="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	    <input type="hidden" id="ID" name="ID" value="<?php echo $current_user->ID; ?>" />
	    <input type="hidden" id="user_login" name="user_login" value="<?php echo $current_user->user_login; ?>" />
	    
	    <table>
	    	<tr>
	    		<td><label for="institution_teacher_name"><?php _e("Vorname, Nachname", 'bp_learning_diary'); ?>*</label> </td>
	    		<td><input type="text" id="institution_teacher_name" name="institution_teacher_name" value="<?php echo $_POST["institution_teacher_name"]; ?>" /></td>
	   		</tr>
	   		<tr>
	    		<td><label for="institution_name"><?php _e("Name der Bildungsinstitution", 'bp_learning_diary'); ?>* </label></td>
	    		<td><input type="text" id="institution_name" name="institution_name" value="<?php echo $_POST["institution_name"]; ?>" /></td>
	   		</tr>
	   		<tr>
	    		<td><label for="institution_adress_1"><?php _e("Address"); ?>* </label></td>
	    		<td><input type="text" id="institution_adress_1" name="institution_adress_1" value="<?php echo $_POST["institution_adress_1"]; ?>" /></td>
	   		</tr>
	   		<tr>
	    		<td><label for="institution_adress_2"><?php _e("PLZ, Ort", 'bp_learning_diary'); ?>*</td>
	    		<td><input type="text" id="institution_adress_2" name="institution_adress_2" value="<?php echo $_POST["institution_adress_2"]; ?>" /></td>
	   		</tr>
	   		<tr>
	    		<td><label for="institution_role"><?php _e("Ihre Rolle in der Bildungsinstitution", 'bp_learning_diary'); ?>*&nbsp;&nbsp;</label></td>
	    		<td><input type="text" id="institution_role" name="institution_role" value="<?php echo $_POST["institution_role"]; ?>" /></td>
	   		</tr>
	   		<tr>
	    		<td><label for="institution_bemerkungen"><?php _e("Bemerkungen", 'bp_learning_diary'); ?></label></td>
	    		<td><textarea id="institution_bemerkungen" name="institution_bemerkungen"><?php echo $_POST["institution_bemerkungen"]; ?></textarea></td>
	   		</tr>
	   		<tr>
	    		<td></td>
	    		<td><input type="submit" name="submit" value="<?php _e("Submit") ?>" /></td>
	   		</tr>
	    	 	    	
	    	
	    </table>	
	    </form>
		<p><small><?php _e("* Diese Angaben sind Pflichtangaben.", 'bp_learning_diary'); ?></small></p>
	    </div>
		
		<?php 
	}

	
	/*
	 * Import single user
	 */
	
	public function single_user_import(){
		global $current_user;
		global $wpdb;
	    // Plugin content here will only be accessible by site admins
	
		$RecordErrors="";
		$ErrorCount=0;
		$complete=0;
		$Result = "";
		
		if (isset($_GET['key'])){
			print_r(bp_core_activate_signup ($_GET['key']));
			//bp_core_activate_signup ($_GET['key']);
			return;	
		}
		  
		if (isset($_POST['data_submit'])) {
	
	
			// Get form data into an array
			
			$username = trim( (string) $_POST["username"]);
			$email = trim( (string) $_POST["email"]);
			$name = explode(' ', (string) $_POST["name"], 2);
			$firstname = $name[0];
			$lastname = $name[1];
			
	        /*
	         * Check for Errors
	         */
			
			$results_validate_user_signup = bp_core_validate_user_signup($username,$email);
	            
	        $user_data["results_validate_user_signup"] = $results_validate_user_signup;
	            
	        $results_validate_blog_signup = bp_core_validate_blog_signup($username,$username);
	            
	        $user_data["results_validate_blog_signup"] = $results_validate_blog_signup;
	        
	        do_action( 'bp_signup_validate' );
	        
	        if ( $results_validate_blog_signup["errors"]->errors || $results_validate_user_signup["errors"]->errors ) {
	        	
	        	if ( $results_validate_user_signup["errors"]->get_error_message('user_name') )
		            $RecordErrors .= "<br/>".__($results_validate_user_signup["errors"]->get_error_message('user_name'),"buddypress")." ";
		                     
                if ( $results_validate_user_signup["errors"]->get_error_message('user_email') )
                    $RecordErrors .= "<br/>".__($results_validate_user_signup["errors"]->get_error_message('user_email'),"buddypress")." ";
                    
                if ( $results_validate_blog_signup["errors"]->get_error_message('blogname') )
                    $RecordErrors .= "<br/>".__($results_validate_blog_signup["errors"]->get_error_message('blogname'),"buddypress")." ";
                                    
                if (($firstname == '') || ($lastname == '')) 
					$RecordErrors .= "<br/> ". __("Vor- und Nachnamen angeben. ", 'bp_learning_diary');
					
				$ErrorCount = 1;
	        }
			
	         $complete = 0;
	        
	  		 if ( $ErrorCount == 0 ) {
	  		 	
				/*
				 * Signup Blog if there are no errors
				 */
	  		 	
	  		 	//Add filter for preventing sending the signup blog notification mail
	  		 	add_filter( 'wpmu_signup_blog_notification' , array($this,'prevent_signup_notification'),1);
	  			
  				// populate user data hash
                $user = array( 'user_login' => $username, 'user_email' => $email, 'role' => $the_role, 'user_pass' => $password );

                    // Add first/last name if defined
                $user['first_name'] = $firstname;
                $user['last_name'] = $lastname;
                    

                $usermeta['public'] = true;

				$usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );
				
				$usermeta['first_name'] = $user['first_name'];
				
				$usermeta['last_name'] = $user['last_name'];
				
				$usermeta['learning_diary_tasks_student'] = true;
				
				$usermeta['join_groups'] = array();
				
				if($_POST["join_groups"]){
					foreach($_POST["join_groups"] as $the_group_id){
						$usermeta['join_groups'][] = $the_group_id;
					}
				}	
				
				bp_core_signup_blog( $user_data["results_validate_blog_signup"]["domain"], $user_data["results_validate_blog_signup"]["path"], $user['user_login'], $user['user_login'], $user['user_email'], $usermeta );

				//get key
				$key = $wpdb->get_var("SELECT activation_key FROM $wpdb->signups WHERE user_login='".$user['user_login']."'");
				
				usleep(20000);
				
				$domain = get_blog_details( 1 )->siteurl;
				
				$html_code = wp_remote_retrieve_body( wp_remote_get("$domain/activate?batchimportusers=true&key=$key"));
				
				$complete++;
					
			}
				
	        if ($RecordErrors){
	
	            echo '<div class="error"><strong>' . __('Error') . ': ' . '</strong>' . $RecordErrors . '</div>';
	
	        } else {
	        	
	        	$_POST["textarea_data"] = "";
		        if ($complete>0){
		
		            echo '<div class="updated fade">' . __('Lerntagebuch erfolgreich erstellt', 'bp_learning_diary') . ' <br />'. $RecordErrors .'</div>';
		
		        }
	        	
	        }
	       
	    }
	
	  ?>
	  <div class="wrap">
	  <div id="icon-users" class="icon32"><br /></div>
	    <h2 id="add-new-user"><?php _e("Eine/n Lernende/n registrieren", 'bp_learning_diary'); ?></h2>
	        <p><?php _e('1. Benutzername, Vorname, Nachname und E-Mail-Adresse des/der Lernenden eingeben.', 'bp_learning_diary') ?></p>
	        <p><?php _e('2. Die/den Lernende(n) einer Gruppe zuordnen.', 'bp_learning_diary') ?></p>
	        <p><?php _e('3. Die/der Lernende bekommt eine E-Mail mit ihren/seinen Nutzernamen und Passwort an die angegebene E-Mail-Adresse.', 'bp_learning_diary') ?></p>
		
	        <form enctype="multipart/form-data" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>"  >
	            <input type="hidden" name="data_submit" id="data_submit" value="true" />
	
	
	            <div class="form-table">
	                <input type="hidden" name="delimiter" value=";" />
	                    <table id="group_1" class="widefat">
	                            
	                            <tbody id="the-list">
	                               <tr class="header nodrag"  >
	                                    <td style="text-align:right"><?php _e('Username')?></td>
	                                    <td><input name="username" id="username" value="<?php echo $_POST["username"]; ?>" /></td>
	                               </tr>
	                               <tr class="header nodrag"  >
	                                    <td style="text-align:right"><?php _e('Vor- und Nachname', 'bp_learning_diary')?></td>
	                                    <td><input name="name" id="name" value="<?php echo $_POST["name"]; ?>" /></td>
	                               </tr>
	                               <tr class="header nodrag"  >
	                                    <td style="text-align:right"><?php _e('E-Mail-Adresse', 'bp_learning_diary')?></td>
	                                    <td><input name="email" id="email" value="<?php echo $_POST["email"]; ?>" /></td>
	                               </tr>
	                               <tr valign="top">
	                                    <td scope="row"  style="text-align:right"><?php _e('Nutzer der folgenden Gruppe(n) anschliessen', 'bp_learning_diary') ?></td>
	
	                                    <td>
	                                    <?php 
	                                    
	                                    $groups = groups_get_user_groups($current_user->ID,0,100);
	                                    
                       
	                                    foreach($groups["groups"] as $the_group){
	                                    	$group = new BP_Groups_Group( $the_group );
	                                    	$group_name = $group->name;
											$checked = false;
											if($_POST["join_groups"][$group->id])
												$checked = "checked";
	                                    	?>
											
	                                        <label><input name="join_groups[<?php echo $group->id; ?>]" type="checkbox" id="join_groups[<?php echo $group->id; ?>]" value='<?php echo $group->id; ?>' <?php echo $checked; ?> /> <?php echo $group_name ?></label><br />
								<?php }?>
	
	                                    </td>
	                                </tr>
	                               <tr valign="top">
	                                    <th scope="row">
	                                    </th>
	
	                                    <td>
	                                    
										<?php 
	                                    
	                                    	_e('Der Lernende oder die Lernende bekommt eine E-Mail mit dem Benutzernamen und Passwort zugesendet.', 'bp_learning_diary');
	                                    
	                                    ?>
	                                    
	                                    </td>
	                                </tr>
	
	                            </tbody>
	                </table>
	            </div>
	
	            <div class="submit">
	                <input type="submit" name="data_submit" value="<?php _e('Lernende/r registrieren', 'bp_learning_diary'); ?> &raquo;" />
	            </div>
	        </form>
	
	  </div><?php
	  
	}

	
	/*
	 * Shows user import field
	 */
	
	function user_import() {
		global $current_user;
		global $wpdb;
	    // Plugin content here will only be accessible by site admins
	
		$RecordErrors="";
		$ErrorCount=0;
		$complete=0;
		$Result = "";
		
		if (isset($_GET['key'])){
			print_r(bp_core_activate_signup ($_GET['key']));
			//bp_core_activate_signup ($_GET['key']);
			return;	
		}
		  
		if (isset($_POST['data_submit'])) {
	
			$delimiter = (string) $_POST['delimiter'];
	
			// Get form data into an array
			$user_data_temp = array();
			if( trim ( (string) $_POST["textarea_data"] ) != "" ){
				$user_data_temp = array_merge($user_data_temp, $this->fieldParseFunction(((string) ($_POST["textarea_data"]))));
			}else{
				$RecordErrors .= "<p><strong>"
					. __('Bitte gib mindestens eine Zeile in der folgenden Form ein:', 'bp_learning_diary')
					. "</strong><br />"
					. __('Benutzername;Vorname Nachname;email@domainname.ch', 'bp_learning_diary')
					. "</p>";
				$ErrorCount++;
			}
	
			$user_data = array();
			$i = 0;
			
	
			foreach ($user_data_temp as $ut) {
				
				if(substr_count($ut, $delimiter) != 2){
					$user_data[$i]["errors"] = __("Ungenügende Angaben", 'bp_learning_diary');
				}
				
				if (trim($ut) != '') {
					
					// split out username, fullname and email
					if ( ! ($user_strings = explode($delimiter, $ut, 3))){
						$Result .= "<p>Regex ".$delimiter." not valid.</p>";
	                    echo "<div class='error'>".$Result."</div>";
					}
	
					// split out firstname and lastname from fullname
					$user_name_strings = explode(' ', $user_strings[1], 2);
	
					$my_user_name = trim($user_strings[0]);
					$my_user_fname = trim($user_name_strings[0]);
					$my_user_lname = trim($user_name_strings[1]);
					$my_user_email = trim($user_strings[2]);
	
					$user_data[$i]['username'] = $my_user_name;
					if($my_user_name)
						$check_for_double_entry_username[] = $my_user_name;
					$user_data[$i]['firstname'] = $my_user_fname;
					$user_data[$i]['lastname'] = $my_user_lname;
					$user_data[$i]['email'] = $my_user_email;
					if($my_user_email)
						$check_for_double_entry_email[] = $my_user_email;
					$i++;
	
				}
	
			}
	
	        /*
	         * Check for Errors
	         */
	        
	        $errors = array();
	        
	        // TODO: CHECK IF vorname und nachnahme != "" 

	        if(!$RecordErrors){
		        /*
		         * check username for double entries
		         */
		        
		        if(count($check_for_double_entry_username) && count(array_unique($check_for_double_entry_username))!=count($check_for_double_entry_username)){
		        	 $RecordErrors .= __("Doppelte Werte bei den Benutzernamen.", 'bp_learning_diary') . "<br /> ";
		        	 $ErrorCount++;
		        }
		
		        /*
		         * check email for double entries
		         */
		        
		  		if(count($check_for_double_entry_email) && count(array_unique($check_for_double_entry_email))!=count($check_for_double_entry_email)){
		        	 $RecordErrors .= __("Doppelte Werte bei den E-Mail-Adressen.", 'bp_learning_diary') . "<br /> ";
		        	 $ErrorCount++;
		        }
	        }
	        
			foreach ($user_data as $key => $ud) {
	        		
				$user_line = '(' . __('vergleiche Zeile', 'bp_learning_diary') . ' ' . ($key+1) .': <b>' . htmlspecialchars($ud['username']) . ';' . htmlspecialchars($ud['firstname']) . ' ' . htmlspecialchars($ud['lastname']) . ';' . htmlspecialchars($ud['email']) . '</b>)';
	
	            $results_validate_user_signup = bp_core_validate_user_signup($ud['username'],$ud['email']);
	            
	            $user_data[$key]["results_validate_user_signup"] = $results_validate_user_signup;
	            
	            $results_validate_blog_signup = bp_core_validate_blog_signup($ud['username'],$ud['username']);
	            
	            $user_data[$key]["results_validate_blog_signup"] = $results_validate_blog_signup;
	          	
	            do_action( 'bp_signup_validate' );
	             	
				if ( $results_validate_blog_signup["errors"]->errors || $results_validate_user_signup["errors"]->errors ) {
					
					if ($ud['errors']){
	                     $RecordErrors .= __($ud['errors'],"buddypress");
					}else{
						
						if (($ud['firstname'] == '') || ($ud['lastname'] == '')) 
							$RecordErrors .= " " . __('Vor- und Nachnamen angeben.', 'bp_learning_diary') . " ";
		
		                
		                     
		                if ( $results_validate_user_signup["errors"]->get_error_message('user_name') )
		                     $RecordErrors .= __($results_validate_user_signup["errors"]->get_error_message('user_name'),"buddypress")." ";
		                     
		                if ( $results_validate_user_signup["errors"]->get_error_message('user_email') )
		                     $RecordErrors .= __($results_validate_user_signup["errors"]->get_error_message('user_email'),"buddypress")." ";
					}
	
	                $RecordErrors = $RecordErrors . " " . $user_line."<br />";
	                    
					$ErrorCount++;
	            }
	
	         }
	        
	         $complete = 0;
	        
	  		 if ( $ErrorCount == 0 ) {
	  		 	
				/*
				 * Signup Blog if there are no errors
				 */
	  		 	
	  		 	//Add filter for preventing sending the signup blog notification mail
	  		 	add_filter( 'wpmu_signup_blog_notification' , array($this,'prevent_signup_notification'),1);
	  			
	  			foreach ($user_data as $ud) {
	  			
  				// populate user data hash
                    $user = array( 'user_login' => $ud['username'], 'user_email' => $ud['email'], 'role' => $the_role, 'user_pass' => $password );

                    // Add first/last name if defined
                    if ( ($ud['firstname'] != '') || ($ud['lastname'] != '') ) {
                        $user['first_name'] = $ud['firstname'];
                        $user['last_name'] = $ud['lastname'];
                    } else {
                    	$user['first_name'] = "";
                    	$user['last_name'] = $ud['username'];
                    }
                    
                    //PASSWORD
                    
					$usermeta['public'] = true;

					$usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );
					
					$usermeta['first_name'] = $user['first_name'];
					
					$usermeta['last_name'] = $user['last_name'];
					
					$usermeta['learning_diary_tasks_student'] = true;
					
					$usermeta['join_groups'] = array();
					
					if($_POST["join_groups"]){
						foreach($_POST["join_groups"] as $the_group_id){
							$usermeta['join_groups'][] = $the_group_id;
						}
					}	
					
					bp_core_signup_blog( $ud["results_validate_blog_signup"]["domain"], $ud["results_validate_blog_signup"]["path"], $user['user_login'], $user['user_login'], $user['user_email'], $usermeta );

										
  				}
  				
		  		foreach($user_data as $ud){
					//get key
					$key = $wpdb->get_var("SELECT activation_key FROM $wpdb->signups WHERE user_login='".$ud['username']."'");
					
					usleep(20000);
					
					$domain = get_blog_details( 1 )->siteurl;
					
					$html_code = wp_remote_retrieve_body( wp_remote_get("$domain/activate?batchimportusers=true&key=$key"));
				
					$complete++;
					
				}
			}
			
			
	
	        if ($RecordErrors){
	
	            echo '<div class="error"><h3>' . $ErrorCount . ' ' . __('Error') . '</h3>' . $RecordErrors . '</div>';
	
	        } else {
	        	
	        	$_POST["textarea_data"] = "";
		        if ($complete>0){
		
		            echo '<div class="updated fade">' . $complete . ' ' . __('Lerntageb&uuml;cher erfolgreich erstellt', 'bp_learning_diary') . '<br />' . $RecordErrors . '</div>';
		
		        }
	        	
	        }
	       
	    }
	
	  ?>
	  <div class="wrap">
	  <div id="icon-users" class="icon32"><br /></div>
	    <h2 id="add-new-user"><?php _e("Mehrere Lernende registrieren", 'bp_learning_diary'); ?></h2>
	        <p><?php _e('1. Gib unten den Benutzernamen, Vornamen, Nachnamen und die E-Mailadresse der Lernenden ein.', 'bp_learning_diary') ?></p>
	        <p><?php _e('2. Unterteile die Daten mit einem ";". Zum Beispiel nutzername;Vorname Nachname;email@domainname.ch.', 'bp_learning_diary') ?></p>
	        <p><?php _e('3. Die Lernenden erhalten eine E-Mail mit ihrem Nutzernamen und dem Passwort an die angegebene Mailadresse.', 'bp_learning_diary') ?></p>
		
	        <form enctype="multipart/form-data" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>"  >
	            <input type="hidden" name="data_submit" id="data_submit" value="true" />
	
	
	            <div class="form-table">
	                <input type="hidden" name="delimiter" value=";" />
	                    <table id="group_1" class="widefat">
	                            <thead>
	                                <tr class="nodrag">
	                                    <th scope="col" colspan="2">
											<strong>
												<?php _e("Sie k&ouml;nnen die folgenden Daten in das untenstehende Textfeld kopieren:", 'bp_learning_diary')?><br />
											</strong>
											<?php _e('Benutzername;Vorname Nachname;email@domainname.ch', 'bp_learning_diary')?>
										</th>
	                                </tr>
	
	                            </thead>
	                            <tbody id="the-list">
	                               <tr class="header nodrag"  >
	                                    <td colspan="2"><textarea name="textarea_data" cols="90" rows="12" style="width:100%"><?php echo $_POST["textarea_data"]; ?></textarea></td>
	                               </tr>
	                               <tr valign="top">
	                                    <td scope="row"><?php _e('Nutzer der folgenden Gruppe anschliessen', 'bp_learning_diary') ?></td>
	
	                                    <td>
	                                    <?php 
	                                    
	                                    $groups = groups_get_user_groups($current_user->ID,0,100);
	                                    
                       
	                                    foreach($groups["groups"] as $the_group){
	                                    	$group = new BP_Groups_Group( $the_group );
	                                    	$group_name = $group->name;
											$checked = false;
											if($_POST["join_groups"][$group->id])
												$checked = "checked";
	                                    	?>
											
	                                        <label><input name="join_groups[<?php echo $group->id; ?>]" type="checkbox" id="join_groups[<?php echo $group->id; ?>]" value='<?php echo $group->id; ?>' <?php echo $checked; ?> /> <?php echo $group_name ?></label><br />
								<?php }?>
	
	                                    </td>
	                                </tr>
	                               <tr valign="top">
	                                    <th scope="row">
	                                    
										<?php 
											
										_e('Information') 
										
										?></th>
	
	                                    <td>
	                                    
										<?php 
	                                    
	                                    	_e('Die Lernenden bekommen eine E-Mail mit ihrem Benutzernamen und Passwort zugesendet.', 'bp_learning_diary');
	                                    
	                                    ?>
	                                    
	                                    </td>
	                                </tr>
	
	                            </tbody>
	                </table>
	            </div>
	
	            <div class="submit">
	                <input type="submit" name="data_submit" value="<?php _e('Lernende registrieren', 'bp_learning_diary'); ?> &raquo;" />
	            </div>
	        </form>
	
	  </div><?php
	  
	}
}

?>