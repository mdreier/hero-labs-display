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
 * Class representing character data.
 */
class Character {

  /**
   * @var Character name.
   */
  private $name;

  /**
   * @var Character summary.
   */
  private $summary;

  /**
   * @var Associative array with statblock types as the
   * keys and statblock file paths as the corrsponding values.
   */
  private $statblockFiles;

  /**
   * Create a new character instance.
   *
   * @param string $name Character name.
   * @param string $summary Character summary.
   * @param array $statblockFiles Associative array with statblock types as the
   * keys and statblock file paths as the corrsponding values.
   */
  public function __construct($name, $summary, $statblockFiles = array()) {
    $this->name = $name;
    $this->summary = $summary;
    $this->statblockFiles = $statblockFiles;
  }

  /**
   * Get the character name.
   *
   * @return Character name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Get the character summary.
   *
   * @return Character summary.
   */
  public function getSummary() {
    return $this->summary;
  }

  /**
   * Get a statblock for this character.
   *
   * @param string $format The target format. May be either html or text.
   * @return string Statblock of this character in the desired format.
   */
  public function getStatblock($format) {
    if (isset($this->statblockFiles[$format])) {
      $statblock_file = $this->statblockFiles[$format];
      $statblock_content = "";
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
      return $statblock_content;
    } else {
      return '';
    }
  }
}
