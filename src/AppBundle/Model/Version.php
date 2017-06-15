<?php

namespace AppBundle\Model;

use AppBundle\Utils\StringTools;

class Version {

  /**
   * @var string
   *   Version name (e.g. "4.6" or "latest"). This is what readers see.
   *   Sometimes it's the same as name of the branch, but not always. It can
   *   contain pretty much whatever characters you want.
   */
  public $name;

  /**
   * @var string
   *   The git branch that corresponds to this version (e.g. "master" or "4.6").
   *   Sometimes it's the same as $name but not always. It can be any string
   *   that actually maps to a real git branch, including strings with forward
   *   slashes.
   */
  public $branch;

  /**
   * @var array
   *   An array (without keys) of strings which represent aliases to this
   *   version of the book. For each alias, we will create symbolic links so
   *   that a reader can also access this version of the book at a URL with that
   *   alias.
   */
  public $aliases;

  /**
   * Defines a new "version" of a book, with aliases.
   *
   * A version has one and only
   * one "branch", meaning the git branch used for the version. A version also
   * can have many "aliases", which are other descriptors like "stable" that we
   * can also use to refer to this version. When the book gets published, its
   * files live in a directory named after the branch. Then we create symbolic
   * links to this directory for each of the aliases. So if the branch is
   * "master" and we have an alias called "stable", then the book will be
   * accessible at "master" via the directory and at "stable" via the symlink.
   *
   * If the constructor receives different $name and $branch values, it will
   * automatically add an alias for $name.
   *
   * @param string $name
   *   e.g. "latest", "master", "4.7"
   *
   * @param string $branch
   *   e.g "master", "4.7"
   *
   * @param array $aliases
   *   Array of strings containing names which can also be used to reference
   *   this version.
   */
  public function __construct($name, $branch = NULL, $aliases = array()) {
    $this->name = $name;
    $this->branch = $branch ?: $name;
    $this->setupAliases($aliases);
  }

  /**
   * @param $aliases array|string
   *   e.g. "latest"
   */
  private function setupAliases($aliases) {
    // wrap $aliases in array, if necessary
    if (!is_array($aliases)) {
      $aliases = array($aliases);
    }

    // Add an alias for $name if necessary
    if ($this->name != $this->branch && !isset($aliases[$this->name])) {
      $aliases[] = $this->name;
    }

    // Remove alias for $branch if it exists
    unset($aliases[$this->branch]);

    // Make sure each alias is URL-safe
    foreach ($aliases as &$alias) {
      $alias = StringTools::urlSafe($alias);
    }

    $this->aliases = array_unique($aliases);
  }

  /**
   * Gives an array of all unique strings that can be used to describe this
   * version, including branch, name, and any aliases.
   *
   * @return array
   *   Array of strings (without keys)
   */
  public function allDescriptors() {
    $result = $this->aliases;
    $result[] = $this->name;
    $result[] = $this->branch;
    return array_unique($result);
  }


  /**
   * Check this version for any problems in the way it's defined.
   *
   * If validation succeeds, this function returns nothing
   *
   * @throws \Exception
   *   If validation fails
   */
  public function validate() {
    if (preg_match("#/#", $this->branch)) {
      throw new Exception("Branch name can not contain a forward slash.");
    }
  }

}
