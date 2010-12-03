<?php
/*
 * 
 */

class LearningDiaryTaskAnswerFormat {
	
	public $ques = "";
	public $format = "";
	public $value = array(); //numeric array
	public $format_value = array(); // array('ques' => [string], format'=>[string], 'value'=>[numeric array])
	
	/*
	 * @format_value must be array('ques' => [string], 'format'=> [string], ['value'=>[string or numeric array]])
	 */
	function __construct($format_value)
	{
		//TODO:  sanitize input data
		$this->ques = $format_value['ques'];
		
		$this->format = $format_value['format'];
		
		if ($format_value['value']) {
			$this->value = is_array($format_value['value']) ? $format_value['value'] : array($format_value['value']);
		}
		
		$this->format_value = array('ques' => $this->ques, 'format' => $this->format, 'value' => $this->value);
		
	}
	
	public function show($disabled=false, $preview=false, $count_answers=false)
	{
		static $id_x = 0; //id and name suffixes to prevent id/name collisions
		
		$idx = $id_x==0 ? "" : "_" . $id_x; 
		
		++$id_x;
		
		$blog_details = get_blog_details(1);
		
		if ($this->format AND 'text' != $this->format AND $disabled AND !$preview) {
			?>
			<input type="text" name="answer_format_ques" id="answer_format_ques" maxlength="50"
				<?php echo $count_answers ? 'disabled="disabled"' : ""?>
				value="<?php echo $this->ques ? $this->ques : __('Kurzfrage hier eingeben', 'bp_learning_diary')?>">
			<br>
			<?php
		}
		
/*		if('text' != $this->format AND $preview){ ?>
			<div><?php echo "Kurzfrage: " . $this->ques;?></div>
			<?php 
		}
*/		
		switch ($this->format) {
			case 'smiley3' :
				$url_smilies = $blog_details->siteurl . "/" . WPINC . "/images/smilies/";
				
				$disabled_html = $disabled ? ' disabled="disabled"' : '';
				
				$checked = array();
				$checked[$this->value[0] - 1] = ' checked="checked"';
				
				?>
				<div>
					<input type="hidden" name="answer_format_format" value="smiley3">
					
					<input type="radio" name="smiley3<?php echo $idx?>" id="smiley3_1<?php echo $idx?>" value="1" <?php echo $checked[0] . $disabled_html?>>
					<label for="smiley3_1<?php echo $idx?>"><img style="margin-bottom: 0px;" src="<?php echo $url_smilies?>icon_sad.gif"></label>
					&nbsp;
					<input type="radio" name="smiley3<?php echo $idx?>" id="smiley3_2<?php echo $idx?>" value="2" <?php echo $checked[1] . $disabled_html?>>
					<label for="smiley3_2<?php echo $idx?>"><img style="margin-bottom: 0px;" src="<?php echo $url_smilies?>icon_neutral.gif"></label>
					&nbsp;
					<input type="radio" name="smiley3<?php echo $idx?>" id="smiley3_3<?php echo $idx?>" value="3" <?php echo $checked[2] . $disabled_html?>>
					<label for="smiley3_3<?php echo $idx?>"><img style="margin-bottom: 0px;" src="<?php echo $url_smilies?>icon_smile.gif"></label>
				</div>
				<?php
				return;
				
			case 'smiley5' :
				$url_smilies = $blog_details->siteurl . "/" . WPINC . "/images/smilies/";

				$disabled_html = $disabled ? ' disabled="disabled"' : '';

				$checked = array();
				$checked[$this->value[0] - 1] = ' checked="checked"';

				?>
				<div>
					<input type="hidden" name="answer_format_format" value="smiley5">

					<input type="radio" name="smiley5<?php echo $idx?>" id="smiley5_1<?php echo $idx?>" value="1" <?php echo $checked[0] . $disabled_html?>>
					<label for="smiley5_1<?php echo $idx?>"><img style="margin-bottom: 0px;" src="<?php echo $url_smilies?>icon_cry.gif"></label>
					&nbsp;
					<input type="radio" name="smiley5<?php echo $idx?>" id="smiley5_2<?php echo $idx?>" value="2" <?php echo $checked[1] . $disabled_html?>>
					<label for="smiley5_2<?php echo $idx?>"><img style="margin-bottom: 0px;" src="<?php echo $url_smilies?>icon_sad.gif"></label>
					&nbsp;
					<input type="radio" name="smiley5<?php echo $idx?>" id="smiley5_3<?php echo $idx?>" value="3" <?php echo $checked[2] . $disabled_html?>>
					<label for="smiley5_3<?php echo $idx?>"><img style="margin-bottom: 0px;" src="<?php echo $url_smilies?>icon_neutral.gif"></label>
					&nbsp;
					<input type="radio" name="smiley5<?php echo $idx?>" id="smiley5_4<?php echo $idx?>" value="4" <?php echo $checked[3] . $disabled_html?>>
					<label for="smiley5_4<?php echo $idx?>"><img style="margin-bottom: 0px;" src="<?php echo $url_smilies?>icon_smile.gif"></label>
					&nbsp;
					<input type="radio" name="smiley5<?php echo $idx?>" id="smiley5_5<?php echo $idx?>" value="5" <?php echo $checked[4] . $disabled_html?>>
					<label for="smiley5_5<?php echo $idx?>"><img style="margin-bottom: 0px;" src="<?php echo $url_smilies?>icon_biggrin.gif"></label>
				</div>
				<?php
				return;
				
			case 'weekdays' :
				$disabled_html = $disabled ? ' disabled="disabled"' : '';
				
				$checked = array();
				
				for($i=0; $i<count($this->value); $i++) $checked[$this->value[$i] - 1] = ' checked="checked"';
				?>
				<div>
					<input type="hidden" name="answer_format_format" value="weekdays">
					
					<?php $weekdays = array(
						__('Mo', 'bp_learning_diary'),
						__('Di', 'bp_learning_diary'),
						__('Mi', 'bp_learning_diary'),
						__('Do', 'bp_learning_diary'),
						__('Fr', 'bp_learning_diary'),
						__('Sa', 'bp_learning_diary'),
						__('So', 'bp_learning_diary')
					);
					
					for($i=0;$i<7;$i++):?>
						<input 	type="checkbox" name="weekdays<?php echo $idx?>[]" id="weekdays_<?php echo $i+1?><?php echo $idx?>" value="<?php echo $i+1?>" 
								<?php echo $checked[$i] . $disabled_html?>>
						<?php echo $weekdays[$i] . "&nbsp&nbsp&nbsp";
					endfor;?>
				</div>
				<?php
				return;
				
			case '1to10' :
				$disabled_html = $disabled ? ' disabled="disabled"' : '';

				$checked = array();
				$checked[$this->value[0] - 1] = ' checked="checked"';
				?>
				<div>
					<input type="hidden" name="answer_format_format" value="1to10">

					<?php for($i=0;$i<10;$i++):?>
						<input 	type="radio" name="1to10<?php echo $idx?>" id="1to10_<?php echo $i+1?><?php echo $idx?>" value="<?php echo $i+1?>" 
								<?php echo $checked[$i] . $disabled_html?>>
						<?php echo $i+1;
					endfor;?>
				</div>
				<?php
				return;
				
			case 'marks' :
				$disabled_html = $disabled ? ' disabled="disabled"' : '';

				$checked = array();
				$checked[$this->value[0] - 1] = ' checked="checked"';
				?>
				<div>
					<input type="hidden" name="answer_format_format" value="marks">

					<?php for($i=10;$i>-1;$i--):?>
						<input 	type="radio" name="marks<?php echo $idx?>" id="marks_<?php echo $i+1?><?php echo $idx?>" value="<?php echo $i+1?>" 
								<?php echo $checked[$i] . $disabled_html?>>
						<?php echo 1+($i*0.5) . "<br>";
					endfor;?>
				</div>
				<?php
				return;

			case 'yesno' :
				$disabled_html = $disabled ? ' disabled="disabled"' : '';

				$checked = array();
				$checked[ $this->value[0] ] = ' checked="checked"';
				?>
				<div>
					<input type="hidden" name="answer_format_format" value="yesno">

					<input 	type="radio" name="yesno<?php echo $idx?>" id="yesno_1<?php echo $idx?>" value="1" <?php echo $checked[1] . $disabled_html?>>
						<?php _e('ja', 'bp_learning_diary'); ?>
					&nbsp;&nbsp;
					<input 	type="radio" name="yesno<?php echo $idx?>" id="yesno_2<?php echo $idx?>" value="0" <?php echo $checked[0] . $disabled_html?>> 
						<?php _e('nein', 'bp_learning_diary'); ?>
					
				</div>
				<?php
				return;

		}//end switch
	}//end function show
	
