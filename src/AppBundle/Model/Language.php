<?php

namespace AppBundle\Model;

use AppBundle\Utils\LocaleTools;

class Language {

  /**
   * @var string
   *   The URL to the git repository for this language
   */
  public $repo;

  /**
   * @var Version[]
   *   An array (without keys) of Version objects
   */
  public $versions;

  /**
   * @var string
   *   The two letter language code for this language as specified by
   *   https://en.wikipedia.org/wiki/ISO_639-1
   */
  public $code;

  /**
   *
   * @var string[]
   *   Email addresses for people who would like to receive a notification any
   *   time a version within this language is published
   */
  public $watchers = array();

  /**
   * Initialize a language with values in it.
   *
   * @param string $code
   *   Two letter language code
   *
   * @param array $yaml
   *   Language data from a book's yaml file
   */
  public function __construct($code, $yaml) {
    $this->code = $code;
    $this->repo = $yaml['repo'];
    $this->setupVersions($yaml);
    if (isset($yaml['watchers'])) {
      foreach ($yaml['watchers'] as $watcher) {
        $this->watchers[] = $watcher;
      }
    }
  }

  /**
   * Set up $this->versions based on parsed yaml data in $yaml
   *
   * @param array $yaml
   *   Data passed into the language constructor.
   */
  private function setupVersions($yaml) {
    // Add versions defined in yaml
    $versions = $yaml['versions'] ?? [];
    foreach ($versions as $slug => $version) {
      $name = $version['name'] ?? 'Latest';
      $path = $version['path'] ?? $slug;
      $branch = $version['branch'] ?? 'master';
      $redirects = $version['redirects'] ?? [];
      $this->versions[] = new Version($slug, $name, $path, $branch, $redirects);
    }

    // If no versions were defined, then add one version (with default values)
    if (count($this->versions) == 0) {
      $this->versions[] = new Version();
    }
  }

  /**
   * Check this language for any problems in the way it's defined.
   *
   * If validation succeeds, this function returns nothing
   *
   * @throws \Exception
   *   If validation fails
   */
  public function validate() {
    $this->validateCode();
    $this->validateAllVersionDescriptors();
  }

  /**
   * Check the code definied for this language to see if it's valid
   *
   * If validation succeeds, this function returns nothing
   *
   * @throws \Exception
   *   If validation fails
   */
  private function validateCode() {
    if (!LocaleTools::codeIsValid($this->code)) {
      throw new \Exception("Language code '{$this->code}' is not a valid "
      . "ISO 639-1 code.");
    }
  }

  /**
   * Check all versions within this language to make sure there are no
   * collisions between name/branch/aliases across different versions.
   * Note that this function does not perform validation at the version level,
   * only at the language level. It finds problems when multiple versions are
   * defined in ways that made a language invalid. It won't find problems when
   * one version is defined in an invalid way. For that, use Version::validate
   *
   * If validation succeeds, this function returns nothing
   *
   * @throws \Exception
   *   If validation fails
   */
  private function validateAllVersionDescriptors() {
    $descriptors = array();
    foreach ($this->versions as $version) {
      $descriptors = array_merge($descriptors, $version->allDescriptors());
    }
    $duplicateDescriptors
      = array_diff_assoc($descriptors, array_unique($descriptors));
    if ($duplicateDescriptors) {
      throw new \Exception(
          "Duplicate descriptors '" . implode(", ", $duplicateDescriptors)
          . "' found for the versions defined within language '{$this->code}'");
    }
  }

  /**
   * The name of the language, in English
   *
   * @return string
   *   e.g. "Spanish"
   */
  public function getEnglishName() {
    return LocaleTools::getLanguageNameInLocale($this->code, 'en');
  }

  /**
   * The native name of the language, in the language
   *
   * @return string
   *   e.g. "español"
   */
  public function nativeName() {
    return LocaleTools::getLanguageNameInLocale($this->code, $this->code);
  }

  /**
   * A string which refers to this language by both its native name and its
   * English name
   *
   * @return string
   *   e.g. "español (Spanish)"
   */
  public function descriptiveName() {
    if ($this->code == 'en') {
      return $this->getEnglishName();
    }
    else {
      return $this->nativeName() . " (" . $this->getEnglishName() . ")";
    }
  }

  /**
   * True when this language contains multiple distinct versions
   *
   * @return bool
   */
  public function isMultiVersion() {
    return count($this->versions) > 1;
  }

  /**
   * Count the number of versions defined within this language
   *
   * @return integer
   */
  public function countVersions() {
    return count($this->versions);
  }

  /**
   * Retrieves a version object defined for this language
   *
   * @param string $branch
   *
   * @return \AppBundle\Model\Version
   *   The first version in $versions which matches the specified branch
   */
  public function getVersionByBranch($branch) {
    $chosen = NULL;
    foreach ($this->versions as $version) {
      if ($version->branch == $branch) {
        $chosen = $version;
        break;
      }
    }
    return $chosen;
  }

  /**
   * @return \AppBundle\Model\Version
   */
  public function getDefaultVersion() {
    return $this->versions[0];
  }

  /**
   * Retrieves a version object defined for this language, based on a descriptor
   * which can be either a branch, or a name, or an alias.
   *
   * @param string $descriptor
   *
   * @return \AppBundle\Model\Version
   *   The first version in $versions which matches the specified descriptor
   */
  public function getVersionByDescriptor($descriptor) {
    $chosen = NULL;
    foreach ($this->versions as $version) {
      if (in_array($descriptor, $version->allDescriptors())) {
        $chosen = $version;
        break;
      }
    }
    return $chosen;
  }

}
