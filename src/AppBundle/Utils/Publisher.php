<?php

namespace AppBundle\Utils;

use AppBundle\Model\Book;
use AppBundle\Model\Language;
use AppBundle\Model\Version;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use AppBundle\Model\Library;

class Publisher {

  /**
   * @var FileSystem
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
   * @param Logger $logger
   * @param Filesystem $fs
   * @param Library $library
   * @param MkDocs $mkDocs
   * @param GitTools $git
   * @param Paths $paths
   */
  public function __construct(
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

    $parts = $this->library::parseIdentifier($identifier);
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
        $this->publishVersion($book, $language, $version->slug);
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
   *   of a redirect defined for the version
   *
   * @throws \Exception
   */
  private function publishVersion($book, $language, $versionDescriptor) {
    $version = $this->getVersion($book, $language, $versionDescriptor);
    $branch = $version->branch;
    $langCode = $language->code;
    $slug = $book->slug;
    $tmpPrefix = $slug . '_' . $langCode . '_' . time();
    $tmpRepoDir = $this->makeTmpDir('repo_' . $tmpPrefix);
    $tmpPublishDir = $this->makeTmpDir('publish_' . $tmpPrefix);
    $repoRoot = $this->paths->getRepoPathRoot();
    $masterRepoDir = sprintf('%s/%s/%s', $repoRoot, $slug, $langCode);
    $webPath = sprintf('%s/%s/%s', $slug, $langCode, $version->path);
    $webRoot = $this->paths->getPublishPathRoot() . "/" . $webPath;

    try {
      $this->showVersionInfo($book, $language, $version);

      // setup temp repo to build from
      $this->setupLocalRepo($masterRepoDir, $language->repo);
      $this->fs->copyDir($masterRepoDir, $tmpRepoDir);
      $this->git->checkout($tmpRepoDir, $branch);
      $this->git->pull($tmpRepoDir);
      $this->addMessage('INFO', sprintf("Checked out branch '%s'", $branch));

      $this->build($book, $language, $version, $tmpRepoDir, $tmpPublishDir);

      // remove existing and copy new
      $this->fs->removeDir($webRoot);
      $this->fs->copyDir($tmpPublishDir, $webRoot);
      $this->setupRedirects($tmpRepoDir, $webRoot);

      $msg = sprintf("<a href='/%s'>Book published!</a>.", $webPath);
      $this->addMessage('INFO', $msg);

    } catch (\Exception $e) {
      $this->cleanup($tmpRepoDir, $tmpPublishDir);
      throw $e;
    }

    $this->cleanup($tmpRepoDir, $tmpPublishDir);
  }

  /**
   * Find the requested book within the library
   *
   * @param string $bookSlug
   *
   * @return Book
   *
   * @throws \Exception
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
   *
   * @throws \Exception
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
   *
   * @throws \Exception
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
   * Use MkDocs to build a static site for the book
   *
   * @param Book $book
   * @param Language $language
   * @param Version $version
   * @param string $repoPath
   * @param string $publishPath
   *
   * @throws \Exception
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

  /**
   * @param $book
   * @param $language
   * @param $version
   */
  private function showVersionInfo(
    Book $book,
    Language $language,
    Version $version
  ) {
    $this->addMessage('INFO', "Using book: " . $book->name);

    $aliasText = "";
    if ($version->redirects) {
      $aliasList = implode('", "', $version->redirects);
      $aliasText = sprintf("with aliases: %s", $aliasList);
    }

    $this->addMessage('INFO', "Using language: " . $language->getEnglishName());
    $msg = sprintf("Using branch: '%s' %s", $version->branch, $aliasText);
    $this->addMessage('INFO', $msg);
  }

  /**
   * @param $masterRepoDir
   * @param $bookRepo
   */
  private function setupLocalRepo($masterRepoDir, $bookRepo) {
    if (!$this->fs->exists($masterRepoDir)) {
      $this->fs->mkdir($masterRepoDir);
      $this->git->clone($bookRepo, $masterRepoDir);
    }
    $this->git->fetch($masterRepoDir);
  }

  /**
   * @param $prefix
   *
   * @return string
   */
  private function makeTmpDir($prefix) {
    $cacheDir = $this->paths->getCacheDir() . '/publishing';
    $this->fs->mkdir($cacheDir);
    $tmpRepoDir = $cacheDir . '/' . $prefix;
    $this->fs->mkdir($tmpRepoDir);

    return $tmpRepoDir;
  }

  /**
   * @param $tmpRepoDir
   * @param $tmpPublishDir
   */
  private function cleanup($tmpRepoDir, $tmpPublishDir) {
    $this->fs->removeDir($tmpRepoDir);
    $this->fs->removeDir($tmpPublishDir);
    $mkdocsSiteFile = $tmpPublishDir . '-mkdocs.yml';
    $this->fs->remove($mkdocsSiteFile);
  }

}
