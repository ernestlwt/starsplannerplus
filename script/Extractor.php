<?php

require_once('../entity/Course.php');
require_once('../entity/CourseIndex.php');
require_once('../entity/Lesson.php');
require_once('../database/DBManager.php');

class Extractor{
	//input information
	private $year;
	private $semester;
	private $course_content_filename;
	private $exam_content_filename;
	
	//arrays to store processed information
	private $course_array;
	private $index_array;
	private $lesson_array;
	
	function __construct($year , $semester , $course_filename , $exam_filename){
		$this->year = $year;
		$this->semester = $semester;
		$this->course_content_filename = $course_filename;
		$this->exam_content_filename = $exam_filename;
		$this->fetch_course_content();
		$this->fetch_exam_content();
		$this->parse_course_content();
		$this->parse_exam_content();
		$this->convert_to_objects();
	}
	
	function get_course_array(){
		return $this->course_array;
	}
	
	function get_index_array(){
		return $this->index_array;
	}
	
	function get_lesson_array(){
		return $this->lesson_array;
	}
	
	private function send_post_request($url, $postdata){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$response = curl_exec($ch);
		curl_close ($ch);
		return $response;
	}
	
	private function fetch_course_content(){
		$course_url = "https://wish.wis.ntu.edu.sg/webexe/owa/AUS_SCHEDULE.main_display1";
		$course_post = [
			'staff_access' => 'false',
			'acadsem' => $this->year.';'.$this->semester,
			'r_subj_code'   => '',
			'r_search_type' => 'F',
			'boption' => 'Search',
		];

		$course_content = $this->send_post_request($course_url , $course_post);
		
		file_put_contents($this->course_content_filename , $course_content);
	}
	
	private function fetch_exam_content(){
		$exam_url = "https://wis.ntu.edu.sg/webexe/owa/exam_timetable_und.get_detail";
		$exam_post = [
			'p_exam_dt' => '',
			'p_start_time' => '',
			'p_dept' => '',
			'p_subj' => '',
			'p_venue' => '',
			'p_plan_no' => '1',
			'p_exam_yr' => $this->year,
			'p_semester' => $this->semester,
			'academic_session' => 'Semester '.$this->semester.' Academic Year '.$this->year.'-'.($this->year+1),//not exactly required
			'boption' => 'Next',
		];

		$exam_content = $this->send_post_request($exam_url , $exam_post);
		
		file_put_contents($this->exam_content_filename , $exam_content);
	}
	
	private function parse_course_content(){
		$course_content = file_get_contents($this->course_content_filename);

		// remove starting section
		$course_content = preg_replace('/<HTML>([\s\S]*?)<HR SIZE=2>\n/' ,''  , $course_content);

		// remove ending section
		$course_content = preg_replace('/<P><HR>\n<B>([\s\S]*?)<\/HTML>\n/' ,''  , $course_content);

		// remove  prerequisites
		$course_content = preg_replace('/<TR>\n<TD><B><FONT SIZE=2 COLOR=#FF00FF>([\s\S]*?)<\/TR>\n/' , '' , $course_content);

		// remove  remarks
		$course_content = preg_replace('/<TR>\n<TD WIDTH="100"><B><FONT SIZE=2 COLOR=#FF00FF>Remark:([\s\S]*?)<\/TR>\n/' , ''  , $course_content);

		// remove table headers
		$course_content = preg_replace('/<TR>\n<TH><B>([\s\S]*?)<\/TR>\n/' , ''  , $course_content);

		// replace course headers
		$course_content = preg_replace('/<TD WIDTH="100"><B><FONT COLOR=#0000FF>/' , 'CourseCode:'  , $course_content);
		$course_content = preg_replace('/<TD WIDTH="500"><B><FONT COLOR=#0000FF>/' , 'CourseName:'  , $course_content);
		$course_content = preg_replace('/<TD WIDTH="50"><B><FONT COLOR=#0000FF>   /' , 'AU:'  , $course_content);
		$course_content = preg_replace('/ AU<\/FONT><\/B><\/TD>/' , ''  , $course_content);
		$course_content = preg_replace('/<\/FONT><\/B><\/TD>/' , ''  , $course_content);

		// additional cleaning
		$course_content = preg_replace('/<P><HR>\n/' , ''  , $course_content);
		$course_content = preg_replace('/<TABLE >\n/' , ''  , $course_content);
		$course_content = preg_replace('/<\/TABLE >\n/' , ''  , $course_content);
		$course_content = preg_replace('/<\/TABLE>\n/' , ''  , $course_content);
		$course_content = preg_replace('/<TR>\n/' , ''  , $course_content);
		$course_content = preg_replace('/<\/TR>\n/' , ''  , $course_content);
		$course_content = preg_replace('/<TD><B>/' , ''  , $course_content);
		$course_content = preg_replace('/<\/B><\/TD>/' , ''  , $course_content);
		$course_content = preg_replace('/<TABLE  border>\n/' , ''  , $course_content);
		$course_content = preg_replace('/<TR BGCOLOR="#CAE2EA">\n/' , ''  , $course_content);
		$course_content = preg_replace('/<TR BGCOLOR="#EBFAFF">\n/' , ''  , $course_content);
		$course_content = preg_replace('/\n$/' , ''  , $course_content);// remove last linebreak if exist
		

		// remove blanks for online modules
		$course_content = preg_replace('/<TD>&nbsp;<\/TD>/' , ''  , $course_content);

		file_put_contents($this->course_content_filename , $course_content);
	}	
	
