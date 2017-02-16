<?php

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
   * Configuration data. An associated array of configuration
   * keys and values.
   */
  private $config;
  /**
   * Parsed Simple XML of the portfoli index file of the currently
   * selected portfolio. Not set when no portfolio is selected.
   */
  private $portfolio_index;
  /**
   * Generated path for the currently selected portfolio.
   */
  private $portfolio_path;

  /**
   * Create new instance and pass in configuration.
   * @param string[] $config Cnofiguration data as associative array.
   */
  function __construct($config) {
    $this->config = $config;
  }

  /**
   * Initialize the internal DropPHP instance.
   */
  function initializeDropboxClient() {
    $this->dropbox = new DropboxClient(array(
    	'app_key' => $this->config['dropbox_app_key'],
    	'app_secret' => $this->config['dropbox_app_secret'],
    	'app_full_access' => true,
    	'proxy_url' => $this->config['proxy_url'],
    	'proxy_user' => $this->config['proxy_user'],
    	'proxy_password' => $this->config['proxy_password'],
    ), 'en');
    //Use CUrl for proxy support.
    $this->dropbox->SetUseCUrl(true);
  }

  /**
   * Handle OAuth login. Checks or sets the OAuth access or request token in the
   * HTTP session.
   */
  function handleOauth() {
    //Access token is stored in the session if user has authenticated before.
    $access_token = $_SESSION['oauth_access_token'];

    if(!empty($access_token)) {
      //Set the access token for further use by the dropbox client.
    	$this->dropbox->SetAccessToken($access_token);
    }
    elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
    {
    	// then load our previosly created request token
    	$request_token = $_SESSION['oauth_request_token'];
    	if(empty($request_token)) die('Request token not found!');

    	// get & store access token, the request token is not needed anymore
    	$access_token = $this->dropbox->GetAccessToken($request_token);
    	$_SESSION['oauth_access_token'] = $access_token;
    	unset($_SESSION['oauth_request_token']);
    }
  }

  /**
   * Check if the application has been authorized by the user.
   * @return boolean TRUE if the application is authorized and FALSE otherwise.
   */
  function isAuthorized() {
    return $this->dropbox->IsAuthorized();
  }

  /**
   * Redirect the user to the Dropbox OAuth authorization page. This terminates
   * processing.
   */
  function redirectToAuthPage() {
    //Build return URL
    $return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
    //Dropbox OAuth URL is build by DropPHP
  	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
    //Generate request token and store in session for later use by handleOAuth()
  	$request_token = $dropbox->GetRequestToken();
  	$_SESSION['oauth_request_token'] = $request_token;
    //Redirect to authorization page
  	header('Location: ' . $auth_url);
  	die("Authentication required. <a href='$auth_url'>Click here</a> if you are not redirected automatically.");
  }

  /**
   * Read portfolios available in the user's dropbox. Reads only .por files
   * in the Apps/Hero Lab directory.
   *
   * @return string[] List of portfolio (file) names. If no portfolios are
   * stored in the user's dropbox, an empty array is returned.
   */
  function readPortfolios() {
    //List of portfolio files is cached to improve performance
    if (empty($_SESSION['files'])) {
    	$files = $this->dropbox->GetFiles("/Apps/Hero Lab",true);
    	$_SESSION['files'] = $files;
    } else {
    	$files = $_SESSION['files'];
    }

    //Build list of portfolios
    $portfolios = array();
    foreach ($files as $filename => $file) {
    	if (substr($filename, -4) == ".por") {
    		$portfolios[] = $filename;
    	}
    }
    return $portfolios;
  }

  /**
   * Load a portfolio for further processing.
   *
   * @param string $portfolio_name The (file) name of the portfolio inside
   * Dropbox.
   */
  function loadPortfolio($portfolio_name) {
    //Create working directory based on hash of portfolio name
  	$this->portfolio_path = $this->config['working_directory'] . "/" . sha1($portfolio_name);
  	if (!is_dir($this->portfolio_path)) {
  		mkdir($this->portfolio_path, 0777, true);
  	}
    /*
       Download portfolio file from Dropbox, name by revision. A file is only
       downloaded once unless a new revision is created. This should increase
       performance. The Portfolio file is then extracted into the working
       directory for later access.
    */

  	$file = $this->dropbox->GetMetadata($portfolio_name);
  	$portfolio_file = $this->portfolio_path . "/" . $file->revision . ".zip";
  	if (!file_exists($portfolio_file)) {
  		$this->dropbox->DownloadFile($file, $portfolio_file);
  		//Extract ZIP file to working directory.
  		$zip = new ZipArchive();
  		$zip->open($portfolio_file);
  		$zip->extractTo($this->portfolio_path);
  		$zip->close();
  	}

  	//Read portfolio index file
  	$this->portfolio_index = simplexml_load_file($this->portfolio_path . "/index.xml");
  }

  /**
   * Read characters in the currently selected portfolio. Call loadPortfolio()
   * before calling this method.
   *
   * @return array An array of all characters in the currently selected
   * portfolio. Each entry is itself an associative array with the name and
   * a summary of the character. Returns an empty array if no portfolio is
   * currently selected.
   */
  function readCharacters() {
    if (isset($this->portfolio_index)) {
      //Collect characters from portfolio.
    	foreach ($this->portfolio_index->characters->character as $character) {
    		$characters[] = array(
    			'name'    => $character['name'],
    			'summary' => $character['summary']
    		);
    	}
      return $characters;
    } else {
      //No portfolio loaded.
      return array();
    }
  }

  /**
   * Read the statblock of a character in the currently selected portfolio.
   * Call loadPortfolio() before calling this method.
   *
   * @param int $character_index Index of the selected character.
   * @param string $format The target format. May be either html or text.
   * @return string Statblock of the selected character in the desired format.
   */
  function readStatblock($character_index, $format) {
    if (isset($this->portfolio_index)) {
      //Get selected character and path of statblock file
      $character = $this->portfolio_index->characters->character[$character_index];
      $statblock = $character->xpath("statblocks/statblock[@format='$format']");
      $statblock_content = "";
      if ($statblock != FALSE) {
        //Load content from statblock file
        $statblock_file = $this->portfolio_path . "/" . $statblock[0]['folder'] . "/" . $statblock[0]['filename'];
        if ($format == 'html') {
          //For HTML, extract body content from stablock file (which is
          //a complete HTML file).
          $statblock_content = file_get_contents($statblock_file);
          $content_begin = strpos($statblock_content, '<body>');
          if ($content_begin > -1) {
            $content_begin += 6;
            $content_end = strpos($statblock_content, '</body>');
            if ($content_end < $content_begin) {
              $content_end = $content_begin;
            }
            $statblock_content = substr($statblock_content, $content_begin, -16);
          }
        } elseif ($format = 'text') {
          //For plain text, just get complete file.
          $statblock_content = file_get_contents($statblock_file);
        }
      }
      return $statblock_content;
    } else {
      return "";
    }
  }
}
?>
