<?php

namespace AppBundle\Utils;

use AppBundle\Model\Book;
use AppBundle\Model\Language;
use AppBundle\Model\Version;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Filesystem\Filesystem;
use AppBundle\Model\Library;

class Publisher {

  /**
   * @var Filesystem
   */
  protected $fs;

  /**
   * @var Library The library of all books
   */
  protected $library;

  /**
   * @var Logger
   */
  protected $logger;

  /**
   * @var Paths
   */
  protected $paths;

  /**
   * @var MkDocs
   */
  private $mkDocs;

  /**
   * @var GitTools
   */
  protected $git;

  /**
   * @var string[] A temporary array of messages specific to one publish() call
   */
  private $publishingMessages = [];

  /**
   * @var string
   */
  protected $publishURLBase;

  /**
   *
   * @param RequestStack $requestStack
   * @param LoggerInterface $logger
   * @param Filesystem $fs
   * @param Library $library
   * @param MkDocs $mkDocs
   * @param GitTools $git
   * @param Paths $paths
   */
  public function __construct(
    RequestStack $requestStack,
    Logger $logger,
    Filesystem $fs,
    Library $library,
    MkDocs $mkDocs,
    GitTools $git,
    Paths $paths
  ) {
    $this->logger = $logger;
    $this->fs = $fs;
    $this->library = $library;
    $this->git = $git;
    $this->mkDocs = $mkDocs;
    $this->paths = $paths;

    if ($requestStack->getCurrentRequest()) {
      $this->publishURLBase
        = $requestStack->getCurrentRequest()->getUriForPath('');
    } else {
      $this->publishURLBase = '/';
    }
  }

  /**
   * Publish a book, or multiple books, based on a flexible identifier
   *
   * @param string $identifier
   *   A string describing the book, or books. For example "user/en/latest" will
   *   publish one version of one language of one book, or "user" will publish
   *   all languages and all versions within the book "user".
   */
  public function publish($identifier = "") {
    $this->publishingMessages = [];
    $this->addMessage('NOTICE', "PUBLISHING $identifier");

    $parts = Library::parseIdentifier($identifier);
    $bookSlug = $parts['bookSlug'];
    $languageCode = $parts['languageCode'];
    $versionDescriptor = $parts['versionDescriptor'];

    if ($versionDescriptor) {
      $book = $this->getBook($bookSlug);
      $language = $this->getLanguage($book, $languageCode);
      $this->publishVersion($book, $language, $versionDescriptor);
    } elseif ($languageCode) {
      $book = $this->getBook($bookSlug);
      $this->publishLanguage($book, $languageCode);
    } elseif ($bookSlug) {
      $this->publishBook($bookSlug);
    } else {
      $this->publishLibrary();
    }
  }

  /**
   * @param string $label
   *   Should be 'NOTICE', 'INFO', 'WARNING', or 'CRITICAL'
   *
   * @param string $content
   */
  public function addMessage($label, $content) {
    $this->publishingMessages[] = ['label' => $label, 'content' => $content];
    $this->logger->addRecord($this->logger->toMonologLevel($label), $content);
  }

  /**
   * @return array
   */
  public function getMessages() {
    return $this->publishingMessages;
  }

  /**
   * @return string
   *   All messages as lines in one big string
   */
  public function getMessagesInPlainText() {
    $text = '';
    foreach ($this->getMessages() as $message) {
      $text = "{$text}{$message['label']} - {$message['content']}\n";
    }

    return $text;
  }

  /**
   * Publishes all the things!
   *
   * All the versions of all the languages of all the books
   */
  private function publishLibrary() {
    foreach ($this->library->books as $book) {
      $this->publishBook($book->slug);
    }
  }

  /**
   * Publish a book
   *
   * @param string $bookSlug The short name of the book
   */
  private function publishBook($bookSlug) {
    $book = $this->getBook($bookSlug);
    if ($book) {
      foreach ($book->languages as $language) {
        $this->publishLanguage($book, $language->code);
      }
    }
  }

  /**
   * Publish a language
   *
   * @param Book $book
   * @param string $languageCode An ISO 639-1 two letter language code
   */
  private function publishLanguage($book, $languageCode) {
    $language = $this->getLanguage($book, $languageCode);
    if ($language) {
      foreach ($language->versions as $version) {
        $this->publishVersion($book, $language, $version->branch);
      }
    }
  }

