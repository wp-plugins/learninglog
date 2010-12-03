<?php
/*
 * sends answer format to client
 */

function response_answer_format() {
	include_once ("learning-diary-tasks-answer-format.php");
	
	$format = $_GET ['format'];
	
	$AnswerFormat = new LearningDiaryTaskAnswerFormat ( array ('format' => $format ) );
	
	$AnswerFormat->show ( true );
	
	die ();

}

/*
 * sends users ids of selected group to client
 */

function response_users_to_select() {
	//gets ARRAYs from client
	$gids ['p'] = $_GET ["gids_p"];
	$gids ['n'] = $_GET ["gids_n"];
	
	$mem_ids = array ();
	$pn = array ('p', 'n' );
	
	//for selected (p) and not selected (n)
	for($i = 0; $i < 2; $i ++) {
		//for each group
		foreach ( $gids [$pn [$i]] as $gid ) {
			//$members = 	groups_get_group_members($gid);
			$members = BP_Groups_Member::get_all_for_group ( $gid, false, false, false, true );
			//for each member of group
			foreach ( $members ['members'] as $member ) {
				//only deselect if not selected by any other group
				if (! in_array ( $member->user_id, $mem_ids ['p'] )) {
					$mem_ids [$pn [$i]] [] = $member->user_id;
				}
			}
		}
	}
	//json object will be convertet to js object on client side
	echo json_encode ( $mem_ids );
	
	die ();
}

function response_users_to_highlight() {
	$gid = $_GET ["gid"];
	$mem_ids = array ();
	$members = BP_Groups_Member::get_all_for_group ( $gid, false, false, false, true );
	
	foreach ( $members ['members'] as $member ) {
		$mem_ids [] = $member->user_id;
	}
	
	echo json_encode ( $mem_ids );
	
	die ();
}

function response_groups_to_highlight() {
	$uid = $_GET ["uid"];
	
	$groups = groups_get_user_groups ( $uid );
	
	echo json_encode ( $groups ['groups'] );
	
	die ();

}
