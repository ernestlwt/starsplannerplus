<?php

trait JsonSerializer_1 {
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}

class Course implements \JsonSerializable{
	//core information
	private $course_code;
	private $course_name;
	private $course_au;
	
	//exam information
	private $course_exam_date;
	private $course_exam_day;
	private $course_exam_time;
	private $course_exam_duration;
	private $course_has_exam;
	

	use JsonSerializer_1;
	
	public function __construct($code , $name , $au){
		$this->course_code = $code;
		$this->course_name = $name;
		$this->course_au = $au;
		$this->course_has_exam = false;
	}
	
	public static function with_exam_details($code , $name , $au , $date , $day , $time , $duration , $has_exam){
		$instance = new self($code , $name , $au);
        $instance->set_exam_date($date);
        $instance->set_exam_day($day);
        $instance->set_exam_time($time);
        $instance->set_exam_duration($duration);
        $instance->set_has_exam($has_exam);
        return $instance;
	}
	
	public function set_exam_date($date){
		$this->course_exam_date = $date;
	}
	
	public function set_exam_day($day){
		$this->course_exam_day = $day;
	}
	
	public function set_exam_time($time){
		$this->course_exam_time = $time;
	}
	
	public function set_exam_duration($duration){
		$this->course_exam_duration = $duration;
	}
	
	public function set_has_exam($has_exam){
		$this->course_has_exam = $has_exam;
	}
	
	public function __toString(){
		return "CourseCode:".$this->course_code."\nCourseName:".$this->course_name."\nCourseAU:".$this->course_au."\nExamDate:".$this->course_exam_date."\nExamDay:".$this->course_exam_day."\nExamTime:".$this->course_exam_time."\nExamDuration:".$this->course_exam_duration."\nHasExam:".$this->course_has_exam."\n";
	}
	
	public function add_exam_info($date , $day , $time , $duration){
		$this->course_exam_date = $date;
		$this->course_exam_day = $day;
		$this->course_exam_time = $time;
		$this->course_exam_duration = $duration;
		$this->course_has_exam = true;
	}
	
	public function get_course_code(){
		return $this->course_code;
	}
	
	public function get_course_name(){
		return $this->course_name;
	}
	
	public function get_course_au(){
		return $this->course_au;
	}
	
	public function get_course_exam_date(){
		return $this->course_exam_date;
	}
	
	public function get_course_exam_day(){
		return $this->course_exam_day;
	}
	
	public function get_course_exam_time(){
		return $this->course_exam_time;
	}
	
	public function get_course_exam_duration(){
		return $this->course_exam_duration;
	}
	
	public function has_exam(){
		return $this->course_has_exam;
	}
	
	public function get_course_exam_datetime(){
		return $this->course_exam_date." ".$this->course_exam_time;
	}
	
}

?>