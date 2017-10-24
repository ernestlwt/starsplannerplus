<?php 

require_once("Planning_V2.php");
require_once("../database/DBManager.php");

header('Content-Type: application/json');

# get course code list
if(isset($_POST['action']) && $_POST['action'] == 'get_courses'){
	$course_code_list = DBManager::retrieve_all_course_code(); #database function_exists
	echo json_encode(implode("|", $course_code_list));
}

# to plan the timetable
if(isset($_POST['action']) && $_POST['action'] == 'plantimetable'){
	$course_code_list = explode("|", $_POST['course_code']);
	if(strlen($_POST['free_day_time']) > 0){
		$free_day_time = explode("|", $_POST['free_day_time']);
	}
	else{ $free_day_time = array(); }
	if(strlen($_POST['fixed_index']) > 0){
		$fixed_index = explode("|", $_POST['fixed_index']);
	}
	else{ $fixed_index = array(); }
	
	
	#################### EXAMPLES FOR STRING FROM FRONTEND #######################################
	#variables (get from frontend)
	#$course_code_list = array("CZ3005", "HA2017", "CZ3001", "HP8003", "CZ3003", "EE4413", "IM4413");
	#$course_code_list = array("CZ3005", "CZ3007", "CZ3001", "CZ3002", "CZ3003");
	#$course_code_list = array("CZ2005", "CZ3006", "CZ3001", "CZ3002", "CZ2004");
	#$free_day_time = array("WED 0830-1030");
	#$free_day_time = array();
	#$fixed_index = array("10232", "10693");
	#$fixed_index = array();	
	##############################################################################################
	$error_code_list = array();
	if(sizeof($error_code_list) > 0){
		$returnstr = "ERR_COURSECODE: ";
		foreach($error_code_list as $code){
			$returnstr = $returnstr.$code."|";
		}
		$returnstr = substr($returnstr, 0, -1);
		echo json_encode($returnstr);
	}
	else{
		$returnstr = generate_return_str($course_code_list, $free_day_time, $fixed_index);
		echo json_encode($returnstr);
	}
}

# to load previously saved timetable
if(isset($_POST['action']) && $_POST['action'] == 'load_timetable'){
	$user_email= $_POST['user_email'];
	
	echo json_encode(get_saved_timetable($user_email));
}

if(isset($_POST['action']) && $_POST['action'] == 'save_timetable'){
	$user_email = $_POST['user_email'];
	$index_str = $_POST['index'];
	$plan_no = $_POST['plan_id'];
	
	save_timetable($user_email , $index_str , $plan_no);
	echo json_encode("SUCCESS");
}
	

?>