	public function show_dropdown_list()
	{
		$selected = array();
		$selected[$this->format] = ' selected="selected"';
		?>
		<option value="text" <?php echo $selected['text']?>><?php _e('Reiner Text ...', 'bp_learning_diary');?></option>	
    	<option value="smiley3" <?php echo $selected['smiley3']?>><?php _e('... und 3 Smileys', 'bp_learning_diary');?></option>
    	<option value="smiley5" <?php echo $selected['smiley5']?>><?php _e('... und 5 Smileys', 'bp_learning_diary');?></option>
		<option value="weekdays" <?php echo $selected['weekdays']?>><?php _e('... und Wochentage (Mehrfachauswahl)', 'bp_learning_diary');?></option>
		<option value="1to10" <?php echo $selected['1to10']?>><?php _e('... und Skala 1 bis 10', 'bp_learning_diary');?></option>
		<option value="marks" <?php echo $selected['marks']?>><?php _e('... und Notenskala', 'bp_learning_diary');?></option>
		<option value="yesno" <?php echo $selected['yesno']?>><?php _e('... und ja/nein', 'bp_learning_diary');?></option>
		<?php
	}
	
	public function update_task_meta($task_id, $metatable)
	{
		global $wpdb;
		
		$data = array(
			"task_id" => $task_id,
			"meta_key" => "_ld_answer_format",
			"meta_value" => maybe_serialize($this->format_value)
		);
		
		$table = $wpdb->base_prefix . $metatable;
		
		$where = array('task_id' => $task_id, 'meta_key' => '_ld_answer_format');
		
		$exist = $wpdb->get_var("SELECT * FROM $table WHERE task_id=$task_id AND meta_key='_ld_answer_format'");
		
		//update/insert in db if format not NULL and delete if format is NULL
		if ($exist){
			if($this->format){
				$wpdb->update($table, $data, $where);
			}else{
				$wpdb->get_var("DELETE FROM $table WHERE task_id=$task_id AND meta_key='_ld_answer_format'");
			}
		}elseif($this->format){
			$wpdb->insert($table, $data);
		}
		

	}
	
	public static function get_task_meta($task_id, $metatable)
	{
		global $wpdb;
		
		$query = "SELECT meta_value 
			 	  FROM `" . $wpdb->base_prefix . $metatable ."` 
			 	  WHERE task_id = $task_id AND meta_key = '_ld_answer_format'";
		
		$answer_format_db_object = $wpdb->get_row($query);
		
		return maybe_unserialize($answer_format_db_object->meta_value);
		

	}
	
}