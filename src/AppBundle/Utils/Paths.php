<?php

namespace AppBundle\Utils;

class Paths {

  /**
   * @var string Directory where all published books go
   */
  protected $publishPathRoot;

  /**
   * @var string Directory containing all the git repositories.
   */
  protected $repoPathRoot;

  /**
   * @param string $publishPathRoot
   * @param string $repoPathRoot
   */
  public function __construct($publishPathRoot, $repoPathRoot) {
    $this->publishPathRoot = $publishPathRoot;
    $this->repoPathRoot = $repoPathRoot;
  }

  /**
   * @return string
   */
  public function getPublishPathRoot(): string {
    return $this->publishPathRoot;
  }

  /**
   * @return string
   */
  public function getRepoPathRoot(): string {
    return $this->repoPathRoot;
  }

}
