<?php

class User{
	private $user_email;
	private $user_password;

	public function __construct($email , $password){
		$this->user_email = $email;
		$this->user_password = $password;
	}
	
	public function get_user_email(){
		return $this->user_email;
	}
	
	public function get_user_password(){
		return $this->user_password;
	}
	
}

?>