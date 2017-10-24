<?php

trait JsonSerializer {
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}

class Lesson implements \JsonSerializable {
	//core information
	private $lesson_index;
	private $lesson_type;
	private $lesson_group;
	private $lesson_day;
	private $lesson_time;
	private $lesson_venue;
	private $lesson_remark;
	
	use JsonSerializer;
	
	public function __construct($index , $type , $group , $day , $time , $venue , $remark){
		$this->lesson_index = $index;
		$this->lesson_type = $type;
		$this->lesson_group = $group;
		$this->lesson_day = $day;
		$this->lesson_time = $time;
		$this->lesson_venue = $venue;
		$this->lesson_remark = $remark;
	}
	
	public function __toString(){
		return "{\"LessonIndex\": \"".$this->lesson_index."\", \"LessonType\": \"".$this->lesson_type."\", \"LessonGroup\": \"".$this->lesson_group."\", \"LessonDay\": \"".$this->lesson_day."\", \"LessonTime\": \"".$this->lesson_time."\", \"LessonVenue\": \"".$this->lesson_venue."\", \"LessonRemark\": \"".$this->lesson_remark."\"}";
	}
	
	public function get_lesson_index(){
		return $this->lesson_index;
	}
	
	public function get_lesson_type(){
		return $this->lesson_type;
	}
	
	public function get_lesson_group(){
		return $this->lesson_group;
	}
	
	public function get_lesson_day(){
		return $this->lesson_day;
	}
	
	public function get_lesson_time(){
		return $this->lesson_time;
	}
	
	public function get_lesson_start_time(){
		return substr($this->lesson_time, 0, 4);
	}
	
	public function get_lesson_end_time(){
		return substr($this->lesson_time, 5);
	}
	
	public function get_lesson_venue(){
		return $this->lesson_venue;
	}
	
	public function get_lesson_remark(){
		return $this->lesson_remark;
	}

	
}

?>