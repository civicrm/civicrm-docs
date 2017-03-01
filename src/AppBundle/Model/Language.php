<?php

namespace AppBundle\Model;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Utils\LocaleTools;

class Language extends ContainerAwareCommand {

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
   * Initialize a language with values in it. This function is separate from the
   * constructor function only so that we have have a "language" service which
   * gets the $localesDir passed in when the service is retrieved. 
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
   * The name of the language, in English (e.g. "Spanish")
   * 
   * @return string
   */
  public function englishName() {
    return LocaleTools::getLaguageNameInLocale($this->code, 'en');
  }

  /**
   * The native name of the language, in the language (e.g. "Español")
   * 
   * @return string
   */  
  public function nativeName() {
    return LocaleTools::getLaguageNameInLocale($this->code, $this->code);
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
