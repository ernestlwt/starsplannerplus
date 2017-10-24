<?php 
require_once("../entity/Course.php");
require_once("../entity/CourseIndex.php");
require_once("../entity/Lesson.php");
require_once("../entity/Timetable.php");
require_once('../database/DBManager.php');

	#=========================
	# FUNCTIONS
	#=========================
	
	
	function generate_return_str($course_code_list, $free_day_time, $fixed_index){
		# check for clashes in timetable
		$clash_list = check_exam_clash($course_code_list);
		# list to keep all permutations of timetable
		if(sizeof($clash_list)==0){
			$timetable_list = generate_timetable($course_code_list, $free_day_time, $fixed_index);
			if(sizeof($timetable_list)==0){
				$returnstr = "ERR_TIMETABLE";
			}
			else{
				# create json file to pass to frontend
				$returnstr = createJsonStr($timetable_list);
			}
		}
		else {
			$returnstr = "ERR_EXAM:";
			foreach($clash_list as $date_time => $course_code){
				$returnstr = $returnstr.$date_time."/";
				foreach($course_code as $code){
					$returnstr = $returnstr.$code.";";
				}
				$returnstr = substr($returnstr, 0, -1);
				$returnstr = $returnstr."|";
			}
			$returnstr = substr($returnstr, 0, -1);
		}
		return $returnstr;
	}
	
	
	# to check if there is clash in exam
	function check_exam_clash($course_code_list){
		# get course objects to plan for
		$course_obj_list = array();
		foreach($course_code_list as $course_code){
			$course_obj_list[] = DBManager::retrieve_course($course_code);
		}	
		# get exam date time
		$exam_datetime_list = array();
		foreach($course_obj_list as $course){
			$exam_datetime_list[$course->get_course_code()] = $course->get_course_exam_datetime();
		}
		
		# find if there is any clash in dates and times
		$clash_list = array();
		# count the number of times the same date time appear
		$datetime_count = array_count_values($exam_datetime_list);
		foreach($datetime_count as $date_time => $count){
			if ($count>1){
				foreach($exam_datetime_list as $course_code => $exam_datetime){
					if($exam_datetime == $date_time){
						$clash_list[$date_time][] = $course_code;
					}
				}
			}
		}
		return($clash_list);
	}
	
	# main of generating timetable
	function generate_timetable($course_code_list, $free_day_time, $fixed_index){
		global $timetable_list;
		# creating blank 2d array for timetable planning
		$plan = array(array());
		for($i=0;$i<30;$i++){
			for($j=0;$j<6;$j++){
				$plan[$i][$j] = array();
			}
		}
		# block out timing that user wants to be free
		if(sizeof($free_day_time)>0){
			$plan = block_free_daytime($plan, $free_day_time);
		}
		
		# track fixed index for each course
		$fixed_course_index = array();
		foreach($fixed_index as $fix){
			$course_code = DBManager::retrieve_course_index($fix)->get_course_code();
			$fixed_course_index[$course_code] = $fix;
		}

		# to track index for each course
		$course_and_index = array_fill_keys($course_code_list, "0");

		# function to solve timetable planning
		solve($plan, $course_code_list, 0, $course_and_index, $fixed_course_index);
		
		if(sizeof($timetable_list)>0){
			#sort timetable by cost
			usort($timetable_list, 	function($a, $b){
										return ($a->get_timetable_cost() > $b->get_timetable_cost());
									}
				);
		}
		return $timetable_list;
	}	

	# function to solve timetable permutations
	function solve($plan, $course_code_list, $code_index, $course_and_index, $fixed_course_index){
		global $timetable_list;
		$course_code = $course_code_list[$code_index];
		# get list of course index objects for a Course
		if(array_key_exists($course_code, $fixed_course_index)){
			$lesson_index = $fixed_course_index[$course_code];
			$course_index_obj_list = array(DBManager::retrieve_course_index($lesson_index));
		}else {
			$course_index_obj_list = DBManager::retrieve_list_of_course_index($course_code);
		}
		foreach($course_index_obj_list as $course_index){
			# check if all lessons for that index can fit into plan
			if(allowed($course_index, $plan)){
				# fill the lesson into plan
				$plan = add_index($course_index, $plan);
				# record the index chosen for that course
				$course_and_index[$course_code] = $course_index->get_lesson_index();
				
				# check if finished planning or can have next step
				if(!in_array("0", $course_and_index)){
					$ttbl = new Timetable(array_values($course_and_index));
					$ttbl->set_cost(calculate_cost($plan));
					$timetable_list[] = $ttbl;
				}
				if($code_index < sizeof($course_code_list)-1 && solve($plan, $course_code_list, $code_index+1, $course_and_index, $fixed_course_index)){
					return true;
				}
				
				# if false (means next course cannot be slotted), then remove the lessons from timetable
				$plan = remove_index($course_index, $plan);
				$course_and_index[$course_code] = "0";
			}
		}
		return false;
	}
				
	
	# function to check if the timeslot is available
	function allowed($course_index, $plan){
		$lessonList = DBManager::retrieve_list_of_lesson($course_index->get_lesson_index());
		$unavailableWeeks = array();
		foreach($lessonList as $lesson){
			$day_index = get_day_index($lesson->get_lesson_day());
			$start_time_index = get_time_index($lesson->get_lesson_start_time());
			$end_time_index = get_time_index($lesson->get_lesson_end_time());
			$lesson_weeks = get_lesson_weeks($lesson->get_lesson_remark());
			for($i=$start_time_index; $i<$end_time_index; $i++){
				if(sizeof($plan[$i][$day_index]) > 0){
					if($plan[$i][$day_index][0] != "BLOCKED"){
						foreach($plan[$i][$day_index] as $existingLesson){
							$unavailableWeeks = array_merge($unavailableWeeks, get_lesson_weeks($existingLesson->get_lesson_remark()));
						}
						if(count(array_intersect($lesson_weeks, $unavailableWeeks))>0){
							return false;
						}
					}
					else {
						return false;
					}
				}
			}
		}
		return true;
	}

	# to block away free time so that cannot plan lesson there
	function block_free_daytime($plan, $free_day_time){
		$free_day = array();
		$free_start_time = array();
		$free_end_time = array();
		foreach($free_day_time as $day_time){
			$free_day[] = get_day_index(substr($day_time, 0, 3));
			$free_start_time[] = get_time_index(substr($day_time, 4, 4));
			$free_end_time[] = get_time_index(substr($day_time, 9));
		}
		for($i=0; $i<sizeof($free_day); $i++){
			for($j=$free_start_time[$i]; $j<$free_end_time[$i]; $j++){
				$plan[$j][$free_day[$i]][] = "BLOCKED";
			}
		}
		return $plan;
	}
	
	# to add lessons for index into timetable
	function add_index($courseIndex, $plan){
		$lessonList = DBManager::retrieve_list_of_lesson($courseIndex->get_lesson_index());
		foreach($lessonList as $lesson){
			$day_index = get_day_index($lesson->get_lesson_day());
			$start_time_index = get_time_index($lesson->get_lesson_start_time());
			$end_time_index = get_time_index($lesson->get_lesson_end_time());
			$lesson_weeks = get_lesson_weeks($lesson->get_lesson_remark());
			for($i=$start_time_index; $i<$end_time_index; $i++){
				$plan[$i][$day_index][] = $lesson;
			}
		}
		return $plan;
	}

	# to remove lessons of index from timetable
	function remove_index($courseIndex, $plan){
		$lessonList = DBManager::retrieve_list_of_lesson($courseIndex->get_lesson_index());
		foreach($lessonList as $lesson){
			$day_index = get_day_index($lesson->get_lesson_day());
			$start_time_index = get_time_index($lesson->get_lesson_start_time());
			$end_time_index = get_time_index($lesson->get_lesson_end_time());
			$lesson_weeks = get_lesson_weeks($lesson->get_lesson_remark());
			for($i=$start_time_index; $i<$end_time_index; $i++){
				array_pop($plan[$i][$day_index]);
			}
		}
		return $plan;
	}	
	
	# get indexes of days of lessons for an index on timetable
	function get_day_index($lesson_day){
		switch($lesson_day){
			case "MON":
				return 0;
			case "TUE":
				return 1;
			case "WED":
				return 2;
			case "THU":
				return 3;
			case "FRI":
				return 4;
			case "SAT":
				return 5;
		}
	}
	
	# get index of start time on timetable
	function get_time_index($time){
		$time_diff = $time - 830;
		$time_index = (int)($time_diff / 100) * 2;
		if($time_diff % 100 == 70){
			$time_index++;
		}
		return $time_index;
	}
	
	# get list of weeks for lesson through remarks
	function get_lesson_weeks($remark){
		$lesson_weeks = array();
		if($remark == ""){
			$remark = "Wk1-13";
		}
		$remark = substr($remark, 2);
		$weekRange = explode(",", $remark);
		foreach($weekRange as $week){
			if (strpos($week, "-")){
				$week = range($week[0], substr($week,strpos($week, "-")+1));
				$lesson_weeks = array_merge($lesson_weeks, $week);
			}
			else {
				$lesson_weeks[] = $week;
			}
		}
		return $lesson_weeks;
	}
	

	/*# print the timetable (for checking)
	function print_plan($plan){
		echo "<br> Printing plan: <br>";
		for($i=0;$i<30;$i++){
			for($j=0;$j<6;$j++){
				echo "|";
				if(sizeof($plan[$i][$j]) == 0){
					echo "0";
				}
				else {
					# print course code and index for each lesson (can put in a function)
					if($plan[$i][$j][0] == "BLOCKED"){
						echo "B";
					}
					else {
						foreach($plan[$i][$j] as $lesson){
							$lesson_index = $lesson -> get_lesson_index();
							$courseIndex = get_course_index_object($lesson_index);
							echo " ".$courseIndex->get_course_code().": ".$lesson_index."[".$lesson->get_lesson_remark()."] ";
						}
					}
				}
				echo "|";
			}
			echo "<br>";
		}
	}*/

	
	# calculate cost of timetable
	function calculate_cost($plan){
		$cost = 0;
		for($i=0;$i<30;$i++){
			for($j=0;$j<6;$j++){
				# check if monday or friday
				if($j==0 || $j==4){
					if(sizeof($plan[$i][$j])>0){
						$cost++;
					}
				}
				# check if got lunch time
				if($i>5 && $i<10){
					if(sizeof($plan[$i][$j])>0){
						$cost++;
					}
				}
				# check if before 1030
				if($i<4){
					if(sizeof($plan[$i][$j])>0){
						$cost = $cost + (4-$i)/4;
					}
				}
				# check if after 1630
				if($i>15){
					if(sizeof($plan[$i][$j])>0){
						$cost = $cost + ($i-15)/14;
					}
				}
			}
		}
		return $cost;
	}
	
	# generate json file (to pass to frontend)
	function createJsonStr($timetable_list){
		
		# creating the output to write
		$jsonstr = "[";
		for($i=0; $i<sizeof($timetable_list); $i++){
			$jsonstr = $jsonstr."\n{\n\"timetable\": \"".($i+1)."\", \n \"table\": [";
			$ttbl = $timetable_list[$i];
			$lesson_index_list = $ttbl->get_lesson_index();
			foreach($lesson_index_list as $lesson_index){
				$jsonstr = $jsonstr."\n{\n  \"course\":";
				$course_code = DBManager::retrieve_course_index($lesson_index)->get_course_code();
				$course = DBManager::retrieve_course($course_code);
				$jsonstr = $jsonstr.json_encode($course);
				$jsonstr = $jsonstr.",\n  \"lessons\": \n";
				$jsonstr = $jsonstr.json_encode(DBManager::retrieve_list_of_lesson($lesson_index))."\n},";
			}
			$jsonstr = substr($jsonstr, 0, -1);
			$jsonstr = $jsonstr."\n]\n},";
		}
		$jsonstr = substr($jsonstr, 0, -1);
		$jsonstr = $jsonstr."\n]";

		return $jsonstr;
	}
		
	// need to see the actual function and variable names
	function load_timetable_json($timetable_list){
		if(sizeof($timetable_list)>0){
			#sort timetable by cost
			usort($timetable_list, 	function($a, $b){
										return ($a->get_timetable_number() > $b->get_timetable_number());
									}
				);
		}
		# creating the output to write
		$jsonstr = "[";
		foreach($timetable_list as $ttbl){
			$jsonstr = $jsonstr."\n{\n\"timetable\": \"".$ttbl->get_timetable_number()."\", \n \"table\": [";
			$lesson_index_list = $ttbl->get_lesson_index();
			foreach($lesson_index_list as $lesson_index){
				$jsonstr = $jsonstr."\n{\n  \"course\":";
				$course_code = get_course_index_object($lesson_index)->get_course_code();
				$course = get_course_object($course_code);
				$jsonstr = $jsonstr.json_encode($course);
				$jsonstr = $jsonstr.",\n  \"lessons\": \n";
				$jsonstr = $jsonstr.json_encode(get_lesson_list($lesson_index))."\n},";
			}
			$jsonstr = substr($jsonstr, 0, -1);
			$jsonstr = $jsonstr."\n]\n},";
		}
		$jsonstr = substr($jsonstr, 0, -1);
		$jsonstr = $jsonstr."\n]";

		return $jsonstr;
	}
	
	
	function get_saved_timetable($user_email){
		#database function
		$timetable_list = DBManager::retrieve_timetables($user_email);
		if(sizeof($timetable_list)>0){
			# need to sort according to plan id
			return createJsonStr($timetable_list);
		}
		else{
			return "ERR_NORECORD";
		}
	}
	
	function save_timetable($user_email, $index_str, $plan_no){
		$ttbl = Timetable::with_user(explode("|",$index_str), $user_email, $plan_no);
		DBManager::update_timetable($ttbl);
	}


?>