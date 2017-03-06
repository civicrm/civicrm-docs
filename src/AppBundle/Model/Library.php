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

}
