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
use \HLDisplay as hl;

session_start();

require_once("config.php");
require_once("hldisplay.inc.php");

$hldisplay = new hl\HLDisplay($config);
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
		<link rel="stylesheet" href="font/gandhi.css" type="text/css" charset="utf-8" />
		<link rel="stylesheet" href="hldisplay.css" type="text/css" charset="utf-8" />
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
			<p>Press "Update" after selecting a new value to update the other lists and the statblock.</p>
		</form>
		<h2>Statblock</h2>
		  <section class="statblock">
			<?php
				if ($format == 'html') {
					echo "$statblock_content";
				} else {
					echo "<pre>$statblock_content</pre>";
				}
			?>
		</section>
	</body>
</html>
