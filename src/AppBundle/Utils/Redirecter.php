<?php

namespace AppBundle\Utils;

use \AppBundle\Model\Library;
use \AppBundle\Utils\Paths;

class Redirecter {

  /**
   * @var string Filesystem path to the directory where all published books go
   */
  public $publishPathRoot;

  /**
   * @var Library $library
   */
  protected $library;

  /**
   * @param Paths $paths
   * @param Library $library
   */
  public function __construct($paths, $library) {
    $this->publishPathRoot = $paths->getPublishPathRoot();
    $this->library = $library;
  }

  /**
   * See if we have a redirect stored for the given URI. If so, return the full
   * path to it as a string (which begins with a slash). If not, return NULL
   *
   * @param string $requestUri
   *   e.g. "/dev/en/latest/my-category/my-page"
   *
   * @return null|string
   *   e.g. "/dev/en/latest/foo/bar"
   */
  public function lookupRedirect($requestUri) {
    // Give up right away if the request contains two dots (for security)
    if (strstr($requestUri, '..')) {
      return NULL;
    }
    return
      $this->lookupVersionRedirect($requestUri) ??
      $this->lookupPageRedirect($requestUri);
  }

  /**
   * Try to find a redirect for the page by looking to see if the version
   * supplied in the request is actually one of the redirects for the versions
   * defined for the book/language
   *
   * @param string $requestUri
   *
   * @return null|string
   */
  private function lookupVersionRedirect($requestUri) {
    $objects = $this->library->getObjectsByIdentifier($requestUri);
    $requestParts = Library::parseIdentifier($requestUri);

    /* @var \AppBundle\Model\Version $version */
    $version = $objects['version'];

    if (!$version) {
      return NULL;
    }
    foreach ($version->redirects as $versionRedirect) {
      if ($requestParts['versionDescriptor'] == $versionRedirect) {
        $requestParts['versionDescriptor'] = $version->path;
        $requestParts['editionIdentifier'] = NULL;
        return '/' . Library::assembleIdentifier($requestParts);
      }
    }
    return NULL;
  }

  /**
   * Try to find a redirect for the page by looking to see if the book has
   * published a `redirects.txt` file which maps the supplied path to another
   * path.
   *
   * @param string $requestUri
   *
   * @return null|string
   */
  private function lookupPageRedirect($requestUri) {
    $requestParts = Library::parseIdentifier($requestUri);
    $edition = $requestParts['editionIdentifier'];
    $path = $requestParts['path'];
    $redirectsFile = $this->publishPathRoot . '/' . $edition . '/redirects.txt';

    // If we don't have all the info we need, then give up
    if ($edition === NULL || $path === NULL || !file_exists($redirectsFile)) {
      return NULL;
    }

    // Look for a redirect
    $redirects = file($redirectsFile);
    foreach ($redirects as $redirect) {
      $rule = StringTools::parseRedirectRule($redirect);
      if (empty($rule)) {
        // Skip any rules that are invalid
        break;
      }
      $ruleMatchesRequest = ($rule['from'] == $path);
      if ($ruleMatchesRequest) {
        if ($rule['type'] == 'internal') {
          $requestParts['path'] = $rule['to'];
          return '/' . Library::assembleIdentifier($requestParts);
        }
        else {
          return $rule['to'];
        }
      }
    }

    return NULL;
  }

}
