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
namespace HLDisplay;

require_once("DropPHP/DropboxClient.php");
require_once('configuration.class.php');
require_once('portfolio.class.php');
require_once('character.class.php');

/**
 * Hero Labs Display class.
  */
class HLDisplay
{
  /**
   * DropPHP instance.
   */
  private $dropbox;
  /**
   * Currently selected portfolio.
   */
  private $selectedPortfolio;
  /**
   * Create new instance and pass in configuration.
   */
  function __construct() {
  }

  /**
   * Handle OAuth login. Checks or sets the OAuth access or request token in the
   * HTTP session.
   */
  function handleOauth() {
    //Access token is stored in the session if user has authenticated before.
    if (isset($_SESSION['oauth_access_token'])) {
      $access_token = $_SESSION['oauth_access_token'];
    }

    if(!empty($access_token)) {
      //Set the access token for further use by the dropbox client.
    	Configuration::getDropbox()->SetAccessToken($access_token);
    }
    elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
    {
    	// then load our previosly created request token
    	$request_token = $_SESSION['oauth_request_token'];
    	if(empty($request_token)) die('Request token not found!');

    	// get & store access token, the request token is not needed anymore
    	$access_token = Configuration::getDropbox()->GetAccessToken($request_token);
    	$_SESSION['oauth_access_token'] = $access_token;
    	unset($_SESSION['oauth_request_token']);
    }
  }

  /**
   * Check if the application has been authorized by the user.
   * @return boolean TRUE if the application is authorized and FALSE otherwise.
   */
  function isAuthorized() {
    return Configuration::getDropbox()->IsAuthorized();
  }

  /**
   * Redirect the user to the Dropbox OAuth authorization page. This terminates
   * processing.
   */
  function redirectToAuthPage() {
    //Build return URL
    $return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
    //Dropbox OAuth URL is build by DropPHP
  	$auth_url = Configuration::getDropbox()->BuildAuthorizeUrl($return_url);
    //Generate request token and store in session for later use by handleOAuth()
  	$request_token = Configuration::getDropbox()->GetRequestToken();
  	$_SESSION['oauth_request_token'] = $request_token;
    //Redirect to authorization page
  	header('Location: ' . $auth_url);
  	die("Authentication required. <a href='$auth_url'>Click here</a> if you are not redirected automatically.");
  }
}
?>
