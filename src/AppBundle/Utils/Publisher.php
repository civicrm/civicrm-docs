<?php

namespace AppBundle\Utils;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Publisher {

  public $bookConfigFile;
  public $messages;

  public function __construct(RequestStack $requestStack, $logger, $fs, $configDir, $reposRoot, $publishRoot) {
    $this->configDir = $configDir;
    $this->reposRoot = $reposRoot;
    $this->publishRoot = $publishRoot;
    $this->baseUrl = $requestStack->getCurrentRequest() ? $requestStack->getCurrentRequest()->getUriForPath('') : '/';
    $this->logger = $logger;
    $this->fs = $fs;
  }

  public function Publish($book, $lang, $branch) {
    $this->book = $book;
    $this->lang = $lang;
    $this->branch = $branch;
    //Attempt to load a configuration file for the book
    $bookConfigFile = $this->configDir . "/{$book}.yml";
    if (!$this->fs->exists($bookConfigFile)) {
      $this->addMessage('CRITICAL', "Could not find config file ({$bookConfigFile}) for book '{$book}'.", 'd');
      return;
    }

    $this->addMessage('INFO', "Loaded config file ({$bookConfigFile}) for '{$book}' documentation.");
    $this->addMessage('NOTICE', "Publishing '{$book}' documentation...");

    $yaml = new Parser();
    $bookConfig = $yaml->parse(file_get_contents("$bookConfigFile"));

    //Check that the requested publishing language exists in the book config.
    if (!isset($bookConfig['langs'][$lang])) {
      $this->addMessage('CRITICAL', "Language '{$lang}' is not defined in config file ({$bookConfigFile}).");
      return;
    }

    $bookRepo = "$this->reposRoot/{$book}/{$lang}";
    //See if the repo has already been cloned, and if not, clone it
    if (!$this->fs->exists($bookRepo . '/.git')) {
      $gitClone = new Process("git clone {$bookConfig['langs'][$lang]['repo']} {$bookRepo}");
      $gitClone->run();
      if ($gitClone->isSuccessful()) {
        $this->addMessage('INFO', $gitClone->getErrorOutput());
      }
      else {
        $this->addMessage('CRITICAL', $gitClone->getErrorOutput());
        return;
      }
    }
    else {
      $this->addMessage('INFO', "Repository exists at '{$bookRepo}'.");
    }

    //If we are on the not on the correct branch, attempt to check it out (first locally, then remotely).
    $gitCheckCurrentBranch = new Process('git rev-parse --abbrev-ref HEAD', $bookRepo);
    $gitCheckCurrentBranch->run();
    $currentBranch = trim($gitCheckCurrentBranch->getOutput());
    if ($currentBranch != $branch) {
      $this->addMessage('INFO', "Not currently on '{$branch}' branch (on '{$currentBranch}').");
      $gitLocalBranchExists = new Process("git show-ref --verify refs/heads/$branch", $bookRepo);
      $gitLocalBranchExists->run();
      if (!$gitLocalBranchExists->isSuccessful()) {
        $this->addMessage('INFO', "'{$branch}' branch does not exist locally.");
        $gitRemoteBranchExists = new Process("git show-ref --verify refs/remotes/origin/$branch", $bookRepo);
        $gitRemoteBranchExists->run();
        if (!$gitRemoteBranchExists->isSuccessful()) {
          $this->addMessage('CRITICAL', "'{$branch}' branch does not exist remotely or locally.");
          return;
        }
        else {
          $this->addMessage('INFO', "'{$branch}' branch exists remotely.");
        }
      }
      $this->addMessage('INFO', "Checking out '{$branch}' branch.");
      $gitCheckoutBranch = new Process("git checkout $branch", $bookRepo);
      $gitCheckoutBranch->run();
    }
    $this->addMessage('INFO', "On '{$branch}' branch.");

    $this->addMessage('INFO', "Running 'git pull' to update '{$branch}' branch.");
    $gitPull = new Process('git pull', $bookRepo);
    $gitPull->run();

    //Override some settings from the yml before publishing
    try {
      $mkdocsConfig = $yaml->parse(file_get_contents("{$bookRepo}/mkdocs.yml"));
    }
    catch (\Exception $e) {
      $this->addMessage('CRITICAL', "Error processing yaml: {$e->getMessage()}");
      return;
    }

    //@TODO create a CiviCRM theme so that we can uncomment this line
    //$mkdocsConfig['theme']="bootstrap";
    //$mkdocsConfig['theme_dir']=$k->getRootDir().'/mkdocsthemes/civicrm';

    $mkDocsBuildFileDir = $this->reposRoot . '/buildfiles';
    if (!$this->fs->exists($mkDocsBuildFileDir)) {
      $this->fs->mkdir($mkDocsBuildFileDir);
    }
    $buildConfigFile = $mkDocsBuildFileDir . "/{$book}.{$lang}.{$branch}.yml";

    $dumper = new Dumper();
    file_put_contents($buildConfigFile, $dumper->dump($mkdocsConfig, 2));

    $publishDir = $this->publishRoot . "/{$book}/{$lang}/{$branch}";
    $buildCommand = "mkdocs build -c -f {$buildConfigFile} -d {$publishDir}";
    $this->addMessage('NOTICE', "Running '{$buildCommand}'");

    $mkdocs = new Process($buildCommand, $bookRepo);
    $mkdocsErrors = FALSE;
    $mkdocs->run();
    //echo nl2br($mkdocs->getOutput()); (don't think anything gets outputed by this command)
    $mkdocsLogMessages = explode("\n", trim($mkdocs->getErrorOutput()));
    $this->addMessage('INFO', "mkdocs output: '{$mkdocs->getErrorOutput()}'");

    // var_dump($mkdocsLogMessages);
    foreach ($mkdocsLogMessages as $mkdocsLogMessage) {
      //var_dump($mkdocsLogMessage);
      if (substr($mkdocsLogMessage, 0, 4) != 'INFO') {
        $mkdocsErrors = TRUE;
      }
    }

    $bookUrl = "/{$book}/{$lang}/{$branch}";
    if ($mkdocsErrors) {
      $this->addMessage('CRITICAL', "Book published with errors (see above for details) at <a href='{$this->baseUrl}$bookUrl'>{$this->baseUrl}$bookUrl</a>.");
    }
    else {
      $this->addMessage('NOTICE', "Book published successfully at <a href='{$this->baseUrl}$bookUrl'>{$this->baseUrl}$bookUrl</a>.");
    }

    //check and update symlinks so that latest and stable point to the right places
    $symlinks = array('latest', 'stable');

    $langDir = realpath("$publishDir/..");

    //@TODO break this out into its own function that can also be called at a certain URL.
    foreach ($symlinks as $symlink) {
      if ($this->fs->exists("$langDir/$symlink")) {
        $this->fs->remove("$langDir/$symlink");
      }
      if (isset($bookConfig['langs'][$lang][$symlink])) {
        $source = $bookConfig['langs'][$lang][$symlink];
        if ($this->fs->exists("$langDir/$source")) {
          $this->fs->symlink("$langDir/$source", "$langDir/$symlink");
        }
        else {
          $this->addMessage('CRITICAL', "'$source' is defined as the '{$symlink}' version of this documentation but is not yet published. <a href='{$this->baseUrl}/admin/publish/{$book}/{$lang}/{$source}'>Publish now</a>");
        }
      }
      else {
        $this->addMessage('WARNING', "'{$symlink}' is not defined in this documentation's config file.");
      }
    }
    return 1;
  }

  protected function addMessage($label, $content) {
    $this->messages[] = array('label' => $label, 'content' => $content);
    $this->logger->addRecord($this->logger->toMonologLevel($label), $content);
  }

  public function getMessages() {
    return $this->messages;
  }

  public function clearMessages() {
    $this->messages = array();
  }

  /**
   * @return string
   */
  public function getBaseUrl() {
    return $this->baseUrl;
  }

  /**
   * @param string $baseUrl
   */
  public function setBaseUrl($baseUrl) {
    $this->baseUrl = $baseUrl;
  }

}
