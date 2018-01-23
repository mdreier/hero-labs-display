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
    //Calculate return URL for OAuth
    $return_url = $this->getProtocol() . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?auth_redirect=1";
    
    //Access token is stored in the session if user has authenticated before.
    if (isset($_SESSION['oauth_bearer_token'])) {
      $bearer_token = $_SESSION['oauth_bearer_token'];
    }

    if(!empty($bearer_token)) {
      //Set the access token for further use by the dropbox client.
      Configuration::getDropbox()->SetBearerToken($bearer_token);
    }
    elseif(!empty($_GET['auth_redirect'])) // are we coming from dropbox's auth page?
    {
      // then load our previosly created request token
      $bearer_token = Configuration::getDropbox()->GetBearerToken(null, $return_url);

      if(empty($bearer_token)) die('Bearer token not found!');
      $_SESSION['oauth_bearer_token'] = $bearer_token;
    }
    elseif(!Configuration::getDropbox()->IsAuthorized())
    {
      //Redirect to Dropbox authorization page
      $auth_url = Configuration::getDropbox()->BuildAuthorizeUrl($return_url);
      header("Location: " . $auth_url);
      die("Authorization required. Click <a href=\"" . $auth_url . "\">here</a> to redirect to Dropbox.");
    }
  }
  
  /**
   * Get the correct protocol string for the current script execution.
   * @return string The protocol, http or https.
   */
  function getProtocol()
  {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
    {
      return "https";
    } else {
      return "http";
    }
  }

  /**
   * Check if the application has been authorized by the user.
   * @return boolean TRUE if the application is authorized and FALSE otherwise.
   */
  function isAuthorized() {
    return Configuration::getDropbox()->IsAuthorized();
  }
}
?>
