<?php

class Timetable{
	//core information
	private $lesson_index; # array of lesson_index
	private $cost = 0;
	private $user_email;
	private $timetable_number;
	
	public function __construct($lesson_index){
		$this->lesson_index = $lesson_index;
	}
	
	public static function with_user($lesson_index, $user_email , $timetable_number){
		$instance = new self($lesson_index);
		$instance->set_user_email($user_email);
		$instance->set_timetable_number($timetable_number);
		return $instance;
	}
	
	public function set_user_email($user_email){
		$this->user_email = $user_email;
	}
	
	public function set_timetable_number($timetable_number){
		$this->timetable_number = $timetable_number;
	}
	
	public function __toString(){
		echo "<br> Cost of this is: ".$this->cost."<br>";
		print_r ($this->lesson_index);
		
		#for($i=0; $i<sizeof($this->course_and_index); $i++){
		#	echo "<br>".array_keys($this->course_and_index)[$i].": ".array_values($this->course_and_index)[$i];
		#}
	}
	
	public function set_cost($cost){
		$this->cost = $cost;
	}
	
	public function get_timetable_cost(){
		return $this->cost;
	}
	
	public function get_lesson_index(){
		return $this->lesson_index;
	}
	
	public function get_user_email(){
		return $this->user_email;
	}
	
	public function get_timetable_number(){
		return $this->timetable_number;
	}


}

?>