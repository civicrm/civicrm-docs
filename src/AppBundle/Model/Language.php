<?php

namespace AppBundle\Model;

use AppBundle\Utils\LocaleTools;
use \AppBundle\Model\Version;

class Language {

  /**
   * @var string The URL to the git repository for this language
   */
  public $repo;

  /**
   * @var array An array (without keys) of Version objects
   */
  public $versions;

  /**
   * @var string The two letter language code for this language as
   *             specified by https://en.wikipedia.org/wiki/ISO_639-1
   */
  public $code;

  /**
   * Initialize a language with values in it.
   *
   * @param string $code two letter language code
   * @param array  $yaml language data from a book's yaml file
   */
  public function __construct($code, $yaml) {
    $this->code = $code;
    $this->repo = $yaml['repo'];
    $this->setupVersions($yaml);
  }

  /**
   * Set up $this->versions based on parsed yaml data in $yaml
   *
   * @param array $yaml Data passed into the language constructor.
   */
  private function setupVersions($yaml) {
    $latestBranch = isset($yaml['latest']) ? $yaml['latest'] : NULL;
    $stableBranch = isset($yaml['stable']) ? $yaml['stable'] : NULL;
    $history = isset($yaml['history']) ? $yaml['history'] : array();
    if ($latestBranch && $stableBranch) {
      if ($latestBranch == $stableBranch) {
        $this->addVersion('latest', $latestBranch,
            array('stable'));
      }
      else {
        $this->addVersion('latest', $latestBranch);
        $this->addVersion('stable', $stableBranch);
      }
    }
    elseif ($latestBranch) {
      $this->addVersion('latest', $latestBranch);
    }
    elseif ($stableBranch) {
      $this->addVersion('stable', $latestBranch);
    }
    foreach ($history as $item) {
      $this->addVersion($item);
    }
    if (count($this->versions) == 0) {
      $this->addVersion('latest', 'master');
    }
  }

  /**
   * Check this language for any problems in the way it's defined.
   *
   * If validation succeeds, this function returns nothing
   *
   * If validation fails, this function throws an exception.
   */
  public function validate() {
    $this->validateCode();
    $this->validateVersions();
  }

  private function validateCode() {
    if (!LocaleTools::codeIsValid($this->code)) {
      throw new Exception("Language code '{$this->code}' is not a valid "
      . "ISO 639-1 code.");
    }
  }

  /**
   * Adds a new version to this branch
   *
   * @param string $name (e.g. "latest", "master", "4.7", etc.)
   * @param string $branch (e.g "master", "4.7", etc)
   * @param array $aliases Array of strings containing names which can also be
   *                       used to reference the version.
   */
  public function addVersion($name, $branch = NULL, $aliases = array()) {
    $this->versions[] = new Version($name, $branch, $aliases);
  }

  /**
   * Check all versions within this language to make sure there are no
   * collisions between name/branch/aliases across different versions.
   *
   * If validation succeeds, this function returns nothing
   *
   * If validation fails, this function throws an exception.
   */
  private function validateVersions() {
    $descriptors = array();
    foreach ($this->versions as $version) {
      $descriptors = array_merge($descriptors, $version->allDescriptors());
    }
    $duplicateDescriptors
        = array_diff_assoc($descriptors, array_unique($descriptors));
    if ($duplicateDescriptors) {
      throw new Exception(
          "Duplicate descriptors '" . implode(", ", $duplicateDescriptors)
          . "' found for the versions defined within language '{$this->code}'");
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

  /**
   * Retrieves a version object defined for this language
   *
   * @param string $branch
   * @return \AppBundle\Model\Version The first version in $versions which
   *                                  matches the specified branch
   */
  public function getVersionByBranch($branch) {
    $chosen = NULL;
    foreach($this->versions as $version) {
      if($version->branch == $branch) {
        $chosen = $version;
        break;
      }
    }
    return $chosen;
  }

  /**
   * Retrieves a version object defined for this language, based on a descriptor
   * which can be either a branch, or a name, or an alias.
   *
   * @param string $descriptor
   * @return \AppBundle\Model\Version The first version in $versions which
   *                                  matches the specified descriptor
   */
  public function getVersionByDescriptor($descriptor) {
    $chosen = NULL;
    foreach($this->versions as $version) {
      if (in_array($descriptor, $version->allDescriptors())) {
        $chosen = $version;
        break;
      }
    }
    return $chosen;
  }

}
