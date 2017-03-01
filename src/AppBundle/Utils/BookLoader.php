<?php

namespace AppBundle\Utils;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

class BookLoader {

  /**
   * @var string
   */
  private $configDir;

  /**
   * @var array
   */
  private $cache;

  /**
   * Books constructor.
   * @param string $configDir
   */
  public function __construct($configDir) {
    $this->configDir = $configDir;
  }

  /**
   * Compares 2 books, side by side, for the purpose of sorting an array of
   * books with uasort()
   * @param array $bookA
   * @param array $bookB
   * @return int
   */
//  private function compareBooksBySortOrder($bookA, $bookB) {
//    $aWeight = isset($bookA['weight']) ? $bookA['weight'] : 0;
//    $bWeight = isset($bookB['weight']) ? $bookB['weight'] : 0;
//    if ($aWeight == $bWeight) {
//      return strnatcmp($bookA['name'], $bookB['name']);
//    }
//    return $aWeight - $bWeight;
//  }

  /**
   * Modifies the internal book cache by sorting the array of books correctly
   */
//  private function cacheSort() {
//    if (isset($this->cache)) {
//      uasort($this->cache, [$this, 'compareBooksBySortOrder']);
//    }
//  }

  /**
   * Modifies the book passed in by inserting new elements into the book's
   * array structure that provide additional information about the book, such
   * as the number of languages, etc.
   *
   * @param array &$book an array of properties which represent a book
   */
//  private function addStatsToBook(&$book) {
//    $this->addToBookDistinctVersions($book);
//    $this->addToBookIsMultiVersion($book);
//    $this->addToBookIsMultiLang($book);
//    $this->addToBookStableLangs($book);
//    $this->addtoBookIsMultiStableLang($book);
//  }

  /**
   * Adds 'distinct_versions' element to a book which lists all the distinct
   * versions. If 'stable' and 'latest' both point to 'master', then it will
   * list only 'stable' in the distinct versions.
   *
   * @param array &$book an array of properties which represent a book
   *
   */
//  private function addToBookDistinctVersions(&$book) {
//    foreach ($book['langs'] as &$lang) {
//      $lang['distinct_versions']['latest'] = $lang['latest'];
//      if (isset($lang['stable']) && $lang['stable'] != $lang['latest']) {
//        $lang['distinct_versions']['stable'] = $lang['stable'];
//      }
//      if (isset($lang['history'])) {
//        foreach ($lang['history'] as $version) {
//          $key = (string) $version;
//          $lang['distinct_versions'][$key] = $version;
//        }
//      }
//    }
//  }

  /**
   * Adds 'is_multi_version' to all language elements of a book to say
   * whether the language has multiple versions that are different from one
   * another
   *
   * @param array &$book an array of properties which represent a book
   */
//  private function addToBookIsMultiVersion(&$book) {
//    foreach ($book['langs'] as &$lang) {
//      $lang['is_multi_version'] = count($lang['distinct_versions']) > 1 ? 1 : 0;
//    }
//  }

  /**
   * Adds 'is_multi_lang' element to a book to say whether the book has
   * multiple languages.
   *
   * @param array &$book an array of properties which represent a book
   */
//  private function addToBookIsMultiLang(&$book) {
//    $book['is_multi_lang'] = count($book['langs']) > 1 ? 1 : 0;
//  }

  /**
   * Adds 'stable_langs' element to a book -- an array of all the languages
   * which have a stable version defined.
   *
   * @param array &$book an array of properties which represent a book
   */
//  private function addToBookStableLangs(&$book) {
//    foreach ($book['langs'] as $lang => $language) {
//      if (isset($language['stable'])) {
//        $book['stable_langs'][$lang] = $language;
//      }
//    }
//  }

  /**
   * Adds 'is_multi_stable_lang' element to a book to say whether the book has
   * multiple languages which have stable versions defined.
   *
   * @param array &$book an array of properties which represent a book
   */
//  private function addToBookIsMultiStableLang(&$book) {
//    if (!isset($book['stable_langs'])) {
//      $this->addToBookStableLangs($book);
//    }
//    $book['is_multi_stable_lang'] = count($book['stable_langs']) > 1 ? 1 : 0;
//  }

  /**
   * Find all the books
   * Fills the private $cache variable with an array of books, as they are
   * defined in the yaml config files
   *
   * @return array all books
   */
//  public function find() {
//    if ($this->cache === NULL) {
//      $finder = new Finder();
//      $yaml = new Parser();
//      $books = array();
//      foreach ($finder->in($this->configDir)
//          ->name("*.yml") as $file) {
//        $books[basename($file, '.yml')] = $yaml->parse(file_get_contents("$file"));
//      }
//      foreach ($books as &$book) {
//        $this->addStatsToBook($book);
//      }
//      $this->cache = $books;
//      $this->cacheSort();
//    }
//    return $this->cache;
//  }

  /**
   * Get the list of books as a flat list of (book,lang,repo,branch) pairs.
   *
   * @return array
   *   Each item in the array contains keys:
   *     - book: string (ex: 'dev')
   *     - lang: string (ex: 'en')
   *     - repo: string (ex: 'https://example.com/dev.git')
   *     - branch: string (ex: 'master')
   */
  public function findAsList() {
    $rows = array();
    foreach ($this->find() as $bookName => $book) {
      foreach ($book['langs'] as $lang => $langSpec) {
        foreach ($this->getBranches($book, $lang) as $branch) {
          $key = "$bookName/$lang/$branch";
          $row = array(
            'book' => $bookName,
            'lang' => $lang,
            'repo' => $langSpec['repo'],
            'branch' => $branch,
          );
          $rows[$key] = $row;
        }
      }
    }
    return $rows;
  }

  /**
   * Get a list of all branches declared for this book.
   *
   * @param array $book
   * @param string $lang
   * @return array
   *   List of branch names which apply to this book.
   */
  public function getBranches($book, $lang) {
    $langSpec = $book['langs'][$lang];
    $branches = array();
    foreach (array('latest', 'stable', 'history') as $key) {
      if (!isset($langSpec[$key])) {
        continue;
      }
      elseif (is_array($langSpec[$key])) {
        $branches = array_merge($branches, $langSpec[$key]);
      }
      else {
        $branches[] = $langSpec[$key];
      }
    }
    $branches = array_unique($branches);
    sort($branches);
    return $branches;
  }

}
