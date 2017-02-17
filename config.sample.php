<?php
/*
MIT License

Copyright (c) 2017 Martin Dreier

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
$config = array(

	/*
	 Set the Dropbox API application key and secret. You can get these
	 by registering this app on the Dropbox developer portal:
	 https://www.dropbox.com/developers/apps/create
	*/
	'dropbox_app_key'		=> "<app key>",
	'dropbox_app_secret'	=> "<app secret>",

	/*
	 Proxy configuration.
	 If your server must use a proxy to connect to the Dropbox servers
	 configure it here. If no proxy is set direct connection is used.
	 Use 'proxy_user' and 'proxy_password' if your proxy requires
	 authentication.
	*/
	'proxy_url'				=> '',
	'proxy_user'			=> '',
	'proxy_password'		=> '',

	/*
	 Path to the working directory for caches and tokens. For security reasons
	 this directory should not be reachable externally.
	*/
	'working_directory' 	=> dirname(__FILE__) . '/work',

	/*
	 Search parameters.
	 'search_path' is the path inside Dropbox which is searched for portfolios.
	               The default value is the path used for Hero Lab on iPad. You
								 can add any number of paths here. To search the whole Dropbox
								 directory, set it to '/', but be aware that this will cause a
								 dramaticperformance decrease depending on the number of files
								 in your dropbox.
	'result_with_path' determines if the path of the portfolio in Dropbox is
	                   shown in the list of portfolios. If set to true, the path
										 is shown. If set to false, only the portfolio name (i.e.
										 the file name) is shown in the list.
	*/
	'result_size'			=> 25,
	'search_path'			=> array('/Apps/Hero Lab'),
	'result_with_path' => false
);

?>
