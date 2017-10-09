<?php

namespace AppBundle\Utils;

class Paths {

  /**
   * @var string The root of the application
   */
  protected $kernelRoot;

  /**
   * @var string The cache directory for the current environment
   */
  protected $cacheDir;

  /**
   * @param string $kernelRoot
   * @param string $cacheDir
   */
  public function __construct($kernelRoot, $cacheDir) {
    $this->kernelRoot = $kernelRoot;
    $this->cacheDir = $cacheDir;
  }

  /**
   * @return string
   */
  public function getKernelRoot(): string {
    return $this->kernelRoot;
  }

  /**
   * @return string
   */
  public function getPublishPathRoot(): string {
    return $this->kernelRoot . '/../web';
  }

  /**
   * @return string
   */
  public function getRepoPathRoot(): string {
    return $this->kernelRoot . '/../var/repos';
  }

  /**
   * @return string
   */
  public function getCacheDir(): string {
    return $this->cacheDir;
  }

}
