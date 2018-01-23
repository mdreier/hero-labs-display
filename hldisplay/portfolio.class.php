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
 * Class representing portfolio data.
 */
class Portfolio {
  /**
   * @var Portfolio name.
   */
  private $name;

  /**
   * @var Portfolio path in Dropbox.
   */
  private $path;

  /**
   * @var Dropbox client instance.
   */
  private $dropbox;

  /**
   * @var Portfolio index. Parsed XML document using Simple XML.
   */
  public $index;

  /**
   * Read portfolios available in the user's dropbox. Reads only .por files
   * in the search path specified in the configuration.
   *
   * @return string[] List of portfolio (file) names. If no portfolios are
   * stored in the user's dropbox, an empty array is returned.
   */
  public static function readPortfolios() {
    //List of portfolio files is cached to improve performance
    if (empty($_SESSION['files'])) {
      //Read search paths from config, create array if only single
      //path is given.
      $search_paths = Configuration::get('search_path', array());
      if (!is_array($search_paths)) {
        $search_paths = array($search_paths);
      }
      $files = array();
      foreach($search_paths as $path) {
    	   $files += Configuration::getDropbox()->GetFiles($path, true);
      }
    	$_SESSION['files'] = $files;
    } else {
    	$files = $_SESSION['files'];
    }

    //Build list of portfolios
    $portfolios = array();
    foreach ($files as $filename => $file) {
    	if (substr($filename, -4) == ".por") {
    		$portfolios[] = new Portfolio(Configuration::getDropbox(), $filename);
    	}
    }
    return $portfolios;
  }


  /**
   * Create a new portfolio instance.
   *
   * @param object $dropbox Dropbox client instance.
   * @param string $file_path Dropbox path of the file.
   */
  public function __construct($dropbox, $file_path) {
    $this->dropbox = $dropbox;
    $this->path = $file_path;
    $this->name = basename($file_path, '.por');
  }

  /**
   * Get the portfolio name.
   *
   * @return string The portfolio name, which is the file name
   * without extension.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Get the dropbox file path.
   *
   * @return string The portfolio path inside dropbox.
   */
  public function getDropboxPath() {
    return '/' . $this->path;
  }

  /**
   * Get the display name. By default it is the name, followed by the
   * path in brackets.
   *
   * @param boolean $withPath Set to FALSE to omit the path from the output.
   * @return string The display name of the porfolio.
   */
  public function getDisplayName($withPath = true) {
    if ($withPath) {
      return $this->getName() . ' (' . $this->getDropboxPath() . ')';
    } else {
      return $this->getName();
    }
  }

  /**
   * Get the local working directory path for this portfolio.
   *
   * @return string Local path in working directory.
   */
  public function getLocalPath() {
    return Configuration::get('working_directory', '', true) . "/" . sha1($this->getDropboxPath());
  }

  /**
   * Check if portfolio data has been loaded into memory.
   *
   * @return boolean TRUE iff it has been loaded using the load() function.
   */
  public function isLoaded() {
    return isset($this->index);
  }

  /**
   * Load the portfolio into memory.
   */
  public function load() {
    $workdir = $this->getLocalPath();
    //Create working directory based on hash of portfolio name
    if (!is_dir($workdir)) {
      mkdir($workdir, 0777, true);
    }
    /*
       Download portfolio file from Dropbox, name by revision. A file is only
       downloaded once unless a new revision is created. This should increase
       performance. The Portfolio file is then extracted into the working
       directory for later access.
    */
    $file = $this->dropbox->GetMetadata($this->getDropboxPath());
    $portfolio_file = $workdir . "/" . $file->rev . ".zip";
    if (!file_exists($portfolio_file)) {
      $this->dropbox->DownloadFile($file, $portfolio_file);
      //Extract ZIP file to working directory.
      $zip = new \ZipArchive();
      $zip->open($portfolio_file);
      $zip->extractTo($workdir);
      $zip->close();
    }

    //Read portfolio index file
    $this->index = simplexml_load_file($workdir . "/index.xml");
  }

  /**
   * Read characters in this portfolio.
   *
   * @return array List of characters in this portfolio.
   */
  public function getCharacters() {
    //Collect characters from portfolio.
    foreach ($this->index->characters->character as $index => $character) {
      $statblocks = array();
      foreach ($character->xpath('statblocks/statblock') as $statblock) {
        $statblock_file = $this->getLocalPath() . "/" . $statblock['folder'] . "/" . $statblock['filename'];
        $statblocks[$statblock['format'] . ""] = $statblock_file;
      }

      $characters[] = new Character($character['name'], $character['summary'], $statblocks);
    }
    return $characters;
  }
}

?>
