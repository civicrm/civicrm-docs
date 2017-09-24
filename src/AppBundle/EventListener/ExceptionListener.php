<?php

namespace AppBundle\EventListener;

use AppBundle\Utils\StringTools;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \AppBundle\Model\Library;

class ExceptionListener {

  /**
   * @var string Filesystem path to the directory where all published books go
   */
  public $publishPathRoot;

  /**
   * ExceptionListener constructor.
   *
   * @param string $publishPathRoot
   *   Full filesystem path to the directory where books are to be published
   */
  public function __construct($publishPathRoot) {
    $this->publishPathRoot = $publishPathRoot;
  }

  /**
   * This method is called by some sort of Symfony magic for every exception
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   */
  public function onKernelException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof NotFoundHttpException) {
      $requestUri = $event->getRequest()->getRequestUri();
      $redirect = $this->lookupRedirect($requestUri);
      if ($redirect) {
        $response = new RedirectResponse($redirect);
        $event->setResponse($response);
      }
    }
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
  private function lookupRedirect($requestUri) {
    // Give up right away if the request contains two dots (for security)
    if (strstr($requestUri, '..')) {
      return NULL;
    }

    $requestParts = Library::parseIdentifier($requestUri);
    $edition = $requestParts['editionIdentifier'];
    $path = $requestParts['path'];
    $fragment = $requestParts['fragment'] ? "#${requestParts['fragment']}" : '';
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
          return "/$edition/${rule['to']}$fragment";
        }
        else {
          return $rule['to'];
        }
      }
    }

    return NULL;
  }

}
