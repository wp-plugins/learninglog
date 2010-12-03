/*
 *
 * all html-classes and html-ids start with learning-diary-tasks- (to prevent operlaps)
 * 
 */

jQuery(document).ready(function($) {

	/*
	 * 
	 * Toggle .show-members-of-group HTML-Table in post.php and post-edit.php
	 * 
	 */

	
	$(".learning-diary-tasks-show-members-of-group").each(function() {
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
	
/*	$("#learning-diary-tasks-visible-for option:selected").ready(function(){
		var $value = $("#learning-diary-tasks-visible-for option:selected").val();
		switch ($value){
		case "nobody":
			//do nothing
			break;
		case "specific_groups":
			//show learning-diary-tasks-groups-of-user
			$("#learning-diary-tasks-groups-of-user").removeClass('hidden');
			break;
		case "specific_users":
			//show all useres
			$("#learning-diary-tasks-all-users").removeClass('hidden');
			break;
		}	
	});
*/

	/*
	 * 
	 * show or hide groups_of_user and all_users 
	 * 
	 */
/*	
	$("#learning-diary-tasks-visible-for").change(function() {
		var $value = $("#learning-diary-tasks-visible-for option:selected").val();
		switch ($value){
		case "nobody":
			//hide learning-diary-tasks-groups-of-user
			$("#learning-diary-tasks-groups-of-user").addClass('hidden');
			//hide learning-diary-tasks-all-users
			$("#learning-diary-tasks-all-users").addClass('hidden');
			//uncheck every learning-diary-tasks-groups-of-user checkbox
			/*$("#learning-diary-tasks-groups-of-user input:checkbox").each(function() {
				$(this).val([""]);	
			});
			//uncheck every groups_of_user checkbox
			$("#learning-diary-tasks-all-users input:checkbox").each(function() {
				$(this).val([""]);	
			});*/
/*			break;
		case "specific_groups":
			//show learning-diary-tasks-groups-of-user
			$("#learning-diary-tasks-groups-of-user").removeClass('hidden');
			//hide learning-diary-tasks-all-users
			$("#learning-diary-tasks-all-users").addClass('hidden');
			//uncheck every learning-diary-tasks-all-users checkbox
			/*$("#learning-diary-tasks-all-users input:checkbox").each(function() {
				$(this).val([""]);	
			});*/
/*			break;
		case "specific_users":
			//show all useres
			$("#learning-diary-tasks-all-users").removeClass('hidden');
			//hide learning-diary-tasks-groups-of-user
			$("#learning-diary-tasks-groups-of-user").addClass('hidden');
			//uncheck every learning-diary-tasks-groups-of-user checkbox
			/*$("#learning-diary-tasks-groups-of-user input:checkbox").each(function() {
				$(this).val([""]);	
			});*/
/*			break;
		}
	});
	
	/*
	 * 
	 * Select/Unselect all users of a group
	 * 
	 */
/*	
	$("#learning-diary-tasks-groups-of-user .learning-diary-tasks-groups-of-users-td").each(function() {
		var $thisCheckbox = $(this).find(".learning-diary-tasks-groups-of-users-checkbox");
		$thisCheckbox.change(function() {
			var $checkbox_value = "." + $(this).val();
			var $checkbox_checked = $(this).attr('checked');
			//check or uncheck all users of a group
			$($checkbox_value).each(function() {
				if($checkbox_checked){
					$(this).val(["allow"]);
				}else{
					$(this).val([""]);
				}
			});			
		});
	});
*/	
	$("#learning_diary_tasks_dashboard_widget_create_task form").submit(function() {
		$("#learning_diary_tasks_dashboard_widget_create_task #publish").attr("disabled", true); 
		$("#learning_diary_tasks_dashboard_widget_create_task #save-post").attr("disabled", true); 
		$("#learning_diary_tasks_dashboard_widget_create_task #publish").addClass('dashboard-submit-alpha');
		$("#learning_diary_tasks_dashboard_widget_create_task #save-post").addClass('dashboard-submit-alpha');
		
	});
	
	$("#editnewtask #publish").mouseup(function(){
		$("#editnewtask #save_or_publish").val("publish"); 
	});
	
	/*
	 * disable submit button to prevent submiting twice
	 */
	
	$("#editnewtask form").submit(function() {
		$("#editnewtask #publish").attr("disabled", true); 
		$("#editnewtask #save-post").attr("disabled", true); 
		$("#editnewtask #publish").addClass('dashboard-submit-alpha');
		$("#editnewtask #save-post").addClass('dashboard-submit-alpha');
		
	});
	
	/*
	 * Toggle Publish Date in New Task and Edit Task (Publish Date)
	 */
	
	$("#learning_diary_task_publish_immediately").mousedown(function() {
		$("#learning_diary_task_publish_date").removeClass('hidden');
		$("#learning_diary_task_publish_immediately").addClass('hidden');
		$("#learning_diary_task_publish_immediately_input").val(["specific"]);
	});	

	
	$("#learning_diary_task_publish_immediately_toggle").mousedown(function() {
		$("#learning_diary_task_publish_date").addClass('hidden');
		$("#learning_diary_task_publish_immediately").removeClass('hidden');
		$("#learning_diary_task_publish_immediately_input").val(["none"]);
	});
	
	/*
	 * Toggle Review Date in New Task and Edit Task (Review Date)
	 */
	
	$("#learning_diary_task_review_no_date").mousedown(function() {
		$("#learning_diary_task_review_date").removeClass('hidden');
		$("#learning_diary_task_review_no_date").addClass('hidden');
		$("#learning_diary_task_review_date_input").val(["specific"]);
	});	

	 
	$("#learning_diary_task_review_date_toggle").mousedown(function() {
		$("#learning_diary_task_review_date").addClass('hidden');
		$("#learning_diary_task_review_no_date").removeClass('hidden');
		$("#learning_diary_task_review_date_input").val(["none"]);
	});	
	
	/*
	 * change Handler in learning_diary_tasks_edit.php
	 */
	
	$("#show_tasks_of_group").change(function() {
		$.urlParam = function(name){
			var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(window.location.href);
			if (!results) { return 0; }
			return results[1] || 0;
		};
		if($.urlParam('page') == 'learning_tasks'){
			window.location = $(location).attr('pathname') + "?page=" + $.urlParam('page') + "&show_tasks_of_group=" + $(this).val();
		}else{
			window.location = $(location).attr('pathname') + "?page=" + $.urlParam('page') + "&task_status=all&show_tasks_of_group=" + $(this).val();
		}
	});
	
	/*
	 * fires on dropdown list changes
	 * @action function that is called by 'admin-ajax.php' with suffix 'wp_ajax_' (http://codex.wordpress.org/AJAX_in_Plugins)
	 * @format dropdown list item value
	 * @de_strings.shortq is defined in 'learning_diary_tasks_edit.php' and is localized there
	 */

	$('#answer_format_select').change(function(){
		var data = {
			action: 'answer_format_ajax',
			format: $(this).val()
		};
		
		var ques=$('#answer_format_ques').val()
		
		jQuery.get(ajaxurl, data, function(response) {
			jQuery('#answer_format_preselect').html(response);
			
			if (ques) $('#answer_format_ques').val(ques)
			
			//fires on focus of question text input
			$('#answer_format_ques').focus(function(){
				if($(this).val()==de_strings.shortq) $(this).val('')
			});
			
			$('#answer_format_ques').focusout(function(){
				if($(this).val()=='') $(this).val(de_strings.shortq)
			});
			
		});
	});
	
	$('#answer_format_ques').focus(function(){
		if($(this).val()==de_strings.shortq) $(this).val('')
	});

	$('#answer_format_ques').focusout(function(){
		if($(this).val()=='') $(this).val(de_strings.shortq)
	});
	
	/*
	 * select/deselect user in _all_ groups in the "aufgabe senden an:" widget
	 */
	
/*	$("#learning-diary-tasks-groups-of-user .learning-diary-tasks-show-members-of-group").each(function() {
		var thisCheckbox = $(this).find(".learning-diary-tasks-user-post-access-setting-specific-groups-checkbox");
		
		thisCheckbox.change(function() {
			var checkbox_checked = $(this).attr('checked');
			var checkbox_name = $(this).attr('name');

			$("#learning-diary-tasks-groups-of-user .learning-diary-tasks-show-members-of-group").each(function() {
				$(this).find(".learning-diary-tasks-user-post-access-setting-specific-groups-checkbox").each(function(){
					if($(this).attr('name')==checkbox_name) $(this).attr('checked', checkbox_checked)
				})
			})
		});
	});	
*/

	/*
	 * select users of selected groups in the "aufgabe senden an:" widget
	 * users are selected if at least one group is selected to which the user belongs to
	 */
	
	var groupchk = $('.learning-diary-tasks-groups-of-users-checkbox')
	
	groupchk.change(function(){

		var gids_p = []
		var gids_n = []
	
		$('#ajax-loader-img').removeClass('hidden');
	
	//	groupchk.each(function(){
			
			if ( $(this).attr('checked') ){
				gids_p = gids_p.concat(this.value.substr(6)) //substract "allow_"
			}else{
				gids_n = gids_n.concat(this.value.substr(6)) //substract "allow_"
			}
	//	})
	
		var data = {
			action: 'select_users_ajax',
			gids_p: gids_p,
			gids_n: gids_n   
		};
		
		jQuery.get(ajaxurl, data, function(response) {
			//response is converted from json to js object
			ids_p = response['p']
			ids_n = response['n']
		
			for (id in ids_p){
				attrname = 'learning-diary-tasks-user-post-access-setting-specific-users[' + ids_p[id] + ']'
				$('#learning-diary-tasks-all-users').find("input[name*='"+attrname+"']").attr('checked','checked')
			}

			for (id in ids_n){
				attrname = 'learning-diary-tasks-user-post-access-setting-specific-users[' + ids_n[id] + ']'
				checkbox = $('#learning-diary-tasks-all-users').find("input[name*='"+attrname+"']")
				if (!checkbox.attr('disabled')) {
					checkbox.removeAttr('checked')
				}
			}
		
			$('#ajax-loader-img').addClass('hidden'); //must be inside $.get
			
		}, 'json')
	});
	
	/*
	 * highlight users from highlighted group
	 */

	var highlight = function(obj, gid){

		$('.highlighted').removeClass('highlighted')
	
		obj.parents('tr').addClass('highlighted')

		$('#ajax-loader-img').removeClass('hidden');
		
		var data = {
			action: 'highlight_users_ajax',
			gid: gid
		};
		
		jQuery.get(ajaxurl, data, function(response) {
		
			ids = response
			
			for (id in ids){
				attrname = 'learning-diary-tasks-user-post-access-setting-specific-users[' + ids[id] + ']'
				$('#learning-diary-tasks-all-users').find("input[name*='"+attrname+"']").parents('tr').addClass('highlighted')
			}
		
		$('#ajax-loader-img').addClass('hidden'); //must be inside $.get
		
		}, 'json')
	}
	
	$('.learning-diary-tasks-groups-of-users-checkbox').click(function(){
		highlight($(this), this.value.substr(6))
	})
	
	$('.learning-diary-tasks-show-members-of-group').click(function(){
		highlight($(this), $(this).attr('name').substr(9))
	}) 
	
	/*
	 * highlight groups the selected user belongs
	 */
	
	var highlightGroup = function(obj, uid) {	
		$('.highlighted').removeClass('highlighted')
	
		obj.parents('tr').addClass('highlighted')
		$('#ajax-loader-img').removeClass('hidden');
		
		var data = {
			action: 'highlight_groups_ajax',
			uid: uid
		};
		
		jQuery.get(ajaxurl, data, function(response) {
		
			ids = response
		
			$('.learning-diary-tasks-show-members-of-group').each(function(){
				if(jQuery.inArray($(this).attr('name').substr(9), ids) != '-1') {
					$(this).parents('tr').addClass('highlighted')
				}
			})
		
			$('#ajax-loader-img').addClass('hidden');
				
		}, 'json')		
		
	}

	$('.learning-diary-tasks-users-checkbox').click(function(){
		highlightGroup($(this), this.value.substr(6))
	})
	
	$('.learning-diary-tasks-show-members-of-all-groups').click(function(){
		highlightGroup($(this), $(this).attr('name').substr(8))
	})
	
	
});

