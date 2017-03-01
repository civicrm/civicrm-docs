<?php

namespace AppBundle\Model;

use Symfony\Component\Yaml\Parser;

class Book {

  /**
   * @var string $shortName The reference identifier for this book, taken from
   *                        the name of the book's .yml file.
   */
  public $slug;
  
  /**
   * @var string $fullName The title of the book, taken from the "name" 
   *                       attribute in the book's .yml file.
   */
  public $name;
  
  /**
   * @var string $description Description of the book taken from the 
   *                          "description" attribute in the book's .yml file
   *                          and displayed on the home page. 
   */
  public $description;
  
  /**
   * @var array $languages An array (without keys of Language objects to 
   *                       represent the available languages for the book.
   */
  public $languages;
  
  /**
   *
   * @var int $weight Used to sort books
   */
  public $weight;
  
  /**
   * Creates a book based on a yaml conf file
   * 
   * @param string $confFile The path to the yaml configuration file which 
   *                         defines the attributes of the book.
   */
  public function __construct($confFile) {
    $parser = new Parser();
    $yaml = $parser->parse(file_get_contents($confFile));
    $this->slug        = basename($confFile, '.yml');
    $this->name        = $yaml['name'];
    $this->weight      = isset($yaml['weight'])      ? $yaml['weight']      : 0;
    $this->description = isset($yaml['description']) ? $yaml['description'] : "";
    foreach ($yaml['langs'] as $code => $languageData) {
      $this->languages[] = new Language($code, $languageData);
    }
  }

  /**
   * @return bool True when the book contains multiple languages.
   */
  public function isMultiLanguage(){
    return count($this->languages) > 1;
  }
}
