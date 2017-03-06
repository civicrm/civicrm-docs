<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Library;

/**
 * @TODO Make the errors that will occur if the slugs don't pass the regexp
 * more obvious to the user
 */
class PublishController extends Controller {

  /**
   * @Route("/admin/publish{identifier}" , requirements={"identifier": ".*"})
   */
  public function PublishAction(Request $request, $identifier) {
    /** @var \AppBundle\Utils\Publisher $publisher */
    $publisher = $this->get('publisher');
    $bookSlug = Library::parseIdentifier($identifier)['bookSlug'];
    if ($bookSlug) {
      $publisher->publish($identifier);
    }
    else {
      $publisher->addMessage('INFO', "Publish action called without a book "
          . "specified, thus attempting to publish all books.");
      $publisher->addMessage('CRITICAL', "Publishing all books it not "
          . "supported through the web interface because it has the potential "
          . "to really slow down the server. If you want to publish all books "
          . "you can run 'docs:publish' from the command line interface.");
    }
    $content['messages'] = $publisher->getMessages();
    return $this->render('AppBundle:Publish:publish.html.twig', $content);
  }

  /**
   * @Route("/admin/listen")
   */
  public function ListenAction(Request $request) {
    $body = $request->getContent();
    $event = $request->headers->get('X-GitHub-Event');
    $payload = json_decode($body);

    $processor = $this->get('github.hook.processor');
    $processor->process($event, $payload);
    if (!$processor->published) {
      return new Response('Something went wrong during publishing.', 200); // @TODO Add more appropriate error code
    }
    $messages = $processor->getMessages();
    $subject = $processor->getSubject();
    $recipients = $processor->getRecipients();

    $mail = \Swift_Message::newInstance()
        ->setSubject("[CiviCRM docs] $subject")
        ->setFrom('no-reply@civicrm.org')
        ->setTo($recipients)
        ->setBody(
            $this->renderView('Emails/notify.html.twig',
                array(
                  'branch' => $processor->publisher->branch,
                  'book' => $processor->publisher->book,
                  'lang' => $processor->publisher->lang,
                  'messages' => $processor->publisher->getMessages(),
                )
            ), 'text/html'
        );
    $this->get('mailer')->send($mail);
    return new Response($subject, 200);
  }

}