  /**
   * Publish a specific version of a book based on certain identifiers
   *
   * @param Book $book
   * @param Language $language
   * @param string $versionDescriptor
   *   Can be the name of the version, the name of the git branch, or a name
   *   of an alias defined for the version
   */
  private function publishVersion($book, $language, $versionDescriptor) {
    $version = $this->getVersion($book, $language, $versionDescriptor);

    // info text
    $this->addMessage('INFO', "Using book: " . $book->name);
    $aliasText = "";
    if ($version->aliases) {
      $aliasList = implode('", "', $version->aliases);
      $aliasText = sprintf("with aliases: %s", $aliasList);
    }
    $this->addMessage('INFO', "Using language: " . $language->getEnglishName());
    $msg = sprintf("Using branch: '%s' %s", $version->branch, $aliasText);
    $this->addMessage('INFO', $msg);

    $fullIdentifier = "{$book->slug}/{$language->code}/" . "{$version->branch}";
    $publishURLFull = "{$this->publishURLBase}/{$fullIdentifier}";
    $repoURL = $language->repo;

    $publishPath = $this->paths->getPublishPathRoot() . "/{$fullIdentifier}";
    $this->fs->mkdir($publishPath);

    $repoPath = $this->paths->getRepoPathRoot() . "/{$book->slug}/" . "{$language->code}";
    $this->fs->mkdir($repoPath);

    $this->initializeRepo($repoPath, $repoURL);

    $this->git->checkout($repoPath, $version->branch);
    $msg = sprintf("Checked out branch '%s'", $version->branch);
    $this->addMessage('INFO', $msg);

    $msg = sprintf("Running 'git pull' to update '%s' branch.", $repoPath);
    $this->addMessage('INFO', $msg);
    $this->git->pull($repoPath);

    $this->build($book, $language, $version, $repoPath, $publishPath);
    $format = "Book published successfully at <a href='%s'>%s</a>.";
    $msg = sprintf($format, $publishURLFull, $publishURLFull);
    $this->addMessage('INFO', $msg);

    $this->setupSymlinks($book, $language, $version, $publishPath);
    $this->setupRedirects($repoPath, $publishPath);
  }

  /**
   * Find the requested book within the library
   *
   * @param string $bookSlug
   *
   * @return Book
   */
  private function getBook($bookSlug) {
    $book = $this->library->getBookBySlug($bookSlug);
    if (!$book) {
      throw new \Exception("Unable to locate book: '{$bookSlug}'.");
    }

    $book->validate();

    return $book;
  }

  /**
   * Find the requested language within the book
   *
   * @param Book $book
   * @param string $languageCode
   *
   * @return Language
   */
  private function getLanguage($book, $languageCode) {
    $language = $book->getLanguageByCode($languageCode);

    if (!$language) {
      $format = "Language '%s' not defined for book '%s'";
      $msg = sprintf($format, $languageCode, $book->name);
      throw new \Exception($msg);
    }

    $language->validate();

    return $language;
  }

  /**
   * Find the requested version within the language
   *
   * @param Book $book
   * @param Language $language
   * @param string $versionDescriptor
   *
   * @return Version
   */
  private function getVersion($book, $language, $versionDescriptor) {
    $version = $language->getVersionByDescriptor($versionDescriptor);
    if (!$version) {
      $msg = sprintf(
        "Version '%s' not defined within language '%s' for book '%s'",
        $versionDescriptor,
        $language->getEnglishName(),
        $book->name
      );
      throw new \Exception($msg);
    }

    $version->validate();

    return $version;
  }

  /**
   * Ensure that we have the repository locally
   *
   * @param string $repoPath
   * @param string $repoURL
   */
  private function initializeRepo($repoPath, $repoURL) {
    $repoExists = (bool)$this->fs->exists($repoPath . '/.git');

    if ($repoExists) {
      return;
    }

    $this->git->clone($repoURL, $repoPath);
  }

  /**
   * Use MkDocs to build a static site for the book
   *
   * @param Book $book
   * @param Language $language
   * @param Version $version
   * @param string $repoPath
   * @param string $publishPath
   */
  private function build(
    Book $book,
    Language $language,
    Version $version,
    string $repoPath,
    string $publishPath
  ) {
    $extraConfig['edition'] = "{$language->nativeName()} / {$version->name}";
    $extraConfig['book_home'] = "/{$book->slug}";
    try {
      $this->mkDocs->build($repoPath, $publishPath, $extraConfig);
    } catch (\Exception $e) {
      $msg = sprintf(
        "Build errors encountered. Book not published. Build error: '%s'",
        $e->getMessage()
      );
      throw new \Exception($msg);
    }
  }

  /**
   * Check and update symlinks so that latest and stable point to the right
   * places
   *
   * @param Book $book
   * @param Language $language
   * @param Version $version
   * @param string $publishPath
   */
  private function setupSymlinks(
    Book $book,
    Language $language,
    Version $version,
    string $publishPath
  ) {
    $publishPathRoot = $this->paths->getPublishPathRoot();
    $path = sprintf('%s/%s/%s', $publishPathRoot, $book->slug, $language->code);

    // Remove any existing symlinks to the branch
    $cmd = "rm $(find -L '$path' -xtype l -samefile '$publishPath')";
    $purgeExisting = new Process($cmd);
    $purgeExisting->run();

    // Add new symlinks
    foreach ($version->aliases as $alias) {
      $this->fs->symlink($publishPath, "$path/$alias");
      $url = "{$this->publishURLBase}/{$book->slug}/{$language->code}/$alias";
      $a = "<a href='$url'>$url</a>";
      $this->addMessage('INFO', "Adding alias '{$alias}' at $a.");
    }
  }

  /**
   * If we have an internal redirects file in the repo, then copy it to the
   * path where the book is published. This app will look for redirects in this
   * file when it can't find static HTML files to serve.
   *
   * @param string $repoPath
   * @param string $publishPath
   */
  private function setupRedirects(string $repoPath, string $publishPath) {
    $redirectsFile = $repoPath . '/redirects/internal.txt';
    if (file_exists($redirectsFile)) {
      copy($redirectsFile, $publishPath . '/redirects.txt');
    }
  }


}
