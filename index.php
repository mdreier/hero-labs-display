<?php

session_start();

require_once("DropPHP/DropboxClient.php");
require_once("config.php");

/*
$portfolio = "https://www.dropbox.com/s/n7qjcki50dr6fbc/Skulls%20%26%20Shackles.por?dl=1";
$portfolio_hash = sha1($portfolio);
$portfolio_cache = dirname(__FILE__) . "/" . $portfolio_hash . ".cache.zip";
echo "Writing to " . $portfolio_cache;

$ch = curl_init($portfolio);
$fp = fopen($portfolio_cache, "w");

curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);

curl_exec($ch);
curl_close($ch);
fclose($fp);
*/

$dropbox = new DropboxClient(array(
	'app_key' => $config['dropbox_app_key'],
	'app_secret' => $config['dropbox_app_secret'],
	'app_full_access' => true,
	'proxy_url' => $config['proxy_url'],
	'proxy_user' => $config['proxy_user'],
	'proxy_password' => $config['proxy_password'],
), 'en');
$dropbox->SetUseCUrl(true);

$access_token = $_SESSION['oauth_access_token'];

if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = $_SESSION['oauth_request_token'];
	if(empty($request_token)) die('Request token not found!');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	$_SESSION['oauth_access_token'] = $access_token;
	unset($_SESSION['oauth_request_token']);
}

// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	$_SESSION['oauth_request_token'] = $request_token;
	header('Location: ' . $auth_url);
	die("Authentication required. <a href='$auth_url'>Click here</a> if you are not redirected automatically.");
}

//Read portfolios from dropbox
$files = $dropbox->GetFiles("/Apps/Hero Lab",true);
$portfolios = array();
foreach ($files as $filename => $file) {
	if (substr($filename, -4) == ".por") {
		$portfolios[] = $filename;
	}
}

if (isset($_POST['portfolio'])) {
	
}

?>

<html>
	<head>
		<title>HeroLabs Portfolio Display</title>
	</head>
	<body>
		<h1>HeroLabs Portfolio Display</h1>
		<h2>Select Portfolio from Dropbox</h2>
		<form method="post">
			<table>
				<tr>
					<td>Portfolio:</td>
					<td>
						<select name="portfolio">
							<?php
							foreach($portfolios as $portfolio) {
								echo "<option value=\"" . $portfolio . ">" . htmlspecialchars($portfolio) . "</option>";
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Character:</td>
					<td>
						<select name="character">
						
						</select>
					</td>
				</tr>
				<tr>
					<td>Format:</td>
					<td>
						<select name="format">
							<option value="html">HTML</option>
							<option value="text">Plain Text</option>
							<option value="xml">XML</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<input type="submit" value="Update" />
					</td>
				</tr>
			</table>
		</form>
	</body>
</html>