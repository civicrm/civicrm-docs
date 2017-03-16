<?php

namespace AppBundle\Model;

use Symfony\Component\Finder\Finder;

class Library {

  /**
   *
   * @var array An array (without keys) of Book objects to represent all the
   *            books in the system.
   */
  public $books;

  /**
   * Build a new Library based on a directory of book conf files.
   *
   * @param strig $configDir
   */
  public function __construct($configDir) {
    $finder = new Finder();
    $files = $finder->in($configDir)->name("*.yml");
    foreach ($files as $file) {
      $book = new Book($file);
      $this->books[] = $book;
    }
    $this->sortBooks();
  }

  /**
   * Compares 2 books, side by side, for the purpose of sorting an array of
   * books with uasort()
   *
   * @param Book $a
   * @param Book $b
   * @return int - Negative when $a comes before $b.
   *               Zero when $a and $b have identical sort orders.
   *               Positive when $b comes before $a.
   */
  public static function compareBooksBySortOrder($a, $b) {
    $weightDiff = $a->weight - $b->weight;
    return ($weightDiff != 0) ? $weightDiff : strnatcmp($a->name, $b->name);
  }

  /**
   * Modifies $this->books by sorting the array of books correctly
   */
  private function sortBooks() {
    if (isset($this->books)) {
      uasort($this->books, ['self', 'compareBooksBySortOrder']);
    }
  }

  /**
   * @return array Book data in 2-dimensional array format.
   *               Used for the docs:list command.
   *
   *   Each item in the array contains keys:
   *     - book: string (ex: 'dev')
   *     - lang: string (ex: 'en')
   *     - repo: string (ex: 'https://example.com/dev.git')
   *     - branch: string (ex: 'master')
   */
  public function booksAsTable() {
    $rows = array();
    foreach ($this->books as $book) {
      foreach ($book->languages as $language) {
        foreach ($language->versions as $version) {
          $key = "$book->slug/$language->code/$version->branch";
          $row = array(
            'book' => $book->name,
            'language' => $language->englishName(),
            'repo' => $language->repo,
            'branch' => $version->branch,
          );
          $rows[$key] = $row;
        }
      }
    }
    return $rows;
  }

  /**
   * Selects one of the many books within the library
   *
   * @param string $slug The short name describing the book
   *
   * @return Book
   */
  public function getBookBySlug($slug) {
    $chosen = NULL;
    foreach ($this->books as $book) {
      if ($book->slug == $slug) {
        $chosen = $book;
        break;
      }
    }
    return $chosen;
  }

  /**
   * Gives an array of book objects which match a given category
   *
   * @param string $category
   *
   * @return array of Book objects
   */
  public function getBooksByCategory($category) {
    $books = array();
    foreach ($this->books as $book) {
      if ($book->category == $category) {
        $books[] = $book;
      }
    }
    return $books;
  }

  /**
   * See which books/languages are using a given repository.
   *
   * @param string $repoURL
   *
   * @return array of strings to identify each occurence of a book/language
   *               which matches the specified repository. Example return:
   *               ["mybook/en", "mybook/es"]
   *               Note that it's rare for a repository to map to multiple
   *               identifiers. In most cases the return will be an array with
   *               a single element.
   *               Note also that the return identifier only has the book slug
   *               and the language code, not the branch name.
   */
  public function getIdentifiersByRepo($repoURL) {
    $identifiers = array();
    foreach ($this->books as $book) {
      foreach ($book->languages as $language) {
        if ($language->repo == $repoURL) {
          $identifiers[] = "$book->slug/$language->code";
        }
      }
    }
    return $identifiers;
  }

  /**
   * Parses an identifier into components we can use to identify a book
   *
   * @param string $identifier (e.g. "user/en/master", "user/en", "user", "")
   *
   * @return array The following keys/values are present:
   *               "bookSlug" => (string/NULL) slug used to identify a book
   *               "languageCode" => (string/NULL) two letter language code
   *               "versionDescriptor" => (string/NULL)
   */
  public static function parseIdentifier($identifier) {
    $identifier = preg_replace("#/+#", "/", trim($identifier));
    $identifier = trim($identifier, "/");
    $parts = explode("/", $identifier);
    $result['bookSlug'] = ($parts[0]) ? $parts[0] : NULL;
    $result['languageCode'] = isset($parts[1]) ? $parts[1] : NULL;
    $result['versionDescriptor'] = isset($parts[2]) ? $parts[2] : NULL;
    return $result;
  }

}
