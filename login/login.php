<?php

require_once('../entity/User.php');
require_once('../database/DBManager.php');

header('Content-Type: application/json');


session_start();

if(isset($_POST['action']) && $_POST['action'] == 'login' ){
	$email = trim($_POST['email']);
	$password = trim($_POST['password']);
	$returnstr = "ERR_LOGIN_FAILED";

	if(filter_var($email, FILTER_VALIDATE_EMAIL)){
		$user = new User($email , $password);

		if(DBManager::check_user_credential($user)){
			$_SESSION['user_email'] = $email;
			$_SESSION['loggedin'] = true;
			$returnstr = $email;
		}
	}
	echo json_encode($returnstr);
}

if(isset($_POST['action']) && $_POST['action'] == 'logout' ){
	session_destroy();
	echo json_encode("LOGOUT_SUCCESS");
}

if(isset($_POST['action']) && $_POST['action'] == 'check_status' ){
	if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']){
		$returnstr = $_SESSION['user_email'];
	}else{
		$returnstr = "ERR_LOGIN_INVALID";
	}
	echo json_encode($returnstr);
}

?>
