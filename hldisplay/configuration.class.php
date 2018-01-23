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

/**
 * Class for configuration access.
 */
class Configuration {

  /**
   * @var Internal configuration array.
   */
  private static $config;

  /**
   * @var Dropbox instance.
   */
  private static $dropbox;

  public static function initialize($config) {
    self::$config = $config;
    self::initializeDropboxClient();
  }

  /**
   * Get a configuration value.
   * @param string $key Key of the configuration value.
   * @param string $default Default value to be returned if the key is
   * not set in the config. Defaults to an empty string.
   * @param boolean $required Setting this to TRUE causes the script to fail
   * if the value is not set.
   * @return string The configuration value for the given key, or the
   * default value if the key is not set.
   */
  public static function get($key, $default = '', $required = false) {
    if (isset(self::$config[$key])) {
      return self::$config[$key];
    } else {
      if ($required) {
        die("Required configuration $key is not set");
      }
      return $default;
    }
  }

  /**
   * Get the dropbox client.
   */
  public static function getDropbox() {
    return self::$dropbox;
  }

  /**
   * Initialize the internal DropPHP instance.
   */
  protected static function initializeDropboxClient() {
    self::$dropbox = new \DropboxClient(array(
    	'app_key' => Configuration::get('dropbox_app_key', '', true),
    	'app_secret' => Configuration::get('dropbox_app_secret', '', true),
    	'app_full_access' => true,
    	'proxy_url' => Configuration::get('proxy_url'),
    	'proxy_user' => Configuration::get('proxy_user'),
    	'proxy_password' => Configuration::get('proxy_password'),
    ), 'en');
    //Use CUrl for proxy support.
    //self::$dropbox->SetUseCUrl(true);
  }
}
