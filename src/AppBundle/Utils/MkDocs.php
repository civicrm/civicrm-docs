<?php

namespace AppBundle\Utils;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Config\FileLocatorInterface as FileLocator;
use Symfony\Component\Process\Process;

class MkDocs {

  /**
   * @var Filesystem
   */
  private $fs;

  /**
   * @var FileLocator
   */
  private $fileLocator;

  /**
   * @var string The full filesystem path to the directory containing the
   *             markdown files
   */
  private $sourcePath;

  /**
   * @var string $destinationPath The full filesystem path to the directory
   *                              where we want the published content to go
   */
  private $destinationPath;

  /**
   * @var string The full filesystem path to the directory which stores
   *             different possible theme customizations. Within this directory,
   *             separate directories should exist, per theme, for the
   *             customizations, named with the same name as the theme.
   */
  private $themeCustomPathRoot;

  /**
   * @var string The full filesystem path to the directory
   */
  private $themeCustomPath;

  /**
   * @var array A associative array with config values to put in the 'extra'
   *            setting when building the book
   */
  private $extraConfig;

  /**
   * @var string The full filesystem location of the mkdocs.yml config file to
   *             use when building the book. This is the file as it's stored
   *             after adjustments we make to it.
   */
  private $configFile;

  /**
   * @param Filesystem $fs
   * @param FileLocator $fileLocator
   */
  public function __construct(Filesystem $fs, FileLocator $fileLocator) {
    $this->fs = $fs;
    $this->fileLocator = $fileLocator;
    $this->themeCustomPathRoot = $this->fileLocator->locate(
        '@AppBundle/Resources/theme-customizations');
  }

  /**
   * Reads the mkdocs.yml config file from the book source. Makes some
   * customizations to it, and the write the file into the directory where
   * we're going to publish the book. Why put it there? It doesn't need to be in
   * the publish destination, but it seems like as good a place as any. It just
   * needs to be stored somewhere so that mkdocs can read it while building the
   * book.
   */
  private function customizeConfig() {
    // Read config in
    $inFile = "{$this->sourcePath}/mkdocs.yml";
    $parser = new Parser();
    $config = $parser->parse(file_get_contents($inFile));

    // If we have a theme-cumstomization directory which matches the theme used
    // in the book, then use these theme customizations when building.
    $theme = $config['theme'];
    $this->themeCustomPath = "{$this->themeCustomPathRoot}/$theme";
    if ($this->fs->exists($this->themeCustomPath)) {
      $config['theme_dir'] = $this->themeCustomPath;
    }

    // Set extra config which was passed into build()
    foreach ($this->extraConfig as $key => $val) {
      $config['extra'][$key] = $val;
    }

    // Set up custom config for our Material theme extension
    if ($theme == 'material') {
      $config['site_favicon'] = 'assets/images/favicon.ico';
      $config['extra']['palette']['primary'] = 'indigo';
      $config['extra']['palette']['accent'] = 'green';
      if (!isset($config['extra']['edition'])) {
        $config['extra']['edition'] = "English / Latest";
      }
      if (!isset($config['extra']['book_home'])) {
        $config['extra']['book_home'] = "/";
      }
    }

    // Dump config out
    $dumper = new Dumper();
    $this->configFile = dirname($this->destinationPath) . "/"
        . basename($this->destinationPath) . "-mkdocs.yml";
    $this->fs->dumpFile($this->configFile, $dumper->dump($config, 4));
  }

  private function getOptions() {
    // discard existing build files -- build site from scratch
    $opts[] = "--clean";

    // abort the build if any errors occur
    $opts[] = "--strict";

    // use our customized config file
    $opts[] = "--config-file {$this->configFile}";

    // this is where the finished site should go
    $opts[] = "--site-dir {$this->destinationPath}";

    return implode(" ", $opts);
  }

  /**
   * Run MkDocs to build a book
   *
   * @param string $sourcePath The full filesystem path to the directory
   *                           containing the markdown files
   *
   * @param string $destinationPath The full filesystem path to the directory
   *                                where we want the published content to go
   *
   * @param array $extraConfig A associative array with config values to put in
   *                           the 'extra' setting when building the book
   */
  public function build($sourcePath, $destinationPath, $extraConfig = array()) {
    $this->sourcePath = $sourcePath;
    $this->destinationPath = $destinationPath;
    $this->extraConfig = $extraConfig;

    $this->customizeConfig();

    $buildCommand = "mkdocs build " . $this->getOptions();
    $mkdocs = new Process($buildCommand, $this->sourcePath);
    $mkdocs->run();
    if (!$mkdocs->isSuccessful()) {
      throw new \Exception("MkDocs was unable to build the book. "
          . "MkDocs command output: "
          . $mkdocs->getErrorOutput());
    }
  }

}
