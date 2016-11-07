<?php

$config = array(

	//Set the Dropbox app key and app secret
	'dropbox_app_key'		=> "<app key>",
	'dropbox_app_secret'	=> "<app secret>",
	
	//Proxy configuration
	'proxy_url'				=> '',
	'proxy_user'			=> '',
	'proxy_password'		=> '',
	
	//Path to the working directory for caches and tokens
	'working_directory' 	=> dirname(__FILE__) . '/work',
	
	//Search parameters
	'result_size'			=> 25
);

?>