<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener {

  /**
   * @var string Filesystem path to the directory where all published books go
   */
  public $redirecter;

  /**
   * @param \AppBundle\Utils\Redirecter $redirecter
   */
  public function __construct($redirecter) {
    $this->redirecter = $redirecter;
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
      $redirect = $this->redirecter->lookupRedirect($requestUri);
      if ($redirect) {
        $response = new RedirectResponse($redirect);
        $event->setResponse($response);
      }
    }
  }

}
