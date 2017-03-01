<?php

namespace AppBundle\Model;

/**
 * Description of Version
 *
 */
class Version {

  /**
   *
   * @var string $name Version name (e.g. "4.6" or "latest"). This is what
   *                   readers see. Sometimes it's the same as name of the
   *                   branch, but not always.
   */
  public $name;
  
  /**
   *
   * @var string $branch The git branch that corresponds to this version
   *                     (e.g. "master" or "4.6"). Sometimes it's the same as
   *                     $name but not always.
   */
  public $branch;
  
  /**
   *
   * @var array $aliases An array (without keys) of strings which represent 
   *                     aliases to this version of the book. For each alias, 
   *                     we will create symbolic links so that a reader can also
   *                     access this version of the book at a URL with that
   *                     alias.
   */
  public $aliases;

  public function __construct($name, $branch = NULL, $aliases = array()) {
    $this->name = $name;
    $this->branch = $branch ?: $name;
    $this->aliases = $aliases;
  }
}
