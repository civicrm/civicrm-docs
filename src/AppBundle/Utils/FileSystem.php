<?php

namespace AppBundle\Utils;

use Symfony\Component\Filesystem\Filesystem as BaseFileSystem;

class FileSystem extends BaseFileSystem {

  /**
   * @param $source
   * @param $target
   */
  public function copyDir($source, $target) {
    $this->mkdir($target);

    $mode = \RecursiveIteratorIterator::SELF_FIRST;
    $directoryIterator = new \RecursiveDirectoryIterator($source);
    $iterator = new \RecursiveIteratorIterator($directoryIterator, $mode);

    foreach ($iterator as $item) {
      if ($item->isDir()) {
        $targetDir = $target.DIRECTORY_SEPARATOR. $iterator->getSubPathName();
        $this->mkdir($targetDir);
      } else {
        $targetFilename = $target.DIRECTORY_SEPARATOR. $iterator->getSubPathName();
        $this->copy($item, $targetFilename);
      }
    }
  }

  /**
   * @param string $dir
   */
  public function removeDir($dir) {

    if (!$this->exists($dir)) {
      return;
    }

    $childFirst = \RecursiveIteratorIterator::CHILD_FIRST;
    $noDots = \RecursiveDirectoryIterator::SKIP_DOTS;
    $directoryIterator = new \RecursiveDirectoryIterator($dir, $noDots);
    $iterator = new \RecursiveIteratorIterator($directoryIterator, $childFirst);

    foreach ($iterator as $item) {
      if ($item->isDir()) {
        $this->removeDir($item->getPathname());
      } else {
        $this->remove($item->getPathname());
      }
    }

    rmdir($dir);
  }
}
