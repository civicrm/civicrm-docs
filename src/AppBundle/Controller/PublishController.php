<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Library;


class PublishController extends Controller {

  /**
   *
   * @var \AppBundle\Utils\Publisher
   */
  private $publisher;

  /**
   *
   * @var bool TRUE if the book was published without any errors
   */
  private $publishSuccess;

  /**
   * @Route("/admin/publish{identifier}" , requirements={"identifier": ".*"})
   */
  public function PublishAction(Request $request, $identifier) {
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
    $content['messages'] = $this->publisher->getMessages();
    return $this->render('AppBundle:Publish:publish.html.twig', $content);
  }

  /**
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
        $this->publisher->publish("{$identifier}/{$processor->branch}");
        if ($this->publisher->version) {
          $this->sendEmail($processor->recipients);
        }
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
   * @param array $extraRecipients Array of strings for email addresses that
   *                               should receive the notification email. If
   *                               non are specified, then the email will be
   *                               sent to all addresses set in the book's yaml
   *                               configuration.
   */
  private function sendEmail($extraRecipients = array()) {
    $subject = $this->publisher->status;
    $recipients = array_unique(array_merge(
        $extraRecipients,
        $this->publisher->language->watchers));
    $mail = \Swift_Message::newInstance()
        ->setSubject("$subject")
        ->setFrom('no-reply@civicrm.org', "CiviCRM docs")
        ->setTo($recipients)
        ->setBody(
            $this->renderView('AppBundle:Emails:notify.html.twig',
                array(
                    'publishURLBase' => $this->publisher->publishURLBase,
                    'status'         => $this->publisher->status,
                    'messages'       => $this->publisher->messages,
                )
            ), 'text/html'
        );
    $this->get('mailer')->send($mail);
  }

}
