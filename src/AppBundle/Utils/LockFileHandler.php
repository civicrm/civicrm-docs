<?php

namespace AppBundle\Utils;

class LockFileHandler {

  /**
   * @var string
   *   Where the lock files will be stored.
   */
  protected $lockDirectory;

  /**
   * @var int
   *   How many seconds a lock remains active
   */
  protected $lockLifetimeInSeconds;

  /**
   * @param $cacheDir
   *  The cache directory for the current environment
   * @param $lockLifetimeInSeconds
   *  How many seconds a lock remains active
   */
  public function __construct($cacheDir, $lockLifetimeInSeconds)
  {
    $lockDirectory = $cacheDir . '/locks';

    if (!is_dir($lockDirectory)) {
      mkdir($lockDirectory, 0777, true);
    }

    $this->lockDirectory = $lockDirectory;
    $this->lockLifetimeInSeconds = (int) $lockLifetimeInSeconds;
  }

  /**
   * Creates a lock file or updates an existing one
   *
   * @param string $name
   */
  public function create($name) {
    $name = trim($name);

    if (!$name) {
      throw new \Exception('Lock file name cannot be empty');
    }

    $lockFilename = $this->getFullFilename($name);
    touch($lockFilename);
  }

  /**
   * Check if a lock file exists. Removes it if it is expired.
   *
   * @param string $name
   * @return bool
   */
  public function exists($name) {
    $lockFilename = $this->getFullFilename($name);
    $exists = file_exists($lockFilename);

    if ($exists && $this->isExpired($lockFilename)) {
      $this->release($name);
      $exists = FALSE;
    }

    return $exists;
  }

  /**
   * Removes a lock file. Non-existing locks are ignored
   *
   * @param $name
   */
  public function release($name) {
    $lockFilename = $this->getFullFilename($name);

    if (!file_exists($lockFilename)) {
      return;
    }

    unlink($lockFilename);
  }

  /**
   * @param $name
   *
   * @return string
   */
  protected function getFullFilename($name)
  {
    $lockFilename = sprintf('%s/%s', $this->lockDirectory, $name);

    return $lockFilename;
  }

  /**
   * Checks if a lock file was last modified in the last LOCK_EXPIRY seconds
   *
   * @param $lockFilename
   *  The full filename
   *
   * @return bool
   */
  protected function isExpired($lockFilename)
  {
    $lastModified = filemtime($lockFilename);
    $expiresAt = $lastModified + $this->lockLifetimeInSeconds;
    $now = time();

    return $now > $expiresAt;
  }

}
