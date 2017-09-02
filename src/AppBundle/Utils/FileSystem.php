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

    $selfFirst = \RecursiveIteratorIterator::SELF_FIRST;

    $directoryIterator = new \RecursiveDirectoryIterator($source);
    $iterator = new \RecursiveIteratorIterator($directoryIterator, $selfFirst);

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
}
