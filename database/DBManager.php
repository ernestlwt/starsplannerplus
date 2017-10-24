<?php

require_once("../config.php");
require_once('../entity/Course.php');
require_once('../entity/CourseIndex.php');
require_once('../entity/Lesson.php');
require_once('../entity/User.php');
require_once('../entity/Timetable.php');

class DBManager {
	private static $db_host = "";
	private static $db_name = "";
	private static $db_username = "";
	private static $db_password = "";
	private static $connection;
	
	/* Function for initialization */
	private static function set_db_host($host){
		self::$db_host = $host;
	}
	
	private static function set_db_name($name){
		self::$db_name = $name;
	}
	
	private static function set_db_username($username){
		self::$db_username = $username;
	}
	
	private static function set_db_password($password){
		self::$db_password = $password;
	}
	
	private static function initialize(){
		self::set_db_host(DB_HOST);
		self::set_db_name(DB_NAME);
		self::set_db_username(DB_USER);
		self::set_db_password(DB_PASSWORD);
	}
	/* End of initialization */
	
	private static function connect(){
		self::$connection = new mysqli(self::$db_host , self::$db_username , self::$db_password , self::$db_name);
		if (self::$connection->connect_error){
			die("Connection failed: ".self::$connection->connect_error);
		}
	}
	
	private static function disconnect(){
		self::$connection->close();
	}
	
	public static function add_courses($course_array){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("INSERT INTO Course (course_code , course_name , course_au , course_exam_date , course_exam_day , course_exam_time , course_exam_duration , course_has_exam) VALUES (?,?,?,?,?,?,?,?)");
		$stmt->bind_param("ssissssi" , $course_code , $course_name , $course_au , $course_exam_date , $course_exam_day , $course_exam_time, $course_exam_duration , $course_has_exam);
		
		foreach($course_array as $course){
			$course_code = $course->get_course_code();
			$course_name = $course->get_course_name();
			$course_au = $course->get_course_au();
			$course_exam_date = $course->get_course_exam_date();
			$course_exam_day = $course->get_course_exam_day();
			$course_exam_time = $course->get_course_exam_time();
			$course_exam_duration = $course->get_course_exam_duration();
			$course_has_exam = $course->has_exam();
			$stmt->execute();
		}
		$stmt->close();
		self::disconnect();
	}
	
	public static function add_course_indexes($index_array){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("INSERT INTO CourseIndex (lesson_index , course_code) VALUES (?,?)");
		$stmt->bind_param("ss" , $lesson_index , $course_code);
		
		foreach($index_array as $course_index){
			$lesson_index = $course_index->get_lesson_index();
			$course_code = $course_index->get_course_code();
			$stmt->execute();
		}
		$stmt->close();
		self::disconnect();
	}
	
	public static function add_lessons($lesson_array){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("INSERT INTO Lesson (lesson_index , lesson_type , lesson_group, lesson_day , lesson_time, lesson_venue , lesson_remarks) VALUES (?,?,?,?,?,?,?)");
		$stmt->bind_param("sssssss" , $lesson_index , $lesson_type , $lesson_group , $lesson_day , $lesson_time , $lesson_venue , $lesson_remark);
		
		foreach($lesson_array as $lesson){
			$lesson_index = $lesson->get_lesson_index();
			$lesson_type = $lesson->get_lesson_type();
			$lesson_group = $lesson->get_lesson_group();
			$lesson_day = $lesson->get_lesson_day();
			$lesson_time = $lesson->get_lesson_time();
			$lesson_venue = $lesson->get_lesson_venue();
			$lesson_remark = $lesson->get_lesson_remark();
			$stmt->execute();
		}
		$stmt->close();
		self::disconnect();
	}
	
	public static function retrieve_course_index($lesson_index){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("SELECT * FROM CourseIndex WHERE lesson_index = ?");
		$stmt->bind_param("s" , $lesson_index);
		
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($record = $result->fetch_assoc()){
			$course_index = new CourseIndex($record['course_code'] , $record['lesson_index']);
		}
		
		$stmt->free_result();
		$stmt->close();
		self::disconnect();
		
		return $course_index;
	}
	
	public static function retrieve_list_of_course_index($course_code){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("SELECT * FROM CourseIndex WHERE course_code = ?");
		$stmt->bind_param("s" , $course_code);
		
		$stmt->execute();
		$result = $stmt->get_result();
		$num_of_rows = $result->num_rows;
		
		while($row = $result->fetch_assoc()){
			$course_index = new CourseIndex($row['course_code'] , $row['lesson_index']);
			$index_array[] = $course_index;
		}
		
		$stmt->free_result();
		$stmt->close();
		self::disconnect();
		
		return $index_array;
	}
	
