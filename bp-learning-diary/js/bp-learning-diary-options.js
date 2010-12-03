
jQuery(document).ready(function($) {

	
	/*
	 * 
	 */
	
	$('#learning_diary_tasks_student').change(function(){
		var chkbox = $('#ld_send_email_on_new_task')
		
		if( $(this).attr('checked') )  {
			chkbox.removeAttr('disabled')
			chkbox.parent().css('color','#333')
			chkbox.attr('checked','true')
		}else{
			chkbox.attr('disabled', 'disabled')
			chkbox.parent().css('color','#777')
			chkbox.removeAttr('checked')
		}
	});
	
});
