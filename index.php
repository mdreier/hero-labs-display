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
if (empty($_SESSION['files'])) {
	$files = $dropbox->GetFiles("/Apps/Hero Lab",true);
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

//Get and extract selected portfolio
$characters = array();
$statblock_content = "";
if (isset($_POST['portfolio'])) {
	//Create working directory
	$portfolio_path = $config['working_directory'] . "/" . sha1($_POST['portfolio']);
	if (!is_dir($portfolio_path)) {
		mkdir($portfolio_path, 0777, true);
	}
	$file = $dropbox->GetMetadata($_POST['portfolio']);
	$portfolio_file = $portfolio_path . "/" . $file->revision . ".zip";
	if (!file_exists($portfolio_file)) {
		$dropbox->DownloadFile($file, $portfolio_file);
		//Load ZIP file
		$zip = new ZipArchive();
		$zip->open($portfolio_file);
		$zip->extractTo($portfolio_path);
		$zip->close();
	}

	//Read portfolio index
	$portfolio_index = simplexml_load_file($portfolio_path . "/index.xml");
	foreach ($portfolio_index->characters->character as $character) {
		$characters[] = array(
			'name'    => $character['name'],
			'summary' => $character['summary']
		);
	}
	
	//Read statblock
	if (isset($_POST['character'])) {
		$character_index = intval($_POST['character']);
		$character = $portfolio_index->characters->character[$character_index];
		$format = "html";
		if (isset($_POST['format'])) {
			$format = $_POST['format'];
		}
		$statblock = $character->xpath("statblocks/statblock[@format='$format']");
		if ($statblock != FALSE) {
			$statblock_file = $portfolio_path . "/" . $statblock[0]['folder'] . "/" . $statblock[0]['filename'];
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
	}
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
								$selected = isset($_POST['portfolio']) && $portfolio == $_POST['portfolio'];
								$selected_attr = $selected ? " selected=\"selected\"" : "";
								echo "<option value=\"" . htmlspecialchars($portfolio) . "\"" . $selected_attr . ">" . htmlspecialchars($portfolio) . "</option>\n";
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Character:</td>
					<td>
						<select name="character">
						<?php
						foreach ($characters as $index => $character) {
							$selected = isset($_POST['character']) && $index == $_POST['character'];
							$selected_attr = $selected ? " selected=\"selected\"" : "";
							echo "<option value=\"" . $index . "\"" . $selected_attr . ">" . htmlspecialchars($character['name']) . "</option>\n";
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Format:</td>
					<td>
						<select name="format">
							<option value="html">HTML</option>
							<option value="text" <?=$_POST['format'] == 'text' ? "selected=\"selected\"" : "" ?>>Plain Text</option>
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
		<h2>Statblock</h2>
		<?php
			if ($_POST['format'] == 'html') {
				echo "<p>$statblock_content</p>";
			} else {
				echo "<pre>$statblock_content</pre>";
			}
		?>
	</body>
</html>