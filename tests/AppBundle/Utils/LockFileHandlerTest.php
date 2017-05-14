<?php

namespace AppBundle\Tests\Utils;

use AppBundle\Utils\LockFileHandler;

class LockFileHandlerTest extends \PHPUnit_Framework_TestCase
{
  /**
   * @var string
   */
  protected $lockDir;

  /**
   * @test
   */
  public function canCreateLockFile() {
    $handler = $this->getHandler();
    $handler->create('foo');

    $this->assertTrue($handler->exists('foo'));
  }

  /**
   * @test
   */
  public function creationAndDeletionWillWork() {
    $handler = $this->getHandler();
    $handler->create('foo');
    $handler->release('foo');

    $this->assertFalse($handler->exists('foo'));
  }

  /**
   * @test
   */
  public function nonExistingLockWillReturnFalse() {
    $handler = $this->getHandler();

    $this->assertFalse($handler->exists('foo'));
  }

  /**
   * @test
   */
  public function releaseWithNonExistingLockWillDoNothing() {
    $this->getHandler()->release('foo');
  }

  /**
   * @test
   */
  public function createWithExistingLockWillDoNothing() {
    $handler = $this->getHandler();
    $handler->create('foo');
    $handler->create('foo');

    $this->assertTrue($handler->exists('foo'));
  }

  /**
   * @test
   */
  public function expiryWillWork() {
    $handler = $this->getHandler(0);
    $handler->create('foo');
    sleep(1);

    $this->assertFalse($handler->exists('foo'));
  }

  /**
   * @test
   */
  public function recreatingALockWillResetExpiry() {
    $handler = $this->getHandler(1);
    $handler->create('foo');
    sleep(2);
    $handler->create('foo'); // first lock would have expired

    $this->assertTrue($handler->exists('foo'));
  }

  /**
   * @param int $lockTTL
   *   How long the lock will remain active
   *
   * @return LockFileHandler
   */
  private function getHandler($lockTTL = 5) {
    return new LockFileHandler($this->lockDir, $lockTTL);
  }

  /**
   * Create the lock directory in temp directory
   */
  protected function setUp() {
    $this->lockDir = sys_get_temp_dir() . '/civicrm-docs-test-locks';
  }

  /**
   * Removes all locks in the temp lock directory
   */
  protected function tearDown() {
    array_map('unlink', glob($this->lockDir . '/locks/*'));
  }

}
