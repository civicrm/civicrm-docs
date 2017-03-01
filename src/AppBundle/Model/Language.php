<?php

namespace AppBundle\Model;

class Language {
  
  /**
   * @var string $LOCALES_DIR the path to the symfony directory containing 
   *                          locale information in the form of json files. 
   *                          TODO: is there a better way to determine this 
   *                          path?
   */
  const LOCALES_DIR = '../vendor/symfony/symfony/src/Symfony/Component/Intl/Resources/data/locales';

  /**
   *
   * @var string $repo The URL to the git repository for this language
   */
  public $repo;

  /**
   *
   * @var array $versions An array (without keys) of Version objects
   */
  public $versions;

  /**
   *
   * @var string $code The two letter language code for this language as
   *                   specified by https://en.wikipedia.org/wiki/ISO_639-1
   */
  public $code;

  /**
   *
   * @param string $code two letter language code
   * @param array  $yaml language data from a book's yaml file
   */
  public function __construct($code, $yaml) {
    $this->code = $code;
    $this->repo = $yaml['repo'];
    $latestBranch = isset($yaml['latest']) ? $yaml['latest'] : NULL;
    $stableBranch = isset($yaml['stable']) ? $yaml['stable'] : NULL;
    if ($latestBranch) {
      $this->versions[] = new Version('latest', $latestBranch);
    }
    if ($stableBranch != $latestBranch) {
      $this->versions[] = new Version('stable', $stableBranch);
    }
    $history = isset($yaml['history']) ? $yaml['history'] : array();
    foreach ($history as $item) {
      $this->versions[] = new Version($item);
    }
  }
  
  /**
   * Returns the name of a language, in another language. For example, if 
   * $languageCode = 'en' and $localeCode = 'es' this function would answer the
   * question "what word do Spanish-speaking people use to refer to English?"
   * 
   * @param type $languageCode The language we're asking about
   * @param type $localeCode   The language in which we want our answer
   * 
   * @return string 
   */
  private static function getLaguageNameInLocale($languageCode, $localeCode) {
    $localesFiles = self::LOCALES_DIR . "/$localeCode.json"; 
    $locales = json_decode(file_get_contents($localesFiles), TRUE);
    return $locales['Names'][$languageCode];
  }
  
  /**
   * The name of the language, in English (e.g. "Spanish")
   * 
   * @return string
   */
  public function englishName() {
    return $this::getLaguageNameInLocale($this->code, 'en');
  }

  /**
   * The native name of the language, in the language (e.g. "Español")
   * 
   * @return string
   */  
  public function nativeName() {
    return $this::getLaguageNameInLocale($this->code, $this->code);
  }
  
  public function descriptiveName() {
    if($this->code == 'en') {
      return $this->englishName();
    }
    else {
      return $this->nativeName() . " (" . $this->englishName() . ")";
    }
  }
  
  /**
   * True when this language contains multiple distinct versions.
   * 
   * @return bool
   */
  public function isMultiVersion() {
    return count($this->versions) > 1;
  }

}