	public static function retrieve_list_of_lesson($lesson_index){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("SELECT * FROM Lesson WHERE lesson_index = ?");
		$stmt->bind_param("s" , $lesson_index);
		
		$stmt->execute();
		$result = $stmt->get_result();
		$num_of_rows = $result->num_rows;
		
		while($row = $result->fetch_assoc()){
			$lesson = new Lesson($row['lesson_index'], $row['lesson_type'], $row['lesson_group'] , $row['lesson_day'] , $row['lesson_time'] , $row['lesson_venue'] , $row['lesson_remarks']);
			$lesson_array[] = $lesson;
		}
		
		$stmt->free_result();
		$stmt->close();
		self::disconnect();
		
		return $lesson_array;
	}
	
	public static function retrieve_course($course_code){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("SELECT * FROM Course WHERE course_code = ?");
		$stmt->bind_param("s" , $course_code);
		
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($row = $result->fetch_assoc()){
			$course= Course::with_exam_details($row['course_code'] , $row['course_name'] , $row['course_au'] , $row['course_exam_date'] , $row['course_exam_day'] , $row['course_exam_time'] , $row['course_exam_duration'] , $row['course_has_exam']);

		}
		
		$stmt->free_result();
		$stmt->close();
		self::disconnect();
		
		return $course;
	}
	
	public static function retrieve_all_course_code(){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("SELECT course_code FROM CourseIndex GROUP BY (course_code)");
		
		$stmt->execute();
		$result = $stmt->get_result();
		
		while($row = $result->fetch_assoc()){
			$course_code_array[] = $row['course_code'];
		}
		
		$stmt->free_result();
		$stmt->close();
		self::disconnect();
		return $course_code_array;
	}
	
	public static function create_user($user){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("INSERT INTO User (user_email , user_password) VALUES (?,?)");
		$stmt->bind_param("ss", $user_email , $user_password);
		
		$user_email = $user->get_user_email();
		$user_password = $user->get_user_password();
		$stmt->execute();
		
		// create 3 rows for timetable
		$stmt = self::$connection->prepare("INSERT INTO Timetable (timetable_number , user_email) VALUES (?,?)");
		$stmt->bind_param("ss", $timetable_number ,	$user_email);
		
		for($i = 1; $i <= 3; $i++){
			$timetable_number = $i;
			$stmt -> execute();
		}
		
		$stmt->close();
		self::disconnect();
	}
	
	public static function check_user_credential($user){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("SELECT user_password FROM User WHERE user_email = ?");
		$stmt->bind_param("s" , $user_email);
		
		$user_email = $user->get_user_email();
		$stmt->execute();
		$stmt->bind_result($password);
		
		if($stmt->fetch()){
			if($user->get_user_password() == $password){
				$stmt->close();
				self::disconnect();
				return true;
			}
		}
		$stmt->close();
		self::disconnect();
		return false;
	}
	
	public static function update_timetable($timetable){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("SELECT timetable_id FROM Timetable WHERE user_email = ? AND timetable_number = ?");
		$stmt->bind_param("si" , $user_email , $timetable_number);
		
		$user_email = $timetable->get_user_email();
		$timetable_number = $timetable->get_timetable_number();
		
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($record = $result->fetch_assoc()){
			$timetable_id = $record['timetable_id'];
		}
		$stmt->free_result();
		
		//delete previous record here
		$stmt = self::$connection->prepare("DELETE FROM Timetable_Index WHERE timetable_id = ?");
		$stmt->bind_param("i" , $timetable_id);
		$stmt->execute();
		//end
		
		$stmt = self::$connection->prepare("INSERT INTO Timetable_Index(timetable_id , lesson_index) VALUES (?,?)");
		$stmt->bind_param("is" , $timetable_id , $lesson_index);
		
		$index_array = $timetable->get_lesson_index();
		foreach($index_array as $lesson_index){
			$stmt->execute();
		}
		
		$stmt->close();
		self::disconnect();
	}
	
	public static function retrieve_timetables($user_email){
		self::initialize();
		self::connect();
		$stmt = self::$connection->prepare("SELECT timetable_id FROM Timetable WHERE user_email = ? AND timetable_number = ?");
		$stmt->bind_param("si" , $user_email , $timetable_number);
		
		for($i = 0; $i <=3; $i++){
			$timetable_number = $i;
			$stmt->execute();
			$result = $stmt->get_result();
			if($record = $result->fetch_assoc()){
				$timetable_id_array[] = $record['timetable_id'];
			}
		}
		
		$stmt->free_result();
		
		foreach($timetable_id_array as $timetable_id){
			$stmt = self::$connection->prepare("SELECT lesson_index FROM Timetable_Index WHERE timetable_id = ?");
			$stmt->bind_param("i" , $timetable_id);
			
			$stmt->execute();
			$result = $stmt->get_result();
			
			$index_array = array();
			$temp = false;
			while($row = $result->fetch_assoc()){
				$index_array[] = $row['lesson_index'];
				$temp = true;
			}
			if($temp){
				$timetable_array[] = new Timetable($index_array , $user_email , $timetable_id);
			}
		}
			
		self::disconnect();
		return $timetable_array;
	}
	
}

?>