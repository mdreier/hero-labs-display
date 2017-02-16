<?php

class HLDisplay
{
  private $dropbox;
  private $config;
  private $portfolio_index;
  private $portfolio_path;

  function __construct($config) {
    $this->config = $config;
  }

  function initializeDropboxClient() {
    $this->dropbox = new DropboxClient(array(
    	'app_key' => $this->config['dropbox_app_key'],
    	'app_secret' => $this->config['dropbox_app_secret'],
    	'app_full_access' => true,
    	'proxy_url' => $this->config['proxy_url'],
    	'proxy_user' => $this->config['proxy_user'],
    	'proxy_password' => $this->config['proxy_password'],
    ), 'en');
    $this->dropbox->SetUseCUrl(true);
  }

  function handleOauth() {
    $access_token = $_SESSION['oauth_access_token'];

    if(!empty($access_token)) {
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

  function isAuthorized() {
    return $this->dropbox->IsAuthorized();
  }

  function redirectToAuthPage() {
    $return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
  	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
  	$request_token = $dropbox->GetRequestToken();
  	$_SESSION['oauth_request_token'] = $request_token;
  	header('Location: ' . $auth_url);
  	die("Authentication required. <a href='$auth_url'>Click here</a> if you are not redirected automatically.");
  }

  function readPortfolios() {
    if (empty($_SESSION['files'])) {
    	$files = $this->dropbox->GetFiles("/Apps/Hero Lab",true);
    	$_SESSION['files'] = $files;
    } else {
    	$files = $_SESSION['files'];
    }

    $portfolios = array();
    foreach ($files as $filename => $file) {
    	if (substr($filename, -4) == ".por") {
    		$portfolios[] = $filename;
    	}
    }
    return $portfolios;
  }

  function loadPortfolio($portfolio_name) {
    //Create working directory
  	$this->portfolio_path = $this->config['working_directory'] . "/" . sha1($portfolio_name);
  	if (!is_dir($this->portfolio_path)) {
  		mkdir($this->portfolio_path, 0777, true);
  	}
  	$file = $this->dropbox->GetMetadata($portfolio_name);
  	$portfolio_file = $this->portfolio_path . "/" . $file->revision . ".zip";
  	if (!file_exists($portfolio_file)) {
  		$this->dropbox->DownloadFile($file, $portfolio_file);
  		//Load ZIP file
  		$zip = new ZipArchive();
  		$zip->open($portfolio_file);
  		$zip->extractTo($this->portfolio_path);
  		$zip->close();
  	}

  	//Read portfolio index
  	$this->portfolio_index = simplexml_load_file($this->portfolio_path . "/index.xml");
  }

  function readCharacters() {
    if (isset($this->portfolio_index)) {
    	foreach ($this->portfolio_index->characters->character as $character) {
    		$characters[] = array(
    			'name'    => $character['name'],
    			'summary' => $character['summary']
    		);
    	}
      return $characters;
    } else {
      return array();
    }
  }

  function readStatblock($character_index, $format) {
    if (isset($this->portfolio_index)) {
      $character = $this->portfolio_index->characters->character[$character_index];
      $statblock = $character->xpath("statblocks/statblock[@format='$format']");
      $statblock_content = "";
      if ($statblock != FALSE) {
        $statblock_file = $this->portfolio_path . "/" . $statblock[0]['folder'] . "/" . $statblock[0]['filename'];
        if ($format == 'html') {
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
