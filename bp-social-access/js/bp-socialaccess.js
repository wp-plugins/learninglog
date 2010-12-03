/*
 *
 * all html-classes and html-ids start with bpsa- (to prevent operlaps)
 * 
 */

jQuery(document).ready(function($) {
	
	/* disable/enable mapto groups */
	var maptoDisable = function(disabled) {
		var maptoCheckbox = $('*').find('#bpsa-groups-of-user-mapto .bpsa-groups-of-users-checkbox')
		maptoCheckbox.each(function(){
			if (disabled) $(this).attr('disabled', 'disable')
			else $(this).removeAttr('disabled')
		})
		
	}
	
	/* synchronise visible for and map to group */
	var mapto = function(visibleGroup){
		var maptoCheckbox = $('*').find('#bpsa-groups-of-user-mapto .bpsa-groups-of-users-checkbox')
		maptoCheckbox.each(function(){
			if($(this).val()==visibleGroup.val()){
				if(visibleGroup.attr('checked')) $(this).val([visibleGroup.val()])
					else $(this).val([''])
			}
		})
		
	}
	
	/*
	 * 
	 * Toggle .show-members-of-group HTML-Table in post.php and post-edit.php
	 * 
	 */
	
	$(".bpsa-show-members-of-group").each(function() {
		var $thisA = $(this).find("a");
		var $thisTable = $(this).find("table");
		$thisA.click(function() {
			$thisTable.toggleClass("hidden");
		});
	});
	
	/*
	 * 
	 * show groups_of_user or all_users or nothing after beeing ready
	 * 
	 */
	
	$("#bpsa-visible-for").each(function(){
		var $value = $("#bpsa-visible-for option:selected").val();
		maptoDisable(false)
		
		switch ($value){
		case "all":
			//show bpsa-groups-of-user-public
			$("#bpsa-groups-of-user-public").removeClass('hidden');
		break;
		
		case "loggedin_users":
			//do nothing
			break;
			
		case "specific_groups":
			//show bpsa-groups-of-user
			$("#bpsa-groups-of-user").removeClass('hidden');
			maptoDisable(true)
			break;
			
		case "specific_users":
			//show all users
			$("#bpsa-all-users").removeClass('hidden');
			break;
		}
	
	});
	
	/*
	 * 
	 * toggle groups_of_user and all_users 
	 * 
	 */
	
	$("#bpsa-visible-for").change(function() {
		var $value = $("#bpsa-visible-for option:selected").val();
		maptoDisable(false)
				
		switch ($value){
		case "all":
			//hide bpsa-groups-of-user
			$("#bpsa-groups-of-user").addClass('hidden');
			//hide bpsa-all-users
			$("#bpsa-all-users").addClass('hidden');
			//uncheck every bpsa-groups-of-user checkbox
			/*$("#bpsa-groups-of-user input:checkbox").each(function() {
				$(this).val([""]);	
			});
			//uncheck every groups_of_user checkbox
			$("#bpsa-all-users input:checkbox").each(function() {
				$(this).val([""]);	
			});*/
			break;
		case "loggedin_users":
			//hide bpsa-groups-of-user
			$("#bpsa-groups-of-user").addClass('hidden');
			//hide bpsa-all-users
			$("#bpsa-all-users").addClass('hidden');
			break;
		case "specific_groups":
			//show bpsa-groups-of-user
			$("#bpsa-groups-of-user").removeClass('hidden');
			//hide bpsa-all-users
			$("#bpsa-all-users").addClass('hidden');
			//uncheck every bpsa-all-users checkbox
			/*$("#bpsa-all-users input:checkbox").each(function() {
				$(this).val([""]);	
			});*/
			
			$('#bpsa-groups-of-user .bpsa-groups-of-users-checkbox').each(function() {
				mapto($(this))
			})
			maptoDisable(true)
			break;
		case "specific_users":
			//show all useres
			$("#bpsa-all-users").removeClass('hidden');
			//hide bpsa-groups-of-user
			$("#bpsa-groups-of-user").addClass('hidden');
			//uncheck every bpsa-groups-of-user checkbox
			/*$("#bpsa-groups-of-user input:checkbox").each(function() {
				$(this).val([""]);	
			});*/
			break;
		}
	});
	
	/*
	 * 
	 * Select/Unselect all users of a group
	 * 
	 */
	
	$("#bpsa-groups-of-user .bpsa-groups-of-users-tr").each(function() {
		var $thisCheckbox = $(this).find(".bpsa-groups-of-users-checkbox");
		var $allow_me_checkbox = $(this).find(".allow_me");

		$thisCheckbox.change(function() {
			var $checkbox_value = "." + $(this).val();
			var $checkbox_checked = $(this).attr('checked');
			//check or uncheck all users of a group
			if($checkbox_checked){
				$allow_me_checkbox.val(["allow"]);
			}else{
				$allow_me_checkbox.val([""]);
			}
			$($checkbox_value).each(function() {
				if($checkbox_checked){
					$(this).val(["allow"]);
				}else{
					$(this).val([""]);
				}
			});			
		});
	});	
	
	/*
	 * 
	 * selects group if user of group is selected, de-selects group if no user is selected anymore
	 * 
	 */
	
	$("#bpsa-groups-of-user .bpsa-groups-of-users-tr").each(function() {
		var $thisCheckbox = $(this).find(".bpsa-groups-of-users-checkbox");
		var $allow_me_checkbox = $(this).find(".allow_me");
		var $thisShowMemberDiv = $(this).find(".bpsa-show-members-of-group")
		var $checkbox_value = "." + $thisCheckbox.val();
		
		//check or uncheck group checkbox, if user checkbox changes
		$($checkbox_value).change(function() {
			var $checkbox_checked = $(this).attr('checked');
			var $any_checkbox_checked = false;
			
			if($checkbox_checked){
				$thisCheckbox.val([$thisCheckbox.val()]);
				$allow_me_checkbox.val(["allow"]);
				mapto($thisCheckbox)
				
			}else{
				//check if any user in the group is selected
				$($checkbox_value).each(function() {
					if($(this).attr('checked')){
						$any_checkbox_checked = true;
						return;
					}
				});
				//check the group checkbox if at least one user is selected
				if($any_checkbox_checked){
					$thisCheckbox.val([$thisCheckbox.val()]);
				}else{
					$thisCheckbox.val([""]);
					$allow_me_checkbox.val([""]);
					mapto($thisCheckbox)
				}
			}
		});			
	});

	/* attache onchange function to each checkbox for group visibility selection */

	$('#bpsa-groups-of-user .bpsa-groups-of-users-checkbox').each(function() {
		$(this).change(function() {
			mapto($(this))
		})
	})

	/*
	 * select/deselect user in _all_ groups in the "sichtbarkeit" widget
	 */
	
	$("#bpsa-groups-of-user .bpsa-show-members-of-group").each(function() {
		var thisCheckbox = $(this).find(".bpsa-user-post-access-setting-specific-groups-checkbox");
		
		thisCheckbox.change(function() {
			var checkbox_checked = $(this).attr('checked');
			var checkbox_name = $(this).attr('name');

			$("#bpsa-groups-of-user .bpsa-show-members-of-group").each(function() {
				$(this).find(".bpsa-user-post-access-setting-specific-groups-checkbox").each(function(){
					if($(this).attr('name')==checkbox_name) $(this).attr('checked', checkbox_checked)
				})
			})
		});
	});	


});	//end

