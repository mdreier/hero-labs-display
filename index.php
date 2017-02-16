<?php

session_start();

require_once("config.php");
require_once("hldisplay.inc.php");

$hldisplay = new HLDisplay($config);
$hldisplay->initializeDropboxClient();
$hldisplay->handleOAuth();

// checks if access token is required
if(!$hldisplay->isAuthorized())
{
	// redirect user to dropbox auth page
	$hldisplay->redirectToAuthPage();
}

//Read portfolios from dropbox
$portfolios = $hldisplay->readPortfolios();

//Get and extract selected portfolio
$characters = array();
$statblock_content = "";

$format = "html";
if (isset($_POST['format'])) {
	$format = $_POST['format'];
}

if (isset($_POST['portfolio'])) {
	$hldisplay->loadPortfolio($_POST['portfolio']);
	$characters = $hldisplay->readCharacters();

	//Read statblock
	if (isset($_POST['character'])) {
		$character_index = intval($_POST['character']);

		$statblock_content = $hldisplay->readStatblock($character_index, $format);
	}
}
?>

<html>
	<head>
		<title>HeroLabs Portfolio Display</title>
		<meta charset="utf-8"/>
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
							<option value="text" <?=$format == 'text' ? "selected=\"selected\"" : "" ?>>Plain Text</option>
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
			if ($format == 'html') {
				echo "<p>$statblock_content</p>";
			} else {
				echo "<pre>$statblock_content</pre>";
			}
		?>
	</body>
</html>
