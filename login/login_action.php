<?php
require('../config.php');

$username = required_param('username', PARAM_TEXT);
$password = required_param('password', PARAM_TEXT);

$user = authenticate_user_login($username, $password);


if(!empty($user)){
	if(complete_user_login($user)){   
	    redirect($CFG->wwwroot.'/my/');
	} else {
		redirect($CFG->wwwroot.'/login/index.php');
	}
}
else
{
 	redirect($CFG->wwwroot.'/login/index.php');
}