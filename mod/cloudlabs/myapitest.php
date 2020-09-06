<?php 
$post_data = array();
$post_data['username'] = 'iprimedapiadmin@nuvelabs.com';
$post_data['password'] = 'wt4l*CG@@1';

// create curl resource
$ch = curl_init();

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Content-Type: application/x-www-form-urlencoded',
	'Cache-Control: no-cache'
));

// set url
curl_setopt($ch, CURLOPT_URL, 'https://cloudlabs.nuvepro.com/v1/users/login');

//return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

///Post the data
curl_setopt($ch,CURLOPT_POSTFIELDS, $post_data);

// $output contains the output string
$output = curl_exec($ch);

// close curl resource to free up system resources
curl_close($ch);

$data = $output;

print_r($data);exit();

