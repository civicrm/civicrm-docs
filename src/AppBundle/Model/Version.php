<?php

namespace AppBundle\Model;

use AppBundle\Utils\StringTools;

class Version {

  /**
   * @var string
   *   The machine readable name of this version. For example, in a book with
   *   multiple versions, the slug should be defined to correspond to the
   *   version numbers of the product (i.e. "4.7", or "4.6"). The slug can be
   *   "master" if a book is only using one version.
   */
  public $slug;

  /**
   * @var string
   *   This is the URL component for the published book. If it's not defined
   *   in the book's yaml file, then we use $slug as the path component.
   */
  public $path;

  /**
   * @var string
   *   A human-readable name of this version (e.g. "4.7 / Current" or "latest").
   *   This is what readers see.
   *   Sometimes it's the same as name of the branch, but not always. It can
   *   contain pretty much whatever characters you want.
   */
  public $name;

  /**
   * @var string
   *   The git branch that corresponds to this version (e.g. "master" or "4.6").
   *   Sometimes it's the same as $name but not always. It can be any string
   *   that actually maps to a real git branch, including strings with forward
   *   slashes.
   */
  public $branch;

  /**
   * @var string[]
   *   An array (without keys) of strings which represent redirects to this
   *   version of the book.
   *   (e.g. ["latest", "current"])
   *   If a reader requests a page with one of these redirects in place of the
   *   $path, then the app will redirect them to the proper page.
   */
  public $redirects;

  /**
   * Defines a new "version" of a book, with aliases.
   * A version has one and only
   * one "branch", meaning the git branch used for the version. A version also
   * can have many "aliases", which are other descriptors like "stable" that we
   * can also use to refer to this version. When the book gets published, its
   * files live in a directory named after the branch. Then we create symbolic
   * links to this directory for each of the aliases. So if the branch is
   * "master" and we have an alias called "stable", then the book will be
   * accessible at "master" via the directory and at "stable" via the symlink.
   * If the constructor receives different $name and $branch values, it will
   * automatically add an alias for $name.
   *
   * @param string $slug
   *   @see Version::slug
   * @param string $name
   *   @see Version::name
   * @param null $path
   *   @see Version::path
   * @param string $branch
   *   @see Version::branch
   * @param array $redirects
   *   @see Version::redirects
   */
  public function __construct($slug = 'latest', $name = 'Latest', $path = NULL, $branch = 'master', $redirects = []) {
    $this->slug = $slug;
    $this->name = $name;
    $this->path = $path ?? $slug;
    $this->branch = $branch;
    $this->setupRedirects($redirects);
  }

  /**
   * @param $redirects array|string
   *   e.g. "latest"
   */
  private function setupRedirects($redirects) {
    // wrap $aliases in array, if necessary
    if (!is_array($redirects)) {
      $redirects = array($redirects);
    }

    // Remove alias for $path if it exists
    unset($redirects[$this->path]);

    // Add alias for $branch (e.g. so urls with "master" will work correctly)
    $redirects[] = $this->branch;

    // Add alias for $slug (e.g. so urls with "4.7" will work correctly)
    $redirects[] = $this->slug;

    // Make sure each alias is URL-safe
    foreach ($redirects as &$redirect) {
      $redirect = StringTools::urlSafe($redirect);
    }

    // Make sure we don't have any duplicate branches
    $this->redirects = array_unique($redirects);
  }

  /**
   * Gives an array of all unique strings that can be used to describe this
   * version, including the path plus any aliases.
   *
   * @return array
   *   Array of strings (without keys)
   */
  public function allDescriptors() {
    $result = $this->redirects;
    $result[] = $this->path;
    return array_unique($result);
  }


  /**
   * Check this version for any problems in the way it's defined.
   *
   * If validation succeeds, this function returns nothing
   *
   * @throws \Exception
   *   If validation fails
   */
  public function validate() {
    if (preg_match("#/#", $this->branch)) {
      throw new \Exception("Branch name can not contain a forward slash.");
    }
  }

}
