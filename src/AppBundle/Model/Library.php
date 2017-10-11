<?php

namespace AppBundle\Model;

use Symfony\Component\Finder\Finder;

class Library {

  /**
   * @var Book[]
   *   An array (without keys) of Book objects to represent all the books in
   *   the system.
   */
  public $books;

  /**
   * Build a new Library based on a directory of book conf files.
   *
   * @param string $configDir
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
   *
   * @param Book $b
   *
   * @return int
   *   Negative when $a comes before $b.Zero when $a and $b have identical sort
   *   orders. Positive when $b comes before $a.
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
   * @return array
   *   Book data in 2-dimensional array format. Used for the docs:list command.
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
            'language' => $language->getEnglishName(),
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
   * @param string $slug
   *   The short name describing the book
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
   * @return array
   *   Array of Book objects (without keys)
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
   * @return array
   *   Array of strings to identify each occurrence of a book/language
   *   which matches the specified repository.
   *
   *   e.g. ["mybook/en", "mybook/es"]
   *
   *   Note that it's rare for a repository to map to multiple identifiers.
   *   In most cases the return will be an array with a single element.
   *
   *   Note also that the return identifier only has the book slug and the
   *   language code, not the branch name.
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
   * This is the reverse of assembleIdentifier.
   *
   * See LibraryTest::identifierProvider() for examples
   *
   * @param string $identifier
   *   examples: "user/en/master", "user/en", "user", ""
   *
   * @return array
   *   example:
   *     [
   *       'bookSlug' => 'dev',
   *       'languageCode' => 'en',
   *       'versionDescriptor' => 'latest',
   *       'editionIdentifier' => 'dev/en/latest',
   *       'path' => 'category/foo/my-page',
   *       'fragment' => 'some-section',
   *     ]
   *   The array will aways have the keys shown above, but values will be NULL
   *   if those components do not exist in the given identifier
 */
  public static function parseIdentifier($identifier) {
    // Remove junk chars from both ends
    $identifier = trim($identifier, "/# \t\n\r\0\x0B");

    // Ensure there are no repeated occurrences of "/" or "#"
    $identifier = preg_replace("_(/|#)+_", "$1", $identifier);

    // Split into 2 parts based on the first "#" character
    $hashSplit = explode('#', $identifier, 2);
    $fragment = $hashSplit[1] ?? NULL;
    $preFragment = $hashSplit[0] ?? NULL;

    // Take everything before "#" and split it into 4 parts
    $slashSplit = explode("/", $preFragment, 4);

    // Assign parts
    $result['bookSlug'] = $slashSplit[0] ?? NULL;
    $result['languageCode'] = $slashSplit[1] ?? NULL;
    $result['versionDescriptor'] = $slashSplit[2] ?? NULL;
    $editionParts = [
      $result['bookSlug'],
      $result['languageCode'],
      $result['versionDescriptor']
    ];
    $result['editionIdentifier'] = in_array(FALSE, $editionParts)
      ? NULL
      : implode('/', $editionParts);
    $result['path'] = $slashSplit[3] ?? NULL;
    $result['fragment'] = $fragment;

    return $result;
  }

  /**
   * Take several pieces of a docs URL, and combined them into a full identifier
   *
   * This is the reverse of parseIdentifier.
   *
   * If 'editionIdentifier' is present, it will trump the values in 'bookSlug',
   * 'languageCode', and 'versionDescriptor'.
   *
   * @param array $pieces
   *   e.g.
   *     [
   *       'bookSlug' => 'dev',
   *       'languageCode' => 'en',
   *       'versionDescriptor' => 'latest',
   *       'editionIdentifier' => 'dev/en/latest',
   *       'path' => 'category/foo/my-page',
   *       'fragment' => 'some-section',
   *     ]
   *
   * @return string
   */
  public static function assembleIdentifier($pieces) {
    $result = '';
    if ($pieces['editionIdentifier']) {
      $result .= $pieces['editionIdentifier'];
    }
    else {
      if ($pieces['bookSlug']) {
        $result .= $pieces['bookSlug'];
      }
      else {
        return $result;
      }
      if ($pieces['languageCode']) {
        $result .= '/' . $pieces['languageCode'];
      }
      else {
        return $result;
      }
      if ($pieces['versionDescriptor']) {
        $result .= '/' . $pieces['versionDescriptor'];
      }
      else {
        return $result;
      }
    }
    if ($pieces['path']) {
      $result .= '/' . $pieces['path'];
    }
    else {
      return $result;
    }
    if ($pieces['fragment']) {
      $result .= '/#' . $pieces['fragment'];
    }
    return $result;
  }

  /**
   * @param string $identifier
   *   examples: "user/en/master", "user/en", "user", ""
   *
   * @return array
   */
  public function getObjectsByIdentifier($identifier) {
    $parts = self::parseIdentifier($identifier);
    $book = $this->getBookBySlug($parts['bookSlug']);
    $language = $book->getLanguageByCode($parts['languageCode']);
    $version = $language->getVersionByDescriptor($parts['versionDescriptor']);
    return [
      'book' => $book,
      'language' => $language,
      'version' => $version,
    ];
  }

}
