<?php

namespace AppBundle\Controller;

use AppBundle\Utils\Publisher;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Library;


class PublishController extends Controller {

  /**
   * @var Publisher
   */
  private $publisher;

  /**
   * @var bool TRUE if the book was published without any errors
   */
  private $publishSuccess;

  /**
   * @param string $identifier
   *
   * @return Response
   *
   * @Route("/admin/publish{identifier}" , requirements={"identifier": ".*"})
   */
  public function PublishAction($identifier) {
    $this->publisher = $this->get('publisher');
    $bookSlug = Library::parseIdentifier($identifier)['bookSlug'];
    if ($bookSlug) {
      $this->publishSuccess = $this->publisher->publish($identifier);
    }
    else {
      $this->publisher->addMessage('INFO', "Publish action called without a book "
          . "specified, thus attempting to publish all books.");
      $this->publisher->addMessage('CRITICAL', "Publishing all books it not "
          . "supported through the web interface because it has the potential "
          . "to really slow down the server. If you want to publish all books "
          . "you can run 'docs:publish' from the command line interface.");
    }
    $content['identifier'] = trim($identifier, "/");
    $content['messages'] = $this->publisher->getMessages();
    return $this->render('AppBundle:Publish:publish.html.twig', $content);
  }

  /**
   * @param Request $request
   *
   * @return Response
   *
   * @Route("/admin/listen")
   */
  public function ListenAction(Request $request) {
    $body = $request->getContent();
    $event = $request->headers->get('X-GitHub-Event');
    $processor = $this->get('github.hook.processor');
    try {
      $processor->process($event, json_decode($body));
    }
    catch (\Exception $e) {
      $response = "CRITICAL - Skipping the publishing process due to the "
          . "following reason: " . $e->getMessage();
      return new Response($response, 200);
    }
    $library = $this->get('library');
    $identifiers = $library->getIdentifiersByRepo($processor->repo);
    if ($identifiers) {
      $this->publisher = $this->get('publisher');
      foreach ($identifiers as $identifier) {
        $fullIdentifier = "{$identifier}/{$processor->branch}";
        $this->publisher->publish($fullIdentifier);
        $this->sendEmail($fullIdentifier);
      }
      $response = $this->publisher->getMessagesInPlainText();
    }
    else {
      $response = "CRITICAL - No books found which match {$processor->repo}";
    }

    return new Response($response, 200);
  }

  /**
   * Send notification emails after publishing
   *
   * @param string $identifier
   */
  private function sendEmail(string $identifier) {

    /**
     * Array of strings for email addresses that should receive the
     * notification email. If none are specified, then the email will be sent to
     * all addresses set in the book's yaml configuration
    */
    $extraRecipients = $this->get('github.hook.processor')->recipients;
    $library = $this->get('library');
    $messages = $this->get('publisher')->getMessages();
    $parts = $library::parseIdentifier($identifier);
    $book = $library->getBookBySlug($parts['bookSlug']);
    $language = $book->getLanguageByCode($parts['languageCode']);
    $version = $language->getVersionByDescriptor($parts['versionDescriptor']);
    $webPath = sprintf('%s/%s/%s', $book->slug, $language->code, $version->branch);

    $subject = "Publishing Successful";
    $recipients = array_unique(array_merge($extraRecipients, $language->watchers));

    $renderParams = [
      'publishURLBase' => $webPath,
      'status'         => $subject,
      'messages'       => $messages,
    ];
    $body = $this->renderView('AppBundle:Emails:notify.html.twig', $renderParams);
    $mail = \Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom('no-reply@civicrm.org', "CiviCRM docs")
        ->setTo($recipients)
        ->setBody($body, 'text/html');

    $this->get('mailer')->send($mail);
  }

}
