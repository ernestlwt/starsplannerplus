<?php

class CourseIndex{
	//core information
	private $course_code;
	private $lesson_index;
	
	public function __construct($code , $index){
		$this->course_code = $code;
		$this->lesson_index = $index;
	}
	
	public function __toString(){
		return "CourseCode:".$this->course_code."\nLessonIndex:".$this->lesson_index."\n";
	}
	
	public function get_course_code(){
		return $this->course_code;
	}
	
	public function get_lesson_index(){
		return $this->lesson_index;
	}

}

?>