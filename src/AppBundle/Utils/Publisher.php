<?php

namespace AppBundle\Utils;

use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Filesystem\Filesystem;

class Publisher {

  /**
   * @var \AppBundle\Model\Book The book to be published
   */
  public $book;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  public $fs;

  /**
   * @var \AppBundle\Model\Language The language to be published
   */
  public $language;

  /**
   * @var \AppBundle\Model\Library The library of all books
   */
  public $library;

  /**
   * @var Logger
   */
  public $logger;

  /**
   * @var array Messages with key as a string to represent message type and
   *            value as a string with the message content
   */
  public $messages;

  /**
   * @var string The domain name of the site (e.g. "https://docs.civicrm.org")
   */
  public $publishURLBase;

  /**
   *
   * @var string The full URL of the published book
   *             (e.g. "https://docs.civicrm.org/user/en/latest")
   */
  public $publishURLFull;

  /**
   * @var string Filesystem path to the directory where all published books go
   */
  public $publishPathRoot;

  /**
   * @var string The full filesystem path to the directory where the book
   *             is to be published
   */
  public $publishPath;

  /**
   * @var string  The filesystem path to the directory containing all the
   *              git repositories.
   */
  public $repoPathRoot;

  /**
   * @var string The filesystem path to the repository to use
   */
  public $repoPath;

  /**
   * @var \AppBundle\Model\Version The version to be published
   */
  public $version;

  /**
   * @var string the URL of the repository (e.g. https://github.com/foo/bar
   */
  public $repoURL;

  /**
   *
   * @param RequestStack $requestStack
   * @param Monolog\Logger $logger
   * @param Filesystem $fs
   * @param Library $library
   * @param string $reposPathRoot
   * @param string $publishPathRoot
   */
  public function __construct(
      $requestStack,
      $logger,
      $fs,
      $library,
      $reposPathRoot,
      $publishPathRoot) {
    $this->logger = $logger;
    $this->fs = $fs;
    $this->library = $library;
    $this->repoPathRoot = realpath($reposPathRoot);
    $this->publishPathRoot = realpath($publishPathRoot);
    if ($requestStack->getCurrentRequest()) {
      $this->publishURLBase
          = $requestStack->getCurrentRequest()->getUriForPath('');
    }
    else {
      $this->publishURLBase = '/';
    }
  }

  /**
   * Determines the proper URLs and paths for things.
   *
   * Here, when we say "URL", we mean a web-accessible location and when we say
   * "path" we mean a local filesystem path on the server running this app.
   *
   * @return boolean TRUE if success
   */
  private function initializeLocations() {
    $this->publishURLFull = "{$this->publishURLBase}/{$this->book->slug}/"
        . "{$this->language->code}/{$this->version->branch}";
    $this->publishPath = "{$this->publishPathRoot}/{$this->book->slug}/"
        . "{$this->language->code}/{$this->version->branch}";
    $this->repoURL = $this->language->repo;
    $this->repoPath = $this->repoPathRoot . "/{$this->book->slug}/"
        . "{$this->language->code}";
    return TRUE;
  }

  /**
   * Find the requested book within the library
   *
   * @param string $bookSlug
   *
   * @return boolean TRUE if success
   */
  private function initializeBook($bookSlug) {
    $this->book = $this->library->getBookBySlug($bookSlug);
    if (!$this->book) {
      $this->addMessage('CRITICAL', "Unable to locate book: '{$bookSlug}'.");
      return FALSE;
    }
    else {
      $this->addMessage('INFO', "Using book: {$this->book->name}.");
    }
    return TRUE;
  }