	private function parse_exam_content(){
		$exam_content = file_get_contents($this->exam_content_filename);

		// remove starting section
		$exam_content = preg_replace('/<HTML>([\s\S]*?)Duration<\/b>\n<\/font><\/td>\n/' ,''  , $exam_content);

		// remove ending section
		$exam_content = preg_replace('/\n<TR ALIGN="yes" VALIGN="yes" bgcolor=#[9CF]{4}FF>\n<\/table>([\s\S]*?)<\/HTML>/' ,''  , $exam_content);

		// additional cleaning
		$exam_content = preg_replace('/<TR ALIGN="yes" VALIGN="yes" bgcolor=#[9CF]{4}FF>\n/' ,''  , $exam_content);
		$exam_content = preg_replace('/<td align=left width=\d0% valign=top>\n/' ,''  , $exam_content);
		$exam_content = preg_replace('/<\/td>\n/' ,''  , $exam_content);
		$exam_content = preg_replace('/<\/tr>\n/' ,''  , $exam_content);
		$exam_content = preg_replace('/\n<\/tr>/' ,''  , $exam_content);
		$exam_content = preg_replace('/\n$/' , ''  , $exam_content);// remove last linebreak if exist

		file_put_contents($this->exam_content_filename , $exam_content);
	}
	
	private function convert_to_objects(){
		if ($course_file = fopen($this->course_content_filename, "r")) {
			while(!feof($course_file)) {
				$line = trim(fgets($course_file));
				if(strpos($line, 'CourseCode:') !== false){ // 0 not equals to false when you use !==
					$code = preg_replace('/CourseCode:/' , ''  , $line);
					$name = trim(fgets($course_file));
					$name = preg_replace('/CourseName:/' , ''  , $name);
					$au = trim(fgets($course_file));
					$au = preg_replace('/AU:/' , ''  , $au);
					$course = new Course($code , $name , $au);
					$this->course_array[] = $course;
				}else{
					$index = $line;
					if(!empty($index)){
						$course_index = new CourseIndex($code , $index);
						$this->index_array[] = $course_index;
					}else{
						$index = $lesson->get_lesson_index();
					}
					$type = trim(fgets($course_file));
					$group = trim(fgets($course_file));
					$day = trim(fgets($course_file));
					$time = trim(fgets($course_file));
					$venue = trim(fgets($course_file));
					$remark = trim(fgets($course_file));
					$lesson = new Lesson($index , $type , $group , $day , $time , $venue , $remark);
					$this->lesson_array[] = $lesson;
				}
			}
			fclose($course_file);
		}
		$counter = 0;
		if($exam_file = fopen($this->exam_content_filename, "r")){
			while(!feof($exam_file)) {
				$exam_date = trim(fgets($exam_file));
				$exam_day = trim(fgets($exam_file));
				$exam_time = trim(fgets($exam_file));
				$code = trim(fgets($exam_file));
				fgets($exam_file); // clear out this line
				$exam_duration = trim(fgets($exam_file));
				foreach($this->course_array as $course){
					if($code == $course->get_course_code()){
						$course->add_exam_info($exam_date , $exam_day , $exam_time , $exam_duration);
						$counter++;
						break;
					}
				}
			}
			fclose($exam_file);
		}
	}

}

//create extractor and extract information
$extractor = new Extractor("2017" , "1" , "course_content.txt" , "exam_content.txt");
$course_array = $extractor->get_course_array();
$index_array = $extractor->get_index_array();
$lesson_array = $extractor->get_lesson_array();

//put into database
DBManager::add_courses($course_array);
DBManager::add_course_indexes($index_array);
DBManager::add_lessons($lesson_array);

?>