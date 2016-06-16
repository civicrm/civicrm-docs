<?php
namespace AppBundle;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

class BookLoader
{

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
    public function __construct($configDir)
    {
        $this->configDir = $configDir;
    }

    /**
     * @return array
     */
    public function find()
    {
        if ($this->cache === null) {
            $finder = new Finder();
            $yaml = new Parser();
            $books = array();
            foreach ($finder->in($this->configDir)
                       ->name("*.yml") as $file) {
                $books[basename($file, '.yml')] = $yaml->parse(file_get_contents("$file"));
            }
            $this->cache = $books;
        }
        return $this->cache;
    }

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
                    $row =  array(
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
                $branches = array_merge($branches,$langSpec[$key]);
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