  /**
   * Find the requested language within the book
   *
   * @param string $languageCode
   *
   * @return boolean TRUE if success
   */
  private function initializeLanguage($languageCode) {
    $this->language = $this->book->getLanguageByCode($languageCode);
    if ($this->language) {
      $this->addMessage('INFO',
          "Using language: {$this->language->englishName()}");
    }
    else {
      $this->addMessage('CRITICAL',
          "Language '{$languageCode}' is not defined for book "
          . "'{$this->book->name}'.");
      return FALSE;
    }
    try {
      $this->language->validate();
    }
    catch (\Exception $e) {
      $this->addMessage('CRITICAL',
          "The book settings for language '{$this->language->code}' could not "
          . "be validated. Validation error is: " . $e->getMessage());
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Find the requested version within the language
   *
   * @param string $branchName
   *
   * @return boolean TRUE if success
   */
  private function initializeVersion($branchName) {
    $this->version = $this->language->getVersionByBranch($branchName);
    if (!$this->version) {
      $this->addMessage('CRITICAL',
          "Branch '{$branchName}' is not defined for "
          . "language '{$this->language->englishName()}' within "
          . "book '{$this->book->name}'.");
      return FALSE;
    }
    else {
      if ($this->version->aliases) {
        $aliasText = " with aliases: "
            . implode(', ', array_map(function ($s) {return "'".$s."'";},
                $this->version->aliases));
      }
      else {
        $aliasText = "";
      }
      $this->addMessage('INFO',
          "Using branch: '{$this->version->branch}'$aliasText.");
    }
    return TRUE;
  }

  /**
   * Ensure that we have the repository locally
   *
   * @return boolean TRUE if success
   */
  private function initializeRepo() {
    $repoExists = (bool) $this->fs->exists($this->repoPath . '/.git');
    if ($repoExists) {
      $this->addMessage('INFO', "Repository exists at '{$this->repoPath}'.");
    }
    else {
      $gitClone = new Process("git clone {$this->repoURL} {$this->repoPath}");
      $gitClone->run();
      if ($gitClone->isSuccessful()) {
        $this->addMessage('INFO', "Git clone: " . $gitClone->getErrorOutput());
      }
      else {
        $this->addMessage('CRITICAL',
            "Unable to run 'git clone'. Output: " . $gitClone->getErrorOutput());
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * If we are on the not on the correct branch, attempt to check it out
   * (first locally, then remotely).
   *
   * @return boolean TRUE if success
   */
  private function gitCheckout(){
    $gitCheckCurrentBranch = new Process(
        'git rev-parse --abbrev-ref HEAD', $this->repoPath);
    $gitCheckCurrentBranch->run();
    $currentBranch = trim($gitCheckCurrentBranch->getOutput());
    if ($currentBranch != $this->version->branch) {
      $this->addMessage(
          'INFO', "Not currently on '{$this->version->branch}' branch "
          . "(on '{$currentBranch}').");
      $gitLocalBranchExists = new Process(
          "git show-ref --verify refs/heads/{$this->version->branch}",
          $this->repoPath);
      $gitLocalBranchExists->run();
      if (!$gitLocalBranchExists->isSuccessful()) {
        $this->addMessage('INFO',
            "'{$this->version->branch}' branch does not exist locally.");
        $gitRemoteBranchExists = new Process(
            "git show-ref --verify refs/remotes/origin/{$this->version->branch}",
            $this->repoPath);
        $gitRemoteBranchExists->run();
        if (!$gitRemoteBranchExists->isSuccessful()) {
          $this->addMessage('CRITICAL',
              "'{$this->version->branch}' branch does not exist "
              . "remotely or locally.");
          return FALSE;
        }
        else {
          $this->addMessage('INFO',
              "'{$this->version->branch}' branch exists remotely.");
        }
      }
      $this->addMessage('INFO',
          "Checking out '{$this->version->branch}' branch.");
      $gitCheckoutBranch = new Process(
          "git checkout {$this->version->branch}", $this->repoPath);
      $gitCheckoutBranch->run();
    }
    $this->addMessage('INFO', "On '{$this->version->branch}' branch.");
    return TRUE;
  }

  /**
   * @return boolean TRUE if success
   */
  private function gitPull() {
    $this->addMessage('INFO',
        "Running 'git pull' to update '{$this->version->branch}' branch.");
    $gitPull = new Process('git pull', $this->repoPath);
    $gitPull->run();
    if (!$gitPull->isSuccessful()) {
      $this->addMessage('CRITICAL',
          "Unable to run 'git pull'. Output: " . $gitPull->getErrorOutput());
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Use MkDocs to build a static site for the book
   *
   * @return boolean TRUE if success
   */
  private function build() {
    $buildCommand = "mkdocs build "
        . "--clean --strict --site-dir {$this->publishPath}";
    $this->addMessage('INFO', "Running '{$buildCommand}'");
    $mkdocs = new Process($buildCommand, $this->repoPath);
    $mkdocs->run();
    $mkdocsLogMessages = explode("\n", trim($mkdocs->getErrorOutput()));
    $this->addMessage('INFO', "mkdocs output: '{$mkdocs->getErrorOutput()}'");
    $mkdocsErrors = FALSE;
    foreach ($mkdocsLogMessages as $mkdocsLogMessage) {
      if (substr($mkdocsLogMessage, 0, 4) != 'INFO') {
        $mkdocsErrors = TRUE;
      }
    }
    if ($mkdocsErrors) {
      $this->addMessage('CRITICAL',
          "MkDocs build errors encountered. Book not published.");
      return FALSE;
    }
    $this->addMessage('INFO', "Book published successfully at "
        . "<a href='{$this->publishURLFull}'>{$this->publishURLFull}</a>.");
    return TRUE;
  }

  /**
   * Check and update symlinks so that latest and stable point to the right
   * places
   *
   * @return boolean TRUE if success
   */
  private function setupSymlinks() {
    $path = "{$this->publishPathRoot}/{$this->book->slug}/{$this->language->code}";

    // Remove any existing symlinks to the branch
    $purgeExisting = new Process(
        "rm $(find -L '$path' -xtype l -samefile '$this->publishPath')");
    $purgeExisting->run();

    // Add new symlinks
    foreach($this->version->aliases as $alias) {
      $this->fs->symlink($this->publishPath, "$path/$alias");
      $url = "{$this->publishURLBase}/{$this->book->slug}/"
        . "{$this->language->code}/$alias";
      $a = "<a href='$url'>$url</a>";
      $this->addMessage('INFO', "Adding alias '{$alias}' at $a.");
    }

    return TRUE;
  }

  /**
   * Publish a book based on certain identifiers
   *
   * @param string $bookSlug
   * @param string $languageCode
   * @param string $branchName
   *
   * @return bool TRUE if book was published, FALSE if there were errors
   */
  public function publish($bookSlug, $languageCode, $branchName) {
    $success =
      $this->initializeBook($bookSlug) &&
      $this->initializeLanguage($languageCode) &&
      $this->initializeVersion($branchName) &&
      $this->initializeLocations() &&
      $this->initializeRepo() &&
      $this->gitCheckout() &&
      $this->gitPull() &&
      $this->build() &&
      $this->setupSymlinks();
    return $success;
  }

  /**
   * @param string $label should be 'INFO', 'WARNING', or 'CRITICAL'
   * @param string $content
   */
  protected function addMessage($label, $content) {
    $this->messages[] = array('label' => $label, 'content' => $content);
    $this->logger->addRecord($this->logger->toMonologLevel($label), $content);
  }

  /**
   * Deletes all stored messages
   */
  public function clearMessages() {
    $this->messages = array();
  }

}